<?php
// This file is part of Moodle - http://moodle.org/.

namespace block_ai_tutor\document;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract for replaceable PDF text extractors.
 */
interface pdf_extractor_interface {
    /**
     * Extract text while preserving page boundaries.
     *
     * @param \stored_file $file
     * @return array{pages: array<int, string>, pagecount: int, extractedchars: int, pendingocr: bool, extractor: string}
     */
    public function extract(\stored_file $file): array;
}
