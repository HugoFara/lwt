<?php

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

use Lwt\Database\Connection;
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
use Lwt\Services\WordService;
use Lwt\Services\WordListService;
use Lwt\Services\WordUploadService;
use Lwt\Services\WordStatusService;
use Lwt\Services\ExpressionService;
use Lwt\Services\ExportService;
use Lwt\Services\TagService;
use Lwt\Services\LanguageService;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\TextService;

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
    /**
     * @var WordService Word service instance
     */
    protected WordService $wordService;

    /**
     * @var WordListService|null Word list service instance
     */
    protected ?WordListService $listService = null;

    /**
     * @var WordUploadService|null Word upload service instance
     */
    protected ?WordUploadService $uploadService = null;

    /**
     * @var LanguageService Language service instance
     */
    protected LanguageService $languageService;

    /**
     * Initialize controller with WordService.
     */
    public function __construct()
    {
        parent::__construct();
        $this->wordService = new WordService();
        $this->languageService = new LanguageService();
    }

    /**
     * Get or create WordListService instance.
     *
     * @return WordListService
     */
    protected function getListService(): WordListService
    {
        if ($this->listService === null) {
            $this->listService = new WordListService();
        }
        return $this->listService;
    }

    /**
     * Get or create WordUploadService instance.
     *
     * @return WordUploadService
     */
    protected function getUploadService(): WordUploadService
    {
        if ($this->uploadService === null) {
            $this->uploadService = new WordUploadService();
        }
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
            && !array_key_exists("op", $_REQUEST)
        ) {
            return;
        }

        $fromAnn = $this->param("fromAnn");

        if (isset($_REQUEST['op'])) {
            $this->handleEditOperation($fromAnn);
        } else {
            $wid = (array_key_exists("wid", $_REQUEST) && is_numeric($this->param('wid')))
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
        $textlc = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
        $text = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoText"]));

        // Validate lowercase matches
        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . \tohtml($textlc);
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

        if ($_REQUEST['op'] == 'Save') {
            // Insert new term
            $result = $this->wordService->create($_REQUEST);
            $isNew = true;
            $hex = $this->wordService->textToClassName($_REQUEST["WoTextLC"]);
            $oldStatus = 0;
            $titletext = "New Term: " . \tohtml($textlc);
        } else {
            // Update existing term
            $result = $this->wordService->update((int)$_REQUEST["WoID"], $_REQUEST);
            $isNew = false;
            $hex = null;
            $oldStatus = $_REQUEST['WoOldStatus'];
            $titletext = "Edit Term: " . \tohtml($textlc);
        }

        PageLayoutHelper::renderPageStartNobody($titletext);
        echo '<h1>' . $titletext . '</h1>';

        $wid = $result['id'];
        $message = $result['message'];

        TagService::saveWordTags($wid);

        // Prepare view variables
        $textId = (int)$_REQUEST['tid'];
        $status = $_REQUEST["WoStatus"];
        $romanization = $_REQUEST["WoRomanization"];

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
                \my_die("Cannot access Term and Language in edit_word.php");
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
                \my_die("Cannot access Term and Language in edit_word.php");
            }
            $term = (string) $wordData['WoText'];
            $lang = (int) $wordData['WoLgID'];
            $termlc = mb_strtolower($term, 'UTF-8');
            $new = false;
        }

        $titletext = ($new ? "New Term" : "Edit Term") . ": " . \tohtml($term);
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
                \my_die("Cannot access word data");
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
            $tbpref = \Lwt\Core\Globals::getTablePrefix();
            $showRoman = (bool) \Lwt\Database\Connection::fetchValue(
                "SELECT LgShowRomanization AS value
                FROM {$tbpref}languages JOIN {$tbpref}texts
                ON TxLgID = LgID
                WHERE TxID = $textId"
            );

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
        $tbpref = \Lwt\Core\Globals::getTablePrefix();

        $translation_raw = ExportService::replaceTabNewline($this->param("WoTranslation"));
        $translation = ($translation_raw == '') ? '*' : $translation_raw;

        if (isset($_REQUEST['op'])) {
            $this->handleEditTermOperation($tbpref, $translation);
        } else {
            $this->displayEditTermForm($tbpref);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle update operation for edit term.
     *
     * @param string $tbpref      Table prefix
     * @param string $translation Translation value
     *
     * @return void
     */
    private function handleEditTermOperation(string $tbpref, string $translation): void
    {
        $textlc = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
        $text = trim(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoText"]));

        if (mb_strtolower($text, 'UTF-8') != $textlc) {
            $titletext = "New/Edit Term: " . \tohtml(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            $this->message($message, false);
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        if ($_REQUEST['op'] == 'Change') {
            $titletext = "Edit Term: " . \tohtml(\Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoTextLC"]));
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $oldstatus = $_REQUEST["WoOldStatus"];
            $newstatus = $_REQUEST["WoStatus"];
            $xx = '';
            if ($oldstatus != $newstatus) {
                $xx = ', WoStatus = ' . $newstatus . ', WoStatusChanged = NOW()';
            }

            \Lwt\Database\Connection::execute(
                'update ' . $tbpref . 'words set WoText = ' .
                \Lwt\Database\Escaping::toSqlSyntax($_REQUEST["WoText"]) . ', WoTranslation = ' .
                \Lwt\Database\Escaping::toSqlSyntax($translation) . ', WoSentence = ' .
                \Lwt\Database\Escaping::toSqlSyntax(ExportService::replaceTabNewline($_REQUEST["WoSentence"])) .
                ', WoRomanization = ' .
                \Lwt\Database\Escaping::toSqlSyntax($_REQUEST["WoRomanization"]) . $xx .
                ',' . WordStatusService::makeScoreRandomInsertUpdate('u') .
                ' where WoID = ' . $_REQUEST["WoID"],
                "Updated"
            );
            $wid = (int)$_REQUEST["WoID"];
            TagService::saveWordTags($wid);

            $message = 'Updated';

            $lang = \Lwt\Database\Connection::fetchValue(
                'select WoLgID as value from ' . $tbpref . 'words where WoID = ' . $wid
            );
            if (!isset($lang)) {
                \my_die('Cannot retrieve language in edit_tword.php');
            }
            $regexword = \Lwt\Database\Connection::fetchValue(
                'select LgRegexpWordCharacters as value from ' . $tbpref . 'languages where LgID = ' . $lang
            );
            if (!isset($regexword)) {
                \my_die('Cannot retrieve language data in edit_tword.php');
            }
            $sent = \tohtml(ExportService::replaceTabNewline($_REQUEST["WoSentence"]));
            $sent1 = str_replace(
                "{",
                ' <b>[',
                str_replace(
                    "}",
                    ']</b> ',
                    ExportService::maskTermInSentence($sent, $regexword)
                )
            );

            $status = $_REQUEST["WoStatus"];
            $romanization = $_REQUEST["WoRomanization"];
            $text = $_REQUEST["WoText"];

            include __DIR__ . '/../Views/Word/edit_term_result.php';
        }
    }

    /**
     * Display the edit term form.
     *
     * @param string $tbpref Table prefix
     *
     * @return void
     */
    private function displayEditTermForm(string $tbpref): void
    {
        $wid = $this->param('wid');

        if ($wid == '') {
            \my_die("Term ID missing in edit_tword.php");
        }

        $sql = 'select WoText, WoLgID, WoTranslation, WoSentence, WoRomanization, WoStatus from ' .
            $tbpref . 'words where WoID = ' . $wid;
        $res = \Lwt\Database\Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        if ($record) {
            $term = (string) $record['WoText'];
            $lang = (int) $record['WoLgID'];
            $transl = ExportService::replaceTabNewline($record['WoTranslation']);
            if ($transl == '*') {
                $transl = '';
            }
            $sentence = ExportService::replaceTabNewline($record['WoSentence']);
            $rom = $record['WoRomanization'];
            $status = $record['WoStatus'];
            $showRoman = (bool) \Lwt\Database\Connection::fetchValue(
                "SELECT LgShowRomanization AS value
                FROM {$tbpref}languages
                WHERE LgID = $lang"
            );
        } else {
            \my_die("Term data not found in edit_tword.php");
        }
        mysqli_free_result($res);

        $termlc = mb_strtolower($term, 'UTF-8');
        $titletext = "Edit Term: " . \tohtml($term);
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
            (string) \processDBParam("filterlang", 'currentlanguage', '', false)
        );
        $currentsort = (int) \processDBParam("sort", 'currentwordsort', '1', true);
        $currentpage = (int) \processSessParam("page", "currentwordpage", '1', true);
        $currentquery = (string) \processSessParam("query", "currentwordquery", '', false);
        $currentquerymode = (string) \processSessParam(
            "query_mode",
            "currentwordquerymode",
            'term,rom,transl',
            false
        );
        $currentregexmode = \Lwt\Database\Settings::getWithDefault("set-regex-mode");
        $currentstatus = (string) \processSessParam("status", "currentwordstatus", '', false);
        $currenttext = \Lwt\Database\Validation::text(
            (string) \processSessParam("text", "currentwordtext", '', false)
        );
        $currenttexttag = (string) \processSessParam("texttag", "currentwordtexttag", '', false);
        $currenttextmode = (string) \processSessParam("text_mode", "currentwordtextmode", 0, false);
        $currenttag1 = \Lwt\Database\Validation::tag(
            (string) \processSessParam("tag1", "currentwordtag1", '', false),
            $currentlang
        );
        $currenttag2 = \Lwt\Database\Validation::tag(
            (string) \processSessParam("tag2", "currentwordtag2", '', false),
            $currentlang
        );
        $currenttag12 = (string) \processSessParam("tag12", "currentwordtag12", '', false);

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
                if (isset($_REQUEST['query'])) {
                    echo '<p id="hide3" class="warning-message">+++ Warning: Invalid Search +++</p>';
                }
            }
        }

        $whTag = $listService->buildTagCondition($currenttag1, $currenttag2, $currenttag12);

        // Check if we should skip page start for exports/tests
        $noPagestart = $this->isExportOrTestAction();

        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart(
                'My ' . $this->languageService->getLanguageName($currentlang) . ' Terms (Words and Expressions)',
                true
            );
        }

        $message = '';

        // Handle mark actions
        if (isset($_REQUEST['markaction'])) {
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
        if (isset($_REQUEST['allaction'])) {
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
        if (isset($_REQUEST['del'])) {
            $message = $listService->deleteSingleWord((int) $_REQUEST['del']);
        }

        // Handle save/update
        if (isset($_REQUEST['op'])) {
            $wid = $this->handleListSaveUpdate($listService);
        }

        // Display appropriate view
        if (isset($_REQUEST['new']) && isset($_REQUEST['lang'])) {
            $this->displayListNewForm($listService, (int) $_REQUEST['lang']);
        } elseif (isset($_REQUEST['chg'])) {
            $this->displayListEditForm($listService, (int) $_REQUEST['chg']);
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
        $markaction = $_REQUEST['markaction'];
        $actiondata = $this->param('data');
        $message = "Multiple Actions: 0";

        if (!isset($_REQUEST['marked']) || !is_array($_REQUEST['marked']) || count($_REQUEST['marked']) == 0) {
            return $message;
        }

        $idList = "(" . implode(",", array_map('intval', $_REQUEST['marked'])) . ")";

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
                \anki_export($listService->getAnkiExportSql($idList, '', '', '', '', ''));
                break;
            case 'exp2':
                \tsv_export($listService->getTsvExportSql($idList, '', '', '', '', ''));
                break;
            case 'exp3':
                \flexible_export($listService->getFlexibleExportSql($idList, '', '', '', '', ''));
                break;
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
        $allaction = (string) $_REQUEST['allaction'];
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
            \anki_export($listService->getAnkiExportSql('', $textId, $whLang, $whStat, $whQuery, $whTag));
            return '';
        }
        if ($allaction == 'expall2') {
            \tsv_export($listService->getTsvExportSql('', $textId, $whLang, $whStat, $whQuery, $whTag));
            return '';
        }
        if ($allaction == 'expall3') {
            \flexible_export($listService->getFlexibleExportSql('', $textId, $whLang, $whStat, $whQuery, $whTag));
            return '';
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

        if ($_REQUEST['op'] == 'Save') {
            $message = $listService->saveNewWord($_REQUEST);
            $wid = (int)Connection::lastInsertId();
            TagService::saveWordTags($wid);
            return $wid;
        } else {
            $message = $listService->updateWord($_REQUEST);
            $wid = (int) $_REQUEST["WoID"];
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
            $lgID = $_REQUEST["WoLgID"] . "-";
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

        // Show new term link
        if ($currentlang != '') {
            ?>
<p><a href="/words/edit?new=1&amp;lang=<?php echo $currentlang; ?>"><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New <?php echo \tohtml($this->languageService->getLanguageName($currentlang)); ?> Term ...</a></p>
            <?php
        } else {
            ?>
<p><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New Term? - Set Language Filter first ...</p>
            <?php
        }

        // Get data for filter dropdowns
        $languages = $this->languageService->getLanguagesForSelect();
        $textService = new TextService();
        $langId = $currentlang !== '' ? (int)$currentlang : null;
        $texts = $textService->getTextsForSelect($langId);

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
        if (isset($_REQUEST['op'])) {
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
            $titletext = "New/Edit Term: " . \tohtml($textlc);
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';
            $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
                '", please go back and correct this!';
            $this->message($message, false);
            return;
        }

        $translationRaw = ExportService::replaceTabNewline($this->param("WoTranslation"));
        $translation = ($translationRaw == '') ? '*' : $translationRaw;

        $data = [
            'text' => \Lwt\Database\Escaping::prepareTextdata($_REQUEST["WoText"]),
            'textlc' => \Lwt\Database\Escaping::prepareTextdata($textlc),
            'translation' => $translation,
            'roman' => $_REQUEST["WoRomanization"] ?? '',
            'sentence' => $_REQUEST["WoSentence"] ?? '',
        ];

        if ($_REQUEST['op'] == 'Save') {
            // Insert new multi-word
            $data['status'] = (int) $_REQUEST["WoStatus"];
            $data['lgid'] = (int) $_REQUEST["WoLgID"];
            $data['wordcount'] = (int) ($_REQUEST["len"] ?? 0);

            $titletext = "New Term: " . \tohtml($data['textlc']);
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $this->wordService->createMultiWord($data);
            $wid = $result['id'];
        } else {
            // Update existing multi-word
            $wid = (int) $_REQUEST["WoID"];
            $oldStatus = (int) $_REQUEST["WoOldStatus"];
            $newStatus = (int) $_REQUEST["WoStatus"];

            $titletext = "Edit Term: " . \tohtml($data['textlc']);
            PageLayoutHelper::renderPageStartNobody($titletext);
            echo '<h1>' . $titletext . '</h1>';

            $result = $this->wordService->updateMultiWord($wid, $data, $oldStatus, $newStatus);

            // Prepare data for view
            $termJson = $this->wordService->exportTermAsJson(
                $wid,
                $data['text'],
                $data['roman'],
                $translation . TagService::getWordTagListFormatted($wid, ' ', true, false),
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
                \my_die("Cannot access Term and Language in edit_mword.php");
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
        $sent = \getSentence(
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
            $sent = \getSentence(
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
     */
    public function delete(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $wordId = isset($_REQUEST['wid']) ? (int) $_REQUEST['wid'] : 0;

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
     * Delete multi-word expression (replaces word_delete_multi.php)
     *
     * Call: ?wid=[wordid]&tid=[textid]
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function deleteMulti(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $wordId = isset($_REQUEST['wid']) ? (int) $_REQUEST['wid'] : 0;

        $term = $this->wordService->getWordText($wordId);
        if ($term === null) {
            \my_die('Word not found');
            return;
        }

        PageLayoutHelper::renderPageStart("Term: " . $term, false);

        $rowsAffected = $this->wordService->deleteMultiWord($wordId);

        $showAll = \Lwt\Database\Settings::getZeroOrOne('showallwords', 1);
        $wid = $wordId;

        include __DIR__ . '/../Views/Word/delete_multi_result.php';

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
        if (!isset($_REQUEST['text'])) {
            return;
        }

        $textId = (int) $_REQUEST['text'];
        $status = isset($_REQUEST['stat']) ? (int) $_REQUEST['stat'] : 99;

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
        // Handle save operation
        if (isset($_REQUEST['op']) && $_REQUEST['op'] === 'Save') {
            $result = $this->wordService->create($_REQUEST);

            $titletext = "New Term: " . \tohtml($result['textlc']);
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

                $len = $this->wordService->getWordCount($wid);
                if ($len > 1) {
                    (new ExpressionService())->insertExpressions($result['textlc'], (int) $_REQUEST["WoLgID"], $wid, $len, 0);
                } elseif ($len == 1) {
                    $this->wordService->linkToTextItems($wid, (int) $_REQUEST["WoLgID"], $result['textlc']);

                    // Prepare view variables
                    $hex = $this->wordService->textToClassName($result['textlc']);
                    $translation = ExportService::replaceTabNewline($this->param("WoTranslation"));
                    if ($translation === '') {
                        $translation = '*';
                    }
                    $status = $_REQUEST["WoStatus"];
                    $romanization = $_REQUEST["WoRomanization"];
                    $text = $result['text'];
                    $textId = (int)$_REQUEST['tid'];
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
        $ann = isset($_REQUEST['ann']) ? $_REQUEST['ann'] : '';

        if ($wid === '') {
            \my_die('Word not found in show_word.php');
            return;
        }

        $word = $this->wordService->getWordDetails((int) $wid);
        if ($word === null) {
            \my_die('Word not found');
            return;
        }

        $tags = TagService::getWordTagListFormatted($wid, '', false, false);
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
     */
    public function insertWellknown(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $ord = isset($_REQUEST['ord']) ? (int) $_REQUEST['ord'] : 0;

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
     */
    public function insertIgnore(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $ord = isset($_REQUEST['ord']) ? (int) $_REQUEST['ord'] : 0;

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
        $value = isset($_POST['value']) ? $_POST['value'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : '';

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
        $tid = (int) ($_REQUEST['tid'] ?? 0);
        $pos = isset($_REQUEST['offset']) ? (int) $_REQUEST['offset'] : null;

        // Handle form submission (save terms)
        if (isset($_REQUEST['term'])) {
            $terms = $_REQUEST['term'];
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
            $sl = $_REQUEST['sl'] ?? null;
            $tl = $_REQUEST['tl'] ?? null;
            $this->displayBulkTranslateForm($tid, $sl, $tl, $pos);
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
        while ($record = mysqli_fetch_assoc($res)) {
            $record['hex'] = StringUtils::toClassName(
                \Lwt\Database\Escaping::prepareTextdata($record['WoTextLC'])
            );
            $record['translation'] = $record['WoTranslation'];
            $newWords[] = $record;
        }
        mysqli_free_result($res);

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
        while ($record = mysqli_fetch_assoc($res)) {
            $cnt++;
            if ($cnt < $limit) {
                $terms[] = $record;
            } else {
                $hasMore = true;
            }
        }
        mysqli_free_result($res);

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
     */
    public function setStatus(array $params): void
    {
        $textId = isset($_REQUEST['tid']) ? (int) $_REQUEST['tid'] : 0;
        $wordId = isset($_REQUEST['wid']) ? (int) $_REQUEST['wid'] : 0;
        $status = isset($_REQUEST['status']) ? (int) $_REQUEST['status'] : 0;

        if ($textId === 0 || $wordId === 0 || $status === 0) {
            return;
        }

        $wordData = $this->wordService->getWordData($wordId);
        if ($wordData === null) {
            \my_die("Word not found");
            return;
        }

        $term = $wordData['text'];
        $translation = $wordData['translation'] . TagService::getWordTagListFormatted($wordId, ' ', true, false);
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

        if (isset($_REQUEST['op']) && $_REQUEST['op'] === 'Import') {
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
        $tabType = $_REQUEST["Tab"] ?? 'c';
        $langId = (int) ($_REQUEST["LgID"] ?? 0);

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
            1 => is_string($_REQUEST["Col1"] ?? '') ? $_REQUEST["Col1"] : '',
            2 => is_string($_REQUEST["Col2"] ?? '') ? $_REQUEST["Col2"] : '',
            3 => is_string($_REQUEST["Col3"] ?? '') ? $_REQUEST["Col3"] : '',
            4 => is_string($_REQUEST["Col4"] ?? '') ? $_REQUEST["Col4"] : '',
            5 => is_string($_REQUEST["Col5"] ?? '') ? $_REQUEST["Col5"] : '',
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
        if ($fileUpl) {
            $fileName = $_FILES["thefile"]["tmp_name"];
        } else {
            if (empty($_REQUEST["Upload"])) {
                $this->message('Error: No data to import', false);
                return;
            }
            $fileName = $uploadService->createTempFile($_REQUEST["Upload"]);
        }

        $ignoreFirst = ($_REQUEST["IgnFirstLine"] ?? '0') === '1';
        $overwrite = (int) ($_REQUEST["Over"] ?? 0);
        $status = (int) ($_REQUEST["WoStatus"] ?? 1);
        $translDelim = $_REQUEST["transl_delim"] ?? '';

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
}
