<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_ai_tutor\document\markdown_normalizer
 */
final class markdown_normalizer_test extends \advanced_testcase {
    public function test_page_markers_and_repeated_headers_are_preserved_safely(): void {
        $normalizer = new \block_ai_tutor\document\markdown_normalizer();
        $markdown = $normalizer->from_pages([
            1 => "Manual institucional\nUNIDAD 1\nContenido inicial.",
            2 => "Manual institucional\nOBJETIVO\nContenido del objetivo.",
            3 => "Manual institucional\nEVALUACIÓN\nContenido de evaluación.",
        ], 'Guía');

        $this->assertStringContainsString('<!-- page: 1 -->', $markdown);
        $this->assertStringContainsString('<!-- page: 3 -->', $markdown);
        $this->assertSame(0, substr_count($markdown, 'Manual institucional'));
    }

    public function test_legal_marker_with_colon_is_preserved_as_heading(): void {
        $normalizer = new \block_ai_tutor\document\markdown_normalizer();
        $markdown = $normalizer->from_pages([6 => "ACUERDA:\nExpedir la siguiente REFORMA AL ACUERDO MINISTERIAL\nArtículo 1.- Objeto.- Regular las ofertas educativas."], 'LEGAL');

        $this->assertStringContainsString('## ACUERDA:', $markdown);
        $this->assertStringContainsString('Artículo 1.- Objeto.-', $markdown);
    }
}
