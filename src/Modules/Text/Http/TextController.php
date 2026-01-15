<?php

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

declare(strict_types=1);

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
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Core\Globals;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;

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
    /**
     * Base path for legacy includes.
     */
    private const BACKEND_PATH = __DIR__ . '/../../../backend';

    /**
     * Module views path.
     */
    private const MODULE_VIEWS = __DIR__ . '/../Views';
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
     * Routes:
     * - GET /text/{text:int}/read (new RESTful route)
     * - GET /text/read?text=[id] (legacy route)
     *
     * @param int|null $text Text ID (injected from route parameter)
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function read(?int $text = null): ?RedirectResponse
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__, 2) . '/Admin/Application/Services/MediaService.php';

        // Get text ID from route param or query params
        $textId = $this->getTextIdFromRequest($text);

        if ($textId === null) {
            return $this->redirect('/text/edit');
        }

        // Render the reading page
        return $this->renderReadPage($textId);
    }

    /**
     * Get text ID from request parameters.
     *
     * @param int|null $injectedId Text ID injected from route parameter
     *
     * @return int|null Text ID or null
     */
    private function getTextIdFromRequest(?int $injectedId = null): ?int
    {
        // First check for injected route parameter
        if ($injectedId !== null) {
            return $injectedId;
        }
        // Then check query parameters
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
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    private function renderReadPage(int $textId): ?RedirectResponse
    {
        // Prepare minimal header data
        $headerData = $this->textService->getTextForReading($textId);
        if ($headerData === null) {
            return $this->redirect('/text/edit');
        }

        $title = (string) $headerData['TxTitle'];
        $langId = (int) $headerData['TxLgID'];
        $media = isset($headerData['TxAudioURI']) ? trim((string) $headerData['TxAudioURI']) : '';
        $audioPosition = (int) ($headerData['TxAudioPosition'] ?? 0);
        $sourceUri = (string) ($headerData['TxSourceURI'] ?? '');

        // Book/chapter context for navigation
        $bookContext = null;
        try {
            $bookFacade = Container::getInstance()->getTyped(
                \Lwt\Modules\Book\Application\BookFacade::class
            );
            $bookContext = $bookFacade->getBookContextForText($textId);
        } catch (\Throwable $e) {
            // Book module may not be available or no book context
            $bookContext = null;
        }

        // Save current text
        Settings::save('currenttext', $textId);

        // Start page layout
        PageLayoutHelper::renderPageStartNobody('Read', 'full-width');

        // Render desktop layout
        include self::MODULE_VIEWS . '/read_desktop.php';

        PageLayoutHelper::renderPageEnd();

        return null;
    }

    /**
     * Show new text form.
     *
     * Route: GET /texts/new
     *
     * @param array $params Route parameters
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function new(array $params): ?RedirectResponse
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        include_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        include_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        include_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        include_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
        include_once dirname(__DIR__, 2) . '/Admin/Application/Services/MediaService.php';
        include_once self::BACKEND_PATH . '/Core/Bootstrap/start_session.php';
        include_once self::BACKEND_PATH . '/Core/Integration/YouTubeImport.php';

        // Get filter parameters
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        // Handle save operation
        $op = $this->param('op');
        if ($op !== '') {
            $noPagestart = (substr($op, -8) == 'and Open');
            if (!$noPagestart) {
                PageLayoutHelper::renderPageStart('Texts', true);
            }
            $result = $this->handleTextOperation($op, $noPagestart, $currentLang);
            if ($result instanceof RedirectResponse) {
                return $result;
            }
            if ($result['redirect']) {
                return null;
            }
            PageLayoutHelper::renderPageEnd();
            return null;
        }

        PageLayoutHelper::renderPageStart('Texts', true);
        $this->showNewTextForm((int) $currentLang);
        PageLayoutHelper::renderPageEnd();

        return null;
    }

    /**
     * Edit texts list (replaces text_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function edit(array $params): ?RedirectResponse
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        include_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        include_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        include_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        include_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
        include_once dirname(__DIR__, 2) . '/Admin/Application/Services/MediaService.php';
        include_once self::BACKEND_PATH . '/Core/Bootstrap/start_session.php';
        include_once self::BACKEND_PATH . '/Core/Integration/YouTubeImport.php';

        // Get filter parameters
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        // Check for actions that skip page start
        $noPagestart = ($this->param('markaction') == 'review' ||
            $this->param('markaction') == 'deltag' ||
            substr($this->param('op'), -8) == 'and Open');

        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart('Texts', true);
        }

        $message = '';

        // Handle mark actions
        $markAction = $this->param('markaction');
        if ($markAction !== '') {
            $result = $this->handleMarkAction(
                $markAction,
                $this->paramArray('marked'),
                $this->param('data')
            );
            if ($result instanceof RedirectResponse) {
                return $result;
            }
            $message = $result;
        }

        // Handle single item actions
        $delId = $this->paramInt('del');
        $archId = $this->paramInt('arch');
        $op = $this->param('op');
        if ($delId !== null) {
            $delResult = $this->textService->deleteText($delId);
            $message = "Text deleted: {$delResult['sentences']} sentences, {$delResult['textItems']} text items";
        } elseif ($archId !== null) {
            $archResult = $this->textService->archiveText($archId);
            $message = "Text archived: {$archResult['sentences']} sentences, {$archResult['textItems']} text items";
        } elseif ($op !== '') {
            $result = $this->handleTextOperation(
                $op,
                $noPagestart,
                $currentLang
            );
            if ($result instanceof RedirectResponse) {
                return $result;
            }
            $message .= ($message ? " / " : "") . $result['message'];
            if ($result['redirect']) {
                return null;
            }
        }

        // Display appropriate page
        if ($this->hasParam('chg')) {
            $this->showEditTextForm($this->paramInt('chg', 0) ?? 0);
        } else {
            $this->showTextsList($currentLang, $message);
        }

        PageLayoutHelper::renderPageEnd();

        return null;
    }

    /**
     * Handle mark actions for multiple texts.
     *
     * @param string $markAction Action to perform
     * @param array  $marked     Array of marked text IDs
     * @param string $actionData Additional data for the action
     *
     * @return string|RedirectResponse Result message or redirect
     */
    private function handleMarkAction(
        string $markAction,
        array $marked,
        string $actionData
    ): string|RedirectResponse {
        $message = "Multiple Actions: 0";

        if (count($marked) === 0) {
            return $message;
        }

        $list = "(" . implode(",", array_map('intval', $marked)) . ")";

        switch ($markAction) {
            case 'del':
                $result = $this->textService->deleteTexts($marked);
                $message = "Texts deleted: {$result['count']}";
                break;

            case 'arch':
                $result = $this->textService->archiveTexts($marked);
                $message = "Archived Text(s): {$result['count']}";
                break;

            case 'addtag':
                $result = TagsFacade::addTagToTexts($actionData, $list);
                $message = $result['error'] ?? "Tag added in {$result['count']} Texts";
                break;

            case 'deltag':
                TagsFacade::removeTagFromTexts($actionData, $list);
                return $this->redirect('/texts');

            case 'setsent':
                $count = $this->textService->setTermSentences($marked, false);
                $message = "Term sentences set: {$count}";
                break;

            case 'setactsent':
                $count = $this->textService->setTermSentences($marked, true);
                $message = "Active term sentences set: {$count}";
                break;

            case 'rebuild':
                $count = $this->textService->rebuildTexts($marked);
                $message = "Rebuilt Text(s): {$count}";
                break;

            case 'review':
                $sessionManager = new SessionStateManager();
                $sessionManager->saveCriteria('texts', array_map('intval', $marked));
                return $this->redirect('/review?selection=3');
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
     * @return array{message: string, redirect: bool}|RedirectResponse
     */
    private function handleTextOperation(
        string $op,
        bool $noPagestart,
        string|int $currentLang
    ): array|RedirectResponse {
        $txText = $this->param('TxText');
        $txLgId = $this->paramInt('TxLgID', 0) ?? 0;
        $txTitle = $this->param('TxTitle');
        $txAudioUri = $this->param('TxAudioURI');
        $txSourceUri = $this->param('TxSourceURI');

        // Check for uploaded subtitle file (.srt, .vtt) - server-side fallback
        $importFile = InputValidator::getUploadedFile('importFile');
        if ($importFile !== null) {
            $extension = strtolower(pathinfo($importFile['name'], PATHINFO_EXTENSION));
            // Only handle subtitle files here; EPUB files are handled by /book/import
            if ($extension === 'srt' || $extension === 'vtt') {
                $subtitleService = new \Lwt\Modules\Text\Application\Services\SubtitleParserService();
                $fileContent = file_get_contents($importFile['tmp_name']);
                if ($fileContent !== false) {
                    $format = $subtitleService->detectFormat($importFile['name'], $fileContent);
                    if ($format !== null) {
                        $parseResult = $subtitleService->parse($fileContent, $format);
                        if ($parseResult['success']) {
                            $txText = $parseResult['text'];
                            // Auto-set title from filename if empty
                            if ($txTitle === '') {
                                $txTitle = pathinfo($importFile['name'], PATHINFO_FILENAME);
                            }
                        }
                    }
                }
            }
        }

        // Check if text needs auto-splitting (> 60KB)
        $needsAutoSplit = false;
        try {
            $bookFacade = Container::getInstance()->getTyped(
                \Lwt\Modules\Book\Application\BookFacade::class
            );
            $needsAutoSplit = $bookFacade->needsSplit($txText);
        } catch (\Throwable $e) {
            // Book module not available, fall back to length validation
            $needsAutoSplit = false;
        }

        // If text is too long and we can't auto-split, reject it
        if (!$needsAutoSplit && !$this->textService->validateTextLength($txText)) {
            $message = "Error: Text too long, must be below 65000 Bytes";
            if ($noPagestart) {
                $pageName = $this->languageService->getLanguageName($currentLang) . ' Texts';
                PageLayoutHelper::renderPageStart($pageName, true);
            }
            return ['message' => $message, 'redirect' => false];
        }

        if ($op == 'Check') {
            // Check text only - render and indicate page was handled
            echo '<p><input type="button" value="&lt;&lt; Back" data-action="history-back" /></p>';
            $this->textService->checkText(
                StringUtils::removeSoftHyphens($txText),
                $txLgId
            );
            echo '<p><input type="button" value="&lt;&lt; Back" data-action="history-back" /></p>';
            PageLayoutHelper::renderPageEnd();
            return ['message' => '', 'redirect' => true];
        }

        $textId = $this->paramInt('TxID', 0) ?? 0;
        $isNew = str_starts_with($op, 'Save');

        // Auto-split long texts into a book (only for new texts)
        if ($needsAutoSplit && $isNew) {
            return $this->handleAutoSplitImport(
                $txLgId,
                $txTitle,
                $txText,
                $txAudioUri,
                $txSourceUri,
                str_ends_with($op, "and Open")
            );
        }

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
            return $this->redirect('/text/read?start=' . $result['textId']);
        }

        return ['message' => $result['message'], 'redirect' => false];
    }

    /**
     * Handle auto-split import for long texts.
     *
     * Creates a book with chapters for texts that exceed 60KB.
     *
     * @param int    $languageId Language ID
     * @param string $title      Text title
     * @param string $text       Text content
     * @param string $audioUri   Audio URI
     * @param string $sourceUri  Source URI
     * @param bool   $openAfter  Whether to open the first chapter after import
     *
     * @return array{message: string, redirect: bool}|RedirectResponse
     */
    private function handleAutoSplitImport(
        int $languageId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri,
        bool $openAfter
    ): array|RedirectResponse {
        try {
            $bookFacade = Container::getInstance()->getTyped(
                \Lwt\Modules\Book\Application\BookFacade::class
            );

            // Get tag IDs from request
            $tagIds = [];
            $tagInput = $this->param('TextTags');
            if ($tagInput !== null && $tagInput !== '') {
                $tagIds = array_map('intval', explode(',', $tagInput));
                $tagIds = array_filter($tagIds, fn($id) => $id > 0);
            }

            // Get user ID for multi-user mode
            $userId = Globals::getCurrentUserId();

            // Create book from text
            $result = $bookFacade->createBookFromText(
                $languageId,
                $title,
                $text,
                null, // No author for text imports
                $audioUri,
                $sourceUri,
                $tagIds,
                $userId
            );

            if (!$result['success']) {
                return ['message' => 'Error: ' . $result['message'], 'redirect' => false];
            }

            // Redirect to book or first chapter
            if ($openAfter && isset($result['textIds']) && count($result['textIds']) > 0) {
                return $this->redirect('/text/read?start=' . $result['textIds'][0]);
            }

            // Redirect to book page
            if ($result['bookId'] !== null) {
                return $this->redirect('/book/' . $result['bookId']);
            }

            return ['message' => $result['message'], 'redirect' => true];
        } catch (\Throwable $e) {
            return ['message' => 'Error creating book: ' . $e->getMessage(), 'redirect' => false];
        }
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

        include self::MODULE_VIEWS . '/edit_form.php';
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
        $scrdir = $this->languageService->getScriptDirectionTag((int)$text->lgid);

        include self::MODULE_VIEWS . '/edit_form.php';
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

        include self::MODULE_VIEWS . '/edit_list.php';
    }

    /**
     * Display improved text (replaces text_display.php)
     *
     * Routes:
     * - GET /text/{text:int}/display (new RESTful route)
     * - GET /text/display?text=[id] (legacy route)
     *
     * @param int|null $text Text ID (injected from route parameter)
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function display(?int $text = null): ?RedirectResponse
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        include_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        include_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        include_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        include_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';
        include_once dirname(__DIR__, 2) . '/Admin/Application/Services/MediaService.php';

        // Support both new route param injection and legacy query param
        $textId = $text ?? $this->paramInt('text', 0) ?? 0;

        if ($textId === 0) {
            return $this->redirect('/text/edit');
        }

        // Get annotated text
        $annotatedText = $this->displayService->getAnnotatedText($textId);
        if (strlen($annotatedText) <= 0) {
            return $this->redirect('/text/edit');
        }

        // Get display settings
        $settings = $this->displayService->getTextDisplaySettings($textId);
        if ($settings === null) {
            return $this->redirect('/text/edit');
        }

        // Get header data
        $headerData = $this->displayService->getHeaderData($textId);
        if ($headerData === null) {
            return $this->redirect('/text/edit');
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
        include self::MODULE_VIEWS . '/display_main.php';
        PageLayoutHelper::renderPageEnd();

        return null;
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
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';

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

        include self::MODULE_VIEWS . '/set_mode_result.php';

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
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';

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

            include self::MODULE_VIEWS . '/check_form.php';
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Archived texts management (replaces text_archived.php)
     *
     * @param array $params Route parameters
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function archived(array $params): ?RedirectResponse
    {
        include_once self::BACKEND_PATH . '/Core/Bootstrap/db_bootstrap.php';
        include_once dirname(__DIR__) . '/Application/Services/TextStatisticsService.php';
        include_once dirname(__DIR__, 2) . '/Text/Application/Services/SentenceService.php';
        include_once dirname(__DIR__) . '/Application/Services/AnnotationService.php';
        include_once dirname(__DIR__) . '/Application/Services/TextNavigationService.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/UseCases/FindSimilarTerms.php';
        include_once dirname(__DIR__, 2) . '/Vocabulary/Application/Services/ExpressionService.php';
        include_once __DIR__ . '/../../../Shared/Infrastructure/Database/Restore.php';

        // Handle mark actions that skip pagestart
        $markAction = $this->param('markaction');
        $noPagestart = ($markAction == 'deltag');
        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart('Archived Texts', true);
        }

        $message = '';

        // Handle mark actions
        if ($markAction !== '') {
            $result = $this->handleArchivedMarkAction(
                $markAction,
                $this->paramArray('marked'),
                $this->param('data')
            );
            if ($result instanceof RedirectResponse) {
                return $result;
            }
            $message = $result;
        }

        // Handle single item actions
        $delId = $this->paramInt('del');
        $unarchId = $this->paramInt('unarch');
        $op = $this->param('op');
        if ($delId !== null) {
            $message = $this->textService->deleteArchivedText($delId);
        } elseif ($unarchId !== null) {
            $result = $this->textService->unarchiveText($unarchId);
            if ($result['success'] ?? false) {
                $message = "Text unarchived: {$result['sentences']} sentences, {$result['textItems']} text items";
            } else {
                $message = $result['error'] ?? 'Failed to unarchive text';
            }
        } elseif ($op == 'Change') {
            $txId = $this->paramInt('TxID', 0) ?? 0;
            $affected = $this->textService->updateArchivedText(
                $txId,
                $this->paramInt('TxLgID', 0) ?? 0,
                $this->param('TxTitle'),
                $this->param('TxText'),
                $this->param('TxAudioURI'),
                $this->param('TxSourceURI')
            );
            $message = "Updated: {$affected}";
            TagsFacade::saveArchivedTextTagsFromForm($txId);
        }

        // Display edit form or list
        $chgId = $this->paramInt('chg');
        if ($chgId !== null) {
            $textId = $chgId;
            $record = $this->textService->getArchivedTextById($textId);
            if ($record !== null) {
                $languages = $this->languageService->getLanguagesForSelect();
                include self::MODULE_VIEWS . '/archived_form.php';
            }
        } else {
            $activeLanguageId = (int) Settings::get('currentlanguage');
            include self::MODULE_VIEWS . '/archived_list.php';
        }

        PageLayoutHelper::renderPageEnd();

        return null;
    }

    /**
     * Handle mark actions for archived texts.
     *
     * @param string $markAction Action to perform
     * @param array  $marked     Array of marked text IDs
     * @param string $actionData Additional data for the action
     *
     * @return string|RedirectResponse Result message or redirect
     */
    private function handleArchivedMarkAction(
        string $markAction,
        array $marked,
        string $actionData
    ): string|RedirectResponse {
        $message = "Multiple Actions: 0";

        if (count($marked) === 0) {
            return $message;
        }

        switch ($markAction) {
            case 'del':
                $result = $this->textService->deleteArchivedTexts($marked);
                $message = "Archived Texts deleted: {$result['count']}";
                break;

            case 'addtag':
                $list = "(" . implode(",", array_map('intval', $marked)) . ")";
                $result = TagsFacade::addTagToArchivedTexts($actionData, $list);
                $message = $result['error'] ?? "Tag added in {$result['count']} Texts";
                break;

            case 'deltag':
                $list = "(" . implode(",", array_map('intval', $marked)) . ")";
                TagsFacade::removeTagFromArchivedTexts($actionData, $list);
                return $this->redirect('/text/archived');

            case 'unarch':
                $result = $this->textService->unarchiveTexts($marked);
                $message = "Unarchived Text(s): {$result['count']}";
                break;
        }

        return $message;
    }
}
