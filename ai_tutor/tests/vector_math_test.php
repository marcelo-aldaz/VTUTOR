<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_ai_tutor\rag\vector_math
 */
final class vector_math_test extends \advanced_testcase {
    public function test_cosine_similarity_for_equal_vectors(): void {
        $this->assertEqualsWithDelta(
            1.0,
            \block_ai_tutor\rag\vector_math::cosine_similarity([1.0, 2.0], [1.0, 2.0]),
            0.000001
        );
    }

    public function test_cosine_similarity_rejects_incompatible_vectors(): void {
        $this->assertSame(0.0, \block_ai_tutor\rag\vector_math::cosine_similarity([1.0], [1.0, 2.0]));
        $this->assertSame(0.0, \block_ai_tutor\rag\vector_math::cosine_similarity([], []));
    }
}
