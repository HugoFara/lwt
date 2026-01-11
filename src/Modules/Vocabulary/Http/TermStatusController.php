<?php

/**
 * Term Status Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for term status operations.
 *
 * Handles:
 * - PUT /vocabulary/term/{wid}/status - Update status
 * - /word/set-status - Set status (iframe view)
 * - /word/set-review-status - Set review status (iframe view)
 * - /word/insert-wellknown - Insert as well-known
 * - /word/insert-ignore - Insert as ignored
 * - /word/set-all-status - Mark all words with status
 *
 * @since 3.0.0
 */
class TermStatusController extends VocabularyBaseController
{
    /**
     * Vocabulary facade.
     */
    private VocabularyFacade $facade;

    /**
     * Create term from hover use case.
     */
    private CreateTermFromHover $createTermFromHover;

    /**
     * Constructor.
     *
     * @param VocabularyFacade|null    $facade              Vocabulary facade
     * @param CreateTermFromHover|null $createTermFromHover Create term from hover use case
     */
    public function __construct(
        ?VocabularyFacade $facade = null,
        ?CreateTermFromHover $createTermFromHover = null
    ) {
        parent::__construct();
        $this->facade = $facade ?? new VocabularyFacade();
        $this->createTermFromHover = $createTermFromHover ?? new CreateTermFromHover();
    }

    /**
     * Update term status.
     *
     * Routes:
     * - PUT /vocabulary/term/{wid:int}/status (new RESTful route)
     * - PUT /vocabulary/term/status?wid=[id] (legacy route)
     *
     * Body: {"status": 1-5|98|99}
     *
     * @param int|null $wid Term ID (injected from route parameter)
     *
     * @return void
     */
    public function updateStatus(?int $wid = null): void
    {
        // Support both new route param injection and legacy query param
        $termId = $wid ?? InputValidator::getInt('wid', 0) ?? 0;
        $status = InputValidator::getInt('status', 0) ?? 0;

        if ($termId === 0 || $status === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID and status required']);
            return;
        }

        $result = $this->facade->updateStatus($termId, $status);

        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }

    /**
     * Set word status (iframe view).
     *
     * Replaces set_word_status.php - sets status and renders result in iframe.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function setWordStatusView(array $params): void
    {
        $wid = InputValidator::getInt('wid', 0) ?? 0;
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $ord = InputValidator::getInt('ord', 0) ?? 0;
        $status = InputValidator::getInt('status', 0) ?? 0;

        if ($wid === 0 || $status === 0) {
            PageLayoutHelper::renderPageStartNobody('Error');
            echo '<p>Invalid parameters</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        // Update status
        $this->facade->updateStatus($wid, $status);

        // Get updated term info
        $term = $this->facade->getTerm($wid);
        if ($term === null) {
            PageLayoutHelper::renderPageStartNobody('Error');
            echo '<p>Term not found</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        PageLayoutHelper::renderPageStartNobody('Term Status');

        $this->render('set_status_result', [
            'wid' => $wid,
            'textId' => $textId,
            'ord' => $ord,
            'status' => $status,
            'term' => $term,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Set review status (iframe view).
     *
     * Replaces set_test_status.php - sets status during review and renders result.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function setReviewStatusView(array $params): void
    {
        $wid = InputValidator::getInt('wid', 0) ?? 0;
        $status = InputValidator::getInt('status');
        $stchange = InputValidator::getInt('stchange');
        $ajax = InputValidator::getString('ajax');

        if ($wid === 0) {
            PageLayoutHelper::renderPageStartNobody('Error');
            echo '<p>Invalid word ID</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $apiHandler = new TermStatusApiHandler();

        // Handle status change (increment/decrement)
        if ($stchange !== null) {
            $up = $stchange > 0;
            $result = $apiHandler->formatIncrementStatusHtml($wid, $up);

            if ($ajax === '1') {
                header('Content-Type: text/html; charset=utf-8');
                echo $result['increment'] ?? '';
                return;
            }

            PageLayoutHelper::renderPageStartNobody('Status Changed');
            echo $result['increment'] ?? '<p>Status updated</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        // Handle direct status set
        if ($status !== null) {
            $apiHandler->formatSetStatus($wid, $status);

            if ($ajax === '1') {
                header('Content-Type: text/html; charset=utf-8');
                echo 'OK';
                return;
            }

            PageLayoutHelper::renderPageStartNobody('Status Set');
            echo '<p>Status set to ' . $status . '</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        PageLayoutHelper::renderPageStartNobody('Error');
        echo '<p>No status operation specified</p>';
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Insert word as well-known (iframe view).
     *
     * Replaces insert_word_wellknown.php - creates term with status 99.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function insertWellknown(array $params): void
    {
        $this->insertWordWithStatus($params, 99);
    }

    /**
     * Insert word as ignored (iframe view).
     *
     * Replaces insert_word_ignore.php - creates term with status 98.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function insertIgnore(array $params): void
    {
        $this->insertWordWithStatus($params, 98);
    }

    /**
     * Insert word with specified status.
     *
     * Common logic for insertWellknown and insertIgnore.
     *
     * @param array<string, string> $params Route parameters
     * @param int                   $status Status to set (98 or 99)
     *
     * @return void
     */
    private function insertWordWithStatus(array $params, int $status): void
    {
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $ord = InputValidator::getInt('ord', 0) ?? 0;
        $text = InputValidator::getString('text');

        if ($textId === 0 || $text === '') {
            PageLayoutHelper::renderPageStartNobody('Error');
            echo '<p>Invalid parameters</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        // Use hover create mechanism
        $result = $this->createFromHover($textId, $text, $status);

        PageLayoutHelper::renderPageStartNobody($status === 99 ? 'Well-Known' : 'Ignored');

        $this->render('insert_status_result', [
            'wid' => $result['wid'],
            'textId' => $textId,
            'ord' => $ord,
            'status' => $status,
            'hex' => $result['hex'],
            'word' => $result['word'],
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Create a term from hover action in reading view.
     *
     * @param int    $textId     Text ID
     * @param string $wordText   Word text
     * @param int    $status     Word status (1-5)
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     *
     * @return array Term creation result
     */
    private function createFromHover(
        int $textId,
        string $wordText,
        int $status,
        string $sourceLang = '',
        string $targetLang = ''
    ): array {
        // Set no-cache headers for new words
        if ($this->createTermFromHover->shouldSetNoCacheHeaders($status)) {
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        return $this->createTermFromHover->execute(
            $textId,
            $wordText,
            $status,
            $sourceLang,
            $targetLang
        );
    }

    /**
     * Mark all words with status (well-known or ignore).
     *
     * @param array<string, string> $params Route parameters
     *
     * @psalm-suppress UnresolvableInclude Path computed from viewPath property
     *
     * @return void
     */
    public function markAllWords(array $params): void
    {
        $textId = InputValidator::getInt('text');
        if ($textId === null) {
            return;
        }

        $status = InputValidator::getInt('stat', 99) ?? 99;

        if ($status == 98) {
            PageLayoutHelper::renderPageStart("Setting all blue words to Ignore", false);
        } else {
            PageLayoutHelper::renderPageStart("Setting all blue words to Well-known", false);
        }

        $discoveryService = $this->getDiscoveryService();
        list($count, $wordsData) = $discoveryService->markAllWordsWithStatus($textId, $status);
        $useTooltips = Settings::getWithDefault('set-tooltip-mode') == 1;

        include $this->viewPath . 'all_wellknown_result.php';

        PageLayoutHelper::renderPageEnd();
    }
}
