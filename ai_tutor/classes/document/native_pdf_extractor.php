<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\document;

defined('MOODLE_INTERNAL') || die();

/**
 * Dependency-free extractor for PDFs that contain a selectable text layer.
 *
 * This is a bounded fallback for shared hosting. It reads page content
 * streams and common ToUnicode maps without executing server commands. It is
 * not OCR: scanned/image-only PDFs are returned as pending OCR.
 */
class native_pdf_extractor implements pdf_extractor_interface {
    private const MAX_OBJECTS = 12000;
    private const MAX_DECODED_STREAM = 8388608;

    public function extract(\stored_file $file): array {
        $pdf = (string)$file->get_content();
        if ($pdf === '' || strpos($pdf, '%PDF-') !== 0) {
            throw new \moodle_exception('rag_mime_mismatch', 'block_ai_tutor');
        }
        if (preg_match('/\/Encrypt\b/', $pdf)) {
            throw new \moodle_exception('rag_pdf_encrypted', 'block_ai_tutor');
        }

        $objects = $this->expand_object_streams($this->parse_objects($pdf));
        $detectedpages = (int)preg_match_all('/\/Type\s*\/Page\b/', $pdf);
        $objectpages = 0;
        foreach ($objects as $body) {
            if (preg_match('/\/Type\s*\/Page\b/', $body)) {
                $objectpages++;
            }
        }
        $pagecount = max(1, $detectedpages, $objectpages);
        $maxpages = max(1, (int)(get_config('block_ai_tutor', 'rag_max_pdf_pages') ?: 40));

        $fontmaps = $this->build_font_maps($objects);
        $globalmap = $this->merge_font_maps($fontmaps);
        $usedpagetree = false;
        $pageids = $this->page_order($objects, $usedpagetree);

        // The ordered page tree is authoritative when it is available. Raw
        // /Type /Page counts may include orphan/template page objects that are
        // not part of the document page tree and must not inflate pagecount.
        if ($pageids) {
            $pagecount = count($pageids);
        }
        if ($pagecount > $maxpages) {
            throw new \moodle_exception('rag_pdf_too_many_pages', 'block_ai_tutor', '', $maxpages);
        }

        $pages = [];
        foreach ($pageids as $index => $pageid) {
            $pagebody = (string)($objects[$pageid] ?? '');
            $resourcecontext = $this->page_resource_context((int)$pageid, $objects);
            $pagemaps = $this->page_font_maps($resourcecontext, $objects, $fontmaps);
            $content = '';
            foreach ($this->content_references($pagebody) as $contentid) {
                if (isset($objects[$contentid])) {
                    $content .= "\n" . $this->decode_stream((string)$objects[$contentid]);
                }
            }

            $text = $this->decode_content($content, $pagemaps, $globalmap);

            // Many producer-generated PDFs place visible text in Form XObjects
            // invoked from a page content stream. Decode those forms within
            // the page that invokes them so page provenance is preserved.
            $visitedforms = [];
            $formtext = $this->decode_invoked_form_xobjects(
                $content,
                $resourcecontext,
                $objects,
                $fontmaps,
                $globalmap,
                $visitedforms,
                0
            );
            if ($formtext !== '') {
                $text = trim($text . "\n" . $formtext);
            }
            $pages[$index + 1] = $text;
        }

        // Some producer-generated PDFs expose page objects in an order that
        // differs from the human-visible pagination even though the page tree
        // count is correct. When every extracted page carries an unambiguous
        // visible N/TOTAL marker (for example Quipux 1/11 ... 11/11), use that
        // marker to reconcile provenance. The remap is all-or-nothing: partial
        // markers never rewrite page metadata.
        $pages = $this->reconcile_visible_page_numbers($pages, $pagecount);

        // Text can also live in Form XObjects or in structures whose page
        // resource inheritance is not directly visible. If page extraction is
        // empty or unusually short, scan decoded text streams conservatively.
        $initialtext = trim(implode("\n", $pages));
        $initialchars = \core_text::strlen($initialtext);
        $minimum = max(200, $pagecount * 80);
        if ((!$pages || $initialchars < $minimum) && !$usedpagetree) {
            // A global stream scan has no reliable page provenance. It is only
            // allowed when a usable PDF page tree could not be recovered at
            // all. When the page tree is authoritative, even a one-page PDF
            // may contain orphan/template page objects or unrelated streams;
            // appending those globally would contaminate documentary evidence.
            $fallback = $this->scan_text_streams($objects, $globalmap, $initialtext);
            if ($fallback !== '') {
                $pages = [1 => $fallback];
                $pagecount = 1;
            }
        }

        $joined = trim(implode("\n", $pages));
        $chars = \core_text::strlen($joined);
        $printable = preg_replace('/[^\pL\pN\pP\pZ\s]/u', '', $joined);
        $ratio = $chars > 0 ? \core_text::strlen((string)$printable) / $chars : 0.0;
        $minimum = max(200, $pagecount * 80);
        $insufficient = $chars < $minimum || $ratio < 0.70;
        $signals = $this->stream_signals($objects);
        $failurecode = '';
        if ($insufficient) {
            if ($signals['textstreams'] > 0) {
                $failurecode = 'unsupported_text_encoding';
            } else if ($signals['images'] > 0) {
                $failurecode = 'image_only';
            } else {
                $failurecode = 'unsupported_pdf_structure';
            }
        }

        return [
            'pages' => $pages,
            'pagecount' => $pagecount,
            'extractedchars' => $chars,
            'pendingocr' => $failurecode === 'image_only',
            'failurecode' => $failurecode,
            'textstreams' => $signals['textstreams'],
            'imageobjects' => $signals['images'],
            'extractor' => 'php_native',
        ];
    }

