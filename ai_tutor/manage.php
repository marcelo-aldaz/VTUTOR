<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once(__DIR__ . '/lib.php');
if (!defined('FILE_INTERNAL')) {
    define('FILE_INTERNAL', 1);
}

class block_ai_tutor_manage_form extends moodleform {
    public function definition() {
        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $canadmin = !empty($this->_customdata['canadmin']);

        $mform->addElement('hidden', 'courseid', (int)$course->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'docheader', get_string('managecoursepage_docs_header', 'block_ai_tutor'));
        $mform->addElement('static', 'dochelp', '', get_string('managecoursepage_docs_help', 'block_ai_tutor'));
        $mform->addElement('filemanager', 'course_document_base', get_string('managecoursepage_filemanager', 'block_ai_tutor'), null, [
            'subdirs' => 0,
            'maxfiles' => max(1, (int)(get_config('block_ai_tutor', 'rag_max_documents') ?: 20)),
            'maxbytes' => max(1024, (int)(get_config('block_ai_tutor', 'rag_max_file_bytes') ?: 26214400)),
            'accepted_types' => ['.pdf', '.txt', '.md'],
            'return_types' => FILE_INTERNAL,
        ]);
        $mform->addElement('advcheckbox', 'course_rag_enabled', get_string('rag_course_enabled', 'block_ai_tutor'));
        $mform->addHelpButton('course_rag_enabled', 'rag_course_enabled', 'block_ai_tutor');
        $mform->addElement('select', 'course_rag_mode', get_string('rag_course_mode', 'block_ai_tutor'), [
            'strict' => get_string('rag_mode_strict', 'block_ai_tutor'),
            'mixed' => get_string('rag_mode_mixed', 'block_ai_tutor'),
        ]);
        $mform->setType('course_rag_mode', PARAM_ALPHA);
        $mform->addElement('static', 'ragworkflow', '', get_string('rag_review_workflow_help', 'block_ai_tutor'));

        $mform->addElement('header', 'promptheader', get_string('managecoursepage_prompt_header', 'block_ai_tutor'));
        $mform->addElement('textarea', 'course_prompt', get_string('managecoursepage_prompt', 'block_ai_tutor'), ['rows' => 8, 'cols' => 80]);
        $mform->setType('course_prompt', PARAM_RAW);
        $mform->addHelpButton('course_prompt', 'managecoursepage_prompt', 'block_ai_tutor');
        $mform->addElement('textarea', 'course_document_prompt', get_string('managecoursepage_document_prompt', 'block_ai_tutor'), ['rows' => 8, 'cols' => 80]);
        $mform->setType('course_document_prompt', PARAM_RAW);
        $mform->addHelpButton('course_document_prompt', 'managecoursepage_document_prompt', 'block_ai_tutor');

        $mform->addElement('header', 'launcherheader', get_string('managecoursepage_launcher_header', 'block_ai_tutor'));
        $mform->addElement('text', 'launcher_label', get_string('managecoursepage_launcher_label', 'block_ai_tutor'), ['size' => 40]);
        $mform->setType('launcher_label', PARAM_TEXT);
        $mform->addElement('text', 'launcher_title', get_string('managecoursepage_launcher_title', 'block_ai_tutor'), ['size' => 80]);
        $mform->setType('launcher_title', PARAM_TEXT);
        $mform->addElement('text', 'launcher_bg', get_string('managecoursepage_launcher_bg', 'block_ai_tutor'), ['size' => 20]);
        $mform->setType('launcher_bg', PARAM_TEXT);
        $mform->addElement('text', 'launcher_fg', get_string('managecoursepage_launcher_fg', 'block_ai_tutor'), ['size' => 20]);
        $mform->setType('launcher_fg', PARAM_TEXT);
        $mform->addElement('text', 'launcher_border', get_string('managecoursepage_launcher_border', 'block_ai_tutor'), ['size' => 20]);
        $mform->setType('launcher_border', PARAM_TEXT);

        if ($canadmin) {
            $mform->addElement('header', 'globalconfigheader', get_string('managecoursepage_global_header', 'block_ai_tutor'));
            $mform->addElement('static', 'globalhelp', '', get_string('managecoursepage_global_help', 'block_ai_tutor'));
            // FINAL release contract: DeepSeek is the only validated provider.
            $providers = [
                'deepseek' => get_string('provider_deepseek', 'block_ai_tutor'),
            ];
            $mform->addElement('select', 'provider', get_string('provider', 'block_ai_tutor'), $providers);
            $mform->setType('provider', PARAM_ALPHANUMEXT);
            $mform->addElement('passwordunmask', 'api_key', get_string('apikey', 'block_ai_tutor'), ['size' => 40]);
            $mform->setType('api_key', PARAM_RAW);
            $mform->addElement('select', 'model', get_string('model', 'block_ai_tutor'), [
                'deepseek-v4-flash' => 'deepseek-v4-flash',
                'deepseek-v4-pro' => 'deepseek-v4-pro',
            ]);
            $mform->setType('model', PARAM_ALPHANUMEXT);
            $mform->addElement('text', 'temperature', get_string('temperature', 'block_ai_tutor'), ['size' => 10]);
            $mform->setType('temperature', PARAM_RAW_TRIMMED);
            $mform->addElement('text', 'max_tokens', get_string('maxtokens', 'block_ai_tutor'), ['size' => 10]);
            $mform->setType('max_tokens', PARAM_INT);
            $mform->addElement('advcheckbox', 'guideonly', get_string('guideonly', 'block_ai_tutor'));

            $mform->addElement('text', 'institution_name', get_string('institution_name', 'block_ai_tutor'), ['size' => 80]);
            $mform->setType('institution_name', PARAM_TEXT);
            $mform->addElement('text', 'institution_subtitle', get_string('institution_subtitle', 'block_ai_tutor'), ['size' => 80]);
            $mform->setType('institution_subtitle', PARAM_TEXT);
            $mform->addElement('text', 'institution_logo_image_url', get_string('institution_logo_image_url', 'block_ai_tutor'), ['size' => 80]);
            $mform->setType('institution_logo_image_url', PARAM_URL);
            $mform->addElement('text', 'institution_cover_image_url', get_string('institution_cover_image_url', 'block_ai_tutor'), ['size' => 80]);
            $mform->setType('institution_cover_image_url', PARAM_URL);
            $mform->addElement('text', 'institution_cover_kicker', get_string('institution_cover_kicker', 'block_ai_tutor'), ['size' => 80]);
            $mform->setType('institution_cover_kicker', PARAM_TEXT);
            $mform->addElement('text', 'institution_cover_title', get_string('institution_cover_title', 'block_ai_tutor'), ['size' => 80]);
            $mform->setType('institution_cover_title', PARAM_TEXT);
            $mform->addElement('textarea', 'institution_cover_message', get_string('institution_cover_message', 'block_ai_tutor'), ['rows' => 4, 'cols' => 80]);
            $mform->setType('institution_cover_message', PARAM_RAW);
            $mform->addElement('textarea', 'institution_cover_cta', get_string('institution_cover_cta', 'block_ai_tutor'), ['rows' => 4, 'cols' => 80]);
            $mform->setType('institution_cover_cta', PARAM_RAW);
            $mform->addElement('textarea', 'institution_response_directive', get_string('institution_response_directive', 'block_ai_tutor'), ['rows' => 4, 'cols' => 80]);
            $mform->setType('institution_response_directive', PARAM_RAW);
            $mform->addElement('textarea', 'institution_document_guidance', get_string('institution_document_guidance', 'block_ai_tutor'), ['rows' => 4, 'cols' => 80]);
            $mform->setType('institution_document_guidance', PARAM_RAW);
            $mform->addElement('textarea', 'global_master_prompt', get_string('global_master_prompt', 'block_ai_tutor'), ['rows' => 4, 'cols' => 80]);
            $mform->setType('global_master_prompt', PARAM_RAW);
            $mform->addElement('textarea', 'global_document_prompt', get_string('global_document_prompt', 'block_ai_tutor'), ['rows' => 4, 'cols' => 80]);
            $mform->setType('global_document_prompt', PARAM_RAW);
        }

        $mform->addElement('advcheckbox', 'reindex_now', get_string('managecoursepage_reindex_now', 'block_ai_tutor'));
        $mform->setDefault('reindex_now', 0);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
$systemcontext = context_system::instance();
$canadmin = has_capability('moodle/site:config', $systemcontext);
$canmanage = $canadmin || has_capability('block/ai_tutor:manage', $context);
if (!$canmanage) {
    throw new required_capability_exception($context, 'block/ai_tutor:manage', 'nopermissions', '');
}

\block_ai_tutor\local\schema::require_tables(['block_ai_tutor_msg', 'block_ai_tutor_embed', 'block_ai_tutor_doc', 'block_ai_tutor_batch', 'block_ai_tutor_route_log', 'block_ai_tutor_coursemap']);

// v10.3.6: reindexación interna desde el centro de control.
// No depende de abrir /blocks/ai_tutor/reindex.php, evitando rutas externas rotas o cacheadas.
$vtutoraction = optional_param('vtutor_action', '', PARAM_ALPHANUMEXT);
if ($vtutoraction !== '' && (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST')) {
    throw new moodle_exception('invalidrequest', 'error');
}
if ($vtutoraction === 'reindex_internal') {
    require_sesskey();
    try {
        $stats = block_ai_tutor_process_institutional_reindex($courseid);
        if (!empty($stats['empty'])) {
            redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
                get_string('rag_reindex_no_documents', 'block_ai_tutor'),
                null,
                \core\output\notification::NOTIFY_WARNING);
        }
        $a = (object)[
            'sources' => (int)($stats['sources'] ?? 0),
            'chunks' => (int)($stats['chunks'] ?? 0),
            'errors' => (int)($stats['errors'] ?? 0),
        ];
        $message = get_string('managecoursepage_internal_reindex_done', 'block_ai_tutor', $a);
        $notificationtype = \core\output\notification::NOTIFY_SUCCESS;
        if ($a->errors > 0) {
            $summary = block_ai_tutor_processing_error_summary($stats);
            if ($summary !== '') {
                $message .= ' ' . get_string('rag_processing_error_detail', 'block_ai_tutor', $summary);
            }
            $notificationtype = \core\output\notification::NOTIFY_ERROR;
        }
        redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
            $message,
            null,
            $notificationtype);
    } catch (\Throwable $e) {
        $safeerr = \block_ai_tutor\security_filter::sanitize_error_message((string)$e->getMessage());
        redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
            get_string('managecoursepage_internal_reindex_error', 'block_ai_tutor', $safeerr),
            null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

if ($vtutoraction === 'migrate_semantic') {
    require_sesskey();
    try {
        if (class_exists('core_php_time_limit')) {
            \core_php_time_limit::raise(300);
        }
        $stats = (new \block_ai_tutor\rag\course_vector_migrator())->migrate($courseid);
        redirect(
            new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
            get_string('rag_semantic_migration_done', 'block_ai_tutor', (object)$stats),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\Throwable $e) {
        $safeerr = \block_ai_tutor\security_filter::sanitize_error_message((string)$e->getMessage());
        redirect(
            new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
            get_string('rag_semantic_migration_failed', 'block_ai_tutor', $safeerr),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

if (in_array($vtutoraction, ['approve_document', 'retry_document'], true)) {
    require_sesskey();
    $documentid = required_param('documentid', PARAM_INT);
    $document = $DB->get_record('block_ai_tutor_doc', [
        'id' => $documentid,
        'courseid' => $courseid,
        'active' => 1,
    ], '*', MUST_EXIST);
    $notificationtype = \core\output\notification::NOTIFY_SUCCESS;
    if ($vtutoraction === 'approve_document') {
        // A semantic failure happens after human approval. Allow the same
        // approved Markdown to be retried without forcing extraction/review
        // again. This also makes a stale approval form idempotent for this
        // recoverable state.
        if (!in_array((string)$document->status, ['pending_review', 'embedding_failed'], true)
                || empty($document->markdownfileid)) {
            throw new moodle_exception('rag_document_not_reviewable', 'block_ai_tutor');
        }
        $DB->set_field('block_ai_tutor_doc', 'status', 'ready', ['id' => $documentid]);
        $result = block_ai_tutor_process_document_now($documentid, 'index');
        if ((string)$result->status === 'indexed') {
            $message = get_string('rag_document_index_done', 'block_ai_tutor');
        } else {
            $message = get_string(
                'rag_document_index_failed',
                'block_ai_tutor',
                \block_ai_tutor\security_filter::sanitize_error_message((string)$result->lasterror)
            );
            $notificationtype = \core\output\notification::NOTIFY_ERROR;
        }
    } else {
        $retrystage = \block_ai_tutor\document\document_processor::retry_stage($document);
        if ((string)$document->status === 'indexing' && $retrystage !== 'index') {
            throw new moodle_exception('rag_index_still_running', 'block_ai_tutor');
        }
        if ($retrystage === 'index') {
            // Extraction and human approval already succeeded. Retry only the
            // failed semantic/index stage and preserve the reviewed Markdown.
            $DB->set_field('block_ai_tutor_doc', 'status', 'ready', ['id' => $documentid]);
            $DB->set_field('block_ai_tutor_doc', 'lasterror', null, ['id' => $documentid]);
            $result = block_ai_tutor_process_document_now($documentid, 'index');
            if ((string)$result->status === 'indexed') {
                $message = get_string('rag_document_index_done', 'block_ai_tutor');
            } else {
                $message = get_string(
                    'rag_document_index_failed',
                    'block_ai_tutor',
                    \block_ai_tutor\security_filter::sanitize_error_message((string)$result->lasterror)
                );
                $notificationtype = \core\output\notification::NOTIFY_ERROR;
            }
        } else {
            $DB->set_field('block_ai_tutor_doc', 'status', 'uploaded', ['id' => $documentid]);
            $DB->set_field('block_ai_tutor_doc', 'lasterror', null, ['id' => $documentid]);
            $result = block_ai_tutor_process_document_now($documentid, 'extract');
            if ((string)$result->status === 'pending_review') {
                $message = get_string('rag_document_extract_done', 'block_ai_tutor');
            } else {
                $message = get_string(
                    'rag_document_extract_failed',
                    'block_ai_tutor',
                    \block_ai_tutor\security_filter::sanitize_error_message((string)$result->lasterror)
                );
                $notificationtype = \core\output\notification::NOTIFY_ERROR;
            }
        }
    }
    redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
        $message, null, $notificationtype);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('managecoursepage', 'block_ai_tutor'));
$PAGE->set_heading(format_string($course->fullname));

$fileoptions = [
    'subdirs' => 0,
    'maxfiles' => max(1, (int)(get_config('block_ai_tutor', 'rag_max_documents') ?: 20)),
    'maxbytes' => max(1024, (int)(get_config('block_ai_tutor', 'rag_max_file_bytes') ?: 26214400)),
    'accepted_types' => ['.pdf', '.txt', '.md'],
    'return_types' => FILE_INTERNAL,
];
$draftitemid = file_get_submitted_draft_itemid('course_document_base');
file_prepare_draft_area($draftitemid, $context->id, 'block_ai_tutor', 'course_document_base', $courseid, $fileoptions);

$currentprompt = (string)(get_config('block_ai_tutor', 'course_prompt_' . $courseid) ?: '');
$currentdocumentprompt = (string)(get_config('block_ai_tutor', 'course_document_prompt_' . $courseid) ?: '');
$currentlauncherlabel = (string)(get_config('block_ai_tutor', 'course_launcher_label_' . $courseid) ?: 'VTutor');
$currentlaunchertitle = (string)(get_config('block_ai_tutor', 'course_launcher_title_' . $courseid) ?: 'Abrir VTutor');
$currentlauncherbg = \block_ai_tutor\local\input_guard::css_color((string)(get_config('block_ai_tutor', 'course_launcher_bg_' . $courseid) ?: '#0f3d66'), '#0f3d66');
$currentlauncherfg = \block_ai_tutor\local\input_guard::css_color((string)(get_config('block_ai_tutor', 'course_launcher_fg_' . $courseid) ?: '#ffffff'), '#ffffff');
$currentlauncherborder = \block_ai_tutor\local\input_guard::css_color((string)(get_config('block_ai_tutor', 'course_launcher_border_' . $courseid) ?: 'rgba(255,255,255,0.18)'), 'rgba(255,255,255,0.18)');

$providerdefaults = [
    'deepseek' => ['model' => 'deepseek-v4-flash'],
];
$currentprovider = 'deepseek';
$defaults = $providerdefaults[$currentprovider] ?? ['model' => 'deepseek-v4-flash'];

$mform = new block_ai_tutor_manage_form(null, ['course' => $course, 'canadmin' => $canadmin]);
$data = [
    'courseid' => $courseid,
    'course_document_base' => $draftitemid,
    'course_rag_enabled' => (int)(get_config('block_ai_tutor', 'course_rag_enabled_' . $courseid) ?: 0),
    'course_rag_mode' => (string)(get_config('block_ai_tutor', 'course_rag_mode_' . $courseid) ?: 'strict'),
    'course_prompt' => $currentprompt,
    'course_document_prompt' => $currentdocumentprompt,
    'launcher_label' => $currentlauncherlabel,
    'launcher_title' => $currentlaunchertitle,
    'launcher_bg' => $currentlauncherbg,
    'launcher_fg' => $currentlauncherfg,
    'launcher_border' => $currentlauncherborder,
    'reindex_now' => 0,
];
if ($canadmin) {
    $data += [
        'provider' => $currentprovider,
        // Never prefill a secret into HTML. Blank on submit means keep existing.
        'api_key' => '',
        'model' => (string)(get_config('block_ai_tutor', 'model') ?: ($defaults['model'] ?? '')),
        'temperature' => (string)(get_config('block_ai_tutor', 'temperature') === false ? '0.15' : get_config('block_ai_tutor', 'temperature')),
        'max_tokens' => (int)(get_config('block_ai_tutor', 'max_tokens') ?: 1200),
        'guideonly' => (int)\block_ai_tutor\local\config::bool('guideonly', true),
        'institution_name' => (string)(get_config('block_ai_tutor', 'institution_name') ?: 'Centro de Capacitación V-Zona'),
        'institution_subtitle' => (string)(get_config('block_ai_tutor', 'institution_subtitle') ?: 'Formación profesional, docente y de especialización'),
        'institution_logo_image_url' => (string)(get_config('block_ai_tutor', 'institution_logo_image_url') ?: ''),
        'institution_cover_image_url' => (string)(get_config('block_ai_tutor', 'institution_cover_image_url') ?: ''),
        'institution_cover_kicker' => (string)(get_config('block_ai_tutor', 'institution_cover_kicker') ?: 'Plataforma profesional de acompañamiento inteligente'),
        'institution_cover_title' => (string)(get_config('block_ai_tutor', 'institution_cover_title') ?: 'VTutor Pro V-Zona'),
        'institution_cover_message' => (string)(get_config('block_ai_tutor', 'institution_cover_message') ?: 'Asistente institucional con base documental para docencia, formación continua y cursos de especialización.'),
        'institution_cover_cta' => (string)(get_config('block_ai_tutor', 'institution_cover_cta') ?: 'Consulte contenidos, protocolos, manuales y recursos técnicos del curso con una experiencia visual consistente en laptop, PC, tablet y celular.'),
        'institution_response_directive' => (string)(get_config('block_ai_tutor', 'institution_response_directive') ?: 'Responder con tono ejecutivo-pedagógico, precisión conceptual, orientación aplicable al trabajo, sustento documental cuando exista y respeto por la ética profesional.'),
        'institution_document_guidance' => (string)(get_config('block_ai_tutor', 'institution_document_guidance') ?: 'Cuando existan documentos especializados del curso, priorizar reglamentos, protocolos, manuales, instructivos, guías metodológicas, formatos institucionales y material técnico oficialmente cargado en Moodle.'),
        'global_master_prompt' => (string)(get_config('block_ai_tutor', 'global_master_prompt') ?: ''),
        'global_document_prompt' => (string)(get_config('block_ai_tutor', 'global_document_prompt') ?: ''),
    ];
}
$mform->set_data((object)$data);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else if ($formdata = $mform->get_data()) {
    require_sesskey();

    try {
        file_save_draft_area_files($formdata->course_document_base, $context->id, 'block_ai_tutor', 'course_document_base', $courseid, $fileoptions);
    } catch (\Throwable $e) {
        $safeerr = \block_ai_tutor\security_filter::sanitize_error_message((string)$e->getMessage());
        redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]),
            get_string('managecoursepage_file_save_error', 'block_ai_tutor', $safeerr),
            null,
            \core\output\notification::NOTIFY_ERROR);
    }
    set_config('course_prompt_' . $courseid, trim((string)$formdata->course_prompt), 'block_ai_tutor');
    set_config('course_document_prompt_' . $courseid, trim((string)$formdata->course_document_prompt), 'block_ai_tutor');
    set_config('course_rag_enabled_' . $courseid, !empty($formdata->course_rag_enabled) ? 1 : 0, 'block_ai_tutor');
    // ALPHA3 keeps institutional documents isolated from legacy Moodle chunks.
    set_config('course_rag_scope_' . $courseid, 'managed', 'block_ai_tutor');
    set_config('course_rag_mode_' . $courseid, (string)$formdata->course_rag_mode, 'block_ai_tutor');
    set_config('course_launcher_label_' . $courseid, trim((string)$formdata->launcher_label), 'block_ai_tutor');
    set_config('course_launcher_title_' . $courseid, trim((string)$formdata->launcher_title), 'block_ai_tutor');
    set_config('course_launcher_bg_' . $courseid, \block_ai_tutor\local\input_guard::css_color((string)$formdata->launcher_bg, '#0f3d66'), 'block_ai_tutor');
    set_config('course_launcher_fg_' . $courseid, \block_ai_tutor\local\input_guard::css_color((string)$formdata->launcher_fg, '#ffffff'), 'block_ai_tutor');
    set_config('course_launcher_border_' . $courseid, \block_ai_tutor\local\input_guard::css_color((string)$formdata->launcher_border, 'rgba(255,255,255,0.18)'), 'block_ai_tutor');

    if ($canadmin) {
        set_config('provider', 'deepseek', 'block_ai_tutor');
        $newapikey = trim((string)$formdata->api_key);
        if ($newapikey !== '') {
            set_config('api_key', $newapikey, 'block_ai_tutor');
        }
        set_config('model', \block_ai_tutor\ai_service::normalize_model_name('deepseek', (string)$formdata->model), 'block_ai_tutor');
        set_config('baseurl', \block_ai_tutor\ai_service::official_base_url(), 'block_ai_tutor');
        set_config('temperature', \block_ai_tutor\ai_service::normalize_temperature($formdata->temperature), 'block_ai_tutor');
        set_config('max_tokens', \block_ai_tutor\ai_service::normalize_max_tokens($formdata->max_tokens), 'block_ai_tutor');
        set_config('guideonly', !empty($formdata->guideonly) ? 1 : 0, 'block_ai_tutor');
        set_config('institution_name', trim((string)$formdata->institution_name), 'block_ai_tutor');
        set_config('institution_subtitle', trim((string)$formdata->institution_subtitle), 'block_ai_tutor');
        set_config('institution_logo_image_url', \block_ai_tutor\local\input_guard::https_url((string)$formdata->institution_logo_image_url), 'block_ai_tutor');
        set_config('institution_cover_image_url', \block_ai_tutor\local\input_guard::https_url((string)$formdata->institution_cover_image_url), 'block_ai_tutor');
        set_config('institution_cover_kicker', trim((string)$formdata->institution_cover_kicker), 'block_ai_tutor');
        set_config('institution_cover_title', trim((string)$formdata->institution_cover_title), 'block_ai_tutor');
        set_config('institution_cover_message', trim((string)$formdata->institution_cover_message), 'block_ai_tutor');
        set_config('institution_cover_cta', trim((string)$formdata->institution_cover_cta), 'block_ai_tutor');
        set_config('institution_response_directive', trim((string)$formdata->institution_response_directive), 'block_ai_tutor');
        set_config('institution_document_guidance', trim((string)$formdata->institution_document_guidance), 'block_ai_tutor');
        set_config('global_master_prompt', trim((string)$formdata->global_master_prompt), 'block_ai_tutor');
        set_config('global_document_prompt', trim((string)$formdata->global_document_prompt), 'block_ai_tutor');
    }

    $message = get_string('managecoursepage_saved', 'block_ai_tutor');
    if (!empty($formdata->reindex_now)) {
        if (empty($formdata->course_rag_enabled)) {
            $message .= ' ' . get_string('managecoursepage_saved_no_reindex_disabled', 'block_ai_tutor');
        } else {
            try {
                $stats = block_ai_tutor_process_institutional_reindex($courseid, true);
                if (!empty($stats['empty'])) {
                    $message .= ' ' . get_string('rag_reindex_no_documents', 'block_ai_tutor');
                } else {
                    $a = (object)[
                        'sources' => (int)($stats['sources'] ?? 0),
                        'chunks' => (int)($stats['chunks'] ?? 0),
                    ];
                    $message .= ' ' . get_string('managecoursepage_saved_reindexed', 'block_ai_tutor', $a);
                    if (!empty($stats['errors'])) {
                        $summary = block_ai_tutor_processing_error_summary($stats);
                        if ($summary !== '') {
                            $message .= ' ' . get_string('rag_processing_error_detail', 'block_ai_tutor', $summary);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $message .= ' ' . get_string('managecoursepage_saved_but_reindex_failed', 'block_ai_tutor', \block_ai_tutor\security_filter::sanitize_error_message((string)$e->getMessage()));
            }
        }
    }

    redirect(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]), $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'block_ai_tutor', 'course_document_base', $courseid, 'filename', false);
$fileitems = [];
foreach ($files as $file) {
    if ($file->is_directory()) {
        continue;
    }
    $url = moodle_url::make_pluginfile_url(
        $context->id,
        'block_ai_tutor',
        'course_document_base',
        $courseid,
        $file->get_filepath(),
        $file->get_filename()
    );
    $fileitems[] = html_writer::link($url, s($file->get_filename()), ['target' => '_blank', 'rel' => 'noopener'])
        . ' <small class="text-muted">(' . display_size($file->get_filesize()) . ')</small>';
}

$documenttablehtml = '';
if (\block_ai_tutor\rag\hybrid_retriever::course_enabled($courseid)
        && \block_ai_tutor\local\schema::table_exists('block_ai_tutor_doc')) {
    $repository = new \block_ai_tutor\document\document_repository();
    $documents = $repository->get_course_documents($courseid);
    if ($documents) {
        $actionform = static function(string $action, int $documentid, string $label, string $class) use ($courseid): string {
            $out = html_writer::start_tag('form', [
                'method' => 'post',
                'action' => (new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]))->out(false),
                'class' => 'd-inline-block',
            ]);
            $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'vtutor_action', 'value' => $action]);
            $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'documentid', 'value' => $documentid]);
            $out .= html_writer::tag('button', $label, ['type' => 'submit', 'class' => $class]);
            $out .= html_writer::end_tag('form');
            return $out;
        };
        $table = new html_table();
        $table->head = [
            get_string('rag_table_document', 'block_ai_tutor'),
            get_string('rag_table_status', 'block_ai_tutor'),
            get_string('rag_table_extractor', 'block_ai_tutor'),
            get_string('rag_table_pages', 'block_ai_tutor'),
            get_string('rag_table_characters', 'block_ai_tutor'),
            get_string('rag_table_chunks', 'block_ai_tutor'),
            get_string('rag_table_embeddings', 'block_ai_tutor'),
            get_string('rag_table_index_mode', 'block_ai_tutor'),
            get_string('rag_table_actions', 'block_ai_tutor'),
        ];
        foreach ($documents as $document) {
            $actions = [];
            if (!empty($document->markdownfileid)) {
                $markdownfile = get_file_storage()->get_file_by_id((int)$document->markdownfileid);
                if ($markdownfile) {
                    $markdownurl = moodle_url::make_pluginfile_url(
                        $context->id,
                        'block_ai_tutor',
                        'document_markdown',
                        (int)$document->id,
                        '/',
                        $markdownfile->get_filename(),
                        false
                    );
                    $actions[] = html_writer::link($markdownurl, get_string('rag_action_markdown', 'block_ai_tutor'), [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'target' => '_blank',
                        'rel' => 'noopener',
                    ]);
                    $downloadurl = moodle_url::make_pluginfile_url(
                        $context->id,
                        'block_ai_tutor',
                        'document_markdown',
                        (int)$document->id,
                        '/',
                        $markdownfile->get_filename(),
                        true
                    );
                    $actions[] = html_writer::link($downloadurl, get_string('rag_action_download_markdown', 'block_ai_tutor'), [
                        'class' => 'btn btn-sm btn-outline-secondary',
                    ]);
                }
            }
            if ((string)$document->status === 'pending_review') {
                $actions[] = $actionform(
                    'approve_document',
                    (int)$document->id,
                    get_string('rag_action_approve', 'block_ai_tutor'),
                    'btn btn-sm btn-success'
                );
            }
            $retryableindex = \block_ai_tutor\document\document_processor::retry_stage($document) === 'index';
            if (in_array((string)$document->status, [
                'pending_review', 'indexed', 'extraction_failed', 'pending_ocr', 'embedding_failed',
            ], true) || $retryableindex) {
                $retrylabel = in_array((string)$document->status, ['pending_review', 'indexed'], true)
                    ? get_string('rag_action_regenerate', 'block_ai_tutor')
                    : get_string('rag_action_retry', 'block_ai_tutor');
                $actions[] = $actionform(
                    'retry_document',
                    (int)$document->id,
                    $retrylabel,
                    'btn btn-sm btn-outline-primary'
                );
            }
            $status = get_string('rag_status_' . (string)$document->status, 'block_ai_tutor');
            if (!empty($document->processingphase) && (string)$document->processingphase !== 'complete') {
                $status .= html_writer::tag(
                    'small',
                    '<br>' . s(get_string('rag_processing_phase', 'block_ai_tutor', $document->processingphase)),
                    ['class' => 'text-muted']
                );
            }
            if (!empty($document->lasterror)) {
                $status .= html_writer::tag('small', '<br>' . s((string)$document->lasterror), ['class' => 'text-danger']);
            }
            $table->data[] = [
                s((string)$document->filename),
                $status,
                s((string)($document->extractormode ?? '—')),
                (int)$document->pagecount,
                (int)$document->extractedchars,
                (int)$document->chunkcount,
                (int)$document->embeddingcount,
                (string)$document->embeddingmodel === 'lexical-local-v1'
                    ? get_string('rag_index_mode_lexical', 'block_ai_tutor')
                    : ((int)$document->embeddingcount > 0
                        ? get_string('rag_index_mode_semantic', 'block_ai_tutor')
                        : get_string('rag_index_mode_pending', 'block_ai_tutor')),
                implode(' ', $actions),
            ];
        }
        $documenttablehtml = html_writer::tag('h4', get_string('rag_document_table_heading', 'block_ai_tutor'))
            . html_writer::table($table);
    }
}

