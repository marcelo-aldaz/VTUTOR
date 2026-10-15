<?php
// This file is part of Moodle - http://moodle.org/
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

        if ((int)$params['cmid'] > 0) {
            try {
                $modinfo = get_fast_modinfo((int)$params['courseid'], $USER->id);
                $cm = $modinfo->get_cm((int)$params['cmid']);
                if ((int)$cm->course !== (int)$params['courseid'] || !$cm->uservisible) {
                    throw new \invalid_parameter_exception('Invalid or inaccessible course module context.');
                }
            } catch (\Throwable $e) {
                throw new \invalid_parameter_exception('Invalid or inaccessible course module context.');
            }
        }

        $filteredinput = \block_ai_tutor\security_filter::filter_input((string)$params['message']);
        if (empty($filteredinput['ok'])) {
            return [
                'response' => (string)$filteredinput['notice'],
                'provider' => 'deepseek',
                'model' => \block_ai_tutor\ai_service::normalize_model_name('deepseek', (string)get_config('block_ai_tutor', 'model')),
            ];
        }
        $params['message'] = (string)$filteredinput['message'];

        \block_ai_tutor\local\schema::require_tables(['block_ai_tutor_msg']);

        try {
            $history = [];
            if (\block_ai_tutor\local\schema::table_exists('block_ai_tutor_msg')) {
                $historyrecords = $DB->get_records('block_ai_tutor_msg',
                    ['courseid' => $params['courseid'], 'userid' => $USER->id],
                    'timecreated DESC, id DESC', '*', 0, 10);

                foreach (array_reverse($historyrecords) as $rec) {
                    $history[] = ['message' => (string)$rec->message, 'response' => (string)$rec->response];
                }
            }

            $service = new \block_ai_tutor\ai_service();
            $response = $service->get_tutor_response($params['message'], $params['courseid'], $history, (int)$params['cmid']);

            return [
                'response' => (string)$response,
                'provider' => $service->get_effective_provider(),
                'model' => $service->get_effective_model(),
            ];
        } catch (\Throwable $e) {
            $safe = class_exists('\\block_ai_tutor\\security_filter')
                ? \block_ai_tutor\security_filter::sanitize_error_message((string)$e->getMessage())
                : clean_param((string)$e->getMessage(), PARAM_TEXT);
            error_log('VTutor AJAX send_message Error: ' . $safe);
            $adminhint = '';
            try {
                if (has_capability('moodle/site:config', \context_system::instance())) {
                    $adminhint = ' Detalle técnico para administrador: ' . $safe;
                }
            } catch (\Throwable $ignored) {}
            return [
                'response' => '⚠️ Hubo un problema técnico en el endpoint del tutor, pero el bloque respondió correctamente al AJAX.' . $adminhint . ' Revise Administración del sitio → Notificaciones y purgue cachés.',
                'provider' => 'deepseek',
                'model' => \block_ai_tutor\ai_service::normalize_model_name('deepseek', (string)get_config('block_ai_tutor', 'model')),
            ];
        }
    }

    public static function execute_returns() {
        return new \external_single_structure([
            'response' => new \external_value(PARAM_RAW, 'AI response'),
            'provider' => new \external_value(PARAM_TEXT, 'Provider'),
            'model' => new \external_value(PARAM_TEXT, 'Model'),
        ]);
    }
}
