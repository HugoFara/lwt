<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Settings;
use Lwt\Services\LanguageService;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\SimilarTermsService;

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
     * @var SimilarTermsService Similar terms service instance
     */
    private SimilarTermsService $similarTermsService;

    /**
     * Constructor - initialize language service.
     */
    public function __construct()
    {
        $this->languageService = new LanguageService();
        $this->similarTermsService = new SimilarTermsService();
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
        $tbpref = Globals::getTablePrefix();

        $record = Connection::preparedFetchOne(
            "SELECT LgName, LgTTSVoiceAPI, LgRegexpWordCharacters
             FROM {$tbpref}languages WHERE LgID = ?",
            [$langId]
        );

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
        return ["similar_terms" => $this->similarTermsService->printSimilarTerms($langId, $term)];
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
        return \sentencesWithWord($langId, $wordLc, $wordId);
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

    /**
     * Format response for languages with text counts.
     *
     * Returns languages that have at least one text, for grouped texts page.
     *
     * @return array{languages: array<int, array{id: int, name: string, text_count: int}>}
     */
    public function formatLanguagesWithTexts(): array
    {
        return [
            'languages' => $this->languageService->getLanguagesWithTextCounts()
        ];
    }

    /**
     * Format response for languages with archived text counts.
     *
     * Returns languages that have at least one archived text, for grouped archived texts page.
     *
     * @return array{languages: array<int, array{id: int, name: string, text_count: int}>}
     */
    public function formatLanguagesWithArchivedTexts(): array
    {
        return [
            'languages' => $this->languageService->getLanguagesWithArchivedTextCounts()
        ];
    }

    // =========================================================================
    // Language CRUD Operations
    // =========================================================================

    /**
     * Format response for getting all languages with stats.
     *
     * @return array{languages: array, currentLanguageId: int}
     */
    public function formatGetAll(): array
    {
        $currentLangId = (int)Settings::get('currentlanguage');
        return [
            'languages' => $this->languageService->getLanguagesWithStats(),
            'currentLanguageId' => $currentLangId
        ];
    }

    /**
     * Format response for getting a single language.
     *
     * @param int $id Language ID
     *
     * @return array|null Language data or null if not found
     */
    public function formatGetOne(int $id): ?array
    {
        $language = $this->languageService->getById($id);
        if ($language === null) {
            return null;
        }

        return [
            'language' => [
                'id' => $language->id()->toInt(),
                'name' => $language->name(),
                'dict1Uri' => $language->dict1Uri(),
                'dict2Uri' => $language->dict2Uri(),
                'translatorUri' => $language->translatorUri(),
                'exportTemplate' => $language->exportTemplate(),
                'textSize' => $language->textSize(),
                'characterSubstitutions' => $language->characterSubstitutions(),
                'regexpSplitSentences' => $language->regexpSplitSentences(),
                'exceptionsSplitSentences' => $language->exceptionsSplitSentences(),
                'regexpWordCharacters' => $language->regexpWordCharacters(),
                'removeSpaces' => $language->removeSpaces(),
                'splitEachChar' => $language->splitEachChar(),
                'rightToLeft' => $language->rightToLeft(),
                'ttsVoiceApi' => $language->ttsVoiceApi(),
                'showRomanization' => $language->showRomanization(),
            ],
            'allLanguages' => $this->languageService->getAllLanguages()
        ];
    }

    /**
     * Format response for creating a new language.
     *
     * @param array $data Language data from request
     *
     * @return array{success: bool, id?: int, error?: string}
     */
    public function formatCreate(array $data): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Language name is required'];
        }

        // Check for duplicate name
        if ($this->languageService->isDuplicateName($data['name'])) {
            return ['success' => false, 'error' => 'A language with this name already exists'];
        }

        $id = $this->languageService->createFromData($data);
        if ($id > 0) {
            return ['success' => true, 'id' => $id];
        }

        return ['success' => false, 'error' => 'Failed to create language'];
    }

    /**
     * Format response for updating a language.
     *
     * @param int   $id   Language ID
     * @param array $data Language data from request
     *
     * @return array{success: bool, reparsed?: int, error?: string}
     */
    public function formatUpdate(int $id, array $data): array
    {
        // Check language exists
        $existing = $this->languageService->getById($id);
        if ($existing === null) {
            return ['success' => false, 'error' => 'Language not found'];
        }

        // Validate required fields
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Language name is required'];
        }

        // Check for duplicate name (excluding current)
        if ($this->languageService->isDuplicateName($data['name'], $id)) {
            return ['success' => false, 'error' => 'A language with this name already exists'];
        }

        $result = $this->languageService->updateFromData($id, $data);
        return [
            'success' => true,
            'reparsed' => $result['reparsed'] ?? 0,
            'message' => $result['message'] ?? ''
        ];
    }

    /**
     * Format response for deleting a language.
     *
     * @param int $id Language ID
     *
     * @return array{success: bool, error?: string}
     */
    public function formatDelete(int $id): array
    {
        // Check if language can be deleted
        if (!$this->languageService->canDelete($id)) {
            $stats = $this->languageService->getRelatedDataCounts($id);
            return [
                'success' => false,
                'error' => 'Cannot delete language with existing data',
                'relatedData' => $stats
            ];
        }

        $result = $this->languageService->deleteById($id);
        return ['success' => $result];
    }

    /**
     * Format response for getting language stats.
     *
     * @param int $id Language ID
     *
     * @return array{texts: int, archivedTexts: int, words: int, feeds: int}
     */
    public function formatGetStats(int $id): array
    {
        return $this->languageService->getRelatedDataCounts($id);
    }

    /**
     * Format response for refreshing (reparsing) a language's texts.
     *
     * @param int $id Language ID
     *
     * @return array{success: bool, sentencesDeleted?: int, textItemsDeleted?: int, sentencesAdded?: int, textItemsAdded?: int}
     */
    public function formatRefresh(int $id): array
    {
        $result = $this->languageService->refreshTexts($id);
        return [
            'success' => true,
            'sentencesDeleted' => $result['sentencesDeleted'],
            'textItemsDeleted' => $result['textItemsDeleted'],
            'sentencesAdded' => $result['sentencesAdded'],
            'textItemsAdded' => $result['textItemsAdded']
        ];
    }

    /**
     * Format response for getting all language definitions (presets).
     *
     * @return array{definitions: array}
     */
    public function formatGetDefinitions(): array
    {
        $definitions = LanguageDefinitions::getAll();
        $formatted = [];

        foreach ($definitions as $name => $def) {
            $formatted[$name] = [
                'glosbeIso' => $def[0],
                'googleIso' => $def[1],
                'biggerFont' => $def[2],
                'wordCharRegExp' => $def[3],
                'sentSplRegExp' => $def[4],
                'makeCharacterWord' => $def[5],
                'removeSpaces' => $def[6],
                'rightToLeft' => $def[7]
            ];
        }

        return ['definitions' => $formatted];
    }

    /**
     * Format response for setting a language as default/current.
     *
     * @param int $id Language ID
     *
     * @return array{success: bool}
     */
    public function formatSetDefault(int $id): array
    {
        \saveSetting('currentlanguage', (string)$id);
        return ['success' => true];
    }
}
