<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** @covers \block_ai_tutor\rag\hybrid_retriever */
final class rerank_policy_test extends \advanced_testcase {
    public function test_rerank_only_for_ambiguous_or_low_confidence_results(): void {
        $this->resetAfterTest(true);
        set_config('rag_rerank_ambiguity_delta', '0.08', 'block_ai_tutor');
        set_config('rag_rerank_confidence', '0.42', 'block_ai_tutor');

        $this->assertFalse(\block_ai_tutor\rag\hybrid_retriever::should_rerank([
            ['score' => 0.90, 'record' => null],
        ]));
        $this->assertFalse(\block_ai_tutor\rag\hybrid_retriever::should_rerank([
            ['score' => 0.91, 'record' => null],
            ['score' => 0.70, 'record' => null],
        ]));
        $this->assertTrue(\block_ai_tutor\rag\hybrid_retriever::should_rerank([
            ['score' => 0.61, 'record' => null],
            ['score' => 0.57, 'record' => null],
        ]));
        $this->assertTrue(\block_ai_tutor\rag\hybrid_retriever::should_rerank([
            ['score' => 0.35, 'record' => null],
            ['score' => 0.12, 'record' => null],
        ]));
    }
    public function test_semantic_blend_never_suppresses_valid_lexical_evidence(): void {
        // Regression from FINAL QA: 0.15 lexical with semantic weight 0.75 and
        // zero semantic overlap used to become 0.0375, below the 0.12 default
        // threshold. Direct documentary evidence must remain at least 0.15.
        $score = \block_ai_tutor\rag\hybrid_retriever::combine_retrieval_scores(
            0.15,
            0.0,
            true,
            0.75
        );
        $this->assertEqualsWithDelta(0.15, $score, 0.000001);

        // Semantic similarity may still lift a weaker blended result.
        $boosted = \block_ai_tutor\rag\hybrid_retriever::combine_retrieval_scores(
            0.15,
            0.60,
            true,
            0.75
        );
        $this->assertGreaterThan(0.15, $boosted);

        // Incompatible/no semantic vectors preserve lexical-only behavior.
        $lexicalonly = \block_ai_tutor\rag\hybrid_retriever::combine_retrieval_scores(
            0.42,
            0.99,
            false,
            0.75
        );
        $this->assertEqualsWithDelta(0.42, $lexicalonly, 0.000001);
    }

}
