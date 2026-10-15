<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// blocks/ai_tutor/admin.php
define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

try {
    \block_ai_tutor\local\schema::inspect_tables();
} catch (\Throwable $e) {
    debugging('VTutor schema guard failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
}

$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/blocks/ai_tutor/admin.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('adminpage', 'block_ai_tutor'));
$PAGE->set_heading(get_string('adminpage', 'block_ai_tutor'));

// Release contract for v10.6.5 FINAL: only DeepSeek is exposed as supported.
$providers = [
    'deepseek' => get_string('provider_deepseek', 'block_ai_tutor'),
];

$providerdefaults = [
    'deepseek' => ['model' => 'deepseek-v4-flash'],
];

$savechanges = optional_param('savechanges', 0, PARAM_BOOL);
if ($savechanges) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        throw new moodle_exception('invalidrequest', 'error');
    }
    require_sesskey();
    $provider = optional_param('provider', 'deepseek', PARAM_ALPHANUMEXT);
    if (!array_key_exists($provider, $providers)) {
        $provider = 'deepseek';
    }

    $rawmap = [
        'api_key' => PARAM_RAW,
        'model' => PARAM_RAW_TRIMMED,
        'temperature' => PARAM_RAW_TRIMMED,
        'max_tokens' => PARAM_INT,
        'guideonly' => PARAM_BOOL,
        'rag_deepseek_semantic_enabled' => PARAM_BOOL,
        'rag_deepseek_rerank_enabled' => PARAM_BOOL,
        'rag_semantic_timeout' => PARAM_INT,
        'rag_semantic_batch_size' => PARAM_INT,
        'institution_name' => PARAM_TEXT,
        'institution_subtitle' => PARAM_TEXT,
        'institution_response_directive' => PARAM_RAW_TRIMMED,
        'institution_document_guidance' => PARAM_RAW_TRIMMED,
        'institution_footer_note' => PARAM_RAW_TRIMMED,
        'institution_cover_image_url' => PARAM_URL,
        'institution_logo_image_url' => PARAM_URL,
        'institution_cover_kicker' => PARAM_TEXT,
        'institution_cover_title' => PARAM_TEXT,
        'institution_cover_message' => PARAM_RAW_TRIMMED,
        'institution_cover_cta' => PARAM_RAW_TRIMMED,
        'global_master_prompt' => PARAM_RAW_TRIMMED,
        'global_document_prompt' => PARAM_RAW_TRIMMED,
    ];

    set_config('provider', 'deepseek', 'block_ai_tutor');
    set_config('baseurl', \block_ai_tutor\ai_service::official_base_url(), 'block_ai_tutor');
    foreach ($rawmap as $name => $paramtype) {
        $value = optional_param($name, '', $paramtype);
        if (in_array($name, ['guideonly', 'rag_deepseek_semantic_enabled',
                'rag_deepseek_rerank_enabled'], true)) {
            set_config($name, $value ? 1 : 0, 'block_ai_tutor');
        } else {
            $value = trim((string)$value);
            if ($name === 'api_key' && $value === '') {
                // A blank password field means "keep the configured secret".
                continue;
            }
            if ($name === 'model') {
                $value = \block_ai_tutor\ai_service::normalize_model_name($provider, $value);
            } else if ($name === 'temperature') {
                $value = \block_ai_tutor\ai_service::normalize_temperature($value);
            } else if ($name === 'max_tokens') {
                $value = \block_ai_tutor\ai_service::normalize_max_tokens($value);
            } else if ($name === 'rag_semantic_timeout') {
                $value = max(10, min(180, (int)$value));
            } else if ($name === 'rag_semantic_batch_size') {
                $value = max(1, min(8, (int)$value));
            } else if (in_array($name, ['institution_cover_image_url', 'institution_logo_image_url'], true)) {
                $value = \block_ai_tutor\local\input_guard::https_url($value);
            }
            set_config($name, $value, 'block_ai_tutor');
        }
    }

    redirect(new moodle_url('/blocks/ai_tutor/admin.php'), get_string('changessaved'));
}

