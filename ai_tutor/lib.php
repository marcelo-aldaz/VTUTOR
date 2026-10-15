<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Serve VTutor managed course documents.
 *
 * @param stdClass $course
 * @param stdClass|null $birecord
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function block_ai_tutor_pluginfile($course, $birecord, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_COURSE) {
        return false;
    }

    if (!in_array($filearea, ['course_document_base', 'document_markdown'], true)) {
        return false;
    }

    require_login($course);

    $canmanage = has_capability('block/ai_tutor:manage', $context)
        || has_capability('moodle/site:config', \context_system::instance());
    if ($filearea === 'document_markdown' && !$canmanage) {
        return false;
    }
    if ($filearea === 'course_document_base'
        && !has_capability('block/ai_tutor:use', $context)
        && !$canmanage) {
        return false;
    }

    if (count($args) < 2) {
        return false;
    }

    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = '/' . implode('/', $args) . '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_ai_tutor', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Return the short display name used in VTutor.
 *
 * @param stdClass $user
 * @return string
 */
function block_ai_tutor_get_firstname(stdClass $user): string {
    $fullusername = trim(fullname($user));
    $parts = preg_split('/\s+/u', $fullusername, -1, PREG_SPLIT_NO_EMPTY);
    $firstname = trim($parts[0] ?? '');
    return $firstname !== '' ? $firstname : 'Usuario';
}

/**
 * Branding/configuration values shared by block and standalone page.
 *
 * @param renderer_base $output
 * @param int $courseid
 * @return array
 */
function block_ai_tutor_get_branding_data($output, int $courseid): array {
    $institutionname = trim((string)(get_config('block_ai_tutor', 'institution_name') ?: 'Centro de Capacitación V-Zona'));
    $institutionsubtitle = trim((string)(get_config('block_ai_tutor', 'institution_subtitle') ?: 'Formación profesional, docente y de especialización'));
    $institutionfooter = trim((string)(get_config('block_ai_tutor', 'institution_footer_note') ?: 'VTutor Pro V-Zona orienta, estructura y fundamenta respuestas con apoyo documental; la validación profesional final corresponde al participante, tutor o coordinador.'));
    $coverkicker = trim((string)(get_config('block_ai_tutor', 'institution_cover_kicker') ?: 'Plataforma profesional de acompañamiento inteligente'));
    $covertitle = trim((string)(get_config('block_ai_tutor', 'institution_cover_title') ?: 'VTutor Pro V-Zona'));
    $covermessage = trim((string)(get_config('block_ai_tutor', 'institution_cover_message') ?: 'Asistente institucional con base documental para docencia, formación continua y cursos de especialización.'));
    $covercta = trim((string)(get_config('block_ai_tutor', 'institution_cover_cta') ?: 'Consulte contenidos, protocolos, manuales y recursos técnicos del curso con una experiencia visual consistente en laptop, PC, tablet y celular.'));

    $coverimage = \block_ai_tutor\local\input_guard::https_url(
        (string)(get_config('block_ai_tutor', 'institution_cover_image_url') ?: '')
    );
    if ($coverimage === '') {
        $coverimage = $output->image_url('vzona_cover', 'block_ai_tutor')->out(false);
    }
    $logoimage = \block_ai_tutor\local\input_guard::https_url(
        (string)(get_config('block_ai_tutor', 'institution_logo_image_url') ?: '')
    );
    if ($logoimage === '') {
        $logoimage = $output->image_url('vzona_mark', 'block_ai_tutor')->out(false);
    }

    $launcherlabel = trim((string)(get_config('block_ai_tutor', 'course_launcher_label_' . $courseid) ?: 'VTutor Pro'));
    $launchertitle = trim((string)(get_config('block_ai_tutor', 'course_launcher_title_' . $courseid) ?: 'Abrir VTutor Pro'));
    $launcherbg = \block_ai_tutor\local\input_guard::css_color(
        (string)(get_config('block_ai_tutor', 'course_launcher_bg_' . $courseid) ?: '#0f3d66'), '#0f3d66'
    );
    $launcherfg = \block_ai_tutor\local\input_guard::css_color(
        (string)(get_config('block_ai_tutor', 'course_launcher_fg_' . $courseid) ?: '#ffffff'), '#ffffff'
    );
    $launcherborder = \block_ai_tutor\local\input_guard::css_color(
        (string)(get_config('block_ai_tutor', 'course_launcher_border_' . $courseid) ?: 'rgba(255,255,255,0.18)'),
        'rgba(255,255,255,0.18)'
    );

    return [
        'institutionname' => format_string($institutionname),
        'institutionsubtitle' => format_string($institutionsubtitle),
        'institutionfooter' => format_text($institutionfooter, FORMAT_PLAIN),
        'coverimage' => s($coverimage),
        'logoimage' => s($logoimage),
        'coverkicker' => format_string($coverkicker),
        'covertitle' => format_string($covertitle),
        'covermessage' => format_string($covermessage),
        'covercta' => format_string($covercta),
        'launcherlabel' => format_string($launcherlabel),
        'launchertitle' => format_string($launchertitle),
        'launcherbg' => s($launcherbg),
        'launcherfg' => s($launcherfg),
        'launcherborder' => s($launcherborder),
    ];
}

