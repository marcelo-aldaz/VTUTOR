<?php
// This file is part of Moodle - http://moodle.org/.

defined('MOODLE_INTERNAL') || die();

$definitions = [
    'semantic_vectors' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 86400,
    ],
    'semantic_rerank' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 21600,
    ],
];
