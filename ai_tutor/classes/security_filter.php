<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

class security_filter {
    public static function enabled(): bool {
        return \block_ai_tutor\local\config::bool('security_filter_enabled', true);
    }

    public static function clamp_history_limit(int $limit): int {
        $max = (int)(get_config('block_ai_tutor', 'security_history_max_limit') ?: 20);
        if ($max < 1) { $max = 20; }
        if ($max > 200) { $max = 200; }
        if ($limit < 1) { $limit = 1; }
        return min($limit, $max);
    }

    public static function filter_input(string $message): array {
        $msg = trim($message);
        if ($msg === '') {
            return ['ok' => false, 'message' => '', 'notice' => '⚠️ El mensaje está vacío.'];
        }
        if (!self::enabled()) {
            return ['ok' => true, 'message' => $msg, 'notice' => ''];
        }
        $max = (int)(get_config('block_ai_tutor', 'security_max_input_chars') ?: 1500);
        if ($max < 50) { $max = 50; }
        if (\core_text::strlen($msg) > $max) {
            return ['ok' => false, 'message' => '', 'notice' => '⚠️ Tu mensaje es demasiado largo. Máximo permitido: ' . $max . ' caracteres.'];
        }
        $patterns = (string)(get_config('block_ai_tutor', 'security_block_patterns') ?: '');
        $hits = self::matches_block_pattern($msg, $patterns);
        if (!empty($hits)) {
            return ['ok' => false, 'message' => '', 'notice' => '⚠️ El mensaje fue bloqueado por la política de seguridad del administrador.'];
        }
        if (\block_ai_tutor\local\config::bool('security_mask_sensitive', true)) {
            $msg = self::mask_sensitive_data($msg);
        }
        return ['ok' => true, 'message' => $msg, 'notice' => ''];
    }

    public static function filter_output(string $response): string {
        $text = trim($response);
        if ($text === '') {
            return $text;
        }
        if (\block_ai_tutor\local\config::bool('security_mask_sensitive', true)) {
            $text = self::mask_sensitive_data($text);
        }
        if (!self::enabled()) {
            return $text;
        }
        $max = (int)(get_config('block_ai_tutor', 'security_max_output_chars') ?: 8000);
        if ($max < 100) { $max = 100; }
        if (\core_text::strlen($text) > $max) {
            $text = self::truncate_text($text, $max, false)
                . "\n\n[Respuesta recortada por la política de longitud configurada por el administrador]";
        }
        return $text;
    }

    public static function prepare_history_record(string $message, string $response): ?array {
        $mode = (string)(get_config('block_ai_tutor', 'security_store_mode') ?: 'full');
        $truncate = (int)(get_config('block_ai_tutor', 'security_store_truncate_chars') ?: 4000);
        if ($truncate < 50) { $truncate = 50; }

        switch ($mode) {
            case 'none':
                return null;
            case 'metadata':
                return ['message' => '[No almacenado por política de privacidad]', 'response' => '[No almacenado por política de privacidad]'];
            case 'truncate':
                return [
                    'message' => self::truncate_text($message, $truncate),
                    'response' => self::truncate_text($response, $truncate),
                ];
            case 'full':
            default:
                return ['message' => $message, 'response' => $response];
        }
    }

    public static function sanitize_error_message(string $msg): string {
        // Never allow the configured provider secret to leak through an HTTP,
        // transport or provider error returned to logs/administrators.
        $apikey = trim((string)(get_config('block_ai_tutor', 'api_key') ?: ''));
        if ($apikey !== '') {
            $msg = str_replace($apikey, '[api-key-oculta]', $msg);
        }
        $msg = (string)preg_replace('/\bBearer\s+[A-Za-z0-9._~+\/-]{12,}/i', 'Bearer [token-oculto]', $msg);
        $msg = (string)preg_replace('/\b(sk|key|token)[-_]?[A-Za-z0-9._-]{12,}\b/i', '[secreto-oculto]', $msg);
        $msg = self::mask_sensitive_data($msg);
        return self::truncate_text($msg, 180);
    }

    private static function truncate_text(string $text, int $max, bool $ellipsis = true): string {
        $text = trim($text);
        if (\core_text::strlen($text) <= $max) {
            return $text;
        }
        $cut = rtrim(\core_text::substr($text, 0, $max));
        // Prefer the final sentence boundary close to the configured limit.
        $windowstart = max(0, \core_text::strlen($cut) - 500);
        $window = \core_text::substr($cut, $windowstart);
        if (preg_match_all('/[.!?](?=\s|$)/u', $window, $matches, PREG_OFFSET_CAPTURE) && !empty($matches[0])) {
            $last = end($matches[0]);
            $byteposition = (int)$last[1] + strlen((string)$last[0]);
            $prefix = substr($window, 0, $byteposition);
            $cut = rtrim(\core_text::substr($cut, 0, $windowstart) . $prefix);
        } else {
            $cut = (string)preg_replace('/\s+\S*$/u', '', $cut);
        }
        return $cut . ($ellipsis ? '…' : '');
    }

    private static function matches_block_pattern(string $text, string $patterns): array {
        $hits = [];
        $lines = preg_split('/\R+/', $patterns, -1, PREG_SPLIT_NO_EMPTY);
        $haystack = \core_text::strtolower($text);
        foreach ($lines as $line) {
            $rule = trim($line);
            if ($rule === '' || strpos($rule, '#') === 0) { continue; }
            $needle = \core_text::strtolower($rule);
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                $hits[] = $rule;
            }
        }
        return $hits;
    }

    public static function mask_sensitive_data(string $text): string {
        // Emails.
        $text = (string)preg_replace('/([A-Z0-9._%+-]+)@([A-Z0-9.-]+\.[A-Z]{2,})/iu', '[correo-oculto]', $text);

        // International/clearly formatted telephone numbers. Do not treat
        // arbitrary hyphenated digit groups as phones: legal identifiers such
        // as MINEDUC-2025-00010-A must remain byte-for-byte intact so document
        // targeting and documentary fidelity are not corrupted by the filter.
        $text = (string)preg_replace(
            '/(?<![\pL\pN_\-])\+\d{1,3}(?:[ .()\-]*\d){7,12}(?![\pL\pN_\-])/u',
            '[telefono-oculto]',
            $text
        );
        $text = (string)preg_replace(
            '/(?<![\pL\pN_\-])(?:\(\d{2,4}\)[ .-]*|\d{2,4}[ .])\d{3,4}[ .-]\d{3,4}(?![\pL\pN_\-])/u',
            '[telefono-oculto]',
            $text
        );

        // Probable national ID / mobile number written as one uninterrupted
        // numeric token. Embedded codes and file names are intentionally
        // excluded by the alphanumeric/hyphen boundaries.
        $text = (string)preg_replace(
            '/(?<![\pL\pN_\-])\d{10,13}(?![\pL\pN_\-])/u',
            '[id-oculto]',
            $text
        );
        return $text;
    }
}
