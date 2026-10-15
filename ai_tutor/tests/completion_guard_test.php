<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Regression tests for provider-side answer truncation.
 *
 * @covers \block_ai_tutor\ai_service
 */
final class completion_guard_test extends \advanced_testcase {
    public function test_hidden_legacy_token_limit_is_clamped(): void {
        $this->assertSame(1200, ai_service::normalize_max_tokens(false));
        $this->assertSame(512, ai_service::normalize_max_tokens(64));
        $this->assertSame(1200, ai_service::normalize_max_tokens(1200));
        $this->assertSame(4096, ai_service::normalize_max_tokens(9000));
    }

    public function test_only_length_finish_reasons_request_continuation(): void {
        $this->assertTrue(ai_service::completion_needs_continuation('length'));
        $this->assertTrue(ai_service::completion_needs_continuation('max_tokens'));
        $this->assertFalse(ai_service::completion_needs_continuation('stop'));
        $this->assertFalse(ai_service::completion_needs_continuation(null));
    }

    public function test_continuation_removes_repeated_tail_without_cutting_words(): void {
        $first = 'La tecnología se elige después de comprender qué cuesta aprender y cómo se enseña mejor.';
        $next = 'qué cuesta aprender y cómo se enseña mejor. Luego se selecciona la herramienta adecuada.';
        $merged = ai_service::merge_response_segments($first, $next);

        $this->assertSame(
            'La tecnología se elige después de comprender qué cuesta aprender y cómo se enseña mejor. '
                . 'Luego se selecciona la herramienta adecuada.',
            $merged
        );
        $this->assertSame(1, substr_count($merged, 'qué cuesta aprender'));
    }

    public function test_restarted_continuation_replaces_partial_duplicate_paragraph(): void {
        $first = 'Con gusto te informo. Para darte contexto operativo: esta release integra tres componentes clave: '
            . '1) DeepSe';
        $next = 'Para darte contexto operativo: esta release integra tres componentes clave: '
            . '1) DeepSeek como motor LLM de las respuestas; 2) RAG para recuperación documental; '
            . 'y 3) Human-in-the-Loop para supervisión humana.';

        $merged = ai_service::merge_response_segments($first, $next);

        $this->assertSame(1, substr_count($merged, 'Para darte contexto operativo'));
        $this->assertStringContainsString('1) DeepSeek como motor LLM', $merged);
        $this->assertStringNotContainsString('1) DeepSePara darte', $merged);
    }
    public function test_continuation_budget_is_bounded(): void {
        $this->assertSame(512, ai_service::continuation_token_budget(512));
        $this->assertSame(600, ai_service::continuation_token_budget(1200));
        $this->assertSame(600, ai_service::continuation_token_budget(4096));
    }

    public function test_bounded_completion_removes_only_incomplete_tail(): void {
        $partial = 'Primero se explica el concepto. Después se presenta un ejemplo completo. Finalmente se analiza un aspecto que';
        $final = ai_service::finalize_bounded_completion($partial);
        $this->assertSame('Primero se explica el concepto. Después se presenta un ejemplo completo.', $final);
    }

    public function test_bounded_completion_keeps_complete_response_unchanged(): void {
        $complete = 'La respuesta termina de forma completa.';
        $this->assertSame($complete, ai_service::finalize_bounded_completion($complete));
    }

}
