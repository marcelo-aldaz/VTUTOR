<?php
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

final class response_integrity_test extends \advanced_testcase {
    public function test_orphaned_leading_fragment_is_removed_without_llm_retry(): void {
        $raw = "...de las lenguas originarias del Ecuador y sus variaciones. En esta unidad se abordan dos grandes ejes: la escritura digital y la diglosia.\n\nPrimer eje: la escritura digital";
        $clean = ai_service::enforce_response_start_integrity($raw, '¿Qué contenidos tiene esta unidad?');
        $this->assertStringStartsWith('En esta unidad se abordan dos grandes ejes', $clean);
        $this->assertStringNotContainsString('...de las lenguas', $clean);
        $this->assertStringContainsString('Primer eje: la escritura digital', $clean);
    }

    public function test_literal_quote_request_preserves_leading_ellipsis(): void {
        $raw = '...tenidas en el presente informe y emitir la reforma.';
        $clean = ai_service::enforce_response_start_integrity($raw, 'Cita literalmente el fragmento recuperado.');
        $this->assertSame($raw, $clean);
    }

    public function test_explicit_continuation_preserves_leading_ellipsis(): void {
        $raw = '...y continúa con el segundo apartado.';
        $clean = ai_service::enforce_response_start_integrity($raw, 'Continúa desde donde terminaste.');
        $this->assertSame($raw, $clean);
    }

    public function test_normal_complete_response_is_unchanged(): void {
        $raw = 'En esta unidad se abordan dos grandes ejes. La escritura digital es el primero.';
        $clean = ai_service::enforce_response_start_integrity($raw, 'Explícame la unidad.');
        $this->assertSame($raw, $clean);
    }

    public function test_ambiguous_ellipsis_without_safe_sentence_boundary_is_unchanged(): void {
        $raw = '...fragmento todavía incompleto';
        $clean = ai_service::enforce_response_start_integrity($raw, 'Explícame la unidad.');
        $this->assertSame($raw, $clean);
    }
}
