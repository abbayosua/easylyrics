<?php

/**
 * EasyLyrics — MySQL configuration.
 * Copy to config.php (gitignored) and adjust credentials for your machine.
 * Env vars (EASILYRICS_DB_*) override defaults — dipakai oleh E2E test
 * agar memakai database terpisah (easilyrics_test).
 */

define('DB_HOST', getenv('EASILYRICS_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int) (getenv('EASILYRICS_DB_PORT') ?: 3306));
define('DB_NAME', getenv('EASILYRICS_DB_NAME') ?: 'easilyrics');
define('DB_USER', getenv('EASILYRICS_DB_USER') ?: 'root');
define('DB_PASS', getenv('EASILYRICS_DB_PASS') ?: '');
