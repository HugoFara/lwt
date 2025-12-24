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
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter instead
 */

namespace Lwt\Services;

use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;

/**
 * Service class for dictionary link operations.
 *
 * This is a backward-compatibility wrapper.
 * Use DictionaryAdapter directly for new code.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter instead
 */
class DictionaryService
{
    private DictionaryAdapter $adapter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->adapter = new DictionaryAdapter();
    }

    /**
     * Create and verify a dictionary URL link.
     *
     * @param string $u Dictionary URL
     * @param string $t Text that substitutes the 'lwt_term'
     *
     * @return string Dictionary link formatted
     *
     * @deprecated Use DictionaryAdapter::createDictLink() instead
     */
    public static function createTheDictLink(string $u, string $t): string
    {
        return DictionaryAdapter::createDictLink($u, $t);
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
     *
     * @deprecated Use DictionaryAdapter::createDictLinksInEditWin() instead
     */
    public function createDictLinksInEditWin(int $lang, string $word, string $sentctlid, bool $openfirst): string
    {
        return $this->adapter->createDictLinksInEditWin($lang, $word, $sentctlid, $openfirst);
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
     * @deprecated Use DictionaryAdapter::makeOpenDictStr() instead
     */
    public function makeOpenDictStr(string $url, string $txt, bool $popup = false): string
    {
        return $this->adapter->makeOpenDictStr($url, $txt, $popup);
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
     * @deprecated Use DictionaryAdapter::makeOpenDictStrDynSent() instead
     */
    public function makeOpenDictStrDynSent(string $url, string $sentctlid, string $txt, bool $popup = false): string
    {
        return $this->adapter->makeOpenDictStrDynSent($url, $sentctlid, $txt, $popup);
    }

    /**
     * Returns dictionary links formatted as HTML (alternate version).
     *
     * @param int    $lang      Language ID
     * @param string $sentctlid ID of the sentence textarea element
     * @param string $wordctlid ID of the word input element
     *
     * @return string HTML formatted interface
     *
     * @deprecated Use DictionaryAdapter::createDictLinksInEditWin2() instead
     */
    public function createDictLinksInEditWin2(int $lang, string $sentctlid, string $wordctlid): string
    {
        return $this->adapter->createDictLinksInEditWin2($lang, $sentctlid, $wordctlid);
    }

    /**
     * Make dictionary links HTML.
     *
     * @param int    $lang Language ID
     * @param string $word The word to translate
     *
     * @return string HTML formatted links
     *
     * @deprecated Use DictionaryAdapter::makeDictLinks() instead
     */
    public function makeDictLinks(int $lang, string $word): string
    {
        return $this->adapter->makeDictLinks($lang, $word);
    }

    /**
     * Create dictionary links for edit window (version 3).
     *
     * @param int    $lang      Language ID
     * @param string $sentctlid ID of the sentence textarea element
     * @param string $wordctlid ID of the word input element
     *
     * @return string HTML formatted interface
     *
     * @deprecated Use DictionaryAdapter::createDictLinksInEditWin3() instead
     */
    public function createDictLinksInEditWin3(int $lang, string $sentctlid, string $wordctlid): string
    {
        return $this->adapter->createDictLinksInEditWin3($lang, $sentctlid, $wordctlid);
    }

    /**
     * Get dictionary URIs for a language.
     *
     * @param int $langId Language ID
     *
     * @return array{dict1: string, dict2: string, translator: string}
     *
     * @deprecated Use DictionaryAdapter::getLanguageDictionaries() instead
     */
    public function getLanguageDictionaries(int $langId): array
    {
        $dicts = $this->adapter->getLanguageDictionaries($langId);
        return [
            'dict1' => $dicts['dict1'],
            'dict2' => $dicts['dict2'],
            'translator' => $dicts['translator'],
        ];
    }

    /**
     * Get the local dictionary mode for a language.
     *
     * @param int $langId Language ID
     *
     * @return int Mode (0=online only, 1=local first, 2=local only, 3=combined)
     *
     * @deprecated Use DictionaryAdapter::getLocalDictMode() instead
     */
    public function getLocalDictMode(int $langId): int
    {
        return $this->adapter->getLocalDictMode($langId);
    }

    /**
     * Look up a term with local dictionary support.
     *
     * @param int    $langId Language ID
     * @param string $term   Term to look up
     *
     * @return array{local: array, online: array{dict1: string, dict2: string, translator: string}}
     *
     * @deprecated Use DictionaryAdapter::lookupWithLocal() instead
     */
    public function lookupWithLocal(int $langId, string $term): array
    {
        return $this->adapter->lookupWithLocal($langId, $term);
    }
}
