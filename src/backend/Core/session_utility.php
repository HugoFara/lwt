<?php

/**
 * \file
 * \brief All the files needed for a LWT session.
 *
 * By requiring this file, you start a session, connect to the
 * database and declare a lot of useful functions.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   2.0.3-fork
 * @deprecated 3.0.0 This file is being phased out. Use direct imports instead:
 *             - database_connect.php for database functions
 *             - Tag/tags.php for tag functions (including getWordTagList)
 *             - UI/ui_helpers.php for UI functions
 *             - Export/export_helpers.php for export functions
 *             - Text/text_helpers.php for text processing
 *             - Http/param_helpers.php for parameter handling
 *             - Utils/string_utilities.php for string utilities
 *             - Media/media_helpers.php for media functions
 *             - Text/text_navigation.php for text navigation
 *             - Word/dictionary_links.php for dictionary links
 *             - Test/test_helpers.php for test helpers
 *             - Feed/feeds.php for feed functions
 */

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/Feed/feeds.php';
require_once __DIR__ . '/Tag/tags.php';
require_once __DIR__ . '/UI/ui_helpers.php';
require_once __DIR__ . '/Export/export_helpers.php';
require_once __DIR__ . '/Text/text_helpers.php';

// Split modules for focused functionality (since 3.0.0-fork)
require_once __DIR__ . '/Http/param_helpers.php';
require_once __DIR__ . '/Utils/string_utilities.php';
require_once __DIR__ . '/Media/media_helpers.php';
require_once __DIR__ . '/Text/text_navigation.php';
require_once __DIR__ . '/Word/dictionary_links.php';
require_once __DIR__ . '/Test/test_helpers.php';
