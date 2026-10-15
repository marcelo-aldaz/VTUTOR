<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** @covers \block_ai_tutor\local\config */
final class config_test extends \advanced_testcase {
    public function test_false_sentinel_uses_default_but_explicit_zero_is_preserved(): void {
        $this->resetAfterTest(true);
        unset_config('qa_bool', 'block_ai_tutor');
        unset_config('qa_int', 'block_ai_tutor');
        $this->assertTrue(\block_ai_tutor\local\config::bool('qa_bool', true));
        $this->assertSame(30, \block_ai_tutor\local\config::int('qa_int', 30));

        set_config('qa_bool', 0, 'block_ai_tutor');
        set_config('qa_int', 0, 'block_ai_tutor');
        $this->assertFalse(\block_ai_tutor\local\config::bool('qa_bool', true));
        $this->assertSame(0, \block_ai_tutor\local\config::int('qa_int', 30));
    }

    public function test_runtime_defaults_that_should_be_on_are_on_when_unset(): void {
        $this->resetAfterTest(true);
        unset_config('security_filter_enabled', 'block_ai_tutor');
        unset_config('routes_enabled', 'block_ai_tutor');
        $this->assertTrue(\block_ai_tutor\security_filter::enabled());
        $this->assertTrue(\block_ai_tutor\route_engine::enabled());
    }
}
