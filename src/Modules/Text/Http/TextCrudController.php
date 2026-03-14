<?php

/**
 * Text CRUD Controller - Text creation, editing, and list management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Text\Http;

use Lwt\Shared\Http\BaseController;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Http\RedirectResponse;
use Lwt\Shared\Infrastructure\Utilities\StringUtils;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;

/**
 * Controller for text CRUD operations and list management.
 *
 * Handles:
 * - New text form and save
 * - Edit text form and save
 * - Delete/archive/unarchive single texts
 * - Text list with bulk actions
 *
 * @since 3.0.0
 */
class TextCrudController extends BaseController
{
    private const MODULE_VIEWS = __DIR__ . '/../Views';
    private TextFacade $textService;
    private LanguageFacade $languageService;

    public function __construct(
        ?TextFacade $textService = null,
        ?LanguageFacade $languageService = null
    ) {
        parent::__construct();
        $this->textService = $textService ?? new TextFacade();
        $this->languageService = $languageService ?? new LanguageFacade();
    }

    /**
     * Show new text form.
     *
     * @param array $params Route parameters
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function new(array $params): ?RedirectResponse
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

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
            // Ensure page structure exists for error messages
            if ($noPagestart) {
                PageLayoutHelper::renderPageStart('Texts', true);
            }
            if (isset($result['message']) && $result['message'] !== '') {
                echo '<p class="notification is-danger">'
                    . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8')
                    . '</p>';
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
     * Edit single text form.
     *
     * @param int $id Text ID from route parameter
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function editSingle(int $id): ?RedirectResponse
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

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
            if ($noPagestart) {
                PageLayoutHelper::renderPageStart('Texts', true);
            }
            if (isset($result['message']) && $result['message'] !== '') {
                echo '<p class="notification is-danger">'
                    . htmlspecialchars($result['message'], ENT_QUOTES, 'UTF-8')
                    . '</p>';
            }
            PageLayoutHelper::renderPageEnd();
            return null;
        }

        PageLayoutHelper::renderPageStart('Texts', true);
        $this->showEditTextForm($id);
        PageLayoutHelper::renderPageEnd();

        return null;
    }

    /**
     * Delete a text.
     *
     * @param int $id Text ID from route parameter
     *
     * @return RedirectResponse Redirect to texts list
     */
    public function delete(int $id): RedirectResponse
    {
        $this->textService->deleteText($id);
        return $this->redirect('/texts');
    }

    /**
     * Archive a text.
     *
     * @param int $id Text ID from route parameter
     *
     * @return RedirectResponse Redirect to texts list
     */
    public function archive(int $id): RedirectResponse
    {
        $this->textService->archiveText($id);
        return $this->redirect('/texts');
    }

    /**
     * Unarchive a text.
     *
     * @param int $id Text ID from route parameter
     *
     * @return RedirectResponse Redirect to archived texts list
     */
    public function unarchive(int $id): RedirectResponse
    {
        $this->textService->unarchiveText($id);
        return $this->redirect('/text/archived');
    }

