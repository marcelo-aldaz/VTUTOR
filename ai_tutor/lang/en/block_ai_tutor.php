<?php
// blocks/ai_tutor/lang/es/block_ai_tutor.php
defined('MOODLE_INTERNAL') || die();

$string['block/ai_tutor:addinstance'] = 'Add a VTutor block';
$string['block/ai_tutor:use'] = 'Use VTutor';

// Configuración
$string['mainsettings'] = 'Main settings';
$string['mainsettings_desc'] = 'Basic configuration for the artificial intelligence provider.';
$string['provider'] = 'AI provider';
$string['provider_desc'] = 'Select the artificial intelligence provider.';
$string['apikey'] = 'API key';
$string['apikey_desc'] = 'Your API key for the selected provider.';
$string['apikey_keep_configured'] = 'Key configured; leave blank to keep it';
$string['model'] = 'AI model';
$string['model_desc'] = 'Exact model name. For DeepSeek use deepseek-v4-flash or deepseek-v4-pro in lowercase.';


// Pedagógica
$string['pedagogysettings'] = 'Pedagogical settings';
$string['pedagogysettings_desc'] = 'Options that control tutor behaviour.';
$string['guideonly'] = 'Guidance only (do not give answers)';
$string['guideonly_desc'] = 'The tutor will guide students without providing direct answers to assignments.';
$string['temperature'] = 'Creativity (temperature)';
$string['temperature_desc'] = 'Value between 0.0 (precise) and 2.0 (creative). Recommended: 0.7';
$string['maxtokens'] = 'Maximum tokens';
$string['maxtokens_desc'] = 'Generation tokens per request (512–4096). Recommended: 1200. If the provider reaches this limit, VTutor allows one short closing continuation to avoid loops and unnecessary usage.';

// RAG
$string['ragsettings'] = 'Context retrieval (RAG)';
$string['ragsettings_desc'] = 'Settings for searching course content.';
$string['vectorstore'] = 'Vector store';
$string['vectorstore_desc'] = 'Where embeddings are stored for retrieval.';
$string['chunksize'] = 'Chunk size';
$string['chunksize_desc'] = 'Words per chunk for indexing.';

// Webhooks
$string['webhookssettings'] = 'Webhooks (optional)';
$string['webhookssettings_desc'] = 'Notifications to external systems.';
$string['webhookurl'] = 'Webhook URL';
$string['webhookurl_desc'] = 'URL that will receive notifications.';
$string['webhooksecret'] = 'Webhook secret';
$string['webhooksecret_desc'] = 'Key used to verify authenticity.';

// Privacidad
$string['privacy:metadata:block_ai_tutor_msg'] = 'The VTutor block stores chat messages to provide conversation continuity.';
$string['privacy:metadata:block_ai_tutor_msg:courseid'] = 'Course ID.';
$string['privacy:metadata:block_ai_tutor_msg:userid'] = 'User ID.';
$string['privacy:metadata:block_ai_tutor_msg:message'] = 'Student message.';
$string['privacy:metadata:block_ai_tutor_msg:response'] = 'AI response.';
$string['privacy:metadata:block_ai_tutor_msg:timecreated'] = 'Creation time.';
$string['privacy:metadata:block_ai_tutor_msg:provider'] = 'AI provider used to generate the response.';
$string['privacy:metadata:block_ai_tutor_msg:model'] = 'AI model used to generate the response.';
$string['privacy:metadata:block_ai_tutor_msg:tokens_used'] = 'Token count recorded for the interaction, when available.';

// Varios
$string['clearconfirm'] = 'Are you sure you want to delete the entire conversation? This action cannot be undone.';
$string['task_cleanup_old_messages'] = 'Clean up old VTutor messages';

$string['gensettings'] = 'Generation settings';
$string['gensettings_desc'] = 'Model output controls.';
$string['advanced'] = 'Advanced';
$string['advanced_desc'] = 'Optional settings for extended integrations.';

$string['adminpage'] = 'VTutor Pro V-Zona - Quick configuration';
$string['adminpage_desc'] = 'Quick configuration page for the AI provider and V-Zona institutional identity in environments where the standard plugin menu is not easy to access.';
$string['missingconfigfields'] = 'Required configuration fields are missing: {$a}';

$string['provider_deepseek'] = 'DeepSeek (direct API)';
$string['openquickconfig'] = 'Open quick configuration page';
$string['providerexamples'] = 'Validated and supported provider in this final branch: DeepSeek. Experimental connectors for other providers are outside this release contract.';
$string['provider_hint_deepseek'] = 'DeepSeek: provider=deepseek, baseurl=https://api.deepseek.com/v1, model=deepseek-v4-flash';

$string['rag_topk'] = 'RAG chunk top-k';
$string['rag_topk_desc'] = 'Number of retrieved chunks sent to the model.';
$string['courseid'] = 'Course ID';


