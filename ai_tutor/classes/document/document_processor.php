<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\document;

defined('MOODLE_INTERNAL') || die();

/**
 * Two-stage document processor: extract/review, then index.
 */
class document_processor {
    /** Indexing older than this can be recovered safely from reviewed Markdown. */
    public const STALE_INDEXING_SECONDS = 240;

    /**
     * Select the safe retry stage without changing persistent state.
     *
     * Semantic failures occur after extraction and human approval, so their
     * reviewed Markdown must be reused. All other recoverable states restart
     * extraction and return to human review.
     */
    public static function retry_stage(\stdClass $document, ?int $now = null): string {
        $status = (string)($document->status ?? '');
        if (empty($document->markdownfileid)) {
            return 'extract';
        }
        if ($status === 'embedding_failed') {
            return 'index';
        }
        $now = $now ?? time();
        if ($status === 'indexing'
                && $now - (int)($document->timemodified ?? 0) >= self::STALE_INDEXING_SECONDS) {
            return 'index';
        }
        return 'extract';
    }

    public function extract_document(int $documentid): void {
        global $DB;

        $document = $DB->get_record('block_ai_tutor_doc', ['id' => $documentid, 'active' => 1], '*', MUST_EXIST);
        $file = get_file_storage()->get_file_by_id((int)$document->fileid);
        if (!$file || $file->is_directory()) {
            $this->fail_document($document, 'extraction_failed', 'Source file is no longer available.');
            return;
        }

        $document->status = 'processing';
        $document->processingphase = 'extract_read';
        $document->lasterror = null;
        $document->timemodified = time();
        $DB->update_record('block_ai_tutor_doc', $document);

        try {
            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
            $this->validate_signature($file, $extension);
            $result = $this->extract_pages($file, $extension);
            $document->extractormode = (string)($result['extractor'] ?? 'native_text');
            $failurecode = (string)($result['failurecode'] ?? '');
            if ($failurecode !== '') {
                $document->pagecount = (int)$result['pagecount'];
                $document->extractedchars = (int)$result['extractedchars'];
                if ($failurecode === 'image_only') {
                    $this->fail_document($document, 'pending_ocr', get_string('rag_pending_ocr_detail', 'block_ai_tutor'));
                } else if ($failurecode === 'unsupported_text_encoding') {
                    $this->fail_document(
                        $document,
                        'extraction_failed',
                        get_string('rag_pdf_text_encoding_unsupported', 'block_ai_tutor')
                    );
                } else {
                    $this->fail_document(
                        $document,
                        'extraction_failed',
                        get_string('rag_pdf_structure_unsupported', 'block_ai_tutor')
                    );
                }
                return;
            }
            if ((int)$result['extractedchars'] < 100) {
                $this->fail_document($document, 'extraction_failed', get_string('rag_extraction_too_short', 'block_ai_tutor'));
                return;
            }

            $normalizer = new markdown_normalizer();
            $this->touch_phase($document, 'extract_markdown');
            $markdown = $normalizer->from_pages($result['pages'], pathinfo($file->get_filename(), PATHINFO_FILENAME));
            $markdownfile = $this->save_markdown($document, $markdown);
            $document->markdownfileid = (int)$markdownfile->get_id();
            $document->pagecount = (int)$result['pagecount'];
            $document->extractedchars = (int)$result['extractedchars'];
            $document->status = 'pending_review';
            $document->processingphase = 'review_required';
            $document->lasterror = null;
            $document->timemodified = time();
            $DB->update_record('block_ai_tutor_doc', $document);
        } catch (\Throwable $e) {
            $this->fail_document($document, 'extraction_failed', $e->getMessage());
        }
    }

