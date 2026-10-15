<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Remove only inactive index batches after a retention window.
 */
class cleanup_old_index_batches extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('rag_cleanup_task', 'block_ai_tutor');
    }

    public function execute(): void {
        global $DB;

        if (!\block_ai_tutor\local\schema::table_exists('block_ai_tutor_batch')) {
            return;
        }
        $retentiondays = max(7, (int)(get_config('block_ai_tutor', 'rag_batch_retention_days') ?: 14));
        $cutoff = time() - ($retentiondays * DAYSECS);
        $sql = "active = 0 AND timemodified < :cutoff AND status <> :activestatus";
        $batches = $DB->get_records_select('block_ai_tutor_batch', $sql, [
            'cutoff' => $cutoff,
            'activestatus' => 'active',
        ], '', 'id');
        foreach ($batches as $batch) {
            // A corrupted state with active chunks pointing to an inactive batch
            // must be preserved for the READ-ONLY auditor, not made worse by cleanup.
            if ($DB->record_exists('block_ai_tutor_embed', [
                'batchid' => (int)$batch->id,
                'active' => 1,
            ])) {
                debugging(
                    'VTutor cleanup skipped inactive batch with active chunks: ' . (int)$batch->id,
                    DEBUG_DEVELOPER
                );
                continue;
            }
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('block_ai_tutor_embed', ['batchid' => (int)$batch->id, 'active' => 0]);
            $DB->delete_records('block_ai_tutor_batch', ['id' => (int)$batch->id, 'active' => 0]);
            $transaction->allow_commit();
        }
    }
}