// Filtro de seguridad (solo administrador)
$string['securityfilter'] = 'Security filter';
$string['securityfilter_desc'] = 'Security and privacy controls for VTutor input, output and history. Recommended: managed by administrators only.';
$string['security_filter_enabled'] = 'Enable security filter';
$string['security_filter_enabled_desc'] = 'Applies limits, trimming and persistence policies to messages and responses.';
$string['security_max_input_chars'] = 'Maximum input characters';
$string['security_max_input_chars_desc'] = 'Maximum length of a user message.';
$string['security_max_output_chars'] = 'Maximum output characters';
$string['security_max_output_chars_desc'] = 'Maximum response length shown to the user. Recommended: 8000; truncation always uses a readable boundary.';
$string['security_history_max_limit'] = 'Maximum retrievable history';
$string['security_history_max_limit_desc'] = 'Maximum number of messages returned by the history endpoint.';
$string['security_store_mode'] = 'History storage mode';
$string['security_store_mode_desc'] = 'Controls how much chat history is stored. Use Full to preserve complete answers after reloading.';
$string['security_store_mode_full'] = 'Full';
$string['security_store_mode_truncate'] = 'Truncated';
$string['security_store_mode_metadata'] = 'Marker only (no content)';
$string['security_store_mode_none'] = 'Do not store history';
$string['security_store_truncate_chars'] = 'History truncation characters';
$string['security_store_truncate_chars_desc'] = 'Maximum number of characters stored per message/response in truncated mode.';
$string['security_mask_sensitive'] = 'Mask basic sensitive data';
$string['security_mask_sensitive_desc'] = 'Masks email addresses, phone numbers and long identifiers in messages, responses and logs.';
$string['security_block_patterns'] = 'Block patterns (one per line)';
$string['security_block_patterns_desc'] = 'If a message contains any of these strings, it is blocked. Use one pattern per line. Lines beginning with # are ignored.';

$string['target_population'] = 'Target population';
$string['target_population_desc'] = 'Target population used to adapt explanations and examples. The user profile (student, teacher or administrator) is detected automatically. Recommended value: people with incomplete schooling.';
$string['short_answers'] = 'Short answers';
$string['short_answers_desc'] = 'When enabled, VTutor answers briefly by default (up to 4 short lines), unless the user asks for more detail.';
$string['always_examples'] = 'Always include an example';
$string['always_examples_desc'] = 'When enabled, VTutor always includes a short example adapted to the target population.';
$string['profile_autodetect_note'] = 'The user profile is detected automatically from course permissions: student, teacher or administrator.';
$string['voice_limited_environment'] = 'Voice features are limited in this environment. Recommended: compatible Chrome/Edge browser and HTTPS site.';
$string['voice_apk_environment'] = 'The current app/APK limits voice features. To listen and dictate, use a compatible Chrome browser or a native Android integration.';
$string['voice_mobile_only'] = 'Voice dictation is available whenever the browser and device permissions allow it, on desktop, tablet and mobile.';

$string['humanized_release'] = 'Safe humanised release';

$string['ai_tutor:addinstance'] = 'Add a VTutor block';
$string['ai_tutor:myaddinstance'] = 'Add a VTutor block to the dashboard';
$string['ai_tutor:use'] = 'Use VTutor';

$string['studentresponsepolicy'] = 'Student response policy';
$string['studentresponsepolicy_desc'] = 'Administrative rules for keeping student responses within the configured length range.';
$string['student_word_policy_enabled'] = 'Enable the 150 to 200 word guideline for students';
$string['student_word_policy_enabled_desc'] = 'When enabled, VTutor answers within the configured range and treats the maximum as an operational ceiling for each new response.';
$string['student_word_min'] = 'Approximate minimum words for students';
$string['student_word_min_desc'] = 'Approximate minimum number of words per response to a student.';
$string['student_word_max'] = 'Approximate maximum words for students';
$string['student_word_max_desc'] = 'Approximate maximum number of words per response to a student.';
$string['copyresponse'] = 'Copy response';
$string['copiedresponse'] = 'Response copied';


$string['teacherresponsepolicy'] = 'Teacher response policy';
$string['teacherresponsepolicy_desc'] = 'Administrative rules for keeping teacher responses within a defined length range.';
$string['teacher_word_policy_enabled'] = 'Enable word-count range for teachers';
$string['teacher_word_policy_enabled_desc'] = 'When enabled, VTutor answers teachers within the configured range and treats the maximum as an operational ceiling for each new response.';
$string['teacher_word_min'] = 'Approximate minimum words for teachers';
$string['teacher_word_min_desc'] = 'Approximate minimum number of words per response to a teacher.';
$string['teacher_word_max'] = 'Approximate maximum words for teachers';
$string['teacher_word_max_desc'] = 'Approximate maximum number of words per response to a teacher.';
$string['adminresponsepolicy'] = 'Administrator response policy';
$string['adminresponsepolicy_desc'] = 'Administrative rules for keeping administrator responses within a defined length range.';
$string['admin_word_policy_enabled'] = 'Enable word-count range for administrators';
$string['admin_word_policy_enabled_desc'] = 'When enabled, VTutor answers administrators within the configured range and treats the maximum as an operational ceiling for each new response.';
$string['admin_word_min'] = 'Approximate minimum words for administrators';
$string['admin_word_min_desc'] = 'Approximate minimum number of words per response to an administrator.';
$string['admin_word_max'] = 'Approximate maximum words for administrators';
$string['admin_word_max_desc'] = 'Approximate maximum number of words per response to an administrator.';


$string['pluginname'] = 'VTutor Pro - Professional AI Assistant';
$string['professionalvariant'] = 'Production professional variant';
$string['professionalvariant_desc'] = 'Settings for training centres, continuing education and courses aimed at teaching professionals and other professional fields.';
$string['site_variant'] = 'Installation type';
$string['site_variant_desc'] = 'Defines whether VTutor behaves as a general academic tutor or as a production professional tutor.';
$string['site_variant_standard'] = 'Academic standard';
$string['site_variant_professional'] = 'Production professional';
$string['professional_sector'] = 'Professional sector or field';
$string['professional_sector_desc'] = 'Primary field used to contextualise language, examples and professional guidance.';
$string['professional_response_focus'] = 'Professional response focus';
$string['professional_response_focus_desc'] = 'Institutional directive for responses with greater technical rigour and practical applicability.';
$string['professional_source_priority'] = 'Prioritise course documents over general knowledge';
$string['professional_source_priority_desc'] = 'When retrieved context or indexed documents are available, VTutor uses them as the primary basis for the response and supplements with general knowledge only when necessary.';
$string['professional_natural_sources'] = 'Mention course documents naturally';
$string['professional_natural_sources_desc'] = 'Allows VTutor to mention document or course-resource titles naturally when this improves professional guidance.';
$string['professionalvariant_admin_note'] = 'This institutional edition processes a minimal PDF, Markdown or TXT document base to reduce dependencies and preserve traceability.';

