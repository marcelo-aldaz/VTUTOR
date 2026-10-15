<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// blocks/ai_tutor/settings.php
defined('MOODLE_INTERNAL') || die();

// Moodle's block plugininfo creates and capability-protects this settings page.
// Restricting the whole file with $hassiteconfig can leave the section absent
// from the admin tree while it is being built, which causes sectionerror.
if ($ADMIN->fulltree) {
    // Release contract for v10.6.5 FINAL: DeepSeek is the only validated provider.
    $providers = [
        'deepseek' => get_string('provider_deepseek', 'block_ai_tutor'),
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

    $settings->add(new admin_setting_configselect('block_ai_tutor/model',
        get_string('model', 'block_ai_tutor'),
        get_string('model_desc', 'block_ai_tutor'),
        'deepseek-v4-flash', [
            'deepseek-v4-flash' => 'deepseek-v4-flash',
            'deepseek-v4-pro' => 'deepseek-v4-pro',
        ]));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/guideonly',
        get_string('guideonly', 'block_ai_tutor'),
        get_string('guideonly_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/temperature',
        get_string('temperature', 'block_ai_tutor'),
        get_string('temperature_desc', 'block_ai_tutor'),
        '0.15', PARAM_FLOAT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/max_tokens',
        get_string('maxtokens', 'block_ai_tutor'),
        get_string('maxtokens_desc', 'block_ai_tutor'),
        '1200', PARAM_INT));

    $settings->add(new admin_setting_heading('block_ai_tutor/securityfilter',
        get_string('securityfilter', 'block_ai_tutor'),
        get_string('securityfilter_desc', 'block_ai_tutor')));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/security_filter_enabled',
        get_string('security_filter_enabled', 'block_ai_tutor'),
        get_string('security_filter_enabled_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/security_max_input_chars',
        get_string('security_max_input_chars', 'block_ai_tutor'),
        get_string('security_max_input_chars_desc', 'block_ai_tutor'), '1500', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/security_max_output_chars',
        get_string('security_max_output_chars', 'block_ai_tutor'),
        get_string('security_max_output_chars_desc', 'block_ai_tutor'), '8000', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/security_history_max_limit',
        get_string('security_history_max_limit', 'block_ai_tutor'),
        get_string('security_history_max_limit_desc', 'block_ai_tutor'), '20', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/history_retention_days',
        get_string('history_retention_days', 'block_ai_tutor'),
        get_string('history_retention_days_desc', 'block_ai_tutor'), '365', PARAM_INT));

    $settings->add(new admin_setting_configselect('block_ai_tutor/security_store_mode',
        get_string('security_store_mode', 'block_ai_tutor'),
        get_string('security_store_mode_desc', 'block_ai_tutor'), 'full', [
            'full' => get_string('security_store_mode_full', 'block_ai_tutor'),
            'truncate' => get_string('security_store_mode_truncate', 'block_ai_tutor'),
            'metadata' => get_string('security_store_mode_metadata', 'block_ai_tutor'),
            'none' => get_string('security_store_mode_none', 'block_ai_tutor'),
        ]));

    $settings->add(new admin_setting_configtext('block_ai_tutor/security_store_truncate_chars',
        get_string('security_store_truncate_chars', 'block_ai_tutor'),
        get_string('security_store_truncate_chars_desc', 'block_ai_tutor'), '4000', PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/security_mask_sensitive',
        get_string('security_mask_sensitive', 'block_ai_tutor'),
        get_string('security_mask_sensitive_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/security_block_patterns',
        get_string('security_block_patterns', 'block_ai_tutor'),
        get_string('security_block_patterns_desc', 'block_ai_tutor'), '', PARAM_RAW_TRIMMED, 5, 60));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_topk',
        get_string('rag_topk', 'block_ai_tutor'),
        get_string('rag_topk_desc', 'block_ai_tutor'),
        '5', PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/rag_show_sources',
        get_string('rag_show_sources', 'block_ai_tutor'),
        get_string('rag_show_sources_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/rag_relational_causality_guard',
        get_string('rag_relational_causality_guard', 'block_ai_tutor'),
        get_string('rag_relational_causality_guard_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_semantic_candidates',
        get_string('rag_semantic_candidates', 'block_ai_tutor'),
        get_string('rag_semantic_candidates_desc', 'block_ai_tutor'), '12', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_semantic_weight',
        get_string('rag_semantic_weight', 'block_ai_tutor'),
        get_string('rag_semantic_weight_desc', 'block_ai_tutor'), '0.75', PARAM_FLOAT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_min_score',
        get_string('rag_min_score', 'block_ai_tutor'),
        get_string('rag_min_score_desc', 'block_ai_tutor'), '0.12', PARAM_FLOAT));

    $settings->add(new admin_setting_heading('block_ai_tutor/internalrag',
        get_string('rag_internal_settings', 'block_ai_tutor'),
        get_string('rag_internal_settings_desc', 'block_ai_tutor')));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/internal_rag_enabled',
        get_string('internal_rag_enabled', 'block_ai_tutor'),
        get_string('internal_rag_enabled_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/rag_deepseek_semantic_enabled',
        get_string('rag_deepseek_semantic_enabled', 'block_ai_tutor'),
        get_string('rag_deepseek_semantic_enabled_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_semantic_timeout',
        get_string('rag_semantic_timeout', 'block_ai_tutor'),
        get_string('rag_semantic_timeout_desc', 'block_ai_tutor'), '60', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_semantic_batch_size',
        get_string('rag_semantic_batch_size', 'block_ai_tutor'),
        get_string('rag_semantic_batch_size_desc', 'block_ai_tutor'), '4', PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/rag_deepseek_rerank_enabled',
        get_string('rag_deepseek_rerank_enabled', 'block_ai_tutor'),
        get_string('rag_deepseek_rerank_enabled_desc', 'block_ai_tutor'), 0));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_rerank_ambiguity_delta',
        get_string('rag_rerank_ambiguity_delta', 'block_ai_tutor'),
        get_string('rag_rerank_ambiguity_delta_desc', 'block_ai_tutor'), '0.08', PARAM_FLOAT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_rerank_confidence',
        get_string('rag_rerank_confidence', 'block_ai_tutor'),
        get_string('rag_rerank_confidence_desc', 'block_ai_tutor'), '0.42', PARAM_FLOAT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/pdftotext_path',
        get_string('pdftotext_path', 'block_ai_tutor'),
        get_string('pdftotext_path_desc', 'block_ai_tutor'), '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/pdf_native_fallback',
        get_string('pdf_native_fallback', 'block_ai_tutor'),
        get_string('pdf_native_fallback_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_max_pdf_pages',
        get_string('rag_max_pdf_pages', 'block_ai_tutor'),
        get_string('rag_max_pdf_pages_desc', 'block_ai_tutor'), '40', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_max_documents',
        get_string('rag_max_documents', 'block_ai_tutor'),
        get_string('rag_max_documents_desc', 'block_ai_tutor'), '20', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_max_file_bytes',
        get_string('rag_max_file_bytes', 'block_ai_tutor'),
        get_string('rag_max_file_bytes_desc', 'block_ai_tutor'), '26214400', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_chunk_target',
        get_string('rag_chunk_target', 'block_ai_tutor'),
        get_string('rag_chunk_target_desc', 'block_ai_tutor'), '2400', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_chunk_max',
        get_string('rag_chunk_max', 'block_ai_tutor'),
        get_string('rag_chunk_max_desc', 'block_ai_tutor'), '3600', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_chunk_overlap',
        get_string('rag_chunk_overlap', 'block_ai_tutor'),
        get_string('rag_chunk_overlap_desc', 'block_ai_tutor'), '350', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_chunk_min',
        get_string('rag_chunk_min', 'block_ai_tutor'),
        get_string('rag_chunk_min_desc', 'block_ai_tutor'), '400', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/rag_batch_retention_days',
        get_string('rag_batch_retention_days', 'block_ai_tutor'),
        get_string('rag_batch_retention_days_desc', 'block_ai_tutor'), '14', PARAM_INT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/target_population',
        get_string('target_population', 'block_ai_tutor'),
        get_string('target_population_desc', 'block_ai_tutor'),
        'profesionales docentes y participantes de formación continua', PARAM_TEXT));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/short_answers',
        get_string('short_answers', 'block_ai_tutor'),
        get_string('short_answers_desc', 'block_ai_tutor'), 0));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/always_examples',
        get_string('always_examples', 'block_ai_tutor'),
        get_string('always_examples_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_heading('block_ai_tutor/studentresponsepolicy',
        get_string('studentresponsepolicy', 'block_ai_tutor'),
        get_string('studentresponsepolicy_desc', 'block_ai_tutor')));
    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/student_word_policy_enabled',
        get_string('student_word_policy_enabled', 'block_ai_tutor'),
        get_string('student_word_policy_enabled_desc', 'block_ai_tutor'), 1));
    $settings->add(new admin_setting_configtext('block_ai_tutor/student_word_min',
        get_string('student_word_min', 'block_ai_tutor'),
        get_string('student_word_min_desc', 'block_ai_tutor'), '150', PARAM_INT));
    $settings->add(new admin_setting_configtext('block_ai_tutor/student_word_max',
        get_string('student_word_max', 'block_ai_tutor'),
        get_string('student_word_max_desc', 'block_ai_tutor'), '200', PARAM_INT));

    $settings->add(new admin_setting_heading('block_ai_tutor/teacherresponsepolicy',
        get_string('teacherresponsepolicy', 'block_ai_tutor'),
        get_string('teacherresponsepolicy_desc', 'block_ai_tutor')));
    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/teacher_word_policy_enabled',
        get_string('teacher_word_policy_enabled', 'block_ai_tutor'),
        get_string('teacher_word_policy_enabled_desc', 'block_ai_tutor'), 1));
    $settings->add(new admin_setting_configtext('block_ai_tutor/teacher_word_min',
        get_string('teacher_word_min', 'block_ai_tutor'),
        get_string('teacher_word_min_desc', 'block_ai_tutor'), '200', PARAM_INT));
    $settings->add(new admin_setting_configtext('block_ai_tutor/teacher_word_max',
        get_string('teacher_word_max', 'block_ai_tutor'),
        get_string('teacher_word_max_desc', 'block_ai_tutor'), '400', PARAM_INT));

    $settings->add(new admin_setting_heading('block_ai_tutor/adminresponsepolicy',
        get_string('adminresponsepolicy', 'block_ai_tutor'),
        get_string('adminresponsepolicy_desc', 'block_ai_tutor')));
    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/admin_word_policy_enabled',
        get_string('admin_word_policy_enabled', 'block_ai_tutor'),
        get_string('admin_word_policy_enabled_desc', 'block_ai_tutor'), 1));
    $settings->add(new admin_setting_configtext('block_ai_tutor/admin_word_min',
        get_string('admin_word_min', 'block_ai_tutor'),
        get_string('admin_word_min_desc', 'block_ai_tutor'), '400', PARAM_INT));
    $settings->add(new admin_setting_configtext('block_ai_tutor/admin_word_max',
        get_string('admin_word_max', 'block_ai_tutor'),
        get_string('admin_word_max_desc', 'block_ai_tutor'), '600', PARAM_INT));

    $settings->add(new admin_setting_heading('block_ai_tutor/professionalvariant',
        get_string('professionalvariant', 'block_ai_tutor'),
        get_string('professionalvariant_desc', 'block_ai_tutor')));

    $settings->add(new admin_setting_configselect('block_ai_tutor/site_variant',
        get_string('site_variant', 'block_ai_tutor'),
        get_string('site_variant_desc', 'block_ai_tutor'),
        'professional', [
            'standard' => get_string('site_variant_standard', 'block_ai_tutor'),
            'professional' => get_string('site_variant_professional', 'block_ai_tutor'),
        ]));

    $settings->add(new admin_setting_configtext('block_ai_tutor/professional_sector',
        get_string('professional_sector', 'block_ai_tutor'),
        get_string('professional_sector_desc', 'block_ai_tutor'),
        'docencia, capacitación profesional y profesiones liberales', PARAM_TEXT));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/professional_response_focus',
        get_string('professional_response_focus', 'block_ai_tutor'),
        get_string('professional_response_focus_desc', 'block_ai_tutor'),
        'Priorizar rigor técnico, aplicabilidad, análisis de casos, criterio profesional, ética, procedimientos, cumplimiento normativo y toma de decisiones basada en evidencia.', PARAM_RAW_TRIMMED, 5, 60));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/professional_source_priority',
        get_string('professional_source_priority', 'block_ai_tutor'),
        get_string('professional_source_priority_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/professional_natural_sources',
        get_string('professional_natural_sources', 'block_ai_tutor'),
        get_string('professional_natural_sources_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_heading('block_ai_tutor/institutionalvariant',
        get_string('institutionalvariant', 'block_ai_tutor'),
        get_string('institutionalvariant_desc', 'block_ai_tutor')));

    $settings->add(new admin_setting_configtext('block_ai_tutor/institution_name',
        get_string('institution_name', 'block_ai_tutor'),
        get_string('institution_name_desc', 'block_ai_tutor'),
        'Centro de Capacitación V-Zona', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/institution_subtitle',
        get_string('institution_subtitle', 'block_ai_tutor'),
        get_string('institution_subtitle_desc', 'block_ai_tutor'),
        'Formación profesional, docente y de especialización', PARAM_TEXT));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/institution_response_directive',
        get_string('institution_response_directive', 'block_ai_tutor'),
        get_string('institution_response_directive_desc', 'block_ai_tutor'),
        'Responder con tono ejecutivo-pedagógico, precisión conceptual, orientación aplicable al trabajo, sustento documental cuando exista y respeto por la ética profesional.', PARAM_RAW_TRIMMED, 5, 60));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/institution_document_guidance',
        get_string('institution_document_guidance', 'block_ai_tutor'),
        get_string('institution_document_guidance_desc', 'block_ai_tutor'),
        'Cuando existan documentos especializados del curso, priorizar reglamentos, protocolos, manuales, instructivos, guías metodológicas, formatos institucionales y material técnico oficialmente cargado en Moodle.', PARAM_RAW_TRIMMED, 5, 60));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/global_master_prompt',
        get_string('global_master_prompt', 'block_ai_tutor'),
        get_string('global_master_prompt_desc', 'block_ai_tutor'),
        '', PARAM_RAW_TRIMMED, 5, 60));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/global_document_prompt',
        get_string('global_document_prompt', 'block_ai_tutor'),
        get_string('global_document_prompt_desc', 'block_ai_tutor'),
        '', PARAM_RAW_TRIMMED, 5, 60));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/institution_footer_note',
        get_string('institution_footer_note', 'block_ai_tutor'),
        get_string('institution_footer_note_desc', 'block_ai_tutor'),
        'VTutor Pro V-Zona orienta, estructura y fundamenta respuestas con apoyo documental; la validación profesional final corresponde al participante, tutor o coordinador.', PARAM_RAW_TRIMMED, 4, 60));

    $settings->add(new admin_setting_heading('block_ai_tutor/hitlgovernance',
        get_string('hitlgovernance', 'block_ai_tutor'),
        get_string('hitlgovernance_desc', 'block_ai_tutor')));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/hitl_enabled',
        get_string('hitl_enabled', 'block_ai_tutor'),
        get_string('hitl_enabled_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/routes_enabled',
        get_string('routes_enabled', 'block_ai_tutor'),
        get_string('routes_enabled_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configcheckbox('block_ai_tutor/routes_append_student_response',
        get_string('routes_append_student_response', 'block_ai_tutor'),
        get_string('routes_append_student_response_desc', 'block_ai_tutor'), 1));

    $settings->add(new admin_setting_configtext('block_ai_tutor/routes_dedup_minutes',
        get_string('routes_dedup_minutes', 'block_ai_tutor'),
        get_string('routes_dedup_minutes_desc', 'block_ai_tutor'), '30', PARAM_INT));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/hitl_policy_text',
        get_string('hitl_policy_text', 'block_ai_tutor'),
        get_string('hitl_policy_text_desc', 'block_ai_tutor'),
        get_string('hitl_policy_text_default', 'block_ai_tutor'), PARAM_RAW_TRIMMED, 6, 80));

    $settings->add(new admin_setting_configtext('block_ai_tutor/institution_cover_image_url',
        get_string('institution_cover_image_url', 'block_ai_tutor'),
        get_string('institution_cover_image_url_desc', 'block_ai_tutor'),
        '', PARAM_URL));

    $settings->add(new admin_setting_configtext('block_ai_tutor/institution_logo_image_url',
        get_string('institution_logo_image_url', 'block_ai_tutor'),
        get_string('institution_logo_image_url_desc', 'block_ai_tutor'),
        '', PARAM_URL));

    $settings->add(new admin_setting_configtext('block_ai_tutor/institution_cover_kicker',
        get_string('institution_cover_kicker', 'block_ai_tutor'),
        get_string('institution_cover_kicker_desc', 'block_ai_tutor'),
        'VTutor Pro', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('block_ai_tutor/institution_cover_title',
        get_string('institution_cover_title', 'block_ai_tutor'),
        get_string('institution_cover_title_desc', 'block_ai_tutor'),
        'Asistente profesional con base documental', PARAM_TEXT));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/institution_cover_message',
        get_string('institution_cover_message', 'block_ai_tutor'),
        get_string('institution_cover_message_desc', 'block_ai_tutor'),
        'Consulte, analice y trabaje con apoyo documental del curso en una ventana independiente.', PARAM_RAW_TRIMMED, 4, 60));

    $settings->add(new admin_setting_configtextarea('block_ai_tutor/institution_cover_cta',
        get_string('institution_cover_cta', 'block_ai_tutor'),
        get_string('institution_cover_cta_desc', 'block_ai_tutor'),
        'Use el centro de control para administrar IA, documentos, prompts, imagen institucional y botón flotante.', PARAM_RAW_TRIMMED, 4, 60));
}
