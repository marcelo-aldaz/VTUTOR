<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Read plugin configuration without confusing Moodle's `false` sentinel for
 * a legitimate zero/disabled value.
 */
final class config {
    public static function bool(string $key, bool $default): bool {
        $value = get_config('block_ai_tutor', $key);
        return $value === false ? $default : (int)$value === 1;
    }

    public static function int(string $key, int $default): int {
        $value = get_config('block_ai_tutor', $key);
        return $value === false ? $default : (int)$value;
    }

    public static function float(string $key, float $default): float {
        $value = get_config('block_ai_tutor', $key);
        return $value === false ? $default : (float)$value;
    }

    public static function string(string $key, string $default = ''): string {
        $value = get_config('block_ai_tutor', $key);
        return $value === false ? $default : (string)$value;
    }
}
