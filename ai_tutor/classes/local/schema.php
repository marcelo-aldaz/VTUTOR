<?php
// This file is part of Moodle - http://moodle.org/
namespace block_ai_tutor\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Runtime/upgrade schema guard for VTutor Pro.
 *
 * Moodle normally creates tables through install.xml/upgrade.php. This guard is
 * intentionally conservative and only creates missing tables from the plugin's
 * own schema to recover installations that were updated from intermediate ZIPs
 * without a complete db/upgrade.php.
 */
class schema {
    /** Inspect VTutor tables without creating tables, fields, keys or indexes. */
    public static function inspect_tables(): array {
        global $DB;

        self::load_xmldb_classes();
        $dbman = $DB->get_manager();
        $result = [
            'created' => [],
            'existing' => [],
            'missing' => [],
            'missingfields' => [],
            'errors' => [],
        ];
        foreach (['msg', 'embed', 'doc', 'batch', 'webhook_log', 'route_log', 'coursemap'] as $name) {
            try {
                $table = self::{'table_' . $name}();
                if ($dbman->table_exists($table)) {
                    $result['existing'][] = $table->getName();
                    foreach ($table->getFields() as $field) {
                        if (!$dbman->field_exists($table, $field)) {
                            $result['missingfields'][] = $table->getName() . '.' . $field->getName();
                        }
                    }
                } else {
                    $result['missing'][] = $table->getName();
                }
            } catch (\Throwable $e) {
                $result['errors'][] = $name . ': ' . $e->getMessage();
            }
        }
        return $result;
    }


    /**
     * Require plugin tables at runtime without mutating the database schema.
     *
     * Stable runtime code must never repair XMLDB structures on page views or
     * AJAX calls. Missing tables/fields indicate an incomplete Moodle upgrade
     * and must be surfaced to the administrator instead.
     *
     * @param array<int, string> $tablenames
     * @throws \moodle_exception
     */
    public static function require_tables(array $tablenames): void {
        global $DB;

        self::load_xmldb_classes();
        $dbman = $DB->get_manager();
        foreach ($tablenames as $tablename) {
            $table = new \xmldb_table($tablename);
            if (!$dbman->table_exists($table)) {
                throw new \moodle_exception('errorschemamissing', 'block_ai_tutor', '', $tablename);
            }
        }
    }

