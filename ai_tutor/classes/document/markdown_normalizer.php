<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\document;

defined('MOODLE_INTERNAL') || die();

/**
 * Conservative page-aware Markdown normalizer.
 */
class markdown_normalizer {
    /**
     * @param array<int, string> $pages
     * @param string $title
     * @return string
     */
    public function from_pages(array $pages, string $title = ''): string {
        $pages = $this->remove_repeated_edges($pages);
        $parts = [];
        if (trim($title) !== '') {
            $parts[] = '# ' . $this->single_line($title);
        }
        foreach ($pages as $number => $text) {
            $parts[] = '<!-- page: ' . (int)$number . ' -->';
            $parts[] = $this->normalize_page((string)$text);
        }
        return trim(implode("\n\n", array_filter($parts, static function($part) {
            return trim((string)$part) !== '';
        }))) . "\n";
    }

    /**
     * Remove a first or last non-empty line repeated on most pages.
     *
     * @param array<int, string> $pages
     * @return array<int, string>
     */
    private function remove_repeated_edges(array $pages): array {
        if (count($pages) < 3) {
            return $pages;
        }
        $firsts = [];
        $lasts = [];
        foreach ($pages as $page => $text) {
            $lines = preg_split('/\n/u', trim((string)$text), -1, PREG_SPLIT_NO_EMPTY);
            if ($lines) {
                $firsts[$page] = trim((string)reset($lines));
                $lasts[$page] = trim((string)end($lines));
            }
        }
        $threshold = (int)ceil(count($pages) * 0.60);
        $removefirst = $this->repeated_value($firsts, $threshold);
        $removelast = $this->repeated_value($lasts, $threshold);
        foreach ($pages as $page => $text) {
            $lines = preg_split('/\n/u', (string)$text);
            if ($removefirst !== '' && isset($firsts[$page]) && $firsts[$page] === $removefirst) {
                $this->remove_line($lines, $removefirst, false);
            }
            if ($removelast !== '' && isset($lasts[$page]) && $lasts[$page] === $removelast) {
                $this->remove_line($lines, $removelast, true);
            }
            $pages[$page] = trim(implode("\n", $lines));
        }
        return $pages;
    }

    private function repeated_value(array $values, int $threshold): string {
        $counts = array_count_values(array_filter($values, static function($value) {
            $length = \core_text::strlen((string)$value);
            return $length >= 3 && $length <= 160;
        }));
        arsort($counts);
        $value = (string)(array_key_first($counts) ?? '');
        return ($value !== '' && (int)$counts[$value] >= $threshold) ? $value : '';
    }

    private function remove_line(array &$lines, string $needle, bool $reverse): void {
        $keys = array_keys($lines);
        if ($reverse) {
            $keys = array_reverse($keys);
        }
        foreach ($keys as $key) {
            if (trim((string)$lines[$key]) === $needle) {
                unset($lines[$key]);
                return;
            }
            if (trim((string)$lines[$key]) !== '') {
                return;
            }
        }
    }

    private function normalize_page(string $text): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $lines = preg_split('/\n/u', (string)$text);
        $out = [];
        $paragraph = [];
        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                $this->flush_paragraph($paragraph, $out);
                continue;
            }
            if ($this->is_heading($line)) {
                $this->flush_paragraph($paragraph, $out);
                $out[] = '## ' . preg_replace('/^#+\s*/u', '', $line);
                continue;
            }
            if (preg_match('/^(?:[-*•]|\d+[.)])\s+/u', $line)) {
                $this->flush_paragraph($paragraph, $out);
                $out[] = preg_replace('/^•\s+/u', '- ', $line);
                continue;
            }
            $paragraph[] = $line;
        }
        $this->flush_paragraph($paragraph, $out);
        return trim(implode("\n\n", $out));
    }

    private function flush_paragraph(array &$paragraph, array &$out): void {
        if (!$paragraph) {
            return;
        }
        $out[] = trim(implode(' ', $paragraph));
        $paragraph = [];
    }

    private function is_heading(string $line): bool {
        $length = \core_text::strlen($line);
        if ($length < 3 || $length > 120) {
            return false;
        }
        // Legal/normative structural markers are meaningful evidence even
        // when the producer writes them with a trailing colon. Preserve them
        // as headings instead of folding or discarding their literal text.
        if (preg_match('/^(?:ACUERDA|CONSIDERANDO|DISPOSICIONES\s+(?:GENERALES|FINALES|TRANSITORIAS)|COMUN[IÍ]QUESE[, ]+PUBL[IÍ]QUESE(?:\s+Y\s+C[UÚ]MPLASE)?)\s*:?$/iu', $line)) {
            return true;
        }
        if (preg_match('/[.!?;:]$/u', $line)) {
            return false;
        }
        if (preg_match('/^#{1,6}\s+/u', $line)
            || preg_match('/^(unidad|tema|cap[ií]tulo|m[oó]dulo|secci[oó]n|objetivo|introducci[oó]n|conclusiones?)\b/iu', $line)) {
            return true;
        }
        $letters = preg_replace('/[^\pL]/u', '', $line);
        if ($letters === '') {
            return false;
        }
        $upper = preg_replace('/[^\p{Lu}]/u', '', $letters);
        return \core_text::strlen((string)$upper) / \core_text::strlen($letters) >= 0.75;
    }

    private function single_line(string $text): string {
        return trim((string)preg_replace('/\s+/u', ' ', $text));
    }
}
