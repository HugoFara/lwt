<?php declare(strict_types=1);
/**
 * Vocabulary Controller
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

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Core\StringUtils;
use Lwt\Core\Utils\ErrorHandler;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\Modules\Vocabulary\Application\Services\ExpressionService;
use Lwt\Modules\Vocabulary\Application\Services\WordUploadService;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Services\WordService;
use Lwt\Services\SentenceService;
use Lwt\Services\TextStatisticsService;
use Lwt\Services\ExportService;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for vocabulary/term management operations.
 *
 * This controller handles all word/term routes with native implementations
 * using module services, facades, and use cases. The migration from the
 * legacy WordController is complete.
 *
 * @since 3.0.0
 */
class VocabularyController
{
    /**
     * View base path.
     */
    private string $viewPath;

    /**
     * Vocabulary facade.
     */
    private VocabularyFacade $facade;

    /**
     * Use cases.
     */
    private CreateTermFromHover $createTermFromHover;
    private FindSimilarTerms $findSimilarTerms;

    /**
     * Services.
     */
    private LanguageFacade $languageFacade;
    private ?WordService $wordService = null;
    private ?SentenceService $sentenceService = null;
    private ?ExpressionService $expressionService = null;
    private ?WordUploadService $uploadService = null;

    /**
     * Adapters.
     */
    private DictionaryAdapter $dictionaryAdapter;

    /**
     * Constructor.
     *
     * @param VocabularyFacade|null     $facade              Vocabulary facade
     * @param CreateTermFromHover|null  $createTermFromHover Create term from hover use case
     * @param FindSimilarTerms|null     $findSimilarTerms    Find similar terms use case
     * @param DictionaryAdapter|null    $dictionaryAdapter   Dictionary adapter
     * @param LanguageFacade|null       $languageFacade      Language facade
     */
    public function __construct(
        ?VocabularyFacade $facade = null,
        ?CreateTermFromHover $createTermFromHover = null,
        ?FindSimilarTerms $findSimilarTerms = null,
        ?DictionaryAdapter $dictionaryAdapter = null,
        ?LanguageFacade $languageFacade = null
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->facade = $facade ?? new VocabularyFacade();
        $this->createTermFromHover = $createTermFromHover ?? new CreateTermFromHover();
        $this->findSimilarTerms = $findSimilarTerms ?? new FindSimilarTerms();
        $this->dictionaryAdapter = $dictionaryAdapter ?? new DictionaryAdapter();
        $this->languageFacade = $languageFacade ?? new LanguageFacade();
    }

    /**
     * Get WordService (lazy loaded).
     *
     * @return WordService
     */
    private function getWordService(): WordService
    {
        if ($this->wordService === null) {
            $this->wordService = new WordService();
        }
        return $this->wordService;
    }

    /**
     * Get SentenceService (lazy loaded).
     *
     * @return SentenceService
     */
    private function getSentenceService(): SentenceService
    {
        if ($this->sentenceService === null) {
            $this->sentenceService = new SentenceService();
        }
        return $this->sentenceService;
    }

    /**
     * Get ExpressionService (lazy loaded).
     *
     * @return ExpressionService
     */
    private function getExpressionService(): ExpressionService
    {
        if ($this->expressionService === null) {
            $this->expressionService = new ExpressionService();
        }
        return $this->expressionService;
    }

    /**
     * Get WordUploadService (lazy loaded).
     *
     * @return WordUploadService
     */
    private function getUploadService(): WordUploadService
    {
        if ($this->uploadService === null) {
            $this->uploadService = new WordUploadService();
        }
        return $this->uploadService;
    }

    /**
     * Set custom view path.
     *
     * @param string $path View path
     *
     * @return void
     */
    public function setViewPath(string $path): void
    {
        $this->viewPath = rtrim($path, '/') . '/';
    }

    // =========================================================================
    // Hover Actions (from text reading view)
    // =========================================================================