    public function index_document(int $documentid): void {
        global $DB;

        $document = $DB->get_record('block_ai_tutor_doc', ['id' => $documentid, 'active' => 1], '*', MUST_EXIST);
        $markdownfile = $document->markdownfileid
            ? get_file_storage()->get_file_by_id((int)$document->markdownfileid)
            : null;
        if (!$markdownfile) {
            $this->fail_document($document, 'extraction_failed', get_string('rag_markdown_missing', 'block_ai_tutor'));
            return;
        }

        $document->status = 'indexing';
        $document->processingphase = 'index_prepare';
        $document->lasterror = null;
        $document->timemodified = time();
        $DB->update_record('block_ai_tutor_doc', $document);

        $batch = null;
        try {
            $builder = new \block_ai_tutor\rag\chunk_builder();
            $chunks = $builder->build((string)$markdownfile->get_content());
            if (!$chunks) {
                throw new \moodle_exception('rag_no_useful_chunks', 'block_ai_tutor');
            }

            $semantic = \block_ai_tutor\rag\deepseek_semantic_vectorizer::is_configured();
            $vectors = [];
            $dimensions = 0;
            $model = 'lexical-local-v1';
            $embeddingstatus = 'lexical';
            if ($semantic) {
                $this->touch_phase($document, 'index_reuse_scan');
                $provider = new \block_ai_tutor\rag\deepseek_semantic_vectorizer();
                $model = $provider->get_model();
                $dimensions = \block_ai_tutor\rag\deepseek_semantic_vectorizer::DIMENSIONS;
                $missing = [];
                foreach ($chunks as $index => $chunk) {
                    $reused = $DB->get_record_select(
                        'block_ai_tutor_embed',
                        'courseid = :courseid AND contenthash = :contenthash
                            AND embedding_model = :model AND embedding_status = :status
                            AND ' . $DB->sql_isnotempty(
                                'block_ai_tutor_embed',
                                'embedding_vector',
                                true,
                                true
                            ),
                        [
                            'courseid' => (int)$document->courseid,
                            'contenthash' => (string)$chunk['contenthash'],
                            'model' => $model,
                            'status' => 'ready',
                        ],
                        'id, embedding_vector',
                        IGNORE_MULTIPLE
                    );
                    $decoded = $reused ? json_decode((string)$reused->embedding_vector, true) : null;
                    if (is_array($decoded) && $decoded) {
                        $vectors[$index] = $decoded;
                    } else {
                        $missing[$index] = (string)$chunk['content'];
                    }
                }
                if ($missing) {
                    $this->touch_phase($document, 'index_semantic_0_' . count($missing));
                    $generated = $provider->vectorize(
                        array_values($missing),
                        (int)$document->courseid,
                        'document',
                        function(int $completed, int $total) use ($document): void {
                            // This write is also a database heartbeat between
                            // external API batches, preventing an idle Moodle
                            // connection from expiring during long documents.
                            $this->touch_phase($document, 'index_semantic_' . $completed . '_' . $total);
                        }
                    );
                    foreach (array_keys($missing) as $position => $index) {
                        $vectors[$index] = $generated[$position] ?? null;
                    }
                }
                ksort($vectors, SORT_NUMERIC);
                $vectors = array_values($vectors);
                if (count($vectors) !== count($chunks)) {
                    throw new \moodle_exception('rag_embedding_count_mismatch', 'block_ai_tutor');
                }
                foreach ($vectors as $vector) {
                    if (!is_array($vector) || !$vector) {
                        throw new \moodle_exception('rag_embedding_invalid_vector', 'block_ai_tutor');
                    }
                }
                $embeddingstatus = 'ready';
            }

            $now = time();
            $this->touch_phase($document, 'index_persist_batch');
            $batchid = $DB->insert_record('block_ai_tutor_batch', (object)[
                'courseid' => (int)$document->courseid,
                'documentid' => (int)$document->id,
                'batchkey' => hash('sha256', $document->id . '|' . $document->contenthash . '|' . microtime(true)),
                'status' => 'building',
                'embeddingmodel' => $model,
                'dimensions' => $dimensions,
                'chunkcount' => count($chunks),
                'active' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            $batch = $DB->get_record('block_ai_tutor_batch', ['id' => $batchid], '*', MUST_EXIST);

            $transaction = null;
            try {
                $this->touch_phase($document, 'index_activate');
                $transaction = $DB->start_delegated_transaction();
                foreach ($chunks as $index => $chunk) {
                    $DB->insert_record('block_ai_tutor_embed', (object)[
                        'courseid' => (int)$document->courseid,
                        'contenttype' => $semantic ? 'managedoc_deepseek_semantic' : 'managedoc_lexical',
                        'contentid' => (int)$document->fileid,
                        'title' => $document->filename,
                        'content' => (string)$chunk['content'],
                        'embedding_text' => (string)$chunk['content'],
                        'chunk_index' => (int)$chunk['chunk_index'],
                        'documentid' => (int)$document->id,
                        'page_start' => (int)$chunk['page_start'],
                        'page_end' => (int)$chunk['page_end'],
                        'headingpath' => substr((string)$chunk['headingpath'], 0, 255),
                        'contenthash' => (string)$chunk['contenthash'],
                        'embedding_vector' => $semantic
                            ? json_encode($vectors[$index], JSON_PRESERVE_ZERO_FRACTION)
                            : null,
                        'embedding_model' => $model,
                        'embedding_dimensions' => $dimensions,
                        'embedding_status' => $embeddingstatus,
                        'embedding_time' => $semantic ? $now : null,
                        'batchid' => (int)$batchid,
                        'active' => 0,
                        'timeindexed' => $now,
                    ]);
                }

                $DB->set_field('block_ai_tutor_embed', 'active', 0, ['documentid' => $document->id, 'active' => 1]);
                $DB->set_field('block_ai_tutor_batch', 'active', 0, ['documentid' => $document->id, 'active' => 1]);
                $DB->set_field('block_ai_tutor_embed', 'active', 1, ['batchid' => $batchid]);
                $previousdocuments = $DB->get_records_select(
                    'block_ai_tutor_doc',
                    'courseid = :courseid AND filename = :filename AND id <> :id AND active = 1',
                    [
                        'courseid' => $document->courseid,
                        'filename' => $document->filename,
                        'id' => $document->id,
                    ]
                );
                foreach ($previousdocuments as $previous) {
                    $DB->set_field('block_ai_tutor_embed', 'active', 0, ['documentid' => $previous->id]);
                    $DB->set_field('block_ai_tutor_batch', 'active', 0, ['documentid' => $previous->id]);
                    $previous->active = 0;
                    $previous->status = 'inactive';
                    $previous->timemodified = $now;
                    $DB->update_record('block_ai_tutor_doc', $previous);
                }
                $batch->status = 'active';
                $batch->active = 1;
                $batch->timeactivated = $now;
                $batch->timemodified = $now;
                $DB->update_record('block_ai_tutor_batch', $batch);

                $document->status = 'indexed';
                $document->processingphase = 'complete';
                $document->chunkcount = count($chunks);
                $document->embeddingcount = $semantic ? count($vectors) : 0;
                $document->embeddingmodel = $model;
                $document->lasterror = null;
                $document->timeindexed = $now;
                $document->timemodified = $now;
                $DB->update_record('block_ai_tutor_doc', $document);
                $transaction->allow_commit();
            } catch (\Throwable $transactionerror) {
                if ($transaction !== null) {
                    $transaction->rollback($transactionerror);
                }
                throw $transactionerror;
            }
        } catch (\Throwable $e) {
            if ($batch) {
                try {
                    $batch->status = 'failed';
                    $batch->active = 0;
                    $batch->lasterror = \block_ai_tutor\security_filter::sanitize_error_message($e->getMessage());
                    $batch->timemodified = time();
                    $DB->update_record('block_ai_tutor_batch', $batch);
                } catch (\Throwable $ignored) {
                    // Preserve the primary exception; recovery writes are best effort.
                }
            }
            $hasactive = false;
            try {
                $hasactive = $DB->record_exists('block_ai_tutor_embed', [
                    'documentid' => $document->id,
                    'active' => 1,
                ]);
            } catch (\Throwable $ignored) {
                // Never replace the primary failure with a secondary DB read.
            }
            $phase = (string)($document->processingphase ?? 'index_unknown');
            try {
                $this->fail_document(
                    $document,
                    $hasactive ? 'indexed' : 'embedding_failed',
                    '[' . $phase . '] ' . $e->getMessage()
                );
            } catch (\Throwable $persistenceerror) {
                throw $e;
            }
        }
    }

    /**
     * @return array{pages: array<int, string>, pagecount: int, extractedchars: int, pendingocr: bool, extractor: string}
     */
    private function extract_pages(\stored_file $file, string $extension): array {
        if ($extension === 'pdf') {
            return (new pdf_extractor())->extract($file);
        }
        $binary = (string)$file->get_content();
        if ($binary === '') {
            return ['pages' => [1 => ''], 'pagecount' => 1, 'extractedchars' => 0, 'pendingocr' => false];
        }
        switch ($extension) {
            case 'txt':
            case 'md':
                $text = $this->normalize_text($binary);
                break;
            default:
                throw new \moodle_exception('rag_unsupported_extension', 'block_ai_tutor', '', $extension);
        }
        return [
            'pages' => [1 => $text],
            'pagecount' => 1,
            'extractedchars' => \core_text::strlen($text),
            'pendingocr' => false,
            'extractor' => 'native_text',
        ];
    }

    private function save_markdown(\stdClass $document, string $markdown): \stored_file {
        $context = \context_course::instance((int)$document->courseid);
        $fs = get_file_storage();
        $basename = clean_filename(pathinfo($document->filename, PATHINFO_FILENAME));
        $filename = $basename . '-' . substr(hash('sha256', $markdown), 0, 12) . '.md';
        $existing = $fs->get_file(
            $context->id,
            'block_ai_tutor',
            'document_markdown',
            (int)$document->id,
            '/',
            $filename
        );
        $newfile = $existing ?: $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'block_ai_tutor',
            'filearea' => 'document_markdown',
            'itemid' => (int)$document->id,
            'filepath' => '/',
            'filename' => $filename,
        ], $markdown);

