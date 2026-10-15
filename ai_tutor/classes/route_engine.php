<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Motor institucional de rutas pedagógicas para VTutor Pro.
 *
 * Esta capa no toma decisiones automáticas finales. Clasifica la consulta y propone
 * una ruta de trabajo orientativa bajo el principio Human-in-the-Loop.
 */
class route_engine {
    public const ROUTE_UNDERSTANDING = 'understanding';
    public const ROUTE_RECOVERY = 'recovery';
    public const ROUTE_LOW_PERFORMANCE = 'low_performance';
    public const ROUTE_ASSESSMENT = 'assessment';
    public const ROUTE_REENTRY = 'reentry';
    public const ROUTE_DEEPENING = 'deepening';

    /** @var int */
    private $courseid;

    /** @var int */
    private $userid;

    public function __construct(int $courseid, int $userid) {
        $this->courseid = $courseid;
        $this->userid = $userid;
    }

    /**
     * Indicates whether route suggestions are enabled.
     */
    public static function enabled(): bool {
        return \block_ai_tutor\local\config::bool('routes_enabled', true);
    }

    /**
     * Determine whether the current student query deserves an actionable route.
     * This avoids adding routes to greetings, thanks, or very short conversational turns.
     */
    public function should_suggest_route(string $message, string $mode = 'tutorial'): bool {
        $text = $this->lower(trim($message . ' ' . $mode));
        if (strlen($text) < 8) {
            return false;
        }
        if ($this->contains_any($text, ['gracias', 'ok', 'listo', 'hola', 'buenos dias', 'buenos días', 'buenas tardes', 'buenas noches'])) {
            // Do not block if the same message also contains a clear learning need.
            return $this->contains_any($text, [
                'no entiendo', 'no comprendo', 'explica', 'explícame', 'ayuda', 'duda', 'confundido',
                'atras', 'atrasado', 'pendiente', 'recuperar', 'entregar', 'tarea',
                'prueba', 'examen', 'evaluacion', 'evaluación', 'estudiar', 'autoevaluacion', 'autoevaluación',
                'baja nota', 'mala nota', 'reprob', 'fallé', 'falle', 'incorrecto', 'error',
                'volvi', 'volví', 'regrese', 'regresé', 'mucho tiempo', 'inactivo',
                'profundizar', 'avanzado', 'reto', 'ampliar', 'investigar'
            ]);
        }
        return $this->classify_message($message, $mode) !== self::ROUTE_UNDERSTANDING
            || $this->contains_any($text, ['no entiendo', 'no comprendo', 'explica', 'explícame', 'ayuda', 'duda', 'confundido', 'tema', 'actividad']);
    }

    /**
     * Build a suggested route for the current query.
     */
    public function suggest_route(string $message, string $mode = 'tutorial'): object {
        $type = $this->classify_message($message, $mode);
        $definition = $this->get_definition($type);

        $route = (object)[
            'courseid' => $this->courseid,
            'userid' => $this->userid,
            'routekey' => $type,
            'name' => $definition['name'],
            'description' => $definition['description'],
            'priority' => $definition['priority'],
            'estimatedminutes' => $definition['estimatedminutes'],
            'steps' => $definition['steps'],
            'courseitems' => \block_ai_tutor\coursemap::recommendations_for_route($this->courseid, $type),
            'hitl_notice' => $this->get_hitl_notice(),
        ];

        return $route;
    }

    /**
     * Render route as student-facing Markdown.
     */
    public function render_route(object $route): string {
        $out = "\n\n---\n\n";
        $out .= "### Ruta sugerida de trabajo\n";
        $out .= "**{$route->name}**\n\n";
        $out .= $route->description . "\n\n";
        $out .= "**Tiempo estimado:** {$route->estimatedminutes} minutos.  \n";
        $out .= "**Prioridad:** {$route->priority}.\n\n";
        $order = 1;
        foreach ($route->steps as $step) {
            $out .= $order . ". **" . $step['module'] . ":** " . $step['instruction'] . "\n";
            $order++;
        }
        if (!empty($route->courseitems)) {
            $out .= "\n**Acciones sugeridas dentro de este curso:**\n";
            foreach ($route->courseitems as $item) {
                $name = isset($item['name']) ? $item['name'] : '';
                $module = isset($item['module']) ? $item['module'] : 'Recurso';
                $url = isset($item['url']) ? $item['url'] : '';
                if ($url !== '') {
                    $out .= "- **" . $module . ":** [" . $name . "](" . $url . ")\n";
                } else {
                    $out .= "- **" . $module . ":** " . $name . "\n";
                }
            }
        }
        $out .= "\n> " . $route->hitl_notice;
        return $out;
    }

