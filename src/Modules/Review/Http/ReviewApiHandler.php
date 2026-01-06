<?php declare(strict_types=1);
/**
 * Review API Handler
 *
 * REST API handler for review/test operations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Review\Http;

use Lwt\Modules\Review\Application\ReviewFacade;
use Lwt\Modules\Review\Domain\TestConfiguration;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\View\Helper\StatusHelper;

// LanguageFacade loaded via autoloader
require_once __DIR__ . '/../../Vocabulary/Application/Services/ExportService.php';
require_once __DIR__ . '/../../../backend/View/Helper/StatusHelper.php';

/**
 * Handler for review/test-related API operations.
 *
 * @since 3.0.0
 */
class ReviewApiHandler
{
    private ReviewFacade $reviewFacade;

    /**
     * Constructor.
     *
     * @param ReviewFacade|null $reviewFacade Review facade (optional)
     */
    public function __construct(?ReviewFacade $reviewFacade = null)
    {
        $this->reviewFacade = $reviewFacade ?? new ReviewFacade();
    }

    /**
     * Get the next word to test as structured data.
     *
     * @param string $testsql  SQL projection query
     * @param bool   $wordMode Test is in word mode
     * @param int    $testtype Test type
     *
     * @return array{term_id: int|string, solution?: string, term_text: string, group: string}
     */
    public function getWordTestData(string $testsql, bool $wordMode, int $testtype): array
    {
        $wordRecord = $this->reviewFacade->getNextWord($testsql);
        if (empty($wordRecord)) {
            return [
                "term_id" => 0,
                "term_text" => '',
                "group" => ''
            ];
        }

        // Get sentence context
        if ($wordMode) {
            $sent = "{" . $wordRecord['WoText'] . "}";
        } else {
            $sentenceData = $this->reviewFacade->getSentenceForWord(
                (int)$wordRecord['WoID'],
                $wordRecord['WoTextLC']
            );
            $sent = $sentenceData['sentence'] ?? "{" . $wordRecord['WoText'] . "}";
        }

        // Format term for test display
        list($htmlSentence, $save) = $this->formatTermForTest(
            $wordRecord,
            $sent,
            $testtype
        );

        // Get solution
        $solution = $this->reviewFacade->getTestSolution(
            $testtype,
            $wordRecord,
            $wordMode,
            $save
        );

        return [
            "term_id" => $wordRecord['WoID'],
            "solution" => $solution,
            "term_text" => $save,
            "group" => $htmlSentence
        ];
    }

    /**
     * Format term for test display.
     *
     * @param array  $wordRecord Word database record
     * @param string $sentence   Sentence containing the word (word marked with {})
     * @param int    $testType   Test type (1-5)
     *
     * @return array{0: string, 1: string} [HTML display, plain word text]
     */
    private function formatTermForTest(
        array $wordRecord,
        string $sentence,
        int $testType
    ): array {
        $baseType = $this->reviewFacade->getBaseTestType($testType);
        $wordText = $wordRecord['WoText'];

        // Extract the word from sentence (marked with {})
        if (preg_match('/\{([^}]+)\}/', $sentence, $matches)) {
            $markedWord = $matches[1];
        } else {
            $markedWord = $wordText;
        }

        // Build display HTML based on test type
        if ($baseType == 1) {
            // Type 1: Show term, guess translation
            $displayHtml = str_replace(
                '{' . $markedWord . '}',
                '<span class="word-test">' . htmlspecialchars($markedWord, ENT_QUOTES, 'UTF-8') . '</span>',
                $sentence
            );
        } elseif ($baseType == 2) {
            // Type 2: Show translation, guess term (hide term)
            $hiddenSpan = '<span class="word-test-hidden">[...]</span>';
            $displayHtml = str_replace('{' . $markedWord . '}', $hiddenSpan, $sentence);
        } else {
            // Type 3: Show sentence with hidden term
            $hiddenSpan = '<span class="word-test-hidden">[...]</span>';
            $displayHtml = str_replace('{' . $markedWord . '}', $hiddenSpan, $sentence);
        }

        // Clean up any remaining braces
        $displayHtml = str_replace(['{', '}'], '', $displayHtml);

        return [$displayHtml, $markedWord];
    }

    /**
     * Get the next word to test based on request parameters.
     *
     * @param array $params Request parameters
     *
     * @return array{term_id: int|string, solution?: string, term_text: string, group: string}
     */
    public function wordTestAjax(array $params): array
    {
        $testSql = $this->reviewFacade->getTestSql(
            $params['test_key'],
            $this->parseSelection($params['test_key'], $params['selection'])
        );
        if ($testSql === null) {
            return [
                "term_id" => 0,
                "term_text" => '',
                "group" => ''
            ];
        }
        return $this->getWordTestData(
            $testSql,
            filter_var($params['word_mode'], FILTER_VALIDATE_BOOLEAN),
            (int)$params['type']
        );
    }

