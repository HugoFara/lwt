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
use Lwt\Database\Settings;
use Lwt\Database\Validation;

require_once __DIR__ . '/../Services/TextService.php';
require_once __DIR__ . '/../Services/TextDisplayService.php';

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
     * Constructor - initialize services.
     */
    public function __construct()
    {
        parent::__construct();
        $this->textService = new TextService();
    }

    /**
     * Read text interface (replaces text_read.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function read(array $params): void
    {
        include __DIR__ . '/../Legacy/text_read.php';
    }

    /**
     * Edit texts list (replaces text_edit.php)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function edit(array $params): void
    {
        include __DIR__ . '/../Legacy/text_edit.php';
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
        require_once __DIR__ . '/../Core/UI/ui_helpers.php';
        require_once __DIR__ . '/../Core/Text/text_helpers.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';
        require_once __DIR__ . '/../Core/Language/language_utilities.php';
        require_once __DIR__ . '/../Core/Media/media_helpers.php';

        $textId = (int) \getreq('text');

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
        \pagestart_nobody('Display');
        include __DIR__ . '/../Views/Text/display_main.php';
        \pageend();
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
     */
    public function importLong(array $params): void
    {
        include __DIR__ . '/../Legacy/text_import_long.php';
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
        require_once __DIR__ . '/../Core/UI/ui_helpers.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';

        $textId = \getreq('text');
        if ($textId === '') {
            return;
        }

        $showAll = (int) \getreq('mode');
        $showLearning = (int) \getreq('showLearning');

        // Save settings and get the old value
        Settings::save('showallwords', $showAll);
        $oldShowLearning = Settings::getZeroOrOne('showlearningtranslations', 1);
        Settings::save('showlearningtranslations', $showLearning);

        // Display result page
        \pagestart("Text Display Mode changed", false);

        $waitingIconPath = \get_file_path('assets/icons/waiting.gif');
        flush();

        include __DIR__ . '/../Views/Text/set_mode_result.php';

        \pageend();
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
        require_once __DIR__ . '/../Core/UI/ui_helpers.php';
        require_once __DIR__ . '/../Core/Language/language_utilities.php';

        \pagestart('Check a Text', true);

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
            $languagesOption = \get_languages_selectoptions(
                \Lwt\Database\Settings::get('currentlanguage'),
                '[Choose...]'
            );

            include __DIR__ . '/../Views/Text/check_form.php';
        }

        \pageend();
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
        require_once __DIR__ . '/../Core/UI/ui_helpers.php';
        require_once __DIR__ . '/../Core/Tag/tags.php';
        require_once __DIR__ . '/../Core/Text/text_helpers.php';
        require_once __DIR__ . '/../Core/Http/param_helpers.php';
        require_once __DIR__ . '/../Core/Language/language_utilities.php';

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
        $noPagestart = (\getreq('markaction') == 'deltag');
        if (!$noPagestart) {
            \pagestart('My ' . \getLanguage($currentLang) . ' Text Archive', true);
        }

        $message = '';

        // Handle mark actions
        if (isset($_REQUEST['markaction'])) {
            $message = $this->handleArchivedMarkAction(
                $_REQUEST['markaction'],
                $_REQUEST['marked'] ?? [],
                \getreq('data')
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
            \saveArchivedTextTags((int) $_REQUEST['AtID']);
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

        \pageend();
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
                $message = \addarchtexttaglist($actionData, $list);
                break;

            case 'deltag':
                $list = "(" . implode(",", array_map('intval', $marked)) . ")";
                \removearchtexttaglist($actionData, $list);
                header("Location: /text/archived");
                exit();

            case 'unarch':
                $message = $this->textService->unarchiveTexts($marked);
                break;
        }

        return $message;
    }
}
