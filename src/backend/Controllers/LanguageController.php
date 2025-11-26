<?php

/**
 * \file
 * \brief Language Controller - Language configuration
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-languagecontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

/**
 * Controller for language configuration.
 *
 * Handles:
 * - Language listing and management
 * - Language pair selection
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class LanguageController extends BaseController
{
    /**
     * Languages index page (replaces language_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        include __DIR__ . '/../Legacy/language_edit.php';
    }

    /**
     * Select language pair page (replaces language_select_pair.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function selectPair(array $params): void
    {
        include __DIR__ . '/../Legacy/language_select_pair.php';
    }
}
