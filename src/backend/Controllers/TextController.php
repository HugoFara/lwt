<?php

/**
 * \file
 * \brief Text Controller - Text management and reading
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @author  HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-textcontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

/**
 * Controller for text management and reading interface.
 *
 * Handles:
 * - Text reading interface
 * - Text CRUD operations
 * - Text display/print modes
 * - Archived texts
 */
class TextController extends BaseController
{
    /**
     * Read text interface (replaces text_read.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function read(array $params): void
    {
        include __DIR__ . '/../Legacy/text_read.php';
    }

    /**
     * Edit texts list (replaces text_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        include __DIR__ . '/../Legacy/text_edit.php';
    }

    /**
     * Display improved text (replaces text_display.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function display(array $params): void
    {
        include __DIR__ . '/../Legacy/text_display.php';
    }

    /**
     * Print text (replaces text_print.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function printText(array $params): void
    {
        include __DIR__ . '/../Legacy/text_print.php';
    }

    /**
     * Print plain text (replaces text_print_plain.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function printPlain(array $params): void
    {
        include __DIR__ . '/../Legacy/text_print_plain.php';
    }

    /**
     * Import long text (replaces text_import_long.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function importLong(array $params): void
    {
        include __DIR__ . '/../Legacy/text_import_long.php';
    }

    /**
     * Set text mode (replaces text_set_mode.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function setMode(array $params): void
    {
        include __DIR__ . '/../Legacy/text_set_mode.php';
    }

    /**
     * Check text (replaces text_check.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function check(array $params): void
    {
        include __DIR__ . '/../Legacy/text_check.php';
    }

    /**
     * Archived texts (replaces text_archived.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function archived(array $params): void
    {
        include __DIR__ . '/../Legacy/text_archived.php';
    }
}