$currentprovider = 'deepseek';
$defaults = $providerdefaults[$currentprovider] ?? ['model' => 'deepseek-v4-flash'];
$semanticenabled = get_config('block_ai_tutor', 'rag_deepseek_semantic_enabled');
$rerankenabled = get_config('block_ai_tutor', 'rag_deepseek_rerank_enabled');
$current = (object) [
    'provider' => $currentprovider,
    'api_key' => (string)(get_config('block_ai_tutor', 'api_key') ?: ''),
    'model' => (string)(get_config('block_ai_tutor', 'model') ?: ($defaults['model'] ?? '')),
    'guideonly' => (int)\block_ai_tutor\local\config::bool('guideonly', true),
    'temperature' => (string)(get_config('block_ai_tutor', 'temperature') === false ? '0.15' : get_config('block_ai_tutor', 'temperature')),
    'max_tokens' => (int)(get_config('block_ai_tutor', 'max_tokens') ?: 1200),
    'rag_deepseek_semantic_enabled' => $semanticenabled === false ? 1 : (int)$semanticenabled,
    'rag_deepseek_rerank_enabled' => $rerankenabled === false ? 0 : (int)$rerankenabled,
    'rag_semantic_timeout' => (int)(get_config('block_ai_tutor', 'rag_semantic_timeout') ?: 60),
    'rag_semantic_batch_size' => (int)(get_config('block_ai_tutor', 'rag_semantic_batch_size') ?: 4),
    'institution_name' => (string)(get_config('block_ai_tutor', 'institution_name') ?: 'Centro de Capacitación V-Zona'),
    'institution_subtitle' => (string)(get_config('block_ai_tutor', 'institution_subtitle') ?: 'Formación profesional, docente y de especialización'),
    'institution_response_directive' => (string)(get_config('block_ai_tutor', 'institution_response_directive') ?: 'Responder con tono ejecutivo-pedagógico, precisión conceptual, orientación aplicable al trabajo, sustento documental cuando exista y respeto por la ética profesional.'),
    'institution_document_guidance' => (string)(get_config('block_ai_tutor', 'institution_document_guidance') ?: 'Cuando existan documentos especializados del curso, priorizar reglamentos, protocolos, manuales, instructivos, guías metodológicas, formatos institucionales y material técnico oficialmente cargado en Moodle.'),
    'institution_footer_note' => (string)(get_config('block_ai_tutor', 'institution_footer_note') ?: 'VTutor Pro V-Zona orienta, estructura y fundamenta respuestas con apoyo documental; la validación profesional final corresponde al participante, tutor o coordinador.'),
    'institution_cover_image_url' => (string)(get_config('block_ai_tutor', 'institution_cover_image_url') ?: ''),
    'institution_logo_image_url' => (string)(get_config('block_ai_tutor', 'institution_logo_image_url') ?: ''),
    'institution_cover_kicker' => (string)(get_config('block_ai_tutor', 'institution_cover_kicker') ?: 'Plataforma profesional de acompañamiento inteligente'),
    'institution_cover_title' => (string)(get_config('block_ai_tutor', 'institution_cover_title') ?: 'VTutor Pro V-Zona'),
    'institution_cover_message' => (string)(get_config('block_ai_tutor', 'institution_cover_message') ?: 'Asistente institucional con base documental para docencia, formación continua y cursos de especialización.'),
    'institution_cover_cta' => (string)(get_config('block_ai_tutor', 'institution_cover_cta') ?: 'Consulte contenidos, protocolos, manuales y recursos técnicos del curso con una experiencia visual consistente en laptop, PC, tablet y celular.'),
    'global_master_prompt' => (string)(get_config('block_ai_tutor', 'global_master_prompt') ?: ''),
    'global_document_prompt' => (string)(get_config('block_ai_tutor', 'global_document_prompt') ?: ''),
];

$missing = [];
foreach (['provider', 'model'] as $k) {
    if (empty($current->{$k})) {
        $missing[] = $k;
    }
}
if (empty($current->api_key)) {
    $missing[] = 'api_key';
}

function block_ai_tutor_admin_input($label, $name, $value, $type = 'text', $attrs = []) {
    $id = 'id_' . $name;
    $baseattrs = ['id' => $id, 'name' => $name, 'class' => 'form-control mb-3'];
    $attrs = array_merge($baseattrs, $attrs);
    echo html_writer::tag('label', $label, ['for' => $id]);
    if ($type === 'textarea') {
        echo html_writer::tag('textarea', s((string)$value), $attrs + ['rows' => 4]);
    } else {
        $attrs['type'] = $type;
        $attrs['value'] = s((string)$value);
        echo html_writer::empty_tag('input', $attrs);
    }
}