    /**
     * Reconcile extracted page slots with visible N/TOTAL footer markers.
     *
     * The remap is intentionally conservative. It is applied only when every
     * extracted page exposes one unique marker, all markers declare the same
     * total, and the set covers exactly 1..pagecount. This prevents unrelated
     * fractions in document text from being mistaken for pagination.
     *
     * @param array<int, string> $pages
     * @return array<int, string>
     */
    private function reconcile_visible_page_numbers(array $pages, int $pagecount): array {
        if ($pagecount < 2 || count($pages) !== $pagecount) {
            return $pages;
        }

        $remapped = [];
        foreach ($pages as $text) {
            if (!preg_match_all('/(?:^|[\s])([1-9]\d{0,3})\s*\/\s*([1-9]\d{0,3})(?=[\s]|$)/u',
                    (string)$text, $matches, PREG_SET_ORDER)) {
                return $pages;
            }
            $selected = null;
            foreach (array_reverse($matches) as $match) {
                $number = (int)$match[1];
                $total = (int)$match[2];
                if ($total === $pagecount && $number >= 1 && $number <= $pagecount) {
                    $selected = $number;
                    break;
                }
            }
            if ($selected === null || isset($remapped[$selected])) {
                return $pages;
            }
            $remapped[$selected] = (string)$text;
        }

        if (count($remapped) !== $pagecount) {
            return $pages;
        }
        for ($page = 1; $page <= $pagecount; $page++) {
            if (!array_key_exists($page, $remapped)) {
                return $pages;
            }
        }
        ksort($remapped, SORT_NUMERIC);
        return $remapped;
    }

    /**
     * @param array<int, string> $objects
     * @return array{textstreams: int, images: int}
     */
    private function stream_signals(array $objects): array {
        $textstreams = 0;
        $images = 0;
        foreach ($objects as $body) {
            if (preg_match('/\/Subtype\s*\/Image\b/', $body)) {
                $images++;
                continue;
            }
            $decoded = $this->decode_stream((string)$body);
            if ($decoded !== '' && preg_match('/(?:\bTj\b|\bTJ\b|[\'"]\s*(?:ET|$))/m', $decoded)) {
                $textstreams++;
            }
        }
        return ['textstreams' => $textstreams, 'images' => $images];
    }

    /**
     * @param array<int, string> $objects
     * @param array<string, string> $globalmap
     */
    private function scan_text_streams(array $objects, array $globalmap, string $existingtext): string {
        $parts = [];
        $seen = [];
        foreach ($objects as $body) {
            if (preg_match('/\/Subtype\s*\/Image\b/', $body) || strpos($body, '/ToUnicode') !== false) {
                continue;
            }
            $decoded = $this->decode_stream((string)$body);
            if ($decoded === '' || !preg_match('/(?:\bTj\b|\bTJ\b|[\'"]\s*(?:ET|$))/m', $decoded)) {
                continue;
            }
            $text = $this->decode_content($decoded, [], $globalmap);
            $hash = hash('sha256', $text);
            if ($text === '' || isset($seen[$hash]) || strpos($existingtext, $text) !== false) {
                continue;
            }
            $seen[$hash] = true;
            $parts[] = $text;
        }
        return trim(implode("\n", $parts));
    }

    /**
     * @return array<int, string>
     */
    private function parse_objects(string $pdf): array {
        $objects = [];
        if (!preg_match_all('/(?:^|[\r\n])(\d+)\s+\d+\s+obj\b(.*?)endobj/s', $pdf, $matches, PREG_SET_ORDER)) {
            return $objects;
        }
        foreach (array_slice($matches, 0, self::MAX_OBJECTS) as $match) {
            $objects[(int)$match[1]] = (string)$match[2];
        }
        return $objects;
    }

