<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Deterministic query routing for VTutor.
 *
 * Keeps social turns out of RAG, preserves strict documentary behaviour only
 * when the user explicitly asks for document evidence, and lets ordinary
 * learning questions fall back to visible Moodle course context.
 */
final class query_intent {
    public const CONVERSATIONAL = 'conversational';
    public const DOCUMENTARY = 'documentary';
    public const LEARNING = 'learning';

    /** @return string One of the class constants. */
    public static function classify(string $message): string {
        $text = self::normalize($message);
        if (self::is_conversational($text)) {
            return self::CONVERSATIONAL;
        }
        if (self::requires_document_evidence($text)) {
            return self::DOCUMENTARY;
        }
        return self::LEARNING;
    }


    /** True only when an explicit documentary request lacks required evidence. */
    public static function should_fail_closed(string $message, bool $strict, bool $hasevidence): bool {
        return $strict && !$hasevidence && self::classify($message) === self::DOCUMENTARY;
    }

    /**
     * Only pure social turns are conversational. "Hola, explícame X" must
     * remain a learning query.
     */
    public static function is_conversational(string $message): bool {
        $text = self::normalize($message);
        if ($text === '') {
            return true;
        }
        $text = trim($text, " \t\n\r\0\x0B.!?¿¡,;:");
        return (bool)preg_match(
            '/^(?:hola(?:\s+(?:vtutor|tutor))?|buenos\s+dias|buen\s+dia|buenas\s+tardes|buenas\s+noches|'
                . 'que\s+tal|como\s+estas|gracias|muchas\s+gracias|mil\s+gracias|ok|okay|listo|lista|'
                . 'perfecto|de\s+acuerdo|entendido|entendida|hasta\s+luego|adios)$/u',
            $text
        );
    }

    /**
     * Return true only when failure to retrieve document evidence must stop the
     * answer. This protects explicit "según el documento" requests without
     * making strict RAG a global conversational gate.
     */
    public static function requires_document_evidence(string $message): bool {
        $text = self::normalize($message);
        if ($text === '') {
            return false;
        }

        // Explicit file reference is a hard documentary scope.
        if (preg_match('/\b[^\s<>"\']+\.(?:pdf|docx?|pptx?|xlsx?|txt|md|odt|ods|odp)\b/u', $text)) {
            return true;
        }

        // Strong evidence/provenance wording.
        if (preg_match('/\b(?:segun|conforme\s+a|de\s+acuerdo\s+con)\s+(?:el|los|la|las)?\s*'
                . '(?:documento|documentos|archivo|archivos|pdf|fuente|fuentes)\b/u', $text)) {
            return true;
        }
        if (preg_match('/\b(?:en|del|dentro\s+del)\s+(?:el\s+)?(?:documento|archivo|pdf)\b/u', $text)
                && preg_match('/\b(?:localiza|ubica|busca|recupera|cita|indica|muestra|dice|aparece|consta|pagina|articulo|seccion|fragmento)\b/u', $text)) {
            return true;
        }
        if (preg_match('/\b(?:pagina|paginas)\s+\d+\b/u', $text)) {
            return true;
        }
        if (preg_match('/\b(?:evidencia\s+documental|documentos?\s+indexados?|fuentes?\s+consultadas?|'
                . 'fragmento\s+literal|texto\s+literal|cita\s+literal|literalmente)\b/u', $text)
                && preg_match('/\b(?:documento|archivo|pdf|fuente|pagina|fragmento|texto|curso|indexad)\w*/u', $text)) {
            return true;
        }
        if (preg_match('/\b(?:no\s+infieras|no\s+deduzcas|solo\s+evidencia|unicamente\s+evidencia)\b/u', $text)) {
            return true;
        }
        return false;
    }

    /** @return string greeting|thanks|ack|farewell */
    public static function conversational_kind(string $message): string {
        $text = self::normalize($message);
        if (preg_match('/\b(?:gracias|muchas\s+gracias|mil\s+gracias)\b/u', $text)) {
            return 'thanks';
        }
        if (preg_match('/\b(?:hasta\s+luego|adios)\b/u', $text)) {
            return 'farewell';
        }
        if (preg_match('/\b(?:ok|okay|listo|lista|perfecto|de\s+acuerdo|entendido|entendida)\b/u', $text)) {
            return 'ack';
        }
        return 'greeting';
    }

    private static function normalize(string $text): string {
        $text = \core_text::strtolower(trim($text));
        $text = strtr($text, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        ]);
        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }
}
