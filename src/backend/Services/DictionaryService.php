<?php declare(strict_types=1);
/**
 * Dictionary Service - Dictionary link generation utilities
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Database\QueryBuilder;

/**
 * Service class for dictionary link operations.
 *
 * Functions for creating and formatting dictionary lookup URLs.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class DictionaryService
{
    /**
     * Create and verify a dictionary URL link.
     *
     * Case 1: url without lwt_term: append UTF-8-term
     * Case 2: url with lwt_term: substitute UTF-8-term
     *
     * @param string $u Dictionary URL. It may contain 'lwt_term' that will get parsed
     * @param string $t Text that substitutes the 'lwt_term'
     *
     * @return string Dictionary link formatted
     *
     * @since 2.7.0-fork Use "lwt_term" placeholder
     * @since 3.1.0 Removed support for deprecated "###" placeholder
     */
    public static function createTheDictLink(string $u, string $t): string
    {
        $url = trim($u);
        $trm = trim($t);
        $encodedTrm = $trm === '' ? '+' : urlencode($trm);

        // Check for lwt_term placeholder
        if (str_contains($url, 'lwt_term')) {
            return str_replace('lwt_term', $encodedTrm, $url);
        }

        // No placeholder found - append term to URL
        return $url . $encodedTrm;
    }

    /**
     * Returns dictionary links formatted as HTML.
     *
     * @param int    $lang      Language ID
     * @param string $word      Word to look up
     * @param string $sentctlid ID of the sentence textarea element
     * @param bool   $openfirst True if we should open right frames with translation first
     *
     * @return string HTML-formatted interface
     */
    public function createDictLinksInEditWin(int $lang, string $word, string $sentctlid, bool $openfirst): string
    {
        $record = QueryBuilder::table('languages')
            ->select([
                'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
                'LgDict1PopUp', 'LgDict2PopUp', 'LgGoogleTranslatePopUp'
            ])
            ->where('LgID', '=', $lang)
            ->firstPrepared();
        $wb1 = isset($record['LgDict1URI']) ? (string) $record['LgDict1URI'] : "";
        $wb2 = isset($record['LgDict2URI']) ? (string) $record['LgDict2URI'] : "";
        $wb3 = isset($record['LgGoogleTranslateURI']) ?
        (string) $record['LgGoogleTranslateURI'] : "";
        $popup1 = (bool) ($record['LgDict1PopUp'] ?? false);
        $popup2 = (bool) ($record['LgDict2PopUp'] ?? false);
        $popup3 = (bool) ($record['LgGoogleTranslatePopUp'] ?? false);

        $r = '';
        $dictUrl = self::createTheDictLink($wb1, $word);
        if ($openfirst) {
            $action = $popup1 ? 'dict-auto-popup' : 'dict-auto-frame';
            $r .= '<span class="dict-auto-init" data-action="' . $action . '" data-url="' .
            htmlspecialchars($dictUrl, ENT_QUOTES, 'UTF-8') . '"></span>';
        }
        $r .= 'Lookup Term: ';
        $r .= $this->makeOpenDictStr($dictUrl, "Dict1", $popup1);
        if ($wb2 != "") {
            $r .= $this->makeOpenDictStr(self::createTheDictLink($wb2, $word), "Dict2", $popup2);
        }
        if ($wb3 != "") {
            $r .= $this->makeOpenDictStr(self::createTheDictLink($wb3, $word), "Translator", $popup3) .
            ' | ' .
            $this->makeOpenDictStrDynSent($wb3, $sentctlid, "Translate sentence", $popup3);
        }
        return $r;
    }

    /**
     * Create a dictionary open URL HTML element.
     *
     * @param string $url   The dictionary URL
     * @param string $txt   Clickable text to display
     * @param bool   $popup Whether to open in popup window
     *
     * @return string HTML-formatted string
     *
     * @since 3.1.0 Removed asterisk prefix and lwt_popup URL detection, added $popup parameter
     */
    public function makeOpenDictStr(string $url, string $txt, bool $popup = false): string
    {
        if ($url === '' || $txt === '') {
            return '';
        }
        if ($popup) {
            return ' <span class="click" data-action="dict-popup" data-url="' .
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' .
            htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') .
            '</span> ';
        }
        return ' <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') .
        '" target="ru" data-action="dict-frame">' .
        htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</a> ';
    }

    /**
     * Create a dictionary open URL HTML element for dynamic sentence translation.
     *
     * @param string $url       Translator URL
     * @param string $sentctlid ID of the textarea element containing the sentence
     * @param string $txt       Clickable text to display
     * @param bool   $popup     Whether to open in popup window
     *
     * @return string HTML-formatted string
     *
     * @since 2.7.0-fork Supports LibreTranslate
     * @since 3.1.0 Removed asterisk prefix and lwt_popup URL detection, added $popup parameter
     */
    public function makeOpenDictStrDynSent(string $url, string $sentctlid, string $txt, bool $popup = false): string
    {
        if ($url === '') {
            return '';
        }
        $parsed_url = parse_url($url);
        if ($parsed_url === false) {
            $parsed_url = parse_url('http://' . $url);
        }
        // Handle ggl.php translator
        if (
            str_starts_with($url, "ggl.php")
            || str_ends_with($parsed_url['path'] ?? '', "/ggl.php")
        ) {
            $url = str_replace('?', '?sent=1&', $url);
        }
        $action = $popup ? 'translate-sentence-popup' : 'translate-sentence';
        return '<span class="click" data-action="' . $action . '" ' .
        'data-url="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-sentctl="' . htmlspecialchars($sentctlid, ENT_QUOTES, 'UTF-8') . '">' .
        htmlspecialchars($txt, ENT_QUOTES, 'UTF-8') . '</span>';
    }

    /**
     * Returns dictionary links formatted as HTML (alternate version).
     *
     * Always opens in popup mode.
     *
     * @param int    $lang      Language ID
     * @param string $sentctlid ID of the sentence textarea element
     * @param string $wordctlid ID of the word input element
     *
     * @return string HTML formatted interface
     */
    public function createDictLinksInEditWin2(int $lang, string $sentctlid, string $wordctlid): string
    {
        $record = QueryBuilder::table('languages')
            ->select(['LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI'])
            ->where('LgID', '=', $lang)
            ->firstPrepared();
        $wb1 = (string) ($record['LgDict1URI'] ?? '');
        $wb2 = (string) ($record['LgDict2URI'] ?? '');
        $wb3 = (string) ($record['LgGoogleTranslateURI'] ?? '');

        $r = 'Lookup Term:
        <span class="click" data-action="translate-word-popup" ' .
        'data-url="' . htmlspecialchars($wb1, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">Dict1</span> ';
        if ($wb2 !== '') {
            $r .= '<span class="click" data-action="translate-word-popup" ' .
            'data-url="' . htmlspecialchars($wb2, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">Dict2</span> ';
        }
        if ($wb3 !== '') {
            $sent_mode = str_starts_with($wb3, 'ggl.php') ||
            str_ends_with(parse_url($wb3, PHP_URL_PATH) ?? '', '/ggl.php');
            $sentUrl = $sent_mode ? str_replace('?', '?sent=1&', $wb3) : $wb3;
            $r .= '<span class="click" data-action="translate-word-popup" ' .
            'data-url="' . htmlspecialchars($wb3, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">Translator</span>
             | <span class="click" data-action="translate-sentence-popup" ' .
            'data-url="' . htmlspecialchars($sentUrl, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-sentctl="' . htmlspecialchars($sentctlid, ENT_QUOTES, 'UTF-8') . '">Translate sentence</span>';
        }
        return $r;
    }

    /**
     * Make dictionary links HTML.
     *
     * @param int    $lang Language ID
     * @param string $word The word to translate
     *
     * @return string HTML formatted links
     */
    public function makeDictLinks(int $lang, string $word): string
    {
        $record = QueryBuilder::table('languages')
            ->select(['LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI'])
            ->where('LgID', '=', $lang)
            ->firstPrepared();
        $wb1 = (string) ($record['LgDict1URI'] ?? '');
        $wb2 = (string) ($record['LgDict2URI'] ?? '');
        $wb3 = (string) ($record['LgGoogleTranslateURI'] ?? '');
        $escapedWord = htmlspecialchars($word, ENT_QUOTES, 'UTF-8');
        $r = '<span class="smaller">';
        $r .= '<span class="click" data-action="translate-word-direct" ' .
        'data-url="' . htmlspecialchars($wb1, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-word="' . $escapedWord . '">[1]</span> ';
        if ($wb2 !== '') {
            $r .= '<span class="click" data-action="translate-word-direct" ' .
            'data-url="' . htmlspecialchars($wb2, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-word="' . $escapedWord . '">[2]</span> ';
        }
        if ($wb3 !== '') {
            $r .= '<span class="click" data-action="translate-word-direct" ' .
            'data-url="' . htmlspecialchars($wb3, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-word="' . $escapedWord . '">[G]</span>';
        }
        $r .= '</span>';
        return $r;
    }

    /**
     * Create dictionary links for edit window (version 3).
     *
     * @param int    $lang      Language ID
     * @param string $sentctlid ID of the sentence textarea element
     * @param string $wordctlid ID of the word input element
     *
     * @return string HTML formatted interface
     */
    public function createDictLinksInEditWin3(int $lang, string $sentctlid, string $wordctlid): string
    {
        $record = QueryBuilder::table('languages')
            ->select([
                'LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI',
                'LgDict1PopUp', 'LgDict2PopUp', 'LgGoogleTranslatePopUp'
            ])
            ->where('LgID', '=', $lang)
            ->firstPrepared();

        $wb1 = (string) ($record['LgDict1URI'] ?? '');
        $wb2 = (string) ($record['LgDict2URI'] ?? '');
        $wb3 = (string) ($record['LgGoogleTranslateURI'] ?? '');
        $popup1 = (bool) ($record['LgDict1PopUp'] ?? false);
        $popup2 = (bool) ($record['LgDict2PopUp'] ?? false);
        $popup3 = (bool) ($record['LgGoogleTranslatePopUp'] ?? false);

        // Handle ggl.php translator for sentence mode
        $parsed_url = parse_url($wb3);
        if ($wb3 !== '' && $parsed_url === false) {
            $parsed_url = parse_url('http://' . $wb3);
        }
        $sentUrl = (str_ends_with($parsed_url['path'] ?? '', "/ggl.php")) ?
            str_replace('?', '?sent=1&', $wb3) : $wb3;

        $r = '';
        $r .= 'Lookup Term: ';
        $action1 = $popup1 ? 'translate-word-popup' : 'translate-word';
        $r .= '<span class="click" data-action="' . $action1 . '" ' .
        'data-url="' . htmlspecialchars($wb1, ENT_QUOTES, 'UTF-8') . '" ' .
        'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">
        Dict1</span> ';
        if ($wb2 !== '') {
            $action2 = $popup2 ? 'translate-word-popup' : 'translate-word';
            $r .= '<span class="click" data-action="' . $action2 . '" ' .
            'data-url="' . htmlspecialchars($wb2, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">
            Dict2</span> ';
        }
        if ($wb3 !== '') {
            $action3 = $popup3 ? 'translate-word-popup' : 'translate-word';
            $action4 = $popup3 ? 'translate-sentence-popup' : 'translate-sentence';
            $r .= '<span class="click" data-action="' . $action3 . '" ' .
            'data-url="' . htmlspecialchars($wb3, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-wordctl="' . htmlspecialchars($wordctlid, ENT_QUOTES, 'UTF-8') . '">
            Translator</span> |
            <span class="click" data-action="' . $action4 . '" ' .
            'data-url="' . htmlspecialchars($sentUrl, ENT_QUOTES, 'UTF-8') . '" ' .
            'data-sentctl="' . htmlspecialchars($sentctlid, ENT_QUOTES, 'UTF-8') . '">
            Translate sentence</span>';
        }
        return $r;
    }

    /**
     * Get dictionary URIs for a language.
     *
     * @param int $langId Language ID
     *
     * @return array{dict1: string, dict2: string, translator: string}
     */
    public function getLanguageDictionaries(int $langId): array
    {
        $record = QueryBuilder::table('languages')
            ->select(['LgDict1URI', 'LgDict2URI', 'LgGoogleTranslateURI'])
            ->where('LgID', '=', $langId)
            ->firstPrepared();

        return [
            'dict1' => $record['LgDict1URI'] ?? '',
            'dict2' => $record['LgDict2URI'] ?? '',
            'translator' => $record['LgGoogleTranslateURI'] ?? '',
        ];
    }

    /**
     * Get the local dictionary mode for a language.
     *
     * @param int $langId Language ID
     *
     * @return int Mode (0=online only, 1=local first, 2=local only, 3=combined)
     */
    public function getLocalDictMode(int $langId): int
    {
        $record = QueryBuilder::table('languages')
            ->select(['LgLocalDictMode'])
            ->where('LgID', '=', $langId)
            ->firstPrepared();

        return (int) ($record['LgLocalDictMode'] ?? 0);
    }

    /**
     * Look up a term with local dictionary support.
     *
     * Based on the language's local dictionary mode:
     * - 0: Online only (returns online dictionary URLs)
     * - 1: Local first, fallback to online if no results
     * - 2: Local only (no online lookups)
     * - 3: Combined (show both local and online results)
     *
     * @param int    $langId Language ID
     * @param string $term   Term to look up
     *
     * @return array{local: array, online: array{dict1: string, dict2: string, translator: string}}
     */
    public function lookupWithLocal(int $langId, string $term): array
    {
        $mode = $this->getLocalDictMode($langId);
        $localService = new LocalDictionaryService();

        $results = [
            'local' => [],
            'online' => ['dict1' => '', 'dict2' => '', 'translator' => ''],
        ];

        // Modes 1, 2, 3 include local lookup
        if ($mode >= 1) {
            $results['local'] = $localService->lookup($langId, $term);
        }

        // Modes 0, 1, 3 include online dictionaries
        // Mode 1: only include online if local found nothing
        if ($mode === 0 || $mode === 3 || ($mode === 1 && empty($results['local']))) {
            $results['online'] = $this->getLanguageDictionaries($langId);
        }

        return $results;
    }
}
