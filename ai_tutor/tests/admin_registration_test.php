<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** @covers \block_ai_tutor */
final class admin_registration_test extends \advanced_testcase {
    public function test_block_declares_global_configuration(): void {
        $block = new \block_ai_tutor();
        $this->assertTrue($block->has_config());
    }
}