    /** Ensure core VTutor tables exist. */
    public static function ensure_tables(bool $optional = true): array {
        global $CFG, $DB;

        self::load_xmldb_classes();
        $dbman = $DB->get_manager();
        $result = ['created' => [], 'existing' => [], 'errors' => []];

        foreach (['msg', 'embed', 'doc', 'batch', 'webhook_log', 'route_log', 'coursemap'] as $name) {
            if (!$optional && !in_array($name, ['msg', 'embed'], true)) {
                continue;
            }
            try {
                $method = 'table_' . $name;
                $table = self::$method();
                if ($dbman->table_exists($table)) {
                    $result['existing'][] = $table->getName();
                    self::ensure_fields($dbman, $table, $name, $result);
                    continue;
                }
                $dbman->create_table($table);
                $result['created'][] = $table->getName();
            } catch (\Throwable $e) {
                $result['errors'][] = $name . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    public static function table_exists(string $tablename): bool {
        global $DB;
        try {
            self::load_xmldb_classes();
            return $DB->get_manager()->table_exists(new \xmldb_table($tablename));
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function load_xmldb_classes(): void {
        global $CFG;
        if (class_exists('xmldb_table')) {
            return;
        }
        require_once($CFG->libdir . '/xmldb/xmldb_object.php');
        require_once($CFG->libdir . '/xmldb/xmldb_field.php');
        require_once($CFG->libdir . '/xmldb/xmldb_key.php');
        require_once($CFG->libdir . '/xmldb/xmldb_index.php');
        require_once($CFG->libdir . '/xmldb/xmldb_table.php');
    }

    private static function ensure_fields($dbman, \xmldb_table $existingtable, string $name, array &$result): void {
        $desired = self::{'table_' . $name}();
        foreach ($desired->getFields() as $field) {
            try {
                if (!$dbman->field_exists($existingtable, $field)) {
                    $dbman->add_field($existingtable, $field);
                    $result['created'][] = $existingtable->getName() . '.' . $field->getName();
                }
            } catch (\Throwable $e) {
                $result['errors'][] = $existingtable->getName() . '.' . $field->getName() . ': ' . $e->getMessage();
            }
        }
    }

    private static function table_msg(): \xmldb_table {
        $table = new \xmldb_table('block_ai_tutor_msg');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('provider', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('model', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('tokens_used', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        return $table;
    }

    private static function table_embed(): \xmldb_table {
        $table = new \xmldb_table('block_ai_tutor_embed');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contenttype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('contentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('embedding_text', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('chunk_index', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
        $table->add_field('documentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('page_start', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('page_end', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('headingpath', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('embedding_vector', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('embedding_model', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('embedding_dimensions', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('embedding_status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'legacy');
        $table->add_field('embedding_time', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('batchid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timeindexed', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('contenttype', XMLDB_INDEX_NOTUNIQUE, ['contenttype']);
        $table->add_index('course_active', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'active']);
        $table->add_index('documentid', XMLDB_INDEX_NOTUNIQUE, ['documentid']);
        $table->add_index('batchid', XMLDB_INDEX_NOTUNIQUE, ['batchid']);
        return $table;
    }

    private static function table_doc(): \xmldb_table {
        $table = new \xmldb_table('block_ai_tutor_doc');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('mimetype', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('filesize', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('versionlabel', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'uploaded');
        $table->add_field('processingphase', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('pagecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('extractedchars', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('markdownfileid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('extractormode', XMLDB_TYPE_CHAR, '30', null, null, null, null);
        $table->add_field('chunkcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('embeddingcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('embeddingmodel', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('lasterror', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeindexed', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('fileid', XMLDB_INDEX_UNIQUE, ['fileid']);
        $table->add_index('course_status', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'status']);
        $table->add_index('course_active', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'active']);
        return $table;
    }

    private static function table_batch(): \xmldb_table {
        $table = new \xmldb_table('block_ai_tutor_batch');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('documentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('batchkey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'building');
        $table->add_field('embeddingmodel', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('dimensions', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('chunkcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lasterror', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeactivated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('batchkey', XMLDB_INDEX_UNIQUE, ['batchkey']);
        $table->add_index('course_document', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'documentid']);
        $table->add_index('document_active', XMLDB_INDEX_NOTUNIQUE, ['documentid', 'active']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        return $table;
    }

    private static function table_webhook_log(): \xmldb_table {
        $table = new \xmldb_table('block_ai_tutor_webhook_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventtype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        return $table;
    }

    private static function table_route_log(): \xmldb_table {
        $table = new \xmldb_table('block_ai_tutor_route_log');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('routekey', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null);
        $table->add_field('routename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('triggerreason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, 'suggested');
        $table->add_field('teachervalidated', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('human_decision', XMLDB_TYPE_CHAR, '60', null, null, null, null);
        $table->add_field('validatedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timevalidated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        return $table;
    }

    private static function table_coursemap(): \xmldb_table {
        $table = new \xmldb_table('block_ai_tutor_coursemap');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionnum', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('modname', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pedagogicaltype', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, 'resource');
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $table->add_index('cmid', XMLDB_INDEX_NOTUNIQUE, ['cmid']);
        $table->add_index('pedagogicaltype', XMLDB_INDEX_NOTUNIQUE, ['pedagogicaltype']);
        $table->add_index('course_type', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'pedagogicaltype']);
        $table->add_index('course_cmid', XMLDB_INDEX_UNIQUE, ['courseid', 'cmid']);
        return $table;
    }
}