    /**
     * Create a term from hover action in reading view.
     *
     * This is called when user clicks a status from the hover menu
     * while reading a text.
     *
     * @param int    $textId     Text ID
     * @param string $wordText   Word text
     * @param int    $status     Word status (1-5)
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     *
     * @return array Term creation result
     */
    public function createFromHover(
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
     * Handle the hover create action from reading view.
     *
     * This is the route handler that parses request params and
     * renders the result view.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function hoverCreate(array $params): void
    {
        $text = InputValidator::getString('text');
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $status = InputValidator::getInt('status', 1) ?? 1;
        $targetLang = InputValidator::getString('tl');
        $sourceLang = InputValidator::getString('sl');

        // Create the term
        $result = $this->createFromHover(
            $textId,
            $text,
            $status,
            $sourceLang,
            $targetLang
        );

        // Render page
        PageLayoutHelper::renderPageStart("New Term: " . $result['word'], false);

        // Prepare view variables
        $word = $result['word'];
        $wordRaw = $result['wordRaw'];
        $wid = $result['wid'];
        $hex = $result['hex'];
        $translation = $result['translation'];

        $this->render('hover_save_result', [
            'word' => $word,
            'wordRaw' => $wordRaw,
            'wid' => $wid,
            'hex' => $hex,
            'translation' => $translation,
            'textId' => $textId,
            'status' => $status,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    // =========================================================================
    // Similar Terms
    // =========================================================================

    /**
     * Get similar terms for a given term.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function similarTerms(array $params): void
    {
        $langId = InputValidator::getInt('lgid', 0) ?? 0;
        $term = InputValidator::getString('term');

        header('Content-Type: text/html; charset=utf-8');
        echo $this->findSimilarTerms->getFormattedTerms($langId, $term);
    }

    // =========================================================================
    // Dictionary Links
    // =========================================================================

    /**
     * Get dictionary links for editing.
     *
     * @param int    $langId    Language ID
     * @param string $word      Word to look up
     * @param string $sentctlid Sentence control ID
     * @param bool   $openFirst Open first dictionary
     *
     * @return string HTML dictionary links
     */
    public function getDictionaryLinks(
        int $langId,
        string $word,
        string $sentctlid,
        bool $openFirst = false
    ): string {
        return $this->dictionaryAdapter->createDictLinksInEditWin(
            $langId,
            $word,
            $sentctlid,
            $openFirst
        );
    }

    // =========================================================================
    // CRUD Operations (delegating to facade)
    // =========================================================================

    /**
     * Show term edit form.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo 'Term ID required';
            return;
        }

        $term = $this->facade->getTerm($termId);

        if ($term === null) {
            http_response_code(404);
            echo 'Term not found';
            return;
        }

        PageLayoutHelper::renderPageStart("Edit Term: " . $term->text(), false);

        $this->render('form_edit', [
            'term' => $term,
            'dictionaryLinks' => $this->getDictionaryLinks(
                $term->languageId()->toInt(),
                $term->text(),
                'sentence_textarea',
                true
            ),
            'similarTermsHtml' => $this->findSimilarTerms->getFormattedTerms(
                $term->languageId()->toInt(),
                $term->textLowercase()
            ),
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Show term details.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function show(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo 'Term ID required';
            return;
        }

        $term = $this->facade->getTerm($termId);

        if ($term === null) {
            http_response_code(404);
            echo 'Term not found';
            return;
        }

        PageLayoutHelper::renderPageStart("Term: " . $term->text(), false);

        $this->render('show', [
            'term' => $term,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Update term status.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function updateStatus(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;
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
     * Delete term.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function delete(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID required']);
            return;
        }

        $result = $this->facade->deleteTerm($termId);

        header('Content-Type: application/json');
        echo json_encode(['deleted' => $result]);
    }

    // =========================================================================
    // View Rendering
    // =========================================================================

    /**
     * Render a view.
     *
     * @param string $view View name (without .php)
     * @param array  $data View data
     *
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract($data);
        require $viewFile;
    }

    // =========================================================================
    // Native Implementations (migrated from WordController)
    // =========================================================================

    /**
     * Delete word.
     *
     * Call: ?tid=[textid]&wid=[wordid]
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use DELETE /api/v1/terms/{id} instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     */
    public function deleteWord(array $params): void
    {
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $wordId = InputValidator::getInt('wid', 0) ?? 0;

        if ($textId === 0 || $wordId === 0) {
            return;
        }

        $term = $this->facade->getTerm($wordId);
        if ($term === null) {
            return;
        }

        $termText = $term->text();
        $message = $this->facade->deleteTerm($wordId) ? 'Term deleted' : 'Delete failed';

        PageLayoutHelper::renderPageStart("Term: " . $termText, false);

        $wid = $wordId;
        $this->render('delete_result', [
            'term' => $termText,
            'wid' => $wid,
            'textId' => $textId,
            'message' => $message,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Show word details.
     *
     * Call: ?wid=[wordid]&ann=[annotation]
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function showWord(array $params): void
    {
        PageLayoutHelper::renderPageStartNobody('Term');

        $wid = InputValidator::getString('wid');
        $ann = InputValidator::getString('ann');

        if ($wid === '') {
            echo '<p>Word ID is required</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $term = $this->facade->getTerm((int) $wid);
        if ($term === null) {
            echo '<p>Word not found</p>';
            PageLayoutHelper::renderPageEnd();
            return;
        }

        $tags = TagsFacade::getWordTagList((int) $wid, false);
        $scrdir = $this->languageFacade->getScriptDirectionTag($term->languageId()->toInt());

        // Convert Term entity to array for view compatibility
        $word = [
            'text' => $term->text(),
            'translation' => $term->translation(),
            'sentence' => $term->sentence(),
            'romanization' => $term->romanization(),
            'notes' => $term->notes(),
            'status' => $term->status()->toInt(),
            'langId' => $term->languageId()->toInt(),
        ];

        $this->render('show', [
            'word' => $word,
            'tags' => $tags,
            'scrdir' => $scrdir,
            'ann' => $ann,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Set word status.
     *
     * Call: ?tid=[textid]&wid=[wordid]&status=1..5/98/99
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use PUT /api/v1/terms/{id}/status/{status} instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     */
    public function setStatus(array $params): void
    {
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $wordId = InputValidator::getInt('wid', 0) ?? 0;
        $status = InputValidator::getInt('status', 0) ?? 0;

        if ($textId === 0 || $wordId === 0 || $status === 0) {
            return;
        }

        $term = $this->facade->getTerm($wordId);
        if ($term === null) {
            return;
        }

        // Update the status
        $this->facade->updateStatus($wordId, $status);

        $termText = $term->text();
        $tagList = TagsFacade::getWordTagList($wordId, false);
        $formattedTags = $tagList !== '' ? ' [' . $tagList . ']' : '';
        $translation = $term->translation() . $formattedTags;
        $romanization = $term->romanization();

        PageLayoutHelper::renderPageStart("Term: $termText", false);

        $this->render('status_result', [
            'wid' => $wordId,
            'textId' => $textId,
            'status' => $status,
            'term' => $termText,
            'translation' => $translation,
            'romanization' => $romanization,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Insert well-known word.
     *
     * Call: ?tid=[textid]&ord=[textpos]
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use POST /api/v1/terms/quick with status=99 instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     */
    public function insertWellknown(array $params): void
    {
        $this->insertWordWithStatus(99, 'OK, you know this term well!');
    }

    /**
     * Insert ignored word.
     *
     * Call: ?tid=[textid]&ord=[textpos]
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use POST /api/v1/terms/quick with status=98 instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     */
    public function insertIgnore(array $params): void
    {
        $this->insertWordWithStatus(98, 'OK, this term will be ignored!');
    }

    /**
     * Insert word with a specific status (helper for wellknown/ignore).
     *
     * @param int    $status  Status to set (98 or 99)
     * @param string $message Success message
     *
     * @return void
     */
    private function insertWordWithStatus(int $status, string $message): void
    {
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $ord = InputValidator::getInt('ord', 0) ?? 0;

        if ($textId === 0 || $ord === 0) {
            return;
        }

        // Get word at position from text items
        $record = QueryBuilder::table('textitems2')
            ->select(['Ti2Text', 'Ti2LgID'])
            ->where('Ti2TxID', '=', $textId)
            ->where('Ti2Order', '=', $ord)
            ->where('Ti2WordCount', '=', 1)
            ->firstPrepared();

        if ($record === null) {
            return;
        }

        $word = (string) $record['Ti2Text'];
        $langId = (int) $record['Ti2LgID'];
        $wordLc = mb_strtolower($word, 'UTF-8');

        // Check if term already exists
        $existingTerm = $this->facade->findByText($langId, $wordLc);
        if ($existingTerm !== null) {
            // Update existing term status
            $this->facade->updateStatus($existingTerm->id()->toInt(), $status);
            $wid = $existingTerm->id()->toInt();
        } else {
            // Create new term
            $term = $this->facade->createTerm(
                $langId,
                $word,
                $status,
                '*', // No translation
                '',  // No sentence
                '',  // No notes
                '',  // No romanization
                1    // Word count
            );
            $wid = $term->id()->toInt();
        }

        $hex = StringUtils::toClassName($wordLc);

        PageLayoutHelper::renderPageStart("Term: " . $word, false);

        $viewName = $status === 99 ? 'insert_wellknown_result' : 'insert_ignore_result';
        $this->render($viewName, [
            'term' => $word,
            'wid' => $wid,
            'hex' => $hex,
            'textId' => $textId,
        ]);

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Inline edit word.
     *
     * Handles AJAX inline editing of translation or romanization fields.
     * POST parameters:
     * - id: string - Field identifier (e.g., "trans123" or "roman123" where 123 is word ID)
     * - value: string - New value for the field
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function inlineEdit(array $params): void
    {
        $value = InputValidator::getStringFromPost('value');
        $id = InputValidator::getStringFromPost('id');

        if (substr($id, 0, 5) === 'trans') {
            $wordId = (int) substr($id, 5);
            $term = $this->facade->getTerm($wordId);
            if ($term === null) {
                echo 'ERROR - term not found!';
                return;
            }
            $this->facade->updateTerm($wordId, null, $value ?: '*', null, null, null);
            $displayValue = $value ?: '*';
            echo htmlspecialchars($displayValue, ENT_QUOTES, 'UTF-8');
            return;
        }

        if (substr($id, 0, 5) === 'roman') {
            $wordId = (int) substr($id, 5);
            $term = $this->facade->getTerm($wordId);
            if ($term === null) {
                echo 'ERROR - term not found!';
                return;
            }
            $this->facade->updateTerm($wordId, null, null, null, null, $value);
            echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            return;
        }

        echo 'ERROR - please refresh page!';
    }

    // =========================================================================
    // Edit Word Methods (migrated from WordController)
    // =========================================================================

    /**
     * Edit word form.
     *
     * Handles:
     * - Display edit form: ?wid=[wordid] or ?tid=[textid]&ord=[ord]
     * - Save/Update: ?op=Save or ?op=Change
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function editWord(array $params): void
    {
        $wid = InputValidator::getString('wid');
        $tid = InputValidator::getString('tid');
        $ord = InputValidator::getString('ord');
        $op = InputValidator::getString('op');

        // Check for valid entry point
        if ($wid === '' && $tid . $ord === '' && $op === '') {
            return;
        }

        $fromAnn = InputValidator::getString('fromAnn');

        if ($op !== '') {
            $this->handleEditWordOperation();
        } else {
            $widInt = ($wid !== '' && is_numeric($wid)) ? (int) $wid : -1;
            $textId = InputValidator::getInt('tid', 0) ?? 0;
            $ordInt = InputValidator::getInt('ord', 0) ?? 0;
            $this->displayEditWordForm($widInt, $textId, $ordInt, $fromAnn);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle save/update operation for word edit.
     *
     * @return void
     */
    private function handleEditWordOperation(): void
    {
        $textlc = trim(Escaping::prepareTextdata(InputValidator::getString('WoTextLC')));
        $text = trim(Escaping::prepareTextdata(InputValidator::getString('WoText')));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            echo '<p class="msg">' . $message . '</p>';
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        $translation = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
        if ($translation == '') {
            $translation = '*';
        }

        $op = InputValidator::getString('op');
        $requestData = $this->getWordFormData();

        if ($op == 'Save') {
            // Insert new term
            $result = $this->getWordService()->create($requestData);
            $hex = $this->getWordService()->textToClassName(InputValidator::getString('WoTextLC'));
            $oldStatus = 0;
            $titletext = "New Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
        } else {
            // Update existing term
            $result = $this->getWordService()->update(InputValidator::getInt('WoID', 0) ?? 0, $requestData);
            $hex = null;
            $oldStatus = InputValidator::getString('WoOldStatus');
            $titletext = "Edit Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
        }

        PageLayoutHelper::renderPageStartNobody($titletext);
        echo '<h1>' . $titletext . '</h1>';

        $wid = $result['id'];
        $message = $result['message'];

        TagsFacade::saveWordTagsFromForm($wid);

        // Prepare view variables
        $textId = InputValidator::getInt('tid', 0) ?? 0;
        $status = InputValidator::getString('WoStatus');
        $romanization = InputValidator::getString('WoRomanization');

        $this->render('edit_result', [
            'wid' => $wid,
            'message' => $message,
            'textId' => $textId,
            'status' => $status,
            'romanization' => $romanization,
            'translation' => $translation,
            'hex' => $hex,
            'oldStatus' => $oldStatus,
            'isNew' => ($op == 'Save'),
        ]);
    }

    /**
     * Display the word edit form (new or existing).
     *
     * @param int    $wid     Word ID (-1 for new)
     * @param int    $textId  Text ID
     * @param int    $ord     Word order position
     * @param string $fromAnn From annotation flag
     *
     * @return void
     */
    private function displayEditWordForm(int $wid, int $textId, int $ord, string $fromAnn): void
    {
        $wordService = $this->getWordService();

        if ($wid == -1) {
            // Get the term from text items
            $termData = $wordService->getTermFromTextItem($textId, $ord);
            if ($termData === null) {
                ErrorHandler::die("Cannot access Term and Language in edit_word.php");
            }
            $term = (string) $termData['Ti2Text'];
            $lang = (int) $termData['Ti2LgID'];
            $termlc = mb_strtolower($term, 'UTF-8');

            // Check if word already exists
            $existingId = $wordService->findByText($termlc, $lang);
            if ($existingId !== null) {
                $new = false;
                $wid = $existingId;
            } else {
                $new = true;
            }
        } else {
            // Get existing word data
            $wordData = $wordService->findById($wid);
            if (!$wordData) {
                ErrorHandler::die("Cannot access Term and Language in edit_word.php");
            }
            $term = (string) $wordData['WoText'];
            $lang = (int) $wordData['WoLgID'];
            $termlc = mb_strtolower($term, 'UTF-8');
            $new = false;
        }

        $titletext = ($new ? "New Term" : "Edit Term") . ": " . htmlspecialchars($term, ENT_QUOTES, 'UTF-8');
        PageLayoutHelper::renderPageStartNobody($titletext);

        $scrdir = $this->languageFacade->getScriptDirectionTag($lang);
        $langData = $wordService->getLanguageData($lang);
        $showRoman = $langData['showRoman'];

        if ($new) {
            // New word form
            $sentence = $wordService->getSentenceForTerm($textId, $ord, $termlc);
            $transUri = $langData['translateUri'];
            $lgname = $langData['name'];
            $langShort = array_key_exists($lgname, LanguagePresets::getAll()) ?
                LanguagePresets::getAll()[$lgname][1] : '';

            $this->render('form_edit_new', [
                'term' => $term,
                'termlc' => $termlc,
                'lang' => $lang,
                'sentence' => $sentence,
                'transUri' => $transUri,
                'lgname' => $lgname,
                'langShort' => $langShort,
                'scrdir' => $scrdir,
                'showRoman' => $showRoman,
                'textId' => $textId,
                'ord' => $ord,
                'dictionaryAdapter' => $this->dictionaryAdapter,
            ]);
        } else {
            // Edit existing word form
            $wordData = $wordService->findById($wid);
            if (!$wordData) {
                ErrorHandler::die("Cannot access word data");
            }

            $status = $wordData['WoStatus'];
            if ($fromAnn == '' && $status >= 98) {
                $status = 1;
            }

            $sentence = ExportService::replaceTabNewline($wordData['WoSentence']);
            if ($sentence == '' && $textId !== 0 && $ord !== 0) {
                $sentence = $wordService->getSentenceForTerm($textId, $ord, $termlc);
            }

            $transl = ExportService::replaceTabNewline($wordData['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }

            // Get showRoman from language joined with text
            $showRoman = (bool) QueryBuilder::table('languages')
                ->join('texts', 'TxLgID', '=', 'LgID')
                ->where('TxID', '=', $textId)
                ->valuePrepared('LgShowRomanization');

            $this->render('form_edit_existing', [
                'wid' => $wid,
                'term' => $term,
                'termlc' => $termlc,
                'lang' => $lang,
                'status' => $status,
                'sentence' => $sentence,
                'transl' => $transl,
                'wordData' => $wordData,
                'scrdir' => $scrdir,
                'showRoman' => $showRoman,
                'textId' => $textId,
                'ord' => $ord,
                'dictionaryAdapter' => $this->dictionaryAdapter,
            ]);
        }
    }

    /**
     * Edit term while testing.
     *
     * Call: ?wid=[wordid] - display edit form
     *       ?op=Change - update the term
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function editTerm(array $params): void
    {
        $translation_raw = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
        $translation = ($translation_raw == '') ? '*' : $translation_raw;

        $op = InputValidator::getString('op');
        if ($op !== '') {
            $this->handleEditTermOperation($translation);
        } else {
            $this->displayEditTermForm();
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle update operation for edit term.
     *
     * @param string $translation Translation value
     *
     * @return void
     */
    private function handleEditTermOperation(string $translation): void
    {
        $woTextLC = InputValidator::getString('WoTextLC');
        $woText = InputValidator::getString('WoText');
        $textlc = trim(Escaping::prepareTextdata($woTextLC));
        $text = trim(Escaping::prepareTextdata($woText));

        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars(Escaping::prepareTextdata($woTextLC), ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            echo '<p class="msg">' . $message . '</p>';
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        $op = InputValidator::getString('op');
        if ($op == 'Change') {
            $titletext = "Edit Term: " . htmlspecialchars(Escaping::prepareTextdata($woTextLC), ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $oldstatus = InputValidator::getString('WoOldStatus');
            $newstatus = InputValidator::getString('WoStatus');
            $woId = InputValidator::getInt('WoID', 0) ?? 0;
            $woSentence = InputValidator::getString('WoSentence');
            $woRomanization = InputValidator::getString('WoRomanization');

            $scoreRandomUpdate = TermStatusService::makeScoreRandomInsertUpdate('u');
            $sentenceEscaped = ExportService::replaceTabNewline($woSentence);

            if ($oldstatus != $newstatus) {
                // Status changed - update with status change timestamp
                $bindings = [
                    $woText, $translation, $sentenceEscaped, $woRomanization,
                    $newstatus, $woId
                ];
                $sql = "UPDATE words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?,
                    WoStatus = ?, WoStatusChanged = NOW(), {$scoreRandomUpdate}
                    WHERE WoID = ?"
                    . \Lwt\Shared\Infrastructure\Database\UserScopedQuery::forTablePrepared('words', $bindings);
                Connection::preparedExecute($sql, $bindings);
            } else {
                // Status unchanged
                $bindings = [
                    $woText, $translation, $sentenceEscaped, $woRomanization,
                    $woId
                ];
                $sql = "UPDATE words SET
                    WoText = ?, WoTranslation = ?, WoSentence = ?, WoRomanization = ?,
                    {$scoreRandomUpdate}
                    WHERE WoID = ?"
                    . \Lwt\Shared\Infrastructure\Database\UserScopedQuery::forTablePrepared('words', $bindings);
                Connection::preparedExecute($sql, $bindings);
            }
            $wid = $woId;
            TagsFacade::saveWordTagsFromForm($wid);

            $message = 'Updated';

            $lang = QueryBuilder::table('words')
                ->where('WoID', '=', $wid)
                ->valuePrepared('WoLgID');
            if (!isset($lang)) {
                ErrorHandler::die('Cannot retrieve language in edit_tword.php');
            }
            $regexword = QueryBuilder::table('languages')
                ->where('LgID', '=', $lang)
                ->valuePrepared('LgRegexpWordCharacters');
            if (!isset($regexword)) {
                ErrorHandler::die('Cannot retrieve language data in edit_tword.php');
            }
            $sent = htmlspecialchars(ExportService::replaceTabNewline($woSentence), ENT_QUOTES, 'UTF-8');
            $sent1 = str_replace(
                "{",
                ' <b>[',
                str_replace(
                    "}",
                    ']</b> ',
                    ExportService::maskTermInSentence($sent, $regexword)
                )
            );

            $status = $newstatus;
            $romanization = $woRomanization;
            $text = $woText;

            $this->render('edit_term_result', [
                'wid' => $wid,
                'message' => $message,
                'status' => $status,
                'romanization' => $romanization,
                'translation' => $translation,
                'text' => $text,
                'sent1' => $sent1,
            ]);
        }
    }

    /**
     * Display the edit term form.
     *
     * @return void
     */
    private function displayEditTermForm(): void
    {
        $widParam = InputValidator::getString('wid');

        if ($widParam == '') {
            ErrorHandler::die("Term ID missing in edit_tword.php");
        }
        $wid = (int) $widParam;

        $record = QueryBuilder::table('words')
            ->select(['WoText', 'WoLgID', 'WoTranslation', 'WoSentence', 'WoNotes', 'WoRomanization', 'WoStatus'])
            ->where('WoID', '=', $wid)
            ->firstPrepared();
        if ($record) {
            $term = (string) $record['WoText'];
            $lang = (int) $record['WoLgID'];
            $transl = ExportService::replaceTabNewline($record['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }
            $sentence = ExportService::replaceTabNewline($record['WoSentence']);
            $notes = ExportService::replaceTabNewline($record['WoNotes'] ?? '');
            $rom = $record['WoRomanization'];
            $status = $record['WoStatus'];
            $showRoman = (bool) QueryBuilder::table('languages')
                ->where('LgID', '=', $lang)
                ->valuePrepared('LgShowRomanization');
        } else {
            ErrorHandler::die("Term data not found in edit_tword.php");
        }

        $termlc = mb_strtolower($term, 'UTF-8');
        $titletext = "Edit Term: " . htmlspecialchars($term, ENT_QUOTES, 'UTF-8');
        PageLayoutHelper::renderPageStartNobody($titletext);
        $scrdir = $this->languageFacade->getScriptDirectionTag($lang);

        $this->render('form_edit_term', [
            'wid' => $wid,
            'term' => $term,
            'termlc' => $termlc,
            'lang' => $lang,
            'transl' => $transl,
            'sentence' => $sentence,
            'notes' => $notes,
            'rom' => $rom,
            'status' => $status,
            'showRoman' => $showRoman,
            'scrdir' => $scrdir,
            'dictionaryAdapter' => $this->dictionaryAdapter,
        ]);
    }

    /**
     * Edit multi-word expression.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function editMulti(array $params): void
    {
        $op = InputValidator::getString('op');
        if ($op !== '') {
            // Handle save/update operation
            $this->handleMultiWordOperation();
        } else {
            // Display form
            $this->displayMultiWordForm();
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle multi-word save/update operation.
     *
     * @return void
     */
    private function handleMultiWordOperation(): void
    {
        $textlc = trim(InputValidator::getString('WoTextLC'));
        $text = trim(InputValidator::getString('WoText'));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars($textlc, ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            echo '<p class="msg">' . $message . '</p>';
            return;
        }

        $translationRaw = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
        $translation = ($translationRaw == '') ? '*' : $translationRaw;

        $woText = InputValidator::getString('WoText');
        $woRomanization = InputValidator::getString('WoRomanization');
        $woSentence = InputValidator::getString('WoSentence');
        $woStatus = InputValidator::getInt('WoStatus', 0) ?? 0;
        $data = [
            'text' => Escaping::prepareTextdata($woText),
            'textlc' => Escaping::prepareTextdata($textlc),
            'translation' => $translation,
            'roman' => $woRomanization,
            'sentence' => $woSentence,
        ];

        $op = InputValidator::getString('op');
        $wordService = $this->getWordService();

        if ($op == 'Save') {
            // Insert new multi-word
            $data['status'] = $woStatus;
            $data['lgid'] = InputValidator::getInt('WoLgID', 0) ?? 0;
            $data['wordcount'] = InputValidator::getInt('len', 0) ?? 0;

            $titletext = "New Term: " . htmlspecialchars($data['textlc'], ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $wordService->createMultiWord($data);
            $wid = $result['id'];
        } else {
            // Update existing multi-word
            $wid = InputValidator::getInt('WoID', 0) ?? 0;
            $oldStatus = InputValidator::getInt('WoOldStatus', 0) ?? 0;
            $newStatus = $woStatus;

            $titletext = "Edit Term: " . htmlspecialchars($data['textlc'], ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $wordService->updateMultiWord($wid, $data, $oldStatus, $newStatus);

            // Prepare data for view
            $tagList = TagsFacade::getWordTagList($wid, false);
            $formattedTags = $tagList !== '' ? ' [' . $tagList . ']' : '';
            $termJson = $wordService->exportTermAsJson(
                $wid,
                $data['text'],
                $data['roman'],
                $translation . $formattedTags,
                $newStatus
            );
            $oldStatusValue = $oldStatus;

            $this->render('edit_multi_update_result', [
                'wid' => $wid,
                'result' => $result,
                'termJson' => $termJson,
                'oldStatusValue' => $oldStatusValue,
            ]);
        }
    }

    /**
     * Display multi-word edit form (new or existing).
     *
     * @return void
     */
    private function displayMultiWordForm(): void
    {
        $tid = InputValidator::getInt('tid', 0) ?? 0;
        $ord = InputValidator::getInt('ord', 0) ?? 0;
        $strWid = InputValidator::getString('wid');
        $wordService = $this->getWordService();

        // Determine if we're editing an existing word or creating new
        if ($strWid == "" || !is_numeric($strWid)) {
            // No ID provided: check if text exists in database
            $lgid = $wordService->getLanguageIdFromText($tid);
            $txtParam = InputValidator::getString('txt');
            $textlc = mb_strtolower(
                Escaping::prepareTextdata($txtParam),
                'UTF-8'
            );

            $strWid = $wordService->findMultiWordByText($textlc, (int) $lgid);
        }

        if ($strWid === null) {
            // New multi-word
            $txtParam = InputValidator::getString('txt');
            $len = InputValidator::getInt('len', 0) ?? 0;
            PageLayoutHelper::renderPageStartNobody("New Term: " . $txtParam);
            $this->displayNewMultiWordForm($txtParam, $tid, $ord, $len);
        } else {
            // Edit existing multi-word
            $wid = (int) $strWid;
            $wordData = $wordService->getMultiWordData($wid);
            if ($wordData === null) {
                ErrorHandler::die("Cannot access Term and Language in edit_mword.php");
            }
            PageLayoutHelper::renderPageStartNobody("Edit Term: " . $wordData['text']);
            $this->displayEditMultiWordForm($wid, $wordData, $tid, $ord);
        }
    }

    /**
     * Display form for new multi-word.
     *
     * @param string $text Original text
     * @param int    $tid  Text ID
     * @param int    $ord  Text order
     * @param int    $len  Number of words
     *
     * @return void
     */
    private function displayNewMultiWordForm(string $text, int $tid, int $ord, int $len): void
    {
        $wordService = $this->getWordService();
        $lgid = $wordService->getLanguageIdFromText($tid);
        $termText = Escaping::prepareTextdata($text);
        $textlc = mb_strtolower($termText, 'UTF-8');

        // Check if word already exists
        $existingWid = $wordService->findMultiWordByText($textlc, (int) $lgid);
        if ($existingWid !== null) {
            // Get text from existing word
            $wordData = $wordService->getMultiWordData($existingWid);
            if ($wordData !== null) {
                $termText = $wordData['text'];
            }
        }

        $scrdir = $this->languageFacade->getScriptDirectionTag((int) $lgid);
        $seid = $wordService->getSentenceIdAtPosition($tid, $ord) ?? 0;
        $sent = $this->getSentenceService()->formatSentence(
            $seid,
            $textlc,
            (int) \Lwt\Shared\Infrastructure\Database\Settings::getWithDefault('set-term-sentence-count')
        );
        $showRoman = $wordService->shouldShowRomanization($tid);

        // Variables for view
        $term = (object) [
            'lgid' => $lgid,
            'text' => $termText,
            'textlc' => $textlc,
            'id' => $existingWid
        ];
        $sentence = ExportService::replaceTabNewline($sent[1] ?? '');

        $this->render('form_edit_multi_new', [
            'term' => $term,
            'sentence' => $sentence,
            'scrdir' => $scrdir,
            'showRoman' => $showRoman,
            'tid' => $tid,
            'ord' => $ord,
            'len' => $len,
            'dictionaryAdapter' => $this->dictionaryAdapter,
        ]);
    }

    /**
     * Display form for editing existing multi-word.
     *
     * @param int   $wid      Word ID
     * @param array $wordData Word data from service
     * @param int   $tid      Text ID
     * @param int   $ord      Text order
     *
     * @return void
     */
    private function displayEditMultiWordForm(int $wid, array $wordData, int $tid, int $ord): void
    {
        $lgid = $wordData['lgid'];
        $termText = $wordData['text'];
        $textlc = mb_strtolower($termText, 'UTF-8');

        $scrdir = $this->languageFacade->getScriptDirectionTag($lgid);
        $showRoman = $this->getWordService()->shouldShowRomanization($tid);

        $this->render('form_edit_multi_existing', [
            'wid' => $wid,
            'wordData' => $wordData,
            'termText' => $termText,
            'textlc' => $textlc,
            'lgid' => $lgid,
            'scrdir' => $scrdir,
            'showRoman' => $showRoman,
            'tid' => $tid,
            'ord' => $ord,
            'dictionaryAdapter' => $this->dictionaryAdapter,
        ]);
    }

    /**
     * Create new word form.
     *
     * Handles:
     * - Display form: ?lang=[langid]&text=[textid]
     * - Save: ?op=Save
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function createWord(array $params): void
    {
        $op = InputValidator::getString('op');
        $wordService = $this->getWordService();

        // Handle save operation
        if ($op === 'Save') {
            $requestData = $this->getWordFormData();
            $result = $wordService->create($requestData);

            $titletext = "New Term: " . htmlspecialchars($result['textlc'] ?? '', ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            if (!$result['success']) {
                // Handle duplicate entry error
                if (strpos($result['message'], 'Duplicate entry') !== false) {
                    $message = 'Error: <b>Duplicate entry for <i>' . $result['textlc'] .
                        '</i></b><br /><br /><input type="button" value="&lt;&lt; Back" data-action="back" />';
                } else {
                    $message = $result['message'];
                }
                echo '<p>' . $message . '</p>';
            } else {
                $wid = $result['id'];
                TagsFacade::saveWordTagsFromForm($wid);
                \Lwt\Shared\Infrastructure\Database\Maintenance::initWordCount();

                echo '<p>' . $result['message'] . '</p>';

                $woLgId = InputValidator::getInt('WoLgID', 0) ?? 0;
                $len = $wordService->getWordCount($wid);
                if ($len > 1) {
                    $this->getExpressionService()->insertExpressions($result['textlc'], $woLgId, $wid, $len, 0);
                } elseif ($len == 1) {
                    $wordService->linkToTextItems($wid, $woLgId, $result['textlc']);

                    // Prepare view variables
                    $hex = $wordService->textToClassName($result['textlc']);
                    $translation = ExportService::replaceTabNewline(InputValidator::getString('WoTranslation'));
                    if ($translation === '') {
                        $translation = '*';
                    }
                    $status = InputValidator::getString('WoStatus');
                    $romanization = InputValidator::getString('WoRomanization');
                    $text = $result['text'];
                    $textId = InputValidator::getInt('tid', 0) ?? 0;
                    $success = true;
                    $message = $result['message'];

                    $this->render('save_result', [
                        'wid' => $wid,
                        'hex' => $hex,
                        'translation' => $translation,
                        'status' => $status,
                        'romanization' => $romanization,
                        'text' => $text,
                        'textId' => $textId,
                        'success' => $success,
                        'message' => $message,
                    ]);
                }
            }
        } else {
            // Display the new word form
            $lang = InputValidator::getInt('lang', 0) ?? 0;
            $textId = InputValidator::getInt('text', 0) ?? 0;
            $scrdir = $this->languageFacade->getScriptDirectionTag($lang);

            $langData = $wordService->getLanguageData($lang);
            $showRoman = $langData['showRoman'];
            $dictService = $this->dictionaryAdapter;

            PageLayoutHelper::renderPageStartNobody('');

            $this->render('form_new', [
                'lang' => $lang,
                'textId' => $textId,
                'scrdir' => $scrdir,
                'showRoman' => $showRoman,
                'dictService' => $dictService,
                'langData' => $langData,
            ]);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Get form data for word create/update operations.
     *
     * @return array<string, mixed> Form data array
     */
    private function getWordFormData(): array
    {
        return [
            'WoID' => InputValidator::getInt('WoID'),
            'WoLgID' => InputValidator::getInt('WoLgID', 0) ?? 0,
            'WoText' => InputValidator::getString('WoText'),
            'WoTextLC' => InputValidator::getString('WoTextLC'),
            'WoStatus' => InputValidator::getString('WoStatus'),
            'WoOldStatus' => InputValidator::getString('WoOldStatus'),
            'WoTranslation' => InputValidator::getString('WoTranslation'),
            'WoRomanization' => InputValidator::getString('WoRomanization'),
            'WoSentence' => InputValidator::getString('WoSentence'),
            'tid' => InputValidator::getInt('tid'),
            'ord' => InputValidator::getInt('ord'),
            'len' => InputValidator::getInt('len'),
        ];
    }

    // =========================================================================
    // Legacy Delegation Methods (remaining complex operations)
    // =========================================================================

    /**
     * List/edit words - Alpine.js SPA version (legacy delegation).
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function listEditAlpine(array $params): void
    {
        $currentlang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        $perPage = (int) Settings::getWithDefault('set-terms-per-page');
        if ($perPage < 1) {
            $perPage = 50;
        }

        // Use a placeholder title - Alpine.js will update it dynamically
        PageLayoutHelper::renderPageStart('Terms', true);

        include $this->viewPath . 'list_alpine.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Bulk translate words.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function bulkTranslate(array $params): void
    {
        $tid = InputValidator::getInt('tid', 0) ?? 0;
        $pos = InputValidator::getInt('offset');

        // Handle form submission (save terms)
        $termsArray = InputValidator::getArray('term');
        if (!empty($termsArray)) {
            $terms = $termsArray;
            $cnt = count($terms);

            if ($pos !== null) {
                $pos -= $cnt;
            }

            PageLayoutHelper::renderPageStart($cnt . ' New Word' . ($cnt == 1 ? '' : 's') . ' Saved', false);
            $this->handleBulkSave($terms, $tid, $pos === null);
        } else {
            PageLayoutHelper::renderPageStartNobody('Translate New Words');
        }

        // Show next page of terms if there are more
        if ($pos !== null) {
            $sl = InputValidator::getString('sl');
            $tl = InputValidator::getString('tl');
            $this->displayBulkTranslateForm($tid, $sl !== '' ? $sl : null, $tl !== '' ? $tl : null, $pos);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle saving bulk translated terms.
     *
     * @param array<int, array<string, mixed>> $terms   Array of term data
     * @param int                              $tid     Text ID
     * @param bool                             $cleanUp Whether to clean up right frames after save
     *
     * @return void
     *
     * @psalm-suppress UnusedParam $tid and $cleanUp are used in included view file
     */
    private function handleBulkSave(array $terms, int $tid, bool $cleanUp): void
    {
        $wordService = $this->getWordService();
        $maxWoId = $wordService->bulkSaveTerms($terms);

        $tooltipMode = Settings::getWithDefault('set-tooltip-mode');
        $res = $wordService->getNewWordsAfter($maxWoId);

        $wordService->linkNewWordsToTextItems($maxWoId);

        // Prepare data for view
        $newWords = [];
        foreach ($res as $record) {
            $record['hex'] = StringUtils::toClassName(
                Escaping::prepareTextdata($record['WoTextLC'])
            );
            $record['translation'] = $record['WoTranslation'];
            $newWords[] = $record;
        }

        include $this->viewPath . 'bulk_save_result.php';
    }

    /**
     * Display the bulk translate form.
     *
     * @param int         $tid Text ID
     * @param string|null $sl  Source language code
     * @param string|null $tl  Target language code
     * @param int         $pos Offset position
     *
     * @return void
     */
    private function displayBulkTranslateForm(int $tid, ?string $sl, ?string $tl, int $pos): void
    {
        $wordService = $this->getWordService();
        $limit = (int) Settings::getWithDefault('set-ggl-translation-per-page') + 1;
        $dictionaries = $wordService->getLanguageDictionaries($tid);

        $res = $wordService->getUnknownWordsForBulkTranslate($tid, $pos, $limit);

        // Collect terms and check if there are more
        $terms = [];
        $hasMore = false;
        $cnt = 0;
        foreach ($res as $record) {
            $cnt++;
            if ($cnt < $limit) {
                $terms[] = $record;
            } else {
                $hasMore = true;
            }
        }

        // Calculate next offset if there are more terms
        $nextOffset = $hasMore ? $pos + $limit - 1 : null;

        include $this->viewPath . 'bulk_translate_form.php';
    }

    /**
     * Mark all words with status (well-known or ignore).
     *
     * @param array<string, string> $params Route parameters
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

        $wordService = $this->getWordService();
        list($count, $wordsData) = $wordService->markAllWordsWithStatus($textId, $status);
        $useTooltips = Settings::getWithDefault('set-tooltip-mode') == 1;

        include $this->viewPath . 'all_wellknown_result.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Upload words from file.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function upload(array $params): void
    {
        PageLayoutHelper::renderPageStart('Import Terms', true);

        $op = InputValidator::getString('op');
        if ($op === 'Import') {
            $this->handleUploadImport();
        } else {
            $this->displayUploadForm();
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Display the word upload form.
     *
     * @return void
     */
    private function displayUploadForm(): void
    {
        $currentLanguage = Settings::get('currentlanguage');
        $languages = $this->languageFacade->getLanguagesForSelect();
        include $this->viewPath . 'upload_form.php';
    }

    /**
     * Handle the word import operation.
     *
     * @return void
     */
    private function handleUploadImport(): void
    {
        $uploadService = $this->getUploadService();
        $tabType = InputValidator::getString("Tab");
        if ($tabType === '') {
            $tabType = 'c';
        }
        $langId = InputValidator::getInt("LgID", 0) ?? 0;

        if ($langId === 0) {
            echo '<p class="msgred">Error: No language selected</p>';
            return;
        }

        $langData = $uploadService->getLanguageData($langId);
        if ($langData === null) {
            echo '<p class="msgred">Error: Invalid language</p>';
            return;
        }

        $removeSpaces = (bool) $langData['LgRemoveSpaces'];

        // Parse column mapping
        $columns = [
            1 => InputValidator::getString("Col1"),
            2 => InputValidator::getString("Col2"),
            3 => InputValidator::getString("Col3"),
            4 => InputValidator::getString("Col4"),
            5 => InputValidator::getString("Col5"),
        ];
        $columns = array_unique($columns);

        $parsed = $uploadService->parseColumnMapping($columns, $removeSpaces);
        $col = $parsed['columns'];
        $fields = $parsed['fields'];

        // Check for file upload vs text input
        $fileUpl = (
            isset($_FILES["thefile"]) &&
            $_FILES["thefile"]["tmp_name"] != "" &&
            $_FILES["thefile"]["error"] == 0
        );

        // Get or create the input file
        $uploadText = InputValidator::getString("Upload");
        if ($fileUpl) {
            $fileName = $_FILES["thefile"]["tmp_name"];
        } else {
            if ($uploadText === '') {
                echo '<p class="msgred">Error: No data to import</p>';
                return;
            }
            $fileName = $uploadService->createTempFile($uploadText);
        }

        $ignoreFirst = InputValidator::getString("IgnFirstLine") === '1';
        $overwrite = InputValidator::getInt("Over", 0) ?? 0;
        $status = InputValidator::getInt("WoStatus", 1) ?? 1;
        $translDelim = InputValidator::getString("transl_delim");

        // Get last update timestamp before import
        $lastUpdate = $uploadService->getLastWordUpdate() ?? '';

        if ($fields["txt"] > 0) {
            // Import terms
            $this->importTerms(
                $uploadService,
                $langId,
                $fields,
                $col,
                $tabType,
                $fileName,
                $status,
                $overwrite,
                $ignoreFirst,
                $translDelim,
                $lastUpdate
            );

            // Display results
            $rtl = $uploadService->isRightToLeft($langId) ? 1 : 0;
            $recno = $uploadService->countImportedTerms($lastUpdate);
            include $this->viewPath . 'upload_result.php';
        } elseif ($fields["tl"] > 0) {
            // Import tags only
            $uploadService->importTagsOnly($fields, $tabType, $fileName, $ignoreFirst);
            echo '<p>Tags imported successfully.</p>';
        } else {
            echo '<p class="msgred">Error: No term column specified</p>';
        }

        // Clean up temp file if we created it
        if (!$fileUpl && file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Import terms from the uploaded file.
     *
     * @param WordUploadService       $uploadService  The upload service
     * @param int                     $langId         Language ID
     * @param array<string, int>      $fields         Field indexes
     * @param array<int, string>      $col            Column mapping
     * @param string                  $tabType        Tab type (c, t, h)
     * @param string                  $fileName       Path to input file
     * @param int                     $status         Word status
     * @param int                     $overwrite      Overwrite mode
     * @param bool                    $ignoreFirst    Ignore first line
     * @param string                  $translDelim    Translation delimiter
     * @param string                  $lastUpdate     Last update timestamp
     *
     * @return void
     */
    private function importTerms(
        WordUploadService $uploadService,
        int $langId,
        array $fields,
        array $col,
        string $tabType,
        string $fileName,
        int $status,
        int $overwrite,
        bool $ignoreFirst,
        string $translDelim,
        string $lastUpdate
    ): void {
        $columnsClause = '(' . rtrim(implode(',', $col), ',') . ')';
        $delimiter = $uploadService->getSqlDelimiter($tabType);

        // Use simple import for no tags and no overwrite, complete import otherwise
        if ($fields["tl"] == 0 && $overwrite == 0) {
            $uploadService->importSimple(
                $langId,
                $fields,
                $columnsClause,
                $delimiter,
                $fileName,
                $status,
                $ignoreFirst
            );
        } else {
            $uploadService->importComplete(
                $langId,
                $fields,
                $columnsClause,
                $delimiter,
                $fileName,
                $status,
                $overwrite,
                $ignoreFirst,
                $translDelim,
                $tabType
            );
        }

        // Post-import processing
        \Lwt\Shared\Infrastructure\Database\Maintenance::initWordCount();
        $uploadService->linkWordsToTextItems();
        $uploadService->handleMultiwords($langId, $lastUpdate);
    }

    // =========================================================================
    // JSON API Methods (for AJAX calls)
    // =========================================================================

    /**
     * Get term data as JSON.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function getTermJson(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID required']);
            return;
        }

        $term = $this->facade->getTerm($termId);

        if ($term === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Term not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'id' => $term->id()->toInt(),
            'text' => $term->text(),
            'textLc' => $term->textLowercase(),
            'translation' => $term->translation(),
            'romanization' => $term->romanization(),
            'sentence' => $term->sentence(),
            'status' => $term->status()->toInt(),
            'langId' => $term->languageId(),
        ]);
    }

    /**
     * Create term via AJAX.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function createJson(array $params): void
    {
        $langId = InputValidator::getInt('lgid', 0) ?? 0;
        $text = InputValidator::getString('text');
        $status = InputValidator::getInt('status', 1) ?? 1;
        $translation = InputValidator::getString('translation');
        $romanization = InputValidator::getString('romanization');
        $sentence = InputValidator::getString('sentence');

        if ($langId === 0 || $text === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Language ID and text required']);
            return;
        }

        try {
            $term = $this->facade->createTerm(
                $langId,
                $text,
                $status,
                $translation ?: '*',
                $romanization,
                $sentence
            );

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $term->id()->toInt(),
                'text' => $term->text(),
                'textLc' => $term->textLowercase(),
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update term via AJAX.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function updateJson(array $params): void
    {
        $termId = InputValidator::getInt('wid', 0) ?? 0;
        $translation = InputValidator::getString('translation');
        $romanization = InputValidator::getString('romanization');
        $sentence = InputValidator::getString('sentence');
        $status = InputValidator::getInt('status', 0) ?? 0;

        if ($termId === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Term ID required']);
            return;
        }

        try {
            $statusVal = $status !== 0 ? $status : null;

            $term = $this->facade->updateTerm(
                $termId,
                $statusVal,
                $translation !== '' ? $translation : null,
                $sentence !== '' ? $sentence : null,
                null, // notes
                $romanization !== '' ? $romanization : null
            );

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $term->id()->toInt(),
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
