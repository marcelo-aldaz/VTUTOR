<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Institutional reindexing preflight regression tests.
 *
 * @covers ::block_ai_tutor_get_reindex_readiness
 */
final class reindex_readiness_test extends \advanced_testcase {
    public function test_preflight_requires_explicit_course_activation_and_a_course_file(): void {
        $this->resetAfterTest(true);
        set_config('internal_rag_enabled', 1, 'block_ai_tutor');
        $course = $this->getDataGenerator()->create_course();

        $missingboth = block_ai_tutor_get_reindex_readiness((int)$course->id);
        $this->assertFalse($missingboth['ready']);
        $this->assertSame('course_and_documents_missing', $missingboth['code']);
        $this->assertSame(0, $missingboth['filecount']);

        set_config('course_rag_enabled_' . $course->id, 1, 'block_ai_tutor');
        $missingfile = block_ai_tutor_get_reindex_readiness((int)$course->id);
        $this->assertFalse($missingfile['ready']);
        $this->assertSame('documents_missing', $missingfile['code']);

        $this->create_course_file((int)$course->id, 'manual.md');
        $ready = block_ai_tutor_get_reindex_readiness((int)$course->id);
        $this->assertTrue($ready['ready']);
        $this->assertSame('ready', $ready['code']);
        $this->assertSame(1, $ready['filecount']);
    }

    public function test_preflight_never_counts_another_courses_files(): void {
        $this->resetAfterTest(true);
        set_config('internal_rag_enabled', 1, 'block_ai_tutor');
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        set_config('course_rag_enabled_' . $coursea->id, 1, 'block_ai_tutor');
        set_config('course_rag_enabled_' . $courseb->id, 1, 'block_ai_tutor');
        $this->create_course_file((int)$courseb->id, 'solo-curso-b.md');

        $result = block_ai_tutor_get_reindex_readiness((int)$coursea->id);

        $this->assertFalse($result['ready']);
        $this->assertSame('documents_missing', $result['code']);
        $this->assertSame(0, $result['filecount']);
    }

    private function create_course_file(int $courseid, string $filename): void {
        get_file_storage()->create_file_from_string([
            'contextid' => \context_course::instance($courseid)->id,
            'component' => 'block_ai_tutor',
            'filearea' => 'course_document_base',
            'itemid' => $courseid,
            'filepath' => '/',
            'filename' => $filename,
        ], str_repeat('Contenido institucional verificable. ', 5));
    }
}
