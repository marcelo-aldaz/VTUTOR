<?php
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** @covers \block_ai_tutor\local\query_intent */
final class query_intent_test extends \advanced_testcase {
    public function test_pure_greeting_never_requires_document_evidence(): void {
        $this->assertSame(\block_ai_tutor\local\query_intent::CONVERSATIONAL,
            \block_ai_tutor\local\query_intent::classify('Hola'));
        $this->assertFalse(\block_ai_tutor\local\query_intent::should_fail_closed('Hola', true, false));
    }

    public function test_greeting_plus_learning_need_is_not_swallowed_as_smalltalk(): void {
        $this->assertSame(\block_ai_tutor\local\query_intent::LEARNING,
            \block_ai_tutor\local\query_intent::classify('Hola, explícame la diglosia.'));
    }

    public function test_learning_question_falls_back_when_rag_has_no_evidence(): void {
        $this->assertFalse(\block_ai_tutor\local\query_intent::should_fail_closed(
            '¿Qué es la diglosia?', true, false));
    }

    public function test_explicit_documentary_request_fails_closed_without_evidence(): void {
        $question = 'Según MINEDUC-MINEDUC-2025-00010-A.pdf, localiza el Artículo 1.';
        $this->assertSame(\block_ai_tutor\local\query_intent::DOCUMENTARY,
            \block_ai_tutor\local\query_intent::classify($question));
        $this->assertTrue(\block_ai_tutor\local\query_intent::should_fail_closed($question, true, false));
        $this->assertFalse(\block_ai_tutor\local\query_intent::should_fail_closed($question, true, true));
    }

    public function test_generic_concept_about_documents_is_not_forced_into_rag(): void {
        $this->assertSame(\block_ai_tutor\local\query_intent::LEARNING,
            \block_ai_tutor\local\query_intent::classify('¿Qué es un documento académico?'));
    }
}
