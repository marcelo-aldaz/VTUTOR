<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** Contract tests for fields required by the document indexer. */
final class schema_contract_test extends \advanced_testcase {
    public function test_document_table_exposes_processing_phase(): void {
        $method = new \ReflectionMethod(\block_ai_tutor\local\schema::class, 'table_doc');
        $method->setAccessible(true);
        $table = $method->invoke(null);
        $fields = $table->getFields();
        $this->assertArrayHasKey('processingphase', $fields);
        $this->assertArrayHasKey('markdownfileid', $fields);
        $this->assertArrayHasKey('lasterror', $fields);
    }

    public function test_embed_table_exposes_semantic_reuse_fields(): void {
        $method = new \ReflectionMethod(\block_ai_tutor\local\schema::class, 'table_embed');
        $method->setAccessible(true);
        $table = $method->invoke(null);
        $fields = $table->getFields();
        foreach (['courseid', 'contenthash', 'embedding_model', 'embedding_status', 'embedding_vector'] as $field) {
            $this->assertArrayHasKey($field, $fields);
        }
    }
}