$docstatus = block_ai_tutor_get_course_document_status($courseid);
$reindexreadiness = block_ai_tutor_get_reindex_readiness($courseid);
$statushtml = html_writer::start_div('vtutor-control-status ' . s($docstatus['statusclass']));
$statushtml .= html_writer::tag('h4', get_string('documentstatuspaneltitle', 'block_ai_tutor'));
$statushtml .= html_writer::div(s($docstatus['statusmessage']), 'vtutor-control-status-message');
$statushtml .= html_writer::start_div('vtutor-control-status-grid');
$statushtml .= html_writer::div(html_writer::div(get_string('documentstatscountlabel', 'block_ai_tutor'), 'vtutor-control-status-label') . html_writer::div((string)$docstatus['documentcount'], 'vtutor-control-status-value'), 'vtutor-control-status-card');
$statushtml .= html_writer::div(html_writer::div(get_string('documentstatschunkslabel', 'block_ai_tutor'), 'vtutor-control-status-label') . html_writer::div((string)$docstatus['chunkcount'], 'vtutor-control-status-value'), 'vtutor-control-status-card');
$statushtml .= html_writer::div(html_writer::div(get_string('documentstatslastlabel', 'block_ai_tutor'), 'vtutor-control-status-label') . html_writer::div(s($docstatus['lastreindextext']), 'vtutor-control-status-value vtutor-control-status-value-sm'), 'vtutor-control-status-card');
$statushtml .= html_writer::end_div();
$statushtml .= html_writer::div(get_string('documentsectiontypes', 'block_ai_tutor'), 'vtutor-control-status-help');
$statushtml .= html_writer::end_div();

