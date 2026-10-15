<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\rag;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds every DeepSeek semantic batch first, then switches the course index.
 */
class course_vector_migrator {
    /**
     * @return array{documents:int,chunks:int,reused:int,generated:int,model:string}
     */
    public function migrate(int $courseid): array {
        global $DB;

        if (!deepseek_semantic_vectorizer::is_configured()) {
            throw new \moodle_exception('rag_deepseek_semantic_not_configured', 'block_ai_tutor');
        }
        if (!hybrid_retriever::course_enabled($courseid)) {
            throw new \moodle_exception('rag_course_disabled_reindex', 'block_ai_tutor');
        }

        $documents = $DB->get_records('block_ai_tutor_doc', [
            'courseid' => $courseid,
            'active' => 1,
        ], 'id ASC');
        $documents = array_values(array_filter($documents, static function($document): bool {
            return !empty($document->markdownfileid)
                && in_array((string)$document->status, ['indexed', 'ready'], true);
        }));
        if (!$documents) {
            throw new \moodle_exception('rag_semantic_migration_no_documents', 'block_ai_tutor');
        }

        $vectorizer = new deepseek_semantic_vectorizer();
        $model = $vectorizer->get_model();
        $prepared = [];
        $stats = [
            'documents' => 0,
            'chunks' => 0,
            'reused' => 0,
            'generated' => 0,
            'model' => $model,
        ];

        try {
            foreach ($documents as $document) {
                $prepared[] = $this->prepare_document($document, $vectorizer, $stats);
            }
        } catch (\Throwable $e) {
            foreach ($prepared as $item) {
                $DB->set_field('block_ai_tutor_batch', 'status', 'failed', ['id' => (int)$item['batchid']]);
                $DB->set_field('block_ai_tutor_batch', 'lasterror',
                    \block_ai_tutor\security_filter::sanitize_error_message($e->getMessage()),
                    ['id' => (int)$item['batchid']]);
            }
            throw $e;
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $now = time();
            foreach ($prepared as $item) {
                $document = $item['document'];
                $batchid = (int)$item['batchid'];
                $DB->set_field('block_ai_tutor_embed', 'active', 0, [
                    'documentid' => (int)$document->id,
                    'active' => 1,
                ]);
                $DB->set_field('block_ai_tutor_batch', 'active', 0, [
                    'documentid' => (int)$document->id,
                    'active' => 1,
                ]);
                $DB->set_field('block_ai_tutor_embed', 'active', 1, ['batchid' => $batchid]);
                $DB->update_record('block_ai_tutor_batch', (object)[
                    'id' => $batchid,
                    'status' => 'active',
                    'active' => 1,
                    'timeactivated' => $now,
                    'timemodified' => $now,
                ]);
                $DB->update_record('block_ai_tutor_doc', (object)[
                    'id' => (int)$document->id,
                    'status' => 'indexed',
                    'processingphase' => 'complete',
                    'chunkcount' => (int)$item['chunkcount'],
                    'embeddingcount' => (int)$item['chunkcount'],
                    'embeddingmodel' => $model,
                    'lasterror' => null,
                    'timeindexed' => $now,
                    'timemodified' => $now,
                ]);
            }
            set_config('course_semantic_model_' . $courseid, $model, 'block_ai_tutor');
            set_config('course_semantic_time_' . $courseid, $now, 'block_ai_tutor');
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }

        return $stats;
    }

