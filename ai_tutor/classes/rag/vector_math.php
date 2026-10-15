<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\rag;

defined('MOODLE_INTERNAL') || die();

class vector_math {
    public static function cosine_similarity(array $left, array $right): float {
        if (!$left || count($left) !== count($right)) {
            return 0.0;
        }
        $dot = 0.0;
        $leftnorm = 0.0;
        $rightnorm = 0.0;
        foreach ($left as $index => $value) {
            $a = (float)$value;
            $b = (float)$right[$index];
            $dot += $a * $b;
            $leftnorm += $a * $a;
            $rightnorm += $b * $b;
        }
        if ($leftnorm <= 0.0 || $rightnorm <= 0.0) {
            return 0.0;
        }
        return $dot / (sqrt($leftnorm) * sqrt($rightnorm));
    }

    /**
     * Cosine similarity for sparse vectors keyed by a hashing dimension.
     */
    public static function sparse_cosine_similarity(array $left, array $right): float {
        if (!$left || !$right) {
            return 0.0;
        }
        if (count($left) > count($right)) {
            [$left, $right] = [$right, $left];
        }
        $dot = 0.0;
        $leftnorm = 0.0;
        $rightnorm = 0.0;
        foreach ($left as $index => $value) {
            if (!is_numeric($value)) {
                return 0.0;
            }
            $number = (float)$value;
            $leftnorm += $number * $number;
            if (isset($right[$index]) && is_numeric($right[$index])) {
                $dot += $number * (float)$right[$index];
            }
        }
        foreach ($right as $value) {
            if (!is_numeric($value)) {
                return 0.0;
            }
            $number = (float)$value;
            $rightnorm += $number * $number;
        }
        if ($leftnorm <= 0.0 || $rightnorm <= 0.0) {
            return 0.0;
        }
        return $dot / (sqrt($leftnorm) * sqrt($rightnorm));
    }
}