    /**
     * Expand PDF object streams used by modern PDF 1.5+ producers.
     *
     * Page trees, resource dictionaries and font dictionaries are frequently
     * stored inside /ObjStm containers even when page content streams remain
     * ordinary indirect objects.
     *
     * @param array<int, string> $objects
     * @return array<int, string>
     */
    private function expand_object_streams(array $objects): array {
        $expanded = $objects;
        foreach ($objects as $body) {
            if (count($expanded) >= self::MAX_OBJECTS
                    || !preg_match('/\/Type\s*\/ObjStm\b/', $body)
                    || !preg_match('/\/N\s+(\d+)\b/', $body, $nmatch)
                    || !preg_match('/\/First\s+(\d+)\b/', $body, $firstmatch)) {
                continue;
            }
            $count = (int)$nmatch[1];
            $first = (int)$firstmatch[1];
            if ($count < 1 || $count > self::MAX_OBJECTS || $first < 1) {
                continue;
            }
            $decoded = $this->decode_stream($body);
            if ($decoded === '' || $first >= strlen($decoded)) {
                continue;
            }
            $header = substr($decoded, 0, $first);
            if (!preg_match_all('/(\d+)\s+(\d+)/', $header, $pairs, PREG_SET_ORDER)) {
                continue;
            }
            $pairs = array_slice($pairs, 0, $count);
            $payload = substr($decoded, $first);
            $payloadlength = strlen($payload);
            foreach ($pairs as $index => $pair) {
                $objectid = (int)$pair[1];
                $offset = (int)$pair[2];
                $end = isset($pairs[$index + 1]) ? (int)$pairs[$index + 1][2] : $payloadlength;
                if ($objectid <= 0 || isset($expanded[$objectid])
                        || $offset < 0 || $end <= $offset || $end > $payloadlength) {
                    continue;
                }
                $expanded[$objectid] = trim(substr($payload, $offset, $end - $offset));
                if (count($expanded) >= self::MAX_OBJECTS) {
                    break 2;
                }
            }
        }
        return $expanded;
    }

    /**
     * @param array<int, string> $objects
     * @return array<int, array<string, string>>
     */
    private function build_font_maps(array $objects): array {
        $maps = [];
        foreach ($objects as $objectid => $body) {
            $map = [];
            if (preg_match('/\/ToUnicode\s+(\d+)\s+\d+\s+R/', $body, $match)) {
                $cmapid = (int)$match[1];
                if (isset($objects[$cmapid])) {
                    $map = $this->parse_cmap($this->decode_stream((string)$objects[$cmapid]));
                }
            }

            // Simple PDF fonts are allowed to omit ToUnicode and define their
            // character mapping through /Encoding and /Differences. Earlier
            // VTutor builds ignored that path, which could silently drop short
            // headings rendered with a dedicated font even while body text was
            // extracted correctly.
            $encodingbody = (string)$body;
            if (preg_match('/\/Encoding\s+(\d+)\s+\d+\s+R/', $body, $encodingref)
                    && isset($objects[(int)$encodingref[1]])) {
                $encodingbody .= "\n" . (string)$objects[(int)$encodingref[1]];
            }
            $fallback = $this->parse_simple_font_encoding($encodingbody);
            if ($fallback) {
                // Explicit ToUnicode entries have precedence; encoding maps
                // only fill codes for which the PDF did not provide Unicode.
                $map = $map + $fallback;
            }
            if ($map) {
                $maps[(int)$objectid] = $map;
            }
        }
        return $maps;
    }

    /**
     * Build a byte-code map for simple fonts using BaseEncoding/Differences.
     *
     * @return array<string, string>
     */
    private function parse_simple_font_encoding(string $body): array {
        $map = [];
        $encoding = 'Windows-1252';
        if (preg_match('/\/(?:BaseEncoding|Encoding)\s*\/([A-Za-z0-9_-]+)/', $body, $base)) {
            $name = (string)$base[1];
            if ($name === 'MacRomanEncoding') {
                $encoding = 'MACINTOSH';
            } else if ($name === 'WinAnsiEncoding') {
                $encoding = 'Windows-1252';
            } else if ($name === 'StandardEncoding') {
                $encoding = 'Windows-1252';
            }
        }
        for ($code = 0; $code <= 255; $code++) {
            $char = @iconv($encoding, 'UTF-8//IGNORE', chr($code));
            if ($char !== false && $char !== '' && $code >= 9) {
                $map[strtoupper(str_pad(dechex($code), 2, '0', STR_PAD_LEFT))] = (string)$char;
            }
        }

        if (preg_match('/\/Differences\s*\[(.*?)\]/s', $body, $differences)) {
            preg_match_all('/(?:\d+|\/[A-Za-z0-9_.]+(?:\.[A-Za-z0-9_.]+)?)/', $differences[1], $tokens);
            $code = null;
            foreach ($tokens[0] ?? [] as $token) {
                if (ctype_digit($token)) {
                    $code = (int)$token;
                    continue;
                }
                if ($code === null || $code < 0 || $code > 255 || $token[0] !== '/') {
                    continue;
                }
                $glyph = substr($token, 1);
                $unicode = $this->glyph_name_to_unicode($glyph);
                if ($unicode !== '') {
                    $map[strtoupper(str_pad(dechex($code), 2, '0', STR_PAD_LEFT))] = $unicode;
                }
                $code++;
            }
        }
        return $map;
    }

