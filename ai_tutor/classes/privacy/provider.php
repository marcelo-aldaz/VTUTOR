<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// blocks/ai_tutor/classes/privacy.php
namespace block_ai_tutor\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements \core_privacy\local\metadata\provider,
                         \core_privacy\local\request\plugin\provider,
                         \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('block_ai_tutor_msg', [
            'courseid' => 'privacy:metadata:block_ai_tutor_msg:courseid',
            'userid' => 'privacy:metadata:block_ai_tutor_msg:userid',
            'message' => 'privacy:metadata:block_ai_tutor_msg:message',
            'response' => 'privacy:metadata:block_ai_tutor_msg:response',
            'provider' => 'privacy:metadata:block_ai_tutor_msg:provider',
            'model' => 'privacy:metadata:block_ai_tutor_msg:model',
            'tokens_used' => 'privacy:metadata:block_ai_tutor_msg:tokens_used',
            'timecreated' => 'privacy:metadata:block_ai_tutor_msg:timecreated',
        ], 'privacy:metadata:block_ai_tutor_msg');

        $collection->add_database_table('block_ai_tutor_route_log', [
            'courseid' => 'privacy:metadata:block_ai_tutor_route_log:courseid',
            'userid' => 'privacy:metadata:block_ai_tutor_route_log:userid',
            'routekey' => 'privacy:metadata:block_ai_tutor_route_log:routekey',
            'routename' => 'privacy:metadata:block_ai_tutor_route_log:routename',
            'status' => 'privacy:metadata:block_ai_tutor_route_log:status',
            'teachervalidated' => 'privacy:metadata:block_ai_tutor_route_log:teachervalidated',
            'human_decision' => 'privacy:metadata:block_ai_tutor_route_log:human_decision',
            'triggerreason' => 'privacy:metadata:block_ai_tutor_route_log:triggerreason',
            'validatedby' => 'privacy:metadata:block_ai_tutor_route_log:validatedby',
            'timevalidated' => 'privacy:metadata:block_ai_tutor_route_log:timevalidated',
            'timecreated' => 'privacy:metadata:block_ai_tutor_route_log:timecreated',
            'timemodified' => 'privacy:metadata:block_ai_tutor_route_log:timemodified',
        ], 'privacy:metadata:block_ai_tutor_route_log');

        $collection->add_external_location_link('ai_provider', [
            'message' => 'privacy:metadata:ai_provider:message',
            'conversationhistory' => 'privacy:metadata:ai_provider:conversationhistory',
            'coursecontext' => 'privacy:metadata:ai_provider:coursecontext',
            'retrievedcontext' => 'privacy:metadata:ai_provider:retrievedcontext',
            'userprofile' => 'privacy:metadata:ai_provider:userprofile',
        ], 'privacy:metadata:ai_provider');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course} co ON co.id = c.instanceid
                  JOIN {block_ai_tutor_msg} m ON m.courseid = co.id
                 WHERE c.contextlevel = :contextlevel
                   AND m.userid = :userid
                UNION
                SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course} co ON co.id = c.instanceid
                  JOIN {block_ai_tutor_route_log} r ON r.courseid = co.id
                 WHERE c.contextlevel = :contextlevel2
                   AND (r.userid = :userid2 OR r.validatedby = :validatorid)";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
            'contextlevel2' => CONTEXT_COURSE,
            'userid2' => $userid,
            'validatorid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $courseids = [];
        foreach ($contextlist->get_contexts() as $context) {
            $courseids[] = $context->instanceid;
        }

        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);

        $sql = "SELECT m.*
                  FROM {block_ai_tutor_msg} m
                 WHERE m.userid = :userid
                   AND m.courseid {$coursesql}
              ORDER BY m.timecreated ASC";

        $params = ['userid' => $contextlist->get_user()->id] + $courseparams;

        $messages = $DB->get_records_sql($sql, $params);

        foreach ($messages as $message) {
            $context = \context_course::instance($message->courseid);
            writer::with_context($context)->export_data([
                'vtutor',
                'chat',
                userdate($message->timecreated, '%Y-%m-%d'),
            ], (object)[
                'message' => $message->message,
                'response' => $message->response,
                'provider' => $message->provider,
                'model' => $message->model,
                'tokens_used' => $message->tokens_used,
                'time' => userdate($message->timecreated, get_string('strftimedatetime')),
            ]);
        }

        $sql = "SELECT r.*
                  FROM {block_ai_tutor_route_log} r
                 WHERE r.userid = :userid
                   AND r.courseid {$coursesql}
              ORDER BY r.timecreated ASC";
        $routes = $DB->get_records_sql($sql, $params);
        foreach ($routes as $route) {
            $context = \context_course::instance($route->courseid);
            writer::with_context($context)->export_data([
                'vtutor',
                'rutas_pedagogicas',
                userdate($route->timecreated, '%Y-%m-%d'),
            ], (object)[
                'route' => $route->routename,
                'routekey' => $route->routekey,
                'status' => $route->status,
                'teacher_validated' => (int)$route->teachervalidated,
                'human_decision' => $route->human_decision,
                'triggerreason' => $route->triggerreason,
                'time' => userdate($route->timecreated, get_string('strftimedatetime')),
            ]);
        }

        $validatorsql = "SELECT r.*
                           FROM {block_ai_tutor_route_log} r
                          WHERE r.validatedby = :validatorid
                            AND r.courseid {$coursesql}
                       ORDER BY r.timevalidated ASC";
        $validatorparams = ['validatorid' => $contextlist->get_user()->id] + $courseparams;
        $validations = $DB->get_records_sql($validatorsql, $validatorparams);
        foreach ($validations as $validation) {
            $context = \context_course::instance($validation->courseid);
            writer::with_context($context)->export_data([
                'vtutor',
                'validaciones_humanas',
                (string)$validation->id,
            ], (object)[
                'route' => $validation->routename,
                'decision' => $validation->human_decision,
                'time' => $validation->timevalidated
                    ? userdate($validation->timevalidated, get_string('strftimedatetime'))
                    : null,
            ]);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records('block_ai_tutor_msg', ['courseid' => $context->instanceid]);
        $DB->delete_records('block_ai_tutor_route_log', ['courseid' => $context->instanceid]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $DB->delete_records('block_ai_tutor_msg', [
                'courseid' => $context->instanceid,
                'userid' => $contextlist->get_user()->id,
            ]);
            $DB->delete_records('block_ai_tutor_route_log', [
                'courseid' => $context->instanceid,
                'userid' => $contextlist->get_user()->id,
            ]);
            $DB->set_field('block_ai_tutor_route_log', 'validatedby', null, [
                'courseid' => $context->instanceid,
                'validatedby' => $contextlist->get_user()->id,
            ]);
        }
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $sql = "SELECT DISTINCT userid
                  FROM {block_ai_tutor_msg}
                 WHERE courseid = :courseid
                UNION
                SELECT DISTINCT userid
                  FROM {block_ai_tutor_route_log}
                 WHERE courseid = :courseid2
                UNION
                SELECT DISTINCT validatedby AS userid
                  FROM {block_ai_tutor_route_log}
                 WHERE courseid = :courseid3
                   AND validatedby IS NOT NULL";

        $params = [
            'courseid' => $context->instanceid,
            'courseid2' => $context->instanceid,
            'courseid3' => $context->instanceid,
        ];
        $userlist->add_from_sql('userid', $sql, $params);
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            $DB->delete_records('block_ai_tutor_msg', [
                'courseid' => $context->instanceid,
                'userid' => $userid,
            ]);
            $DB->delete_records('block_ai_tutor_route_log', [
                'courseid' => $context->instanceid,
                'userid' => $userid,
            ]);
            $DB->set_field('block_ai_tutor_route_log', 'validatedby', null, [
                'courseid' => $context->instanceid,
                'validatedby' => $userid,
            ]);
        }
    }
}