    /**
     * Return the number of reviews for tomorrow.
     *
     * @param array $params Request parameters
     *
     * @return array{count: int}
     */
    public function tomorrowTestCount(array $params): array
    {
        $testSql = $this->reviewFacade->getTestSql(
            $params['test_key'],
            $this->parseSelection($params['test_key'], $params['selection'])
        );
        if ($testSql === null) {
            return ["count" => 0];
        }
        return [
            "count" => $this->reviewFacade->getTomorrowTestCount($testSql)
        ];
    }

    /**
     * Parse selection parameter based on test key type.
     *
     * @param string $testKey   The test key type
     * @param string $selection The selection value
     *
     * @return int|int[] Parsed selection value
     */
    private function parseSelection(string $testKey, string $selection): int|array
    {
        if ($testKey === 'words' || $testKey === 'texts') {
            return array_map('intval', explode(',', $selection));
        }
        return (int)$selection;
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for getting next word test.
     *
     * @param array $params Request parameters
     *
     * @return array
     */
    public function formatNextWord(array $params): array
    {
        return $this->wordTestAjax($params);
    }

    /**
     * Format response for tomorrow count.
     *
     * @param array $params Request parameters
     *
     * @return array{count: int}
     */
    public function formatTomorrowCount(array $params): array
    {
        return $this->tomorrowTestCount($params);
    }

    /**
     * Update word status during review/test mode.
     *
     * @param int      $wordId Word ID
     * @param int|null $status Explicit status
     * @param int|null $change Status change amount
     *
     * @return array{status?: int, controls?: string, error?: string}
     */
    public function updateReviewStatus(int $wordId, ?int $status, ?int $change): array
    {
        if ($status !== null) {
            // Explicit status - validate it
            if (!in_array($status, [1, 2, 3, 4, 5, 98, 99])) {
                return ['error' => 'Invalid status value'];
            }
            $result = $this->reviewFacade->submitAnswer($wordId, $status);
        } elseif ($change !== null) {
            $result = $this->reviewFacade->submitAnswerWithChange($wordId, $change);
        } else {
            return ['error' => 'Must provide either status or change'];
        }

        if (!$result['success']) {
            return ['error' => $result['error'] ?? 'Failed to update status'];
        }

        // Return the new status and controls HTML
        $statusAbbr = StatusHelper::getAbbr($result['newStatus']);
        $controls = StatusHelper::buildTestTableControls(1, $result['newStatus'], $wordId, $statusAbbr);

        return [
            'status' => $result['newStatus'],
            'controls' => $controls
        ];
    }

    /**
     * Format response for updating review status.
     *
     * @param array $params Request parameters
     *
     * @return array{status?: int, controls?: string, error?: string}
     */
    public function formatUpdateStatus(array $params): array
    {
        $termId = (int)($params['term_id'] ?? 0);
        if ($termId === 0) {
            return ['error' => 'term_id is required'];
        }

        $status = isset($params['status']) ? (int)$params['status'] : null;
        $change = isset($params['change']) ? (int)$params['change'] : null;

        return $this->updateReviewStatus($termId, $status, $change);
    }

    /**
     * Get full test configuration for Alpine.js initialization.
     *
     * @param array $params Request parameters
     *
     * @return array Test configuration
     */
    public function formatTestConfig(array $params): array
    {
        $langId = isset($params['lang']) && $params['lang'] !== ''
            ? (int)$params['lang'] : null;
        $textId = isset($params['text']) && $params['text'] !== ''
            ? (int)$params['text'] : null;
        $selection = isset($params['selection']) && $params['selection'] !== ''
            ? (int)$params['selection'] : null;
        $testType = isset($params['type']) && $params['type'] !== ''
            ? (int)$params['type'] : 1;
        $isTableMode = ($params['type'] ?? '') === 'table';

        $sessTestsql = $_SESSION['testsql'] ?? null;

        // Get test data
        $testData = $this->reviewFacade->getTestDataFromParams(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($testData === null) {
            return ['error' => 'Invalid test parameters'];
        }

        // Get test identifier
        $identifier = $this->reviewFacade->getTestIdentifier(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($identifier[0] === '') {
            return ['error' => 'Invalid test identifier'];
        }

        // Handle legacy raw_sql case
        if ($identifier[0] === 'raw_sql') {
            $testsql = is_string($identifier[1]) ? $identifier[1] : null;
        } else {
            /** @var int|int[] $sel */
            $sel = $identifier[1];
            $testsql = $this->reviewFacade->getTestSql($identifier[0], $sel);
        }

        if ($testsql === null) {
            return ['error' => 'Unable to generate test SQL'];
        }

        $testType = $this->reviewFacade->clampTestType($testType);
        $wordMode = $this->reviewFacade->isWordMode($testType);
        $baseType = $this->reviewFacade->getBaseTestType($testType);

        // Get language settings
        $langIdFromSql = $this->reviewFacade->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            return ['error' => 'No words available for testing'];
        }

        $langSettings = $this->reviewFacade->getLanguageSettings($langIdFromSql);

        // Get language code for TTS
        $languageService = new LanguageFacade();
        $langCode = $languageService->getLanguageCode(
            $langIdFromSql,
            LanguagePresets::getAll()
        );

        // Initialize session
        $this->reviewFacade->initializeTestSession($testData['counts']['due']);
        $sessionData = $this->reviewFacade->getTestSessionData();

        return [
            'testKey' => $identifier[0],
            'selection' => is_array($identifier[1])
                ? implode(',', $identifier[1])
                : (string)$identifier[1],
            'testType' => $baseType,
            'isTableMode' => $isTableMode,
            'wordMode' => $wordMode,
            'langId' => $langIdFromSql,
            'wordRegex' => $langSettings['regexWord'] ?? '',
            'langSettings' => [
                'name' => $langSettings['name'] ?? '',
                'dict1Uri' => $langSettings['dict1Uri'] ?? '',
                'dict2Uri' => $langSettings['dict2Uri'] ?? '',
                'translateUri' => $langSettings['translateUri'] ?? '',
                'textSize' => $langSettings['textSize'] ?? 100,
                'rtl' => $langSettings['rtl'] ?? false,
                'langCode' => $langCode
            ],
            'progress' => [
                'total' => $testData['counts']['due'],
                'remaining' => $testData['counts']['due'],
                'wrong' => 0,
                'correct' => 0
            ],
            'timer' => [
                'startTime' => $sessionData['start'],
                'serverTime' => time()
            ],
            'title' => $testData['title'],
            'property' => $testData['property']
        ];
    }

    /**
     * Get all words for table test mode.
     *
     * @param array $params Request parameters
     *
     * @return array Table words data
     */
    public function formatTableWords(array $params): array
    {
        $testKey = $params['test_key'] ?? '';
        $selection = $params['selection'] ?? '';

        if ($testKey === '' || $selection === '') {
            return ['error' => 'test_key and selection are required'];
        }

        $parsedSelection = $this->parseSelection($testKey, $selection);
        $testsql = $this->reviewFacade->getTestSql($testKey, $parsedSelection);

        if ($testsql === null) {
            return ['error' => 'Unable to generate test SQL'];
        }

        // Validate single language
        $validation = $this->reviewFacade->validateTestSelection($testsql);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }

        // Get language settings
        $langIdFromSql = $this->reviewFacade->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            return ['words' => [], 'langSettings' => null];
        }

        $langSettings = $this->reviewFacade->getLanguageSettings($langIdFromSql);
        $regexWord = $langSettings['regexWord'] ?? '';

        // Get language code for TTS
        $languageService = new LanguageFacade();
        $langCode = $languageService->getLanguageCode(
            $langIdFromSql,
            LanguagePresets::getAll()
        );

        // Get words
        $wordsResult = $this->reviewFacade->getTableTestWords($testsql);
        $words = [];

        if ($wordsResult instanceof \mysqli_result) {
            while ($word = mysqli_fetch_assoc($wordsResult)) {
                // Format sentence with highlighted word
                $sent = htmlspecialchars(
                    ExportService::replaceTabNewline((string)($word['WoSentence'] ?? '')),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $sentenceHtml = str_replace(
                    "{",
                    ' <b>[',
                    str_replace(
                        "}",
                        ']</b> ',
                        ExportService::maskTermInSentence($sent, $regexWord)
                    )
                );

                $words[] = [
                    'id' => (int)$word['WoID'],
                    'text' => $word['WoText'] ?? '',
                    'translation' => $word['WoTranslation'] ?? '',
                    'romanization' => $word['WoRomanization'] ?? '',
                    'sentence' => $sent,
                    'sentenceHtml' => $sentenceHtml,
                    'status' => (int)($word['WoStatus'] ?? 1),
                    'score' => (int)($word['Score'] ?? 0)
                ];
            }
            mysqli_free_result($wordsResult);
        }

        return [
            'words' => $words,
            'langSettings' => [
                'name' => $langSettings['name'] ?? '',
                'dict1Uri' => $langSettings['dict1Uri'] ?? '',
                'dict2Uri' => $langSettings['dict2Uri'] ?? '',
                'translateUri' => $langSettings['translateUri'] ?? '',
                'textSize' => $langSettings['textSize'] ?? 100,
                'rtl' => $langSettings['rtl'] ?? false,
                'langCode' => $langCode
            ]
        ];
    }
}