$string['institutionalvariant'] = 'V-Zona institutional identity';
$string['institutionalvariant_desc'] = 'Parameters for presenting VTutor as the institutional assistant of the V-Zona Training Centre, with a professional tone and document-oriented guidance.';
$string['institution_name'] = 'Visible institution name';
$string['institution_name_desc'] = 'Name displayed by VTutor in the institutional interface.';
$string['institution_subtitle'] = 'Institutional subtitle';
$string['institution_subtitle_desc'] = 'Short phrase displayed in the assistant header.';
$string['institution_response_directive'] = 'Institutional response directive';
$string['institution_response_directive_desc'] = 'Institutional style and purpose rule that VTutor includes in the main prompt.';
$string['institution_document_guidance'] = 'Institutional document-use directive';
$string['institution_document_guidance_desc'] = 'Specifies which course document types should be prioritised as the professional basis for responses.';
$string['institution_footer_note'] = 'Institutional footer note';
$string['institution_footer_note_desc'] = 'Notice displayed at the bottom of the interface.';
$string['institutional_admin_note'] = 'V-Zona institutional edition: this interface and assistant behaviour are optimised for professional training with document support in Moodle.';

$string['rag_reindex_error'] = 'Reindexing could not be completed: {$a}';
$string['rag_reindex_course_done'] = 'Reindexing completed in {$a->course}: {$a->sources} sources processed and {$a->chunks} chunks indexed.';
$string['documentsectiontitle'] = 'Recommended professional document base';
$string['documentsectiondesc'] = 'Create a course section with this name and upload the materials that VTutor should use as the professional basis for responses.';
$string['documentsectiontypes'] = 'Active ALPHA3 formats: selectable-text PDF, Markdown and TXT. Scanned PDFs require optional OCR.';
$string['documentsectionaction'] = 'After uploading or updating documents, save the control centre with processing enabled or use the internal button. Extraction runs directly inside Moodle and does not require web cron, ad hoc tasks or cPanel.';
$string['reindexcoursebutton'] = '🔄 Process/reindex course documents';


$string['institution_cover_image_url'] = 'Institutional cover image URL';
$string['institution_cover_image_url_desc'] = 'Optional. If left blank, VTutor uses the institutional cover image included with the plugin.';
$string['institution_logo_image_url'] = 'Institutional logo image URL';
$string['institution_logo_image_url_desc'] = 'Optional. If left blank, VTutor uses the institutional symbol included with the plugin.';
$string['institution_cover_kicker'] = 'Cover eyebrow text';
$string['institution_cover_kicker_desc'] = 'Short top-line text for the VTutor institutional cover.';
$string['institution_cover_title'] = 'Main cover title';
$string['institution_cover_title_desc'] = 'Main title displayed on the institutional cover.';
$string['institution_cover_message'] = 'Main cover message';
$string['institution_cover_message_desc'] = 'Descriptive text displayed on the VTutor institutional cover.';
$string['institution_cover_cta'] = 'Supplementary cover message';
$string['institution_cover_cta_desc'] = 'Short supporting phrase displayed on the institutional cover.';
$string['documentstatuspaneltitle'] = 'Course document-base status';
$string['documentstatscountlabel'] = 'Indexed documents';
$string['documentstatschunkslabel'] = 'Indexed chunks';
$string['documentstatslastlabel'] = 'Last reindex';
$string['documentstatsstatuslabel'] = 'Status';
$string['documentstatsnever'] = 'Not available yet';
$string['documentstatusempty'] = 'No indexed document base yet. Upload materials and run course reindexing.';
$string['documentstatusready'] = 'The document base is active and ready to support professional responses in this course.';
$string['documentstatusstale'] = 'The document base is available, but reindexing is recommended to reflect recent course changes.';
$string['documentstatusdisabled'] = 'Document indexing is disabled by administrative configuration.';
$string['documentstatusinternalmasterdisabled'] = 'The institutional mini-RAG engine is disabled globally.';
$string['documentstatuscoursedisabled'] = 'Document mini-RAG is not enabled for this course. Enable it in the control centre before processing documents.';
$string['managedocumentsbutton'] = '⚙️ VTutor Pro control centre';
$string['managecoursepage'] = 'VTutor Pro control centre';
$string['managecoursepage_intro'] = 'From this control centre you can manage the professional document base for course {$a}, review indexing status, define course prompts, adjust the image and customise visible VTutor Pro access.';
$string['managecoursepage_note'] = 'The main VTutor Pro conversation now opens in a separate resizable window. Document management, prompts, image and visible course access are all managed from this control centre.';
$string['managecoursepage_backcourse'] = '← Back to course';
$string['managecoursepage_docs_header'] = 'Professional course document base';
$string['managecoursepage_docs_help'] = 'Upload selectable-text PDF, Markdown or TXT. VTutor generates reviewable Markdown and builds local vectors with DeepSeek semantic analysis.';
$string['managecoursepage_filemanager'] = 'Course documents for VTutor';
$string['managecoursepage_prompt_header'] = 'Course-specific prompt or directive';
$string['managecoursepage_prompt'] = 'Additional course prompt/directive';
$string['managecoursepage_prompt_help'] = 'Enter a course-specific directive to guide professional treatment of uploaded documents or how VTutor should answer in this course. Example: prioritise internal regulations, use checklist format, respond as a methodological adviser, or clearly separate technical criteria from operational recommendations.';
$string['managecoursepage_launcher_header'] = 'Floating-button customisation';
$string['managecoursepage_launcher_label'] = 'Floating-button visible text';
$string['managecoursepage_launcher_title'] = 'Floating-button tooltip text';
$string['managecoursepage_launcher_bg'] = 'Floating-button background';
$string['managecoursepage_launcher_fg'] = 'Floating-button text colour';
$string['managecoursepage_launcher_border'] = 'Floating-button border colour';
$string['managecoursepage_reindex_now'] = 'Process documents directly after saving';
$string['managecoursepage_saved'] = 'VTutor course changes were saved.';
$string['managecoursepage_saved_reindexed'] = 'Reindexing completed: {$a->sources} sources and {$a->chunks} chunks.';
$string['managecoursepage_saved_but_reindex_failed'] = 'Changes were saved, but reindexing could not be completed: {$a}';
$string['managecoursepage_internal_reindex_done'] = 'Internal reindexing completed from the control centre: {$a->sources} sources, {$a->chunks} chunks and {$a->errors} errors.';
$string['managecoursepage_internal_reindex_error'] = 'Internal reindexing could not be run from the control centre: {$a}';
$string['managecoursepage_file_save_error'] = 'Course documents could not be saved: {$a}';
$string['managecoursepage_currentfiles'] = 'Currently uploaded documents';
$string['managecoursepage_nofiles'] = 'No documents have yet been uploaded to VTutor\'s own document base for this course.';


