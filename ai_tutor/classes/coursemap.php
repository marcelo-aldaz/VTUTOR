<?php
// This file is part of Moodle - http://moodle.org/
// blocks/ai_tutor/classes/coursemap.php

namespace block_ai_tutor;

defined('MOODLE_INTERNAL') || die();

/**
 * Mapa pedagógico institucional del curso para VTutor Pro.
 *
 * Clasifica recursos y actividades visibles del curso Moodle para que las rutas
 * pedagógicas conduzcan al estudiante hacia acciones reales del aula virtual.
 */
class coursemap {
    /**
     * Remove exact course/module duplicates while keeping the oldest record.
     */
    public static function deduplicate(): int {
        global $DB;
        $seen = [];
        $duplicateids = [];
        $recordset = $DB->get_recordset(
            'block_ai_tutor_coursemap',
            null,
            'courseid ASC, cmid ASC, id ASC',
            'id, courseid, cmid'
        );
        foreach ($recordset as $record) {
            $key = (int)$record->courseid . ':' . (int)$record->cmid;
            if (!isset($seen[$key])) {
                $seen[$key] = (int)$record->id;
                continue;
            }
            $duplicateids[] = (int)$record->id;
        }
        $recordset->close();
        foreach (array_chunk($duplicateids, 500) as $ids) {
            $DB->delete_records_list('block_ai_tutor_coursemap', 'id', $ids);
        }
        return count($duplicateids);
    }

    /**
     * Rebuild course map from visible course modules.
     */
    public static function rebuild(int $courseid): int {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists(new \xmldb_table('block_ai_tutor_coursemap'))) {
            return 0;
        }

        $modinfo = get_fast_modinfo($courseid);
        $now = time();
        $count = 0;
        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records('block_ai_tutor_coursemap', ['courseid' => $courseid]);
            foreach ($modinfo->cms as $cm) {
                if (empty($cm->uservisible)) {
                    continue;
                }
                $pedagogicaltype = self::classify_module((string)$cm->modname, (string)$cm->name);
                $record = (object)[
                    'courseid' => $courseid,
                    'cmid' => (int)$cm->id,
                    'sectionnum' => isset($cm->sectionnum) ? (int)$cm->sectionnum : 0,
                    'modname' => substr((string)$cm->modname, 0, 40),
                    'itemname' => substr(format_string((string)$cm->name), 0, 255),
                    'pedagogicaltype' => $pedagogicaltype,
                    'visible' => 1,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $DB->insert_record('block_ai_tutor_coursemap', $record);
                $count++;
            }
            $transaction->allow_commit();
            return $count;
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Return map items without mutating the map during a read request.
     */
    public static function get_items(int $courseid, ?string $pedagogicaltype = null, int $limit = 6): array {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists(new \xmldb_table('block_ai_tutor_coursemap'))) {
            return [];
        }

        $params = ['courseid' => $courseid, 'visible' => 1];
        if ($pedagogicaltype !== null) {
            $params['pedagogicaltype'] = $pedagogicaltype;
        }
        $records = $DB->get_records('block_ai_tutor_coursemap', $params, 'sectionnum ASC, id ASC', '*', 0, $limit);
        return array_values($records);
    }

    /**
     * Build recommended Moodle actions for a route key.
     */
    public static function recommendations_for_route(int $courseid, string $routekey): array {
        global $USER;

        $types = self::types_for_route($routekey);
        $recommendations = [];
        try {
            // The stored map is an institutional catalogue. Access decisions
            // are always re-evaluated for the current user at read time so a
            // map rebuilt by an administrator cannot reveal restricted items.
            $modinfo = get_fast_modinfo($courseid, $USER->id);
        } catch (\Throwable $e) {
            return [];
        }

        foreach ($types as $modulelabel => $pedtype) {
            $items = self::get_items($courseid, $pedtype, 20);
            if (empty($items) && $pedtype !== 'resource') {
                $items = self::get_items($courseid, 'resource', 20);
            }
            foreach ($items as $item) {
                try {
                    $cm = $modinfo->get_cm((int)$item->cmid);
                } catch (\Throwable $e) {
                    continue;
                }
                if (!$cm->uservisible) {
                    continue;
                }
                $recommendations[] = [
                    'module' => $modulelabel,
                    'pedagogicaltype' => $pedtype,
                    'name' => $item->itemname,
                    'modname' => $item->modname,
                    'cmid' => (int)$item->cmid,
                    'url' => (new \moodle_url('/mod/' . $item->modname . '/view.php', ['id' => $item->cmid]))->out(false),
                ];
                break;
            }
        }
        return $recommendations;
    }

    private static function types_for_route(string $routekey): array {
        switch ($routekey) {
            case route_engine::ROUTE_RECOVERY:
                return ['Tutoría' => 'tutorial', 'Refuerzo' => 'reinforcement', 'Autoevaluación' => 'selfassessment', 'Recursos' => 'resource'];
            case route_engine::ROUTE_ASSESSMENT:
                return ['Tutoría' => 'tutorial', 'Autoevaluación' => 'selfassessment', 'Refuerzo' => 'reinforcement', 'Recursos' => 'resource'];
            case route_engine::ROUTE_DEEPENING:
                return ['Tutoría' => 'tutorial', 'Recursos' => 'resource', 'Autoevaluación' => 'selfassessment'];
            case route_engine::ROUTE_REENTRY:
            case route_engine::ROUTE_LOW_PERFORMANCE:
            case route_engine::ROUTE_UNDERSTANDING:
            default:
                return ['Tutoría' => 'tutorial', 'Refuerzo' => 'reinforcement', 'Autoevaluación' => 'selfassessment', 'Recursos' => 'resource'];
        }
    }

    /**
     * Pedagogical classification by module type and name keywords.
     */
    private static function classify_module(string $modname, string $name): string {
        $text = self::lower($modname . ' ' . $name);

        if (in_array($modname, ['quiz'], true) || self::contains_any($text, ['autoevalu', 'cuestionario', 'quiz', 'evaluacion', 'evaluación', 'prueba'])) {
            return 'selfassessment';
        }
        if (in_array($modname, ['assign', 'forum'], true) || self::contains_any($text, ['tarea', 'foro', 'entrega', 'actividad integradora', 'evidencia'])) {
            return 'evidence';
        }
        if (in_array($modname, ['lesson', 'page', 'book'], true) || self::contains_any($text, ['tutoria', 'tutoría', 'guia', 'guía', 'lectura guia', 'lectura guía', 'explicacion', 'explicación'])) {
            return 'tutorial';
        }
        if (in_array($modname, ['h5pactivity', 'lesson'], true) || self::contains_any($text, ['refuerzo', 'practica', 'práctica', 'ejercicio', 'recuperacion', 'recuperación'])) {
            return 'reinforcement';
        }
        if (in_array($modname, ['resource', 'url', 'folder', 'label'], true) || self::contains_any($text, ['pdf', 'video', 'recurso', 'material', 'presentacion', 'presentación'])) {
            return 'resource';
        }
        return 'resource';
    }

    private static function contains_any(string $text, array $needles): bool {
        foreach ($needles as $needle) {
            if (strpos($text, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function lower(string $text): string {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($text, 'UTF-8');
        }
        return strtolower($text);
    }
}
