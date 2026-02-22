<?php
// blocks/ai_tutor/db/services.php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_ai_tutor_send_message' => [
        'classname' => 'block_ai_tutor\external\send_message',
        'methodname' => 'execute',
        'description' => 'Enviar mensaje al tutor VTutor',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_ai_tutor_get_history' => [
        'classname' => 'block_ai_tutor\external\get_history',
        'methodname' => 'execute',
        'description' => 'Obtener historial de chat',
        'type' => 'read',
        'ajax' => true,
    ],
    'block_ai_tutor_clear_history' => [
        'classname' => 'block_ai_tutor\external\clear_history',
        'methodname' => 'execute',
        'description' => 'Limpiar historial de chat',
        'type' => 'write',
        'ajax' => true,
    ],
];