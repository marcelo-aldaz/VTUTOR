<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace block_ai_tutor\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Single source of truth for the distributed VTutor product identity.
 */
final class product_identity {
    /** Moodle build number used by version.php and runtime diagnostics. */
    public const BUILD = 2026082406;

    /** Human-readable release label used by Moodle and VTutor runtime identity. */
    public const RELEASE = 'v10.6.5 STABLE: aislamiento triple y auditoría READ-ONLY';

    /** Publication-safe semantic label for GitHub and Zenodo. */
    public const PUBLIC_VERSION = '10.6.5-stable';
}
