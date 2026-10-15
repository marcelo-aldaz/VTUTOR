<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
namespace block_ai_tutor\task;

defined('MOODLE_INTERNAL') || die();

class cleanup_old_messages extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_cleanup_old_messages', 'block_ai_tutor');
    }

    public function execute() {
        global $DB;

        if (!\block_ai_tutor\local\schema::table_exists('block_ai_tutor_msg')) {
            return;
        }

        $retentiondays = max(1, (int)(get_config('block_ai_tutor', 'history_retention_days') ?: 365));
        $cutoff = time() - ($retentiondays * DAYSECS);

        $DB->delete_records_select(
            'block_ai_tutor_msg',
            'timecreated < :timecreated',
            ['timecreated' => $cutoff]
        );

        mtrace('Mensajes de VTutor fuera de la ventana de retención limpiados');
    }
}
