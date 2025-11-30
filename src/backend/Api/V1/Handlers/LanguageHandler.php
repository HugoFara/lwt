<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Database\Connection;
use Lwt\Services\LanguageService;
use Lwt\Services\LanguageDefinitions;

/**
 * Handler for language-related API operations.
 *
 * Extracted from api_v1.php.
 */
class LanguageHandler
{
    /**
     * @var LanguageService Language service instance
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
     * Get the reading configuration for a language.
     *
     * @param int $langId Language ID
     *
     * @return array{name: string, voiceapi: string, word_parsing: string, abbreviation: mixed, reading_mode: string}
     */
    public function getReadingConfiguration(int $langId): array
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $req = Connection::query(
            "SELECT LgName, LgTTSVoiceAPI, LgRegexpWordCharacters FROM {$tbpref}languages
            WHERE LgID = " . $langId
        );
        $record = mysqli_fetch_assoc($req);
        $abbr = $this->languageService->getLanguageCode($langId, LanguageDefinitions::getAll());

        if ($record["LgTTSVoiceAPI"] != '') {
            $readingMode = "external";
        } elseif ($record["LgRegexpWordCharacters"] == "mecab") {
            $readingMode = "internal";
        } else {
            $readingMode = "direct";
        }

        return [
            "name" => $record["LgName"],
            "voiceapi" => $record["LgTTSVoiceAPI"],
            "word_parsing" => $record["LgRegexpWordCharacters"],
            "abbreviation" => $abbr,
            "reading_mode" => $readingMode
        ];
    }

    /**
     * Get the phonetic reading of a word based on its language.
     *
     * @param string   $text   Text to get phonetic reading for
     * @param int|null $langId Language ID (optional, use lang code if null)
     * @param string|null $langCode Short language name (optional)
     *
     * @return array{phonetic_reading: string}
     */
    public function getPhoneticReading(string $text, ?int $langId = null, ?string $langCode = null): array
    {
        if ($langId !== null) {
            $data = $this->languageService->getPhoneticReadingById($text, $langId);
        } else {
            $data = $this->languageService->getPhoneticReadingByCode($text, $langCode ?? '');
        }
        return ["phonetic_reading" => $data];
    }

    /**
     * Get terms similar to a given term.
     *
     * @param int    $langId Language ID
     * @param string $term   Term to find similar terms for
     *
     * @return array{similar_terms: string}
     */
    public function getSimilarTerms(int $langId, string $term): array
    {
        return ["similar_terms" => \print_similar_terms($langId, $term)];
    }

    /**
     * Get sentences containing a word.
     *
     * @param int         $langId   Language ID
     * @param string      $wordLc   Word in lowercase
     * @param int|null    $wordId   Word ID (null for new terms, -1 for advanced search)
     *
     * @return array Sentences with the word
     */
    public function getSentencesWithTerm(int $langId, string $wordLc, ?int $wordId): array
    {
        return \sentences_with_word($langId, $wordLc, $wordId);
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for reading configuration.
     *
     * @param int $langId Language ID
     *
     * @return array{name: string, voiceapi: string, word_parsing: string, abbreviation: mixed, reading_mode: string}
     */
    public function formatReadingConfiguration(int $langId): array
    {
        return $this->getReadingConfiguration($langId);
    }

    /**
     * Format response for phonetic reading.
     *
     * @param array $params Request parameters with 'text' and either 'lang_id' or 'lang'
     *
     * @return array{phonetic_reading: string}
     */
    public function formatPhoneticReading(array $params): array
    {
        if (array_key_exists("lang_id", $params)) {
            return $this->getPhoneticReading($params['text'], (int)$params['lang_id']);
        }
        return $this->getPhoneticReading($params['text'], null, $params['lang']);
    }

    /**
     * Format response for similar terms.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array{similar_terms: string}
     */
    public function formatSimilarTerms(int $langId, string $term): array
    {
        return $this->getSimilarTerms($langId, $term);
    }

    /**
     * Format response for sentences with registered term.
     *
     * @param int    $langId Language ID
     * @param string $wordLc Word in lowercase
     * @param int    $wordId Word ID
     *
     * @return array Sentences with the word
     */
    public function formatSentencesWithRegisteredTerm(int $langId, string $wordLc, int $wordId): array
    {
        return $this->getSentencesWithTerm($langId, $wordLc, $wordId);
    }

    /**
     * Format response for sentences with new term.
     *
     * @param int    $langId         Language ID
     * @param string $wordLc         Word in lowercase
     * @param bool   $advancedSearch Whether to use advanced search
     *
     * @return array Sentences with the word
     */
    public function formatSentencesWithNewTerm(int $langId, string $wordLc, bool $advancedSearch = false): array
    {
        $advanced = $advancedSearch ? -1 : null;
        return $this->getSentencesWithTerm($langId, $wordLc, $advanced);
    }
}
