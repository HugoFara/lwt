<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Services\TestService;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Services\ExportService;
use Lwt\View\Helper\StatusHelper;

require_once __DIR__ . '/../../../Services/TestService.php';
// LanguageFacade loaded via autoloader
require_once __DIR__ . '/../../../Services/ExportService.php';

/**
 * Handler for review/test-related API operations.
 *
 * Extracted from api_v1.php lines 1157-1259.
 */
class ReviewHandler
{
    private TestService $testService;

    public function __construct()
    {
        $this->testService = new TestService();
    }

    /**
     * Get the next word to test as structured data.
     *
     * @param string $testsql  SQL projection query
     * @param bool   $wordMode Test is in word mode
     * @param int    $testtype Test type
     *
     * @return array{word_id: int|string, solution?: string, word_text: string, group: string}
     */
    public function getWordTestData(string $testsql, bool $wordMode, int $testtype): array
    {
        $wordRecord = $this->testService->getNextWord($testsql);
        if (empty($wordRecord)) {
            return [
                "word_id" => 0,
                "word_text" => '',
                "group" => ''
            ];
        }

        // Get sentence context
        if ($wordMode) {
            $sent = "{" . $wordRecord['WoText'] . "}";
        } else {
            $sentenceData = $this->testService->getSentenceForWord(
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
        $solution = $this->testService->getTestSolution(
            $testtype,
            $wordRecord,
            $wordMode,
            $save
        );

        return [
            "word_id" => $wordRecord['WoID'],
            "solution" => $solution,
            "word_text" => $save,
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
        $baseType = $this->testService->getBaseTestType($testType);
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
     * @param array $params Array with the fields {
     *                      test_key: string, selection: string, word_mode: bool,
     *                      type: int
     *                      }
     *
     * @return array{word_id: int|string, solution?: string, word_text: string, group: string}
     */
    public function wordTestAjax(array $params): array
    {
        $testSql = $this->testService->getTestSql(
            $params['test_key'],
            $this->parseSelection($params['test_key'], $params['selection'])
        );
        return $this->getWordTestData(
            $testSql,
            filter_var($params['word_mode'], FILTER_VALIDATE_BOOLEAN),
            (int)$params['type']
        );
    }

    /**
     * Return the number of reviews for tomorrow.
     *
     * @param array $params Array with the fields "test_key" and "selection"
     *
     * @return array{count: int}
     */
    public function tomorrowTestCount(array $params): array
    {
        $testSql = $this->testService->getTestSql(
            $params['test_key'],
            $this->parseSelection($params['test_key'], $params['selection'])
        );
        return [
            "count" => $this->testService->getTomorrowTestCount($testSql)
        ];
    }

    /**
     * Parse selection parameter based on test key type.
     *
     * For 'words' and 'texts' keys, selection should be an array of IDs.
     * For 'lang' and 'text' keys, selection should be a single integer.
     *
     * @param string $testKey   The test key type
     * @param string $selection The selection value (comma-separated IDs or single ID)
     *
     * @return int|int[] Parsed selection value
     */
    private function parseSelection(string $testKey, string $selection): int|array
    {
        if ($testKey === 'words' || $testKey === 'texts') {
            // These expect an array of IDs
            return array_map('intval', explode(',', $selection));
        }
        // 'lang' and 'text' expect a single integer
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
     * @return array{word_id: int|string, solution?: string, word_text: string, group: string}
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

    // =========================================================================
    // New Phase 2 Methods
    // =========================================================================

    /**
     * Update word status during review/test mode.
     *
     * Supports both explicit status setting and relative changes (+1/-1).
     *
     * @param int      $wordId Word ID
     * @param int|null $status Explicit status (1-5, 98, 99), null if using change
     * @param int|null $change Status change amount (+1 or -1), null if using explicit status
     *
     * @return array{status?: int, controls?: string, error?: string}
     */
    public function updateReviewStatus(int $wordId, ?int $status, ?int $change): array
    {
        // Get current status using QueryBuilder
        $currentStatus = QueryBuilder::table('words')
            ->where('WoID', '=', $wordId)
            ->valuePrepared('WoStatus');

        if ($currentStatus === null) {
            return ['error' => 'Word not found'];
        }

        $currentStatus = (int)$currentStatus;

        if ($status !== null) {
            // Explicit status - validate it
            if (!in_array($status, [1, 2, 3, 4, 5, 98, 99])) {
                return ['error' => 'Invalid status value'];
            }
            $newStatus = $status;
        } elseif ($change !== null) {
            // Relative change
            if ($change > 0) {
                // Increment
                $newStatus = $currentStatus + 1;
                if ($newStatus == 6) {
                    $newStatus = 99;  // 5 -> 99 (well-known)
                } elseif ($newStatus == 100) {
                    $newStatus = 1;   // 99 -> 1 (wrap around)
                }
            } else {
                // Decrement
                $newStatus = $currentStatus - 1;
                if ($newStatus == 0) {
                    $newStatus = 98;  // 1 -> 98 (ignored)
                } elseif ($newStatus == 97) {
                    $newStatus = 5;   // 98 -> 5 (wrap around)
                }
            }
        } else {
            return ['error' => 'Must provide either status or change'];
        }

        // Update the status using raw SQL for dynamic score update
        $scoreUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');
        $bindings = [$newStatus, $wordId];
        $result = Connection::preparedExecute(
            "UPDATE words
             SET WoStatus = ?, WoStatusChanged = NOW(), {$scoreUpdate}
             WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );

        if ($result !== 1) {
            return ['error' => 'Failed to update status'];
        }

        // Return the new status and controls HTML
        $statusAbbr = StatusHelper::getAbbr($newStatus);
        $controls = StatusHelper::buildTestTableControls(1, $newStatus, $wordId, $statusAbbr);

        return [
            'status' => $newStatus,
            'controls' => $controls
        ];
    }

    /**
     * Format response for updating review status.
     *
     * @param array $params Request parameters with word_id, and either status or change
     *
     * @return array{status?: int, controls?: string, error?: string}
     */
    public function formatUpdateStatus(array $params): array
    {
        $wordId = (int)($params['word_id'] ?? 0);
        if ($wordId === 0) {
            return ['error' => 'word_id is required'];
        }

        $status = isset($params['status']) ? (int)$params['status'] : null;
        $change = isset($params['change']) ? (int)$params['change'] : null;

        return $this->updateReviewStatus($wordId, $status, $change);
    }

    // =========================================================================
    // Phase 3 Methods: Alpine Test Interface
    // =========================================================================

    /**
     * Get full test configuration for Alpine.js initialization.
     *
     * @param array $params Request parameters (lang, text, or selection)
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
        $testData = $this->testService->getTestDataFromParams(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($testData === null) {
            return ['error' => 'Invalid test parameters'];
        }

        // Get test identifier
        $identifier = $this->testService->getTestIdentifier(
            $selection,
            $sessTestsql,
            $langId,
            $textId
        );

        if ($identifier[0] === '') {
            return ['error' => 'Invalid test identifier'];
        }

        // Handle legacy raw_sql case differently
        if ($identifier[0] === 'raw_sql') {
            // Legacy: the identifier value is the raw SQL string
            $testsql = is_string($identifier[1]) ? $identifier[1] : null;
        } else {
            // Normal cases: use getTestSql with proper typed selection
            /** @var int|int[] $selection */
            $selection = $identifier[1];
            $testsql = $this->testService->getTestSql($identifier[0], $selection);
        }
        $testType = $this->testService->clampTestType($testType);
        $wordMode = $this->testService->isWordMode($testType);
        $baseType = $this->testService->getBaseTestType($testType);

        // Get language settings
        $langIdFromSql = $this->testService->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            return ['error' => 'No words available for testing'];
        }

        $langSettings = $this->testService->getLanguageSettings($langIdFromSql);

        // Get language code for TTS
        $languageService = new LanguageFacade();
        $langCode = $languageService->getLanguageCode(
            $langIdFromSql,
            LanguagePresets::getAll()
        );

        // Initialize session
        $this->testService->initializeTestSession($testData['counts']['due']);
        $sessionData = $this->testService->getTestSessionData();

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
     * @param array $params Request parameters (test_key, selection)
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
        $testsql = $this->testService->getTestSql($testKey, $parsedSelection);

        // Validate single language
        $validation = $this->testService->validateTestSelection($testsql);
        if (!$validation['valid']) {
            return ['error' => $validation['error']];
        }

        // Get language settings
        $langIdFromSql = $this->testService->getLanguageIdFromTestSql($testsql);
        if ($langIdFromSql === null) {
            return ['words' => [], 'langSettings' => null];
        }

        $langSettings = $this->testService->getLanguageSettings($langIdFromSql);
        $regexWord = $langSettings['regexWord'] ?? '';

        // Get language code for TTS
        $languageService = new LanguageFacade();
        $langCode = $languageService->getLanguageCode(
            $langIdFromSql,
            LanguagePresets::getAll()
        );

        // Get words
        $wordsResult = $this->testService->getTableTestWords($testsql);
        $words = [];

        while ($word = mysqli_fetch_assoc($wordsResult)) {
            // Format sentence with highlighted word
            $sent = htmlspecialchars(
                ExportService::replaceTabNewline($word['WoSentence'] ?? ''),
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
