<?php
// blocks/ai_tutor/db/tasks.php
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'block_ai_tutor\task\cleanup_old_messages',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
];