<?php
namespace block_ai_tutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/blocks/ai_tutor/classes/ai_service.php');


class send_message extends \external_api {

    public static function execute_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'message' => new \external_value(PARAM_RAW_TRIMMED, 'Student message'),
            'cmid' => new \external_value(PARAM_INT, 'Current course module id', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute($courseid, $message, $cmid = 0) {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'message' => $message,
            'cmid' => $cmid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/ai_tutor:use', $context);

        $historyrecords = $DB->get_records('block_ai_tutor_msg',
            ['courseid' => $params['courseid'], 'userid' => $USER->id],
            'timecreated DESC', '*', 0, 10);

        $history = [];
        foreach (array_reverse($historyrecords) as $rec) {
            $history[] = ['message' => (string)$rec->message, 'response' => (string)$rec->response];
        }

        $service = new \block_ai_tutor\ai_service();
        $response = $service->get_tutor_response($params['message'], $params['courseid'], $history, (int)$params['cmid']);

        return [
            'response' => (string)$response,
            'provider' => (string)(get_config('block_ai_tutor', 'provider') ?: 'openai'),
            'model' => (string)(get_config('block_ai_tutor', 'model') ?: 'gpt-3.5-turbo'),
        ];
    }

    public static function execute_returns() {
        return new \external_single_structure([
            'response' => new \external_value(PARAM_RAW, 'AI response'),
            'provider' => new \external_value(PARAM_TEXT, 'Provider'),
            'model' => new \external_value(PARAM_TEXT, 'Model'),
        ]);
    }
}
