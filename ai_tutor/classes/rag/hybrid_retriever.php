<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\rag;

defined('MOODLE_INTERNAL') || die();

/**
 * Course-isolated hybrid semantic and lexical retrieval.
 */
class hybrid_retriever {
    /**
     * Whether the institutional mini-RAG subsystem is available globally.
     *
     * Historical legacy chunks are not part of the v10.6.5 runtime contract.
     */
    public static function institutional_enabled(): bool {
        $configured = get_config('block_ai_tutor', 'internal_rag_enabled');
        return $configured === false || (int)$configured === 1;
    }

    public static function course_enabled(int $courseid): bool {
        return self::institutional_enabled()
            && (int)(get_config('block_ai_tutor', 'course_rag_enabled_' . $courseid) ?: 0) === 1;
    }

    /**
     * @return array{context: string, sources: array<int, string>, hasevidence: bool, strict: bool}
     */
    public function retrieve(int $courseid, string $question): array {
        global $DB;

        $strict = (string)(get_config('block_ai_tutor', 'course_rag_mode_' . $courseid) ?: 'strict') === 'strict';
        if (!self::course_enabled($courseid)
            || !\block_ai_tutor\local\schema::table_exists('block_ai_tutor_doc')) {
            return ['context' => '', 'sources' => [], 'hasevidence' => false, 'strict' => $strict];
        }

        // Resolve exact document/page intent before any provider call. Named
        // document retrieval is deterministic and lexical-first, so spending a
        // DeepSeek query signature on it adds cost without improving isolation.
        $targetdocuments = $this->target_documents($courseid, $question);
        $rankingquestion = $this->ranking_question($question, $targetdocuments);
        $tokens = $this->keywords($rankingquestion);
        $structuralphrases = $this->structural_phrases($question);
        $requestedpage = $this->requested_page($question);
        if ($requestedpage === null && $this->question_requests_header($question)) {
            $requestedpage = 1;
        }

        // Query vectors are deterministic and local. DeepSeek is used once when
        // documents are indexed to enrich their sparse semantic signatures; a
        // normal user question must not incur a second provider call merely to
        // search that already-stored index. Document-side concepts/synonyms and
        // the lexical anchor remain comparable with this local query vector.
        $queryvector = [];
        $querymodel = '';
        if (empty($targetdocuments) && deepseek_semantic_vectorizer::is_configured()) {
            $queryvector = deepseek_semantic_vectorizer::signature_to_vector([], $question);
            $querymodel = deepseek_semantic_vectorizer::configured_model_id();
        }

        $rawsemanticweight = get_config('block_ai_tutor', 'rag_semantic_weight');
        $semanticweight = max(0.0, min(1.0, $rawsemanticweight === false ? 0.75 : (float)$rawsemanticweight));
        if (!$queryvector) {
            $semanticweight = 0.0;
        }
        $rawthreshold = get_config('block_ai_tutor', 'rag_min_score');
        $threshold = max(0.0, min(1.0, $rawthreshold === false ? 0.12 : (float)$rawthreshold));
        $topk = max(1, min(12, (int)(get_config('block_ai_tutor', 'rag_topk') ?: 5)));
        $candidatecount = max($topk, min(100, (int)(get_config('block_ai_tutor', 'rag_semantic_candidates') ?: 12)));
        $sql = "SELECT e.*, d.filename, d.status AS documentstatus
                  FROM {block_ai_tutor_embed} e
                  JOIN {block_ai_tutor_doc} d ON d.id = e.documentid
                  JOIN {block_ai_tutor_batch} b ON b.id = e.batchid
                       AND b.documentid = d.id
                 WHERE e.courseid = :courseid
                   AND d.courseid = :documentcourseid
                   AND b.courseid = :batchcourseid
                   AND e.active = 1
                   AND (e.embedding_status = :semanticstatus OR e.embedding_status = :lexicalstatus)
                   AND d.active = 1
                   AND d.status = :documentstatus
                   AND b.active = 1
                   AND b.status = :batchstatus";
        $records = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'documentcourseid' => $courseid,
            'batchcourseid' => $courseid,
            'semanticstatus' => 'ready',
            'lexicalstatus' => 'lexical',
            'documentstatus' => 'indexed',
            'batchstatus' => 'active',
        ]);
        $scored = [];
        foreach ($records as $record) {
            $documentid = (int)$record->documentid;
            $targeted = !empty($targetdocuments);
            if ($targeted && !isset($targetdocuments[$documentid])) {
                // An explicit filename in the question is a document scope,
                // not merely another weak keyword. Never drift to another
                // indexed document in the same course.
                continue;
            }
            if ($targeted && $requestedpage !== null
                    && !$this->record_contains_page($record, $requestedpage)) {
                // An explicit page within an explicitly named document is a
                // hard page scope. Returning another page as if it answered
                // the request would create false provenance.
                continue;
            }

            $titlecontext = trim((string)$record->title . ' ' . (string)$record->filename);
            $lexical = $this->lexical_score(
                $tokens,
                (string)$record->content,
                (string)$record->headingpath,
                $titlecontext
            );
            $semantic = 0.0;
            $hascompatiblesemantic = $queryvector && (string)$record->embedding_status === 'ready'
                && (string)$record->embedding_model === $querymodel;
            if ($hascompatiblesemantic) {
                $vector = json_decode((string)$record->embedding_vector, true);
                if (is_array($vector)) {
                    $semantic = max(0.0, vector_math::sparse_cosine_similarity($queryvector, $vector));
                }
            }
            // Legacy semantic vectors and lexical fallback rows remain usable
            // lexically during a safe course migration; incompatible vector
            // formats are never compared or blended. For an explicitly named
            // document, lexical evidence is authoritative: semantic similarity
            // may refine ties but must not hide an exact section/article match.
            if ($targeted) {
                $score = $lexical + ($hascompatiblesemantic ? (0.25 * $semantic) : 0.0);
            } else {
                $score = self::combine_retrieval_scores(
                    $lexical,
                    $semantic,
                    $hascompatiblesemantic,
                    $semanticweight
                );
            }

            $structuralhits = $this->structural_hits($structuralphrases,
                trim((string)$record->content . ' ' . (string)$record->headingpath));
            if ($targeted) {
                // Exact document targeting guarantees a lexical-safe fallback
                // even when the semantic query is weak or temporarily fails.
                $score += 1.0;
                // Exact structural evidence (for example ACUERDA or Artículo 1)
                // must dominate generic filename/title matches.
                $score += 3.0 * $structuralhits;
            }
            if ($requestedpage !== null && $this->record_contains_page($record, $requestedpage)) {
                $score += 1.0;
            }

            if ($score >= $threshold || $targeted) {
                $scored[] = ['score' => $score, 'record' => $record];
            }
        }
        usort($scored, static function($left, $right) {
            return $right['score'] <=> $left['score'];
        });
        $scored = array_slice($scored, 0, $candidatecount);

        if (empty($targetdocuments) && self::should_rerank($scored)) {
            // Remote reranking is opt-in because it adds a provider request.
            // The stable default is local hybrid ranking only.
            $rerankenabled = get_config('block_ai_tutor', 'rag_deepseek_rerank_enabled');
            if ((int)$rerankenabled === 1 && deepseek_semantic_vectorizer::is_configured()) {
                try {
                    $vectorizer = new deepseek_semantic_vectorizer();
                    $rerankinput = [];
                    foreach ($scored as $item) {
                        $record = $item['record'];
                        $rerankinput[] = [
                            'id' => (int)$record->id,
                            'text' => (string)$record->content,
                            'source' => $this->source_label($record),
                            'score' => (float)$item['score'],
                        ];
                    }
                    $reranked = $vectorizer->rerank($question, $rerankinput, $courseid);
                    $byid = [];
                    foreach ($scored as $item) {
                        $byid[(int)$item['record']->id] = $item['record'];
                    }
                    $scored = [];
                    foreach ($reranked as $item) {
                        if (isset($byid[(int)$item['id']])) {
                            $scored[] = ['score' => (float)$item['score'], 'record' => $byid[(int)$item['id']]];
                        }
                    }
                } catch (\Throwable $e) {
                    debugging('VTutor DeepSeek rerank fallback: '
                        . \block_ai_tutor\security_filter::sanitize_error_message($e->getMessage()), DEBUG_DEVELOPER);
                }
            }
        }

        $picked = [];
        $seen = [];
        $documentruns = [];
        foreach ($scored as $item) {
            $record = $item['record'];
            $hash = (string)($record->contenthash ?: sha1((string)$record->content));
            if (isset($seen[$hash])) {
                continue;
            }
            $documentid = (int)$record->documentid;
            $documentruns[$documentid] = ($documentruns[$documentid] ?? 0) + 1;
            $perdocumentlimit = !empty($targetdocuments) ? $topk : 3;
            if ($documentruns[$documentid] > $perdocumentlimit) {
                continue;
            }
            $seen[$hash] = true;
            $picked[] = $record;
            if (count($picked) >= $topk) {
                break;
            }
        }

        if (!empty($targetdocuments) && $structuralphrases && $picked) {
            $picked = $this->expand_structural_neighbors($picked, $records, $structuralphrases, $topk, $requestedpage);
        }

        $parts = [];
        $sources = [];
        foreach ($picked as $record) {
            $source = $this->source_label($record);
            $parts[] = "[Fuente: {$source}]\n" . trim((string)$record->content);
            $sources[] = $source;
        }
        $context = '';
        if ($parts) {
            $context = "<document_context>\n"
                . implode("\n\n", $parts)
                . "\n</document_context>";
        }
        return [
            'context' => $context,
            'sources' => array_values(array_unique($sources)),
            'hasevidence' => !empty($parts),
            'strict' => $strict,
        ];
    }

    /**
     * Expand structural hits with immediate chunks from the same active batch.
     *
     * Legal headings and their first article frequently straddle a chunk
     * boundary. Once a named document yields a literal structural hit, include
     * its immediate predecessor/successor before filling remaining top-k slots.
     * Neighbours are restricted to the same document and atomic batch, so this
     * cannot cross course/document isolation boundaries.
     *
     * @param array<int, \stdClass> $picked
     * @param array<int, \stdClass> $records
     * @param array<int, string> $structuralphrases
     * @return array<int, \stdClass>
     */
    private function expand_structural_neighbors(
        array $picked,
        array $records,
        array $structuralphrases,
        int $topk,
        ?int $requestedpage = null
    ): array {
        $byposition = [];
        foreach ($records as $record) {
            $key = (int)$record->documentid . ':' . (int)$record->batchid . ':' . (int)$record->chunk_index;
            $byposition[$key] = $record;
        }

        $priority = [];
        $seen = [];
        $append = static function(\stdClass $record) use (&$priority, &$seen): void {
            $id = (int)$record->id;
            if ($id <= 0 || isset($seen[$id])) {
                return;
            }
            $seen[$id] = true;
            $priority[] = $record;
        };

        foreach ($picked as $record) {
            $haystack = trim((string)$record->content . ' ' . (string)$record->headingpath);
            if ($this->structural_hits($structuralphrases, $haystack) < 1) {
                continue;
            }
            $documentid = (int)$record->documentid;
            $batchid = (int)$record->batchid;
            $index = (int)$record->chunk_index;
            foreach ([$index - 1, $index, $index + 1] as $neighborindex) {
                if ($neighborindex < 0) {
                    continue;
                }
                $key = $documentid . ':' . $batchid . ':' . $neighborindex;
                if (isset($byposition[$key])) {
                    $neighbor = $byposition[$key];
                    if ($requestedpage !== null && !$this->record_contains_page($neighbor, $requestedpage)) {
                        continue;
                    }
                    $append($neighbor);
                }
            }
        }

        foreach ($picked as $record) {
            $append($record);
        }
        $priority = array_slice($priority, 0, max(1, $topk));

        // For a single explicitly targeted document, source-order context is
        // easier for the LLM to interpret and keeps adjacent legal clauses
        // contiguous. Ranking has already determined which neighbourhoods win.
        usort($priority, static function($left, $right) {
            $doccmp = (int)$left->documentid <=> (int)$right->documentid;
            if ($doccmp !== 0) {
                return $doccmp;
            }
            return (int)$left->chunk_index <=> (int)$right->chunk_index;
        });
        return $priority;
    }

    /**
     * Combine lexical and semantic evidence without allowing the semantic
     * component to suppress a direct lexical match.
     *
     * FINAL QA moved query vector construction from a remote DeepSeek semantic
     * signature to a deterministic local query vector. Keeping the historical
     * 0.75 semantic blend meant a valid lexical match of 0.15 was reduced to
     * 0.0375 and fell below the default 0.12 threshold. That produced false
     * strict-mode "no evidence" responses even though the indexed chunk
     * contained the requested terms.
     *
     * Semantic evidence may improve ranking, but documentary lexical evidence
     * is a floor: it must never be degraded by blending.
     */
    public static function combine_retrieval_scores(
        float $lexical,
        float $semantic,
        bool $hascompatiblesemantic,
        float $semanticweight
    ): float {
        $lexical = max(0.0, min(1.0, $lexical));
        $semantic = max(0.0, min(1.0, $semantic));
        if (!$hascompatiblesemantic) {
            return $lexical;
        }
        $semanticweight = max(0.0, min(1.0, $semanticweight));
        $lexicalweight = 1.0 - $semanticweight;
        $blended = ($semanticweight * $semantic) + ($lexicalweight * $lexical);
        return max($lexical, $blended);
    }

    /**
     * Reranking is reserved for ambiguous or low-confidence result sets.
     *
     * @param array<int, array{score:float, record:mixed}> $scored
     */
    public static function should_rerank(array $scored): bool {
        if (count($scored) < 2) {
            return false;
        }
        $best = (float)$scored[0]['score'];
        $second = (float)$scored[1]['score'];
        $rawdelta = get_config('block_ai_tutor', 'rag_rerank_ambiguity_delta');
        $delta = max(0.01, min(0.30, $rawdelta === false ? 0.08 : (float)$rawdelta));
        $rawconfidence = get_config('block_ai_tutor', 'rag_rerank_confidence');
        $confidence = max(0.10, min(0.95, $rawconfidence === false ? 0.42 : (float)$rawconfidence));
        return $best < $confidence || abs($best - $second) <= $delta;
    }

    /**
     * Detect documents explicitly named by the user within the current course.
     *
     * Matching is intentionally course-bound and uses both the full filename and
     * basename without extension. The returned IDs are used as a hard retrieval
     * scope; this prevents a query for one named PDF from drifting to another.
     *
     * @return array<int, string> documentid => filename
     */
    private function target_documents(int $courseid, string $question): array {
        global $DB;

        $questionnormalized = $this->normalize_filename_text($question);
        if ($questionnormalized === '') {
            return [];
        }
        $documents = $DB->get_records('block_ai_tutor_doc', [
            'courseid' => $courseid,
            'active' => 1,
            'status' => 'indexed',
        ], '', 'id,filename');
        $matches = [];
        foreach ($documents as $document) {
            $filename = trim((string)$document->filename);
            if ($filename === '') {
                continue;
            }
            $full = $this->normalize_filename_text($filename);
            $basename = $this->normalize_filename_text(pathinfo($filename, PATHINFO_FILENAME));
            if (($full !== '' && strpos($questionnormalized, $full) !== false)
                    || ($basename !== '' && \core_text::strlen($basename) >= 8
                        && strpos($questionnormalized, $basename) !== false)) {
                $matches[(int)$document->id] = $filename;
            }
        }
        return $matches;
    }

    /**
     * Remove an explicitly selected filename from relevance terms.
     *
     * Once a document is already a hard scope, counting its filename again in
     * every chunk makes all chunks look equally relevant and can swamp the
     * section/article words that actually distinguish the requested passage.
     *
     * @param array<int, string> $targetdocuments
     */
    private function ranking_question(string $question, array $targetdocuments): string {
        $ranking = $question;
        foreach ($targetdocuments as $filename) {
            $candidates = [
                (string)$filename,
                (string)pathinfo((string)$filename, PATHINFO_FILENAME),
            ];
            foreach ($candidates as $candidate) {
                if ($candidate !== '') {
                    $ranking = str_ireplace($candidate, ' ', $ranking);
                }
            }
        }
        return trim((string)preg_replace('/\s+/u', ' ', $ranking));
    }

    /**
     * Detect explicit structural/legal anchors whose literal presence should
     * outrank broad semantic similarity inside a named document.
     *
     * @return array<int, string>
     */
    private function structural_phrases(string $question): array {
        $normalized = $this->normalize_lexical($question);
        $phrases = [];
        foreach ([
            'acuerda', 'articulo 1', 'articulo primero', 'objeto',
            'disposiciones generales', 'disposiciones finales',
            'considerando', 'comuniquese publiquese y cumplase',
        ] as $phrase) {
            if (strpos($normalized, $phrase) !== false) {
                $phrases[] = $phrase;
            }
        }
        return array_values(array_unique($phrases));
    }

    /**
     * Count literal structural anchors present in a candidate chunk.
     *
     * @param array<int, string> $phrases
     */
    private function structural_hits(array $phrases, string $content): int {
        if (!$phrases || $content === '') {
            return 0;
        }
        $normalized = $this->normalize_lexical($content);
        $hits = 0;
        foreach ($phrases as $phrase) {
            if ($phrase !== '' && strpos($normalized, $phrase) !== false) {
                $hits++;
            }
        }
        return $hits;
    }

    /**
     * Extract a requested page number from common Spanish/English forms.
     */
    private function requested_page(string $question): ?int {
        $normalized = $this->normalize_lexical($question);
        if (preg_match('/\\b(?:pagina|page)\\s*(?:n(?:o|ro)?\\.?\\s*)?(\\d{1,4})\\b/u', $normalized, $matches)) {
            return max(1, (int)$matches[1]);
        }
        if (preg_match('/\\b(?:primera pagina|primer pagina|first page)\\b/u', $normalized)) {
            return 1;
        }
        return null;
    }

    /**
     * Whether the wording explicitly asks for the header/opening of a document.
     */
    private function question_requests_header(string $question): bool {
        $normalized = $this->normalize_lexical($question);
        foreach (['encabezado', 'primeras lineas', 'inicio del documento', 'header', 'opening lines'] as $needle) {
            if (strpos($normalized, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check whether a chunk covers the requested page.
     */
    private function record_contains_page(\stdClass $record, int $page): bool {
        $start = (int)($record->page_start ?? 0);
        $end = (int)($record->page_end ?? 0);
        if ($start <= 0) {
            return false;
        }
        if ($end <= 0) {
            $end = $start;
        }
        return $page >= $start && $page <= $end;
    }

    /**
     * Normalize filenames and query text without losing alphanumeric identity.
     */
    private function normalize_filename_text(string $text): string {
        $text = $this->normalize_lexical($text);
        $text = preg_replace('/[^\\pL\\pN]+/u', ' ', $text);
        return trim(preg_replace('/\\s+/u', ' ', (string)$text));
    }

    private function keywords(string $text): array {
        $text = $this->normalize_lexical($text);
        $words = preg_split('/[^\pL\pN_]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stop = ['que', 'para', 'como', 'con', 'una', 'unos', 'unas', 'este', 'esta', 'estas', 'estos',
            'desde', 'hasta', 'sobre', 'entre', 'porque', 'donde', 'cuando', 'cual', 'cuales', 'por', 'del',
            'las', 'los', 'sus', 'son', 'fue', 'han', 'ser', 'sea', 'segun', 'qué', 'cómo'];
        return array_values(array_unique(array_filter($words, static function($word) use ($stop) {
            return \core_text::strlen($word) >= 3 && !in_array($word, $stop, true);
        })));
    }

    private function lexical_score(array $tokens, string $content, string $heading, string $title): float {
        if (!$tokens) {
            return 0.0;
        }
        $content = $this->normalize_lexical($content);
        $heading = $this->normalize_lexical($heading);
        $title = $this->normalize_lexical($title);
        $matches = 0.0;
        $matchedtokens = 0;
        foreach ($tokens as $token) {
            $matched = false;
            if (strpos($content, $token) !== false) {
                $matches += 1.0;
                $matched = true;
            }
            if (strpos($heading, $token) !== false || strpos($title, $token) !== false) {
                $matches += 0.5;
                $matched = true;
            }
            if ($matched) {
                $matchedtokens++;
            }
        }
        if (!$matchedtokens) {
            return 0.0;
        }
        // One meaningful match must clear the default 0.12 threshold, while
        // broader coverage still ranks above isolated matches.
        return max(0.15, min(1.0, $matches / count($tokens)));
    }

    private function normalize_lexical(string $text): string {
        $text = \core_text::strtolower($text);
        return strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ]);
    }

    private function source_label(\stdClass $record): string {
        $label = trim((string)($record->filename ?? $record->title ?? $record->contenttype));
        $start = (int)$record->page_start;
        $end = (int)$record->page_end;
        if ($start > 0) {
            $label .= $end > $start ? ", páginas {$start}-{$end}" : ", página {$start}";
        }
        if (trim((string)$record->headingpath) !== '') {
            $label .= ', ' . trim((string)$record->headingpath);
        }
        return $label;
    }
}
