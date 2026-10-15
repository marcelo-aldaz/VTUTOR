<?php
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

final class conversation_routing_test extends \advanced_testcase {
    public function test_plain_greeting_bypasses_provider_configuration_and_rag(): void {
        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course();
        $this->setAdminUser();
        unset_config('api_key', 'block_ai_tutor');
        set_config('course_rag_enabled_' . $course->id, 1, 'block_ai_tutor');
        set_config('course_rag_mode_' . $course->id, 'strict', 'block_ai_tutor');

        $response = (new ai_service())->get_tutor_response('Hola', $course->id, [], 0);
        $this->assertSame(get_string('conversation_greeting', 'block_ai_tutor'), $response);
        $this->assertStringNotContainsString(get_string('rag_strict_no_evidence', 'block_ai_tutor'), $response);
    }
}
