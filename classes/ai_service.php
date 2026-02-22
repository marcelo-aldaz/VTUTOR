<?php
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

class ai_service {
    private $provider;
    private $apikey;
    private $model;
    private $maxTokens;
    private $temperature;
    private $baseurl;

    private static $providerConfigs = [
        'openai' => ['endpoint' => 'https://api.openai.com/v1/chat/completions', 'model_default' => 'gpt-4o-mini', 'style' => 'openai'],
        'openai_compatible' => ['endpoint' => '', 'model_default' => '', 'style' => 'openai'],
        'deepseek' => ['endpoint' => 'https://api.deepseek.com/v1/chat/completions', 'model_default' => 'deepseek-chat', 'style' => 'openai'],
        'qwen' => ['endpoint' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions', 'model_default' => 'qwen-turbo', 'style' => 'openai'],
        'ollama' => ['endpoint' => 'http://127.0.0.1:11434/api/chat', 'model_default' => 'qwen2.5', 'style' => 'ollama'],
        'gemini' => ['endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models', 'model_default' => 'gemini-1.5-flash', 'style' => 'gemini'],
    ];

    public function __construct() {
        $this->provider = get_config('block_ai_tutor', 'provider') ?: 'deepseek';
        if (!isset(self::$providerConfigs[$this->provider])) {
            $this->provider = 'deepseek';
        }
        $this->apikey = (string)(get_config('block_ai_tutor', 'api_key') ?: '');
        $this->baseurl = trim((string)(get_config('block_ai_tutor', 'baseurl') ?: ''));
        $defaultmodel = self::$providerConfigs[$this->provider]['model_default'] ?? '';
        $this->model = trim((string)(get_config('block_ai_tutor', 'model') ?: $defaultmodel));
        $this->maxTokens = (int)(get_config('block_ai_tutor', 'max_tokens') ?: 1024);
        $this->temperature = (float)(get_config('block_ai_tutor', 'temperature') ?: 0.2);
    }

    public function get_tutor_response($userMessage, $courseid, $conversationHistory = [], $cmid = 0) {
        $missing = [];
        if (empty($this->provider)) { $missing[] = 'provider'; }
        if (empty($this->model)) { $missing[] = 'model'; }
        if ($this->provider !== 'ollama' && empty($this->apikey)) { $missing[] = 'api_key'; }
        if (!empty($missing)) {
            return '⚠️ Configuración incompleta del tutor: faltan ' . implode(', ', $missing) . '. Configura el bloque en Plugins → Bloques → VTutor o desde el bloque (enlace de administrador).';
        }

        try {
            $rag = $this->build_rag_context((int)$courseid, (int)$cmid, (string)$userMessage);
            $courseContext = $this->get_course_context((int)$courseid, (int)$cmid);
            $systemPrompt = $this->build_system_prompt($courseContext, $rag['context']);
            $messages = $this->build_messages($systemPrompt, $userMessage, $conversationHistory);

            $style = self::$providerConfigs[$this->provider]['style'] ?? 'openai';
            if ($style === 'gemini') {
                $response = $this->call_gemini_api($messages);
            } else if ($style === 'ollama') {
                $response = $this->call_ollama_api($messages);
            } else {
                $response = $this->call_openai_compatible_api($messages);
            }

            if (!empty($rag['sources'])) {
                $response .= "\n\nFuentes consultadas:\n" . implode("\n", array_map(function($s){ return '• ' . $s; }, $rag['sources']));
            }
            $this->save_to_history((int)$courseid, $userMessage, $response);
            return $response;
        } catch (\Throwable $e) {
            error_log('VTutor Error [' . $this->provider . ']: ' . $e->getMessage());
            return '⚠️ Hubo un problema al conectar con el tutor. Por favor, intenta nuevamente.';
        }
    }

    public function reindex_course_content($courseid = 0) {
        global $DB;
        $stats = ['sources' => 0, 'chunks' => 0];
        $courses = [];
        if ($courseid > 1) {
            $courses[] = get_course($courseid);
        } else {
            $courses = $DB->get_records_select('course', 'id > 1', null, '', 'id,fullname,summary');
        }
        foreach ($courses as $course) {
            $DB->delete_records('block_ai_tutor_embed', ['courseid' => $course->id]);
            $stats['chunks'] += $this->index_course_content($course->id, $stats['sources']);
        }
        return $stats;
    }

    private function get_course_context($courseid, $cmid = 0) {
        $course = get_course($courseid);
        $context = "📚 CURSO: {$course->fullname}\n";
        $context .= '📝 DESCRIPCIÓN: ' . trim($this->normalize_text((string)$course->summary)) . "\n\n";
        if ($cmid) { $context .= "📍 CONTEXTO ACTUAL (cmid): {$cmid}\n"; }
        try {
            $modinfo = get_fast_modinfo($courseid);
            $count = 0;
            foreach ($modinfo->cms as $cm) {
                if (!$cm->uservisible) { continue; }
                $mark = ($cmid && (int)$cm->id === (int)$cmid) ? '👉' : '•';
                $context .= "{$mark} {$cm->modname}: {$cm->name}\n";
                if (++$count >= 25) { break; }
            }
        } catch (\Throwable $e) {}
        return $context;
    }

    private function build_system_prompt($courseContext, $ragContext = '') {
        $guideOnly = (int)(get_config('block_ai_tutor', 'guideonly') ?? 1);
        $prompt = "Eres VTutor, un asistente pedagógico para estudiantes.\n\n";
        $prompt .= "1. Explica conceptos con claridad y ejemplos prácticos.\n";
        $prompt .= "2. Si hay contexto recuperado (RAG), priorízalo y cita brevemente la fuente.\n";
        $prompt .= "3. Si el contexto no alcanza, indícalo con transparencia.\n";
        if ($guideOnly) { $prompt .= "4. Orienta tareas y cuestionarios sin entregar respuestas finales evaluables.\n"; }
        $prompt .= "5. Responde en el idioma del estudiante.\n";
        $prompt .= "6. Si el usuario pide formato simple, evita markdown.\n\n";
        $prompt .= "CONTEXTO DEL CURSO:\n{$courseContext}\n";
        if ($ragContext !== '') {
            $prompt .= "\nCONTEXTO RECUPERADO (RAG):\n{$ragContext}\n";
        }
        return $prompt;
    }

    private function build_messages($systemPrompt, $userMessage, $history) {
        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach (array_slice($history, -8) as $msg) {
            if (!empty($msg['message'])) { $messages[] = ['role' => 'user', 'content' => $msg['message']]; }
            if (!empty($msg['response'])) { $messages[] = ['role' => 'assistant', 'content' => $msg['response']]; }
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];
        return $messages;
    }

    private function build_rag_context($courseid, $cmid, $question) {
        global $DB;
        if (!(int)(get_config('block_ai_tutor', 'rag_enabled') ?? 1)) {
            return ['context' => '', 'sources' => []];
        }
        if (!(int)$DB->count_records('block_ai_tutor_embed', ['courseid' => $courseid]) && (int)(get_config('block_ai_tutor', 'rag_autolazy') ?? 1)) {
            $dummy = 0;
            $this->index_course_content($courseid, $dummy);
        }
        $tokens = $this->extract_keywords($question);
        $topk = max(1, (int)(get_config('block_ai_tutor', 'rag_topk') ?: 5));
        $records = $DB->get_records('block_ai_tutor_embed', ['courseid' => $courseid], 'timeindexed DESC', '*', 0, 400);
        $scored = [];
        foreach ($records as $r) {
            $score = 0;
            $txt = core_text::strtolower((string)$r->embedding_text);
            foreach ($tokens as $t) {
                $score += substr_count($txt, $t) * 3;
                $score += substr_count(core_text::strtolower((string)$r->title), $t) * 2;
            }
            if ($cmid && (int)$r->contentid === $cmid) { $score += 10; }
            if ($score > 0) { $scored[] = [$score, $r]; }
        }
        usort($scored, function($a,$b){ return $b[0] <=> $a[0]; });
        $picked = array_slice($scored, 0, $topk);
        $parts = [];
        $sources = [];
        foreach ($picked as $item) {
            $r = $item[1];
            $label = trim($r->contenttype . ' | ' . $r->title . ' | chunk ' . $r->chunk_index);
            $parts[] = "[{$label}]\n" . trim((string)$r->content);
            $sources[] = $label;
        }
        return ['context' => implode("\n\n", $parts), 'sources' => array_values(array_unique($sources))];
    }

    private function extract_keywords($text) {
        $text = core_text::strtolower($this->normalize_text($text));
        $words = preg_split('/[^\pL\pN_]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stop = ['que','para','como','con','una','unos','unas','este','esta','estas','estos','desde','hasta','sobre','entre','porque','donde','cuando','cual','cuáles','solo','solo','hola','favor','por','the','and','you'];
        $out = [];
        foreach ($words as $w) {
            if (core_text::strlen($w) < 3) { continue; }
            if (in_array($w, $stop, true)) { continue; }
            $out[] = $w;
        }
        return array_values(array_unique(array_slice($out, 0, 12)));
    }

    private function index_course_content($courseid, &$sourcecounter) {
        global $DB;
        $chunksadded = 0;
        $modinfo = get_fast_modinfo($courseid);
        foreach ($modinfo->cms as $cm) {
            if (!$cm->uservisible) { continue; }
            $texts = [];
            $texts[] = ['type' => 'modulemeta', 'title' => $cm->modname . ': ' . $cm->name, 'text' => $cm->modname . ' ' . $cm->name];
            foreach ($this->get_module_database_texts($cm) as $t) { $texts[] = $t; }
            foreach ($this->get_module_file_texts($cm) as $t) { $texts[] = $t; }
            $hascontent = false;
            foreach ($texts as $t) {
                $text = trim($this->normalize_text((string)$t['text']));
                if ($text === '') { continue; }
                $hascontent = true;
                $chunks = $this->chunk_text($text, 1000, 120);
                foreach ($chunks as $i => $chunk) {
                    $rec = (object)[
                        'courseid' => $courseid,
                        'contenttype' => substr((string)$t['type'], 0, 50),
                        'contentid' => (int)$cm->id,
                        'title' => substr((string)$t['title'], 0, 255),
                        'content' => $chunk,
                        'embedding_text' => $chunk,
                        'chunk_index' => (int)$i,
                        'timeindexed' => time(),
                    ];
                    $DB->insert_record('block_ai_tutor_embed', $rec);
                    $chunksadded++;
                }
            }
            if ($hascontent) { $sourcecounter++; }
        }
        return $chunksadded;
    }

    private function get_module_database_texts($cm) {
        global $DB;
        $out = [];
        $table = $cm->modname;
        if (!$DB->get_manager()->table_exists($table)) { return $out; }
        $record = $DB->get_record($table, ['id' => $cm->instance]);
        if (!$record) { return $out; }
        foreach (['intro','content','description'] as $field) {
            if (property_exists($record, $field) && !empty($record->{$field})) {
                $out[] = [
                    'type' => $cm->modname . '_html',
                    'title' => $cm->name . ' (' . $field . ')',
                    'text' => $this->html_to_text((string)$record->{$field}),
                ];
            }
        }
        if ($cm->modname === 'quiz') {
            foreach (['intro'] as $field) {
                if (!empty($record->{$field})) {
                    $out[] = ['type' => 'quiz_intro', 'title' => $cm->name . ' instrucciones', 'text' => $this->html_to_text((string)$record->{$field})];
                }
            }
        }
        return $out;
    }

    private function get_module_file_texts($cm) {
        global $CFG;
        $out = [];
        $fs = get_file_storage();
        $context = \context_module::instance($cm->id);
        $component = 'mod_' . $cm->modname;
        $areas = ['content','intro','introattachment','attachment'];
        if ($cm->modname === 'folder') { $areas[] = 'content'; }
        foreach (array_unique($areas) as $area) {
            try {
                $files = $fs->get_area_files($context->id, $component, $area, false, 'filename', false);
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($files as $f) {
                if ($f->is_directory()) { continue; }
                $text = $this->extract_text_from_stored_file($f);
                if ($text === '') { continue; }
                $out[] = [
                    'type' => 'file_' . strtolower(pathinfo($f->get_filename(), PATHINFO_EXTENSION)),
                    'title' => $cm->name . ' / ' . $f->get_filename(),
                    'text' => $text,
                ];
            }
        }
        return $out;
    }

    private function extract_text_from_stored_file($f) {
        $ext = strtolower(pathinfo($f->get_filename(), PATHINFO_EXTENSION));
        $content = '';
        try {
            $content = (string)$f->get_content();
        } catch (\Throwable $e) {
            return '';
        }
        if ($content === '') { return ''; }
        switch ($ext) {
            case 'txt': case 'md': case 'csv':
                return $this->normalize_text($content);
            case 'html': case 'htm':
                return $this->html_to_text($content);
            case 'docx':
                return $this->extract_docx_text($content);
            case 'pptx':
                return $this->extract_pptx_text($content);
            case 'xlsx':
                return $this->extract_xlsx_text($content);
            case 'pdf':
                return $this->extract_pdf_text_basic($content);
            default:
                return '';
        }
    }

    private function extract_docx_text($binary) {
        $tmp = make_temp_directory('block_ai_tutor');
        $path = $tmp . '/tmp_' . uniqid('', true) . '.docx';
        file_put_contents($path, $binary);
        $zip = new \ZipArchive();
        $text = '';
        if ($zip->open($path) === true) {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml !== false) { $text = preg_replace('/\s+/u', ' ', strip_tags(str_replace(['</w:p>','</w:tr>'], ["\n","\n"], $xml))); }
            $zip->close();
        }
        @unlink($path);
        return $this->normalize_text($text);
    }

    private function extract_pptx_text($binary) {
        $tmp = make_temp_directory('block_ai_tutor');
        $path = $tmp . '/tmp_' . uniqid('', true) . '.pptx';
        file_put_contents($path, $binary);
        $zip = new \ZipArchive();
        $parts = [];
        if ($zip->open($path) === true) {
            for ($i = 1; $i <= 500; $i++) {
                $xml = $zip->getFromName('ppt/slides/slide' . $i . '.xml');
                if ($xml === false) { continue; }
                $txt = preg_replace('/\s+/u', ' ', strip_tags(str_replace(['</a:p>'], ["\n"], $xml)));
                $txt = trim($this->normalize_text($txt));
                if ($txt !== '') { $parts[] = '[Diapositiva ' . $i . '] ' . $txt; }
            }
            $zip->close();
        }
        @unlink($path);
        return implode("\n", $parts);
    }

    private function extract_xlsx_text($binary) {
        $tmp = make_temp_directory('block_ai_tutor');
        $path = $tmp . '/tmp_' . uniqid('', true) . '.xlsx';
        file_put_contents($path, $binary);
        $zip = new \ZipArchive();
        $out = [];
        if ($zip->open($path) === true) {
            $shared = [];
            $ss = $zip->getFromName('xl/sharedStrings.xml');
            if ($ss !== false) {
                preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $ss, $m);
                $shared = array_map(function($x){ return html_entity_decode(strip_tags($x), ENT_QUOTES | ENT_HTML5, 'UTF-8'); }, $m[1]);
            }
            for ($i = 1; $i <= 30; $i++) {
                $xml = $zip->getFromName('xl/worksheets/sheet' . $i . '.xml');
                if ($xml === false) { continue; }
                $sheettxt = $xml;
                // replace shared string references.
                $sheettxt = preg_replace_callback('/<c[^>]*t="s"[^>]*>\s*<v>(\d+)<\/v>\s*<\/c>/s', function($m) use ($shared) {
                    $idx = (int)$m[1];
                    return ' ' . ($shared[$idx] ?? '') . ' ';
                }, $sheettxt);
                $sheettxt = preg_replace('/<[^>]+>/', ' ', $sheettxt);
                $sheettxt = trim($this->normalize_text(html_entity_decode($sheettxt, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if ($sheettxt !== '') { $out[] = '[Hoja ' . $i . '] ' . $sheettxt; }
            }
            $zip->close();
        }
        @unlink($path);
        return implode("\n", $out);
    }

    private function extract_pdf_text_basic($binary) {
        $txt = '';
        if (preg_match_all('/\(([^\)]{1,500})\)\s*Tj/s', $binary, $m)) {
            $txt .= implode("\n", $m[1]);
        }
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $binary, $m2)) {
            foreach ($m2[1] as $seg) {
                if (preg_match_all('/\(([^\)]{1,500})\)/s', $seg, $m3)) {
                    $txt .= "\n" . implode(' ', $m3[1]);
                }
            }
        }
        $txt = preg_replace('/\\\\([nrt])/', ' ', $txt);
        return $this->normalize_text($txt);
    }

    private function html_to_text($html) {
        $html = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\s*\/p\s*>/i', "\n", $html);
        $html = preg_replace('/<\s*li\b[^>]*>/i', "\n• ", $html);
        return $this->normalize_text(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function normalize_text($text) {
        $text = (string)$text;
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]+/u', ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        return trim($text);
    }

    private function chunk_text($text, $size = 1000, $overlap = 120) {
        $text = trim($text);
        if ($text === '') { return []; }
        $chunks = [];
        $len = core_text::strlen($text);
        $pos = 0;
        while ($pos < $len) {
            $chunk = core_text::substr($text, $pos, $size);
            if ($pos + $size < $len) {
                $cut = max((int)mb_strrpos($chunk, "\n"), (int)mb_strrpos($chunk, '. '));
                if ($cut > 200) { $chunk = mb_substr($chunk, 0, $cut + 1); }
            }
            $chunk = trim($chunk);
            if ($chunk !== '') { $chunks[] = $chunk; }
            $step = max(100, core_text::strlen($chunk) - $overlap);
            $pos += $step;
        }
        return $chunks;
    }

    private function endpoint_from_base($suffix) {
        $base = rtrim((string)$this->baseurl, '/');
        if ($base !== '') { return $base . $suffix; }
        return self::$providerConfigs[$this->provider]['endpoint'];
    }

    private function call_openai_compatible_api($messages) {
        $endpoint = $this->endpoint_from_base('/chat/completions');
        $payload = ['model' => $this->model, 'messages' => $messages, 'max_tokens' => $this->maxTokens, 'temperature' => $this->temperature];
        $headers = ['Content-Type: application/json'];
        if (!empty($this->apikey)) { $headers[] = 'Authorization: Bearer ' . $this->apikey; }
        $data = $this->curl_json_post($endpoint, $payload, $headers);
        if (isset($data['choices'][0]['message']['content'])) { return trim((string)$data['choices'][0]['message']['content']); }
        throw new \Exception('Respuesta inválida del proveedor OpenAI-compatible');
    }
    private function call_ollama_api($messages) {
        $endpoint = $this->baseurl ? rtrim($this->baseurl, '/') . '/api/chat' : self::$providerConfigs['ollama']['endpoint'];
        $payload = ['model' => $this->model, 'messages' => array_values($messages), 'stream' => false,
            'options' => ['temperature' => $this->temperature, 'num_predict' => $this->maxTokens]];
        $data = $this->curl_json_post($endpoint, $payload, ['Content-Type: application/json']);
        if (isset($data['message']['content'])) { return trim((string)$data['message']['content']); }
        throw new \Exception('Respuesta inválida de Ollama');
    }
    private function call_gemini_api($messages) {
        $base = rtrim($this->baseurl ?: self::$providerConfigs['gemini']['endpoint'], '/');
        $url = $base . '/' . rawurlencode($this->model) . ':generateContent?key=' . urlencode($this->apikey);
        $contents = [];
        foreach ($messages as $m) { if ($m['role'] === 'system') continue; $contents[] = ['role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['content']]]]; }
        $payload = ['contents' => $contents ?: [['role'=>'user','parts'=>[['text'=>'Hola']]]], 'generationConfig' => ['temperature' => $this->temperature, 'maxOutputTokens' => $this->maxTokens]];
        $data = $this->curl_json_post($url, $payload, ['Content-Type: application/json']);
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) { return trim((string)$data['candidates'][0]['content']['parts'][0]['text']); }
        throw new \Exception('Respuesta inválida de Gemini');
    }
    private function curl_json_post($url, array $payload, array $headers) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        $raw = curl_exec($ch); $errno = curl_errno($ch); $err = curl_error($ch); $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($errno) throw new \Exception('cURL ' . $errno . ': ' . $err);
        $data = json_decode((string)$raw, true);
        if ($http < 200 || $http >= 300) { throw new \Exception('HTTP ' . $http . ' - ' . (is_array($data)? json_encode($data): substr((string)$raw,0,300))); }
        if (!is_array($data)) throw new \Exception('JSON inválido');
        return $data;
    }
    private function save_to_history($courseid, $message, $response) {
        global $DB, $USER;
        $DB->insert_record('block_ai_tutor_msg', (object)[
            'courseid' => $courseid, 'userid' => $USER->id, 'message' => $message, 'response' => $response,
            'provider' => $this->provider, 'model' => $this->model, 'timecreated' => time()
        ]);
    }
}
