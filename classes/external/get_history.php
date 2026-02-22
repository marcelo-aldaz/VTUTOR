<?php
namespace block_ai_tutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/blocks/ai_tutor/classes/ai_service.php');


class get_history extends \external_api {

    public static function execute_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'limit' => new \external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 50),
        ]);
    }

    public static function execute($courseid, $limit = 50) {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), compact('courseid', 'limit'));
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/ai_tutor:use', $context);

        $records = $DB->get_records('block_ai_tutor_msg',
            ['courseid' => $params['courseid'], 'userid' => $USER->id],
            'timecreated ASC', '*', 0, $params['limit']);

        $history = [];
        foreach ($records as $rec) {
            $history[] = [
                'id' => (int)$rec->id,
                'message' => (string)$rec->message,
                'response' => (string)$rec->response,
                'time' => userdate($rec->timecreated, '%H:%M - %d/%m'),
            ];
        }
        return ['history' => $history];
    }

    public static function execute_returns() {
        return new \external_single_structure([
            'history' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT, 'ID'),
                    'message' => new \external_value(PARAM_RAW, 'Message'),
                    'response' => new \external_value(PARAM_RAW, 'Response'),
                    'time' => new \external_value(PARAM_TEXT, 'Time'),
                ])
            ),
        ]);
    }
}
