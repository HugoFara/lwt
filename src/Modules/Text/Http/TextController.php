<?php declare(strict_types=1);
/**
 * Text Controller - Text management and reading
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Text\Http;

use Lwt\Controllers\BaseController;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Text\Application\Services\TextDisplayService;
use Lwt\Modules\Text\Application\Services\TextNavigationService;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Core\StringUtils;

// Base path for legacy includes
define('LWT_BACKEND_PATH', dirname(__DIR__, 3) . '/backend');

// Module views path
define('LWT_TEXT_MODULE_VIEWS', dirname(__DIR__) . '/Views');

require_once dirname(__DIR__) . '/Application/TextFacade.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/SelectOptionsBuilder.php';
require_once dirname(__DIR__) . '/Application/Services/TextDisplayService.php';
// LanguageFacade and LanguagePresets loaded via autoloader

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
 * @package  Lwt\Modules\Text\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TextController extends BaseController
{
    private TextFacade $textService;
    private LanguageFacade $languageService;
    private TextDisplayService $displayService;

    /**
     * Create a new TextController.
     *
     * @param TextFacade|null         $textService     Text facade for text operations
     * @param LanguageFacade|null     $languageService Language facade for language operations
     * @param TextDisplayService|null $displayService  Text display service
     */
    public function __construct(
        ?TextFacade $textService = null,
        ?LanguageFacade $languageService = null,
        ?TextDisplayService $displayService = null
    ) {
        parent::__construct();
        $this->textService = $textService ?? new TextFacade();
        $this->languageService = $languageService ?? new LanguageFacade();
        $this->displayService = $displayService ?? new TextDisplayService();
    }

    /**
     * Read text interface.
     *
     * Modern text reading interface with client-side rendering using Alpine.js.
     *
     * @param array $params Route parameters
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function read(array $params): void
    {
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        require_once dirname(__DIR__, 2) . '/Admin/Application/Services/MediaService.php';

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
        $textId = $this->paramInt('text');
        if ($textId !== null) {
            return $textId;
        }
        $startId = $this->paramInt('start');
        if ($startId !== null) {
            return $startId;
        }
        return null;
    }

    /**
     * Render the text reading page.
     *
     * Uses client-side rendering via Alpine.js and API.
     *
     * @param int $textId Text ID
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function renderReadPage(int $textId): void
    {
        // Prepare minimal header data
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

        // Save current text
        Settings::save('currenttext', $textId);

        // Start page layout
        PageLayoutHelper::renderPageStartNobody('Read', 'full-width');

        // Render desktop layout
        include LWT_TEXT_MODULE_VIEWS . '/read_desktop.php';

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
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        require_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        require_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        require_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        require_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        require_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        require_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        require_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
        require_once dirname(__DIR__, 2) . '/Admin/Application/Services/MediaService.php';
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/start_session.php';
        require_once LWT_BACKEND_PATH . '/Core/Integration/YouTubeImport.php';

        // Get filter parameters
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        // Check for actions that skip page start
        $noPagestart = ($this->param('markaction') == 'test' ||
            $this->param('markaction') == 'deltag' ||
            substr($this->param('op'), -8) == 'and Open');

        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart('Texts', true);
        }

        $message = '';

        // Handle mark actions
        $markAction = $this->param('markaction');
        if ($markAction !== '') {
            $message = $this->handleMarkAction(
                $markAction,
                $this->paramArray('marked'),
                $this->param('data')
            );
        }

        // Handle single item actions
        $delId = $this->paramInt('del');
        $archId = $this->paramInt('arch');
        $op = $this->param('op');
        if ($delId !== null) {
            $message = $this->textService->deleteText($delId);
        } elseif ($archId !== null) {
            $message = $this->textService->archiveText($archId);
        } elseif ($op !== '') {
            $result = $this->handleTextOperation(
                $op,
                $noPagestart,
                $currentLang
            );
            $message .= ($message ? " / " : "") . $result['message'];
            if ($result['redirect']) {
                return;
            }
        }

        // Display appropriate page
        if ($this->hasParam('new')) {
            $this->showNewTextForm((int) $currentLang);
        } elseif ($this->hasParam('chg')) {
            $this->showEditTextForm($this->paramInt('chg', 0) ?? 0);
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
                $message = TagsFacade::addTagToTexts($actionData, $list);
                break;

            case 'deltag':
                TagsFacade::removeTagFromTexts($actionData, $list);
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
        $txText = $this->param('TxText');
        $txLgId = $this->paramInt('TxLgID', 0) ?? 0;
        $txTitle = $this->param('TxTitle');
        $txAudioUri = $this->param('TxAudioURI');
        $txSourceUri = $this->param('TxSourceURI');

        // Validate text length
        if (!$this->textService->validateTextLength($txText)) {
            $message = "Error: Text too long, must be below 65000 Bytes";
            if ($noPagestart) {
                PageLayoutHelper::renderPageStart($this->languageService->getLanguageName($currentLang) . ' Texts', true);
            }
            return ['message' => $message, 'redirect' => false];
        }

        if ($op == 'Check') {
            // Check text only
            echo '<p><input type="button" value="&lt;&lt; Back" data-action="history-back" /></p>';
            $this->textService->checkText(
                StringUtils::removeSoftHyphens($txText),
                $txLgId
            );
            echo '<p><input type="button" value="&lt;&lt; Back" data-action="history-back" /></p>';
            PageLayoutHelper::renderPageEnd();
            exit();
        }

        $textId = $this->paramInt('TxID', 0) ?? 0;
        $isNew = str_starts_with($op, 'Save');

        $result = $this->textService->saveTextAndReparse(
            $isNew ? 0 : $textId,
            $txLgId,
            $txTitle,
            $txText,
            $txAudioUri,
            $txSourceUri
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
        $text = new \stdClass();
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
        $languages = $this->languageService->getLanguagesForSelect();
        $scrdir = $this->languageService->getScriptDirectionTag($text->lgid);

        include LWT_TEXT_MODULE_VIEWS . '/edit_form.php';
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

        $text = new \stdClass();
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
        $languages = $this->languageService->getLanguagesForSelect();
        $scrdir = $this->languageService->getScriptDirectionTag($text->lgid);

        include LWT_TEXT_MODULE_VIEWS . '/edit_form.php';
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
     */
    private function showTextsList(string|int $currentLang, string $message): void
    {
        $statuses = \Lwt\Modules\Vocabulary\Application\Services\TermStatusService::getStatuses();
        $statuses[0]["name"] = 'Unknown';
        $statuses[0]["abbr"] = 'Ukn';

        $activeLanguageId = (int) Settings::get('currentlanguage');

        include LWT_TEXT_MODULE_VIEWS . '/edit_list.php';
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
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        require_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        require_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        require_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        require_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        require_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        require_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        require_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
        require_once dirname(__DIR__, 2) . '/Admin/Application/Services/MediaService.php';

        $textId = $this->paramInt('text', 0) ?? 0;

        if ($textId === 0) {
            header("Location: /text/edit");
            exit();
        }

        // Get annotated text
        $annotatedText = $this->displayService->getAnnotatedText($textId);
        if (strlen($annotatedText) <= 0) {
            header("Location: /text/edit");
            exit();
        }

        // Get display settings
        $settings = $this->displayService->getTextDisplaySettings($textId);
        if ($settings === null) {
            header("Location: /text/edit");
            exit();
        }

        // Get header data
        $headerData = $this->displayService->getHeaderData($textId);
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
        $textLinks = (new TextNavigationService())->getPreviousAndNextTextLinks(
            $textId,
            'display_impr_text.php?text=',
            true,
            ' &nbsp; &nbsp; '
        );

        // Parse annotations
        $annotations = $this->displayService->parseAnnotations($annotatedText);

        // Save current text
        $this->displayService->saveCurrentText($textId);

        // Render page
        PageLayoutHelper::renderPageStartNobody('Display');
        include LWT_TEXT_MODULE_VIEWS . '/display_main.php';
        PageLayoutHelper::renderPageEnd();
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
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';

        PageLayoutHelper::renderPageStart('Long Text Import', true);

        $maxInputVars = ini_get('max_input_vars');
        if ($maxInputVars === false || $maxInputVars == '') {
            $maxInputVars = 1000;
        }
        $maxInputVars = (int) $maxInputVars;

        $op = $this->param('op');

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
            $languageData[$lgId] = UrlUtilities::langFromDict($uri);
        }

        $languages = $this->languageService->getLanguagesForSelect();
        $languagesOption = SelectOptionsBuilder::forLanguages(
            $languages,
            Settings::get('currentlanguage'),
            '[Choose...]'
        );

        include LWT_TEXT_MODULE_VIEWS . '/import_long_form.php';
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
        $langId = $this->paramInt('LgID', 0) ?? 0;
        $title = $this->param('TxTitle');
        $paragraphHandling = $this->paramInt('paragraph_handling', 0) ?? 0;
        $maxSent = $this->paramInt('maxsent', 0) ?? 0;
        $sourceUri = $this->param('TxSourceURI');
        $textTags = null;
        $textTagsArray = $this->paramArray('TextTags');
        if (!empty($textTagsArray)) {
            $textTags = json_encode($textTagsArray);
        }

        $data = $this->textService->prepareLongTextData(
            $_FILES,
            $this->param('Upload'),
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
        include LWT_TEXT_MODULE_VIEWS . '/import_long_check.php';
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
        $langId = $this->paramInt('LgID', 0) ?? 0;
        $title = $this->param('TxTitle');
        $sourceUri = $this->param('TxSourceURI');

        // TextTags comes as JSON-encoded string from hidden field
        $textTagsJson = $this->param('TextTags');
        $textTags = ($textTagsJson !== '') ? json_decode($textTagsJson, true) : null;

        $textCount = $this->paramInt('TextCount', 0) ?? 0;
        $texts = $this->paramArray('text');

        $result = $this->textService->saveLongTextImport(
            $langId,
            $title,
            $sourceUri,
            $texts,
            $textCount,
            $textTags
        );

        $message = $result['message'];
        include LWT_TEXT_MODULE_VIEWS . '/import_long_result.php';
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
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';

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

        $waitingIconPath = null; // Using Lucide icon instead
        flush();

        include LWT_TEXT_MODULE_VIEWS . '/set_mode_result.php';

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
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';

        PageLayoutHelper::renderPageStart('Check a Text', true);

        $op = $this->param('op');
        if ($op === 'Check') {
            // Do the check operation
            echo '<p><input type="button" value="&lt;&lt; Back" data-action="history-back" /></p>';
            $this->textService->checkText(
                $this->param('TxText'),
                $this->paramInt('TxLgID', 0) ?? 0
            );
            echo '<p><input type="button" value="&lt;&lt; Back" data-action="history-back" /></p>';
        } else {
            // Display the form
            $languageData = [];
            $translateUris = $this->textService->getLanguageTranslateUris();
            foreach ($translateUris as $lgId => $uri) {
                $languageData[$lgId] = UrlUtilities::langFromDict($uri);
            }
            $languages = $this->languageService->getLanguagesForSelect();
            $languagesOption = SelectOptionsBuilder::forLanguages(
                $languages,
                \Lwt\Shared\Infrastructure\Database\Settings::get('currentlanguage'),
                '[Choose...]'
            );

            include LWT_TEXT_MODULE_VIEWS . '/check_form.php';
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
        require_once LWT_BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        require_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        require_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        require_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        require_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        require_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        require_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        require_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';

        // Handle mark actions that skip pagestart
        $markAction = $this->param('markaction');
        $noPagestart = ($markAction == 'deltag');
        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart('Archived Texts', true);
        }

        $message = '';

        // Handle mark actions
        if ($markAction !== '') {
            $message = $this->handleArchivedMarkAction(
                $markAction,
                $this->paramArray('marked'),
                $this->param('data')
            );
        }

        // Handle single item actions
        $delId = $this->paramInt('del');
        $unarchId = $this->paramInt('unarch');
        $op = $this->param('op');
        if ($delId !== null) {
            $message = $this->textService->deleteArchivedText($delId);
        } elseif ($unarchId !== null) {
            $result = $this->textService->unarchiveText($unarchId);
            $message = $result['message'];
        } elseif ($op == 'Change') {
            $atId = $this->paramInt('AtID', 0) ?? 0;
            $message = $this->textService->updateArchivedText(
                $atId,
                $this->paramInt('AtLgID', 0) ?? 0,
                $this->param('AtTitle'),
                $this->param('AtText'),
                $this->param('AtAudioURI'),
                $this->param('AtSourceURI')
            );
            TagsFacade::saveArchivedTextTagsFromForm($atId);
        }

        // Display edit form or list
        $chgId = $this->paramInt('chg');
        if ($chgId !== null) {
            $textId = $chgId;
            $record = $this->textService->getArchivedTextById($textId);
            if ($record !== null) {
                $languages = $this->languageService->getLanguagesForSelect();
                include LWT_TEXT_MODULE_VIEWS . '/archived_form.php';
            }
        } else {
            $activeLanguageId = (int) Settings::get('currentlanguage');
            include LWT_TEXT_MODULE_VIEWS . '/archived_list.php';
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
                $message = TagsFacade::addTagToArchivedTexts($actionData, $list);
                break;

            case 'deltag':
                $list = "(" . implode(",", array_map('intval', $marked)) . ")";
                TagsFacade::removeTagFromArchivedTexts($actionData, $list);
                header("Location: /text/archived");
                exit();

            case 'unarch':
                $message = $this->textService->unarchiveTexts($marked);
                break;
        }

        return $message;
    }
}
