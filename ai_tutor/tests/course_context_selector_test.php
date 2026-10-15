<?php
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/** @covers \block_ai_tutor\local\course_context_selector */
final class course_context_selector_test extends \advanced_testcase {
    public function test_relevant_visible_text_is_selected_without_remote_rerank(): void {
        $items = [
            ['label' => 'Escritura digital', 'text' => 'Curaduría, infoxicación y lectura digital.'],
            ['label' => 'Diglosia', 'text' => 'Lenguas originarias, variaciones lingüísticas y diglosia.'],
            ['label' => 'Matemática', 'text' => 'Ecuaciones cuadráticas.'],
        ];
        $selected = \block_ai_tutor\local\course_context_selector::select(
            'Explícame la diglosia y las lenguas originarias', $items, 2);
        $this->assertNotEmpty($selected);
        $this->assertSame('Diglosia', $selected[0]['label']);
        $this->assertLessThanOrEqual(2, count($selected));
    }

    public function test_unrelated_text_scores_lower(): void {
        $relevant = \block_ai_tutor\local\course_context_selector::score(
            'diglosia lenguas originarias', 'Lenguas originarias del Ecuador y diglosia');
        $unrelated = \block_ai_tutor\local\course_context_selector::score(
            'diglosia lenguas originarias', 'Ecuaciones cuadráticas y productos notables');
        $this->assertGreaterThan($unrelated, $relevant);
    }
}
