<?php
// This file is part of Moodle - http://moodle.org/
// blocks/ai_tutor/db/upgrade.php

defined('MOODLE_INTERNAL') || die();

function xmldb_block_ai_tutor_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026060601) {
        $table = new xmldb_table('block_ai_tutor_route_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('routekey', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null);
            $table->add_field('routename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('triggerreason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, 'suggested');
            $table->add_field('teachervalidated', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('human_decision', XMLDB_TYPE_CHAR, '60', null, null, null, null);
            $table->add_field('validatedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timevalidated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
            $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026060601, 'ai_tutor');
    }


    if ($oldversion < 2026060602) {
        $table = new xmldb_table('block_ai_tutor_coursemap');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sectionnum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('modname', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
            $table->add_field('itemname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('pedagogicaltype', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, 'resource');
            $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $table->add_index('cmid', XMLDB_INDEX_NOTUNIQUE, ['cmid']);
            $table->add_index('pedagogicaltype', XMLDB_INDEX_NOTUNIQUE, ['pedagogicaltype']);
            $table->add_index('course_type', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'pedagogicaltype']);
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026060602, 'ai_tutor');
    }


    if ($oldversion < 2026060604) {
        $table = new xmldb_table('block_ai_tutor_embed');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('contenttype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
            $table->add_field('contentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('embedding_text', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('chunk_index', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
            $table->add_field('timeindexed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $table->add_index('contenttype', XMLDB_INDEX_NOTUNIQUE, ['contenttype']);
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026060604, 'ai_tutor');
    }


    if ($oldversion < 2026060605) {
        try {
            \block_ai_tutor\local\schema::ensure_tables(true);
        } catch (\Throwable $e) {
            debugging('VTutor schema emergency upgrade failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        upgrade_block_savepoint(true, 2026060605, 'ai_tutor');
    }


    if ($oldversion < 2026060606) {
        $provider = (string)(get_config('block_ai_tutor', 'provider') ?: 'deepseek');
        $model = (string)(get_config('block_ai_tutor', 'model') ?: '');
        if ($provider === 'deepseek') {
            $normalized = \block_ai_tutor\ai_service::normalize_model_name($provider, $model);
            if ($normalized === '') {
                $normalized = 'deepseek-v4-flash';
            }
            if ($normalized !== $model) {
                set_config('model', $normalized, 'block_ai_tutor');
            }
        }

        upgrade_block_savepoint(true, 2026060606, 'ai_tutor');
    }


    if ($oldversion < 2026060607) {
        try {
            \block_ai_tutor\local\schema::ensure_tables(true);
        } catch (\Throwable $e) {
            debugging('VTutor v10.3.6 internal flow schema check failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Mantener normalizado el modelo DeepSeek en instalaciones heredadas.
        $provider = (string)(get_config('block_ai_tutor', 'provider') ?: 'deepseek');
        $model = (string)(get_config('block_ai_tutor', 'model') ?: '');
        if ($provider === 'deepseek') {
            $normalized = \block_ai_tutor\ai_service::normalize_model_name($provider, $model);
            if ($normalized === '') {
                $normalized = 'deepseek-v4-flash';
            }
            if ($normalized !== $model) {
                set_config('model', $normalized, 'block_ai_tutor');
            }
        }

        upgrade_block_savepoint(true, 2026060607, 'ai_tutor');
    }

    if ($oldversion < 2026072301) {
        // Add the institutional document lifecycle and hybrid RAG fields without
        // deleting or rewriting any legacy chunk.
        $schemaresult = \block_ai_tutor\local\schema::ensure_tables(true);
        if (!empty($schemaresult['errors'])) {
            throw new moodle_exception(
                'errorupgradingschema',
                'block_ai_tutor',
                '',
                implode(' | ', $schemaresult['errors'])
            );
        }

        $table = new xmldb_table('block_ai_tutor_embed');
        if ($dbman->table_exists($table)) {
            $indexes = [
                new xmldb_index('course_active', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'active']),
                new xmldb_index('documentid', XMLDB_INDEX_NOTUNIQUE, ['documentid']),
                new xmldb_index('batchid', XMLDB_INDEX_NOTUNIQUE, ['batchid']),
            ];
            foreach ($indexes as $index) {
                if (!$dbman->index_exists($table, $index)) {
                    $dbman->add_index($table, $index);
                }
            }
        }

        // Existing text-only chunks remain available as legacy fallback. They
        // are not presented as semantic embeddings.
        $DB->execute(
            "UPDATE {block_ai_tutor_embed}
                SET embedding_status = :status, active = :active
              WHERE embedding_status IS NULL OR embedding_status = ''",
            ['status' => 'legacy', 'active' => 1]
        );

        upgrade_block_savepoint(true, 2026072301, 'ai_tutor');
    }

    if ($oldversion < 2026072302) {
        // Keep the institutional mini-RAG available independently from the
        // legacy RAG switch. Course activation remains opt-in and no existing
        // chunk or course setting is rewritten.
        if (get_config('block_ai_tutor', 'internal_rag_enabled') === false) {
            set_config('internal_rag_enabled', 1, 'block_ai_tutor');
        }

        try {
            \block_ai_tutor\local\schema::ensure_tables(true);
        } catch (\Throwable $e) {
            debugging('VTutor v10.5.6 ALPHA2 schema check failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        upgrade_block_savepoint(true, 2026072302, 'ai_tutor');
    }

    if ($oldversion < 2026072303) {
        // ALPHA3 adds only diagnostic metadata and safe defaults. Existing
        // legacy chunks, institutional batches and course activation flags are
        // preserved unchanged.
        $schemaresult = \block_ai_tutor\local\schema::ensure_tables(true);
        if (!empty($schemaresult['errors'])) {
            throw new moodle_exception(
                'errorupgradingschema',
                'block_ai_tutor',
                '',
                implode(' | ', $schemaresult['errors'])
            );
        }
        if (get_config('block_ai_tutor', 'pdf_native_fallback') === false) {
            set_config('pdf_native_fallback', 1, 'block_ai_tutor');
        }

        upgrade_block_savepoint(true, 2026072303, 'ai_tutor');
    }

    if ($oldversion < 2026072304) {
        // ALPHA3 HF1 changes only the execution strategy: extraction and
        // indexing run directly from authenticated Moodle actions. No schema,
        // course activation, document, chunk or legacy index is rewritten.
        upgrade_block_savepoint(true, 2026072304, 'ai_tutor');
    }

    if ($oldversion < 2026072305) {
        // Remove only exact course/module duplicates and prevent recurrence.
        // No document, embedding, batch or valid course-map record is deleted.
        $table = new xmldb_table('block_ai_tutor_coursemap');
        if ($dbman->table_exists($table)) {
            \block_ai_tutor\coursemap::deduplicate();
            $index = new xmldb_index(
                'course_cmid',
                XMLDB_INDEX_UNIQUE,
                ['courseid', 'cmid']
            );
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        upgrade_block_savepoint(true, 2026072305, 'ai_tutor');
    }

    if ($oldversion < 2026072306) {
        // HF3 changes only runtime response governance. It preserves all
        // documents, batches, chunks, course maps and configuration values.
        upgrade_block_savepoint(true, 2026072306, 'ai_tutor');
    }

    if ($oldversion < 2026072307) {
        // HF4 adds a local response-fidelity guard. No stored document,
        // active batch, chunk, course setting or schema object is changed.
        upgrade_block_savepoint(true, 2026072307, 'ai_tutor');
    }

    if ($oldversion < 2026072308) {
        // HF5 replaces word substitution with a bounded relational audit and
        // deterministic fallback. Stored RAG data and schema remain intact.
        upgrade_block_savepoint(true, 2026072308, 'ai_tutor');
    }

    if ($oldversion < 2026073101) {
        // RC1 freezes the HF5 functional code for final Moodle acceptance.
        // No document, Markdown, batch, chunk, course map, history row or
        // configuration value is created, deleted or rewritten.
        upgrade_block_savepoint(true, 2026073101, 'ai_tutor');
    }

    if ($oldversion < 2026081001) {
        // VECTOR RC1 keeps all documents and active batches intact. The new
        // DeepSeek index is built explicitly from stored Markdown and becomes
        // active only after a complete course migration succeeds.
        if (get_config('block_ai_tutor', 'rag_deepseek_semantic_enabled') === false) {
            set_config('rag_deepseek_semantic_enabled', 1, 'block_ai_tutor');
        }
        if (get_config('block_ai_tutor', 'rag_deepseek_rerank_enabled') === false) {
            set_config('rag_deepseek_rerank_enabled', 1, 'block_ai_tutor');
        }
        // Retire the obsolete second-provider credential and endpoint. No
        // external embeddings service remains part of the supported design.
        foreach (['embedding_apikey', 'embedding_baseurl', 'embedding_endpoint',
            'embedding_model', 'embedding_dimensions', 'embedding_timeout', 'embedding_batch_size'] as $legacysetting) {
            unset_config($legacysetting, 'block_ai_tutor');
        }
        upgrade_block_savepoint(true, 2026081001, 'ai_tutor');
    }

    if ($oldversion < 2026081002) {
        // Correct hidden inherited limits that could make DeepSeek end in the
        // middle of a word. Existing documents, vectors, batches, prompts and
        // history records are preserved exactly as stored.
        $configuredtokens = get_config('block_ai_tutor', 'max_tokens');
        if ($configuredtokens === false || (int)$configuredtokens < 512) {
            set_config('max_tokens', 1200, 'block_ai_tutor');
        }
        if (get_config('block_ai_tutor', 'security_max_output_chars') === false) {
            set_config('security_max_output_chars', 8000, 'block_ai_tutor');
        }
        $storemode = get_config('block_ai_tutor', 'security_store_mode');
        if ($storemode === false) {
            set_config('security_store_mode', 'full', 'block_ai_tutor');
        }
        $truncatechars = get_config('block_ai_tutor', 'security_store_truncate_chars');
        if ($truncatechars === false || ($storemode === 'truncate' && (int)$truncatechars <= 300)) {
            set_config('security_store_truncate_chars', 4000, 'block_ai_tutor');
        }
        upgrade_block_savepoint(true, 2026081002, 'ai_tutor');
    }

    if ($oldversion < 2026081106) {
        // RC4 preserves every document, batch, chunk and vector. Use a safer
        // singleton default only when the administrator never stored a value.
        if (get_config('block_ai_tutor', 'rag_semantic_batch_size') === false) {
            set_config('rag_semantic_batch_size', 1, 'block_ai_tutor');
        }
        upgrade_block_savepoint(true, 2026081106, 'ai_tutor');
    }

    if ($oldversion < 2026081107) {
        // RC5 repairs only missing plugin-owned tables/columns left by an
        // interrupted or intermediate upgrade. It never deletes or rewrites
        // documents, Markdown, chunks, vectors, batches or course settings.
        $schemaresult = \block_ai_tutor\local\schema::ensure_tables(true);
        if (!empty($schemaresult['errors'])) {
            throw new moodle_exception(
                'errorupgradingschema',
                'block_ai_tutor',
                '',
                implode(' | ', $schemaresult['errors'])
            );
        }
        upgrade_block_savepoint(true, 2026081107, 'ai_tutor');
    }

    if ($oldversion < 2026081108) {
        // RC6 changes runtime recovery only. Existing document states, files,
        // Markdown, batches, chunks, vectors and settings remain untouched.
        upgrade_block_savepoint(true, 2026081108, 'ai_tutor');
    }

    if ($oldversion < 2026081109) {
        // RC7 adds only the processingphase diagnostic field. The schema guard
        // is additive and preserves all document and index content.
        $schemaresult = \block_ai_tutor\local\schema::ensure_tables(true);
        if (!empty($schemaresult['errors'])) {
            throw new moodle_exception(
                'errorupgradingschema',
                'block_ai_tutor',
                '',
                implode(' | ', $schemaresult['errors'])
            );
        }
        upgrade_block_savepoint(true, 2026081109, 'ai_tutor');
    }

    if ($oldversion < 2026081110) {
        // RC7 HF1 adds only read-only diagnostic probes.
        upgrade_block_savepoint(true, 2026081110, 'ai_tutor');
    }

    if ($oldversion < 2026081111) {
        // RC8 hardens read-time course isolation and adds a read-only index
        // auditor. It does not create, update or delete RAG data or settings.
        upgrade_block_savepoint(true, 2026081111, 'ai_tutor');
    }

    if ($oldversion < 2026081112) {
        // Stable promotion of the LIVE-accepted RC8 code. No document, batch,
        // chunk, vector, prompt, credential or course setting is changed.
        upgrade_block_savepoint(true, 2026081112, 'ai_tutor');
    }

    if ($oldversion < 2026081601) {
        // Final STABLE HF1 is code-only hardening: runtime identity, course-bound
        // Human-in-the-Loop route validation, privacy metadata and provider
        // transport safety. It does not alter RAG documents, Markdown, batches,
        // chunks, vectors, prompts, credentials or course activation settings.
        upgrade_block_savepoint(true, 2026081601, 'ai_tutor');
    }

    if ($oldversion < 2026081602) {
        // HF1.1 changes only package metadata so Moodle can validate version.php
        // using literal plugin metadata. No schema, RAG content or setting changes.
        upgrade_block_savepoint(true, 2026081602, 'ai_tutor');
    }

    if ($oldversion < 2026081603) {
        // FINAL RC1 fixes regression-test identity, HITL route de-duplication SQL,
        // privacy export completeness, and narrows the validated release contract
        // to DeepSeek. It does not rewrite RAG documents, batches, chunks, vectors,
        // prompts, credentials, or per-course activation settings.
        upgrade_block_savepoint(true, 2026081603, 'ai_tutor');
    }

    if ($oldversion < 2026081701) {
        // FINAL RC2 hardens answer continuation assembly and runtime identity
        // recognition. It does not alter schema, documents, batches, chunks,
        // vectors, prompts, credentials, course activation or stored history.
        upgrade_block_savepoint(true, 2026081701, 'ai_tutor');
    }

    if ($oldversion < 2026081702) {
        // FINAL RC3 changes retrieval-time ranking only: an explicitly named
        // indexed document becomes the hard course-local retrieval scope and an
        // explicitly requested page is prioritised. No document, chunk, vector,
        // batch, schema, credential or course activation setting is rewritten.
        upgrade_block_savepoint(true, 2026081702, 'ai_tutor');
    }

    if ($oldversion < 2026081703) {
        // FINAL RC4 hardens dependency-free PDF extraction: page-tree count,
        // inherited resources and page-bound Form XObjects. Existing document
        // indexes are preserved; administrators reindex a document/course
        // explicitly when they want the improved extraction to replace an
        // older partial index.
        upgrade_block_savepoint(true, 2026081703, 'ai_tutor');
    }

    if ($oldversion < 2026081704) {
        // FINAL RC5 changes retrieval-time ranking only. Explicitly named
        // documents use lexical-first ranking, filename terms are removed from
        // intra-document relevance scoring, and exact structural anchors such
        // as ACUERDA / Artículo 1 receive deterministic priority. No schema,
        // document, batch, chunk or vector data is rewritten automatically.
        upgrade_block_savepoint(true, 2026081704, 'ai_tutor');
    }


    if ($oldversion < 2026081705) {
        // FINAL RC6 hardens PDF provenance and retrieval continuity only. It
        // reconciles complete visible N/TOTAL page markers, keeps new chunks
        // page-local, and expands structural hits with immediate neighbours.
        // Existing indexes remain untouched until an administrator explicitly
        // reindexes the affected course/document.
        upgrade_block_savepoint(true, 2026081705, 'ai_tutor');
    }

    if ($oldversion < 2026081706) {
        // FINAL RC7 changes only runtime extraction/normalization behavior.
        // Existing document indices are never rewritten automatically.
        upgrade_block_savepoint(true, 2026081706, 'ai_tutor');
    }

    if ($oldversion < 2026081707) {
        // FINAL QA consolidates the supported runtime contract without touching
        // documents, Markdown, batches, chunks or vectors. Provider and legacy
        // settings are normalised so ordinary chat cannot drift back to old
        // connectors or trigger unnecessary remote reranking/reindexing.
        set_config('provider', 'deepseek', 'block_ai_tutor');
        set_config('baseurl', \block_ai_tutor\ai_service::official_base_url(), 'block_ai_tutor');
        $model = \block_ai_tutor\ai_service::normalize_model_name(
            'deepseek',
            (string)(get_config('block_ai_tutor', 'model') ?: 'deepseek-v4-flash')
        );
        set_config('model', $model, 'block_ai_tutor');
        set_config('temperature', \block_ai_tutor\ai_service::normalize_temperature(
            get_config('block_ai_tutor', 'temperature')
        ), 'block_ai_tutor');
        set_config('max_tokens', \block_ai_tutor\ai_service::normalize_max_tokens(
            get_config('block_ai_tutor', 'max_tokens')
        ), 'block_ai_tutor');
        set_config('internal_rag_enabled', 1, 'block_ai_tutor');
        set_config('rag_enabled', 0, 'block_ai_tutor');
        set_config('rag_autolazy', 0, 'block_ai_tutor');
        set_config('rag_deepseek_rerank_enabled', 0, 'block_ai_tutor');
        set_config('rag_relational_llm_review', 0, 'block_ai_tutor');
        $batchsize = get_config('block_ai_tutor', 'rag_semantic_batch_size');
        if ($batchsize === false || (int)$batchsize === 1) {
            // Older RCs forced 1 as a defensive temporary default. The final
            // bounded JSON contract safely batches four chunks, cutting routine
            // semantic-index requests while retaining per-item compact recovery.
            set_config('rag_semantic_batch_size', 4, 'block_ai_tutor');
        }
        upgrade_block_savepoint(true, 2026081707, 'ai_tutor');
    }


    if ($oldversion < 2026082401) {
        // FINAL QA2 repairs the retrieval-score regression introduced when
        // query vectors became local/deterministic. Direct lexical evidence is
        // now a non-decreasing floor: semantic blending may improve ranking but
        // can never suppress a valid textual match below the evidence threshold.
        // This is runtime-only and does not rewrite documents, Markdown, batches,
        // chunks, vectors, prompts, credentials or per-course activation.
        upgrade_block_savepoint(true, 2026082401, 'ai_tutor');
    }


    if ($oldversion < 2026082402) {
        // FINAL QA3 adds a deterministic response-start integrity guard,
        // hardens native PDF provenance against orphan-stream fallback, and
        // aligns distributed metadata/tests. It does not rewrite documents,
        // Markdown, batches, chunks, vectors, credentials or course settings.
        upgrade_block_savepoint(true, 2026082402, 'ai_tutor');
    }

    if ($oldversion < 2026082403) {
        // FINAL QA4 bounds answer-generation cost and closure behavior. A new
        // response obeys the configured per-profile word ceiling; at most one
        // short continuation is requested when DeepSeek reports a length stop.
        // No documents, batches, chunks, vectors or course activation are
        // rewritten.
        upgrade_block_savepoint(true, 2026082403, 'ai_tutor');
    }

    if ($oldversion < 2026082404) {
        // FINAL QA5 fixes query routing only: strict document mode now fails
        // closed exclusively for explicit documentary-evidence requests; pure
        // social turns bypass RAG/provider calls, and ordinary learning turns
        // may fall back to user-visible Moodle course context. It also restores
        // bounded visible activity text selection for reused courses. No schema,
        // document, batch, chunk, vector, credential or course activation is
        // rewritten and no reindex is triggered.
        upgrade_block_savepoint(true, 2026082404, 'ai_tutor');
    }

    if ($oldversion < 2026082405) {
        // FINAL QA6 aligns the READ-ONLY integrity auditor with the same
        // institutional row-set consumed by the Mini-RAG runtime. Historical
        // legacy rows are reported separately and are neither activated,
        // deactivated, deleted nor rewritten. No document, batch, chunk,
        // vector, credential or course setting is modified.
        upgrade_block_savepoint(true, 2026082405, 'ai_tutor');
    }

    if ($oldversion < 2026082406) {
        // v10.6.5 STABLE is a metadata-only promotion of the LIVE-accepted
        // FINAL QA6 codebase. No schema, document, batch, chunk, vector,
        // credential, course activation, routing or HITL data is modified.
        upgrade_block_savepoint(true, 2026082406, 'ai_tutor');
    }

    return true;
}
