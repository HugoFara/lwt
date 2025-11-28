<?php

/**
 * \file
 * \brief Text and sentence processing helper functions.
 *
 * This file serves as the main entry point for text processing functionality.
 * It includes all the specialized modules for different aspects of text handling.
 *
 * The functionality has been migrated to Service classes:
 * - TextStatisticsService: Word count and text statistics functions
 * - SentenceService: Sentence retrieval and formatting
 * - AnnotationService: Text annotations for print view
 * - SimilarTermsService: Similar terms calculation
 * - TextNavigationService: Navigation between texts
 * - TextParsingService: Text parsing utilities
 * - TextReadingService: Reading view display functions
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
 * @since   3.0.0 Migrated to Service classes
 */

// Text Statistics Service (word counts, todo words)
require_once __DIR__ . '/../../Services/TextStatisticsService.php';

// Sentence Service (find, format, display sentences)
require_once __DIR__ . '/../../Services/SentenceService.php';

// Annotation Service (create, save, update annotations)
require_once __DIR__ . '/../../Services/AnnotationService.php';

// Similar Terms Service
require_once __DIR__ . '/../../Services/SimilarTermsService.php';

// Text Navigation Service
require_once __DIR__ . '/../../Services/TextNavigationService.php';

// Text Parsing Service
require_once __DIR__ . '/../../Services/TextParsingService.php';

// Expression handling (multi-words, MeCab)
require_once __DIR__ . '/../../Services/ExpressionService.php';

// Database operations (backup, restore, truncate)
require_once __DIR__ . '/../database_operations.php';

// Media players (audio, video)
require_once __DIR__ . '/../Media/media_players.php';

// Language services (utilities, phonetic reading, definitions)
require_once __DIR__ . '/../../Services/LanguageService.php';
require_once __DIR__ . '/../../Services/LanguageDefinitions.php';
