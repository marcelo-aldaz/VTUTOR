<?php
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
            'timecreated' => 'privacy:metadata:block_ai_tutor_msg:timecreated',
        ], 'privacy:metadata:block_ai_tutor_msg');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT c.id
                  FROM {context} c
                  JOIN {course} co ON co.id = c.instanceid
                  JOIN {block_ai_tutor_msg} m ON m.courseid = co.id
                 WHERE c.contextlevel = :contextlevel
                   AND m.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB, $USER;

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

        $params = ['userid' => $USER->id] + $courseparams;

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
                'time' => userdate($message->timecreated, get_string('strftimedatetime')),
            ]);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $DB->delete_records('block_ai_tutor_msg', ['courseid' => $context->instanceid]);
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
        }
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_course) {
            return;
        }

        $sql = "SELECT DISTINCT userid
                  FROM {block_ai_tutor_msg}
                 WHERE courseid = :courseid";

        $params = ['courseid' => $context->instanceid];
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
        }
    }
}