$string['openglobalconfigbutton'] = '🛠 Global panel: AI, image and prompts';
$string['opensettingspage'] = 'Open VTutor Pro administration panel';
$string['purgecacheslabel'] = 'Purge caches';
$string['admintab_ai'] = 'AI and provider';
$string['admintab_visual'] = 'VTutor Pro image and identity';
$string['admintab_prompts'] = 'Prompts and directives';
$string['global_master_prompt'] = 'Institutional master prompt';
$string['global_master_prompt_desc'] = 'Cross-installation directive that VTutor applies throughout the installation before course-specific directives.';
$string['global_document_prompt'] = 'Global document-processing prompt';
$string['global_document_prompt_desc'] = 'Global directive for interpreting, summarising, comparing and prioritising indexed documents in VTutor.';
$string['managecoursepage_document_prompt'] = 'Course document prompt/directive';
$string['managecoursepage_document_prompt_help'] = 'Enter a specific directive for how course documents should be handled. Example: prioritise internal regulations, extract step-by-step procedures, compare protocols, or answer in executive-report format.';
$string['managecoursepage_global_header'] = 'Global configuration visible on this page';
$string['managecoursepage_global_help'] = 'As an administrator, you can also adjust the AI provider, VTutor Pro institutional image and global prompts here without leaving course management.';
$string['managecoursepage_admin_note'] = 'When signed in as an administrator, this control centre also provides quick access to and editing of the AI provider, institutional cover and global prompts without leaving the page.';


$string['launchwindowlabel'] = 'Open VTutor Pro';
$string['launchsamepagelabel'] = 'Open in a separate tab';
$string['launchdescription'] = 'The VTutor Pro conversation opens outside the course block in a separate resizable window. Document management, prompts and visual configuration remain in the control centre.';
$string['controlcenterlabel'] = '⚙️ VTutor Pro control centre';
$string['openchatwindowbutton'] = 'Open VTutor Pro window';

