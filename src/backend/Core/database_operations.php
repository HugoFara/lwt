<?php

/**
 * \file
 * \brief Database backup and restore operations.
 *
 * This file is a backward-compatibility shim. New code should use
 * the Lwt\Database\Restore class directly.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since    3.0.0 Split from text_helpers.php
 * @deprecated 3.0.0 Use Lwt\Database\Restore class instead
 */

require_once __DIR__ . '/Database/Restore.php';
require_once __DIR__ . '/../Services/TagService.php';

use Lwt\Database\Restore;

/**
 * Restore the database from a file.
 *
 * @param resource $handle Backup file handle
 * @param string   $title  File title
 *
 * @return string Human-readable status message
 *
 * @deprecated 3.0.0 Use Restore::restoreFile() instead
 */
function restore_file($handle, string $title): string
{
    return Restore::restoreFile($handle, $title);
}

/**
 * Truncate the database, remove all data belonging by the current user.
 *
 * Keep settings.
 *
 * @deprecated 3.0.0 Use Restore::truncateUserDatabase() instead
 */
function truncateUserDatabase(): void
{
    Restore::truncateUserDatabase();
}
