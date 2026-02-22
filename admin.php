<?php
// blocks/ai_tutor/admin.php
define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$PAGE->set_context($systemcontext);
$PAGE->set_url(new moodle_url('/blocks/ai_tutor/admin.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('adminpage', 'block_ai_tutor'));
$PAGE->set_heading(get_string('adminpage', 'block_ai_tutor'));

$providers = [
    'deepseek' => get_string('provider_deepseek', 'block_ai_tutor'),
    'qwen' => get_string('provider_qwen', 'block_ai_tutor'),
    'openai' => get_string('provider_openai', 'block_ai_tutor'),
    'gemini' => get_string('provider_gemini', 'block_ai_tutor'),
    'openai_compatible' => get_string('provider_openai_compatible', 'block_ai_tutor'),
    'ollama' => get_string('provider_ollama', 'block_ai_tutor'),
];

$providerdefaults = json_decode('"{\"deepseek\": {\"baseurl\": \"https://api.deepseek.com/v1\", \"model\": \"deepseek-chat\"}, \"qwen\": {\"baseurl\": \"https://dashscope.aliyuncs.com/compatible-mode/v1\", \"model\": \"qwen-turbo\"}, \"openai\": {\"baseurl\": \"https://api.openai.com/v1\", \"model\": \"gpt-4o-mini\"}, \"gemini\": {\"baseurl\": \"https://generativelanguage.googleapis.com/v1beta/models\", \"model\": \"gemini-1.5-flash\"}, \"openai_compatible\": {\"baseurl\": \"\", \"model\": \"\"}, \"ollama\": {\"baseurl\": \"http://127.0.0.1:11434\", \"model\": \"qwen2.5\"}}"', true);

if (optional_param('savechanges', 0, PARAM_BOOL) && confirm_sesskey()) {
    require_sesskey();
    $provider = optional_param('provider', 'deepseek', PARAM_ALPHANUMEXT);
    if (!array_key_exists($provider, $providers)) {
        $provider = 'deepseek';
    }
    $apikey = trim((string)optional_param('api_key', '', PARAM_RAW));
    $model = trim((string)optional_param('model', '', PARAM_RAW_TRIMMED));
    $baseurl = trim((string)optional_param('baseurl', '', PARAM_RAW_TRIMMED));
    $guideonly = optional_param('guideonly', 0, PARAM_BOOL);
    $temperature = trim((string)optional_param('temperature', '0.2', PARAM_RAW_TRIMMED));

    set_config('provider', $provider, 'block_ai_tutor');
    set_config('api_key', $apikey, 'block_ai_tutor');
    set_config('model', $model, 'block_ai_tutor');
    set_config('baseurl', $baseurl, 'block_ai_tutor');
    set_config('guideonly', $guideonly ? 1 : 0, 'block_ai_tutor');
    set_config('temperature', $temperature, 'block_ai_tutor');

    redirect(new moodle_url('/blocks/ai_tutor/admin.php'), get_string('changessaved'));
}

$currentprovider = get_config('block_ai_tutor', 'provider') ?: 'deepseek';
$defaults = $providerdefaults[$currentprovider] ?? ['baseurl' => '', 'model' => ''];
$current = (object) [
    'provider' => $currentprovider,
    'api_key' => (string)(get_config('block_ai_tutor', 'api_key') ?: ''),
    'model' => (string)(get_config('block_ai_tutor', 'model') ?: ($defaults['model'] ?? '')),
    'baseurl' => (string)(get_config('block_ai_tutor', 'baseurl') ?: ($defaults['baseurl'] ?? '')),
    'guideonly' => (int)(get_config('block_ai_tutor', 'guideonly') ?? 1),
    'temperature' => (string)(get_config('block_ai_tutor', 'temperature') ?: '0.2'),
];

$missing = [];
foreach (['provider', 'model'] as $k) {
    if (empty($current->{$k})) {
        $missing[] = $k;
    }
}
if ($current->provider !== 'ollama' && empty($current->api_key)) {
    $missing[] = 'api_key';
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('adminpage', 'block_ai_tutor'));
echo html_writer::tag('p', get_string('adminpage_desc', 'block_ai_tutor'));
echo $OUTPUT->notification(get_string('providerexamples', 'block_ai_tutor'), 'info');
if (!empty($missing)) {
    echo $OUTPUT->notification(get_string('missingconfigfields', 'block_ai_tutor', implode(', ', $missing)), 'warning');
}

$reindexcourse = optional_param('courseidreindex', 0, PARAM_INT);
echo html_writer::start_div('mb-3 p-3 border rounded bg-light');
echo html_writer::tag('h5', get_string('ragsettings', 'block_ai_tutor'));
echo html_writer::tag('p', get_string('ragsettings_desc', 'block_ai_tutor'));
echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/blocks/ai_tutor/admin.php'))->out(false), 'class' => 'row g-2 align-items-end']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('col-md-4');
echo html_writer::tag('label', get_string('courseid', 'block_ai_tutor'), ['for' => 'id_courseidreindex']);
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'courseidreindex', 'id' => 'id_courseidreindex', 'value' => (int)$reindexcourse, 'class' => 'form-control', 'min' => 0]);
echo html_writer::end_div();
echo html_writer::start_div('col-md-8');
echo html_writer::empty_tag('button', ['type' => 'submit', 'name' => 'reindexcourse', 'value' => 1, 'class' => 'btn btn-secondary'], get_string('rag_reindex', 'block_ai_tutor'));
echo html_writer::tag('small', ' ' . get_string('rag_reindex_help', 'block_ai_tutor'), ['class' => 'text-muted ms-2']);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::start_tag('form', ['method' => 'post', 'action' => (new moodle_url('/blocks/ai_tutor/admin.php'))->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'savechanges', 'value' => 1]);

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

