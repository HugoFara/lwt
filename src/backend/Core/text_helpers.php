<?php

/**
 * \file
 * \brief Text and sentence processing helper functions.
 *
 * This file serves as the main entry point for text processing functionality.
 * It includes all the specialized modules for different aspects of text handling.
 *
 * The functionality has been split into the following focused modules:
 * - text_statistics.php: Word count and text statistics functions
 * - sentence_operations.php: Sentence retrieval and formatting
 * - language_utilities.php: Language information and configuration
 * - expression_handling.php: Multi-word expressions and MeCab integration
 * - annotation_management.php: Text annotations for print view
 * - database_operations.php: Backup and restore functionality
 * - media_players.php: Audio and video player generation
 * - phonetic_reading.php: Phonetic text conversion (Japanese/MeCab)
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   3.0.0 Split from session_utility.php
 * @since   3.0.0 Refactored into smaller focused modules
 */

// Text statistics (word counts, todo words)
require_once __DIR__ . '/text_statistics.php';

// Sentence operations (find, format, display sentences)
require_once __DIR__ . '/sentence_operations.php';

// Language utilities (get language info, codes, direction)
require_once __DIR__ . '/language_utilities.php';

// Expression handling (multi-words, MeCab)
require_once __DIR__ . '/expression_handling.php';

// Annotation management (create, save, update annotations)
require_once __DIR__ . '/annotation_management.php';

// Database operations (backup, restore, truncate)
require_once __DIR__ . '/database_operations.php';

// Media players (audio, video)
require_once __DIR__ . '/media_players.php';

// Phonetic reading (Japanese/MeCab)
require_once __DIR__ . '/phonetic_reading.php';