    /**
     * @param array{documents:int,chunks:int,reused:int,generated:int,model:string} $stats
     * @return array{document:\stdClass,batchid:int,chunkcount:int}
     */
    private function prepare_document(
        \stdClass $document,
        deepseek_semantic_vectorizer $vectorizer,
        array &$stats
    ): array {
        global $DB;

        $markdownfile = get_file_storage()->get_file_by_id((int)$document->markdownfileid);
        if (!$markdownfile || $markdownfile->is_directory()) {
            throw new \moodle_exception('rag_markdown_missing', 'block_ai_tutor');
        }
        $chunks = (new chunk_builder())->build((string)$markdownfile->get_content());
        if (!$chunks) {
            throw new \moodle_exception('rag_no_useful_chunks', 'block_ai_tutor');
        }

        $vectors = [];
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
                    'model' => $vectorizer->get_model(),
                    'status' => 'ready',
                ],
                'id, embedding_vector',
                IGNORE_MULTIPLE
            );
            $decoded = $reused ? json_decode((string)$reused->embedding_vector, true) : null;
            if (is_array($decoded) && $decoded) {
                $vectors[$index] = $decoded;
                $stats['reused']++;
            } else {
                $missing[$index] = (string)$chunk['content'];
            }
        }
        if ($missing) {
            $totalmissing = count($missing);
            $DB->update_record('block_ai_tutor_doc', (object)[
                'id' => (int)$document->id,
                'processingphase' => 'migrate_semantic_0_' . $totalmissing,
                'timemodified' => time(),
            ]);
            $generated = $vectorizer->vectorize(
                array_values($missing),
                (int)$document->courseid,
                'document',
                static function(int $completed, int $total) use ($DB, $document): void {
                    $DB->update_record('block_ai_tutor_doc', (object)[
                        'id' => (int)$document->id,
                        'processingphase' => 'migrate_semantic_' . $completed . '_' . $total,
                        'timemodified' => time(),
                    ]);
                }
            );
            foreach (array_keys($missing) as $position => $index) {
                $vector = $generated[$position] ?? null;
                if (!is_array($vector) || !$vector) {
                    throw new \moodle_exception('rag_embedding_invalid_vector', 'block_ai_tutor');
                }
                $vectors[$index] = $vector;
                $stats['generated']++;
            }
        }
        ksort($vectors, SORT_NUMERIC);
        if (count($vectors) !== count($chunks)) {
            throw new \moodle_exception('rag_embedding_count_mismatch', 'block_ai_tutor');
        }

        $now = time();
        $transaction = $DB->start_delegated_transaction();
        $batchid = 0;
        try {
            $batchid = $DB->insert_record('block_ai_tutor_batch', (object)[
                'courseid' => (int)$document->courseid,
                'documentid' => (int)$document->id,
                'batchkey' => hash('sha256', $document->id . '|' . $document->contenthash . '|' . $vectorizer->get_model() . '|' . microtime(true)),
                'status' => 'prepared',
                'embeddingmodel' => $vectorizer->get_model(),
                'dimensions' => deepseek_semantic_vectorizer::DIMENSIONS,
                'chunkcount' => count($chunks),
                'active' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            foreach ($chunks as $index => $chunk) {
                $DB->insert_record('block_ai_tutor_embed', (object)[
                    'courseid' => (int)$document->courseid,
                    'contenttype' => 'managedoc_deepseek_semantic',
                    'contentid' => (int)$document->fileid,
                    'title' => (string)$document->filename,
                    'content' => (string)$chunk['content'],
                    'embedding_text' => (string)$chunk['content'],
                    'chunk_index' => (int)$chunk['chunk_index'],
                    'documentid' => (int)$document->id,
                    'page_start' => (int)$chunk['page_start'],
                    'page_end' => (int)$chunk['page_end'],
                    'headingpath' => substr((string)$chunk['headingpath'], 0, 255),
                    'contenthash' => (string)$chunk['contenthash'],
                    'embedding_vector' => json_encode($vectors[$index], JSON_PRESERVE_ZERO_FRACTION),
                    'embedding_model' => $vectorizer->get_model(),
                    'embedding_dimensions' => deepseek_semantic_vectorizer::DIMENSIONS,
                    'embedding_status' => 'ready',
                    'embedding_time' => $now,
                    'batchid' => (int)$batchid,
                    'active' => 0,
                    'timeindexed' => $now,
                ]);
            }
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }

        $stats['documents']++;
        $stats['chunks'] += count($chunks);
        return ['document' => $document, 'batchid' => (int)$batchid, 'chunkcount' => count($chunks)];
    }
}
