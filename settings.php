<?php
// blocks/ai_tutor/settings.php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    if (!isset($settings) || !($settings instanceof admin_settingpage)) {
        $settings = new admin_settingpage('blocksettingai_tutor', get_string('pluginname', 'block_ai_tutor'));
    }

    $providers = [
        'deepseek' => get_string('provider_deepseek', 'block_ai_tutor'),
        'qwen' => get_string('provider_qwen', 'block_ai_tutor'),
        'openai' => get_string('provider_openai', 'block_ai_tutor'),
        'gemini' => get_string('provider_gemini', 'block_ai_tutor'),
        'openai_compatible' => get_string('provider_openai_compatible', 'block_ai_tutor'),
        'ollama' => get_string('provider_ollama', 'block_ai_tutor'),
    ];

    $settings->add(new admin_setting_heading('block_ai_tutor/main',
        get_string('mainsettings', 'block_ai_tutor'),
        get_string('mainsettings_desc', 'block_ai_tutor')));

    $settings->add(new admin_setting_configselect('block_ai_tutor/provider',
        get_string('provider', 'block_ai_tutor'),
        get_string('provider_desc', 'block_ai_tutor'),
        'deepseek', $providers));

    $settings->add(new admin_setting_configpasswordunmask('block_ai_tutor/api_key',
        get_string('apikey', 'block_ai_tutor'),
        get_string('apikey_desc', 'block_ai_tutor'), ''));

    $settings->add(new admin_setting_configtext('block_ai_tutor/model',
        get_string('model', 'block_ai_tutor'),
        get_string('model_desc', 'block_ai_tutor'),
        'deepseek-chat', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/baseurl',
        get_string('baseurl', 'block_ai_tutor'),
        get_string('baseurl_desc', 'block_ai_tutor'),
        'https://api.deepseek.com/v1', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/guideonly',
        get_string('guideonly', 'block_ai_tutor'),
        get_string('guideonly_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/temperature',
        get_string('temperature', 'block_ai_tutor'),
        get_string('temperature_desc', 'block_ai_tutor'),
        '0.2', PARAM_FLOAT));


    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/rag_enabled',
        get_string('rag_enabled', 'block_ai_tutor'),
        get_string('rag_enabled_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/rag_autolazy',
        get_string('rag_autolazy', 'block_ai_tutor'),
        get_string('rag_autolazy_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_topk',
        get_string('rag_topk', 'block_ai_tutor'),
        get_string('rag_topk_desc', 'block_ai_tutor'),
        '5', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/max_tokens',
        get_string('maxtokens', 'block_ai_tutor'),
        get_string('maxtokens_desc', 'block_ai_tutor'),
        '1024', PARAM_INT));

    $settings->add(new admin_setting_heading('block_ai_tutor/adminfallback',
        get_string('adminpage', 'block_ai_tutor'),
        get_string('adminpage_desc', 'block_ai_tutor') . '<br>' .
        html_writer::link(new moodle_url('/blocks/ai_tutor/admin.php'), get_string('openquickconfig', 'block_ai_tutor'))));
}
