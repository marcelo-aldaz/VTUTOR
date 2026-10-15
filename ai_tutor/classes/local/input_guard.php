<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Central validation for administrator-controlled presentation values.
 */
final class input_guard {
    /**
     * Accept conservative CSS colors only.
     *
     * @param string $value
     * @param string $fallback
     * @return string
     */
    public static function css_color(string $value, string $fallback): string {
        $value = trim($value);
        if (preg_match('/^#[0-9a-f]{3,8}$/i', $value)) {
            $length = strlen($value);
            if (in_array($length, [4, 5, 7, 9], true)) {
                return strtolower($value);
            }
        }
        if (preg_match('/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0(?:\.\d+)?|1(?:\.0+)?|\.\d+))?\s*\)$/i', $value, $matches)) {
            $rgb = [(int)$matches[1], (int)$matches[2], (int)$matches[3]];
            if (max($rgb) <= 255) {
                if (!isset($matches[4]) || $matches[4] === '') {
                    return sprintf('rgb(%d,%d,%d)', ...$rgb);
                }
                $alpha = (float)$matches[4];
                if ($alpha >= 0.0 && $alpha <= 1.0) {
                    $alphatext = rtrim(rtrim(number_format($alpha, 3, '.', ''), '0'), '.');
                    if ($alphatext === '') {
                        $alphatext = '0';
                    }
                    return sprintf('rgba(%d,%d,%d,%s)', $rgb[0], $rgb[1], $rgb[2], $alphatext);
                }
            }
        }
        return $fallback;
    }

    /**
     * Accept only HTTPS external image URLs. Empty is allowed.
     *
     * @param string $value
     * @return string
     */
    public static function https_url(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $clean = clean_param($value, PARAM_URL);
        if ($clean === '' || preg_match('#^https://#i', $clean) !== 1) {
            return '';
        }
        return $clean;
    }
}
