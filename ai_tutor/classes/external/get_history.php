<?php
// This file is part of Moodle - http://moodle.org/
namespace block_ai_tutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

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

        \block_ai_tutor\local\schema::require_tables(['block_ai_tutor_msg']);

        if (!\block_ai_tutor\local\schema::table_exists('block_ai_tutor_msg')) {
            return ['history' => []];
        }

        $limit = \block_ai_tutor\security_filter::clamp_history_limit((int)$params['limit']);
        $records = $DB->get_records('block_ai_tutor_msg',
            ['courseid' => $params['courseid'], 'userid' => $USER->id],
            'timecreated ASC, id ASC', '*', 0, $limit);

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
