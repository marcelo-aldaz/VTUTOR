<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\document;

defined('MOODLE_INTERNAL') || die();

/**
 * PDF extractor with optional pdftotext acceleration and a PHP fallback.
 *
 * No command is executed unless the configured path resolves to an executable
 * regular file. Commands are passed to proc_open as an argument array so file
 * names and configuration values are not interpreted by a shell.
 */
class pdf_extractor implements pdf_extractor_interface {
    /** @var string|null */
    private $binary;

    public function __construct(?string $binary = null) {
        $configured = $binary ?? (string)(get_config('block_ai_tutor', 'pdftotext_path') ?: '');
        $resolved = $configured !== '' ? realpath($configured) : false;
        $this->binary = self::valid_binary($resolved) ? $resolved : null;
    }

    /**
     * Return extractor readiness without executing a command.
     *
     * @return array{configured: string, resolved: string, available: bool, fallback: bool}
     */
    public static function status(): array {
        $configured = (string)(get_config('block_ai_tutor', 'pdftotext_path') ?: '');
        $resolved = $configured !== '' ? realpath($configured) : false;
        $fallbackconfig = get_config('block_ai_tutor', 'pdf_native_fallback');
        return [
            'configured' => $configured,
            'resolved' => $resolved === false ? '' : $resolved,
            'available' => self::valid_binary($resolved),
            'fallback' => $fallbackconfig === false || (int)$fallbackconfig === 1,
        ];
    }

    public function extract(\stored_file $file): array {
        $fallbackconfig = get_config('block_ai_tutor', 'pdf_native_fallback');
        $fallbackenabled = $fallbackconfig === false || (int)$fallbackconfig === 1;
        if ($this->binary === null) {
            if (!$fallbackenabled) {
                throw new \moodle_exception('rag_pdftotext_unavailable', 'block_ai_tutor');
            }
            return (new native_pdf_extractor())->extract($file);
        }

        $tempdir = make_temp_directory('block_ai_tutor');
        $path = $tempdir . '/pdf_' . bin2hex(random_bytes(8)) . '.pdf';
        $file->copy_content_to($path);

        try {
            $output = $this->run([$this->binary, '-layout', '-enc', 'UTF-8', $path, '-']);
        } catch (\Throwable $e) {
            if (!$fallbackenabled) {
                throw $e;
            }
            return (new native_pdf_extractor())->extract($file);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $output = str_replace(["\r\n", "\r"], "\n", $output);
        $rawpages = preg_split('/\f/u', $output);
        $pages = [];
        foreach ($rawpages as $index => $text) {
            $text = $this->normalize((string)$text);
            if ($text !== '' || $index < count($rawpages) - 1) {
                $pages[$index + 1] = $text;
            }
        }
        if (!$pages) {
            $pages = [1 => ''];
        }

        $pagecount = count($pages);
        $maxpages = max(1, (int)(get_config('block_ai_tutor', 'rag_max_pdf_pages') ?: 40));
        if ($pagecount > $maxpages) {
            throw new \moodle_exception('rag_pdf_too_many_pages', 'block_ai_tutor', '', $maxpages);
        }

        $joined = implode("\n", $pages);
        $chars = \core_text::strlen($joined);
        $printable = preg_replace('/[^\pL\pN\pP\pZ\s]/u', '', $joined);
        $ratio = $chars > 0 ? \core_text::strlen((string)$printable) / $chars : 0.0;
        $minimum = max(200, $pagecount * 80);
        $pendingocr = $chars < $minimum || $ratio < 0.70;

        return [
            'pages' => $pages,
            'pagecount' => $pagecount,
            'extractedchars' => $chars,
            'pendingocr' => $pendingocr,
            'failurecode' => $pendingocr ? 'image_only' : '',
            'extractor' => 'pdftotext',
        ];
    }

    /**
     * Execute a fixed binary without shell expansion.
     *
     * @param array $command
     * @return string
     */
    /**
     * Accept only an explicitly configured pdftotext executable.
     *
     * @param string|false $resolved
     * @return bool
     */
    private static function valid_binary($resolved): bool {
        if ($resolved === false || !is_file($resolved) || !is_executable($resolved)) {
            return false;
        }
        $basename = strtolower(basename((string)$resolved));
        return in_array($basename, ['pdftotext', 'pdftotext.exe'], true);
    }

    private function run(array $command): string {
        if (!function_exists('proc_open')) {
            throw new \moodle_exception('rag_process_execution_disabled', 'block_ai_tutor');
        }
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes);
        if (!is_resource($process)) {
            throw new \moodle_exception('rag_pdftotext_failed', 'block_ai_tutor');
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitcode = proc_close($process);
        if ($exitcode !== 0) {
            $detail = \block_ai_tutor\security_filter::sanitize_error_message((string)$stderr);
            throw new \moodle_exception('rag_pdftotext_failed_detail', 'block_ai_tutor', '', $detail);
        }
        return (string)$stdout;
    }

    private function normalize(string $text): string {
        $text = preg_replace('/[\x00-\x08\x0B\x0E-\x1F]+/u', ' ', $text);
        $text = preg_replace('/[ \t]+$/um', '', (string)$text);
        $text = preg_replace('/\n{4,}/u', "\n\n\n", (string)$text);
        return trim((string)$text);
    }
}
