<?php

/**
 * TTS Service - Business logic for Text-to-Speech settings
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

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;

/**
 * Service class for Text-to-Speech settings.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TtsService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Get two-letter language code from language ID.
     *
     * @param int   $lgId       Language ID
     * @param array $langArray  Languages array from langdefs
     *
     * @return string Two-letter language code
     */
    public function getLanguageCode(int $lgId, array $langArray): string
    {
        return getLanguageCode($lgId, $langArray);
    }

    /**
     * Get language ID from two-letter code or BCP 47 tag.
     *
     * @param string $code      Two letters, or four letters separated with caret
     * @param array  $langArray Languages array from langdefs
     *
     * @return int Language ID if found, -1 otherwise.
     */
    public function getLanguageIdFromCode(string $code, array $langArray): int
    {
        $trimmed = substr($code, 0, 2);
        foreach (get_languages() as $language => $language_id) {
            $elem = $langArray[$language];
            if ($elem[0] == $trimmed) {
                return $language_id;
            }
        }
        return -1;
    }

    /**
     * Get language options for TTS form.
     *
     * @param array $langArray Languages array from langdefs
     *
     * @return string HTML-formatted options string
     */
    public function getLanguageOptions(array $langArray): string
    {
        $output = '';
        foreach (get_languages() as $language => $language_id) {
            $languageCode = getLanguageCode($language_id, $langArray);
            $output .= sprintf(
                '<option value="%s">%s</option>',
                $languageCode,
                $language
            );
        }
        return $output;
    }

    /**
     * Get current language code for TTS settings.
     *
     * @param array $langArray Languages array from langdefs
     *
     * @return string Current language code
     */
    public function getCurrentLanguageCode(array $langArray): string
    {
        $lid = (int)Settings::get('currentlanguage');
        return getLanguageCode($lid, $langArray);
    }

    /**
     * Save TTS settings as cookies.
     *
     * @param array $formData Form input data
     *
     * @return void
     */
    public function saveSettings(array $formData): void
    {
        $lgname = $formData['LgName'];
        $prefix = 'tts[' . $lgname;

        $cookie_options = array(
            'expires' => strtotime('+5 years'),
            'path' => '/',
            'samesite' => 'Strict'
        );

        setcookie($prefix . 'Voice]', $formData['LgVoice'], $cookie_options);
        setcookie($prefix . 'Rate]', $formData['LgTTSRate'], $cookie_options);
        setcookie($prefix . 'Pitch]', $formData['LgPitch'], $cookie_options);
    }

    /**
     * Get two-letter language code from language name.
     *
     * @param string $language  Language name (e.g., "English")
     * @param array  $langArray Languages array from langdefs
     *
     * @return string Two-letter language code
     *
     * @deprecated Since 2.9.1-fork, use getLanguageCode with ID instead
     */
    public function getLanguageCodeFromName(string $language, array $langArray): string
    {
        $lg_id = (int)Connection::fetchValue(
            "SELECT LgID as value
            FROM {$this->tbpref}languages
            WHERE LgName = " . Escaping::toSqlSyntax($language)
        );
        return getLanguageCode($lg_id, $langArray);
    }
}
