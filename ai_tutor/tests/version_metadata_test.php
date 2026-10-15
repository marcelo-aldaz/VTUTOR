<?php
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Ensures distributed version metadata stays installer-safe and runtime-consistent.
 */
final class version_metadata_test extends \advanced_testcase {
    public function test_runtime_identity_matches_version_metadata(): void {
        $this->assertSame(2026082406, \block_ai_tutor\local\product_identity::BUILD);
        $this->assertSame(
            'v10.6.5 STABLE: aislamiento triple y auditoría READ-ONLY',
            \block_ai_tutor\local\product_identity::RELEASE
        );
    }
}