// DeepSeek semantic mini-RAG with local vectors.
$string['rag_internal_settings'] = 'Institutional internal mini-RAG';
$string['rag_internal_settings_desc'] = 'Settings for the sparse vector index, lexical fallback, extraction and reranking. DeepSeek is the only external semantic provider.';
$string['internal_rag_enabled'] = 'Enable institutional mini-RAG engine';
$string['internal_rag_enabled_desc'] = 'Makes the institutional document workflow available. Each course remains disabled until an authorised person enables it in that course control centre.';
$string['rag_show_sources'] = 'Show verified document sources';
$string['rag_show_sources_desc'] = 'Appends sources built by the server from real document, page and heading metadata.';
$string['rag_relational_causality_guard'] = 'Causality guard for documentary relations';
$string['rag_relational_causality_guard_desc'] = 'Prevents a relational synthesis from introducing causality not supported by retrieved evidence; it does not trigger a second LLM call.';
$string['rag_semantic_candidates'] = 'Semantic candidates';
$string['rag_semantic_candidates_desc'] = 'Candidates evaluated before selecting the final top-k.';
$string['rag_semantic_weight'] = 'Semantic weight';
$string['rag_semantic_weight_desc'] = 'Cosine similarity weight from 0 to 1. Semantic evidence may improve ranking but never suppress a valid documentary lexical match.';
$string['rag_min_score'] = 'Minimum retrieval score';
$string['rag_min_score_desc'] = 'Minimum hybrid score from 0 to 1 required as sufficient evidence.';
$string['pdftotext_path'] = 'Optional pdftotext path';
$string['pdftotext_path_desc'] = 'Optional accelerator. If unavailable or unsuccessful, ALPHA3 uses the internal PHP extractor.';
$string['pdf_native_fallback'] = 'Enable internal PDF extractor';
$string['pdf_native_fallback_desc'] = 'Extracts the PDF text layer without executing server programs. It does not perform OCR.';
$string['rag_max_pdf_pages'] = 'Maximum PDF pages';
$string['rag_max_pdf_pages_desc'] = 'Initial institutional page limit per PDF.';
$string['rag_max_documents'] = 'Maximum documents per course';
$string['rag_max_documents_desc'] = 'Maximum files in the managed document base.';
$string['rag_max_file_bytes'] = 'Maximum file size in bytes';
$string['rag_max_file_bytes_desc'] = 'Size limit applied by Moodle File API and the document processor.';
$string['rag_chunk_target'] = 'Target chunk size';
$string['rag_chunk_target_desc'] = 'Target characters after splitting by headings, pages and paragraphs.';
$string['rag_chunk_max'] = 'Maximum chunk size';
$string['rag_chunk_max_desc'] = 'Absolute character limit for a chunk.';
$string['rag_chunk_overlap'] = 'Chunk overlap';
$string['rag_chunk_overlap_desc'] = 'Repeated context characters only when a long unit must be split.';
$string['rag_chunk_min'] = 'Minimum useful chunk size';
$string['rag_chunk_min_desc'] = 'Smaller final fragments are merged when safe.';
$string['rag_batch_retention_days'] = 'Inactive batch retention';
$string['rag_batch_retention_days_desc'] = 'Days to keep inactive batches before scheduled cleanup. The active batch is never deleted.';
$string['rag_cleanup_task'] = 'Clean inactive VTutor Pro mini-RAG batches';
$string['rag_course_enabled'] = 'Enable document mini-RAG in this course';
$string['rag_course_enabled_help'] = 'Isolates documents, chunks, vectors, caches and queries by the current courseid. Enable only in authorised institutional courses.';
$string['rag_internal_global_disabled'] = 'The institutional mini-RAG engine is disabled globally. An administrator must enable it in the block settings.';
$string['rag_course_disabled_reindex'] = 'Institutional reindexing did not run: first enable mini-RAG only for this course in the control centre. The legacy index was not modified.';
$string['rag_reindex_no_documents'] = 'There are no documents in this course document base. Upload at least one file and save before reindexing.';
$string['rag_reindex_readiness_global_disabled'] = 'Processing is blocked: the institutional mini-RAG engine is disabled globally. An administrator must enable it before authorising courses.';
$string['rag_reindex_readiness_course_and_documents_missing'] = 'Setup required: enable “Document mini-RAG in this course”, upload at least one PDF, TXT or Markdown file, then save once with “Process documents directly after saving” selected. The legacy index was not modified.';
$string['rag_reindex_readiness_course_disabled'] = 'Setup required: enable “Document mini-RAG in this course” and save with direct processing selected. Existing files and the legacy index remain unchanged.';
$string['rag_reindex_readiness_documents_missing'] = 'Setup required: course mini-RAG is enabled, but there are no files. Upload at least one PDF, TXT or Markdown file and save with direct processing selected.';
$string['rag_reindex_readiness_ready'] = 'Ready to process this course institutional documents without modifying the legacy index.';
$string['rag_semantic_migration_prerequisites'] = 'Available after mini-RAG is enabled and at least one document has been uploaded, extracted, reviewed and approved.';
$string['rag_embedding_not_ready_diag'] = 'Embeddings are not configured; local lexical retrieval will be used.';
$string['rag_course_scope'] = 'RAG sources';
$string['rag_scope_managed'] = 'Managed document base only';
$string['rag_scope_managed_moodle'] = 'Managed documents and Moodle resources (legacy compatibility)';
$string['rag_course_mode'] = 'Document response mode';
$string['rag_mode_strict'] = 'Strict: answer only from retrieved evidence';
$string['rag_mode_mixed'] = 'Mixed: allow clearly identified general knowledge';
$string['rag_review_workflow_help'] = 'Safe cron-free flow: save file → direct extraction → reviewable Markdown → human approval → DeepSeek semantic signature → local sparse vector → atomic activation.';
$string['rag_strict_no_evidence'] = 'I could not find sufficient information in the indexed documents for this course.';
$string['rag_sources_heading'] = 'Sources consulted:';
$string['rag_document_task'] = 'Process a VTutor Pro institutional document';
$string['rag_pdftotext_unavailable'] = 'The pdftotext PDF extractor is unavailable or its path is not executable.';
$string['rag_process_execution_disabled'] = 'The server does not allow the configured PDF extractor to run.';
$string['rag_pdftotext_failed'] = 'PDF extraction could not start.';
$string['rag_pdftotext_failed_detail'] = 'PDF extraction failed: {$a}';
$string['rag_pdf_too_many_pages'] = 'The PDF exceeds the {$a}-page limit.';
$string['rag_pdf_encrypted'] = 'The PDF is encrypted or protected and cannot be processed. Upload a password-free copy.';
$string['rag_pending_ocr_detail'] = 'The PDF contains insufficient readable text and requires OCR before indexing.';
$string['rag_pdf_text_encoding_unsupported'] = 'The PDF contains text drawing operations, but its font encoding cannot yet be interpreted safely. It will not be classified as scanned; inspect the original file.';
$string['rag_pdf_structure_unsupported'] = 'The internal PDF structure contains no text or images identifiable by the safe extractor. Inspect or re-export the file.';
$string['rag_extraction_too_short'] = 'Extraction produced too little text and will not be indexed.';
$string['rag_markdown_missing'] = 'No reviewable Markdown exists for this document.';
$string['rag_no_useful_chunks'] = 'The document produced no useful chunks.';
$string['rag_embedding_config_incomplete'] = 'The HTTPS embedding provider configuration is incomplete.';
$string['rag_embedding_transport_error'] = 'Embedding provider transport error ({$a}).';
$string['rag_embedding_http_error'] = 'The embedding provider returned HTTP {$a}.';
$string['rag_embedding_invalid_response'] = 'The embedding provider returned an invalid response.';
$string['rag_embedding_invalid_vector'] = 'The provider returned an empty or non-numeric vector.';
$string['rag_embedding_dimension_mismatch'] = 'Vectors in the batch have different dimensions.';
$string['rag_embedding_expected_dimension_mismatch'] = 'The returned dimension does not match the configured {$a} dimensions.';
$string['rag_embedding_count_mismatch'] = 'The number of vectors does not match the number of submitted texts.';
$string['rag_embedding_optional_diag'] = 'Embeddings are not configured. Mini-RAG remains operational in local lexical mode; embeddings can be added later to enable hybrid semantic retrieval.';
$string['rag_deepseek_semantic_enabled'] = 'Enable DeepSeek semantic vectorisation';
$string['rag_deepseek_semantic_enabled_desc'] = 'Uses the tutor’s existing DeepSeek API key and model to create JSON signatures that Moodle converts into local sparse vectors. No second provider or /embeddings endpoint is used.';
$string['rag_semantic_timeout'] = 'Semantic analysis timeout';
$string['rag_semantic_timeout_desc'] = 'Maximum seconds for each DeepSeek JSON vectorisation or reranking request (10–180).';
$string['rag_semantic_batch_size'] = 'Chunks per semantic batch';
$string['rag_semantic_batch_size_desc'] = 'Number of fragments analysed in one DeepSeek request (1–8). One maximises JSON reliability; RC4 automatically falls back to individual requests when a batch fails.';
$string['rag_semantic_recovery_note'] = 'RC4 distinguishes empty, truncated, invalid and incomplete responses. A recoverable failure gets bounded retries and batch-to-single-item fallback before index activation.';
$string['rag_semantic_configured_not_probed'] = 'Configured; this page does not run or bill a probe request. Real operation is confirmed by active chunks and vectors.';
$string['rag_deepseek_rerank_enabled'] = 'Enable conditional DeepSeek reranking';
$string['rag_deepseek_rerank_enabled_desc'] = 'Adds one request only when candidate scores are close or confidence is low.';
$string['rag_rerank_ambiguity_delta'] = 'Ambiguous-result difference';
$string['rag_rerank_ambiguity_delta_desc'] = 'DeepSeek reranks when the two best results differ by no more than this value.';
$string['rag_rerank_confidence'] = 'Reranking confidence threshold';
$string['rag_rerank_confidence_desc'] = 'DeepSeek reranks when the best result is below this value.';
$string['rag_deepseek_semantic_not_configured'] = 'Semantic vectorisation requires DeepSeek as active provider, a configured API key and the official https://api.deepseek.com URL.';
$string['rag_deepseek_semantic_not_ready_diag'] = 'Lexical retrieval remains available, but semantic vectorisation is waiting for a valid DeepSeek configuration.';
$string['rag_deepseek_semantic_invalid_response'] = 'DeepSeek returned an invalid or incomplete semantic JSON signature.';
$string['rag_deepseek_semantic_empty_response'] = 'DeepSeek returned empty semantic content. VTutor retried safely without activating a partial index.';
$string['rag_deepseek_semantic_truncated_response'] = 'DeepSeek truncated the semantic signature at the output limit. VTutor retried safely without activating a partial index.';
$string['rag_deepseek_semantic_empty_vector'] = 'The semantic signature did not produce a usable local vector.';
$string['rag_deepseek_semantic_request_failed'] = 'The DeepSeek semantic request failed ({$a}). The previous active index remains intact.';
$string['rag_deepseek_rerank_invalid_response'] = 'DeepSeek returned an invalid candidate order; the local ranking is preserved.';
$string['rag_semantic_migration_no_documents'] = 'There are no approved indexed documents with Markdown available for migration.';
$string['rag_semantic_migration_done'] = 'Vector migration completed: {$a->documents} documents, {$a->chunks} chunks, {$a->reused} vectors reused and {$a->generated} generated. Index switching was atomic.';
$string['rag_semantic_migration_failed'] = 'Vector migration was not activated: {$a}. The previous index remains operational.';
$string['rag_action_migrate_semantic'] = 'Build DeepSeek vector index';
$string['cachedef_semantic_vectors'] = 'Semantic query and fragment vectors identified by content hash.';
$string['cachedef_semantic_rerank'] = 'Temporary DeepSeek document reranking results.';
$string['managecoursepage_saved_no_reindex_disabled'] = 'Reindexing did not run because mini-RAG remains disabled for this course; the legacy index was preserved unchanged.';
$string['rag_active_model_mismatch'] = 'The course already has an active index with a different model or dimension. Incompatible vectors will not be mixed.';
$string['rag_openxml_invalid'] = 'The OpenXML file is invalid or damaged.';
$string['rag_too_many_documents'] = 'The course exceeds the {$a}-document limit.';
$string['rag_unsupported_extension'] = 'The .{$a} format is not enabled for the mini-RAG.';
$string['rag_empty_file'] = 'The file is empty.';
$string['rag_file_too_large'] = 'The file exceeds the maximum size of {$a}.';
$string['rag_mime_mismatch'] = 'The real MIME type does not match the file extension.';
$string['rag_document_index_done'] = 'Document approved and indexed successfully inside Moodle.';
$string['rag_document_index_failed'] = 'The document was approved but could not be indexed: {$a}';
$string['rag_document_extract_done'] = 'Extraction completed. Review the Markdown before approving and indexing.';
$string['rag_document_extract_failed'] = 'Extraction could not be completed: {$a}';
$string['rag_processing_error_detail'] = 'Technical detail: {$a}';
$string['rag_document_not_reviewable'] = 'The document is not pending review.';
$string['rag_index_interrupted'] = 'The semantic indexing request was interrupted before completion. The reviewed Markdown was preserved; retry indexing.';
$string['rag_index_still_running'] = 'Semantic indexing is still within its active execution window. Wait a few minutes and refresh before attempting recovery.';
$string['rag_processing_phase'] = 'Technical phase: {$a}';
$string['rag_document_table_heading'] = 'Mini-RAG document lifecycle';
$string['rag_table_document'] = 'Document';
$string['rag_table_status'] = 'Status';
$string['rag_table_extractor'] = 'Extractor';
$string['rag_table_pages'] = 'Pages';
$string['rag_table_characters'] = 'Characters';
$string['rag_table_chunks'] = 'Chunks';
$string['rag_table_embeddings'] = 'Semantic vectors';
$string['rag_table_index_mode'] = 'Index mode';
$string['rag_table_actions'] = 'Actions';
$string['rag_index_mode_lexical'] = 'Local lexical';
$string['rag_index_mode_semantic'] = 'DeepSeek vector';
$string['rag_index_mode_pending'] = 'Pending';
$string['rag_action_markdown'] = 'View Markdown';
$string['rag_action_download_markdown'] = 'Download Markdown';
$string['rag_action_approve'] = 'Approve and index';
$string['rag_action_retry'] = 'Retry';
$string['rag_action_regenerate'] = 'Regenerate Markdown';
$string['rag_documentstatus_attention'] = 'Indexed documents remain available, but some files require review.';
$string['rag_status_uploaded'] = 'Uploaded';
$string['rag_status_processing'] = 'Processing';
$string['rag_status_extracted'] = 'Extracted';
$string['rag_status_pending_review'] = 'Pending review';
$string['rag_status_ready'] = 'Approved';
$string['rag_status_indexing'] = 'Indexing';
$string['rag_status_indexed'] = 'Indexed';
$string['rag_status_extraction_failed'] = 'Extraction failed';
$string['rag_status_pending_ocr'] = 'Pending OCR';
$string['rag_status_embedding_failed'] = 'Semantic analysis failed';
$string['rag_status_inactive'] = 'Inactive';
$string['errorupgradingschema'] = 'The safe VTutor Pro schema extension could not be completed: {$a}';

