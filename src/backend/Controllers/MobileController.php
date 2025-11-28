<?php

/**
 * \file
 * \brief Mobile Controller - Mobile interface
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-mobilecontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../Services/MobileService.php';

use Lwt\Database\Settings;
use Lwt\Services\MobileService;

/**
 * Controller for mobile interface.
 *
 * Handles:
 * - Mobile index page (language listing and navigation)
 * - Mobile reading interface (texts, sentences, terms)
 * - Mobile start page (table set selection)
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class MobileController extends BaseController
{
    /**
     * Mobile service instance.
     *
     * @var MobileService
     */
    private MobileService $service;

    /**
     * Constructor - initialize service.
     */
    public function __construct()
    {
        parent::__construct();
        $this->service = new MobileService();
    }

    /**
     * Mobile index page - main entry point
     *
     * Handles multiple actions via query parameter:
     * - No action: Show language list (main page)
     * - action=1: Language submenu
     * - action=2: Texts list for a language
     * - action=3: Sentences list for a text
     * - action=4: Terms list for a sentence
     * - action=5: Terms list for next sentence (AJAX replacement)
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        $action = $this->param('action');

        if ($action === null) {
            $this->showMainPage();
            return;
        }

        $action = (int) $action;

        switch ($action) {
            case 1:
                $this->showLanguageMenu();
                break;
            case 2:
                $this->showTextsList();
                break;
            case 3:
                $this->showSentencesList();
                break;
            case 4:
            case 5:
                $this->showTermsList($action);
                break;
            default:
                $this->showMainPage();
        }
    }

    /**
     * Display the main mobile page with language listing.
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable - Variables are used in the included view
     */
    private function showMainPage(): void
    {
        $languages = $this->service->getLanguages();
        $version = $this->service->getVersion();

        include __DIR__ . '/../Views/Mobile/index.php';
    }

    /**
     * Display language submenu (action=1).
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable - Variables are used in the included view
     */
    private function showLanguageMenu(): void
    {
        $langId = (int) $this->param('lang', 0);
        $langName = $this->service->getLanguageName($langId);

        if ($langName === null) {
            echo '<p>Language not found.</p>';
            return;
        }

        $action = 1;

        include __DIR__ . '/../Views/Mobile/language_menu.php';
    }

    /**
     * Display texts list for a language (action=2).
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable - Variables are used in the included view
     */
    private function showTextsList(): void
    {
        $langId = (int) $this->param('lang', 0);
        $langName = $this->service->getLanguageName($langId);

        if ($langName === null) {
            echo '<p>Language not found.</p>';
            return;
        }

        $texts = $this->service->getTextsByLanguage($langId);
        $action = 2;

        include __DIR__ . '/../Views/Mobile/texts_list.php';
    }

    /**
     * Display sentences list for a text (action=3).
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable - Variables are used in the included view
     */
    private function showSentencesList(): void
    {
        $langId = (int) $this->param('lang', 0);
        $textId = (int) $this->param('text', 0);

        $text = $this->service->getTextById($textId);

        if ($text === null) {
            echo '<p>Text not found.</p>';
            return;
        }

        $sentences = $this->service->getSentencesByText($textId);
        $action = 3;

        include __DIR__ . '/../Views/Mobile/sentences_list.php';
    }

    /**
     * Display terms list for a sentence (action=4 or 5).
     *
     * @param int $actionCode Action code (4 or 5)
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable - Variables are used in the included view
     * @psalm-suppress UnusedParam - Parameter is used via assignment to $action
     */
    private function showTermsList(int $actionCode): void
    {
        $langId = (int) $this->param('lang', 0);
        $textId = (int) $this->param('text', 0);
        $sentId = (int) $this->param('sent', 0);

        $sentence = $this->service->getSentenceById($sentId);

        if ($sentence === null) {
            echo '<p>Sentence not found.</p>';
            return;
        }

        $terms = $this->service->getTermsBySentence($sentId);
        $nextSentenceId = $this->service->getNextSentenceId($textId, $sentId);
        $action = $actionCode;

        include __DIR__ . '/../Views/Mobile/terms_list.php';
    }

    /**
     * Mobile start page - table set selection
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function start(array $params): void
    {
        // Handle form submission
        if ($this->isPost() && isset($_REQUEST['prefix']) && $_REQUEST['prefix'] !== '-') {
            $this->savePrefix(\getreq('prefix'));
            $this->redirect('/');
        }

        $this->showTableSetPage();
    }

    /**
     * Save a database prefix.
     *
     * @param string $prefix Database prefix to save
     *
     * @return void
     */
    private function savePrefix(string $prefix): void
    {
        Settings::lwtTableSet("current_table_prefix", $prefix);
    }

    /**
     * Display the table set selection page.
     *
     * @return void
     *
     * @psalm-suppress UnusedVariable - Variables are used in the included view
     */
    private function showTableSetPage(): void
    {
        $currentPrefix = \Lwt\Core\Globals::getTablePrefix();
        $isFixed = \Lwt\Core\Globals::isTablePrefixFixed();
        $prefixes = \getprefixes();

        \pagestart('Select Table Set', false);

        include __DIR__ . '/../Views/Mobile/table_set.php';

        \pageend();
    }
}