$managehelp = $statushtml;
$managehelp .= html_writer::div(get_string('managecoursepage_intro', 'block_ai_tutor', format_string($course->fullname)), 'alert alert-info');
$managehelp .= html_writer::div(get_string('managecoursepage_note', 'block_ai_tutor'), 'alert alert-secondary');
if (!$reindexreadiness['ready']) {
    $managehelp .= html_writer::div(s($reindexreadiness['message']), 'alert alert-warning');
}
if ($canadmin) {
    $managehelp .= html_writer::div(get_string('managecoursepage_admin_note', 'block_ai_tutor'), 'alert alert-success');
}

if ($fileitems) {
    $managehelp .= html_writer::tag('h5', get_string('managecoursepage_currentfiles', 'block_ai_tutor'));
    $managehelp .= html_writer::start_tag('ul');
    foreach ($fileitems as $item) {
        $managehelp .= html_writer::tag('li', $item);
    }
    $managehelp .= html_writer::end_tag('ul');
} else {
    $managehelp .= html_writer::div(get_string('managecoursepage_nofiles', 'block_ai_tutor'), 'alert alert-warning');
}
$managehelp .= $documenttablehtml;

echo $OUTPUT->header();
echo '<style>.block_ai_tutor_manage_actions{margin:1rem 0 1.25rem 0;display:flex;flex-wrap:wrap;gap:.5rem}.block_ai_tutor_manage_actions a,.block_ai_tutor_manage_actions form{margin:0}.mform fieldset{border:1px solid #d9e4ef;border-radius:12px;padding:1rem;margin-bottom:1rem;background:#fff}.mform legend{font-size:1rem;font-weight:700;width:auto;padding:0 .35rem}.vtutor-control-status{border:1px solid #d9e4ef;border-radius:14px;padding:1rem 1rem 1.15rem 1rem;background:#fff;margin-bottom:1rem}.vtutor-control-status-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.75rem;margin:.85rem 0}.vtutor-control-status-card{border:1px solid #e5edf5;border-radius:12px;padding:.85rem;background:#f8fbfd}.vtutor-control-status-label{font-size:.8rem;color:#486375}.vtutor-control-status-value{font-size:1.45rem;font-weight:700;color:#0f3d66}.vtutor-control-status-value-sm{font-size:1rem;line-height:1.35}.vtutor-control-status-message{font-weight:600;color:#18466c}.vtutor-control-status-help{font-size:.9rem;color:#486375}.vtutor-control-status.is-ready{background:#f3fbf7}.vtutor-control-status.is-stale{background:#fffaf0}.vtutor-control-status.is-disabled,.vtutor-control-status.is-empty{background:#f8fafc}</style>';
echo $OUTPUT->heading(get_string('managecoursepage', 'block_ai_tutor'));
echo $managehelp;
$links = html_writer::link(new moodle_url('/blocks/ai_tutor/chat.php', ['courseid' => $courseid]), get_string('openchatwindowbutton', 'block_ai_tutor'), ['class' => 'btn btn-primary', 'target' => '_blank', 'rel' => 'noopener']) . ' ' .
    block_ai_tutor_render_internal_reindex_form($courseid, 'btn btn-outline-primary') . ' ' .
    html_writer::link(new moodle_url('/blocks/ai_tutor/routes.php', ['courseid' => $courseid]), get_string('routes_controlcenter_link', 'block_ai_tutor'), ['class' => 'btn btn-outline-success']) . ' ' .
    html_writer::link(new moodle_url('/blocks/ai_tutor/diagnostico.php', ['courseid' => $courseid]), get_string('diagnosticobutton', 'block_ai_tutor'), ['class' => 'btn btn-outline-warning']) . ' ' .
    html_writer::link(new moodle_url('/course/view.php', ['id' => $courseid]), get_string('managecoursepage_backcourse', 'block_ai_tutor'), ['class' => 'btn btn-secondary']);