    private function glyph_name_to_unicode(string $glyph): string {
        if ($glyph === '' || $glyph === '.notdef') {
            return '';
        }
        if (preg_match('/^uni([0-9A-Fa-f]{4})$/', $glyph, $m)
                || preg_match('/^u([0-9A-Fa-f]{4,6})$/', $glyph, $m)) {
            $codepoint = hexdec($m[1]);
            if ($codepoint <= 0x7F) {
                return chr($codepoint);
            }
            if ($codepoint <= 0x7FF) {
                return chr(0xC0 | ($codepoint >> 6)) . chr(0x80 | ($codepoint & 0x3F));
            }
            if ($codepoint <= 0xFFFF) {
                return chr(0xE0 | ($codepoint >> 12))
                    . chr(0x80 | (($codepoint >> 6) & 0x3F))
                    . chr(0x80 | ($codepoint & 0x3F));
            }
            if ($codepoint <= 0x10FFFF) {
                return chr(0xF0 | ($codepoint >> 18))
                    . chr(0x80 | (($codepoint >> 12) & 0x3F))
                    . chr(0x80 | (($codepoint >> 6) & 0x3F))
                    . chr(0x80 | ($codepoint & 0x3F));
            }
            return '';
        }
        if (strlen($glyph) === 1 && preg_match('/[A-Za-z0-9]/', $glyph)) {
            return $glyph;
        }
        $names = [
            'space' => ' ', 'exclam' => '!', 'quotedbl' => '"', 'numbersign' => '#',
            'dollar' => '$', 'percent' => '%', 'ampersand' => '&', 'quotesingle' => "'",
            'parenleft' => '(', 'parenright' => ')', 'asterisk' => '*', 'plus' => '+',
            'comma' => ',', 'hyphen' => '-', 'period' => '.', 'slash' => '/', 'colon' => ':',
            'semicolon' => ';', 'less' => '<', 'equal' => '=', 'greater' => '>', 'question' => '?',
            'at' => '@', 'bracketleft' => '[', 'backslash' => '\\', 'bracketright' => ']',
            'asciicircum' => '^', 'underscore' => '_', 'grave' => '`', 'braceleft' => '{',
            'bar' => '|', 'braceright' => '}', 'asciitilde' => '~', 'endash' => '–',
            'emdash' => '—', 'bullet' => '•', 'ellipsis' => '…', 'questiondown' => '¿',
            'exclamdown' => '¡', 'degree' => '°', 'Aacute' => 'Á', 'aacute' => 'á',
            'Eacute' => 'É', 'eacute' => 'é', 'Iacute' => 'Í', 'iacute' => 'í',
            'Oacute' => 'Ó', 'oacute' => 'ó', 'Uacute' => 'Ú', 'uacute' => 'ú',
            'Udieresis' => 'Ü', 'udieresis' => 'ü', 'Ntilde' => 'Ñ', 'ntilde' => 'ñ',
        ];
        return $names[$glyph] ?? '';
    }

    /**
     * @param array<int, array<string, string>> $fontmaps
     * @return array<string, string>
     */
    private function merge_font_maps(array $fontmaps): array {
        $merged = [];
        foreach ($fontmaps as $map) {
            foreach ($map as $source => $target) {
                if (!isset($merged[$source])) {
                    $merged[$source] = $target;
                }
            }
        }
        return $merged;
    }

    /**
     * @param array<int, string> $objects
     * @return array<int, int>
     */
    private function page_order(array $objects, ?bool &$usedtree = null): array {
        $root = 0;
        foreach ($objects as $body) {
            if (preg_match('/\/Type\s*\/Catalog\b/', $body)
                    && preg_match('/\/Pages\s+(\d+)\s+\d+\s+R/', $body, $match)) {
                $root = (int)$match[1];
                break;
            }
        }
        $ordered = [];
        $visited = [];
        if ($root) {
            $this->walk_page_tree($root, $objects, $visited, $ordered, 0);
        }
        if ($ordered) {
            $usedtree = true;
            return $ordered;
        }
        $usedtree = false;
        foreach ($objects as $objectid => $body) {
            if (preg_match('/\/Type\s*\/Page\b/', $body)) {
                $ordered[] = (int)$objectid;
            }
        }
        return $ordered;
    }

    private function walk_page_tree(
        int $objectid,
        array $objects,
        array &$visited,
        array &$ordered,
        int $depth
    ): void {
        if ($depth > 64 || isset($visited[$objectid]) || !isset($objects[$objectid])) {
            return;
        }
        $visited[$objectid] = true;
        $body = (string)$objects[$objectid];
        if (preg_match('/\/Type\s*\/Page\b/', $body)) {
            $ordered[] = $objectid;
            return;
        }
        if (!preg_match('/\/Kids\s*\[(.*?)\]/s', $body, $kids)) {
            return;
        }
        if (preg_match_all('/(\d+)\s+\d+\s+R/', $kids[1], $refs)) {
            foreach ($refs[1] as $ref) {
                $this->walk_page_tree((int)$ref, $objects, $visited, $ordered, $depth + 1);
            }
        }
    }