// VTutor Pro Institucional v10.3 - HITL y rutas pedagógicas.
$string['hitlgovernance'] = 'AI and Human-in-the-Loop governance';
$string['hitlgovernance_desc'] = 'Defines the mandatory institutional principle: VTutor Pro can guide, suggest pathways and generate alerts, but it does not make automatic final decisions.';
$string['hitl_enabled'] = 'Enable Human-in-the-Loop statement';
$string['hitl_enabled_desc'] = 'Displays and applies the Human-in-the-Loop principle in responses, pathways and plugin documentation.';
$string['routes_enabled'] = 'Enable pedagogical pathway engine';
$string['routes_enabled_desc'] = 'Allows VTutor Pro to suggest work pathways based on Tutoring, Reinforcement, Self-assessment and Resources.';
$string['routes_append_student_response'] = 'Append a suggested pathway to the student response';
$string['routes_append_student_response_desc'] = 'When enabled, VTutor Pro appends an advisory pedagogical pathway to responses addressed to the student.';

$string['routes_dedup_minutes'] = 'Minutes used to prevent duplicate pathways';
$string['routes_dedup_minutes_desc'] = 'Minimum time before the same suggested pathway can be logged again for the same student in the same course. Recommended: 30.';
$string['hitl_policy_text'] = 'Institutional Human-in-the-Loop policy';
$string['hitl_policy_text_desc'] = 'Official governance text applied to institutional VTutor Pro operation.';
$string['hitl_policy_text_default'] = 'VTutor Pro applies the Human-in-the-Loop principle: AI, ML, Deep Learning or ITS components may generate alerts, recommendations, analyses, predictions and suggested pathways, but they never make automatic final decisions about students or institutional processes. Every academic, pedagogical, administrative or institutional decision must be reviewed, validated and executed by an authorised person.';
$string['hitl_statement_short'] = 'Human-in-the-Loop principle: pathways generated by VTutor Pro are support recommendations. The final decision always belongs to an authorised person.';
$string['routespage'] = 'VTutor Pro pedagogical pathways';
$string['routespage_intro'] = 'This view lets you review pedagogical pathways suggested by VTutor Pro and record human validation by a teacher or institution.';
$string['routes_no_records'] = 'There are no suggested pathways for this course yet.';
$string['routes_action_saved'] = 'The human decision for this pathway was recorded.';
$string['routes_table_date'] = 'Date';
$string['routes_table_student'] = 'Student';
$string['routes_table_route'] = 'Suggested pathway';
$string['routes_table_status'] = 'Status';
$string['routes_table_action'] = 'Human validation';
$string['routes_action_validated'] = 'Validate';
$string['routes_action_adjusted'] = 'Adjust';
$string['routes_action_followup'] = 'Follow-up';
$string['routes_action_rejected'] = 'Reject';
$string['routes_controlcenter_link'] = '🧭 Suggested pedagogical pathways';
$string['privacy:metadata:block_ai_tutor_route_log'] = 'Minimal record of pedagogical routes suggested by VTutor Pro for support and human validation.';
$string['privacy:metadata:block_ai_tutor_route_log:courseid'] = 'The ID of the course where the route was suggested.';
$string['privacy:metadata:block_ai_tutor_route_log:userid'] = 'The ID of the user for whom the route was suggested.';
$string['privacy:metadata:block_ai_tutor_route_log:routekey'] = 'The technical type of the suggested pedagogical route.';
$string['privacy:metadata:block_ai_tutor_route_log:routename'] = 'The visible name of the suggested pedagogical route.';
$string['privacy:metadata:block_ai_tutor_route_log:status'] = 'The status of the suggested route.';
$string['privacy:metadata:block_ai_tutor_route_log:teachervalidated'] = 'Whether the route was validated by an authorised person.';
$string['privacy:metadata:block_ai_tutor_route_log:human_decision'] = 'The human decision recorded for the suggested route.';
$string['privacy:metadata:block_ai_tutor_route_log:timecreated'] = 'The date when the suggested route was created.';
$string['coursemap_title'] = 'Course pedagogical map';
$string['coursemap_rebuild_button'] = 'Rebuild course pedagogical map';
$string['coursemap_rebuilt'] = 'Pedagogical map updated. Items recorded: {$a}.';
$string['coursemap_section'] = 'Section';
$string['coursemap_type'] = 'Pedagogical type';
$string['coursemap_activity'] = 'Resource or activity';
$string['coursemap_module'] = 'Moodle module';
$string['coursemap_type_tutorial'] = 'Tutoring';
$string['coursemap_type_reinforcement'] = 'Reinforcement';
$string['coursemap_type_selfassessment'] = 'Self-assessment';
$string['coursemap_type_resource'] = 'Resource';
$string['coursemap_type_evidence'] = 'Evidence';
$string['routes_suggestions_title'] = 'Suggested pathways for teacher validation';

