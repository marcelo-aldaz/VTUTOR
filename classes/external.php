<?php
// blocks/ai_tutor/classes/external.php
namespace block_ai_tutor\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/blocks/ai_tutor/classes/ai_service.php');

class send_message extends \external_api {
    
    public static function execute_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'ID del curso'),
            'message' => new \external_value(PARAM_TEXT, 'Mensaje del estudiante'),
        ]);
    }

    public static function execute($courseid, $message) {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'message' => $message,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/ai_tutor:use', $context);

        // Obtener historial reciente
        $history = $DB->get_records('block_ai_tutor_msg',
            ['courseid' => $params['courseid'], 'userid' => $USER->id],
            'timecreated DESC', '*', 0, 10);

        $conversationHistory = [];
        foreach (array_reverse($history) as $rec) {
            $conversationHistory[] = [
                'message' => $rec->message,
                'response' => $rec->response,
            ];
        }

        // Obtener respuesta de la IA
        $aiService = new \block_ai_tutor\ai_service();
        $response = $aiService->get_tutor_response(
            $params['message'],
            $params['courseid'],
            $conversationHistory
        );

        return [
            'response' => $response,
            'provider' => get_config('block_ai_tutor', 'provider') ?: 'openai',
            'model' => get_config('block_ai_tutor', 'model') ?: 'gpt-3.5-turbo',
        ];
    }

    public static function execute_returns() {
        return new \external_single_structure([
            'response' => new \external_value(PARAM_TEXT, 'Respuesta de la IA'),
            'provider' => new \external_value(PARAM_TEXT, 'Proveedor'),
            'model' => new \external_value(PARAM_TEXT, 'Modelo'),
        ]);
    }
}

class get_history extends \external_api {
    
    public static function execute_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'ID del curso'),
            'limit' => new \external_value(PARAM_INT, 'Límite', VALUE_DEFAULT, 50),
        ]);
    }

    public static function execute($courseid, $limit = 50) {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'limit' => $limit,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/ai_tutor:use', $context);

        $records = $DB->get_records('block_ai_tutor_msg',
            ['courseid' => $params['courseid'], 'userid' => $USER->id],
            'timecreated ASC', '*', 0, $params['limit']);

        $history = [];
        foreach ($records as $rec) {
            $history[] = [
                'id' => $rec->id,
                'message' => $rec->message,
                'response' => $rec->response,
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
                    'message' => new \external_value(PARAM_TEXT, 'Mensaje'),
                    'response' => new \external_value(PARAM_TEXT, 'Respuesta'),
                    'time' => new \external_value(PARAM_TEXT, 'Fecha'),
                ])
            ),
        ]);
    }
}

class clear_history extends \external_api {
    
    public static function execute_parameters() {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'ID del curso'),
        ]);
    }

    public static function execute($courseid) {
        global $USER, $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('block/ai_tutor:use', $context);

        $DB->delete_records('block_ai_tutor_msg', [
            'courseid' => $params['courseid'],
            'userid' => $USER->id,
        ]);

        return ['success' => true];
    }

    public static function execute_returns() {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Éxito'),
        ]);
    }
}