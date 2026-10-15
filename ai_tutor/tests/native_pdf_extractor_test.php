<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for dependency-free PDF extraction.
 *
 * @covers \block_ai_tutor\document\native_pdf_extractor
 * @covers \block_ai_tutor\document\pdf_extractor
 */
final class native_pdf_extractor_test extends \advanced_testcase {
    public function test_native_extractor_reads_uncompressed_text_pages(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->minimal_pdf());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertSame(2, $result['pagecount']);
        $this->assertSame('php_native', $result['extractor']);
        $this->assertStringContainsString('Pregunta uno', $result['pages'][1]);
        $this->assertStringContainsString('Respuesta dos', $result['pages'][2]);
        $this->assertFalse($result['pendingocr']);
    }


    public function test_visible_page_markers_reconcile_scrambled_page_tree_order(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->scrambled_quipux_style_pdf());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertSame(2, $result['pagecount']);
        $this->assertStringContainsString('Encabezado pagina uno', $result['pages'][1]);
        $this->assertStringContainsString('Articulo 1 pagina dos', $result['pages'][2]);
    }

    public function test_pdf_extractor_falls_back_when_binary_is_missing(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');
        set_config('pdf_native_fallback', 1, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->minimal_pdf());
        $result = (new \block_ai_tutor\document\pdf_extractor('/path/that/does/not/exist'))->extract($file);

        $this->assertSame('php_native', $result['extractor']);
        $this->assertGreaterThan(100, $result['extractedchars']);
    }

    public function test_native_extractor_expands_pdf_object_streams(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->object_stream_pdf());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertSame(1, $result['pagecount']);
        $this->assertStringContainsString('Pregunta codificada', $result['pages'][1]);
        $this->assertFalse($result['pendingocr']);
    }

    public function test_native_extractor_reads_form_xobject_text_with_inherited_resources(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->form_xobject_pdf());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertSame(1, $result['pagecount']);
        $this->assertStringContainsString('ACUERDO Nro. PRUEBA-2026-00001-A', $result['pages'][1]);
        $this->assertStringContainsString('ACUERDA', $result['pages'][1]);
        $this->assertStringContainsString('Articulo 1 Objeto', $result['pages'][1]);
    }

    public function test_pagecount_uses_page_tree_not_orphan_page_objects(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->pdf_with_orphan_page_object());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertSame(1, $result['pagecount']);
        $this->assertCount(1, $result['pages']);
        $this->assertStringContainsString('Pagina real', $result['pages'][1]);
        $this->assertStringNotContainsString('Pagina huerfana', $result['pages'][1]);
    }


    public function test_font_without_tounicode_uses_its_raw_text_not_unrelated_global_map(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->mixed_font_mapping_pdf());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertStringContainsString('ACUERDA', $result['pages'][1]);
        $this->assertStringContainsString('Articulo 1 Objeto', $result['pages'][1]);
    }

    public function test_literal_text_respects_simple_font_differences_encoding(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->differences_encoded_heading_pdf());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertStringContainsString('ACUERDA', $result['pages'][1]);
    }

    public function test_actualtext_is_preserved_when_visible_glyphs_are_not_semantic_text(): void {
        $this->resetAfterTest(true);
        set_config('rag_max_pdf_pages', 40, 'block_ai_tutor');

        $file = $this->create_pdf_file($this->actualtext_pdf());
        $result = (new \block_ai_tutor\document\native_pdf_extractor())->extract($file);

        $this->assertStringContainsString('ACUERDA:', $result['pages'][1]);
    }

    private function create_pdf_file(string $content): \stored_file {
        return get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'block_ai_tutor',
            'filearea' => 'course_document_base',
            'itemid' => 999,
            'filepath' => '/',
            'filename' => 'native-test.pdf',
        ], $content);
    }

    private function form_xobject_pdf(): string {
        $formtext = "BT /F1 12 Tf (ACUERDO Nro. PRUEBA-2026-00001-A) Tj T* "
            . "(ACUERDA) Tj T* (Articulo 1 Objeto) Tj ET\n";
        $formstream = gzcompress($formtext);
        $pagecontent = "/Fm1 Do\n";

        return "%PDF-1.5\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 "
            . "/Resources << /XObject << /Fm1 5 0 R >> >> >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($pagecontent) . " >>\nstream\n"
            . $pagecontent . "endstream\nendobj\n"
            . "5 0 obj\n<< /Type /XObject /Subtype /Form /BBox [0 0 600 800] "
            . "/Length " . strlen($formstream) . " /Filter /FlateDecode >>\nstream\n"
            . $formstream . "\nendstream\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    private function pdf_with_orphan_page_object(): string {
        $real = "BT /F1 12 Tf (Pagina real con contenido valido y suficiente para lectura.) Tj ET\n";
        $orphan = "BT /F1 12 Tf (Pagina huerfana no perteneciente al arbol.) Tj ET\n";
        return "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($real) . " >>\nstream\n"
            . $real . "endstream\nendobj\n"
            . "8 0 obj\n<< /Type /Page /Contents 9 0 R >>\nendobj\n"
            . "9 0 obj\n<< /Length " . strlen($orphan) . " >>\nstream\n"
            . $orphan . "endstream\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }


    private function scrambled_quipux_style_pdf(): string {
        $pageone = "BT /F1 12 Tf (Encabezado pagina uno 1/2) Tj ET\n";
        $pagetwo = "BT /F1 12 Tf (ACUERDA Articulo 1 pagina dos 2/2) Tj ET\n";
        // Deliberately reverse /Kids order. Visible N/TOTAL markers are the
        // authoritative provenance signal for this producer-style fixture.
        return "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [5 0 R 3 0 R] /Count 2 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($pageone) . " >>\nstream\n"
            . $pageone . "endstream\nendobj\n"
            . "5 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 6 0 R >>\nendobj\n"
            . "6 0 obj\n<< /Length " . strlen($pagetwo) . " >>\nstream\n"
            . $pagetwo . "endstream\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    private function minimal_pdf(): string {
        $pageone = str_repeat('Pregunta uno con texto seleccionable. ', 5);
        $pagetwo = str_repeat('Respuesta dos con evidencia documental. ', 5);
        return "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R 5 0 R] /Count 2 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>\nendobj\n"
            . "4 0 obj\n<< /Length " . (strlen($pageone) + 20) . " >>\nstream\n"
            . "BT /F1 12 Tf (" . $pageone . ") Tj ET\nendstream\nendobj\n"
            . "5 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 6 0 R >>\nendobj\n"
            . "6 0 obj\n<< /Length " . (strlen($pagetwo) + 20) . " >>\nstream\n"
            . "BT /F1 12 Tf (" . $pagetwo . ") Tj ET\nendstream\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    private function object_stream_pdf(): string {
        $page = str_repeat('Pregunta codificada con texto seleccionable. ', 8);
        $bodies = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>',
        ];
        $header = '';
        $payload = '';
        foreach ($bodies as $objectid => $body) {
            $header .= $objectid . ' ' . strlen($payload) . ' ';
            $payload .= $body . "\n";
        }
        $objectstream = $header . $payload;
        $compressed = gzcompress($objectstream);
        $pagecontent = "BT /F1 12 Tf (" . $page . ") Tj ET\n";
        $compressedpage = gzcompress($pagecontent);

        return "%PDF-1.5\n"
            . "10 0 obj\n"
            . "<< /Type /ObjStm /N 3 /First " . strlen($header)
            . " /Length " . strlen($compressed) . " /Filter /FlateDecode >>\n"
            . "stream\n" . $compressed . "\nendstream\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($compressedpage) . " /Filter /FlateDecode >>\nstream\n"
            . $compressedpage . "\nendstream\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }


    private function differences_encoded_heading_pdf(): string {
        $content = "BT /F1 12 Tf (ABCDEFG) Tj ET\n";
        return "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n"
            . "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding << /Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [65 /A /C /U /E /R /D /A] >> >>\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    private function actualtext_pdf(): string {
        $content = "/Span << /ActualText <FEFF0041004300550045005200440041003A> >> BDC BT /F1 12 Tf (xxxxxxx) Tj ET EMC\n";
        return "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }

    private function mixed_font_mapping_pdf(): string {
        // F1 has a deliberately unrelated ToUnicode map. F2 has no ToUnicode
        // and writes plain literal strings. The extractor must decode F2 from
        // its own bytes instead of applying F1's global merged map.
        $content = "BT /F2 12 Tf (ACUERDA) Tj T* (Articulo 1 Objeto) Tj ET\n";
        $cmap = "1 beginbfchar\n<41> <0058>\nendbfchar\n";
        return "%PDF-1.4\n"
            . "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n"
            . "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n"
            . "3 0 obj\n<< /Type /Page /Parent 2 0 R /Contents 4 0 R >>\nendobj\n"
            . "4 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n"
            . "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /ToUnicode 7 0 R >>\nendobj\n"
            . "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n"
            . "7 0 obj\n<< /Length " . strlen($cmap) . " >>\nstream\n" . $cmap . "endstream\nendobj\n"
            . "trailer\n<< /Root 1 0 R >>\n%%EOF\n";
    }
}
