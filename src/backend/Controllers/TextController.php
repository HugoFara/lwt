<?php

/**
 * \file
 * \brief Text Controller - Text management and reading
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-textcontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Services\TextService;
use Lwt\Services\TextDisplayService;
use Lwt\Services\TagService;
use Lwt\Services\LanguageService;
use Lwt\Services\LanguageDefinitions;
use Lwt\Services\MobileService;
use Lwt\Database\Settings;
use Lwt\Database\Validation;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\SelectOptionsBuilder;

require_once __DIR__ . '/../Services/TextService.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../View/Helper/SelectOptionsBuilder.php';
require_once __DIR__ . '/../Services/TextDisplayService.php';
require_once __DIR__ . '/../Services/TagService.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';
require_once __DIR__ . '/../Services/MobileService.php';

/**
 * Controller for text management and reading interface.
 *
 * Handles:
 * - Text reading interface
 * - Text CRUD operations
 * - Text display/print modes
 * - Archived texts
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextController extends BaseController
{
    /**
     * Text service for business logic.
     *
     * @var TextService
     */
    private TextService $textService;

    /**
     * Language service for language operations.
     *
     * @var LanguageService
     */
    private LanguageService $languageService;

    /**
     * Mobile service for mobile detection.
     *
     * @var MobileService
     */
    private MobileService $mobileService;

    /**
     * Constructor - initialize services.
     */
    public function __construct()
    {
        parent::__construct();
        $this->textService = new TextService();
        $this->languageService = new LanguageService();
        $this->mobileService = new MobileService();
    }

    /**
     * Read text interface (replaces text_read.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function read(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Services/TextStatisticsService.php';
        require_once __DIR__ . '/../Services/SentenceService.php';
        require_once __DIR__ . '/../Services/AnnotationService.php';
        require_once __DIR__ . '/../Services/SimilarTermsService.php';
        require_once __DIR__ . '/../Services/TextNavigationService.php';
        require_once __DIR__ . '/../Services/TextParsingService.php';
        require_once __DIR__ . '/../Services/ExpressionService.php';
        require_once __DIR__ . '/../Core/Database/Restore.php';
        require_once __DIR__ . '/../Services/TextReadingService.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';
        require_once __DIR__ . '/../Services/MediaService.php';
        require_once __DIR__ . '/../Services/WordStatusService.php';
        require_once __DIR__ . '/../Services/ExportService.php';

        // Get text ID from request
        $textId = $this->getTextIdFromRequest();

        if ($textId === null) {
            header("Location: /text/edit");
            exit();
        }

        // Render the reading page
        $this->renderReadPage($textId);
    }

    /**
     * Get text ID from request parameters.
     *
     * @return int|null Text ID or null
     */
    private function getTextIdFromRequest(): ?int
    {
        if (isset($_REQUEST['text']) && is_numeric($_REQUEST['text'])) {
            return (int)$_REQUEST['text'];
        }
        if (isset($_REQUEST['start']) && is_numeric($_REQUEST['start'])) {
            return (int)$_REQUEST['start'];
        }
        return null;
    }

    /**
     * Render the text reading page.
     *
     * @param int $textId Text ID
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function renderReadPage(int $textId): void
    {
        // Prepare header data
        $headerData = $this->textService->getTextForReading($textId);
        if ($headerData === null) {
            header("Location: /text/edit");
            exit();
        }

        $title = (string) $headerData['TxTitle'];
        $langId = (int) $headerData['TxLgID'];
        $media = isset($headerData['TxAudioURI']) ? trim((string) $headerData['TxAudioURI']) : '';
        $audioPosition = (int) ($headerData['TxAudioPosition'] ?? 0);
        $sourceUri = (string) ($headerData['TxSourceURI'] ?? '');
        $text = (string) $headerData['TxText'];
        $languageName = (string) $headerData['LgName'];

        // Save current text
        Settings::save('currenttext', $textId);

        // User settings for header
        $showAll = Settings::getZeroOrOne('showallwords', 1);
        $showLearning = Settings::getZeroOrOne('showlearningtranslations', 1);

        // Get language code and phonetic text for TTS
        $languageCode = $this->languageService->getLanguageCode($langId, LanguageDefinitions::getAll());
        $phoneticText = $this->languageService->getPhoneticReadingById($text, $langId);
        $voiceApi = $this->textService->getTtsVoiceApi($langId);

        // Prepare text content data
        $textData = $this->textService->getTextDataForContent($textId);
        $annotatedText = (string) ($textData['TxAnnotatedText'] ?? '');
        $textPosition = (int) ($textData['TxPosition'] ?? 0);

        // Language settings for text display
        $langSettings = $this->textService->getLanguageSettingsForReading($langId);
        $dictLink1 = $langSettings['LgDict1URI'] ?? '';
        $dictLink2 = $langSettings['LgDict2URI'] ?? '';
        $translatorLink = $langSettings['LgGoogleTranslateURI'] ?? '';
        $textSize = (int) $langSettings['LgTextSize'];
        $regexpWordChars = $langSettings['LgRegexpWordCharacters'] ?? '';
        $removeSpaces = (int) $langSettings['LgRemoveSpaces'];
        $rtlScript = (bool) $langSettings['LgRightToLeft'];

        // Additional settings for text view
        $modeTrans = (int) Settings::getWithDefault('set-text-frame-annotation-position');
        $visitStatus = Settings::getWithDefault('set-text-visit-statuses-via-key');
        if ($visitStatus == '') {
            $visitStatus = '0';
        }
        $termDelimiter = Settings::getWithDefault('set-term-translation-delimiters');
        $tooltipMode = (int) Settings::getWithDefault('set-tooltip-mode');
        $hts = Settings::getWithDefault('set-hts');

        // For text content view, reassign the title
        $textTitle = $title;

        // Desktop frame width
        $frameLWidth = (int) Settings::getWithDefault('set-text-l-framewidth-percent');

        // Start page
        PageLayoutHelper::renderPageStartNobody(
            'Read',
            "body {
                margin: 20px;
                max-width: 100%;
            }"
        );

        // Render appropriate layout
        if ($this->mobileService->isMobile()) {
            include __DIR__ . '/../Views/Text/read_mobile.php';
        } else {
            include __DIR__ . '/../Views/Text/read_desktop.php';
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Edit texts list (replaces text_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function edit(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Services/TextStatisticsService.php';
        require_once __DIR__ . '/../Services/SentenceService.php';
        require_once __DIR__ . '/../Services/AnnotationService.php';
        require_once __DIR__ . '/../Services/SimilarTermsService.php';
        require_once __DIR__ . '/../Services/TextNavigationService.php';
        require_once __DIR__ . '/../Services/TextParsingService.php';
        require_once __DIR__ . '/../Services/ExpressionService.php';
        require_once __DIR__ . '/../Core/Database/Restore.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';
        require_once __DIR__ . '/../Services/MediaService.php';
        require_once __DIR__ . '/../Services/WordStatusService.php';
        require_once __DIR__ . '/../Core/Bootstrap/start_session.php';
        require_once __DIR__ . '/../Core/Integration/text_from_yt.php';
        require_once __DIR__ . '/../Core/Entity/Text.php';

        // Get filter parameters
        $currentLang = Validation::language(
            (string) \processDBParam("filterlang", 'currentlanguage', '', false)
        );

        // Check for actions that skip page start
        $noPagestart = ($this->param('markaction') == 'test' ||
            $this->param('markaction') == 'deltag' ||
            substr($this->param('op'), -8) == 'and Open');

        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart('My ' . $this->languageService->getLanguageName($currentLang) . ' Texts', true);
        }

        $message = '';

        // Handle mark actions
        if (isset($_REQUEST['markaction'])) {
            $message = $this->handleMarkAction(
                $_REQUEST['markaction'],
                $_REQUEST['marked'] ?? [],
                $this->param('data')
            );
        }

        // Handle single item actions
        if (isset($_REQUEST['del'])) {
            $message = $this->textService->deleteText((int) $_REQUEST['del']);
        } elseif (isset($_REQUEST['arch'])) {
            $message = $this->textService->archiveText((int) $_REQUEST['arch']);
        } elseif (isset($_REQUEST['op'])) {
            $result = $this->handleTextOperation(
                $_REQUEST['op'],
                $noPagestart,
                $currentLang
            );
            $message .= ($message ? " / " : "") . $result['message'];
            if ($result['redirect']) {
                return;
            }
        }

        // Display appropriate page
        if (isset($_REQUEST['new'])) {
            $this->showNewTextForm((int) $currentLang);
        } elseif (isset($_REQUEST['chg'])) {
            $this->showEditTextForm((int) $_REQUEST['chg']);
        } else {
            $this->showTextsList($currentLang, $message);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle mark actions for multiple texts.
     *
     * @param string $markAction Action to perform
     * @param array  $marked     Array of marked text IDs
     * @param string $actionData Additional data for the action
     *
     * @return string Result message
     */
    private function handleMarkAction(
        string $markAction,
        array $marked,
        string $actionData
    ): string {
        $message = "Multiple Actions: 0";

        if (count($marked) === 0) {
            return $message;
        }

        $list = "(" . implode(",", array_map('intval', $marked)) . ")";

        switch ($markAction) {
            case 'del':
                $message = $this->textService->deleteTexts($marked);
                break;

            case 'arch':
                $message = $this->textService->archiveTexts($marked);
                break;

            case 'addtag':
                $message = TagService::addTagToTexts($actionData, $list);
                break;

            case 'deltag':
                TagService::removeTagFromTexts($actionData, $list);
                header("Location: /texts");
                exit();

            case 'setsent':
                $message = $this->textService->setTermSentences($marked, false);
                break;

            case 'setactsent':
                $message = $this->textService->setTermSentences($marked, true);
                break;

            case 'rebuild':
                $message = $this->textService->rebuildTexts($marked);
                break;

            case 'test':
                $_SESSION['testsql'] = $list;
                header("Location: /test?selection=3");
                exit();
        }

        return $message;
    }

    /**
     * Handle text save/update operations.
     *
     * @param string     $op          Operation name
     * @param bool       $noPagestart Whether to skip page start
     * @param string|int $currentLang Current language ID
     *
     * @return array{message: string, redirect: bool}
     */
    private function handleTextOperation(
        string $op,
        bool $noPagestart,
        string|int $currentLang
    ): array {
        // Validate text length
        if (!$this->textService->validateTextLength($_REQUEST['TxText'])) {
            $message = "Error: Text too long, must be below 65000 Bytes";
            if ($noPagestart) {
                PageLayoutHelper::renderPageStart('My ' . $this->languageService->getLanguageName($currentLang) . ' Texts', true);
            }
            return ['message' => $message, 'redirect' => false];
        }

        if ($op == 'Check') {
            // Check text only
            echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
            $this->textService->checkText(
                \remove_soft_hyphens($_REQUEST['TxText']),
                (int) $_REQUEST['TxLgID']
            );
            echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        $textId = isset($_REQUEST['TxID']) ? (int) $_REQUEST['TxID'] : 0;
        $isNew = str_starts_with($op, 'Save');

        $result = $this->textService->saveTextAndReparse(
            $isNew ? 0 : $textId,
            (int) $_REQUEST['TxLgID'],
            $_REQUEST['TxTitle'],
            $_REQUEST['TxText'],
            $_REQUEST['TxAudioURI'] ?? '',
            $_REQUEST['TxSourceURI'] ?? ''
        );

        // Redirect if "and Open" was requested
        if (str_ends_with($op, "and Open")) {
            header('Location: /text/read?start=' . $result['textId']);
            exit();
        }

        return ['message' => $result['message'], 'redirect' => false];
    }

    /**
     * Show the new text form.
     *
     * @param int $langId Language ID
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function showNewTextForm(int $langId): void
    {
        $text = new \Lwt\Classes\Text();
        $text->id = 0;
        $text->lgid = $langId;
        $text->title = '';
        $text->text = '';
        $text->source = '';
        $text->media_uri = '';

        $textId = 0;
        $annotated = false;
        $isNew = true;
        $languageData = $this->textService->getLanguageDataForForm();
        $scrdir = $this->languageService->getScriptDirectionTag($text->lgid);

        include __DIR__ . '/../Views/Text/edit_form.php';
        \Lwt\Text_From_Youtube\do_js();
    }

    /**
     * Show the edit text form.
     *
     * @param int $txid Text ID
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function showEditTextForm(int $txid): void
    {
        $record = $this->textService->getTextForEdit($txid);

        if ($record === null) {
            echo '<p>Text not found.</p>';
            return;
        }

        $text = new \Lwt\Classes\Text();
        $text->id = $record['TxID'];
        $text->lgid = $record['TxLgID'];
        $text->title = $record['TxTitle'];
        $text->text = $record['TxText'];
        $text->source = $record['TxSourceURI'] ?? '';
        $text->media_uri = $record['TxAudioURI'] ?? '';

        $textId = (int) $record['TxID'];
        $annotated = (bool) $record['annot_exists'];
        $isNew = false;
        $languageData = $this->textService->getLanguageDataForForm();
        $scrdir = $this->languageService->getScriptDirectionTag($text->lgid);

        include __DIR__ . '/../Views/Text/edit_form.php';
    }

    /**
     * Show the texts list.
     *
     * @param string|int $currentLang Current language filter
     * @param string     $message     Message to display
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     * @psalm-suppress UnusedParam $message is used in included view file
     */
    private function showTextsList(string|int $currentLang, string $message): void
    {
        $currentSort = (int) \processDBParam("sort", 'currenttextsort', '1', true);
        $currentPage = (int) \processSessParam("page", "currenttextpage", '1', true);
        $currentQuery = (string) \processSessParam("query", "currenttextquery", '', false);
        $currentQueryMode = (string) \processSessParam(
            "query_mode",
            "currenttextquerymode",
            'title,text',
            false
        );
        $currentRegexMode = Settings::getWithDefault("set-regex-mode");
        // Cast to string for Validation::textTag
        $langForTag = (string) $currentLang;
        $currentTag1 = Validation::textTag(
            (string) \processSessParam("tag1", "currenttexttag1", '', false),
            $langForTag
        );
        $currentTag2 = Validation::textTag(
            (string) \processSessParam("tag2", "currenttexttag2", '', false),
            $langForTag
        );
        $currentTag12 = (string) \processSessParam("tag12", "currenttexttag12", '', false);

        // Build WHERE clauses
        $whLang = ($currentLang != '') ? (' and TxLgID=' . $currentLang) : '';

        // Build query WHERE clause
        $whQuery = $this->textService->buildTextQueryWhereClause(
            $currentQuery,
            $currentQueryMode,
            $currentRegexMode
        );

        // Validate regex query
        if ($currentQuery !== '' && $currentRegexMode !== '') {
            if (!$this->textService->validateRegexQuery($currentQuery, $currentRegexMode)) {
                $whQuery = '';
                unset($_SESSION['currentwordquery']);
                if (isset($_REQUEST['query'])) {
                    echo '<p id="hide3" style="color:red;text-align:center;">' .
                        '+++ Warning: Invalid Search +++</p>';
                }
            }
        }

        // Build tag HAVING clause
        $whTag = $this->textService->buildTextTagHavingClause(
            $currentTag1,
            $currentTag2,
            $currentTag12
        );

        // Get texts count and list
        $totalCount = $this->textService->getTextCount($whLang, $whQuery, $whTag);
        $perPage = $this->textService->getTextsPerPage();
        $pagination = $this->textService->getPagination($totalCount, $currentPage, $perPage);

        $texts = [];
        if ($totalCount > 0) {
            $texts = $this->textService->getTextsList(
                $whLang,
                $whQuery,
                $whTag,
                $currentSort,
                $pagination['currentPage'],
                $perPage
            );
        }

        // Get word statuses for chart display
        $statuses = \Lwt\Services\WordStatusService::getStatuses();
        $statuses[0]["name"] = 'Unknown';
        $statuses[0]["abbr"] = 'Ukn';

        // Get word count display settings
        $showCounts = Settings::getWithDefault('set-show-text-word-counts');
        if (strlen($showCounts) != 5) {
            $showCounts = "11111";
        }

        include __DIR__ . '/../Views/Text/edit_list.php';
    }

    /**
     * Display improved text (replaces text_display.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function display(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Services/TextStatisticsService.php';
        require_once __DIR__ . '/../Services/SentenceService.php';
        require_once __DIR__ . '/../Services/AnnotationService.php';
        require_once __DIR__ . '/../Services/SimilarTermsService.php';
        require_once __DIR__ . '/../Services/TextNavigationService.php';
        require_once __DIR__ . '/../Services/TextParsingService.php';
        require_once __DIR__ . '/../Services/ExpressionService.php';
        require_once __DIR__ . '/../Core/Database/Restore.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';
        require_once __DIR__ . '/../Services/MediaService.php';

        $textId = $this->paramInt('text', 0);

        if ($textId === 0) {
            header("Location: /text/edit");
            exit();
        }

        $displayService = new TextDisplayService();

        // Get annotated text
        $annotatedText = $displayService->getAnnotatedText($textId);
        if (strlen($annotatedText) <= 0) {
            header("Location: /text/edit");
            exit();
        }

        // Get display settings
        $settings = $displayService->getTextDisplaySettings($textId);
        if ($settings === null) {
            header("Location: /text/edit");
            exit();
        }

        // Get header data
        $headerData = $displayService->getHeaderData($textId);
        if ($headerData === null) {
            header("Location: /text/edit");
            exit();
        }

        // Prepare view variables
        $title = $headerData['title'];
        $audio = $headerData['audio'];
        $sourceUri = $headerData['sourceUri'];
        $textSize = $settings['textSize'];
        $rtlScript = $settings['rtlScript'];

        // Get navigation links
        $textLinks = \getPreviousAndNextTextLinks(
            $textId,
            'display_impr_text.php?text=',
            true,
            ' &nbsp; &nbsp; '
        );

        // Parse annotations
        $annotations = $displayService->parseAnnotations($annotatedText);

        // Save current text
        $displayService->saveCurrentText($textId);

        // Render page
        PageLayoutHelper::renderPageStartNobody('Display');
        include __DIR__ . '/../Views/Text/display_main.php';
        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Print text (replaces text_print.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function printText(array $params): void
    {
        include __DIR__ . '/../Legacy/text_print.php';
    }

    /**
     * Print plain text (replaces text_print_plain.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function printPlain(array $params): void
    {
        include __DIR__ . '/../Legacy/text_print_plain.php';
    }

    /**
     * Import long text (replaces text_import_long.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function importLong(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';

        PageLayoutHelper::renderPageStart('Long Text Import', true);

        $maxInputVars = ini_get('max_input_vars');
        if ($maxInputVars === false || $maxInputVars == '') {
            $maxInputVars = 1000;
        }
        $maxInputVars = (int) $maxInputVars;

        $op = isset($_REQUEST['op']) ? $_REQUEST['op'] : '';

        if (substr($op, 0, 5) == 'NEXT ') {
            $this->importLongCheck($maxInputVars);
        } elseif (substr($op, 0, 5) == 'Creat') {
            $this->importLongSave();
        } else {
            $this->importLongForm($maxInputVars);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Display the long text import form.
     *
     * @param int $maxInputVars Maximum input variables
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function importLongForm(int $maxInputVars): void
    {
        $translateUris = $this->textService->getLanguageTranslateUris();
        $languageData = [];
        foreach ($translateUris as $lgId => $uri) {
            $languageData[$lgId] = \langFromDict($uri);
        }

        $languages = $this->languageService->getLanguagesForSelect();
        $languagesOption = SelectOptionsBuilder::forLanguages(
            $languages,
            Settings::get('currentlanguage'),
            '[Choose...]'
        );

        include __DIR__ . '/../Views/Text/import_long_form.php';
    }

    /**
     * Check/preview long text import.
     *
     * @param int $maxInputVars Maximum input variables
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function importLongCheck(int $maxInputVars): void
    {
        $langId = (int) $_REQUEST["LgID"];
        $title = (string) $_REQUEST["TxTitle"];
        $paragraphHandling = (int) $_REQUEST["paragraph_handling"];
        $maxSent = (int) $_REQUEST["maxsent"];
        $sourceUri = (string) $_REQUEST["TxSourceURI"];
        $textTags = null;
        if (isset($_REQUEST["TextTags"])) {
            $textTags = json_encode($_REQUEST["TextTags"]);
        }

        $data = $this->textService->prepareLongTextData(
            $_FILES,
            (string) ($_REQUEST["Upload"] ?? ''),
            $paragraphHandling
        );

        if ($data == "") {
            $message = "Error: No text specified!";
            $this->message($message, false);
            return;
        }

        $texts = $this->textService->splitLongText($data, $langId, $maxSent);
        $textCount = count($texts);

        if ($textCount > $maxInputVars - 20) {
            $message = "Error: Too many texts (" . $textCount . " > " .
                ($maxInputVars - 20) .
                "). You must increase 'Maximum Sentences per Text'!";
            $this->message($message, false);
            return;
        }

        $scrdir = $this->languageService->getScriptDirectionTag($langId);
        include __DIR__ . '/../Views/Text/import_long_check.php';
    }

    /**
     * Save long text import.
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function importLongSave(): void
    {
        $langId = (int) $_REQUEST["LgID"];
        $title = $_REQUEST["TxTitle"];
        $sourceUri = $_REQUEST["TxSourceURI"];

        if (isset($_REQUEST["TextTags"])) {
            $_REQUEST["TextTags"] = json_decode($_REQUEST["TextTags"], true);
        }

        $textCount = (int) $_REQUEST["TextCount"];
        $texts = $_REQUEST["text"];

        $result = $this->textService->saveLongTextImport(
            $langId,
            $title,
            $sourceUri,
            $texts,
            $textCount
        );

        $message = $result['message'];
        include __DIR__ . '/../Views/Text/import_long_result.php';
    }

    /**
     * Set text mode (replaces text_set_mode.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function setMode(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';

        $textId = $this->param('text');
        if ($textId === '') {
            return;
        }

        $showAll = $this->paramInt('mode', 0);
        $showLearning = $this->paramInt('showLearning', 0);

        // Save settings and get the old value
        Settings::save('showallwords', $showAll);
        $oldShowLearning = Settings::getZeroOrOne('showlearningtranslations', 1);
        Settings::save('showlearningtranslations', $showLearning);

        // Display result page
        PageLayoutHelper::renderPageStart("Text Display Mode changed", false);

        $waitingIconPath = \get_file_path('assets/icons/waiting.gif');
        flush();

        include __DIR__ . '/../Views/Text/set_mode_result.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Check text (replaces text_check.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function check(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';

        PageLayoutHelper::renderPageStart('Check a Text', true);

        if (isset($_REQUEST['op']) && $_REQUEST['op'] === 'Check') {
            // Do the check operation
            echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
            $this->textService->checkText(
                (string) $_REQUEST['TxText'],
                (int) $_REQUEST['TxLgID']
            );
            echo '<p><input type="button" value="&lt;&lt; Back" onclick="history.back();" /></p>';
        } else {
            // Display the form
            $languageData = [];
            $translateUris = $this->textService->getLanguageTranslateUris();
            foreach ($translateUris as $lgId => $uri) {
                $languageData[$lgId] = \langFromDict($uri);
            }
            $languages = $this->languageService->getLanguagesForSelect();
            $languagesOption = SelectOptionsBuilder::forLanguages(
                $languages,
                \Lwt\Database\Settings::get('currentlanguage'),
                '[Choose...]'
            );

            include __DIR__ . '/../Views/Text/check_form.php';
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Archived texts management (replaces text_archived.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function archived(array $params): void
    {
        require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
        require_once __DIR__ . '/../Services/TextStatisticsService.php';
        require_once __DIR__ . '/../Services/SentenceService.php';
        require_once __DIR__ . '/../Services/AnnotationService.php';
        require_once __DIR__ . '/../Services/SimilarTermsService.php';
        require_once __DIR__ . '/../Services/TextNavigationService.php';
        require_once __DIR__ . '/../Services/TextParsingService.php';
        require_once __DIR__ . '/../Services/ExpressionService.php';
        require_once __DIR__ . '/../Core/Database/Restore.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';

        // Get filter parameters
        $currentLang = Validation::language(
            (string) \processDBParam("filterlang", 'currentlanguage', '', false)
        );
        $currentSort = (int) \processDBParam("sort", 'currentarchivesort', '1', true);
        $currentPage = (int) \processSessParam("page", "currentarchivepage", '1', true);
        $currentQuery = (string) \processSessParam("query", "currentarchivequery", '', false);
        $currentQueryMode = (string) \processSessParam(
            "query_mode",
            "currentarchivequerymode",
            'title,text',
            false
        );
        $currentRegexMode = Settings::getWithDefault("set-regex-mode");
        $currentTag1 = Validation::archTextTag(
            (string) \processSessParam("tag1", "currentarchivetexttag1", '', false),
            $currentLang
        );
        $currentTag2 = Validation::archTextTag(
            (string) \processSessParam("tag2", "currentarchivetexttag2", '', false),
            $currentLang
        );
        $currentTag12 = (string) \processSessParam(
            "tag12",
            "currentarchivetexttag12",
            '',
            false
        );

        // Build WHERE clauses
        $whLang = ($currentLang != '') ? (' and AtLgID=' . $currentLang) : '';

        // Build query WHERE clause
        $whQuery = $this->textService->buildArchivedQueryWhereClause(
            $currentQuery,
            $currentQueryMode,
            $currentRegexMode
        );

        // Validate regex query
        if ($currentQuery !== '' && $currentRegexMode !== '') {
            if (!$this->textService->validateRegexQuery($currentQuery, $currentRegexMode)) {
                $currentQuery = '';
                $whQuery = '';
                unset($_SESSION['currentwordquery']);
                if (isset($_REQUEST['query'])) {
                    echo '<p id="hide3" style="color:red;text-align:center;">' .
                        '+++ Warning: Invalid Search +++</p>';
                }
            }
        }

        // Build tag HAVING clause
        $whTag = $this->textService->buildArchivedTagHavingClause(
            $currentTag1,
            $currentTag2,
            $currentTag12
        );

        // Handle mark actions that skip pagestart
        $noPagestart = ($this->param('markaction') == 'deltag');
        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart('My ' . $this->languageService->getLanguageName($currentLang) . ' Text Archive', true);
        }

        $message = '';

        // Handle mark actions
        if (isset($_REQUEST['markaction'])) {
            $message = $this->handleArchivedMarkAction(
                $_REQUEST['markaction'],
                $_REQUEST['marked'] ?? [],
                $this->param('data')
            );
        }

        // Handle single item actions
        if (isset($_REQUEST['del'])) {
            $message = $this->textService->deleteArchivedText((int) $_REQUEST['del']);
        } elseif (isset($_REQUEST['unarch'])) {
            $result = $this->textService->unarchiveText((int) $_REQUEST['unarch']);
            $message = $result['message'];
        } elseif (isset($_REQUEST['op']) && $_REQUEST['op'] == 'Change') {
            $message = $this->textService->updateArchivedText(
                (int) $_REQUEST['AtID'],
                (int) $_REQUEST['AtLgID'],
                (string) $_REQUEST['AtTitle'],
                (string) $_REQUEST['AtText'],
                (string) $_REQUEST['AtAudioURI'],
                (string) $_REQUEST['AtSourceURI']
            );
            TagService::saveArchivedTextTags((int) $_REQUEST['AtID']);
        }

        // Display edit form or list
        if (isset($_REQUEST['chg'])) {
            $textId = (int) $_REQUEST['chg'];
            $record = $this->textService->getArchivedTextById($textId);
            if ($record) {
                include __DIR__ . '/../Views/Text/archived_form.php';
            }
        } else {
            // Display list
            $totalCount = $this->textService->getArchivedTextCount($whLang, $whQuery, $whTag);
            $perPage = $this->textService->getArchivedTextsPerPage();
            $pagination = $this->textService->getPagination($totalCount, $currentPage, $perPage);

            $texts = [];
            if ($totalCount > 0) {
                $texts = $this->textService->getArchivedTextsList(
                    $whLang,
                    $whQuery,
                    $whTag,
                    $currentSort,
                    $pagination['currentPage'],
                    $perPage
                );
            }

            include __DIR__ . '/../Views/Text/archived_list.php';
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle mark actions for archived texts.
     *
     * @param string $markAction Action to perform
     * @param array  $marked     Array of marked text IDs
     * @param string $actionData Additional data for the action
     *
     * @return string Result message
     */
    private function handleArchivedMarkAction(
        string $markAction,
        array $marked,
        string $actionData
    ): string {
        $message = "Multiple Actions: 0";

        if (count($marked) === 0) {
            return $message;
        }

        switch ($markAction) {
            case 'del':
                $message = $this->textService->deleteArchivedTexts($marked);
                break;

            case 'addtag':
                $list = "(" . implode(",", array_map('intval', $marked)) . ")";
                $message = TagService::addTagToArchivedTexts($actionData, $list);
                break;

            case 'deltag':
                $list = "(" . implode(",", array_map('intval', $marked)) . ")";
                TagService::removeTagFromArchivedTexts($actionData, $list);
                header("Location: /text/archived");
                exit();

            case 'unarch':
                $message = $this->textService->unarchiveTexts($marked);
                break;
        }

        return $message;
    }
}
