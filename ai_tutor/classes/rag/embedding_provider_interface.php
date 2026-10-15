<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\rag;

defined('MOODLE_INTERNAL') || die();

interface embedding_provider_interface {
    /**
     * @param array<int, string> $texts
     * @return array<int, array<int|string, float>> Dense or sparse numeric vectors.
     */
    public function embed(array $texts): array;

    public function get_model(): string;
}