/**
 * Return current indexing status for the course document base.
 *
 * @param int $courseid
 * @return array
 */
function block_ai_tutor_get_course_document_status(int $courseid): array {
    global $DB;

    $empty = [
        'documentcount' => 0,
        'chunkcount' => 0,
        'lastreindextext' => get_string('documentstatsnever', 'block_ai_tutor'),
        'statusmessage' => get_string('documentstatusempty', 'block_ai_tutor'),
        'statusclass' => 'is-empty',
    ];

    if (!\block_ai_tutor\rag\hybrid_retriever::institutional_enabled()) {
        $empty['statusmessage'] = get_string('documentstatusinternalmasterdisabled', 'block_ai_tutor');
        $empty['statusclass'] = 'is-disabled';
        return $empty;
    }

    if (!\block_ai_tutor\rag\hybrid_retriever::course_enabled($courseid)) {
        $empty['statusmessage'] = get_string('documentstatuscoursedisabled', 'block_ai_tutor');
        $empty['statusclass'] = 'is-disabled';
        return $empty;
    }

    if (\block_ai_tutor\local\schema::table_exists('block_ai_tutor_doc')) {
        $documents = $DB->get_records('block_ai_tutor_doc', [
            'courseid' => $courseid,
            'active' => 1,
        ]);
        $indexeddocuments = [];
        $chunkcount = 0;
        $lasttime = 0;
        $hasfailures = false;
        foreach ($documents as $document) {
            if (in_array((string)$document->status, ['extraction_failed', 'pending_ocr', 'embedding_failed'], true)) {
                $hasfailures = true;
            }
        }
        if (\block_ai_tutor\local\schema::table_exists('block_ai_tutor_embed')) {
            $recordset = $DB->get_recordset_select(
                'block_ai_tutor_embed',
                'courseid = :courseid AND active = 1
                    AND (embedding_status = :semanticstatus OR embedding_status = :lexicalstatus)
                    AND documentid IS NOT NULL',
                [
                    'courseid' => $courseid,
                    'semanticstatus' => 'ready',
                    'lexicalstatus' => 'lexical',
                ],
                '',
                'documentid, timeindexed'
            );
            foreach ($recordset as $chunk) {
                $chunkcount++;
                $indexeddocuments[(int)$chunk->documentid] = true;
                $lasttime = max($lasttime, (int)$chunk->timeindexed);
            }
            $recordset->close();
        }
        $documentcount = count($indexeddocuments);
        $statusclass = $documentcount > 0 ? 'is-ready' : 'is-empty';
        $statusmessage = $documentcount > 0
            ? get_string('documentstatusready', 'block_ai_tutor')
            : get_string('documentstatusempty', 'block_ai_tutor');
        if ($hasfailures) {
            $statusclass = 'is-stale';
            $statusmessage = get_string('rag_documentstatus_attention', 'block_ai_tutor');
        }
        return [
            'documentcount' => $documentcount,
            'chunkcount' => $chunkcount,
            'lastreindextext' => $lasttime
                ? userdate($lasttime, '%d/%m/%Y %H:%M')
                : get_string('documentstatsnever', 'block_ai_tutor'),
            'statusmessage' => $statusmessage,
            'statusclass' => $statusclass,
        ];
    }

    return $empty;
}

/**
 * Check every prerequisite before offering institutional document processing.
 *
 * The course switch remains an explicit authorisation boundary. This helper
 * never enables mini-RAG and never writes files, document rows or indexes.
 *
 * @param int $courseid
 * @return array{ready:bool,code:string,message:string,globalenabled:bool,courseenabled:bool,filecount:int}
 */