echo html_writer::tag('label', get_string('apikey', 'block_ai_tutor'), ['for' => 'id_api_key']);
echo html_writer::empty_tag('input', ['type' => 'password', 'name' => 'api_key', 'id' => 'id_api_key', 'value' => s($current->api_key), 'class' => 'form-control mb-3', 'autocomplete' => 'new-password']);

echo html_writer::tag('label', get_string('model', 'block_ai_tutor'), ['for' => 'id_model']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'model', 'id' => 'id_model', 'value' => s($current->model), 'class' => 'form-control mb-3']);

echo html_writer::tag('label', get_string('baseurl', 'block_ai_tutor'), ['for' => 'id_baseurl']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'baseurl', 'id' => 'id_baseurl', 'value' => s($current->baseurl), 'class' => 'form-control mb-3']);

echo html_writer::tag('label', get_string('temperature', 'block_ai_tutor'), ['for' => 'id_temperature']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'temperature', 'id' => 'id_temperature', 'value' => s($current->temperature), 'class' => 'form-control mb-3']);

$checkboxattrs = ['type' => 'checkbox', 'name' => 'guideonly', 'id' => 'id_guideonly', 'value' => 1, 'class' => 'form-check-input'];
if ($current->guideonly) {
    $checkboxattrs['checked'] = 'checked';
}
echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', $checkboxattrs);
echo html_writer::tag('label', get_string('guideonly', 'block_ai_tutor'), ['for' => 'id_guideonly', 'class' => 'form-check-label']);
echo html_writer::end_div();

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]);
echo html_writer::end_tag('form');

echo html_writer::tag('hr', '');
echo html_writer::tag('p', html_writer::link(new moodle_url('/admin/settings.php', ['section' => 'blocksettingai_tutor']), get_string('opensettingspage', 'block_ai_tutor')));
echo html_writer::tag('ul',
    html_writer::tag('li', get_string('provider_hint_deepseek', 'block_ai_tutor')) .
    html_writer::tag('li', get_string('provider_hint_qwen', 'block_ai_tutor')) .
    html_writer::tag('li', get_string('provider_hint_openai', 'block_ai_tutor')) .
    html_writer::tag('li', get_string('provider_hint_gemini', 'block_ai_tutor'))
);

$jsdefaults = '{"deepseek": {"baseurl": "https://api.deepseek.com/v1", "model": "deepseek-chat"}, "qwen": {"baseurl": "https://dashscope.aliyuncs.com/compatible-mode/v1", "model": "qwen-turbo"}, "openai": {"baseurl": "https://api.openai.com/v1", "model": "gpt-4o-mini"}, "gemini": {"baseurl": "https://generativelanguage.googleapis.com/v1beta/models", "model": "gemini-1.5-flash"}, "openai_compatible": {"baseurl": "", "model": ""}, "ollama": {"baseurl": "http://127.0.0.1:11434", "model": "qwen2.5"}}';
echo '<script>(function(){ var defaults = ' . $jsdefaults . '; var sel=document.getElementById("id_provider"); var base=document.getElementById("id_baseurl"); var model=document.getElementById("id_model"); if(!sel||!base||!model) return; sel.addEventListener("change", function(){ var d=defaults[this.value]||{}; if(!base.value.trim()&&d.baseurl) base.value=d.baseurl; if(!model.value.trim()&&d.model) model.value=d.model; }); })();</script>';
echo $OUTPUT->footer();
