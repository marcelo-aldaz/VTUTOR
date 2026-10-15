<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_ai_tutor\local\input_guard
 */
final class input_guard_test extends \advanced_testcase {
    public function test_https_url_accepts_https_and_rejects_unsafe_schemes(): void {
        $this->assertSame(
            'https://example.org/logo.png',
            \block_ai_tutor\local\input_guard::https_url('https://example.org/logo.png')
        );
        $this->assertSame('', \block_ai_tutor\local\input_guard::https_url('http://example.org/logo.png'));
        $this->assertSame('', \block_ai_tutor\local\input_guard::https_url('javascript:alert(1)'));
    }

    public function test_css_color_accepts_conservative_values_and_falls_back(): void {
        $this->assertSame('#123abc', \block_ai_tutor\local\input_guard::css_color('#123ABC', '#000000'));
        $this->assertSame('rgb(10,20,30)', \block_ai_tutor\local\input_guard::css_color('rgb(10, 20, 30)', '#000000'));
        $this->assertSame('#000000', \block_ai_tutor\local\input_guard::css_color('red; background:url(javascript:alert(1))', '#000000'));
        $this->assertSame('#000000', \block_ai_tutor\local\input_guard::css_color('rgb(999,0,0)', '#000000'));
    }
}