    /**
     * Build a resource context for a page, including inherited /Resources
     * dictionaries from its /Pages ancestors.
     *
     * Parent resources are added first and page-local resources last so local
     * names can override inherited names when the same alias is reused.
     *
     * @param array<int, string> $objects
     */
    private function page_resource_context(int $pageid, array $objects): string {
        $chain = [];
        $visited = [];
        $current = $pageid;
        $depth = 0;
        while ($current > 0 && isset($objects[$current]) && !isset($visited[$current]) && $depth < 64) {
            $visited[$current] = true;
            $body = (string)$objects[$current];
            $chain[] = $body;
            if (!preg_match('/\/Parent\s+(\d+)\s+\d+\s+R/', $body, $parent)) {
                break;
            }
            $current = (int)$parent[1];
            $depth++;
        }

        $parts = [];
        foreach (array_reverse($chain) as $body) {
            $parts[] = $body;
            foreach ($this->resource_dictionary_references($body) as $resourceid) {
                if (isset($objects[$resourceid])) {
                    $parts[] = (string)$objects[$resourceid];
                }
            }
        }
        return implode("\n", $parts);
    }

    /**
     * Return referenced /Resources dictionaries from an object body.
     *
     * @return array<int, int>
     */
    private function resource_dictionary_references(string $body): array {
        if (!preg_match_all('/\/Resources\s+(\d+)\s+\d+\s+R/', $body, $matches)) {
            return [];
        }
        return array_values(array_unique(array_map('intval', $matches[1] ?? [])));
    }

    /**
     * Resolve named Form XObjects visible in a resource context.
     *
     * @param array<int, string> $objects
     * @return array<string, int>
     */
    private function xobject_references(string $resourcecontext, array $objects): array {
        $dictionaries = [];
        if (preg_match_all('/\/XObject\s*<<(.*?)>>/s', $resourcecontext, $direct)) {
            foreach ($direct[1] ?? [] as $dictionary) {
                $dictionaries[] = (string)$dictionary;
            }
        }
        if (preg_match_all('/\/XObject\s+(\d+)\s+\d+\s+R/', $resourcecontext, $indirect)) {
            foreach ($indirect[1] ?? [] as $dictionaryid) {
                if (isset($objects[(int)$dictionaryid])) {
                    $dictionaries[] = (string)$objects[(int)$dictionaryid];
                }
            }
        }

        $result = [];
        foreach ($dictionaries as $dictionary) {
            if (!preg_match_all(
                '/\/([A-Za-z0-9_.+\-]+)\s+(\d+)\s+\d+\s+R/',
                $dictionary,
                $refs,
                PREG_SET_ORDER
            )) {
                continue;
            }
            foreach ($refs as $ref) {
                $result[(string)$ref[1]] = (int)$ref[2];
            }
        }
        return $result;
    }

    /**
     * Decode text from Form XObjects actually invoked by a page/form stream.
     *
     * @param array<int, string> $objects
     * @param array<int, array<string, string>> $fontmaps
     * @param array<string, string> $globalmap
     * @param array<int, bool> $visitedforms
     */
    private function decode_invoked_form_xobjects(
        string $content,
        string $resourcecontext,
        array $objects,
        array $fontmaps,
        array $globalmap,
        array &$visitedforms,
        int $depth
    ): string {
        if ($content === '' || $depth > 16) {
            return '';
        }
        if (!preg_match_all('/\/([A-Za-z0-9_.+\-]+)\s+Do\b/', $content, $invocations)) {
            return '';
        }

        $references = $this->xobject_references($resourcecontext, $objects);
        if (!$references) {
            return '';
        }

        $parts = [];
        foreach ($invocations[1] ?? [] as $name) {
            if (!isset($references[$name])) {
                continue;
            }
            $objectid = (int)$references[$name];
            if ($objectid <= 0 || isset($visitedforms[$objectid]) || !isset($objects[$objectid])) {
                continue;
            }
            $formbody = (string)$objects[$objectid];
            if (!preg_match('/\/Subtype\s*\/Form\b/', $formbody)) {
                continue;
            }

            $visitedforms[$objectid] = true;
            $decoded = $this->decode_stream($formbody);
            if ($decoded === '') {
                continue;
            }

            // A form can declare its own resources. Merge them after the page
            // context so form-local aliases take precedence.
            $formcontext = $resourcecontext . "\n" . $formbody;
            foreach ($this->resource_dictionary_references($formbody) as $resourceid) {
                if (isset($objects[$resourceid])) {
                    $formcontext .= "\n" . (string)$objects[$resourceid];
                }
            }
            $formmaps = $this->page_font_maps($formcontext, $objects, $fontmaps);
            $text = $this->decode_content($decoded, $formmaps, $globalmap);
            $nested = $this->decode_invoked_form_xobjects(
                $decoded,
                $formcontext,
                $objects,
                $fontmaps,
                $globalmap,
                $visitedforms,
                $depth + 1
            );
            $combined = trim($text . "\n" . $nested);
            if ($combined !== '') {
                $parts[] = $combined;
            }
        }

        return trim(implode("\n", $parts));
    }

    /**
     * @return array<int, int>
     */
    private function content_references(string $pagebody): array {
        if (!preg_match('/\/Contents\s*(\[(.*?)\]|(\d+)\s+\d+\s+R)/s', $pagebody, $match)) {
            return [];
        }
        $source = (string)($match[2] ?? '');
        if ($source === '' && !empty($match[3])) {
            return [(int)$match[3]];
        }
        preg_match_all('/(\d+)\s+\d+\s+R/', $source, $refs);
        return array_values(array_unique(array_map('intval', $refs[1] ?? [])));
    }

