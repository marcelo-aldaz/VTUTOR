<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** Regression tests for per-user, per-course chat history isolation. */
final class history_isolation_test extends \advanced_testcase {
    public function test_get_history_returns_only_current_user_in_current_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $coursea = $this->getDataGenerator()->create_course();
        $courseb = $this->getDataGenerator()->create_course();
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($usera->id, $coursea->id, 'student');
        $this->getDataGenerator()->enrol_user($usera->id, $courseb->id, 'student');
        $this->getDataGenerator()->enrol_user($userb->id, $coursea->id, 'student');

        $now = time();
        foreach ([
            [$coursea->id, $usera->id, 'mine-a'],
            [$coursea->id, $userb->id, 'other-user'],
            [$courseb->id, $usera->id, 'other-course'],
        ] as $row) {
            $DB->insert_record('block_ai_tutor_msg', (object)[
                'courseid' => $row[0],
                'userid' => $row[1],
                'message' => $row[2],
                'response' => 'response-' . $row[2],
                'provider' => 'deepseek',
                'model' => 'deepseek-v4-flash',
                'tokens_used' => null,
                'timecreated' => $now,
            ]);
        }

        $this->setUser($usera);
        $result = \block_ai_tutor\external\get_history::execute((int)$coursea->id, 50);
        $this->assertCount(1, $result['history']);
        $this->assertSame('mine-a', $result['history'][0]['message']);
    }

    public function test_clear_history_deletes_only_current_user_in_current_course(): void {
        global $DB;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($usera->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($userb->id, $course->id, 'student');
        $now = time();
        foreach ([$usera, $userb] as $user) {
            $DB->insert_record('block_ai_tutor_msg', (object)[
                'courseid' => $course->id,
                'userid' => $user->id,
                'message' => 'message-' . $user->id,
                'response' => 'response',
                'provider' => 'deepseek',
                'model' => 'deepseek-v4-flash',
                'tokens_used' => null,
                'timecreated' => $now,
            ]);
        }

        $this->setUser($usera);
        $result = \block_ai_tutor\external\clear_history::execute((int)$course->id);
        $this->assertTrue($result['success']);
        $this->assertFalse($DB->record_exists('block_ai_tutor_msg', [
            'courseid' => $course->id,
            'userid' => $usera->id,
        ]));
        $this->assertTrue($DB->record_exists('block_ai_tutor_msg', [
            'courseid' => $course->id,
            'userid' => $userb->id,
        ]));
    }
}
