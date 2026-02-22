<?php
// blocks/ai_tutor/lang/en/block_ai_tutor.php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'VTutor - AI Assistant';
$string['block/ai_tutor:addinstance'] = 'Add VTutor block';
$string['block/ai_tutor:use'] = 'Use VTutor';

$string['mainsettings'] = 'Main settings';
$string['mainsettings_desc'] = 'Basic AI provider configuration';
$string['provider'] = 'AI provider';
$string['provider_desc'] = 'Select the AI provider';
$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Your API key for the selected provider';
$string['model'] = 'AI model';
$string['model_desc'] = 'Model name to use (e.g., gpt-3.5-turbo)';

$string['baseurl'] = 'Base URL / Endpoint';
$string['baseurl_desc'] = 'Base URL for OpenAI-compatible or Ollama endpoint (optional for official OpenAI).';

$string['pedagogysettings'] = 'Pedagogical settings';
$string['pedagogysettings_desc'] = 'Options to control tutor behavior';
$string['guideonly'] = 'Guide only (no direct answers)';
$string['guideonly_desc'] = 'The tutor will guide students without directly answering assignment questions';
$string['temperature'] = 'Creativity (Temperature)';
$string['temperature_desc'] = 'Value between 0.0 (precise) and 2.0 (creative). Recommended: 0.7';
$string['maxtokens'] = 'Maximum tokens';
$string['maxtokens_desc'] = 'Maximum response length';

$string['ragsettings'] = 'Context retrieval (RAG)';
$string['ragsettings_desc'] = 'Settings for searching course content';
$string['vectorstore'] = 'Vector store';
$string['vectorstore_desc'] = 'Where embeddings are stored for retrieval';
$string['chunksize'] = 'Chunk size';
$string['chunksize_desc'] = 'Words per chunk for indexing';

$string['webhookssettings'] = 'Webhooks (Optional)';
$string['webhookssettings_desc'] = 'Notifications to external systems';
$string['webhookurl'] = 'Webhook URL';
$string['webhookurl_desc'] = 'URL that will receive notifications';
$string['webhooksecret'] = 'Webhook secret';
$string['webhooksecret_desc'] = 'Secret key to verify authenticity';

$string['privacy:metadata:block_ai_tutor_msg'] = 'The VTutor block stores chat messages to provide continuity in conversations.';
$string['privacy:metadata:block_ai_tutor_msg:courseid'] = 'Course ID';
$string['privacy:metadata:block_ai_tutor_msg:userid'] = 'User ID';
$string['privacy:metadata:block_ai_tutor_msg:message'] = 'Student message';
$string['privacy:metadata:block_ai_tutor_msg:response'] = 'AI response';
$string['privacy:metadata:block_ai_tutor_msg:timecreated'] = 'Creation time';

$string['clearconfirm'] = 'Are you sure you want to delete the entire conversation? This action cannot be undone.';

$string['task_cleanup_old_messages'] = 'Cleanup old VTutor messages';

$string['gensettings'] = 'Generation settings';
$string['gensettings_desc'] = 'Controls for model output.';
$string['advanced'] = 'Advanced';
$string['advanced_desc'] = 'Optional settings for extended integrations.';

$string['adminpage'] = 'VTutor - Quick configuration';
$string['adminpage_desc'] = 'Explicit configuration page for environments where the plugin settings menu is not visible.';
$string['opensettingspage'] = 'Open standard plugin settings page';
$string['missingconfigfields'] = 'Required configuration fields are missing: {$a}';

$string['provider_deepseek'] = 'DeepSeek (direct API)';
$string['provider_qwen'] = 'Qwen (DashScope compatible API)';
$string['provider_openai'] = 'OpenAI';
$string['provider_gemini'] = 'Google Gemini';
$string['provider_openai_compatible'] = 'OpenAI-compatible (custom endpoint)';
$string['provider_ollama'] = 'Ollama (local)';
$string['openquickconfig'] = 'Open quick configuration page';
$string['providerexamples'] = 'Supported direct providers in v9: DeepSeek, Qwen, OpenAI and Gemini. You can configure from this page or from Plugins > Blocks.';
$string['provider_hint_deepseek'] = 'DeepSeek: provider=deepseek, baseurl=https://api.deepseek.com/v1, model=deepseek-chat';
$string['provider_hint_qwen'] = 'Qwen (DashScope): provider=qwen, baseurl=https://dashscope.aliyuncs.com/compatible-mode/v1, model=qwen-turbo (or your enabled Qwen model)';
$string['provider_hint_openai'] = 'OpenAI: provider=openai, baseurl=https://api.openai.com/v1, model=gpt-4o-mini (or your enabled model)';
$string['provider_hint_gemini'] = 'Gemini: provider=gemini, baseurl=https://generativelanguage.googleapis.com/v1beta/models, model=gemini-1.5-flash';

$string['rag_enabled'] = 'Enable RAG retrieval';
$string['rag_enabled_desc'] = 'Use indexed course content to answer with context.';
$string['rag_autolazy'] = 'Auto-index on first question';
$string['rag_autolazy_desc'] = 'If the course has no index yet, build a basic index automatically.';
$string['rag_topk'] = 'RAG top-k chunks';
$string['rag_topk_desc'] = 'Number of retrieved chunks sent to the model.';
$string['rag_reindex'] = 'Reindex course content (RAG)';
$string['rag_reindex_help'] = 'Enter a course ID, or leave 0 to reindex all courses (admin only).';
$string['rag_reindex_done'] = 'RAG reindex completed: {$a}';
$string['courseid'] = 'Course ID';
$string['ragdisablednotice'] = 'RAG temporarily disabled in this stable version. Use the test branch for advanced RAG.';
