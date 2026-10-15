<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Regression tests for Human-in-the-Loop route validation isolation.
 *
 * @covers \block_ai_tutor\route_engine
 */
final class route_validation_isolation_test extends \advanced_testcase {
    public function test_route_validation_requires_the_same_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();

        $now = time();
        $logid = $DB->insert_record('block_ai_tutor_route_log', (object)[
            'courseid' => $courseb->id,
            'userid' => $student->id,
            'routekey' => 'understanding',
            'routename' => 'Comprensión inicial del tema',
            'triggerreason' => 'Prueba de aislamiento',
            'status' => 'suggested',
            'teachervalidated' => 0,
            'human_decision' => null,
            'validatedby' => null,
            'timevalidated' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $this->expectException(\dml_missing_record_exception::class);
        route_engine::validate_route((int)$logid, 'validated', (int)$coursea->id);
    }

    public function test_unknown_decision_is_rejected_instead_of_validated(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->expectException(\invalid_parameter_exception::class);
        route_engine::validate_route(123, 'approve_anything', 456);
    }

    public function test_route_log_deduplicates_same_suggested_route_in_same_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        set_config('routes_dedup_minutes', 30, 'block_ai_tutor');

        $engine = new route_engine((int)$course->id, (int)$student->id);
        $route = $engine->suggest_route('No entiendo esta actividad', 'tutorial');
        $firstid = $engine->log_route($route, 'test');
        $secondid = $engine->log_route($route, 'test');

        $this->assertSame($firstid, $secondid);
        $this->assertSame(1, $DB->count_records('block_ai_tutor_route_log', [
            'courseid' => $course->id,
            'userid' => $student->id,
            'routekey' => $route->routekey,
        ]));
    }

    public function test_route_validation_succeeds_in_same_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $now = time();
        $logid = $DB->insert_record('block_ai_tutor_route_log', (object)[
            'courseid' => $course->id,
            'userid' => $student->id,
            'routekey' => 'understanding',
            'routename' => 'Comprensión inicial del tema',
            'triggerreason' => 'Prueba positiva HITL',
            'status' => 'suggested',
            'teachervalidated' => 0,
            'human_decision' => null,
            'validatedby' => null,
            'timevalidated' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $this->assertTrue(route_engine::validate_route((int)$logid, 'validated', (int)$course->id));
        $record = $DB->get_record('block_ai_tutor_route_log', ['id' => $logid], '*', MUST_EXIST);
        $this->assertSame('validated', $record->status);
        $this->assertSame(1, (int)$record->teachervalidated);
        $this->assertSame('validated', $record->human_decision);
        $this->assertGreaterThan(0, (int)$record->validatedby);
        $this->assertGreaterThan(0, (int)$record->timevalidated);
    }

}
