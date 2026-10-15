<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Regression tests for complete history and readable output boundaries.
 *
 * @covers \block_ai_tutor\security_filter
 */
final class security_filter_test extends \advanced_testcase {
    public function test_complete_history_is_the_safe_functional_default(): void {
        $this->resetAfterTest(true);
        unset_config('security_store_mode', 'block_ai_tutor');
        unset_config('security_store_truncate_chars', 'block_ai_tutor');
        $response = str_repeat('Una respuesta pedagógica completa. ', 30);

        $record = security_filter::prepare_history_record('Pregunta', $response);

        $this->assertNotNull($record);
        $this->assertSame($response, $record['response']);
    }

    public function test_configured_output_limit_never_ends_inside_a_word(): void {
        $this->resetAfterTest(true);
        set_config('security_filter_enabled', 1, 'block_ai_tutor');
        set_config('security_mask_sensitive', 0, 'block_ai_tutor');
        set_config('security_max_output_chars', 100, 'block_ai_tutor');
        $text = 'Primera explicación completa. Segunda explicación completa que contiene muchos detalles '
            . 'pedagógicos y continúa bastante más allá del límite configurado.';

        $filtered = security_filter::filter_output($text);

        $this->assertStringContainsString('Primera explicación completa.', $filtered);
        $this->assertStringContainsString('Respuesta recortada', $filtered);
        $this->assertStringNotContainsString('deta\n\n', $filtered);
    }
    public function test_document_identifiers_are_not_masked_as_phone_numbers(): void {
        $source = 'MINEDUC-MINEDUC-2025-00010-A.pdf y Acuerdo Nro. MINEDUC-2024-00046-A';
        $this->assertSame($source, \block_ai_tutor\security_filter::mask_sensitive_data($source));
    }

    public function test_standalone_personal_identifiers_are_masked(): void {
        $masked = \block_ai_tutor\security_filter::mask_sensitive_data(
            'Correo persona@example.org, móvil +593 99 123 4567, cédula 0123456789.'
        );
        $this->assertStringContainsString('[correo-oculto]', $masked);
        $this->assertStringContainsString('[telefono-oculto]', $masked);
        $this->assertStringContainsString('[id-oculto]', $masked);
        $this->assertStringNotContainsString('persona@example.org', $masked);
        $this->assertStringNotContainsString('0123456789', $masked);
    }

    public function test_sanitize_error_masks_configured_api_key(): void {
        set_config('api_key', 'very-secret-provider-key-123456789', 'block_ai_tutor');
        $safe = \block_ai_tutor\security_filter::sanitize_error_message(
            'HTTP 401 Authorization: Bearer very-secret-provider-key-123456789'
        );
        $this->assertStringNotContainsString('very-secret-provider-key-123456789', $safe);
    }

}