function block_ai_tutor_get_reindex_readiness(int $courseid): array {
    $globalenabled = \block_ai_tutor\rag\hybrid_retriever::institutional_enabled();
    $courseenabled = $globalenabled
        && (int)(get_config('block_ai_tutor', 'course_rag_enabled_' . $courseid) ?: 0) === 1;
    $filecount = 0;

    if ($courseid > 1) {
        $context = context_course::instance($courseid);
        $files = get_file_storage()->get_area_files(
            $context->id,
            'block_ai_tutor',
            'course_document_base',
            $courseid,
            'id',
            false
        );
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $filecount++;
            }
        }
    }

    if (!$globalenabled) {
        $code = 'global_disabled';
        $stringkey = 'rag_reindex_readiness_global_disabled';
    } else if (!$courseenabled && $filecount === 0) {
        $code = 'course_and_documents_missing';
        $stringkey = 'rag_reindex_readiness_course_and_documents_missing';
    } else if (!$courseenabled) {
        $code = 'course_disabled';
        $stringkey = 'rag_reindex_readiness_course_disabled';
    } else if ($filecount === 0) {
        $code = 'documents_missing';
        $stringkey = 'rag_reindex_readiness_documents_missing';
    } else {
        $code = 'ready';
        $stringkey = 'rag_reindex_readiness_ready';
    }

    return [
        'ready' => $code === 'ready',
        'code' => $code,
        'message' => get_string($stringkey, 'block_ai_tutor'),
        'globalenabled' => $globalenabled,
        'courseenabled' => $courseenabled,
        'filecount' => $filecount,
    ];
}

/**
 * Process a document immediately inside the authenticated Moodle request.
 *
 * The institutional installation does not expose web cron and does not depend
 * on cPanel. Processing remains bounded by the document limits configured in
 * the plugin and keeps the human review boundary between extraction and index.
 *
 * @param int $documentid
 * @param string $stage extract or index
 * @return stdClass Refreshed document record.
 */
function block_ai_tutor_process_document_now(int $documentid, string $stage = 'extract'): stdClass {
    global $DB;

    if (!in_array($stage, ['extract', 'index'], true)) {
        throw new coding_exception('Invalid institutional document stage.');
    }
    if (class_exists('core_php_time_limit')) {
        \core_php_time_limit::raise(900);
    }
    if ($stage === 'index') {
        // Continue safely if the browser/proxy closes while DeepSeek is still
        // returning signatures. If PHP itself terminates before the processor
        // records a final state, the shutdown guard makes the operation
        // recoverable instead of leaving a permanent `indexing` row.
        ignore_user_abort(true);
        register_shutdown_function(static function() use ($documentid): void {
            global $DB;
            try {
                $current = $DB->get_record('block_ai_tutor_doc', [
                    'id' => $documentid,
                    'active' => 1,
                ]);
                if ($current && (string)$current->status === 'indexing') {
                    $current->status = 'embedding_failed';
                    $current->lasterror = get_string('rag_index_interrupted', 'block_ai_tutor');
                    $current->timemodified = time();
                    $DB->update_record('block_ai_tutor_doc', $current);
                }
            } catch (\Throwable $e) {
                // Shutdown recovery must never replace the original failure.
            }
        });
    }

    $document = $DB->get_record('block_ai_tutor_doc', [
        'id' => $documentid,
        'active' => 1,
    ], '*', MUST_EXIST);
    $processor = new \block_ai_tutor\document\document_processor();
    if ($stage === 'index') {
        $processor->index_document($documentid);
    } else {
        $processor->extract_document($documentid);
    }

    return $DB->get_record('block_ai_tutor_doc', [
        'id' => $documentid,
        'active' => 1,
    ], '*', MUST_EXIST);
}

/**
 * Run a safe institutional extraction pass for one course.
 *
 * This function never invokes cron, queues ad hoc work or falls back to the
 * legacy indexer. Existing active batches and legacy chunks remain untouched
 * while new Markdown is generated for human review.
 *
 * @param int $courseid
 * @param bool $changedonly Process only newly created or replaced files.
 * @return array{sources: int, chunks: int, errors: int, empty: bool, details: array}
 */
