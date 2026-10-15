<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
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
    [
        'classname' => 'block_ai_tutor\task\cleanup_old_index_batches',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
];
