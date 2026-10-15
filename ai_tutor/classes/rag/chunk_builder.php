<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\rag;

defined('MOODLE_INTERNAL') || die();

/**
 * Build page-aware chunks from reviewed Markdown.
 */
class chunk_builder {
    /** @var int */
    private $target;
    /** @var int */
    private $maximum;
    /** @var int */
    private $overlap;
    /** @var int */
    private $minimum;

    public function __construct(?int $target = null, ?int $maximum = null, ?int $overlap = null, ?int $minimum = null) {
        $this->target = max(800, $target ?? (int)(get_config('block_ai_tutor', 'rag_chunk_target') ?: 2400));
        $this->maximum = max($this->target, $maximum ?? (int)(get_config('block_ai_tutor', 'rag_chunk_max') ?: 3600));
        $configuredoverlap = get_config('block_ai_tutor', 'rag_chunk_overlap');
        $effectiveoverlap = $overlap ?? ($configuredoverlap === false ? 350 : (int)$configuredoverlap);
        $this->overlap = max(0, min(600, $effectiveoverlap));
        $this->minimum = max(100, $minimum ?? (int)(get_config('block_ai_tutor', 'rag_chunk_min') ?: 400));
    }

    /**
     * @param string $markdown
     * @return array<int, array>
     */
    public function build(string $markdown): array {
        $units = $this->parse_units($markdown);
        $chunks = [];
        $current = null;
        foreach ($units as $unit) {
            foreach ($this->split_long_unit($unit) as $piece) {
                if ($current === null) {
                    $current = $piece;
                    continue;
                }
                $candidate = trim($current['content'] . "\n\n" . $piece['content']);
                $sameheading = $current['headingpath'] === $piece['headingpath'];
                $samepage = (int)$current['page_start'] === (int)$current['page_end']
                    && (int)$piece['page_start'] === (int)$piece['page_end']
                    && (int)$current['page_start'] === (int)$piece['page_start'];
                // Page provenance is stronger than target chunk size. Never
                // merge units from different pages; otherwise a legal/article
                // hit can inherit the first page of a multi-page chunk and
                // produce false source attribution.
                if ($samepage && (\core_text::strlen($candidate) <= $this->target
                        || ($sameheading && \core_text::strlen($candidate) <= $this->maximum))) {
                    $current['content'] = $candidate;
                } else {
                    $chunks[] = $current;
                    $current = $piece;
                }
            }
        }
        if ($current !== null) {
            $chunks[] = $current;
        }

        if (count($chunks) > 1) {
            $last = end($chunks);
            if (\core_text::strlen($last['content']) < $this->minimum) {
                array_pop($chunks);
                $index = count($chunks) - 1;
                $merged = trim($chunks[$index]['content'] . "\n\n" . $last['content']);
                $samepage = (int)$chunks[$index]['page_start'] === (int)$chunks[$index]['page_end']
                    && (int)$last['page_start'] === (int)$last['page_end']
                    && (int)$chunks[$index]['page_start'] === (int)$last['page_start'];
                if ($samepage && \core_text::strlen($merged) <= $this->maximum) {
                    $chunks[$index]['content'] = $merged;
                } else {
                    $chunks[] = $last;
                }
            }
        }

        $seen = [];
        $result = [];
        foreach ($chunks as $chunk) {
            $content = trim((string)$chunk['content']);
            if ($content === '' || preg_match('/advertencia:\s*no se pudo extraer/iu', $content)) {
                continue;
            }
            $hash = hash('sha256', $content);
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;
            $chunk['chunk_index'] = count($result);
            $chunk['contenthash'] = $hash;
            $result[] = $chunk;
        }
        return $result;
    }

    private function parse_units(string $markdown): array {
        $page = 1;
        $headings = [];
        $buffer = [];
        $units = [];
        $flush = static function() use (&$buffer, &$units, &$page, &$headings): void {
            $content = trim(implode("\n", $buffer));
            if ($content !== '') {
                $units[] = [
                    'content' => $content,
                    'page_start' => $page,
                    'page_end' => $page,
                    'headingpath' => implode(' > ', array_values($headings)),
                ];
            }
            $buffer = [];
        };
        foreach (preg_split('/\n/u', $markdown) as $line) {
            if (preg_match('/^\s*<!--\s*page:\s*(\d+)\s*-->\s*$/u', $line, $match)) {
                $flush();
                $page = (int)$match[1];
                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.+)$/u', trim((string)$line), $match)) {
                $flush();
                $level = strlen($match[1]);
                foreach (array_keys($headings) as $key) {
                    if ($key >= $level) {
                        unset($headings[$key]);
                    }
                }
                $headingtext = trim($match[2]);
                $headings[$level] = $headingtext;
                // Heading text is evidence, not only metadata. Keeping it in
                // the chunk body allows literal queries such as "ACUERDA"
                // or "DISPOSICIONES GENERALES" to be quoted faithfully.
                $buffer[] = $headingtext;
                continue;
            }
            if (trim((string)$line) === '') {
                $flush();
            } else {
                $buffer[] = trim((string)$line);
            }
        }
        $flush();
        return $units;
    }

    private function split_long_unit(array $unit): array {
        if (\core_text::strlen($unit['content']) <= $this->maximum) {
            return [$unit];
        }
        $pieces = [];
        $text = $unit['content'];
        $length = \core_text::strlen($text);
        $position = 0;
        while ($position < $length) {
            $piece = \core_text::substr($text, $position, $this->maximum);
            if ($position + $this->maximum < $length) {
                $cut = max((int)mb_strrpos($piece, '. '), (int)mb_strrpos($piece, "\n"));
                if ($cut > $this->minimum) {
                    $piece = \core_text::substr($piece, 0, $cut + 1);
                }
            }
            $piece = trim($piece);
            if ($piece !== '') {
                $copy = $unit;
                $copy['content'] = $piece;
                $pieces[] = $copy;
            }
            $step = max(100, \core_text::strlen($piece) - $this->overlap);
            $position += $step;
        }
        return $pieces;
    }
}