function block_ai_tutor_process_institutional_reindex(int $courseid, bool $changedonly = false): array {
    global $DB;

    $readiness = block_ai_tutor_get_reindex_readiness($courseid);
    if (!$readiness['globalenabled']) {
        throw new moodle_exception('rag_internal_global_disabled', 'block_ai_tutor');
    }
    if (!$readiness['courseenabled']) {
        if ($readiness['code'] === 'course_and_documents_missing') {
            throw new moodle_exception('rag_reindex_readiness_course_and_documents_missing', 'block_ai_tutor');
        }
        throw new moodle_exception('rag_course_disabled_reindex', 'block_ai_tutor');
    }
    if ($readiness['filecount'] === 0) {
        return [
            'sources' => 0,
            'chunks' => 0,
            'errors' => 0,
            'empty' => true,
            'details' => [],
        ];
    }

    \block_ai_tutor\local\schema::require_tables(['block_ai_tutor_embed', 'block_ai_tutor_doc', 'block_ai_tutor_batch']);
    $repository = new \block_ai_tutor\document\document_repository();
    $sync = $repository->sync_course_files($courseid);
    $documents = $repository->get_course_documents($courseid);
    if (!$documents) {
        return [
            'sources' => 0,
            'chunks' => 0,
            'errors' => count($sync['errors']),
            'empty' => true,
            'details' => array_values($sync['errors']),
        ];
    }

    if ($changedonly) {
        $targetids = array_fill_keys(array_merge($sync['created'], $sync['changed']), true);
        $documents = array_filter($documents, static function($document) use ($targetids): bool {
            return isset($targetids[(int)$document->id]);
        });
    }

    $processed = 0;
    $errors = count($sync['errors']);
    $details = array_values($sync['errors']);
    foreach ($documents as $document) {
        $documentid = (int)$document->id;
        $DB->set_field('block_ai_tutor_doc', 'status', 'uploaded', ['id' => $documentid]);
        $DB->set_field('block_ai_tutor_doc', 'lasterror', null, ['id' => $documentid]);
        try {
            $result = block_ai_tutor_process_document_now($documentid, 'extract');
            if ((string)$result->status === 'pending_review') {
                $processed++;
            } else {
                $errors++;
                $details[] = [
                    'documentid' => (int)$result->id,
                    'filename' => (string)$result->filename,
                    'status' => (string)$result->status,
                    'extractor' => (string)($result->extractormode ?? ''),
                    'pagecount' => (int)$result->pagecount,
                    'extractedchars' => (int)$result->extractedchars,
                    'message' => (string)$result->lasterror,
                ];
            }
        } catch (\Throwable $e) {
            $errors++;
            $details[] = [
                'documentid' => $documentid,
                'filename' => (string)$document->filename,
                'status' => 'exception',
                'extractor' => (string)($document->extractormode ?? ''),
                'pagecount' => (int)$document->pagecount,
                'extractedchars' => (int)$document->extractedchars,
                'message' => \block_ai_tutor\security_filter::sanitize_error_message($e->getMessage()),
            ];
            debugging('VTutor direct document extraction failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    return [
        'sources' => $processed,
        'chunks' => 0,
        'errors' => $errors,
        'empty' => false,
        'details' => $details,
    ];
}

/**
 * Build a short administrator-facing summary of document processing errors.
 *
 * @param array $stats
 * @return string
 */
function block_ai_tutor_processing_error_summary(array $stats): string {
    $messages = [];
    foreach (array_slice((array)($stats['details'] ?? []), 0, 3) as $detail) {
        $detail = (array)$detail;
        $filename = trim((string)($detail['filename'] ?? ''));
        $status = trim((string)($detail['status'] ?? ''));
        $message = trim((string)($detail['message'] ?? ''));
        $metrics = [];
        if (array_key_exists('pagecount', $detail)) {
            $metrics[] = (int)$detail['pagecount'] . ' páginas';
        }
        if (array_key_exists('extractedchars', $detail)) {
            $metrics[] = (int)$detail['extractedchars'] . ' caracteres';
        }
        $label = implode(' · ', array_filter([$filename, $status, implode(', ', $metrics), $message]));
        if ($label !== '') {
            $messages[] = $label;
        }
    }
    return implode(' | ', $messages);
}


/**
 * Render an internal Moodle POST form for course reindexing.
 *
 * This avoids depending on /blocks/ai_tutor/reindex.php as the only route.
 * The caller may keep the POST on its current page. This avoids sending the
 * user to a separate route and removes reindex.php from the critical path.
 *
 * @param int $courseid
 * @param string $buttonclass
 * @param moodle_url|null $actionurl
 * @return string
 */
function block_ai_tutor_render_internal_reindex_form(
    int $courseid,
    string $buttonclass = 'btn btn-outline-primary',
    ?moodle_url $actionurl = null
): string {
    $readiness = block_ai_tutor_get_reindex_readiness($courseid);
    $action = $actionurl ?? new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]);
    $out = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $action->out(false),
        'class' => 'block-ai-tutor-inline-reindex-form d-inline-block',
        'style' => 'display:inline-block;margin:0;',
    ]);
    $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => (int)$courseid]);
    $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'vtutor_action', 'value' => 'reindex_internal']);
    $buttonattributes = [
        'type' => 'submit',
        'class' => $buttonclass,
        'title' => $readiness['message'],
    ];
    if (!$readiness['ready']) {
        $buttonattributes['disabled'] = 'disabled';
        $buttonattributes['aria-disabled'] = 'true';
    }
    $out .= html_writer::tag('button', get_string('reindexcoursebutton', 'block_ai_tutor'), $buttonattributes);
    $out .= html_writer::end_tag('form');
    return $out;
}

