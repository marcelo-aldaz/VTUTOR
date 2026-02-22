<?php
namespace block_ai_tutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/blocks/ai_tutor/classes/ai_service.php');


class clear_history extends \external_api {

    public static function execute_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function execute($courseid) {
        global $USER, $DB;
        $params = self::validate_parameters(self::execute_parameters(), compact('courseid'));
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/ai_tutor:use', $context);

        $DB->delete_records('block_ai_tutor_msg', ['courseid' => $params['courseid'], 'userid' => $USER->id]);
        return ['success' => true];
    }

    public static function execute_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success'),
        ]);
    }
}
