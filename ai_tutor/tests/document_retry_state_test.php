<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** Tests for safe document retry routing. */
final class document_retry_state_test extends \advanced_testcase {
    public function test_embedding_failure_reuses_reviewed_markdown(): void {
        $document = (object)[
            'status' => 'embedding_failed',
            'markdownfileid' => 42,
        ];
        $this->assertSame('index', \block_ai_tutor\document\document_processor::retry_stage($document));
    }

    public function test_embedding_failure_without_markdown_reextracts(): void {
        $document = (object)[
            'status' => 'embedding_failed',
            'markdownfileid' => null,
        ];
        $this->assertSame('extract', \block_ai_tutor\document\document_processor::retry_stage($document));
    }

    public function test_extraction_failure_reextracts(): void {
        $document = (object)[
            'status' => 'extraction_failed',
            'markdownfileid' => 42,
        ];
        $this->assertSame('extract', \block_ai_tutor\document\document_processor::retry_stage($document));
    }

    public function test_stale_indexing_reuses_reviewed_markdown(): void {
        $document = (object)[
            'status' => 'indexing',
            'markdownfileid' => 42,
            'timemodified' => 1000,
        ];
        $now = 1000 + \block_ai_tutor\document\document_processor::STALE_INDEXING_SECONDS;
        $this->assertSame('index', \block_ai_tutor\document\document_processor::retry_stage($document, $now));
    }

    public function test_recent_indexing_is_not_retried_concurrently(): void {
        $document = (object)[
            'status' => 'indexing',
            'markdownfileid' => 42,
            'timemodified' => 1000,
        ];
        $this->assertSame('extract', \block_ai_tutor\document\document_processor::retry_stage($document, 1100));
    }
}
