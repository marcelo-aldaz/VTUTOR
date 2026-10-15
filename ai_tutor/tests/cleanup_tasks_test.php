<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** Regression tests for safe scheduled cleanup. */
final class cleanup_tasks_test extends \advanced_testcase {
    public function test_inactive_batch_with_active_chunk_is_preserved(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $old = time() - (30 * DAYSECS);
        $documentid = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $course->id,
            'fileid' => 900001,
            'filename' => 'cleanup-test.txt',
            'filesize' => 1,
            'contenthash' => hash('sha256', 'cleanup-test'),
            'status' => 'indexed',
            'pagecount' => 1,
            'extractedchars' => 1,
            'chunkcount' => 1,
            'embeddingcount' => 1,
            'active' => 1,
            'timecreated' => $old,
            'timemodified' => $old,
        ]);
        $batchid = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $course->id,
            'documentid' => $documentid,
            'batchkey' => hash('sha256', 'cleanup-batch'),
            'status' => 'superseded',
            'dimensions' => 2048,
            'chunkcount' => 1,
            'active' => 0,
            'timecreated' => $old,
            'timemodified' => $old,
        ]);
        $DB->insert_record('block_ai_tutor_embed', (object)[
            'courseid' => $course->id,
            'contenttype' => 'managedoc',
            'contentid' => $documentid,
            'content' => 'active evidence',
            'documentid' => $documentid,
            'batchid' => $batchid,
            'embedding_status' => 'ready',
            'active' => 1,
            'timeindexed' => $old,
        ]);

        set_config('rag_batch_retention_days', 7, 'block_ai_tutor');
        (new \block_ai_tutor\task\cleanup_old_index_batches())->execute();

        $this->assertTrue($DB->record_exists('block_ai_tutor_batch', ['id' => $batchid]));
    }

    public function test_old_messages_cleanup_removes_only_expired_messages(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('block_ai_tutor_msg', (object)[
            'courseid' => $course->id,
            'userid' => $user->id,
            'message' => 'old',
            'response' => 'old',
            'timecreated' => time() - (366 * DAYSECS),
        ]);
        $recentid = $DB->insert_record('block_ai_tutor_msg', (object)[
            'courseid' => $course->id,
            'userid' => $user->id,
            'message' => 'recent',
            'response' => 'recent',
            'timecreated' => time(),
        ]);

        (new \block_ai_tutor\task\cleanup_old_messages())->execute();

        $this->assertSame(1, $DB->count_records('block_ai_tutor_msg'));
        $this->assertTrue($DB->record_exists('block_ai_tutor_msg', ['id' => $recentid]));
    }
}
