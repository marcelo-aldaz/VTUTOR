<?php
// This file is part of Moodle - http://moodle.org/
// blocks/ai_tutor/routes.php
require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);
$logid = optional_param('logid', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
$canmanage = has_capability('moodle/site:config', context_system::instance())
    || has_capability('block/ai_tutor:manageroutes', $context);
if (!$canmanage) {
    throw new required_capability_exception($context, 'block/ai_tutor:manageroutes', 'nopermissions', '');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/ai_tutor/routes.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('routespage', 'block_ai_tutor'));
$PAGE->set_heading(format_string($course->fullname));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    require_sesskey();
    if ($action === 'rebuildmap') {
        $count = \block_ai_tutor\coursemap::rebuild((int)$courseid);
        redirect(new moodle_url('/blocks/ai_tutor/routes.php', ['courseid' => $courseid]), get_string('coursemap_rebuilt', 'block_ai_tutor', $count), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($logid > 0) {
        \block_ai_tutor\route_engine::validate_route($logid, $action, (int)$courseid);
        redirect(new moodle_url('/blocks/ai_tutor/routes.php', ['courseid' => $courseid]), get_string('routes_action_saved', 'block_ai_tutor'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('routespage', 'block_ai_tutor'));
echo html_writer::div(get_string('routespage_intro', 'block_ai_tutor'), 'alert alert-info');
echo html_writer::div(get_string('hitl_statement_short', 'block_ai_tutor'), 'alert alert-warning');

global $DB;

$mapurl = new moodle_url('/blocks/ai_tutor/routes.php', [
    'courseid' => $courseid,
    'action' => 'rebuildmap',
]);
echo $OUTPUT->single_button($mapurl, get_string('coursemap_rebuild_button', 'block_ai_tutor'), 'post', ['class' => 'mb-3']);

$mapitems = \block_ai_tutor\coursemap::get_items((int)$courseid, null, 200);
if (!empty($mapitems)) {
    echo $OUTPUT->heading(get_string('coursemap_title', 'block_ai_tutor'), 3);
    $maptable = new html_table();
    $maptable->head = [
        get_string('coursemap_section', 'block_ai_tutor'),
        get_string('coursemap_type', 'block_ai_tutor'),
        get_string('coursemap_activity', 'block_ai_tutor'),
        get_string('coursemap_module', 'block_ai_tutor'),
    ];
    $maptable->data = [];
    foreach ($mapitems as $item) {
        $url = new moodle_url('/mod/' . $item->modname . '/view.php', ['id' => $item->cmid]);
        $maptable->data[] = [
            (int)$item->sectionnum,
            s(get_string('coursemap_type_' . $item->pedagogicaltype, 'block_ai_tutor')),
            html_writer::link($url, s($item->itemname)),
            s($item->modname),
        ];
    }
    echo html_writer::table($maptable);
}

echo $OUTPUT->heading(get_string('routes_suggestions_title', 'block_ai_tutor'), 3);

$records = $DB->get_records('block_ai_tutor_route_log', ['courseid' => $courseid], 'timecreated DESC', '*', 0, 100);

$table = new html_table();
$table->head = [
    get_string('routes_table_date', 'block_ai_tutor'),
    get_string('routes_table_student', 'block_ai_tutor'),
    get_string('routes_table_route', 'block_ai_tutor'),
    get_string('routes_table_status', 'block_ai_tutor'),
    get_string('routes_table_action', 'block_ai_tutor'),
];
$table->data = [];
foreach ($records as $r) {
    $user = core_user::get_user($r->userid, 'id,firstname,lastname,email', IGNORE_MISSING);
    $username = $user ? fullname($user) : get_string('unknownuser');
    $buttons = [];
    foreach (['validated', 'adjusted', 'followup', 'rejected'] as $decision) {
        $url = new moodle_url('/blocks/ai_tutor/routes.php', [
            'courseid' => $courseid,
            'logid' => $r->id,
            'action' => $decision,
        ]);
        $buttons[] = $OUTPUT->single_button(
            $url,
            get_string('routes_action_' . $decision, 'block_ai_tutor'),
            'post',
            ['class' => 'd-inline-block m-1']
        );
    }
    $table->data[] = [
        userdate($r->timecreated, get_string('strftimedatetime')),
        s($username),
        html_writer::tag('strong', s($r->routename)) . html_writer::div(s($r->routekey), 'text-muted small'),
        s($r->status),
        implode(' ', $buttons),
    ];
}

if (empty($records)) {
    echo html_writer::div(get_string('routes_no_records', 'block_ai_tutor'), 'alert alert-secondary');
} else {
    echo html_writer::table($table);
}

echo html_writer::link(new moodle_url('/course/view.php', ['id' => $courseid]), get_string('managecoursepage_backcourse', 'block_ai_tutor'), ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();