/**
 * Shared template data for the standalone VTutor chat window.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param renderer_base $output
 * @param bool $canadmin
 * @param bool $canmanagedocuments
 * @param bool $showdocumentstatus
 * @param bool $standalone
 * @return array
 */
function block_ai_tutor_build_chat_template_data(stdClass $course, stdClass $user, $output, bool $canadmin, bool $canmanagedocuments, bool $showdocumentstatus = false, bool $standalone = false): array {
    $branding = block_ai_tutor_get_branding_data($output, (int)$course->id);
    $docstatus = block_ai_tutor_get_course_document_status((int)$course->id);

    return array_merge($branding, [
        'courseid' => (int)$course->id,
        'coursename' => format_string($course->fullname),
        'userid' => (int)$user->id,
        'firstname' => format_string(block_ai_tutor_get_firstname($user)),
        'showadminlink' => false,
        'showdocumentstatus' => $showdocumentstatus,
        'showdocumentactions' => $showdocumentstatus && $canmanagedocuments,
        'showglobaladminactions' => $showdocumentstatus && $canadmin,
        'showlauncherbutton' => !$standalone,
        'showcontrolcenterlink' => $canmanagedocuments,
        'standalone' => $standalone,
        'adminurl' => (new moodle_url('/blocks/ai_tutor/admin.php'))->out(false),
        'manageurl' => (new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $course->id]))->out(false),
        'controlcenterurl' => (new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $course->id]))->out(false),
        'controlcenterlabel' => get_string('controlcenterlabel', 'block_ai_tutor'),
        'sesskey' => sesskey(),
        'documentsectiontitle' => get_string('documentsectiontitle', 'block_ai_tutor'),
        'documentsectiondesc' => get_string('documentsectiondesc', 'block_ai_tutor'),
        'documentsectiontypes' => get_string('documentsectiontypes', 'block_ai_tutor'),
        'documentsectionaction' => get_string('documentsectionaction', 'block_ai_tutor'),
        'managedocumentsbutton' => get_string('managedocumentsbutton', 'block_ai_tutor'),
        'openglobalconfigbutton' => get_string('openglobalconfigbutton', 'block_ai_tutor'),
        'reindexcoursebutton' => get_string('reindexcoursebutton', 'block_ai_tutor'),
        'documentstatuspaneltitle' => get_string('documentstatuspaneltitle', 'block_ai_tutor'),
        'documentstatscountlabel' => get_string('documentstatscountlabel', 'block_ai_tutor'),
        'documentstatschunkslabel' => get_string('documentstatschunkslabel', 'block_ai_tutor'),
        'documentstatslastlabel' => get_string('documentstatslastlabel', 'block_ai_tutor'),
        'documentstatsstatuslabel' => get_string('documentstatsstatuslabel', 'block_ai_tutor'),
        'documentcount' => $docstatus['documentcount'],
        'chunkcount' => $docstatus['chunkcount'],
        'lastreindextext' => $docstatus['lastreindextext'],
        'documentstatusmessage' => $docstatus['statusmessage'],
        'documentstatusclass' => $docstatus['statusclass'],
    ]);
}
