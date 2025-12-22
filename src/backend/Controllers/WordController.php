<?php declare(strict_types=1);
/**
 * \file
 * \brief Word Controller - Vocabulary/term management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-wordcontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\UserScopedQuery;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\SelectOptionsBuilder;

require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/SelectOptionsBuilder.php';
require_once __DIR__ . '/../Services/TagService.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';
require_once __DIR__ . '/../Services/SimilarTermsService.php';
require_once __DIR__ . '/../Services/TextService.php';

use Lwt\Core\StringUtils;
use Lwt\Core\Utils\ErrorHandler;
use Lwt\Services\WordService;
use Lwt\Services\WordListService;
use Lwt\Services\WordUploadService;
use Lwt\Services\WordStatusService;
use Lwt\Services\ExpressionService;
use Lwt\Services\ExportService;
use Lwt\Services\SentenceService;
use Lwt\Services\TagService;
use Lwt\Services\LanguageService;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\TextService;
use Lwt\Core\Http\InputValidator;

/**
 * Controller for vocabulary/term management.
 *
 * Handles:
 * - Word/term CRUD operations
 * - Multi-word expressions
 * - Bulk operations (translate, status changes)
 * - Word import/upload
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class WordController extends BaseController
{
    protected WordService $wordService;
    protected LanguageService $languageService;
    protected WordListService $listService;
    protected WordUploadService $uploadService;
    protected ExportService $exportService;
    protected TextService $textService;
    protected ExpressionService $expressionService;
    protected SentenceService $sentenceService;

    /**
     * Create a new WordController.
     *
     * @param WordService|null       $wordService       Word service for vocabulary operations
     * @param LanguageService|null   $languageService   Language service for language operations
     * @param WordListService|null   $listService       Word list service
     * @param WordUploadService|null $uploadService     Word upload service
     * @param ExportService|null     $exportService     Export service
     * @param TextService|null       $textService       Text service
     * @param ExpressionService|null $expressionService Expression service
     * @param SentenceService|null   $sentenceService   Sentence service
     */
    public function __construct(
        ?WordService $wordService = null,
        ?LanguageService $languageService = null,
        ?WordListService $listService = null,
        ?WordUploadService $uploadService = null,
        ?ExportService $exportService = null,
        ?TextService $textService = null,
        ?ExpressionService $expressionService = null,
        ?SentenceService $sentenceService = null
    ) {
        parent::__construct();
        $this->wordService = $wordService ?? new WordService();
        $this->languageService = $languageService ?? new LanguageService();
        $this->listService = $listService ?? new WordListService();
        $this->uploadService = $uploadService ?? new WordUploadService();
        $this->exportService = $exportService ?? new ExportService();
        $this->textService = $textService ?? new TextService();
        $this->expressionService = $expressionService ?? new ExpressionService();
        $this->sentenceService = $sentenceService ?? new SentenceService();
    }

    /**
     * Get the word list service instance.
     *
     * @return WordListService
     */
    protected function getListService(): WordListService
    {
        return $this->listService;
    }

    /**
     * Get the word upload service instance.
     *
     * @return WordUploadService
     */
    protected function getUploadService(): WordUploadService
    {
        return $this->uploadService;
    }

    /**
     * Get the word service instance.
     *
     * @return WordService
     */
    public function getWordService(): WordService
    {
        return $this->wordService;
    }
    /**
     * Edit single word (replaces word_edit.php)
     *
     * Call: ?tid=[textid]&ord=[textpos]&wid= - new word
     *       ?tid=[textid]&ord=[textpos]&wid=[wordid] - edit existing
     *       ?op=Save - save new word
     *       ?op=Change - update existing word
     *       ?fromAnn=recno&tid=[textid]&ord=[textpos] - from annotation editing
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        // Check for valid entry point
        if (
            $this->param("wid") == ""
            && $this->param("tid") . $this->param("ord") == ""
            && !$this->hasParam("op")
        ) {
            return;
        }

        $fromAnn = $this->param("fromAnn");

        if ($this->hasParam('op')) {
            $this->handleEditOperation($fromAnn);
        } else {
            $wid = ($this->hasParam("wid") && is_numeric($this->param('wid')))
                ? $this->paramInt('wid', -1)
                : -1;
            $textId = $this->paramInt("tid", 0);
            $ord = $this->paramInt("ord", 0);
            $this->displayEditForm($wid, $textId, $ord, $fromAnn);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle save/update operation for word edit.
     *
     * @param string $fromAnn From annotation flag
     *
     * @return void
     */
    private function handleEditOperation(string $fromAnn): void
    {
        $textlc = trim(\Lwt\Database\Escaping::prepareTextdata($this->param("WoTextLC")));
        $text = trim(\Lwt\Database\Escaping::prepareTextdata($this->param("WoText")));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars($textlc ?? '', ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            $this->message($message, false);
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        $translation = ExportService::replaceTabNewline($this->param("WoTranslation"));
        if ($translation == '') {
            $translation = '*';
        }

        $op = $this->param('op');
        $requestData = $this->getWordFormData();
        if ($op == 'Save') {
            // Insert new term
            $result = $this->wordService->create($requestData);
            $isNew = true;
            $hex = $this->wordService->textToClassName($this->param("WoTextLC"));
            $oldStatus = 0;
            $titletext = "New Term: " . htmlspecialchars($textlc ?? '', ENT_QUOTES, 'UTF-8');
        } else {
            // Update existing term
            $result = $this->wordService->update($this->paramInt("WoID", 0) ?? 0, $requestData);
            $isNew = false;
            $hex = null;
            $oldStatus = $this->param('WoOldStatus');
            $titletext = "Edit Term: " . htmlspecialchars($textlc ?? '', ENT_QUOTES, 'UTF-8');
        }

        PageLayoutHelper::renderPageStartNobody($titletext);
        echo '<h1>' . $titletext . '</h1>';

        $wid = $result['id'];
        $message = $result['message'];

        TagService::saveWordTags($wid);

        // Prepare view variables
        $textId = $this->paramInt('tid', 0) ?? 0;
        $status = $this->param("WoStatus");
        $romanization = $this->param("WoRomanization");

        include __DIR__ . '/../Views/Word/edit_result.php';
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
    private function displayEditForm(int $wid, int $textId, int $ord, string $fromAnn): void
    {
        if ($wid == -1) {
            // Get the term from text items
            $termData = $this->wordService->getTermFromTextItem($textId, $ord);
            if ($termData === null) {
                ErrorHandler::die("Cannot access Term and Language in edit_word.php");
            }
            $term = (string) $termData['Ti2Text'];
            $lang = (int) $termData['Ti2LgID'];
            $termlc = mb_strtolower($term, 'UTF-8');

            // Check if word already exists
            $existingId = $this->wordService->findByText($termlc, $lang);
            if ($existingId !== null) {
                $new = false;
                $wid = $existingId;
            } else {
                $new = true;
            }
        } else {
            // Get existing word data
            $wordData = $this->wordService->findById($wid);
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

        $scrdir = $this->languageService->getScriptDirectionTag($lang);
        $langData = $this->wordService->getLanguageData($lang);
        $showRoman = $langData['showRoman'];

        if ($new) {
            // New word form
            $sentence = $this->wordService->getSentenceForTerm($textId, $ord, $termlc);
            $transUri = $langData['translateUri'];
            $lgname = $langData['name'];
            $langShort = array_key_exists($lgname, LanguageDefinitions::getAll()) ?
                LanguageDefinitions::getAll()[$lgname][1] : '';

            include __DIR__ . '/../Views/Word/form_edit_new.php';
        } else {
            // Edit existing word form
            $wordData = $this->wordService->findById($wid);
            if (!$wordData) {
                ErrorHandler::die("Cannot access word data");
            }

            $status = $wordData['WoStatus'];
            if ($fromAnn == '' && $status >= 98) {
                $status = 1;
            }

            $sentence = ExportService::replaceTabNewline($wordData['WoSentence']);
            if ($sentence == '' && $textId !== 0 && $ord !== 0) {
                $sentence = $this->wordService->getSentenceForTerm($textId, $ord, $termlc);
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

            include __DIR__ . '/../Views/Word/form_edit_existing.php';
        }
    }

    /**
     * Edit term while testing (replaces word_edit_term.php)
     *
     * Call: ?wid=[wordid] - display edit form
     *       ?op=Change - update the term
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function editTerm(array $params): void
    {
        $translation_raw = ExportService::replaceTabNewline($this->param("WoTranslation"));
        $translation = ($translation_raw == '') ? '*' : $translation_raw;

        if ($this->hasParam('op')) {
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
        $woTextLC = $this->param("WoTextLC");
        $woText = $this->param("WoText");
        $textlc = trim(\Lwt\Database\Escaping::prepareTextdata($woTextLC));
        $text = trim(\Lwt\Database\Escaping::prepareTextdata($woText));

        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars(\Lwt\Database\Escaping::prepareTextdata($woTextLC), ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            $this->message($message, false);
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        $op = $this->param('op');
        if ($op == 'Change') {
            $titletext = "Edit Term: " . htmlspecialchars(\Lwt\Database\Escaping::prepareTextdata($woTextLC), ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $oldstatus = $this->param("WoOldStatus");
            $newstatus = $this->param("WoStatus");
            $woId = $this->paramInt("WoID", 0) ?? 0;
            $woSentence = $this->param("WoSentence");
            $woRomanization = $this->param("WoRomanization");

            $scoreRandomUpdate = WordStatusService::makeScoreRandomInsertUpdate('u');
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
                    . \Lwt\Database\UserScopedQuery::forTablePrepared('words', $bindings);
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
                    . \Lwt\Database\UserScopedQuery::forTablePrepared('words', $bindings);
                Connection::preparedExecute($sql, $bindings);
            }
            $wid = $woId;
            TagService::saveWordTags($wid);

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

            include __DIR__ . '/../Views/Word/edit_term_result.php';
        }
    }

    /**
     * Display the edit term form.
     *
     * @return void
     */
    private function displayEditTermForm(): void
    {
        $widParam = $this->param('wid');

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
        $scrdir = $this->languageService->getScriptDirectionTag($lang);

        include __DIR__ . '/../Views/Word/form_edit_term.php';
    }

    /**
     * Edit words list (replaces words_edit.php)
     *
     * Handles:
     * - markaction=[opcode] ... do actions on marked terms
     * - allaction=[opcode] ... do actions on all terms
     * - del=[wordid] ... do delete
     * - op=Save ... do insert new
     * - op=Change ... do update
     * - new=1&lang=[langid] ... display new term screen
     * - chg=[wordid] ... display edit screen
     * - filterlang=[langid] ... language filter
     * - sort=[sortcode] ... sort
     * - page=[pageno] ... page
     * - query=[termtextfilter] ... term text filter
     * - status=[statuscode] ... status filter
     * - text=[textid] ... text filter
     * - tag1=[tagid] ... tag filter 1
     * - tag2=[tagid] ... tag filter 2
     * - tag12=0/1 ... tag1-tag2 OR=0, AND=1
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function listEdit(array $params): void
    {
        $listService = $this->getListService();

        // Process filter parameters
        $currentlang = \Lwt\Database\Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );
        $currentsort = InputValidator::getIntWithDb("sort", 'currentwordsort', 1);
        $currentpage = InputValidator::getIntWithSession("page", "currentwordpage", 1);
        $currentquery = InputValidator::getStringWithSession("query", "currentwordquery");
        $currentquerymode = InputValidator::getStringWithSession(
            "query_mode",
            "currentwordquerymode",
            'term,rom,transl'
        );
        $currentregexmode = \Lwt\Database\Settings::getWithDefault("set-regex-mode");
        $currentstatus = InputValidator::getStringWithSession("status", "currentwordstatus");
        $currenttext = \Lwt\Database\Validation::text(
            InputValidator::getStringWithSession("text", "currentwordtext")
        );
        $currenttexttag = InputValidator::getStringWithSession("texttag", "currentwordtexttag");
        $currenttextmode = InputValidator::getStringWithSession("text_mode", "currentwordtextmode", '0');
        $currenttag1 = \Lwt\Database\Validation::tag(
            InputValidator::getStringWithSession("tag1", "currentwordtag1"),
            $currentlang
        );
        $currenttag2 = \Lwt\Database\Validation::tag(
            InputValidator::getStringWithSession("tag2", "currentwordtag2"),
            $currentlang
        );
        $currenttag12 = InputValidator::getStringWithSession("tag12", "currentwordtag12");

        // Build filter conditions
        $whLang = $listService->buildLangCondition($currentlang);
        $whStat = $listService->buildStatusCondition($currentstatus);
        $whQuery = $listService->buildQueryCondition($currentquery, $currentquerymode, $currentregexmode);

        // Validate regex pattern
        if ($currentquery !== '' && $currentregexmode !== '') {
            if (!$listService->validateRegexPattern($currentquery)) {
                $currentquery = '';
                $whQuery = '';
                unset($_SESSION['currentwordquery']);
                if ($this->hasParam('query')) {
                    echo '<p id="hide3" class="warning-message">+++ Warning: Invalid Search +++</p>';
                }
            }
        }

        $whTag = $listService->buildTagCondition($currenttag1, $currenttag2, $currenttag12);

        // Check if we should skip page start for exports/tests
        $noPagestart = $this->isExportOrTestAction();

        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart(
                'Terms',
                true
            );
        }

        $message = '';

        // Handle mark actions
        if ($this->hasParam('markaction')) {
            $message = $this->handleMarkAction(
                $listService,
                $currenttext,
                $whLang,
                $whStat,
                $whQuery,
                $whTag
            );
        }

        // Handle all actions
        if ($this->hasParam('allaction')) {
            $message = $this->handleAllAction(
                $listService,
                $currenttext,
                $whLang,
                $whStat,
                $whQuery,
                $whTag
            );
            if ($message === null) {
                return; // Exit on redirect
            }
        }

        // Handle single delete
        $delId = $this->paramInt('del');
        if ($delId !== null) {
            $message = $listService->deleteSingleWord($delId);
        }

        // Handle save/update
        if ($this->hasParam('op')) {
            $wid = $this->handleListSaveUpdate($listService);
        }

        // Display appropriate view
        $langId = $this->paramInt('lang');
        if ($this->hasParam('new') && $langId !== null) {
            $this->displayListNewForm($listService, $langId);
        } elseif ($this->hasParam('chg')) {
            $this->displayListEditForm($listService, $this->paramInt('chg', 0) ?? 0);
        } else {
            $this->displayWordList(
                $listService,
                $message,
                $currentlang,
                $currenttext,
                $currenttexttag,
                $currenttextmode,
                $currentstatus,
                $currentquery,
                $currentquerymode,
                $currentregexmode,
                $currenttag1,
                $currenttag2,
                $currenttag12,
                $currentsort,
                $currentpage,
                $whLang,
                $whStat,
                $whQuery,
                $whTag
            );
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Edit words list - Alpine.js SPA version.
     *
     * This provides a full reactive SPA for word management with:
     * - Client-side filtering, sorting, and pagination via API
     * - Inline editing of translations and romanizations
     * - Bulk selection and actions
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function listEditAlpine(array $params): void
    {
        $currentlang = \Lwt\Database\Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        $perPage = (int) \Lwt\Database\Settings::getWithDefault('set-terms-per-page');
        if ($perPage < 1) {
            $perPage = 50;
        }

        // Use a placeholder title - Alpine.js will update it dynamically
        PageLayoutHelper::renderPageStart(
            'Terms',
            true
        );

        include __DIR__ . '/../Views/Word/list_alpine.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Check if current action is export or test (skip page start).
     *
     * @return bool
     */
    private function isExportOrTestAction(): bool
    {
        $markAction = $this->param('markaction');
        $allAction = $this->param('allaction');

        return in_array($markAction, ['exp', 'exp2', 'exp3', 'test', 'deltag']) ||
               in_array($allAction, ['expall', 'expall2', 'expall3', 'testall', 'deltagall']);
    }

    /**
     * Handle mark actions for selected words.
     *
     * @param WordListService $listService Service instance
     * @param string          $textId      Current text filter
     * @param string          $whLang      Language condition
     * @param string          $whStat      Status condition
     * @param string          $whQuery     Query condition
     * @param string          $whTag       Tag condition
     *
     * @return string Result message
     */
    private function handleMarkAction(
        WordListService $listService,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): string {
        $markaction = $this->param('markaction');
        $actiondata = $this->param('data');
        $message = "Multiple Actions: 0";

        $markedArray = $this->paramArray('marked');
        if (empty($markedArray)) {
            return $message;
        }

        $idList = "(" . implode(",", array_map('intval', $markedArray)) . ")";

        switch ($markaction) {
            case 'del':
                $message = $listService->deleteByIdList($idList);
                break;
            case 'addtag':
                $message = TagService::addTagToWords($actiondata, $idList);
                break;
            case 'deltag':
                TagService::removeTagFromWords($actiondata, $idList);
                header("Location: /words/edit");
                exit();
            case 'spl1':
                $message = $listService->updateStatusByIdList($idList, 1, true, 'spl1');
                break;
            case 'smi1':
                $message = $listService->updateStatusByIdList($idList, -1, true, 'smi1');
                break;
            case 's5':
                $message = $listService->updateStatusByIdList($idList, 5, false, 's5');
                break;
            case 's1':
                $message = $listService->updateStatusByIdList($idList, 1, false, 's1');
                break;
            case 's99':
                $message = $listService->updateStatusByIdList($idList, 99, false, 's99');
                break;
            case 's98':
                $message = $listService->updateStatusByIdList($idList, 98, false, 's98');
                break;
            case 'today':
                $message = $listService->updateStatusDateByIdList($idList);
                break;
            case 'delsent':
                $message = $listService->deleteSentencesByIdList($idList);
                break;
            case 'lower':
                $message = $listService->toLowercaseByIdList($idList);
                break;
            case 'cap':
                $message = $listService->capitalizeByIdList($idList);
                break;
            case 'exp':
                $this->exportService->exportAnki($listService->getAnkiExportSql($idList, '', '', '', '', ''));
                // @codeCoverageIgnoreStart - exportAnki returns never
            case 'exp2':
                $this->exportService->exportTsv($listService->getTsvExportSql($idList, '', '', '', '', ''));
                // @codeCoverageIgnoreStart - exportTsv returns never
            case 'exp3':
                $this->exportService->exportFlexible($listService->getFlexibleExportSql($idList, '', '', '', '', ''));
                // @codeCoverageIgnoreStart - exportFlexible returns never
            case 'test':
                $_SESSION['testsql'] = $idList;
                header("Location: /test?selection=2");
                exit();
        }

        return $message;
    }

    /**
     * Handle all actions for filtered words.
     *
     * @param WordListService $listService Service instance
     * @param string          $textId      Current text filter
     * @param string          $whLang      Language condition
     * @param string          $whStat      Status condition
     * @param string          $whQuery     Query condition
     * @param string          $whTag       Tag condition
     *
     * @return string|null Result message or null on redirect
     */
    private function handleAllAction(
        WordListService $listService,
        string $textId,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): ?string {
        $allaction = $this->param('allaction');
        $actiondata = $this->param('data');

        // Get word IDs matching filter
        $wordIds = $listService->getFilteredWordIds($textId, $whLang, $whStat, $whQuery, $whTag);

        // Actions that process IDs one by one
        if (in_array($allaction, ['delall', 'spl1all', 'smi1all', 's5all', 's1all', 's99all', 's98all', 'todayall', 'addtagall', 'deltagall', 'delsentall', 'lowerall', 'capall'])) {
            $cnt = 0;
            foreach ($wordIds as $id) {
                switch ($allaction) {
                    case 'delall':
                        $listService->deleteSingleWord($id);
                        $cnt++;
                        break;
                    case 'addtagall':
                        TagService::addTagToWords($actiondata, '(' . $id . ')');
                        $cnt++;
                        break;
                    case 'deltagall':
                        TagService::removeTagFromWords($actiondata, '(' . $id . ')');
                        $cnt++;
                        break;
                    case 'spl1all':
                        $cnt += (int) $listService->updateStatusByIdList('(' . $id . ')', 1, true, 'spl1');
                        break;
                    case 'smi1all':
                        $cnt += (int) $listService->updateStatusByIdList('(' . $id . ')', -1, true, 'smi1');
                        break;
                    case 's5all':
                        $cnt += (int) $listService->updateStatusByIdList('(' . $id . ')', 5, false, 's5');
                        break;
                    case 's1all':
                        $cnt += (int) $listService->updateStatusByIdList('(' . $id . ')', 1, false, 's1');
                        break;
                    case 's99all':
                        $cnt += (int) $listService->updateStatusByIdList('(' . $id . ')', 99, false, 's99');
                        break;
                    case 's98all':
                        $cnt += (int) $listService->updateStatusByIdList('(' . $id . ')', 98, false, 's98');
                        break;
                    case 'todayall':
                        $cnt += (int) $listService->updateStatusDateByIdList('(' . $id . ')');
                        break;
                    case 'delsentall':
                        $cnt += (int) $listService->deleteSentencesByIdList('(' . $id . ')');
                        break;
                    case 'lowerall':
                        $cnt += (int) $listService->toLowercaseByIdList('(' . $id . ')');
                        break;
                    case 'capall':
                        $cnt += (int) $listService->capitalizeByIdList('(' . $id . ')');
                        break;
                }
            }

            if ($allaction == 'deltagall') {
                header("Location: /words/edit");
                return null;
            }
            if ($allaction == 'addtagall') {
                return "Tag added in $cnt Terms";
            }
            if ($allaction == 'delall') {
                \Lwt\Database\Maintenance::adjustAutoIncrement('words', 'WoID');
                return "Deleted: $cnt Terms";
            }
            return "$cnt Terms changed";
        }

        // Export actions
        if ($allaction == 'expall') {
            $this->exportService->exportAnki($listService->getAnkiExportSql('', $textId, $whLang, $whStat, $whQuery, $whTag));
        }
        if ($allaction == 'expall2') {
            $this->exportService->exportTsv($listService->getTsvExportSql('', $textId, $whLang, $whStat, $whQuery, $whTag));
        }
        if ($allaction == 'expall3') {
            $this->exportService->exportFlexible($listService->getFlexibleExportSql('', $textId, $whLang, $whStat, $whQuery, $whTag));
        }

        // Test action
        if ($allaction == 'testall') {
            $sql = $listService->getTestWordIdsSql('', $textId, $whLang, $whStat, $whQuery, $whTag);
            $idList = [];
            $res = \Lwt\Database\Connection::query($sql);
            while ($record = mysqli_fetch_assoc($res)) {
                $idList[] = $record['WoID'];
            }
            mysqli_free_result($res);
            $_SESSION['testsql'] = "(" . implode(",", $idList) . ")";
            header("Location: /test?selection=2");
            return null;
        }

        return '';
    }

    /**
     * Handle save/update operation for word list.
     *
     * @param WordListService $listService Service instance
     *
     * @return int|null Word ID or null on error
     */
    private function handleListSaveUpdate(WordListService $listService): ?int
    {
        $translationRaw = ExportService::replaceTabNewline($this->param("WoTranslation"));
        $translation = ($translationRaw == '') ? '*' : $translationRaw;

        $requestData = $this->getWordFormData();
        $op = $this->param('op');
        if ($op == 'Save') {
            $message = $listService->saveNewWord($requestData);
            $wid = (int)Connection::lastInsertId();
            TagService::saveWordTags($wid);
            return $wid;
        } else {
            $message = $listService->updateWord($requestData);
            $wid = $this->paramInt("WoID", 0) ?? 0;
            TagService::saveWordTags($wid);
            return $wid;
        }
    }

    /**
     * Display new word form for word list.
     *
     * @param WordListService $listService Service instance
     * @param int             $lgid        Language ID
     *
     * @return void
     */
    private function displayListNewForm(WordListService $listService, int $lgid): void
    {
        $formData = $listService->getNewTermFormData($lgid);
        $scrdir = $formData['scrdir'];
        $showRoman = $formData['showRoman'];
        $languageName = $this->languageService->getLanguageName($lgid);

        include __DIR__ . '/../Views/Word/list_new_form.php';
    }

    /**
     * Display edit word form for word list.
     *
     * @param WordListService $listService Service instance
     * @param int             $wordId      Word ID
     *
     * @return void
     */
    private function displayListEditForm(WordListService $listService, int $wordId): void
    {
        $word = $listService->getEditFormData($wordId);
        if ($word === null) {
            echo '<p>Word not found.</p>';
            return;
        }

        $scrdir = $word['scrdir'];
        $showRoman = $word['LgShowRomanization'];
        $transl = $word['WoTranslation'];

        include __DIR__ . '/../Views/Word/list_edit_form.php';
    }

    /**
     * Display the word list.
     *
     * @param WordListService $listService     Service instance
     * @param string          $message         Status message
     * @param string          $currentlang     Language filter
     * @param string          $currenttext     Text filter
     * @param string          $currenttexttag  Text tag filter
     * @param string          $currenttextmode Text/tag mode
     * @param string          $currentstatus   Status filter
     * @param string          $currentquery    Search query
     * @param string          $currentquerymode Query mode
     * @param string          $currentregexmode Regex mode
     * @param string          $currenttag1     First tag filter
     * @param string          $currenttag2     Second tag filter
     * @param string          $currenttag12    Tag logic
     * @param int             $currentsort     Sort option
     * @param int             $currentpage     Page number
     * @param string          $whLang          Language condition
     * @param string          $whStat          Status condition
     * @param string          $whQuery         Query condition
     * @param string          $whTag           Tag condition
     *
     * @return void
     */
    private function displayWordList(
        WordListService $listService,
        string $message,
        string $currentlang,
        string $currenttext,
        string $currenttexttag,
        string $currenttextmode,
        string $currentstatus,
        string $currentquery,
        string $currentquerymode,
        string $currentregexmode,
        string $currenttag1,
        string $currenttag2,
        string $currenttag12,
        int $currentsort,
        int $currentpage,
        string $whLang,
        string $whStat,
        string $whQuery,
        string $whTag
    ): void {
        // Handle duplicate entry error message
        if (
            substr($message, 0, 24) == "Error: Duplicate entry '"
            && substr($message, -24) == "' for key 'WoLgIDTextLC'"
        ) {
            $lgID = $this->param("WoLgID") . "-";
            $msg = substr($message, 24 + strlen($lgID));
            $msg = substr($msg, 0, strlen($msg) - 24);
            $message = "Error: Term '" . $msg . "' already exists. Please go back and correct this!";
        }

        $this->message($message, false);

        // Count records
        $recno = $listService->countWords($currenttext, $whLang, $whStat, $whQuery, $whTag);

        // Calculate pagination
        $maxperpage = (int) \Lwt\Database\Settings::getWithDefault('set-terms-per-page');
        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if ($currentpage > $pages) {
            $currentpage = $pages;
        }

        // Validate sort
        if ($currentsort < 1) {
            $currentsort = 1;
        }
        if ($currentsort > 7) {
            $currentsort = 7;
        }

        // Show action card
        $actions = [];
        if ($currentlang != '') {
            $actions[] = [
                'url' => '/words/edit?new=1&lang=' . $currentlang,
                'label' => 'New Term',
                'icon' => 'circle-plus',
                'class' => 'is-primary'
            ];
        }
        $actions[] = [
            'url' => '/word/upload',
            'label' => 'Import Terms',
            'icon' => 'file-up'
        ];
        $actions[] = [
            'url' => '/term-tags',
            'label' => 'Term Tags',
            'icon' => 'tags'
        ];
        echo PageLayoutHelper::buildActionCard($actions);

        // Get data for filter dropdowns
        $languages = $this->languageService->getLanguagesForSelect();
        $langId = $currentlang !== '' ? (int)$currentlang : null;
        $texts = $this->textService->getTextsForSelect($langId);

        // Include filter view
        include __DIR__ . '/../Views/Word/list_filter.php';

        if ($recno == 0) {
            echo '<p>No terms found.</p>';
            return;
        }

        // Get words data
        $filters = [
            'whLang' => $whLang,
            'whStat' => $whStat,
            'whQuery' => $whQuery,
            'whTag' => $whTag,
            'textId' => $currenttext
        ];

        $res = $listService->getWordsList($filters, $currentsort, $currentpage, $maxperpage);

        $words = [];
        while ($record = mysqli_fetch_assoc($res)) {
            $words[] = $record;
        }
        mysqli_free_result($res);

        // Include table view
        include __DIR__ . '/../Views/Word/list_table.php';
    }

    /**
     * Edit multi-word expression (replaces word_edit_multi.php)
     *
     * Call: ?op=Save ... do insert new
     *       ?op=Change ... do update
     *       ?tid=[textid]&ord=[textpos]&wid=[wordid] ... edit existing
     *       ?tid=[textid]&ord=[textpos]&txt=[word] ... new or edit
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function editMulti(array $params): void
    {
        if ($this->hasParam('op')) {
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
        $textlc = trim($this->param("WoTextLC"));
        $text = trim($this->param("WoText"));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . htmlspecialchars($textlc ?? '', ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            $this->message($message, false);
            return;
        }

        $translationRaw = ExportService::replaceTabNewline($this->param("WoTranslation"));
        $translation = ($translationRaw == '') ? '*' : $translationRaw;

        $woText = $this->param("WoText");
        $woRomanization = $this->param("WoRomanization");
        $woSentence = $this->param("WoSentence");
        $woStatus = $this->paramInt("WoStatus", 0) ?? 0;
        $data = [
            'text' => \Lwt\Database\Escaping::prepareTextdata($woText),
            'textlc' => \Lwt\Database\Escaping::prepareTextdata($textlc),
            'translation' => $translation,
            'roman' => $woRomanization,
            'sentence' => $woSentence,
        ];

        $op = $this->param('op');
        if ($op == 'Save') {
            // Insert new multi-word
            $data['status'] = $woStatus;
            $data['lgid'] = $this->paramInt("WoLgID", 0) ?? 0;
            $data['wordcount'] = $this->paramInt("len", 0) ?? 0;

            $titletext = "New Term: " . htmlspecialchars($data['textlc'] ?? '', ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $this->wordService->createMultiWord($data);
            $wid = $result['id'];
        } else {
            // Update existing multi-word
            $wid = $this->paramInt("WoID", 0) ?? 0;
            $oldStatus = $this->paramInt("WoOldStatus", 0) ?? 0;
            $newStatus = $woStatus;

            $titletext = "Edit Term: " . htmlspecialchars($data['textlc'] ?? '', ENT_QUOTES, 'UTF-8');
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $this->wordService->updateMultiWord($wid, $data, $oldStatus, $newStatus);

            // Prepare data for view
            $tagList = TagService::getWordTagList($wid, false);
            $formattedTags = $tagList !== '' ? ' [' . $tagList . ']' : '';
            $termJson = $this->wordService->exportTermAsJson(
                $wid,
                $data['text'],
                $data['roman'],
                $translation . $formattedTags,
                $newStatus
            );
            $oldStatusValue = $oldStatus;

            include __DIR__ . '/../Views/Word/edit_multi_update_result.php';
        }
    }

    /**
     * Display multi-word edit form (new or existing).
     *
     * @return void
     */
    private function displayMultiWordForm(): void
    {
        $tid = $this->paramInt('tid', 0);
        $ord = $this->paramInt('ord', 0);
        $strWid = $this->param('wid');

        // Determine if we're editing an existing word or creating new
        if ($strWid == "" || !is_numeric($strWid)) {
            // No ID provided: check if text exists in database
            $lgid = $this->wordService->getLanguageIdFromText($tid);
            $txtParam = $this->param('txt');
            $textlc = mb_strtolower(
                \Lwt\Database\Escaping::prepareTextdata($txtParam),
                'UTF-8'
            );

            $strWid = $this->wordService->findMultiWordByText($textlc, (int) $lgid);
        }

        if ($strWid === null) {
            // New multi-word
            $txtParam = $this->param('txt');
            $len = $this->paramInt('len', 0);
            PageLayoutHelper::renderPageStartNobody("New Term: " . $txtParam);
            $this->displayNewMultiWordForm($txtParam, $tid, $ord, $len);
        } else {
            // Edit existing multi-word
            $wid = (int) $strWid;
            $wordData = $this->wordService->getMultiWordData($wid);
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
        $lgid = $this->wordService->getLanguageIdFromText($tid);
        $termText = \Lwt\Database\Escaping::prepareTextdata($text);
        $textlc = mb_strtolower($termText, 'UTF-8');

        // Check if word already exists
        $existingWid = $this->wordService->findMultiWordByText($textlc, (int) $lgid);
        if ($existingWid !== null) {
            // Get text from existing word
            $wordData = $this->wordService->getMultiWordData($existingWid);
            if ($wordData !== null) {
                $termText = $wordData['text'];
            }
        }

        $scrdir = $this->languageService->getScriptDirectionTag((int) $lgid);
        $seid = $this->wordService->getSentenceIdAtPosition($tid, $ord);
        $sent = $this->sentenceService->formatSentence(
            $seid,
            $textlc,
            (int) \Lwt\Database\Settings::getWithDefault('set-term-sentence-count')
        );
        $showRoman = $this->wordService->shouldShowRomanization($tid);

        // Variables for view
        $term = (object) [
            'lgid' => $lgid,
            'text' => $termText,
            'textlc' => $textlc,
            'id' => $existingWid
        ];
        $sentence = ExportService::replaceTabNewline($sent[1] ?? '');

        include __DIR__ . '/../Views/Word/form_edit_multi_new.php';
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

        $scrdir = $this->languageService->getScriptDirectionTag($lgid);
        $showRoman = $this->wordService->shouldShowRomanization($tid);

        $status = $wordData['status'];
        if ($status >= 98) {
            $status = 1;
        }

        $sentence = $wordData['sentence'];
        if ($sentence == '') {
            $seid = $this->wordService->getSentenceIdAtPosition($tid, $ord);
            $sent = $this->sentenceService->formatSentence(
                $seid,
                $textlc,
                (int) \Lwt\Database\Settings::getWithDefault('set-term-sentence-count')
            );
            $sentence = ExportService::replaceTabNewline($sent[1] ?? '');
        }

        $transl = $wordData['translation'];
        if ($transl == '*') {
            $transl = '';
        }

        $notes = $wordData['notes'] ?? '';

        // Variables for view
        $term = (object) [
            'id' => $wid,
            'lgid' => $lgid,
            'text' => $termText,
            'textlc' => $textlc
        ];
        $romanization = $wordData['romanization'];
        $originalStatus = $wordData['status'];

        include __DIR__ . '/../Views/Word/form_edit_multi_existing.php';
    }

    /**
     * Delete word (replaces word_delete.php)
     *
     * Call: ?tid=[textid]&wid=[wordid]
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use DELETE /api/v1/terms/{id} instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     *             The frontend now uses API mode by default (see setUseApiMode in text_events.ts).
     */
    public function delete(array $params): void
    {
        $textId = $this->paramInt('tid', 0) ?? 0;
        $wordId = $this->paramInt('wid', 0) ?? 0;

        if ($textId === 0 || $wordId === 0) {
            return;
        }

        $term = $this->wordService->getWordText($wordId);
        if ($term === null) {
            return;
        }

        $message = $this->wordService->delete($wordId);

        PageLayoutHelper::renderPageStart("Term: " . $term, false);

        $wid = $wordId;
        include __DIR__ . '/../Views/Word/delete_result.php';

        PageLayoutHelper::renderPageEnd();
    }


    /**
     * Mark all words as well-known or ignored (replaces words_all.php)
     *
     * Call: ?text=[textid] - mark all as well-known (99)
     *       ?text=[textid]&stat=[status] - mark with specific status (98 or 99)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function all(array $params): void
    {
        $textId = $this->paramInt('text');
        if ($textId === null) {
            return;
        }

        $status = $this->paramInt('stat', 99) ?? 99;

        if ($status == 98) {
            PageLayoutHelper::renderPageStart("Setting all blue words to Ignore", false);
        } else {
            PageLayoutHelper::renderPageStart("Setting all blue words to Well-known", false);
        }

        list($count, $wordsData) = $this->wordService->markAllWordsWithStatus($textId, $status);
        $useTooltips = \Lwt\Database\Settings::getWithDefault('set-tooltip-mode') == 1;

        include __DIR__ . '/../Views/Word/all_wellknown_result.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * New word form (replaces word_new.php)
     *
     * Call: ?text=[textid]&lang=[langid] - display new term form
     *       ?op=Save - save the new term
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function create(array $params): void
    {
        $op = $this->param('op');
        // Handle save operation
        if ($op === 'Save') {
            $requestData = $this->getWordFormData();
            $result = $this->wordService->create($requestData);

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
                TagService::saveWordTags($wid);
                \Lwt\Database\Maintenance::initWordCount();

                echo '<p>' . $result['message'] . '</p>';

                $woLgId = $this->paramInt("WoLgID", 0) ?? 0;
                $len = $this->wordService->getWordCount($wid);
                if ($len > 1) {
                    $this->expressionService->insertExpressions($result['textlc'], $woLgId, $wid, $len, 0);
                } elseif ($len == 1) {
                    $this->wordService->linkToTextItems($wid, $woLgId, $result['textlc']);

                    // Prepare view variables
                    $hex = $this->wordService->textToClassName($result['textlc']);
                    $translation = ExportService::replaceTabNewline($this->param("WoTranslation"));
                    if ($translation === '') {
                        $translation = '*';
                    }
                    $status = $this->param("WoStatus");
                    $romanization = $this->param("WoRomanization");
                    $text = $result['text'];
                    $textId = $this->paramInt('tid', 0) ?? 0;
                    $success = true;
                    $message = $result['message'];

                    include __DIR__ . '/../Views/Word/save_result.php';
                }
            }
        } else {
            // Display the new word form
            $lang = $this->paramInt('lang', 0);
            $textId = $this->paramInt('text', 0);
            $scrdir = $this->languageService->getScriptDirectionTag($lang);

            $langData = $this->wordService->getLanguageData($lang);
            $showRoman = $langData['showRoman'];
            $dictService = new \Lwt\Services\DictionaryService();

            PageLayoutHelper::renderPageStartNobody('');

            include __DIR__ . '/../Views/Word/form_new.php';
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Show word details (replaces word_show.php)
     *
     * Call: ?wid=[wordid]&ann=[annotation]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function show(array $params): void
    {
        PageLayoutHelper::renderPageStartNobody('Term');

        $wid = $this->param('wid');
        $ann = $this->param('ann');

        if ($wid === '') {
            ErrorHandler::die('Word not found in show_word.php');
            return;
        }

        $word = $this->wordService->getWordDetails((int) $wid);
        if ($word === null) {
            ErrorHandler::die('Word not found');
            return;
        }

        $tags = TagService::getWordTagList((int) $wid, false);
        $scrdir = $this->languageService->getScriptDirectionTag($word['langId']);

        include __DIR__ . '/../Views/Word/show.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Insert well-known word (replaces word_insert_wellknown.php)
     *
     * Call: ?tid=[textid]&ord=[textpos]
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use POST /api/v1/terms/quick with status=99 instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     *             The frontend now uses API mode by default (see setUseApiMode in text_events.ts).
     */
    public function insertWellknown(array $params): void
    {
        $textId = $this->paramInt('tid', 0) ?? 0;
        $ord = $this->paramInt('ord', 0) ?? 0;

        if ($textId === 0 || $ord === 0) {
            return;
        }

        $word = $this->wordService->getWordAtPosition($textId, $ord);
        if ($word === null) {
            return;
        }

        $result = $this->wordService->insertWordWithStatus($textId, $word, 99);

        PageLayoutHelper::renderPageStart("Term: " . $word, false);

        $term = $result['term'];
        $wid = $result['id'];
        $hex = $result['hex'];
        include __DIR__ . '/../Views/Word/insert_wellknown_result.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Insert ignored word (replaces word_insert_ignore.php)
     *
     * Call: ?tid=[textid]&ord=[textpos]
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use POST /api/v1/terms/quick with status=98 instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     *             The frontend now uses API mode by default (see setUseApiMode in text_events.ts).
     */
    public function insertIgnore(array $params): void
    {
        $textId = $this->paramInt('tid', 0) ?? 0;
        $ord = $this->paramInt('ord', 0) ?? 0;

        if ($textId === 0 || $ord === 0) {
            return;
        }

        $word = $this->wordService->getWordAtPosition($textId, $ord);
        if ($word === null) {
            return;
        }

        $result = $this->wordService->insertWordWithStatus($textId, $word, 98);

        PageLayoutHelper::renderPageStart("Term: " . $word, false);

        $term = $result['term'];
        $wid = $result['id'];
        $hex = $result['hex'];
        include __DIR__ . '/../Views/Word/insert_ignore_result.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Inline edit word (replaces word_inline_edit.php)
     *
     * Handles AJAX inline editing of translation or romanization fields.
     * POST parameters:
     * - id: string - Field identifier (e.g., "trans123" or "roman123" where 123 is word ID)
     * - value: string - New value for the field
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function inlineEdit(array $params): void
    {
        $value = InputValidator::getStringFromPost('value');
        $id = InputValidator::getStringFromPost('id');

        if (substr($id, 0, 5) === 'trans') {
            $wordId = (int) substr($id, 5);
            echo $this->wordService->updateTranslation($wordId, $value);
            return;
        }

        if (substr($id, 0, 5) === 'roman') {
            $wordId = (int) substr($id, 5);
            echo $this->wordService->updateRomanization($wordId, $value);
            return;
        }

        echo 'ERROR - please refresh page!';
    }

    /**
     * Bulk translate words (replaces word_bulk_translate.php)
     *
     * Call: ?tid=[textid]&sl=[sourcelg]&tl=[targetlg]&offset=[pos]
     *       POST: term[n][text], term[n][lg], term[n][status], term[n][trans]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function bulkTranslate(array $params): void
    {
        $tid = $this->paramInt('tid', 0) ?? 0;
        $pos = $this->paramInt('offset');

        // Handle form submission (save terms)
        $termsArray = $this->paramArray('term');
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
            $sl = $this->param('sl');
            $tl = $this->param('tl');
            $this->displayBulkTranslateForm($tid, $sl !== '' ? $sl : null, $tl !== '' ? $tl : null, $pos);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle saving bulk translated terms.
     *
     * @param array $terms   Array of term data
     * @param int   $tid     Text ID
     * @param bool  $cleanUp Whether to clean up right frames after save
     *
     * @return void
     */
    private function handleBulkSave(array $terms, int $tid, bool $cleanUp): void
    {
        $maxWoId = $this->wordService->bulkSaveTerms($terms);

        $tooltipMode = \Lwt\Database\Settings::getWithDefault('set-tooltip-mode');
        $res = $this->wordService->getNewWordsAfter($maxWoId);

        $this->wordService->linkNewWordsToTextItems($maxWoId);

        // Prepare data for view
        $newWords = [];
        foreach ($res as $record) {
            $record['hex'] = StringUtils::toClassName(
                \Lwt\Database\Escaping::prepareTextdata($record['WoTextLC'])
            );
            $record['translation'] = $record['WoTranslation'];
            $newWords[] = $record;
        }

        include __DIR__ . '/../Views/Word/bulk_save_result.php';
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
        $limit = (int) \Lwt\Database\Settings::getWithDefault('set-ggl-translation-per-page') + 1;
        $dictionaries = $this->wordService->getLanguageDictionaries($tid);

        $res = $this->wordService->getUnknownWordsForBulkTranslate($tid, $pos, $limit);

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

        include __DIR__ . '/../Views/Word/bulk_translate_form.php';
    }

    /**
     * Set word status (replaces word_set_status.php)
     *
     * Call: ?tid=[textid]&wid=[wordid]&status=1..5/98/99
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @deprecated 3.0.0 Use PUT /api/v1/terms/{id}/status/{status} instead.
     *             This endpoint is kept for backward compatibility with frame-based mode.
     *             The frontend now uses API mode by default (see setUseApiMode in text_events.ts).
     */
    public function setStatus(array $params): void
    {
        $textId = $this->paramInt('tid', 0) ?? 0;
        $wordId = $this->paramInt('wid', 0) ?? 0;
        $status = $this->paramInt('status', 0) ?? 0;

        if ($textId === 0 || $wordId === 0 || $status === 0) {
            return;
        }

        $wordData = $this->wordService->getWordData($wordId);
        if ($wordData === null) {
            ErrorHandler::die("Word not found");
            return;
        }

        $term = $wordData['text'];
        $tagList = TagService::getWordTagList($wordId, false);
        $formattedTags = $tagList !== '' ? ' [' . $tagList . ']' : '';
        $translation = $wordData['translation'] . $formattedTags;
        $romanization = $wordData['romanization'];
        $wid = $wordId;

        PageLayoutHelper::renderPageStart("Term: $term", false);

        include __DIR__ . '/../Views/Word/status_result.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Upload words (replaces word_upload.php)
     *
     * Handles:
     * - GET: Display the upload form
     * - POST with op=Import: Process the import
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function upload(array $params): void
    {
        PageLayoutHelper::renderPageStart('Import Terms', true);

        $op = $this->param('op');
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
        $currentLanguage = \Lwt\Database\Settings::get('currentlanguage');
        $languageService = new \Lwt\Services\LanguageService();
        $languages = $languageService->getLanguagesForSelect();
        include __DIR__ . '/../Views/Word/upload_form.php';
    }

    /**
     * Handle the word import operation.
     *
     * @return void
     */
    private function handleUploadImport(): void
    {
        $uploadService = $this->getUploadService();
        $tabType = $this->param("Tab", 'c');
        if ($tabType === '') {
            $tabType = 'c';
        }
        $langId = $this->paramInt("LgID", 0) ?? 0;

        if ($langId === 0) {
            $this->message('Error: No language selected', false);
            return;
        }

        $langData = $uploadService->getLanguageData($langId);
        if ($langData === null) {
            $this->message('Error: Invalid language', false);
            return;
        }

        $removeSpaces = (bool) $langData['LgRemoveSpaces'];

        // Parse column mapping
        $columns = [
            1 => $this->param("Col1"),
            2 => $this->param("Col2"),
            3 => $this->param("Col3"),
            4 => $this->param("Col4"),
            5 => $this->param("Col5"),
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
        $uploadText = $this->param("Upload");
        if ($fileUpl) {
            $fileName = $_FILES["thefile"]["tmp_name"];
        } else {
            if ($uploadText === '') {
                $this->message('Error: No data to import', false);
                return;
            }
            $fileName = $uploadService->createTempFile($uploadText);
        }

        $ignoreFirst = $this->param("IgnFirstLine") === '1';
        $overwrite = $this->paramInt("Over", 0) ?? 0;
        $status = $this->paramInt("WoStatus", 1) ?? 1;
        $translDelim = $this->param("transl_delim");

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
            include __DIR__ . '/../Views/Word/upload_result.php';
        } elseif ($fields["tl"] > 0) {
            // Import tags only
            $uploadService->importTagsOnly($fields, $tabType, $fileName, $ignoreFirst);
            echo '<p>Tags imported successfully.</p>';
        } else {
            $this->message('Error: No term column specified', false);
        }

        // Clean up temp file if we created it
        if (!$fileUpl && file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Import terms from the uploaded file.
     *
     * @param WordUploadService $uploadService  The upload service
     * @param int               $langId         Language ID
     * @param array             $fields         Field indexes
     * @param array             $col            Column mapping
     * @param string            $tabType        Tab type (c, t, h)
     * @param string            $fileName       Path to input file
     * @param int               $status         Word status
     * @param int               $overwrite      Overwrite mode
     * @param bool              $ignoreFirst    Ignore first line
     * @param string            $translDelim    Translation delimiter
     * @param string            $lastUpdate     Last update timestamp
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
        \Lwt\Database\Maintenance::initWordCount();
        $uploadService->linkWordsToTextItems();
        $uploadService->handleMultiwords($langId, $lastUpdate);
    }

    /**
     * Get the upload service instance for testing.
     *
     * @return WordUploadService
     */
    public function getUploadServiceForTest(): WordUploadService
    {
        return $this->getUploadService();
    }

    /**
     * Get word form data from request parameters.
     *
     * Collects all word-related form fields into an array for service methods.
     *
     * @return array<string, mixed> Form data array
     */
    private function getWordFormData(): array
    {
        return [
            'WoID' => $this->paramInt('WoID'),
            'WoLgID' => $this->paramInt('WoLgID', 0) ?? 0,
            'WoText' => $this->param('WoText'),
            'WoTextLC' => $this->param('WoTextLC'),
            'WoStatus' => $this->param('WoStatus'),
            'WoOldStatus' => $this->param('WoOldStatus'),
            'WoTranslation' => $this->param('WoTranslation'),
            'WoRomanization' => $this->param('WoRomanization'),
            'WoSentence' => $this->param('WoSentence'),
            'tid' => $this->paramInt('tid'),
            'ord' => $this->paramInt('ord'),
            'len' => $this->paramInt('len'),
        ];
    }
}
