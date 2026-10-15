<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_ai_tutor\rag\chunk_builder
 */
final class chunk_builder_test extends \advanced_testcase {
    public function test_chunks_preserve_page_and_heading_metadata(): void {
        $markdown = "<!-- page: 1 -->\n\n# Unidad 1\n\n"
            . str_repeat('Fundamento pedagógico verificable. ', 30)
            . "\n\n<!-- page: 2 -->\n\n## Evaluación\n\n"
            . str_repeat('Evidencia y retroalimentación. ', 30);
        $builder = new \block_ai_tutor\rag\chunk_builder(900, 1200, 100, 200);
        $chunks = $builder->build($markdown);

        $this->assertNotEmpty($chunks);
        $this->assertSame(1, $chunks[0]['page_start']);
        $this->assertStringContainsString('Unidad 1', $chunks[0]['headingpath']);
        $this->assertNotEmpty($chunks[0]['contenthash']);
    }


    public function test_chunks_never_merge_across_page_boundaries(): void {
        $markdown = "<!-- page: 1 -->\n\n" . str_repeat('Contenido uno. ', 20)
            . "\n\n<!-- page: 2 -->\n\n" . str_repeat('Contenido dos. ', 20);
        $builder = new \block_ai_tutor\rag\chunk_builder(1200, 1600, 100, 100);
        $chunks = $builder->build($markdown);

        $this->assertGreaterThanOrEqual(2, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertSame($chunk['page_start'], $chunk['page_end']);
        }
        $this->assertSame(1, $chunks[0]['page_start']);
        $this->assertSame(2, $chunks[count($chunks) - 1]['page_start']);
    }

    public function test_explicit_zero_overlap_is_respected(): void {
        $this->resetAfterTest(true);
        set_config('rag_chunk_target', 800, 'block_ai_tutor');
        set_config('rag_chunk_max', 800, 'block_ai_tutor');
        set_config('rag_chunk_overlap', 0, 'block_ai_tutor');
        set_config('rag_chunk_min', 100, 'block_ai_tutor');

        $markdown = "<!-- page: 1 -->\n\n" . str_repeat('abcdefghij ', 140);
        $builder = new \block_ai_tutor\rag\chunk_builder();
        $chunks = $builder->build($markdown);

        $this->assertGreaterThanOrEqual(2, count($chunks));
        $this->assertNotSame($chunks[0]['content'], $chunks[1]['content']);
        // With zero overlap, the tail of the first chunk must not be copied as
        // the prefix of the second merely because a legacy default was used.
        $tail = \core_text::substr($chunks[0]['content'], -80);
        $prefix = \core_text::substr($chunks[1]['content'], 0, 80);
        $this->assertNotSame($tail, $prefix);
    }

    public function test_warning_text_is_not_a_chunk(): void {
        $builder = new \block_ai_tutor\rag\chunk_builder(900, 1200, 100, 200);
        $chunks = $builder->build('Advertencia: no se pudo extraer texto legible automáticamente.');
        $this->assertSame([], $chunks);
    }

    public function test_heading_literal_remains_inside_chunk_content(): void {
        $markdown = "<!-- page: 6 -->\n\n## ACUERDA:\n\n"
            . "Expedir la siguiente REFORMA AL ACUERDO MINISTERIAL.\n\n"
            . "Artículo 1.- Objeto.- Regular las ofertas y servicios educativos.";
        $builder = new \block_ai_tutor\rag\chunk_builder(900, 1200, 100, 100);
        $chunks = $builder->build($markdown);

        $this->assertNotEmpty($chunks);
        $joined = implode("\n", array_column($chunks, 'content'));
        $this->assertStringContainsString('ACUERDA:', $joined);
        $this->assertStringContainsString('Artículo 1.- Objeto.-', $joined);
        $this->assertSame(6, $chunks[0]['page_start']);
    }
}
