<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// blocks/ai_tutor/block_ai_tutor.php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

class block_ai_tutor extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_ai_tutor');
    }

    public function applicable_formats() {
        return ['course' => true];
    }

    /** Tell Moodle that this block provides site-wide settings.php options. */
    public function has_config() {
        return true;
    }

    public function get_content() {
        global $COURSE, $PAGE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        $context = context_course::instance($COURSE->id);
        if (!has_capability('block/ai_tutor:use', $context)) {
            $this->content->text = get_string('nopermissions', 'error');
            return $this->content;
        }

        $canadmin = has_capability('moodle/site:config', context_system::instance());
        $canmanagedocuments = $canadmin || has_capability('block/ai_tutor:manage', $context);

        $branding = block_ai_tutor_get_branding_data($OUTPUT, (int)$COURSE->id);
        $chaturl = new moodle_url('/blocks/ai_tutor/chat.php', ['courseid' => $COURSE->id]);
        $manageurl = new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $COURSE->id]);
        $adminurl = new moodle_url('/blocks/ai_tutor/admin.php');

        $data = array_merge($branding, [
            'courseid' => (int)$COURSE->id,
            'coursename' => format_string($COURSE->fullname),
            'chaturl' => $chaturl->out(false),
            'manageurl' => $manageurl->out(false),
            'adminurl' => $adminurl->out(false),
            'canmanage' => $canmanagedocuments,
            'canadmin' => $canadmin,
            'popupwindowname' => 'vtutorpro_' . (int)$COURSE->id,
            'launchwindowlabel' => get_string('launchwindowlabel', 'block_ai_tutor'),
            'launchsamepagelabel' => get_string('launchsamepagelabel', 'block_ai_tutor'),
            'controlcenterlabel' => get_string('controlcenterlabel', 'block_ai_tutor'),
            'launchdescription' => get_string('launchdescription', 'block_ai_tutor'),
            'openglobalconfigbutton' => get_string('openglobalconfigbutton', 'block_ai_tutor'),
        ]);

        $this->content->text = $OUTPUT->render_from_template('block_ai_tutor/launch_panel', $data);
        $PAGE->requires->js_call_amd('block_ai_tutor/launcher', 'init', [$COURSE->id, $chaturl->out(false)]);

        return $this->content;
    }
}