    /**
     * @param array<int, string> $objects
     * @param array<int, array<string, string>> $fontmaps
     * @return array<string, array<string, string>>
     */
    private function page_font_maps(string $pagebody, array $objects, array $fontmaps): array {
        $resources = $pagebody;
        if (preg_match('/\/Resources\s+(\d+)\s+\d+\s+R/', $pagebody, $resource)) {
            $resources .= "\n" . (string)($objects[(int)$resource[1]] ?? '');
        }
        $fontdictionaries = [];
        if (preg_match_all('/\/Font\s*<<(.*?)>>/s', $resources, $directfonts)) {
            $fontdictionaries = $directfonts[1] ?? [];
        }
        if (preg_match_all('/\/Font\s+(\d+)\s+\d+\s+R/', $resources, $indirectfonts)) {
            foreach ($indirectfonts[1] as $fontdictionaryid) {
                if (isset($objects[(int)$fontdictionaryid])) {
                    $fontdictionaries[] = (string)$objects[(int)$fontdictionaryid];
                }
            }
        }
        $result = [];
        foreach ($fontdictionaries as $fontdictionary) {
            if (preg_match_all('/\/([A-Za-z0-9_.+\-]+)\s+(\d+)\s+\d+\s+R/',
                    $fontdictionary, $refs, PREG_SET_ORDER)) {
                foreach ($refs as $ref) {
                    $fontid = (int)$ref[2];
                    if (isset($fontmaps[$fontid])) {
                        $result[(string)$ref[1]] = $fontmaps[$fontid];
                    }
                }
            }
        }
        return $result;
    }

    private function decode_stream(string $object): string {
        if (!preg_match('/^(.*?)stream(?:\r\n|\r|\n)(.*?)(?:\r\n|\r|\n)endstream/s', $object, $match)) {
            return '';
        }
        $dictionary = (string)$match[1];
        $data = (string)$match[2];
        $filters = [];
        if (preg_match('/\/Filter\s*(\[(.*?)\]|\/([A-Za-z0-9]+))/s', $dictionary, $filtermatch)) {
            if (!empty($filtermatch[2])) {
                preg_match_all('/\/([A-Za-z0-9]+)/', (string)$filtermatch[2], $names);
                $filters = $names[1] ?? [];
            } else if (!empty($filtermatch[3])) {
                // For a single filter the capture already excludes the slash.
                // Do not run the array-filter regex again or decompression is
                // silently skipped.
                $filters = [(string)$filtermatch[3]];
            }
        }
        foreach ($filters as $filter) {
            if ($filter === 'FlateDecode' || $filter === 'Fl') {
                $decoded = @gzuncompress($data, self::MAX_DECODED_STREAM);
                if ($decoded === false) {
                    $decoded = @gzinflate($data, self::MAX_DECODED_STREAM);
                }
                if ($decoded === false) {
                    return '';
                }
                $data = (string)$decoded;
            } else if ($filter === 'ASCIIHexDecode' || $filter === 'AHx') {
                $hex = preg_replace('/[^0-9A-Fa-f]/', '', rtrim($data, '>'));
                if (strlen($hex) % 2) {
                    $hex .= '0';
                }
                $decoded = @hex2bin($hex);
                if ($decoded === false) {
                    return '';
                }
                $data = $decoded;
            } else if ($filter === 'ASCII85Decode' || $filter === 'A85') {
                $decoded = $this->ascii85_decode($data);
                if ($decoded === null) {
                    return '';
                }
                $data = $decoded;
            } else {
                return '';
            }
            if (strlen($data) > self::MAX_DECODED_STREAM) {
                return '';
            }
        }
        return $data;
    }

    private function ascii85_decode(string $data): ?string {
        $data = preg_replace('/\s+/', '', $data);
        $data = preg_replace('/^<~|~>$/', '', (string)$data);
        $output = '';
        $group = [];
        foreach (str_split((string)$data) as $character) {
            if ($character === 'z' && !$group) {
                $output .= "\0\0\0\0";
                continue;
            }
            $value = ord($character) - 33;
            if ($value < 0 || $value > 84) {
                return null;
            }
            $group[] = $value;
            if (count($group) === 5) {
                $number = 0;
                foreach ($group as $item) {
                    $number = ($number * 85) + $item;
                }
                $output .= pack('N', $number);
                $group = [];
            }
        }
        if ($group) {
            $count = count($group);
            while (count($group) < 5) {
                $group[] = 84;
            }
            $number = 0;
            foreach ($group as $item) {
                $number = ($number * 85) + $item;
            }
            $output .= substr(pack('N', $number), 0, $count - 1);
        }
        return $output;
    }

