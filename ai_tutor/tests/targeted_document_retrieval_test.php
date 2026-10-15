<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Regression tests for explicit filename and page-aware retrieval.
 *
 * @covers \block_ai_tutor\rag\hybrid_retriever
 */
final class targeted_document_retrieval_test extends \advanced_testcase {
    public function test_explicit_filename_and_page_force_course_local_document_scope(): void {
        global $DB;

        $this->resetAfterTest(true);
        set_config('rag_deepseek_semantic_enabled', 0, 'block_ai_tutor');
        $course = $this->getDataGenerator()->create_course();
        set_config('course_rag_enabled_' . $course->id, 1, 'block_ai_tutor');
        set_config('course_rag_mode_' . $course->id, 'strict', 'block_ai_tutor');
        $now = time();

        $doca = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $course->id, 'fileid' => 2000001,
            'filename' => 'MINEDUC-MINEDUC-2025-00010-A.pdf',
            'mimetype' => 'application/pdf', 'filesize' => 1000,
            'contenthash' => hash('sha256', 'doc-a'), 'status' => 'indexed',
            'pagecount' => 2, 'extractedchars' => 500, 'chunkcount' => 2,
            'embeddingcount' => 0, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeindexed' => $now,
        ]);
        $docb = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $course->id, 'fileid' => 2000002,
            'filename' => 'OTRO-DOCUMENTO.pdf',
            'mimetype' => 'application/pdf', 'filesize' => 1000,
            'contenthash' => hash('sha256', 'doc-b'), 'status' => 'indexed',
            'pagecount' => 1, 'extractedchars' => 500, 'chunkcount' => 1,
            'embeddingcount' => 0, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeindexed' => $now,
        ]);
        $batcha = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $course->id, 'documentid' => $doca,
            'batchkey' => hash('sha256', 'batch-a-target'), 'status' => 'active',
            'embeddingmodel' => 'lexical-local-v1', 'dimensions' => 0,
            'chunkcount' => 2, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeactivated' => $now,
        ]);
        $batchb = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $course->id, 'documentid' => $docb,
            'batchkey' => hash('sha256', 'batch-b-target'), 'status' => 'active',
            'embeddingmodel' => 'lexical-local-v1', 'dimensions' => 0,
            'chunkcount' => 1, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeactivated' => $now,
        ]);
        foreach ([
            [$doca, $batcha, 1, 'Considerandos iniciales de la primera página del instrumento ministerial.'],
            [$doca, $batcha, 2, 'ACUERDA. Artículo 1. Disposición resolutiva específica del instrumento.'],
            [$docb, $batchb, 1, 'Encabezado muy relevante pero perteneciente a otro documento del mismo curso.'],
        ] as $index => [$docid, $batchid, $page, $content]) {
            $DB->insert_record('block_ai_tutor_embed', (object)[
                'courseid' => $course->id, 'contenttype' => 'document',
                'contentid' => $docid, 'title' => 'Documento', 'content' => $content,
                'chunk_index' => $index, 'documentid' => $docid, 'batchid' => $batchid,
                'page_start' => $page, 'page_end' => $page,
                'contenthash' => hash('sha256', 'target-' . $index),
                'embedding_status' => 'lexical', 'active' => 1, 'timeindexed' => $now,
            ]);
        }

        $retriever = new \block_ai_tutor\rag\hybrid_retriever();
        $result = $retriever->retrieve((int)$course->id,
            'Del documento MINEDUC-MINEDUC-2025-00010-A.pdf recupera las primeras líneas de la página 1.');

        $this->assertTrue($result['hasevidence']);
        $this->assertStringContainsString('MINEDUC-MINEDUC-2025-00010-A.pdf, página 1', implode(' ', $result['sources']));
        $this->assertStringContainsString('Considerandos iniciales', $result['context']);
        $this->assertStringNotContainsString('OTRO-DOCUMENTO.pdf', $result['context']);
    }
    public function test_explicit_filename_and_missing_page_returns_no_other_page_as_evidence(): void {
        global $DB;

        $this->resetAfterTest(true);
        set_config('rag_deepseek_semantic_enabled', 0, 'block_ai_tutor');
        $course = $this->getDataGenerator()->create_course();
        set_config('course_rag_enabled_' . $course->id, 1, 'block_ai_tutor');
        set_config('course_rag_mode_' . $course->id, 'strict', 'block_ai_tutor');
        $now = time();

        $docid = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $course->id, 'fileid' => 2000010,
            'filename' => 'TARGET.pdf',
            'mimetype' => 'application/pdf', 'filesize' => 1000,
            'contenthash' => hash('sha256', 'target-missing-page'), 'status' => 'indexed',
            'pagecount' => 2, 'extractedchars' => 300, 'chunkcount' => 1,
            'embeddingcount' => 0, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeindexed' => $now,
        ]);
        $batchid = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $course->id, 'documentid' => $docid,
            'batchkey' => hash('sha256', 'target-missing-page-batch'), 'status' => 'active',
            'embeddingmodel' => 'lexical-local-v1', 'dimensions' => 0,
            'chunkcount' => 1, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeactivated' => $now,
        ]);
        $DB->insert_record('block_ai_tutor_embed', (object)[
            'courseid' => $course->id, 'contenttype' => 'document',
            'contentid' => $docid, 'title' => 'TARGET.pdf',
            'content' => 'Contenido exclusivo de la página dos.',
            'chunk_index' => 0, 'documentid' => $docid, 'batchid' => $batchid,
            'page_start' => 2, 'page_end' => 2,
            'contenthash' => hash('sha256', 'target-missing-page-chunk'),
            'embedding_status' => 'lexical', 'active' => 1, 'timeindexed' => $now,
        ]);

        $result = (new \block_ai_tutor\rag\hybrid_retriever())->retrieve(
            (int)$course->id,
            'En TARGET.pdf muestra literalmente el encabezado de la página 1.'
        );

        $this->assertFalse($result['hasevidence']);
        $this->assertSame('', $result['context']);
        $this->assertSame([], $result['sources']);
    }


    public function test_structural_anchor_outranks_filename_noise_inside_target_document(): void {
        global $DB;

        $this->resetAfterTest(true);
        set_config('rag_deepseek_semantic_enabled', 0, 'block_ai_tutor');
        set_config('rag_topk', 5, 'block_ai_tutor');
        $course = $this->getDataGenerator()->create_course();
        set_config('course_rag_enabled_' . $course->id, 1, 'block_ai_tutor');
        set_config('course_rag_mode_' . $course->id, 'strict', 'block_ai_tutor');
        $now = time();

        $docid = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $course->id, 'fileid' => 2000020,
            'filename' => 'MINEDUC-MINEDUC-2025-00010-A.pdf',
            'mimetype' => 'application/pdf', 'filesize' => 1000,
            'contenthash' => hash('sha256', 'structural-target'), 'status' => 'indexed',
            'pagecount' => 5, 'extractedchars' => 1500, 'chunkcount' => 5,
            'embeddingcount' => 0, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeindexed' => $now,
        ]);
        $batchid = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $course->id, 'documentid' => $docid,
            'batchkey' => hash('sha256', 'structural-target-batch'), 'status' => 'active',
            'embeddingmodel' => 'lexical-local-v1', 'dimensions' => 0,
            'chunkcount' => 5, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeactivated' => $now,
        ]);

        $contents = [
            'Considerandos constitucionales de carácter general.',
            'Normas reglamentarias de educación para adultos.',
            'Antecedentes administrativos y memorandos institucionales.',
            'ACUERDA: Reformar el instrumento. Artículo 1.- Objeto.- La presente reforma tiene por objeto ajustar disposiciones específicas.',
            'DISPOSICIONES FINALES y firma de la autoridad.',
        ];
        foreach ($contents as $index => $content) {
            $DB->insert_record('block_ai_tutor_embed', (object)[
                'courseid' => $course->id, 'contenttype' => 'document',
                'contentid' => $docid, 'title' => 'MINEDUC-MINEDUC-2025-00010-A.pdf',
                'content' => $content, 'chunk_index' => $index,
                'documentid' => $docid, 'batchid' => $batchid,
                'page_start' => $index + 1, 'page_end' => $index + 1,
                'contenthash' => hash('sha256', 'structural-target-' . $index),
                'embedding_status' => 'lexical', 'active' => 1, 'timeindexed' => $now,
            ]);
        }

        $result = (new \block_ai_tutor\rag\hybrid_retriever())->retrieve(
            (int)$course->id,
            'En MINEDUC-MINEDUC-2025-00010-A.pdf localiza ACUERDA y Artículo 1 Objeto.'
        );

        $this->assertTrue($result['hasevidence']);
        $this->assertStringContainsString('ACUERDA', $result['context']);
        $this->assertStringContainsString('Artículo 1.- Objeto', $result['context']);
        $this->assertLessThan(
            strpos($result['context'], 'Considerandos constitucionales'),
            strpos($result['context'], 'ACUERDA')
        );
    }


    public function test_structural_hit_includes_adjacent_chunk_from_same_batch(): void {
        global $DB;

        $this->resetAfterTest(true);
        set_config('rag_deepseek_semantic_enabled', 0, 'block_ai_tutor');
        set_config('rag_topk', 4, 'block_ai_tutor');
        $course = $this->getDataGenerator()->create_course();
        set_config('course_rag_enabled_' . $course->id, 1, 'block_ai_tutor');
        set_config('course_rag_mode_' . $course->id, 'strict', 'block_ai_tutor');
        $now = time();

        $docid = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $course->id, 'fileid' => 2000030,
            'filename' => 'LEGAL.pdf', 'mimetype' => 'application/pdf', 'filesize' => 1000,
            'contenthash' => hash('sha256', 'legal-neighbor'), 'status' => 'indexed',
            'pagecount' => 6, 'extractedchars' => 1000, 'chunkcount' => 3,
            'embeddingcount' => 0, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeindexed' => $now,
        ]);
        $batchid = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $course->id, 'documentid' => $docid,
            'batchkey' => hash('sha256', 'legal-neighbor-batch'), 'status' => 'active',
            'embeddingmodel' => 'lexical-local-v1', 'dimensions' => 0,
            'chunkcount' => 3, 'active' => 1, 'timecreated' => $now,
            'timemodified' => $now, 'timeactivated' => $now,
        ]);
        foreach ([
            [0, 5, 'En ejercicio de las atribuciones legales. ACUERDA:'],
            [1, 6, 'Artículo 1.- Objeto.- Regular las ofertas y servicios educativos.'],
            [2, 6, 'Artículo 2.- Ámbito de aplicación.'],
        ] as [$index, $page, $content]) {
            $DB->insert_record('block_ai_tutor_embed', (object)[
                'courseid' => $course->id, 'contenttype' => 'document', 'contentid' => $docid,
                'title' => 'LEGAL.pdf', 'content' => $content, 'chunk_index' => $index,
                'documentid' => $docid, 'batchid' => $batchid,
                'page_start' => $page, 'page_end' => $page,
                'contenthash' => hash('sha256', 'legal-neighbor-' . $index),
                'embedding_status' => 'lexical', 'active' => 1, 'timeindexed' => $now,
            ]);
        }

        $result = (new \block_ai_tutor\rag\hybrid_retriever())->retrieve(
            (int)$course->id,
            'En LEGAL.pdf localiza Artículo 1.- Objeto y el encabezado ACUERDA.'
        );

        $this->assertTrue($result['hasevidence']);
        $this->assertStringContainsString('ACUERDA', $result['context']);
        $this->assertStringContainsString('Artículo 1.- Objeto', $result['context']);
        $this->assertStringContainsString('LEGAL.pdf, página 5', $result['context']);
        $this->assertStringContainsString('LEGAL.pdf, página 6', $result['context']);
    }


}
