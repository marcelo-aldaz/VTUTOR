<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \block_ai_tutor\rag\deepseek_semantic_vectorizer
 * @covers \block_ai_tutor\rag\vector_math
 */
final class deepseek_semantic_vectorizer_test extends \advanced_testcase {
    public function test_configuration_requires_deepseek_and_single_api_key(): void {
        $this->resetAfterTest(true);
        set_config('provider', 'deepseek', 'block_ai_tutor');
        set_config('baseurl', 'https://api.deepseek.com/v1', 'block_ai_tutor');
        unset_config('api_key', 'block_ai_tutor');
        $this->assertFalse(\block_ai_tutor\rag\deepseek_semantic_vectorizer::is_configured());

        set_config('api_key', 'test-only-key', 'block_ai_tutor');
        $this->assertTrue(\block_ai_tutor\rag\deepseek_semantic_vectorizer::is_configured());

        set_config('provider', 'openai', 'block_ai_tutor');
        $this->assertFalse(\block_ai_tutor\rag\deepseek_semantic_vectorizer::is_configured());
    }

    public function test_signature_vector_is_sparse_deterministic_and_normalised(): void {
        $signature = [
            'concepts' => ['evaluación formativa', 'retroalimentación'],
            'synonyms' => ['evaluación para el aprendizaje'],
            'entities' => ['docente', 'estudiante'],
            'relations' => ['la retroalimentación orienta la mejora'],
            'pedagogical_terms' => ['metacognición'],
            'questions' => ['¿Cómo mejora el aprendizaje la retroalimentación?'],
        ];
        $first = \block_ai_tutor\rag\deepseek_semantic_vectorizer::signature_to_vector(
            $signature,
            'La evaluación formativa ofrece retroalimentación al estudiante.'
        );
        $second = \block_ai_tutor\rag\deepseek_semantic_vectorizer::signature_to_vector(
            $signature,
            'La evaluación formativa ofrece retroalimentación al estudiante.'
        );
        $this->assertSame($first, $second);
        $this->assertNotEmpty($first);
        $this->assertLessThan(\block_ai_tutor\rag\deepseek_semantic_vectorizer::DIMENSIONS, count($first));
        $norm = sqrt(array_sum(array_map(static function($value): float {
            return $value * $value;
        }, $first)));
        $this->assertEqualsWithDelta(1.0, $norm, 0.00001);
    }

    public function test_related_signatures_score_above_unrelated_signatures(): void {
        $question = \block_ai_tutor\rag\deepseek_semantic_vectorizer::signature_to_vector([
            'concepts' => ['evaluación formativa', 'retroalimentación'],
            'synonyms' => ['evaluación para aprender'],
        ], '¿Cómo ayuda la retroalimentación en la evaluación formativa?');
        $related = \block_ai_tutor\rag\deepseek_semantic_vectorizer::signature_to_vector([
            'concepts' => ['evaluación formativa', 'retroalimentación pedagógica'],
            'synonyms' => ['evaluación para el aprendizaje'],
        ], 'El docente retroalimenta durante la evaluación formativa.');
        $unrelated = \block_ai_tutor\rag\deepseek_semantic_vectorizer::signature_to_vector([
            'concepts' => ['seguridad informática', 'copias de respaldo'],
        ], 'Las copias de seguridad protegen el servidor.');

        $relatedscore = \block_ai_tutor\rag\vector_math::sparse_cosine_similarity($question, $related);
        $unrelatedscore = \block_ai_tutor\rag\vector_math::sparse_cosine_similarity($question, $unrelated);
        $this->assertGreaterThan($unrelatedscore, $relatedscore);
    }

    public function test_valid_json_response_preserves_original_item_keys(): void {
        $content = json_encode(['items' => [
            ['id' => 7, 'concepts' => ['evaluación formativa'], 'synonyms' => [],
                'entities' => ['docente'], 'relations' => [], 'pedagogical_terms' => [], 'questions' => []],
        ]]);
        $result = \block_ai_tutor\rag\deepseek_semantic_vectorizer::decode_signature_response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => $content],
            ]],
        ], [7 => 'Texto de prueba']);

        $this->assertArrayHasKey(7, $result);
        $this->assertSame(['evaluación formativa'], $result[7]['concepts']);
        $this->assertSame(['docente'], $result[7]['entities']);
    }

    public function test_empty_semantic_content_has_distinct_error_code(): void {
        try {
            \block_ai_tutor\rag\deepseek_semantic_vectorizer::decode_signature_response([
                'choices' => [['finish_reason' => 'stop', 'message' => ['content' => '']]],
            ], [0 => 'Texto']);
            $this->fail('An empty response must fail.');
        } catch (\moodle_exception $e) {
            $this->assertSame('rag_deepseek_semantic_empty_response', $e->errorcode);
        }
    }

    public function test_bounded_json_fence_is_normalised(): void {
        $content = "```json\n{\"items\":[{\"id\":2,\"concepts\":[\"Moodle\"]}]}\n```";
        $result = \block_ai_tutor\rag\deepseek_semantic_vectorizer::decode_signature_response([
            'choices' => [['finish_reason' => 'stop', 'message' => ['content' => $content]]],
        ], [2 => 'Texto']);
        $this->assertSame(['Moodle'], $result[2]['concepts']);
    }

    public function test_length_finish_reason_has_distinct_error_code(): void {
        try {
            \block_ai_tutor\rag\deepseek_semantic_vectorizer::decode_signature_response([
                'choices' => [['finish_reason' => 'length', 'message' => ['content' => '{"items":[]']]],
            ], [0 => 'Texto']);
            $this->fail('A truncated response must fail.');
        } catch (\moodle_exception $e) {
            $this->assertSame('rag_deepseek_semantic_truncated_response', $e->errorcode);
        }
    }

    public function test_missing_item_is_rejected_before_vector_creation(): void {
        $this->expectException(\moodle_exception::class);
        \block_ai_tutor\rag\deepseek_semantic_vectorizer::decode_signature_response([
            'choices' => [[
                'finish_reason' => 'stop',
                'message' => ['content' => '{"items":[]}'],
            ]],
        ], [3 => 'Texto']);
    }
}
