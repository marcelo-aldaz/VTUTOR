<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\document;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistence and queue boundary for institutional course documents.
 */
class document_repository {
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'txt', 'md',
    ];

    /**
     * Synchronize Moodle File API records without processing file contents.
     *
     * @param int $courseid
     * @return array{created: array<int, int>, changed: array<int, int>, unchanged: int, inactive: int, errors: array}
     */
    public function sync_course_files(int $courseid): array {
        global $DB;

        \block_ai_tutor\local\schema::require_tables(['block_ai_tutor_doc', 'block_ai_tutor_embed', 'block_ai_tutor_batch']);
        $context = \context_course::instance($courseid);
        $files = get_file_storage()->get_area_files(
            $context->id,
            'block_ai_tutor',
            'course_document_base',
            $courseid,
            'filename',
            false
        );
        $maxdocuments = max(1, (int)(get_config('block_ai_tutor', 'rag_max_documents') ?: 20));
        if (count($files) > $maxdocuments) {
            throw new \moodle_exception('rag_too_many_documents', 'block_ai_tutor', '', $maxdocuments);
        }

        $result = ['created' => [], 'changed' => [], 'unchanged' => 0, 'inactive' => 0, 'errors' => []];
        $seen = [];
        $seenfilenames = [];
        foreach ($files as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $seen[(int)$file->get_id()] = true;
            $seenfilenames[(string)$file->get_filename()] = true;
            try {
                $this->validate_file($file);
                $existing = $DB->get_record('block_ai_tutor_doc', ['fileid' => $file->get_id()]);
                $hash = (string)$file->get_contenthash();
                $now = time();
                if (!$existing) {
                    $id = $DB->insert_record('block_ai_tutor_doc', (object)[
                        'courseid' => $courseid,
                        'fileid' => (int)$file->get_id(),
                        'filename' => $file->get_filename(),
                        'mimetype' => (string)$file->get_mimetype(),
                        'filesize' => (int)$file->get_filesize(),
                        'contenthash' => $hash,
                        'status' => 'uploaded',
                        'pagecount' => 0,
                        'extractedchars' => 0,
                        'chunkcount' => 0,
                        'embeddingcount' => 0,
                        'active' => 1,
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ]);
                    $result['created'][] = (int)$id;
                    continue;
                }
                if ((string)$existing->contenthash === $hash && (int)$existing->active === 1) {
                    $result['unchanged']++;
                    continue;
                }
                $existing->courseid = $courseid;
                $existing->filename = $file->get_filename();
                $existing->mimetype = (string)$file->get_mimetype();
                $existing->filesize = (int)$file->get_filesize();
                $existing->contenthash = $hash;
                $existing->status = 'uploaded';
                $existing->active = 1;
                $existing->lasterror = null;
                $existing->timemodified = $now;
                $DB->update_record('block_ai_tutor_doc', $existing);
                $result['changed'][] = (int)$existing->id;
            } catch (\Throwable $e) {
                $result['errors'][] = [
                    'filename' => $file->get_filename(),
                    'message' => \block_ai_tutor\security_filter::sanitize_error_message($e->getMessage()),
                ];
            }
        }

        $documents = $DB->get_records('block_ai_tutor_doc', ['courseid' => $courseid, 'active' => 1]);
        foreach ($documents as $document) {
            if (isset($seen[(int)$document->fileid])) {
                continue;
            }
            // If Moodle currently contains a replacement with the same
            // filename but that new file failed validation/extraction, keep
            // the last indexed version active until a valid replacement can
            // be promoted atomically. Removing the old source file must not
            // create an avoidable knowledge outage.
            if (isset($seenfilenames[(string)$document->filename])
                    && (string)$document->status === 'indexed') {
                continue;
            }
            $replacement = $DB->get_record_select(
                'block_ai_tutor_doc',
                'courseid = :courseid AND filename = :filename AND active = 1 AND id <> :id',
                [
                    'courseid' => $courseid,
                    'filename' => $document->filename,
                    'id' => $document->id,
                ],
                '*',
                IGNORE_MULTIPLE
            );
            if ($replacement && (string)$replacement->status !== 'indexed'
                    && (string)$document->status === 'indexed') {
                // Preserve the last valid index while the replacement is being
                // extracted, reviewed or embedded.
                continue;
            }
            $transaction = $DB->start_delegated_transaction();
            $document->active = 0;
            $document->status = 'inactive';
            $document->timemodified = time();
            $DB->update_record('block_ai_tutor_doc', $document);
            $DB->set_field('block_ai_tutor_embed', 'active', 0, ['documentid' => $document->id]);
            $DB->set_field('block_ai_tutor_batch', 'active', 0, ['documentid' => $document->id]);
            $transaction->allow_commit();
            $result['inactive']++;
        }
        return $result;
    }

    /**
     * @return array<int, \stdClass>
     */
    public function get_course_documents(int $courseid): array {
        global $DB;
        if (!\block_ai_tutor\local\schema::table_exists('block_ai_tutor_doc')) {
            return [];
        }
        return $DB->get_records('block_ai_tutor_doc', ['courseid' => $courseid, 'active' => 1], 'filename ASC');
    }

    private function validate_file(\stored_file $file): void {
        $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \moodle_exception('rag_unsupported_extension', 'block_ai_tutor', '', $extension);
        }
        if ((int)$file->get_filesize() <= 0) {
            throw new \moodle_exception('rag_empty_file', 'block_ai_tutor');
        }
        $maxbytes = max(1024, (int)(get_config('block_ai_tutor', 'rag_max_file_bytes') ?: 26214400));
        if ((int)$file->get_filesize() > $maxbytes) {
            throw new \moodle_exception('rag_file_too_large', 'block_ai_tutor', '', display_size($maxbytes));
        }
        $mimetype = strtolower((string)$file->get_mimetype());
        $allowedmimes = [
            'pdf' => ['application/pdf'],
            'txt' => ['text/plain'],
            'md' => ['text/plain', 'text/markdown'],
        ];
        if ($mimetype !== '' && $mimetype !== 'application/octet-stream'
            && isset($allowedmimes[$extension]) && !in_array($mimetype, $allowedmimes[$extension], true)) {
            throw new \moodle_exception('rag_mime_mismatch', 'block_ai_tutor');
        }
    }
}
