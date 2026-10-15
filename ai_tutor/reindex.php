<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    throw new moodle_exception('invalidrequest', 'error');
}
require_sesskey();

$course = get_course($courseid);
require_login($course);

$coursecontext = context_course::instance($courseid);
$systemcontext = context_system::instance();
$canmanage = has_capability('moodle/site:config', $systemcontext)
    || has_capability('block/ai_tutor:manage', $coursecontext);

if (!$canmanage) {
    throw new required_capability_exception($coursecontext, 'block/ai_tutor:manage', 'nopermissions', '');
}

\block_ai_tutor\local\schema::require_tables(['block_ai_tutor_embed', 'block_ai_tutor_doc', 'block_ai_tutor_batch']);

try {
    $stats = block_ai_tutor_process_institutional_reindex($courseid);
    if (!empty($stats['empty'])) {
        redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
            get_string('rag_reindex_no_documents', 'block_ai_tutor'),
            null,
            \core\output\notification::NOTIFY_WARNING);
    }
    $a = (object) [
        'course' => format_string($course->fullname),
        'sources' => $stats['sources'],
        'chunks' => $stats['chunks'],
    ];
    $message = get_string('rag_reindex_course_done', 'block_ai_tutor', $a);
    $notificationtype = \core\output\notification::NOTIFY_SUCCESS;
    if (!empty($stats['errors'])) {
        $summary = block_ai_tutor_processing_error_summary($stats);
        if ($summary !== '') {
            $message .= ' ' . get_string('rag_processing_error_detail', 'block_ai_tutor', $summary);
        }
        $notificationtype = \core\output\notification::NOTIFY_ERROR;
    }
    redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]), $message, null, $notificationtype);
} catch (\Throwable $e) {
    $safeerror = \block_ai_tutor\security_filter::sanitize_error_message((string)$e->getMessage());
    redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]), get_string('rag_reindex_error', 'block_ai_tutor', $safeerror), null, \core\output\notification::NOTIFY_ERROR);
}
