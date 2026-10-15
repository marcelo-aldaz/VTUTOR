<?php
// Diagnóstico administrativo VTutor Pro V-Zona.
define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
$plugin = new stdClass();
require(__DIR__ . '/version.php');

$courseid = optional_param('courseid', 0, PARAM_INT);

require_login();
$systemcontext = context_system::instance();
$course = null;
$context = $systemcontext;
if ($courseid > 1) {
    $course = get_course($courseid);
    require_login($course);
    $context = context_course::instance($courseid);
}

$canadmin = has_capability('moodle/site:config', $systemcontext);
$canmanage = $canadmin || ($courseid > 1 && has_capability('block/ai_tutor:manage', $context));
if (!$canmanage) {
    throw new required_capability_exception($context, 'block/ai_tutor:manage', 'nopermissions', '');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/ai_tutor/diagnostico.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout($courseid > 1 ? 'incourse' : 'admin');
$PAGE->set_title(get_string('diagnosticopage', 'block_ai_tutor'));
$PAGE->set_heading(get_string('diagnosticopage', 'block_ai_tutor'));

$schema = [];
try {
    $schema = \block_ai_tutor\local\schema::inspect_tables();
} catch (\Throwable $e) {
    $schema = [
        'created' => [],
        'existing' => [],
        'missing' => [],
        'missingfields' => [],
        'errors' => [$e->getMessage()],
    ];
}
$missingfields = (array)($schema['missingfields'] ?? []);
$embedready = \block_ai_tutor\local\schema::table_exists('block_ai_tutor_embed')
    && !array_filter($missingfields, static function(string $field): bool {
        return strpos($field, 'block_ai_tutor_embed.') === 0;
    });
$docready = \block_ai_tutor\local\schema::table_exists('block_ai_tutor_doc')
    && !array_filter($missingfields, static function(string $field): bool {
        return strpos($field, 'block_ai_tutor_doc.') === 0;
    });

$vtutoraction = optional_param('vtutor_action', '', PARAM_ALPHANUMEXT);
if ($vtutoraction !== '' && (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST')) {
    throw new moodle_exception('invalidrequest', 'error');
}
if ($vtutoraction === 'reindex_internal') {
    require_sesskey();
    if ($courseid <= 1) {
        throw new moodle_exception('invalidcourseid');
    }
    try {
        $stats = block_ai_tutor_process_institutional_reindex($courseid);
        if (!empty($stats['empty'])) {
            redirect(new moodle_url('/blocks/ai_tutor/diagnostico.php', ['courseid' => $courseid]),
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
        redirect(new moodle_url('/blocks/ai_tutor/diagnostico.php', ['courseid' => $courseid]),
            $message,
            null,
            $notificationtype);
    } catch (\Throwable $e) {
        $safeerror = \block_ai_tutor\security_filter::sanitize_error_message((string)$e->getMessage());
        redirect(new moodle_url('/blocks/ai_tutor/diagnostico.php', ['courseid' => $courseid]),
            get_string('managecoursepage_internal_reindex_error', 'block_ai_tutor', $safeerror),
            null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

$tables = [
    'block_ai_tutor_msg',
    'block_ai_tutor_embed',
    'block_ai_tutor_doc',
    'block_ai_tutor_batch',
    'block_ai_tutor_route_log',
    'block_ai_tutor_coursemap',
    'block_ai_tutor_webhook_log',
];
$rows = [];
foreach ($tables as $t) {
    $exists = \block_ai_tutor\local\schema::table_exists($t);
    $count = '';
    if ($exists) {
        try { $count = (string)$DB->count_records($t); } catch (\Throwable $e) { $count = 'Error: ' . s($e->getMessage()); }
    }
    $rows[] = [s($t), $exists ? 'OK' : 'FALTA', $count];
}

$fileitems = [];
$managedchunks = 0;
$allchunks = 0;
$lastlegacyindex = 0;
$lastinternalindex = 0;
$documentstates = [];
$extractorstates = [];
$activevectors = 0;
$activelexical = 0;
$globallegacychunks = 0;
$globalinstitutionalchunks = 0;
$coursemapcount = 0;
$coursemapduplicates = 0;
$inactivedocuments = 0;
$documentdetailrows = [];
$pipelineprobes = [];
$integrityrows = [];
$integrityapproved = false;
$integrity = null;
if ($courseid > 1) {
    $pipelineprobes[] = ['Motor de base de datos', 'Información', s((string)$DB->get_dbfamily())];
    try {
        if (!$docready) {
            throw new moodle_exception('errorupgradingschema', 'block_ai_tutor');
        }
        $DB->get_records(
            'block_ai_tutor_doc',
            ['courseid' => $courseid, 'active' => 1],
            'id ASC',
            'id,status,processingphase',
            0,
            1
        );
        $pipelineprobes[] = ['Lectura del ciclo documental', 'OK', 'block_ai_tutor_doc'];
    } catch (\Throwable $e) {
        $pipelineprobes[] = [
            'Lectura del ciclo documental',
            'ERROR',
            s(\block_ai_tutor\security_filter::sanitize_error_message($e->getMessage())),
        ];
    }
    try {
        if (!$embedready) {
            throw new moodle_exception('errorupgradingschema', 'block_ai_tutor');
        }
        $DB->get_record_select(
            'block_ai_tutor_embed',
            'courseid = :courseid AND contenthash = :contenthash
                AND embedding_model = :model AND embedding_status = :status
                AND ' . $DB->sql_isnotempty('block_ai_tutor_embed', 'embedding_vector', true, true),
            [
                'courseid' => $courseid,
                'contenthash' => str_repeat('0', 64),
                'model' => '__vtutor_readonly_probe__',
                'status' => 'ready',
            ],
            'id',
            IGNORE_MULTIPLE
        );
        $pipelineprobes[] = ['Consulta portable de reutilización vectorial', 'OK', 'block_ai_tutor_embed'];
    } catch (\Throwable $e) {
        $pipelineprobes[] = [
            'Consulta portable de reutilización vectorial',
            'ERROR',
            s(\block_ai_tutor\security_filter::sanitize_error_message($e->getMessage())),
        ];
    }
    try {
        $DB->count_records('block_ai_tutor_batch', ['courseid' => $courseid]);
        $pipelineprobes[] = ['Lectura de lotes atómicos', 'OK', 'block_ai_tutor_batch'];
    } catch (\Throwable $e) {
        $pipelineprobes[] = [
            'Lectura de lotes atómicos',
            'ERROR',
            s(\block_ai_tutor\security_filter::sanitize_error_message($e->getMessage())),
        ];
    }
    try {
        if (!$docready || !$embedready
                || !\block_ai_tutor\local\schema::table_exists('block_ai_tutor_batch')) {
            throw new moodle_exception('errorupgradingschema', 'block_ai_tutor');
        }
        $integrity = (new \block_ai_tutor\rag\index_integrity_auditor())->audit_course($courseid);
        $integrityapproved = (bool)$integrity['approved'];
        $integrityrows = [
            ['Documentos indexados activos', (string)$integrity['documents'],
                (int)$integrity['documents'] > 0 ? 'OK' : 'PENDIENTE'],
            ['Lotes atómicos activos', (string)$integrity['activebatches'],
                (int)$integrity['activebatches'] > 0 ? 'OK' : 'PENDIENTE'],
            ['Paridad documento/lote activo', $integrity['batchdocumentparity'] ? 'Exacta' : 'No coincide',
                $integrity['batchdocumentparity'] ? 'OK' : 'ERROR'],
            ['Paridad chunks declarados por lote', $integrity['batchchunkparity'] ? 'Exacta' : 'No coincide',
                $integrity['batchchunkparity'] ? 'OK' : 'ERROR'],
            ['Lotes activos sin documento indexado activo', (string)$integrity['orphanbatches'],
                (int)$integrity['orphanbatches'] === 0 ? 'OK' : 'ERROR'],
            ['Fragmentos institucionales activos', (string)$integrity['activechunks'],
                (int)$integrity['activechunks'] > 0 ? 'OK' : 'PENDIENTE'],
            ['Fragmentos heredados activos aislados', (string)$integrity['activelegacy'], 'INFO'],
            ['Vectores semánticos activos', (string)$integrity['semanticvectors'],
                (int)$integrity['semanticvectors'] > 0 ? 'OK' : 'PENDIENTE'],
            ['Vectores inválidos o incompatibles', (string)$integrity['invalidvectors'],
                (int)$integrity['invalidvectors'] === 0 ? 'OK' : 'ERROR'],
            ['Paridad documento/chunks/vectores', $integrity['parity'] ? 'Exacta' : 'No coincide',
                $integrity['parity'] ? 'OK' : 'ERROR'],
            ['Filas cruzadas o lotes inconsistentes', (string)$integrity['crosscourserows'],
                (int)$integrity['crosscourserows'] === 0 ? 'OK' : 'ERROR'],
            ['Resultado de integridad READ-ONLY', $integrityapproved ? 'APROBADO' : 'PENDIENTE',
                $integrityapproved ? 'OK' : 'REVISAR'],
        ];
        $pipelineprobes[] = ['Auditoría de integridad e aislamiento', 'OK', 'Cero escrituras'];
    } catch (\Throwable $e) {
        $integrityrows = [[
            'Auditoría de integridad e aislamiento',
            'No disponible',
            s(\block_ai_tutor\security_filter::sanitize_error_message($e->getMessage())),
        ]];
        $pipelineprobes[] = [
            'Auditoría de integridad e aislamiento',
            'ERROR',
            s(\block_ai_tutor\security_filter::sanitize_error_message($e->getMessage())),
        ];
    }
}
if ($embedready) {
    $globallegacychunks = (int)$DB->count_records_select(
        'block_ai_tutor_embed',
        'documentid IS NULL'
    );
    $globalinstitutionalchunks = (int)$DB->count_records_select(
        'block_ai_tutor_embed',
        'documentid IS NOT NULL'
    );
}
if ($courseid > 1) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'block_ai_tutor', 'course_document_base', $courseid, 'filename', false);
    foreach ($files as $file) {
        if ($file->is_directory()) { continue; }
        $fileitems[] = [s($file->get_filename()), display_size($file->get_filesize()), userdate($file->get_timemodified(), '%d/%m/%Y %H:%M')];
    }
    if ($embedready) {
        $allchunks = (int)$DB->count_records('block_ai_tutor_embed', ['courseid' => $courseid]);
        $managedchunks = (int)$DB->count_records_select('block_ai_tutor_embed', 'courseid = ? AND ' . $DB->sql_like('contenttype', '?', false), [$courseid, 'managedoc%']);
        $lastlegacy = $DB->get_record_sql(
            'SELECT MAX(timeindexed) AS lastindex
               FROM {block_ai_tutor_embed}
              WHERE courseid = ? AND documentid IS NULL',
            [$courseid]
        );
        $lastlegacyindex = (int)($lastlegacy->lastindex ?? 0);
        $lastinternal = $DB->get_record_sql(
            'SELECT MAX(timeindexed) AS lastindex
               FROM {block_ai_tutor_embed}
              WHERE courseid = ? AND documentid IS NOT NULL',
            [$courseid]
        );
        $lastinternalindex = (int)($lastinternal->lastindex ?? 0);
        // Reuse the integrity auditor as the single source of truth for
        // operational institutional counts. This avoids a secondary diagnostic
        // query drifting from the exact row-set consumed by the runtime.
        if (is_array($integrity)) {
            $activevectors = (int)$integrity['semanticvectors'];
            $activelexical = (int)$integrity['activelexical'];
        }
    }
    if ($docready) {
        $documents = $DB->get_records('block_ai_tutor_doc', ['courseid' => $courseid, 'active' => 1]);
        $inactivedocuments = (int)$DB->count_records('block_ai_tutor_doc', [
            'courseid' => $courseid,
            'active' => 0,
        ]);
        foreach ($documents as $document) {
            $documentstates[(string)$document->status] = ($documentstates[(string)$document->status] ?? 0) + 1;
            $extractor = trim((string)($document->extractormode ?? ''));
            if ($extractor !== '') {
                $extractorstates[$extractor] = ($extractorstates[$extractor] ?? 0) + 1;
            }
            $documentdetailrows[] = [
                (int)$document->id,
                s((string)$document->filename),
                s((string)$document->status),
                s((string)($document->processingphase ?: '—')),
                s($extractor ?: '—'),
                (int)$document->pagecount,
                (int)$document->extractedchars,
                (int)$document->chunkcount,
                s((string)($document->lasterror ?: '—')),
                userdate((int)$document->timemodified, '%d/%m/%Y %H:%M'),
            ];
        }
    }
    if (\block_ai_tutor\local\schema::table_exists('block_ai_tutor_coursemap')) {
        $coursemapcount = (int)$DB->count_records('block_ai_tutor_coursemap', ['courseid' => $courseid]);
        $duplicaterows = $DB->get_records_sql(
            'SELECT MIN(id) AS id, courseid, cmid, COUNT(1) AS duplicatecount
               FROM {block_ai_tutor_coursemap}
              WHERE courseid = :courseid
           GROUP BY courseid, cmid
             HAVING COUNT(1) > 1',
            ['courseid' => $courseid]
        );
        foreach ($duplicaterows as $duplicaterow) {
            $coursemapduplicates += max(0, (int)$duplicaterow->duplicatecount - 1);
        }
    }
}

$provider = (string)(get_config('block_ai_tutor', 'provider') ?: 'deepseek');
$model = (string)(get_config('block_ai_tutor', 'model') ?: '');
$effectivemodel = \block_ai_tutor\ai_service::normalize_model_name($provider, $model);
$baseurl = \block_ai_tutor\ai_service::official_base_url();
$apikey = (string)(get_config('block_ai_tutor', 'api_key') ?: '');
$internalmaster = \block_ai_tutor\rag\hybrid_retriever::institutional_enabled();
$courseinternalconfigured = $courseid > 1
    ? (int)(get_config('block_ai_tutor', 'course_rag_enabled_' . $courseid) ?: 0)
    : 0;
$semanticready = \block_ai_tutor\rag\deepseek_semantic_vectorizer::is_configured();
$semanticmodel = $semanticready
    ? (new \block_ai_tutor\rag\deepseek_semantic_vectorizer())->get_model()
    : \block_ai_tutor\rag\deepseek_semantic_vectorizer::FORMAT_VERSION;
$pdfstatus = \block_ai_tutor\document\pdf_extractor::status();
$reindexreadiness = $courseid > 1 ? block_ai_tutor_get_reindex_readiness($courseid) : null;

function block_ai_tutor_diag_table(array $headers, array $rows): string {
    $out = html_writer::start_tag('table', ['class' => 'generaltable table table-striped']);
    $out .= html_writer::start_tag('thead') . html_writer::start_tag('tr');
    foreach ($headers as $h) { $out .= html_writer::tag('th', s($h)); }
    $out .= html_writer::end_tag('tr') . html_writer::end_tag('thead') . html_writer::start_tag('tbody');
    foreach ($rows as $row) {
        $out .= html_writer::start_tag('tr');
        foreach ($row as $cell) { $out .= html_writer::tag('td', (string)$cell); }
        $out .= html_writer::end_tag('tr');
    }
    $out .= html_writer::end_tag('tbody') . html_writer::end_tag('table');
    return $out;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('diagnosticopage', 'block_ai_tutor'));

echo html_writer::tag('div', get_string('diagnosticohelp', 'block_ai_tutor'), ['class' => 'alert alert-info']);

echo $OUTPUT->heading(get_string('diagnosticosectionconfig', 'block_ai_tutor'), 3);
echo block_ai_tutor_diag_table(['Elemento', 'Valor'], [
    ['Release', s((string)($plugin->release ?? ''))],
    ['Provider', s($provider)],
    ['Model configurado', s($model ?: 'NO CONFIGURADO')],
    ['Model efectivo enviado a API', s($effectivemodel ?: 'NO CONFIGURADO')],
    ['Base URL efectiva', s($baseurl)],
    ['API key', $apikey !== '' ? 'Configurada' : 'FALTA'],
    ['RAG heredado', 'Retirado del runtime; registros históricos permanecen aislados'],
    ['Motor mini-RAG institucional', $internalmaster ? 'Disponible' : 'Desactivado globalmente'],
    ['Procesamiento documental', 'Directo desde Moodle; no requiere cron web, tareas ad hoc ni cPanel'],
    ['Identidad técnica en el chat', 'Verificada por el servidor; versión, proveedor y modelo no dependen del LLM'],
    ['Perfil efectivo del usuario', 'Derivado exclusivamente de capacidades Moodle; no cambia por instrucciones del chat'],
    ['Fidelidad documental', 'Auditoría condicional integral de síntesis relacionales y fallback local seguro; sin sustitución de palabras'],
    ['Modo base del mini-RAG', 'Vector disperso local con firma semántica DeepSeek y fallback léxico'],
    ['Modelo del índice semántico', s($semanticmodel)],
    ['Vectorización DeepSeek', $semanticready
        ? get_string('rag_semantic_configured_not_probed', 'block_ai_tutor')
        : get_string('rag_deepseek_semantic_not_configured', 'block_ai_tutor')],
    ['Dimensión hashing local', (string)\block_ai_tutor\rag\deepseek_semantic_vectorizer::DIMENSIONS],
    ['Reranking', (int)(get_config('block_ai_tutor', 'rag_deepseek_rerank_enabled') ?? 0)
        ? 'Condicional: solo ante ambigüedad o baja confianza'
        : 'Desactivado'],
    ['pdftotext opcional', $pdfstatus['available']
        ? 'Disponible: ' . s($pdfstatus['resolved'])
        : 'No disponible'],
    ['Extractor PDF interno', $pdfstatus['fallback'] ? 'Activo' : 'Desactivado'],
]);

if ($provider === 'deepseek' && $model !== '' && $model !== $effectivemodel) {
    echo html_writer::div('Modelo DeepSeek normalizado automáticamente: ' . s($model) . ' → ' . s($effectivemodel) . '. DeepSeek exige identificadores en minúsculas.', 'alert alert-warning');
}

echo $OUTPUT->heading(get_string('diagnosticosectionschema', 'block_ai_tutor'), 3);
echo block_ai_tutor_diag_table(['Tabla', 'Estado', 'Registros'], $rows);
echo $OUTPUT->heading('Integridad e aislamiento de índices', 4);
echo block_ai_tutor_diag_table(['Elemento', 'Valor'], [
    ['Fragmentos heredados globales (documentid NULL)', (string)$globallegacychunks],
    ['Fragmentos institucionales globales (documentid no NULL)', (string)$globalinstitutionalchunks],
    ['Registros del mapa pedagógico en este curso', $courseid > 1 ? (string)$coursemapcount : 'No aplica'],
    ['Duplicados exactos courseid + cmid en este curso', $courseid > 1 ? (string)$coursemapduplicates : 'No aplica'],
]);
if (!empty($schema['missing'])) {
    echo html_writer::div('Elementos ausentes; ejecute la actualización oficial de Moodle: '
        . s(implode(', ', $schema['missing'])), 'alert alert-danger');
}
if (!empty($schema['missingfields'])) {
    echo html_writer::div('Columnas ausentes; instale la actualización oficial de VTutor Pro para repararlas: '
        . s(implode(', ', $schema['missingfields'])), 'alert alert-danger');
}
if (!empty($schema['errors'])) {
    echo html_writer::div('Errores de inspección de esquema: ' . s(implode(' | ', $schema['errors'])), 'alert alert-danger');
}

if ($courseid > 1) {
    if (!$internalmaster) {
        $operationalstate = 'BLOQUEADO: motor mini-RAG institucional desactivado globalmente';
    } else if ($reindexreadiness['code'] === 'course_and_documents_missing') {
        $operationalstate = 'PENDIENTE: activar mini-RAG en este curso y cargar documentos';
    } else if (!$courseinternalconfigured) {
        $operationalstate = 'PENDIENTE: activar mini-RAG únicamente para este curso';
    } else if (!$fileitems) {
        $operationalstate = 'PENDIENTE: cargar documentos en la base documental del curso';
    } else if ($activevectors > 0) {
        $operationalstate = 'OPERATIVO: recuperación vectorial semántica DeepSeek con respaldo léxico';
    } else if ($activelexical > 0) {
        $operationalstate = 'OPERATIVO: recuperación léxica local; migración semántica DeepSeek pendiente';
    } else {
        $operationalstate = 'PENDIENTE: procesar, revisar y aprobar documentos';
    }

    echo $OUTPUT->heading(get_string('diagnosticosectioncourse', 'block_ai_tutor'), 3);
    echo $OUTPUT->heading('Preflight READ-ONLY del pipeline documental', 4);
    echo block_ai_tutor_diag_table(['Control', 'Estado', 'Detalle'], $pipelineprobes);
    echo $OUTPUT->heading('Auditoría READ-ONLY del índice activo', 4);
    echo block_ai_tutor_diag_table(['Control', 'Evidencia', 'Estado'], $integrityrows);
    echo block_ai_tutor_diag_table(['Elemento', 'Valor'], [
        ['Curso', format_string($course->fullname) . ' / ID ' . (int)$courseid],
        ['Archivos cargados en base documental', (string)count($fileitems)],
        ['Fragmentos documentales managedoc (históricos + activos)', (string)$managedchunks],
        ['Filas RAG totales del curso (incluye legado histórico)', (string)$allchunks],
        ['Vectores semánticos institucionales activos', (string)$activevectors],
        ['Fragmentos léxicos institucionales activos', (string)$activelexical],
        ['Estados documentales', s($documentstates ? json_encode($documentstates) : 'Sin registros')],
        ['Extractores utilizados', s($extractorstates ? json_encode($extractorstates) : 'Sin registros')],
        ['Registros documentales inactivos conservados', (string)$inactivedocuments],
        ['Activación mini-RAG en este curso', $courseinternalconfigured ? 'Activado' : 'Desactivado'],
        ['Estado efectivo mini-RAG', \block_ai_tutor\rag\hybrid_retriever::course_enabled($courseid) ? 'Activo' : 'Desactivado'],
        ['Preparación operativa', s($operationalstate)],
        ['Último índice heredado histórico', $lastlegacyindex ? userdate($lastlegacyindex, '%d/%m/%Y %H:%M') : 'Nunca'],
        ['Última indexación institucional', $lastinternalindex ? userdate($lastinternalindex, '%d/%m/%Y %H:%M') : 'Nunca'],
    ]);
    if (!$reindexreadiness['ready']) {
        echo html_writer::div(s($reindexreadiness['message']), 'alert alert-warning');
    }
    if (!$semanticready) {
        echo html_writer::div(get_string('rag_deepseek_semantic_not_ready_diag', 'block_ai_tutor'), 'alert alert-info');
    }
    echo $OUTPUT->heading(get_string('diagnosticosectionfiles', 'block_ai_tutor'), 4);
    echo $fileitems ? block_ai_tutor_diag_table(['Archivo', 'Tamaño', 'Modificado'], $fileitems) : html_writer::div(get_string('managecoursepage_nofiles', 'block_ai_tutor'), 'alert alert-warning');
    echo $OUTPUT->heading('Detalle técnico de documentos activos', 4);
    echo $documentdetailrows
        ? block_ai_tutor_diag_table(
            ['ID', 'Documento', 'Estado', 'Fase', 'Extractor', 'Páginas', 'Caracteres', 'Chunks', 'Último error', 'Modificado'],
            $documentdetailrows
        )
        : html_writer::div('No existen registros documentales activos para este curso.', 'alert alert-warning');
    echo html_writer::div(
        html_writer::link(new moodle_url('/blocks/ai_tutor/manage.php', ['courseid' => $courseid]), get_string('managedocumentsbutton', 'block_ai_tutor'), ['class' => 'btn btn-primary']) . ' ' .
        block_ai_tutor_render_internal_reindex_form(
            $courseid,
            'btn btn-outline-primary',
            new moodle_url('/blocks/ai_tutor/diagnostico.php', ['courseid' => $courseid])
        ) . ' ' .
        html_writer::link(new moodle_url('/blocks/ai_tutor/chat.php', ['courseid' => $courseid]), get_string('openchatwindowbutton', 'block_ai_tutor'), ['class' => 'btn btn-outline-secondary', 'target' => '_blank']),
        'block_ai_tutor_manage_actions'
    );
}

echo $OUTPUT->footer();
