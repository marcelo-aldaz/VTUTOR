<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\rag;

defined('MOODLE_INTERNAL') || die();

/** Read-only integrity auditor for an active institutional course index. */
final class index_integrity_auditor {
    /**
     * @return array<string, int|bool>
     */
    public function audit_course(int $courseid): array {
        global $DB;

        $documents = $DB->get_records('block_ai_tutor_doc', [
            'courseid' => $courseid,
            'active' => 1,
            'status' => 'indexed',
        ]);
        // Audit the same institutional row-set that the runtime retriever is
        // allowed to consume. Historical/legacy rows (documentid IS NULL) may
        // remain active for preservation purposes, but they are outside the
        // v10.6.5 Mini-RAG contract and must never be mixed into document/vector
        // parity.
        $institutionalsql = "SELECT e.*
                  FROM {block_ai_tutor_embed} e
                  JOIN {block_ai_tutor_doc} d ON d.id = e.documentid
                  JOIN {block_ai_tutor_batch} b ON b.id = e.batchid
                       AND b.documentid = d.id
                 WHERE e.courseid = :courseid
                   AND d.courseid = :documentcourseid
                   AND b.courseid = :batchcourseid
                   AND e.active = 1
                   AND d.active = 1
                   AND d.status = :documentstatus
                   AND b.active = 1
                   AND b.status = :batchstatus
                   AND (e.embedding_status = :semanticstatus
                        OR e.embedding_status = :lexicalstatus)";
        $institutionalparams = [
            'courseid' => $courseid,
            'documentcourseid' => $courseid,
            'batchcourseid' => $courseid,
            'documentstatus' => 'indexed',
            'batchstatus' => 'active',
            'semanticstatus' => 'ready',
            'lexicalstatus' => 'lexical',
        ];
        $institutionalrows = $DB->get_records_sql($institutionalsql, $institutionalparams);
        $activechunks = count($institutionalrows);

        $batches = $DB->get_records('block_ai_tutor_batch', [
            'courseid' => $courseid,
            'active' => 1,
            'status' => 'active',
        ]);
        $semanticrows = [];
        $activelexical = 0;
        foreach ($institutionalrows as $row) {
            if ((string)$row->embedding_status === 'ready') {
                $semanticrows[(int)$row->id] = $row;
            } else if ((string)$row->embedding_status === 'lexical') {
                $activelexical++;
            }
        }
        $activelegacy = (int)$DB->count_records_select(
            'block_ai_tutor_embed',
            'courseid = :courseid AND active = 1 AND documentid IS NULL',
            ['courseid' => $courseid]
        );

        $invalidvectors = 0;
        foreach ($semanticrows as $row) {
            $vector = json_decode((string)$row->embedding_vector, true);
            if (!is_array($vector) || !$vector
                    || (int)$row->embedding_dimensions !== deepseek_semantic_vectorizer::DIMENSIONS
                    || strpos((string)$row->embedding_model, deepseek_semantic_vectorizer::FORMAT_VERSION . ':') !== 0) {
                $invalidvectors++;
            }
        }

        $orphanbatches = 0;
        $batchchunkparity = true;
        foreach ($batches as $batch) {
            if (!isset($documents[(int)$batch->documentid])) {
                $orphanbatches++;
            }
            $actual = (int)$DB->count_records('block_ai_tutor_embed', [
                'courseid' => $courseid,
                'batchid' => (int)$batch->id,
                'active' => 1,
            ]);
            if ($actual !== (int)$batch->chunkcount) {
                $batchchunkparity = false;
            }
        }

        // Inspect every active institutional row touching this course, even
        // if its document/batch reference is broken. LEFT JOIN is deliberate:
        // missing links are integrity errors and must not disappear from the
        // auditor merely because an INNER JOIN could not resolve them.
        $touchingrows = $DB->get_records_sql(
            "SELECT e.id, e.courseid AS embedcourseid, e.documentid AS embeddocumentid,
                    d.id AS documentid, d.courseid AS documentcourseid,
                    b.courseid AS batchcourseid, b.documentid AS batchdocumentid,
                    b.active AS batchactive, b.status AS batchstatus
               FROM {block_ai_tutor_embed} e
          LEFT JOIN {block_ai_tutor_doc} d ON d.id = e.documentid
          LEFT JOIN {block_ai_tutor_batch} b ON b.id = e.batchid
              WHERE e.active = 1
                AND e.documentid IS NOT NULL
                AND (e.courseid = :embedcourseid
                     OR d.courseid = :documentcourseid
                     OR b.courseid = :batchcourseid)",
            [
                'embedcourseid' => $courseid,
                'documentcourseid' => $courseid,
                'batchcourseid' => $courseid,
            ]
        );
        $crosscourserows = 0;
        foreach ($touchingrows as $row) {
            if ($row->documentid === null
                    || (int)$row->embeddocumentid !== (int)$row->documentid
                    || (int)$row->embedcourseid !== (int)$row->documentcourseid
                    || $row->batchcourseid === null
                    || (int)$row->batchcourseid !== (int)$row->embedcourseid
                    || (int)$row->batchdocumentid !== (int)$row->documentid
                    || (int)$row->batchactive !== 1
                    || (string)$row->batchstatus !== 'active') {
                $crosscourserows++;
            }
        }

        $declaredchunks = 0;
        $declaredvectors = 0;
        foreach ($documents as $document) {
            $declaredchunks += (int)$document->chunkcount;
            $declaredvectors += (int)$document->embeddingcount;
        }
        $semanticcount = count($semanticrows);
        $parity = $activechunks === ($semanticcount + $activelexical)
            && $declaredchunks === $activechunks
            && $declaredvectors === $semanticcount;
        $activebatches = count($batches);
        $batchdocumentparity = $activebatches === count($documents)
            && $orphanbatches === 0;
        $approved = count($documents) > 0
            && $activebatches > 0
            && $activechunks > 0
            && $parity
            && $batchdocumentparity
            && $batchchunkparity
            && $invalidvectors === 0
            && $crosscourserows === 0;

        return [
            'documents' => count($documents),
            'activechunks' => $activechunks,
            'semanticvectors' => $semanticcount,
            'activelexical' => $activelexical,
            'activelegacy' => $activelegacy,
            'activebatches' => $activebatches,
            'orphanbatches' => $orphanbatches,
            'batchdocumentparity' => $batchdocumentparity,
            'batchchunkparity' => $batchchunkparity,
            'declaredchunks' => $declaredchunks,
            'declaredvectors' => $declaredvectors,
            'invalidvectors' => $invalidvectors,
            'crosscourserows' => $crosscourserows,
            'parity' => $parity,
            'approved' => $approved,
        ];
    }
}
