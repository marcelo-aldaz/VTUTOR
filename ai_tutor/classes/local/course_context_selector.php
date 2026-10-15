<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\local;

defined('MOODLE_INTERNAL') || die();

/** Local, deterministic selector for visible Moodle text snippets. */
final class course_context_selector {
    /** @return array<int,string> */
    public static function keywords(string $question): array {
        $text = \core_text::strtolower($question);
        $text = strtr($text, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u',
            'ñ'=>'n',
        ]);
        preg_match_all('/[\p{L}\p{N}]{3,}/u', $text, $m);
        $stop = array_flip([
            'que','como','cual','cuales','para','por','con','sin','una','uno','unos','unas','del','las','los','este','esta','estos','estas',
            'curso','tema','unidad','actividad','explica','explicame','dime','sobre','segun','puede','puedes','quiero','necesito','hay','tiene','tienen'
        ]);
        $out = [];
        foreach ($m[0] ?? [] as $token) {
            if (!isset($stop[$token]) && !isset($out[$token])) {
                $out[$token] = $token;
            }
            if (count($out) >= 16) {
                break;
            }
        }
        return array_values($out);
    }

    public static function score(string $question, string $candidate): float {
        $tokens = self::keywords($question);
        if (!$tokens || trim($candidate) === '') {
            return 0.0;
        }
        $haystack = \core_text::strtolower($candidate);
        $haystack = strtr($haystack, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        $hits = 0;
        foreach ($tokens as $token) {
            if (strpos($haystack, $token) !== false) {
                $hits++;
            }
        }
        return $hits / max(1, count($tokens));
    }

    /**
     * @param array<int,array{label:string,text:string,priority?:float}> $items
     * @return array<int,array{label:string,text:string,priority?:float,score:float}>
     */
    public static function select(string $question, array $items, int $limit = 6): array {
        $ranked = [];
        foreach ($items as $item) {
            $score = self::score($question, ($item['label'] ?? '') . ' ' . ($item['text'] ?? ''));
            $score += (float)($item['priority'] ?? 0.0);
            if ($score > 0.0) {
                $item['score'] = $score;
                $ranked[] = $item;
            }
        }
        usort($ranked, static function(array $a, array $b): int {
            return ($b['score'] <=> $a['score']);
        });
        return array_slice($ranked, 0, max(0, $limit));
    }
}