$string['diagnosticobutton'] = 'VTutor diagnostics';
$string['diagnosticopage'] = 'VTutor Pro V-Zona diagnostics';
$string['diagnosticohelp'] = 'This page checks tables, AI configuration, uploaded files, RAG chunks and course reindexing status. Use it to isolate document upload, incomplete upgrade or tutor AJAX errors.';
$string['diagnosticosectionconfig'] = 'Active configuration';
$string['diagnosticosectionschema'] = 'Database schema';
$string['diagnosticosectioncourse'] = 'Course status';
$string['diagnosticosectionfiles'] = 'Files in document base';
$string['runtime_identity_version'] = 'I am VTutor Pro V-Zona. Installed release: {$a->release}. Build: {$a->build}.';
$string['runtime_identity_model'] = 'The active LLM provider for this conversation is {$a->provider}, and the effective model sent to its API is {$a->model}.';
$string['runtime_identity_profile'] = 'Your effective profile, determined by Moodle permissions, is: {$a}.';
$string['runtime_identity_secret_protected'] = 'Credentials, API keys, tokens, and secrets are protected and are not displayed in chat.';
$string['runtime_identity_role_locked'] = 'A written instruction does not change the profile or grant permissions; I will retain the effective profile assigned by Moodle.';
$string['runtime_profile_admin'] = 'Administrator';
$string['runtime_profile_teacher'] = 'Teacher';
$string['runtime_profile_student'] = 'Student';
$string['rag_relational_fidelity_notice'] = 'Documentary fidelity note: the retrieved sources allow these concepts to be related; to keep the wording conservative, the response links them without attributing direct causality.';
$string['rag_relational_fidelity_fallback'] = 'The retrieved sources allow the requested concepts to be treated as related elements of the topic, but the generated response tried to organize them as a cause-and-effect chain that cannot be supported safely. To preserve documentary fidelity, I will not present that synthesis. Review the appended sources or ask a separate question about each concept.';