    /**
     * Edit texts list.
     *
     * @param array $params Route parameters
     *
     * @return RedirectResponse|null Redirect response or null if rendered
     *
     * @psalm-suppress UnusedVariable Variables are used in included view files
     */
    public function edit(array $params): ?RedirectResponse
    {
        $currentLang = Validation::language(
            InputValidator::getStringWithDb("filterlang", 'currentlanguage')
        );

        $noPagestart = ($this->param('markaction') == 'review' ||
            $this->param('markaction') == 'deltag' ||
            substr($this->param('op'), -8) == 'and Open');

        if (!$noPagestart) {
            PageLayoutHelper::renderPageStart('Texts', true);
        }

        $message = '';

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

        $this->showTextsList($currentLang, $message);

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
    public function handleMarkAction(
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
                $result = $this->textService->deleteTexts($marked);
                $message = "Texts deleted: {$result['count']}";
                break;

            case 'arch':
                $result = $this->textService->archiveTexts($marked);
                $message = "Archived Text(s): {$result['count']}";
                break;

            case 'addtag':
                $result = TagsFacade::addTagToTexts($actionData, $marked);
                $message = $result['error'] ?? "Tag added in {$result['count']} Texts";
                break;

            case 'deltag':
                TagsFacade::removeTagFromTexts($actionData, $marked);
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
    public function handleTextOperation(
        string $op,
        bool $noPagestart,
        string|int $currentLang
    ): array|RedirectResponse {
        $txText = $this->param('TxText');
        $txLgId = $this->paramInt('TxLgID', 0) ?? 0;
        $txTitle = $this->param('TxTitle');
        $txAudioUri = $this->param('TxAudioURI');
        $txSourceUri = $this->param('TxSourceURI');

        $importFile = InputValidator::getUploadedFile('importFile');
        if ($importFile !== null) {
            $extension = strtolower(pathinfo($importFile['name'], PATHINFO_EXTENSION));
            if ($extension === 'srt' || $extension === 'vtt') {
                $subtitleService = new \Lwt\Modules\Text\Application\Services\SubtitleParserService();
                $fileContent = file_get_contents($importFile['tmp_name']);
                if ($fileContent !== false) {
                    $format = $subtitleService->detectFormat($importFile['name'], $fileContent);
                    if ($format !== null) {
                        $parseResult = $subtitleService->parse($fileContent, $format);
                        if ($parseResult['success']) {
                            $txText = $parseResult['text'];
                            if ($txTitle === '') {
                                $txTitle = pathinfo($importFile['name'], PATHINFO_FILENAME);
                            }
                        }
                    }
                }
            }
        }

        $needsAutoSplit = false;
        try {
            $bookFacade = Container::getInstance()->getTyped(
                \Lwt\Modules\Book\Application\BookFacade::class
            );
            $needsAutoSplit = $bookFacade->needsSplit($txText);
        } catch (\Throwable $e) {
            $needsAutoSplit = false;
        }

        if (!$needsAutoSplit && !$this->textService->validateTextLength($txText)) {
            return [
                'message' => "Error: Text too long, must be below 65000 Bytes",
                'redirect' => false,
            ];
        }

        if ($op == 'Check') {
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

        if (str_ends_with($op, "and Open")) {
            return $this->redirect('/text/' . $result['textId'] . '/read');
        }

        return ['message' => $result['message'], 'redirect' => false];
    }

    /**
     * Handle auto-split import for long texts.
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

            $tagIds = [];
            $tagInput = $this->param('TextTags');
            if ($tagInput !== null && $tagInput !== '') {
                $tagIds = array_map('intval', explode(',', $tagInput));
                $tagIds = array_filter($tagIds, fn($id) => $id > 0);
            }

            $userId = Globals::getCurrentUserId();

            $result = $bookFacade->createBookFromText(
                $languageId,
                $title,
                $text,
                null,
                $audioUri,
                $sourceUri,
                $tagIds,
                $userId
            );

            if (!$result['success']) {
                return ['message' => 'Error: ' . $result['message'], 'redirect' => false];
            }

            if ($openAfter && isset($result['textIds']) && count($result['textIds']) > 0) {
                return $this->redirect('/text/' . $result['textIds'][0] . '/read');
            }

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
    public function showNewTextForm(int $langId): void
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

        $mediaService = new \Lwt\Modules\Admin\Application\Services\MediaService();
        $mediaPaths = $mediaService->getMediaPaths();
        $mediaPathSelectorHtml = $mediaService->getMediaPathSelector('TxAudioURI');
        $youtubeConfigured = (new YouTubeApiHandler())->formatIsConfigured()['configured'];
        $textTagsHtml = TagsFacade::getTextTagsHtml($textId);

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
    public function showEditTextForm(int $txid): void
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

        $mediaService = new \Lwt\Modules\Admin\Application\Services\MediaService();
        $mediaPaths = $mediaService->getMediaPaths();
        $mediaPathSelectorHtml = $mediaService->getMediaPathSelector('TxAudioURI');
        $youtubeConfigured = (new YouTubeApiHandler())->formatIsConfigured()['configured'];
        $textTagsHtml = TagsFacade::getTextTagsHtml($textId);

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
    public function showTextsList(string|int $currentLang, string $message): void
    {
        $statuses = \Lwt\Modules\Vocabulary\Application\Services\TermStatusService::getStatuses();
        $statuses[0]["name"] = 'Unknown';
        $statuses[0]["abbr"] = 'Ukn';

        $activeLanguageId = (int) Settings::get('currentlanguage');

        include self::MODULE_VIEWS . '/edit_list.php';
    }
}
