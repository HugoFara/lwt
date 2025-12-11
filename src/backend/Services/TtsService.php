<?php declare(strict_types=1);
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
use Lwt\Core\Http\InputValidator;
use Lwt\Database\Connection;
use Lwt\Database\Settings;

require_once __DIR__ . '/../Core/Http/InputValidator.php';
require_once __DIR__ . '/LanguageService.php';

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
     * Language service instance.
     *
     * @var LanguageService
     */
    private LanguageService $languageService;

    /**
     * Constructor - initialize language service.
     */
    public function __construct()
    {
        $this->languageService = new LanguageService();
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
        return $this->languageService->getLanguageCode($lgId, $langArray);
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
        foreach ($this->languageService->getAllLanguages() as $language => $language_id) {
            if (!isset($langArray[$language])) {
                continue;
            }
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
        foreach ($this->languageService->getAllLanguages() as $language => $language_id) {
            $languageCode = $this->languageService->getLanguageCode($language_id, $langArray);
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
        return $this->languageService->getLanguageCode($lid, $langArray);
    }

    /**
     * Save TTS settings as cookies.
     *
     * @return void
     */
    public function saveSettings(): void
    {
        $lgname = InputValidator::getString('LgName');
        $prefix = 'tts[' . $lgname;

        $cookie_options = array(
            'expires' => strtotime('+5 years'),
            'path' => '/',
            'samesite' => 'Strict'
        );

        setcookie($prefix . 'Voice]', InputValidator::getString('LgVoice'), $cookie_options);
        setcookie($prefix . 'Rate]', InputValidator::getString('LgTTSRate'), $cookie_options);
        setcookie($prefix . 'Pitch]', InputValidator::getString('LgPitch'), $cookie_options);
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
        $lg_id = (int)Connection::preparedFetchValue(
            "SELECT LgID as value
            FROM " . Globals::getTablePrefix() . "languages
            WHERE LgName = ?",
            [$language]
        );
        return $this->languageService->getLanguageCode($lg_id, $langArray);
    }
}
