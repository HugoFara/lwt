<?php

/**
 * \file
 * \brief Define all the supported languages.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    2.9.1 Use LWT_LANGUAGES_ARRAY instead of $langDefs
 * @since    3.0.0 Load language definitions from JSON file
 */

/**
 * Load language definitions from JSON file and convert to legacy array format.
 *
 * @return array<string, array{0: string, 1: string, 2: bool, 3: string, 4: string, 5: bool, 6: bool, 7: bool}>
 */
function loadLanguageDefinitions(): array
{
    $jsonPath = __DIR__ . '/langdefs.json';
    $jsonContent = file_get_contents($jsonPath);
    if ($jsonContent === false) {
        throw new RuntimeException("Could not read language definitions from $jsonPath");
    }

    $languages = json_decode($jsonContent, true);
    if ($languages === null) {
        throw new RuntimeException("Invalid JSON in language definitions file");
    }

    // Convert from object format to legacy indexed array format
    $result = [];
    foreach ($languages as $name => $props) {
        $result[$name] = [
            $props['glosbeIso'],
            $props['googleIso'],
            $props['biggerFont'],
            $props['wordCharRegExp'],
            $props['sentSplRegExp'],
            $props['makeCharacterWord'],
            $props['removeSpaces'],
            $props['rightToLeft']
        ];
    }

    return $result;
}

/**
 * Associative array for all the supported languages
 *
 * @var array LWT_LANGUAGES_ARRAY
 *
 * "Name" =>
 *  (0: "glosbeIso", 1: "googleIso", 2: biggerFont, 3: "wordCharRegExp",
 *  4: "sentSplRegExp", 5: makeCharacterWord, 6: removeSpaces, 7: rightToLeft)
 */
define('LWT_LANGUAGES_ARRAY', loadLanguageDefinitions());