    /**
     * @return array<string, string>
     */
    private function parse_cmap(string $cmap): array {
        $map = [];
        if ($cmap === '') {
            return $map;
        }
        if (preg_match_all('/beginbfchar(.*?)endbfchar/s', $cmap, $blocks)) {
            foreach ($blocks[1] as $block) {
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $block, $pairs, PREG_SET_ORDER)) {
                    foreach ($pairs as $pair) {
                        $map[strtoupper($pair[1])] = $this->unicode_from_hex($pair[2]);
                    }
                }
            }
        }
        if (preg_match_all('/beginbfrange(.*?)endbfrange/s', $cmap, $blocks)) {
            foreach ($blocks[1] as $block) {
                foreach (preg_split('/\R/', trim((string)$block)) as $line) {
                    if (preg_match('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>/', $line, $range)) {
                        $start = hexdec($range[1]);
                        $end = hexdec($range[2]);
                        $target = hexdec($range[3]);
                        $width = strlen($range[1]);
                        $targetwidth = strlen($range[3]);
                        if ($end - $start > 4096 || $width > 8 || $targetwidth > 8) {
                            continue;
                        }
                        for ($value = $start; $value <= $end; $value++) {
                            $sourcehex = strtoupper(str_pad(dechex($value), $width, '0', STR_PAD_LEFT));
                            $targethex = str_pad(dechex($target + ($value - $start)), $targetwidth, '0', STR_PAD_LEFT);
                            $map[$sourcehex] = $this->unicode_from_hex($targethex);
                        }
                    } else if (preg_match('/<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*\[(.*?)\]/', $line, $range)
                            && preg_match_all('/<([0-9A-Fa-f]+)>/', $range[3], $targets)) {
                        $start = hexdec($range[1]);
                        $width = strlen($range[1]);
                        foreach ($targets[1] as $offset => $targethex) {
                            $sourcehex = strtoupper(str_pad(dechex($start + $offset), $width, '0', STR_PAD_LEFT));
                            $map[$sourcehex] = $this->unicode_from_hex($targethex);
                        }
                    }
                }
            }
        }
        return array_filter($map, static function($value) {
            return $value !== '';
        });
    }

    private function unicode_from_hex(string $hex): string {
        $binary = @hex2bin(strlen($hex) % 2 ? '0' . $hex : $hex);
        if ($binary === false || $binary === '') {
            return '';
        }
        if (strlen($binary) % 2 === 0) {
            if (function_exists('mb_convert_encoding')) {
                return (string)@mb_convert_encoding($binary, 'UTF-8', 'UTF-16BE');
            }
            if (function_exists('iconv')) {
                $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', $binary);
                if ($converted !== false) {
                    return (string)$converted;
                }
            }
        }
        return $binary;
    }

    /**
     * @param array<string, array<string, string>> $fontmaps
     * @param array<string, string> $globalmap
     */
    private function decode_content(string $content, array $fontmaps, array $globalmap): string {
        if ($content === '') {
            return '';
        }
        $pattern = '/(\/[A-Za-z0-9_.+\-]+\s+[-+]?\d*\.?\d+\s+Tf)'
            . '|((?:\((?:\\\\.|[^\\\\()])*\)|<[0-9A-Fa-f\s]+>)\s*Tj)'
            . '|(\[(?:\\\\.|[^\]])*\]\s*TJ)'
            . '|((?:\((?:\\\\.|[^\\\\()])*\)|<[0-9A-Fa-f\s]+>)\s*[\'"])'
            . '|([-+]?\d*\.?\d+\s+[-+]?\d*\.?\d+\s+(?:Td|TD))'
            . '|([-+]?\d*\.?\d+(?:\s+[-+]?\d*\.?\d+){5}\s+Tm)'
            . '|(T\*)/s';
        if (!preg_match_all($pattern, $content, $tokens, PREG_SET_ORDER)) {
            return '';
        }
        $font = '';
        $out = '';
        foreach ($tokens as $token) {
            $raw = (string)$token[0];
            if (preg_match('/^\/([A-Za-z0-9_.+\-]+)\s+[-+]?\d*\.?\d+\s+Tf$/', trim($raw), $fontmatch)) {
                $font = (string)$fontmatch[1];
                continue;
            }
            if (preg_match('/^(\((?:\\\\.|[^\\\\()])*\)|<[0-9A-Fa-f\s]+>)\s*Tj$/s', trim($raw), $textmatch)) {
                $out .= $this->decode_pdf_string($textmatch[1], $font !== '' ? ($fontmaps[$font] ?? []) : $globalmap) . ' ';
                continue;
            }
            if (preg_match('/^\[(.*)\]\s*TJ$/s', trim($raw), $arraymatch)) {
                if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)|<[0-9A-Fa-f\s]+>/', $arraymatch[1], $strings)) {
                    foreach ($strings[0] as $string) {
                        $out .= $this->decode_pdf_string($string, $font !== '' ? ($fontmaps[$font] ?? []) : $globalmap);
                    }
                    $out .= ' ';
                }
                continue;
            }
            if (preg_match('/^(\((?:\\\\.|[^\\\\()])*\)|<[0-9A-Fa-f\s]+>)\s*([\'"])$/s', trim($raw), $quotematch)) {
                $out .= "\n" . $this->decode_pdf_string($quotematch[1], $font !== '' ? ($fontmaps[$font] ?? []) : $globalmap) . ' ';
                continue;
            }
            if ($raw === 'T*') {
                $out .= "\n";
                continue;
            }
            if (preg_match('/([-+]?\d*\.?\d+)\s+(?:Td|TD)$/', trim($raw), $move) && abs((float)$move[1]) > 0.01) {
                $out .= "\n";
                continue;
            }
            if (substr(trim($raw), -2) === 'Tm') {
                $out .= "\n";
            }
        }
        // Accessibility-aware PDFs can provide authoritative text in
        // /ActualText marked-content properties. Preserve it when it is not
        // already represented by normal text-showing operators.
        if (preg_match_all('/\/ActualText\s*(\((?:\\.|[^\\()])*\)|<[0-9A-Fa-f\s]+>)/s', $content, $actualmatches)) {
            foreach ($actualmatches[1] ?? [] as $actualtoken) {
                $actual = trim($this->decode_pdf_string((string)$actualtoken, []));
                if ($actual !== '' && stripos($out, $actual) === false) {
                    $out .= "\n" . $actual . "\n";
                }
            }
        }
        return $this->normalize($out);
    }

    /**
     * @param array<string, string> $map
     */
    private function decode_pdf_string(string $token, array $map): string {
        if ($token !== '' && $token[0] === '<') {
            $hex = strtoupper((string)preg_replace('/[^0-9A-Fa-f]/', '', $token));
            $mapped = $this->decode_hex_with_map($hex, $map);
            if ($mapped !== '') {
                return $mapped;
            }
            $binary = @hex2bin(strlen($hex) % 2 ? $hex . '0' : $hex);
            return $this->convert_bytes((string)$binary);
        }

        $literal = substr($token, 1, -1);
        $literal = preg_replace_callback('/\\\\([0-7]{1,3})/', static function($match) {
            return chr(octdec($match[1]));
        }, $literal);
        $literal = str_replace(
            ['\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)', '\\\\'],
            ["\n", "\r", "\t", "\x08", "\f", '(', ')', '\\'],
            (string)$literal
        );
        $literal = preg_replace("/\\\\\r?\n/", '', (string)$literal);

        // Apply the selected font map to literal strings too. PDF literal Tj
        // strings carry encoded bytes, not necessarily Unicode text.
        if ($map) {
            $mapped = $this->decode_hex_with_map(strtoupper(bin2hex((string)$literal)), $map);
            if ($mapped !== '') {
                return $mapped;
            }
        }
        return $this->convert_bytes((string)$literal);
    }

    /**
     * Decode a hexadecimal byte/CID sequence with the longest keys first.
     */
    private function decode_hex_with_map(string $hex, array $map): string {
        if ($hex === '' || !$map) {
            return '';
        }
        $lengths = array_values(array_unique(array_map('strlen', array_keys($map))));
        rsort($lengths, SORT_NUMERIC);
        $out = '';
        $matchedcount = 0;
        for ($position = 0; $position < strlen($hex);) {
            $matched = false;
            foreach ($lengths as $length) {
                $piece = substr($hex, $position, $length);
                if (strlen($piece) === $length && isset($map[$piece])) {
                    $out .= $map[$piece];
                    $position += $length;
                    $matched = true;
                    $matchedcount++;
                    break;
                }
            }
            if (!$matched) {
                // Simple encodings are byte oriented. Preserve an unmapped
                // byte rather than silently dropping it; mixed fonts are
                // common in headings and legal documents.
                $bytehex = substr($hex, $position, 2);
                if (strlen($bytehex) === 2) {
                    $out .= $this->convert_bytes((string)@hex2bin($bytehex));
                    $position += 2;
                } else {
                    break;
                }
            }
        }
        return $matchedcount > 0 ? $out : '';
    }

    private function convert_bytes(string $bytes): string {
        if ($bytes === '') {
            return '';
        }
        if (substr($bytes, 0, 2) === "\xFE\xFF") {
            $payload = substr($bytes, 2);
            if (function_exists('mb_convert_encoding')) {
                return (string)@mb_convert_encoding($payload, 'UTF-8', 'UTF-16BE');
            }
            if (function_exists('iconv')) {
                $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', $payload);
                if ($converted !== false) {
                    return (string)$converted;
                }
            }
        }
        if (strlen($bytes) % 2 === 0 && strpos($bytes, "\0") !== false) {
            if (function_exists('mb_convert_encoding')) {
                return (string)@mb_convert_encoding($bytes, 'UTF-8', 'UTF-16BE');
            }
            if (function_exists('iconv')) {
                $converted = @iconv('UTF-16BE', 'UTF-8//IGNORE', $bytes);
                if ($converted !== false && $converted !== '') {
                    return (string)$converted;
                }
            }
        }
        if (preg_match('//u', $bytes)) {
            return $bytes;
        }
        if (function_exists('mb_convert_encoding')) {
            return (string)@mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
        }
        if (function_exists('iconv')) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $bytes);
            if ($converted !== false) {
                return (string)$converted;
            }
        }
        return $bytes;
    }

    private function normalize(string $text): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0E-\x1F]+/u', ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', (string)$text);
        $text = preg_replace('/ *\n */u', "\n", (string)$text);
        $text = preg_replace('/\n{3,}/u', "\n\n", (string)$text);
        return trim((string)$text);
    }
}
