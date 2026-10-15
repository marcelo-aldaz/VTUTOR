<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('block/ai_tutor:use', $context);

$canadmin = has_capability('moodle/site:config', context_system::instance());
$canmanagedocuments = $canadmin || has_capability('block/ai_tutor:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/ai_tutor/chat.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('pluginname', 'block_ai_tutor') . ' - ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagetype('block-ai_tutor-chat');

$data = block_ai_tutor_build_chat_template_data($course, $USER, $OUTPUT, $canadmin, $canmanagedocuments, false, true);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('block_ai_tutor/chat_interface', $data);

$cmid = optional_param('id', 0, PARAM_INT);
$PAGE->requires->js_call_amd('block_ai_tutor/chat', 'init', [$courseid, $cmid]);
echo $OUTPUT->footer();