$semanticform = html_writer::start_tag('form', [
    'method' => 'post',
    'action' => (new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]))->out(false),
    'class' => 'd-inline-block',
]);
$semanticform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
$semanticform .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'vtutor_action', 'value' => 'migrate_semantic']);
$semanticattributes = [
    'type' => 'submit',
    'class' => 'btn btn-outline-success',
];
if (!$reindexreadiness['ready'] || !$documenttablehtml) {
    $semanticattributes['disabled'] = 'disabled';
    $semanticattributes['aria-disabled'] = 'true';
    $semanticattributes['title'] = get_string('rag_semantic_migration_prerequisites', 'block_ai_tutor');
}
$semanticform .= html_writer::tag('button', get_string('rag_action_migrate_semantic', 'block_ai_tutor'), $semanticattributes);
$semanticform .= html_writer::end_tag('form');
$links .= ' ' . $semanticform;
if ($canadmin) {
    $links .= ' ' . html_writer::link(new moodle_url('/blocks/ai_tutor/admin.php'), get_string('openglobalconfigbutton', 'block_ai_tutor'), ['class' => 'btn btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']);
    $links .= ' ' . html_writer::link(new moodle_url('/blocks/ai_tutor/admin.php'), get_string('opensettingspage', 'block_ai_tutor'), ['class' => 'btn btn-outline-secondary', 'target' => '_blank', 'rel' => 'noopener']);
}
echo html_writer::div($links, 'block_ai_tutor_manage_actions');
$mform->display();
echo $OUTPUT->footer();