    /**
     * Persist minimal route log for analytics and teacher validation.
     */
    public function log_route(object $route, string $trigger = 'chat'): int {
        global $DB;
        $now = time();
        $routekey = substr((string)$route->routekey, 0, 60);

        // Ruta crítica: no llenar la tabla con duplicados si el estudiante repite la consulta.
        $window = \block_ai_tutor\local\config::int('routes_dedup_minutes', 30) * 60;
        if ($window < 300) {
            $window = 300;
        }
        $existingrecords = $DB->get_records_sql(
            'SELECT * FROM {block_ai_tutor_route_log}
              WHERE courseid = :courseid
                AND userid = :userid
                AND routekey = :routekey
                AND status = :status
                AND timecreated >= :since
           ORDER BY timecreated DESC',
            [
                'courseid' => $this->courseid,
                'userid' => $this->userid,
                'routekey' => $routekey,
                'status' => 'suggested',
                'since' => $now - $window,
            ],
            0,
            1
        );
        if (!empty($existingrecords)) {
            $existing = reset($existingrecords);
            $existing->timemodified = $now;
            $DB->update_record('block_ai_tutor_route_log', $existing);
            return (int)$existing->id;
        }

        $record = (object)[
            'courseid' => $this->courseid,
            'userid' => $this->userid,
            'routekey' => $routekey,
            'routename' => substr((string)$route->name, 0, 255),
            'triggerreason' => substr((string)$trigger, 0, 255),
            'status' => 'suggested',
            'teachervalidated' => 0,
            'human_decision' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        return (int)$DB->insert_record('block_ai_tutor_route_log', $record);
    }

    /**
     * Set a human validation decision for a route log.
     */
    public static function validate_route(int $logid, string $decision, int $courseid): bool {
        global $DB, $USER;
        $allowed = ['validated', 'adjusted', 'rejected', 'followup'];
        if (!in_array($decision, $allowed, true)) {
            throw new \invalid_parameter_exception('Unsupported VTutor route validation decision.');
        }
        $record = $DB->get_record(
            'block_ai_tutor_route_log',
            ['id' => $logid, 'courseid' => $courseid],
            '*',
            MUST_EXIST
        );
        $record->status = $decision;
        $record->teachervalidated = 1;
        $record->human_decision = $decision;
        $record->validatedby = (int)$USER->id;
        $record->timevalidated = time();
        $record->timemodified = time();
        return $DB->update_record('block_ai_tutor_route_log', $record);
    }

    private function classify_message(string $message, string $mode): string {
        $text = $this->lower($message . ' ' . $mode);

        if ($this->contains_any($text, ['atras', 'atrasado', 'pendiente', 'recuperar', 'entregar', 'tarea'])) {
            return self::ROUTE_RECOVERY;
        }
        if ($this->contains_any($text, ['prueba', 'examen', 'evaluacion', 'evaluación', 'estudiar', 'autoevaluacion', 'autoevaluación'])) {
            return self::ROUTE_ASSESSMENT;
        }
        if ($this->contains_any($text, ['no entiendo', 'no comprendo', 'explica', 'explícame', 'ayuda', 'duda', 'confundido'])) {
            return self::ROUTE_UNDERSTANDING;
        }
        if ($this->contains_any($text, ['baja nota', 'mala nota', 'reprob', 'fallé', 'falle', 'incorrecto', 'error'])) {
            return self::ROUTE_LOW_PERFORMANCE;
        }
        if ($this->contains_any($text, ['volvi', 'volví', 'regrese', 'regresé', 'mucho tiempo', 'inactivo'])) {
            return self::ROUTE_REENTRY;
        }
        if ($this->contains_any($text, ['profundizar', 'avanzado', 'reto', 'ampliar', 'investigar'])) {
            return self::ROUTE_DEEPENING;
        }
        if ($mode === 'refuerzo') {
            return self::ROUTE_LOW_PERFORMANCE;
        }
        if ($mode === 'recursos') {
            return self::ROUTE_UNDERSTANDING;
        }
        return self::ROUTE_UNDERSTANDING;
    }

    private function get_definition(string $type): array {
        $defs = [
            self::ROUTE_UNDERSTANDING => [
                'name' => 'Comprensión inicial del tema',
                'description' => 'Ruta breve para comprender la idea central antes de avanzar a actividades o tareas.',
                'priority' => 'Media',
                'estimatedminutes' => 25,
                'steps' => [
                    ['module' => 'Tutoría', 'instruction' => 'Revisa la explicación guiada del tema y anota la idea principal con tus palabras.'],
                    ['module' => 'Refuerzo', 'instruction' => 'Trabaja un ejemplo corto paso a paso antes de pasar a la actividad.'],
                    ['module' => 'Autoevaluación', 'instruction' => 'Responde de 3 a 5 preguntas rápidas para verificar comprensión.'],
                    ['module' => 'Recursos', 'instruction' => 'Consulta la ficha, lectura o video del aula virtual relacionado con el tema.'],
                ],
            ],
            self::ROUTE_RECOVERY => [
                'name' => 'Recuperación de tarea pendiente',
                'description' => 'Ruta prioritaria para organizar una entrega atrasada sin saturar al estudiante.',
                'priority' => 'Alta',
                'estimatedminutes' => 35,
                'steps' => [
                    ['module' => 'Tutoría', 'instruction' => 'Identifica qué producto debes entregar y cuál es el criterio mínimo de cumplimiento.'],
                    ['module' => 'Refuerzo', 'instruction' => 'Realiza una práctica corta que prepare la entrega.'],
                    ['module' => 'Autoevaluación', 'instruction' => 'Usa una lista de cotejo antes de enviar la tarea.'],
                    ['module' => 'Recursos', 'instruction' => 'Revisa la guía, rúbrica o ejemplo disponible en el curso.'],
                ],
            ],
            self::ROUTE_LOW_PERFORMANCE => [
                'name' => 'Refuerzo por bajo rendimiento',
                'description' => 'Ruta de recuperación conceptual para mejorar comprensión y desempeño.',
                'priority' => 'Alta',
                'estimatedminutes' => 30,
                'steps' => [
                    ['module' => 'Tutoría', 'instruction' => 'Revisa una reexplicación sencilla del tema.'],
                    ['module' => 'Refuerzo', 'instruction' => 'Practica con ejercicios progresivos, de menor a mayor dificultad.'],
                    ['module' => 'Autoevaluación', 'instruction' => 'Completa un cuestionario diagnóstico corto y revisa tus errores.'],
                    ['module' => 'Recursos', 'instruction' => 'Consulta material remedial o ejemplos resueltos del curso.'],
                ],
            ],
            self::ROUTE_ASSESSMENT => [
                'name' => 'Preparación para evaluación',
                'description' => 'Ruta para estudiar con orden antes de una prueba, lección o autoevaluación.',
                'priority' => 'Media',
                'estimatedminutes' => 40,
                'steps' => [
                    ['module' => 'Tutoría', 'instruction' => 'Revisa un resumen de los contenidos clave.'],
                    ['module' => 'Refuerzo', 'instruction' => 'Practica por niveles de dificultad.'],
                    ['module' => 'Autoevaluación', 'instruction' => 'Responde un simulador o cuestionario breve.'],
                    ['module' => 'Recursos', 'instruction' => 'Estudia el banco de preguntas, lecturas o videos sugeridos.'],
                ],
            ],
            self::ROUTE_REENTRY => [
                'name' => 'Reingreso progresivo al aula',
                'description' => 'Ruta mínima viable para retomar el curso luego de inactividad o interrupciones.',
                'priority' => 'Alta',
                'estimatedminutes' => 20,
                'steps' => [
                    ['module' => 'Tutoría', 'instruction' => 'Lee una orientación breve sobre qué hacer hoy primero.'],
                    ['module' => 'Refuerzo', 'instruction' => 'Realiza una actividad corta y alcanzable.'],
                    ['module' => 'Autoevaluación', 'instruction' => 'Marca qué comprendiste y qué todavía necesitas consultar.'],
                    ['module' => 'Recursos', 'instruction' => 'Usa solo el material esencial para no saturarte.'],
                ],
            ],
            self::ROUTE_DEEPENING => [
                'name' => 'Profundización y reto',
                'description' => 'Ruta para estudiantes que desean ampliar, aplicar o investigar más allá del mínimo.',
                'priority' => 'Baja',
                'estimatedminutes' => 45,
                'steps' => [
                    ['module' => 'Tutoría', 'instruction' => 'Formula una pregunta desafiante sobre el tema.'],
                    ['module' => 'Refuerzo', 'instruction' => 'Aplica el contenido a un caso o problema real.'],
                    ['module' => 'Autoevaluación', 'instruction' => 'Contrasta tu respuesta con criterios de calidad.'],
                    ['module' => 'Recursos', 'instruction' => 'Revisa lecturas ampliadas o materiales complementarios.'],
                ],
            ],
        ];
        return $defs[$type] ?? $defs[self::ROUTE_UNDERSTANDING];
    }

    private function get_hitl_notice(): string {
        return 'Esta ruta es una recomendación pedagógica generada por VTutor Pro. No constituye una decisión automática final. Tu docente, tutor o autoridad institucional puede revisarla, validarla o ajustarla según tu avance.';
    }

    private function contains_any(string $text, array $needles): bool {
        foreach ($needles as $needle) {
            if (strpos($text, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private function lower(string $text): string {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }
        return strtolower($text);
    }
}