        // Create-first cleanup: a failed write can never destroy the previous
        // reviewed Markdown that backs the currently active index.
        foreach ($fs->get_area_files(
            $context->id,
            'block_ai_tutor',
            'document_markdown',
            (int)$document->id,
            'id ASC',
            false
        ) as $oldfile) {
            if ((int)$oldfile->get_id() !== (int)$newfile->get_id()) {
                $oldfile->delete();
            }
        }
        return $newfile;
    }

    private function validate_signature(\stored_file $file, string $extension): void {
        if (!method_exists($file, 'get_content_file_handle')) {
            return;
        }
        $handle = $file->get_content_file_handle();
        if (!is_resource($handle)) {
            return;
        }
        $prefix = (string)fread($handle, 4096);
        fclose($handle);
        if ($extension === 'pdf' && strpos($prefix, '%PDF-') !== 0) {
            throw new \moodle_exception('rag_mime_mismatch', 'block_ai_tutor');
        }
        if (in_array($extension, ['txt', 'md'], true)
            && strpos($prefix, "\0") !== false) {
            throw new \moodle_exception('rag_mime_mismatch', 'block_ai_tutor');
        }
    }

    private function normalize_text(string $text): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/u', ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', (string)$text);
        $text = preg_replace('/\n{3,}/u', "\n\n", (string)$text);
        return trim((string)$text);
    }

    private function fail_document(\stdClass $document, string $status, string $error): void {
        global $DB;
        $document->status = $status;
        $document->lasterror = \block_ai_tutor\security_filter::sanitize_error_message($error);
        $document->timemodified = time();
        $DB->update_record('block_ai_tutor_doc', $document);
    }

    /** Persist an auditable phase and refresh the DB connection heartbeat. */
    private function touch_phase(\stdClass $document, string $phase): void {
        global $DB;
        $document->processingphase = substr($phase, 0, 50);
        $document->timemodified = time();
        $DB->update_record('block_ai_tutor_doc', (object)[
            'id' => (int)$document->id,
            'processingphase' => $document->processingphase,
            'timemodified' => $document->timemodified,
        ]);
    }
}