// Privacy metadata for external AI processing and HITL validation.
$string['privacy:metadata:block_ai_tutor_route_log:triggerreason'] = 'The summarised reason that triggered the pedagogical route suggestion.';
$string['privacy:metadata:block_ai_tutor_route_log:validatedby'] = 'The ID of the authorised person who recorded the human validation.';
$string['privacy:metadata:block_ai_tutor_route_log:timevalidated'] = 'The date and time of the human validation.';
$string['privacy:metadata:block_ai_tutor_route_log:timemodified'] = 'The date and time when the record was last modified.';
$string['privacy:metadata:ai_provider'] = 'VTutor may send conversation and contextual data to an external AI provider configured by the institution in order to generate a response.';
$string['privacy:metadata:ai_provider:message'] = 'The current message sent by the user.';
$string['privacy:metadata:ai_provider:conversationhistory'] = 'Recent conversation history included according to institutional configuration.';
$string['privacy:metadata:ai_provider:coursecontext'] = 'Course context required to formulate the response.';
$string['privacy:metadata:ai_provider:retrievedcontext'] = 'Document fragments retrieved by Mini-RAG when enabled.';
$string['privacy:metadata:ai_provider:userprofile'] = 'The effective Moodle profile used to adapt the interaction while preserving permissions.';

$string['errorschemamissing'] = 'Required table {$a} is missing. Complete the Moodle upgrade from Site administration → Notifications before using VTutor.';

$string['ai_tutor:manage'] = 'Manage VTutor settings and documents';
$string['ai_tutor:manageroutes'] = 'Manage and validate VTutor Human-in-the-Loop routes';

$string['history_retention_days'] = 'Chat history retention';
$string['history_retention_days_desc'] = 'Days VTutor keeps stored chat history before scheduled cleanup. Set this value according to the institutional privacy policy.';

// Conversational routing: zero-cost social turns must never be blocked by strict RAG.
$string['conversation_greeting'] = 'Hello. How can I help you with this course?';
$string['conversation_thanks'] = 'You are welcome. I can help you review a topic, activity, or course document.';
$string['conversation_ack'] = 'Great. Which topic, activity, or document would you like to review?';
$string['conversation_farewell'] = 'See you later. When you want to continue with the course, I will be here to help.';