echo $OUTPUT->header();
echo html_writer::tag('style', '
.block-ai-admin-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;align-items:start}
.block-ai-admin-card{background:#fff;border:1px solid #d9e4ef;border-radius:16px;padding:1rem;box-shadow:0 10px 24px rgba(15,23,42,.05)}
.block-ai-admin-card h4{margin:0 0 .75rem 0;font-size:1rem}
.block-ai-admin-note{margin:.5rem 0 0 0;color:#516070}
.block-ai-admin-links a{margin-right:.5rem;margin-bottom:.5rem}
');
echo $OUTPUT->heading(get_string('adminpage', 'block_ai_tutor'));
echo html_writer::tag('p', get_string('adminpage_desc', 'block_ai_tutor'));
echo $OUTPUT->notification(get_string('professionalvariant_admin_note', 'block_ai_tutor'), 'success');
echo $OUTPUT->notification(get_string('institutional_admin_note', 'block_ai_tutor'), 'info');
echo $OUTPUT->notification(get_string('providerexamples', 'block_ai_tutor'), 'info');
if (!empty($missing)) {
    echo $OUTPUT->notification(get_string('missingconfigfields', 'block_ai_tutor', implode(', ', $missing)), 'warning');
}

echo html_writer::start_div('block-ai-admin-links mb-3');
echo html_writer::link(new moodle_url('/blocks/ai_tutor/admin.php'), get_string('opensettingspage', 'block_ai_tutor'), ['class' => 'btn btn-secondary']);
echo html_writer::link(new moodle_url('/admin/purgecaches.php'), get_string('purgecacheslabel', 'block_ai_tutor'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::link(new moodle_url('/blocks/ai_tutor/diagnostico.php'), get_string('diagnosticobutton', 'block_ai_tutor'), ['class' => 'btn btn-outline-warning']);
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/blocks/ai_tutor/admin.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savechanges', 'value' => 1]);

echo html_writer::start_div('block-ai-admin-grid');

echo html_writer::start_div('block-ai-admin-card');
echo html_writer::tag('h4', get_string('admintab_ai', 'block_ai_tutor'));
echo html_writer::tag('label', get_string('provider', 'block_ai_tutor'), ['for' => 'id_provider']);
echo html_writer::start_tag('select', ['name' => 'provider', 'id' => 'id_provider', 'class' => 'form-select mb-3']);
foreach ($providers as $value => $label) {
    $attrs = ['value' => $value];
    if ($current->provider === $value) {
        $attrs['selected'] = 'selected';
    }
    echo html_writer::tag('option', s($label), $attrs);
}
echo html_writer::end_tag('select');
block_ai_tutor_admin_input(get_string('apikey', 'block_ai_tutor'), 'api_key', '', 'password', [
    'autocomplete' => 'new-password',
    'placeholder' => $current->api_key !== '' ? get_string('apikey_keep_configured', 'block_ai_tutor') : '',
]);
echo html_writer::tag('label', get_string('model', 'block_ai_tutor'), ['for' => 'id_model']);
echo html_writer::select([
    'deepseek-v4-flash' => 'deepseek-v4-flash',
    'deepseek-v4-pro' => 'deepseek-v4-pro',
], 'model', $current->model, false, ['id' => 'id_model', 'class' => 'form-control mb-3']);
echo html_writer::tag('p', 'Endpoint: ' . s(\block_ai_tutor\ai_service::official_base_url()), ['class' => 'block-ai-admin-note']);
block_ai_tutor_admin_input(get_string('temperature', 'block_ai_tutor'), 'temperature', $current->temperature);
block_ai_tutor_admin_input(get_string('maxtokens', 'block_ai_tutor'), 'max_tokens', $current->max_tokens,
    'number', ['min' => 512, 'max' => 4096, 'step' => 128]);
$checkboxattrs = ['type' => 'checkbox', 'name' => 'guideonly', 'id' => 'id_guideonly', 'value' => 1, 'class' => 'form-check-input'];
if ($current->guideonly) {
    $checkboxattrs['checked'] = 'checked';
}
echo html_writer::start_div('form-check mb-1');
echo html_writer::empty_tag('input', $checkboxattrs);
echo html_writer::tag('label', get_string('guideonly', 'block_ai_tutor'), ['for' => 'id_guideonly', 'class' => 'form-check-label']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('block-ai-admin-card');
echo html_writer::tag('h4', get_string('rag_internal_settings', 'block_ai_tutor'));
block_ai_tutor_admin_input(get_string('rag_semantic_timeout', 'block_ai_tutor'),
    'rag_semantic_timeout', $current->rag_semantic_timeout, 'number', ['min' => 10, 'max' => 180, 'step' => 1]);
block_ai_tutor_admin_input(get_string('rag_semantic_batch_size', 'block_ai_tutor'),
    'rag_semantic_batch_size', $current->rag_semantic_batch_size, 'number', ['min' => 1, 'max' => 8, 'step' => 1]);
foreach (['rag_deepseek_semantic_enabled', 'rag_deepseek_rerank_enabled'] as $settingname) {
    $attrs = ['type' => 'checkbox', 'name' => $settingname, 'id' => 'id_' . $settingname,
        'value' => 1, 'class' => 'form-check-input'];
    if ($current->{$settingname}) {
        $attrs['checked'] = 'checked';
    }
    echo html_writer::start_div('form-check mb-2');
    echo html_writer::empty_tag('input', $attrs);
    echo html_writer::tag('label', get_string($settingname, 'block_ai_tutor'),
        ['for' => 'id_' . $settingname, 'class' => 'form-check-label']);
    echo html_writer::end_div();
}
echo html_writer::tag('p', get_string('rag_semantic_recovery_note', 'block_ai_tutor'), ['class' => 'block-ai-admin-note']);
echo html_writer::end_div();

echo html_writer::start_div('block-ai-admin-card');
echo html_writer::tag('h4', get_string('admintab_visual', 'block_ai_tutor'));
block_ai_tutor_admin_input(get_string('institution_name', 'block_ai_tutor'), 'institution_name', $current->institution_name);
block_ai_tutor_admin_input(get_string('institution_subtitle', 'block_ai_tutor'), 'institution_subtitle', $current->institution_subtitle);
block_ai_tutor_admin_input(get_string('institution_logo_image_url', 'block_ai_tutor'), 'institution_logo_image_url', $current->institution_logo_image_url);
block_ai_tutor_admin_input(get_string('institution_cover_image_url', 'block_ai_tutor'), 'institution_cover_image_url', $current->institution_cover_image_url);
block_ai_tutor_admin_input(get_string('institution_cover_kicker', 'block_ai_tutor'), 'institution_cover_kicker', $current->institution_cover_kicker);
block_ai_tutor_admin_input(get_string('institution_cover_title', 'block_ai_tutor'), 'institution_cover_title', $current->institution_cover_title);
block_ai_tutor_admin_input(get_string('institution_cover_message', 'block_ai_tutor'), 'institution_cover_message', $current->institution_cover_message, 'textarea', ['rows' => 4]);
block_ai_tutor_admin_input(get_string('institution_cover_cta', 'block_ai_tutor'), 'institution_cover_cta', $current->institution_cover_cta, 'textarea', ['rows' => 4]);
echo html_writer::end_div();

echo html_writer::start_div('block-ai-admin-card');
echo html_writer::tag('h4', get_string('admintab_prompts', 'block_ai_tutor'));
block_ai_tutor_admin_input(get_string('institution_response_directive', 'block_ai_tutor'), 'institution_response_directive', $current->institution_response_directive, 'textarea', ['rows' => 4]);
block_ai_tutor_admin_input(get_string('institution_document_guidance', 'block_ai_tutor'), 'institution_document_guidance', $current->institution_document_guidance, 'textarea', ['rows' => 4]);
block_ai_tutor_admin_input(get_string('global_master_prompt', 'block_ai_tutor'), 'global_master_prompt', $current->global_master_prompt, 'textarea', ['rows' => 5]);
block_ai_tutor_admin_input(get_string('global_document_prompt', 'block_ai_tutor'), 'global_document_prompt', $current->global_document_prompt, 'textarea', ['rows' => 5]);
block_ai_tutor_admin_input(get_string('institution_footer_note', 'block_ai_tutor'), 'institution_footer_note', $current->institution_footer_note, 'textarea', ['rows' => 3]);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary mt-3', 'value' => get_string('savechanges')]);
echo html_writer::end_tag('form');

echo html_writer::tag('ul',
    html_writer::tag('li', get_string('provider_hint_deepseek', 'block_ai_tutor')),
    ['class' => 'mt-3']
);

echo $OUTPUT->footer();
