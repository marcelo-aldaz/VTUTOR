<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Institutional mini-RAG course isolation regression tests.
 *
 * @covers \block_ai_tutor\rag\hybrid_retriever
 */
final class course_isolation_test extends \advanced_testcase {
    public function test_retriever_never_returns_another_courses_document(): void {
        global $DB;

        $this->resetAfterTest(true);
        set_config('rag_deepseek_semantic_enabled', 0, 'block_ai_tutor');

        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        set_config('course_rag_enabled_' . $coursea->id, 1, 'block_ai_tutor');
        set_config('course_rag_enabled_' . $courseb->id, 1, 'block_ai_tutor');

        $now = time();
        $documenta = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $coursea->id,
            'fileid' => 1000001,
            'filename' => 'curso-a.md',
            'mimetype' => 'text/markdown',
            'filesize' => 100,
            'contenthash' => hash('sha256', 'curso-a'),
            'status' => 'indexed',
            'pagecount' => 1,
            'extractedchars' => 100,
            'chunkcount' => 1,
            'embeddingcount' => 0,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'timeindexed' => $now,
        ]);
        $documentb = $DB->insert_record('block_ai_tutor_doc', (object)[
            'courseid' => $courseb->id,
            'fileid' => 1000002,
            'filename' => 'curso-b.md',
            'mimetype' => 'text/markdown',
            'filesize' => 100,
            'contenthash' => hash('sha256', 'curso-b'),
            'status' => 'indexed',
            'pagecount' => 1,
            'extractedchars' => 100,
            'chunkcount' => 1,
            'embeddingcount' => 0,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'timeindexed' => $now,
        ]);

        $batcha = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $coursea->id,
            'documentid' => $documenta,
            'batchkey' => hash('sha256', 'batch-a'),
            'status' => 'active',
            'embeddingmodel' => 'lexical-local-v1',
            'dimensions' => 0,
            'chunkcount' => 1,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'timeactivated' => $now,
        ]);
        $batchb = $DB->insert_record('block_ai_tutor_batch', (object)[
            'courseid' => $courseb->id,
            'documentid' => $documentb,
            'batchkey' => hash('sha256', 'batch-b'),
            'status' => 'active',
            'embeddingmodel' => 'lexical-local-v1',
            'dimensions' => 0,
            'chunkcount' => 1,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
            'timeactivated' => $now,
        ]);

        $DB->insert_record('block_ai_tutor_embed', (object)[
            'courseid' => $coursea->id,
            'contenttype' => 'document',
            'contentid' => $documenta,
            'title' => 'Curso A',
            'content' => 'El termino exclusivo curriculoazul pertenece al curso A.',
            'chunk_index' => 0,
            'documentid' => $documenta,
            'batchid' => $batcha,
            'contenthash' => hash('sha256', 'chunk-a'),
            'embedding_status' => 'lexical',
            'active' => 1,
            'timeindexed' => $now,
        ]);
        $DB->insert_record('block_ai_tutor_embed', (object)[
            'courseid' => $courseb->id,
            'contenttype' => 'document',
            'contentid' => $documentb,
            'title' => 'Curso B',
            'content' => 'El termino exclusivo fotosintesisverde pertenece al curso B.',
            'chunk_index' => 0,
            'documentid' => $documentb,
            'batchid' => $batchb,
            'contenthash' => hash('sha256', 'chunk-b'),
            'embedding_status' => 'lexical',
            'active' => 1,
            'timeindexed' => $now,
        ]);

        // Historical rows may remain active for preservation, but because they
        // have no institutional document/batch provenance they are outside the
        // Mini-RAG runtime contract and must not poison document/vector parity.
        $DB->insert_record('block_ai_tutor_embed', (object)[
            'courseid' => $coursea->id,
            'contenttype' => 'legacy_course_text',
            'contentid' => 999001,
            'title' => 'Legado histórico',
            'content' => 'Fila heredada preservada y aislada del Mini-RAG institucional.',
            'embedding_status' => 'legacy',
            'active' => 1,
            'timeindexed' => $now - 1000,
        ]);

        // A corrupted or hand-crafted row must never bypass course isolation.
        $corruptid = $DB->insert_record('block_ai_tutor_embed', (object)[
            'courseid' => $coursea->id,
            'contenttype' => 'document',
            'contentid' => $documentb,
            'title' => 'Fila cruzada',
            'content' => 'El termino filtracioncruzada nunca debe recuperarse.',
            'chunk_index' => 1,
            'documentid' => $documentb,
            'batchid' => $batchb,
            'contenthash' => hash('sha256', 'cross-course-chunk'),
            'embedding_status' => 'lexical',
            'active' => 1,
            'timeindexed' => $now,
        ]);

        $retriever = new \block_ai_tutor\rag\hybrid_retriever();
        $ownresult = $retriever->retrieve((int)$coursea->id, 'curriculoazul');
        $crossresult = $retriever->retrieve((int)$coursea->id, 'fotosintesisverde');
        $corruptresult = $retriever->retrieve((int)$coursea->id, 'filtracioncruzada');

        $this->assertTrue($ownresult['hasevidence']);
        $this->assertStringContainsString('curso-a.md', implode(' ', $ownresult['sources']));
        $this->assertStringNotContainsString('curso-b.md', $ownresult['context']);
        $this->assertFalse($crossresult['hasevidence']);
        $this->assertStringNotContainsString('curso-b.md', $crossresult['context']);
        $this->assertFalse($corruptresult['hasevidence']);

        $audit = (new \block_ai_tutor\rag\index_integrity_auditor())->audit_course((int)$coursea->id);
        $this->assertSame(1, $audit['crosscourserows']);
        $this->assertFalse($audit['approved']);

        $DB->delete_records('block_ai_tutor_embed', ['id' => $corruptid]);
        $cleanaudit = (new \block_ai_tutor\rag\index_integrity_auditor())->audit_course((int)$coursea->id);
        $this->assertSame(0, $cleanaudit['crosscourserows']);
        $this->assertSame(1, $cleanaudit['activechunks']);
        $this->assertSame(1, $cleanaudit['activelexical']);
        $this->assertSame(1, $cleanaudit['activelegacy']);
        $this->assertTrue($cleanaudit['parity']);
        $this->assertTrue($cleanaudit['batchdocumentparity']);
        $this->assertTrue($cleanaudit['batchchunkparity']);
        $this->assertTrue($cleanaudit['approved']);
    }
}
