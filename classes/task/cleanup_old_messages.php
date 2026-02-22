<?php
namespace block_ai_tutor\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_old_messages extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_cleanup_old_messages', 'block_ai_tutor');
    }

    public function execute() {
        global $DB;

        $oneyearago = time() - (365 * 24 * 60 * 60);

        $DB->delete_records_select(
            'block_ai_tutor_msg',
            'timecreated < :timecreated',
            ['timecreated' => $oneyearago]
        );

        mtrace('Mensajes antiguos de VTutor limpiados');
    }
}
