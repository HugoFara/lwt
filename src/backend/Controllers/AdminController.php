<?php declare(strict_types=1);
/**
 * \file
 * \brief Admin Controller - Administrative functions
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-admincontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Entity\GoogleTranslate;
use Lwt\Core\Globals;
use Lwt\Core\Http\InputValidator;
use Lwt\Services\BackupService;
use Lwt\Services\DemoService;
use Lwt\Services\ServerDataService;
use Lwt\Services\SettingsService;
use Lwt\Services\StatisticsService;
use Lwt\Services\ThemeService;
use Lwt\Services\TtsService;
use Lwt\Services\WordService;
use Lwt\Services\LanguageDefinitions;

use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../Services/TextStatisticsService.php';
require_once __DIR__ . '/../Services/SentenceService.php';
require_once __DIR__ . '/../Services/AnnotationService.php';
require_once __DIR__ . '/../Services/SimilarTermsService.php';
require_once __DIR__ . '/../Services/TextNavigationService.php';
require_once __DIR__ . '/../Services/TextParsingService.php';
require_once __DIR__ . '/../Services/ExpressionService.php';
require_once __DIR__ . '/../Core/Database/Restore.php';
require_once __DIR__ . '/../Services/MediaService.php';
require_once __DIR__ . '/../Services/LanguageService.php';
require_once __DIR__ . '/../Core/Entity/GoogleTranslate.php';
require_once __DIR__ . '/../Services/LanguageDefinitions.php';
require_once __DIR__ . '/../Services/BackupService.php';
require_once __DIR__ . '/../Services/DemoService.php';
require_once __DIR__ . '/../Services/ServerDataService.php';
require_once __DIR__ . '/../Services/SettingsService.php';
require_once __DIR__ . '/../Services/StatisticsService.php';
require_once __DIR__ . '/../Services/TtsService.php';
require_once __DIR__ . '/../Services/WordService.php';

/**
 * Controller for administrative functions.
 *
 * Handles:
 * - Backup and restore
 * - Database wizard
 * - Statistics
 * - Settings
 * - Install demo
 * - Server data
 * - TTS settings
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class AdminController extends BaseController
{
    private BackupService $backupService;
    private StatisticsService $statisticsService;
    private SettingsService $settingsService;
    private TtsService $ttsService;
    private WordService $wordService;
    private DemoService $demoService;
    private ServerDataService $serverDataService;
    private ThemeService $themeService;

    /**
     * Constructor - initialize dependencies.
     *
     * @param BackupService|null      $backupService      Backup service
     * @param StatisticsService|null  $statisticsService  Statistics service
     * @param SettingsService|null    $settingsService    Settings service
     * @param TtsService|null         $ttsService         TTS service
     * @param WordService|null        $wordService        Word service
     * @param DemoService|null        $demoService        Demo service
     * @param ServerDataService|null  $serverDataService  Server data service
     * @param ThemeService|null       $themeService       Theme service
     */
    public function __construct(
        ?BackupService $backupService = null,
        ?StatisticsService $statisticsService = null,
        ?SettingsService $settingsService = null,
        ?TtsService $ttsService = null,
        ?WordService $wordService = null,
        ?DemoService $demoService = null,
        ?ServerDataService $serverDataService = null,
        ?ThemeService $themeService = null
    ) {
        parent::__construct();
        $this->backupService = $backupService ?? new BackupService();
        $this->statisticsService = $statisticsService ?? new StatisticsService();
        $this->settingsService = $settingsService ?? new SettingsService();
        $this->ttsService = $ttsService ?? new TtsService();
        $this->wordService = $wordService ?? new WordService();
        $this->demoService = $demoService ?? new DemoService();
        $this->serverDataService = $serverDataService ?? new ServerDataService();
        $this->themeService = $themeService ?? new ThemeService();
    }

    /**
     * Backup and restore page
     *
     * Handles:
     * - restore=xxx: Restore from uploaded file
     * - backup=xxx: Download backup
     * - orig_backup=xxx: Download official format backup
     * - empty=xxx: Empty the database
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function backup(array $params): void
    {
        $message = '';

        // Handle operations
        if ($this->hasParam('restore')) {
            $message = $this->backupService->restoreFromUpload($_FILES);
        } elseif ($this->hasParam('backup')) {
            $this->backupService->downloadBackup();
            // downloadBackup exits, so we never reach here
        } elseif ($this->hasParam('orig_backup')) {
            $this->backupService->downloadOfficialBackup();
            // downloadOfficialBackup exits, so we never reach here
        } elseif ($this->hasParam('empty')) {
            $message = $this->backupService->emptyDatabase();
        }

        // Get view data (used by included view)
        /** @psalm-suppress UnusedVariable */
        $prefinfo = $this->backupService->getPrefixInfo();

        // Render page
        $this->render('Database Operations', true);
        $this->message($message, true);

        include __DIR__ . '/../Views/Admin/backup.php';

        $this->endRender();
    }

    /**
     * Database wizard page
     *
     * The wizard is a standalone page that can run without database connection.
     * It uses its own self-contained HTML output.
     *
     * Handles:
     * - op=Autocomplete: Auto-detect connection settings
     * - op=Check: Test connection with provided settings
     * - op=Change: Save new connection settings
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function wizard(array $params): void
    {
        // Load service (no database required)
        require_once __DIR__ . '/../Services/DatabaseWizardService.php';
        $wizardService = new \Lwt\Services\DatabaseWizardService();

        /** @psalm-suppress UnusedVariable - Used by included view */
        $conn = null;

        /** @psalm-suppress UnusedVariable - Used by included view */
        $errorMessage = null;

        // Handle operations
        $op = $this->param('op');
        if ($op !== '') {
            if ($op === "Autocomplete") {
                $conn = $wizardService->autocompleteConnection();
            } elseif ($op === "Check") {
                $formData = InputValidator::getMany([
                    'hostname' => 'string',
                    'login' => 'string',
                    'password' => 'string',
                    'dbname' => 'string',
                    'tbpref' => 'string',
                ]);
                $conn = $wizardService->createConnectionFromForm($formData);
                $errorMessage = $wizardService->testConnection($conn);
            } elseif ($op === "Change") {
                $formData = InputValidator::getMany([
                    'hostname' => 'string',
                    'login' => 'string',
                    'password' => 'string',
                    'dbname' => 'string',
                    'tbpref' => 'string',
                ]);
                $conn = $wizardService->createConnectionFromForm($formData);
                $wizardService->saveConnection($conn);
                // Redirect to home after saving
                $this->redirect('/');
            }
        } elseif ($wizardService->envFileExists()) {
            $conn = $wizardService->loadConnection();
        } else {
            $conn = $wizardService->createEmptyConnection();
        }

        // The wizard view is standalone (includes its own HTML structure)
        include __DIR__ . '/../Views/Admin/wizard.php';
    }

    /**
     * Statistics page
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function statistics(array $params): void
    {
        /** @psalm-suppress UnusedVariable - Used by included view */
        $intensityStats = $this->statisticsService->getIntensityStatistics();
        /** @psalm-suppress UnusedVariable - Used by included view */
        $frequencyStats = $this->statisticsService->getFrequencyStatistics();

        // Render page
        $this->render('Statistics', true);

        include __DIR__ . '/../Views/Admin/statistics.php';

        $this->endRender();
    }

    /**
     * Settings page
     *
     * Handles:
     * - op=Save: Save all settings
     * - op=Reset: Reset to defaults
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function settings(array $params): void
    {
        $message = '';

        // Handle form submission
        $op = $this->param('op');
        if ($op !== '') {
            if ($op === 'Save') {
                // Settings are saved via settingsService which reads from $_REQUEST
                $message = $this->settingsService->saveAll();
            } else {
                $message = $this->settingsService->resetAll();
            }
        }

        // Load current settings for the form (used by included view)
        /** @psalm-suppress UnusedVariable */
        $settings = $this->settingsService->getAll();

        // Get available themes for the dropdown (used by included view)
        /** @psalm-suppress UnusedVariable */
        $themes = $this->themeService->getAvailableThemes();

        // Get TTS data for the form (used by included view)
        /** @psalm-suppress UnusedVariable */
        $languageOptions = $this->ttsService->getLanguageOptions(LanguageDefinitions::getAll());
        /** @psalm-suppress UnusedVariable */
        $currentLanguageCode = json_encode(
            $this->ttsService->getCurrentLanguageCode(LanguageDefinitions::getAll())
        );

        // Render page
        $this->render('Settings/Preferences', true);
        $this->message($message, true);

        include __DIR__ . '/../Views/Admin/settings_form.php';

        $this->endRender();
    }

    /**
     * Hover settings page - creates a word with status from text reading hover action.
     *
     * This is a helper frame that creates a word when user clicks a status
     * from the hover menu while reading a text.
     *
     * Required GET parameters:
     * - text: Word text
     * - tid: Text ID
     * - status: Word status (1-5)
     *
     * Optional GET parameters (for translation):
     * - tl: Target language code
     * - sl: Source language code
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function settingsHover(array $params): void
    {
        $text = $this->param('text');
        $textId = $this->paramInt('tid', 0) ?? 0;
        $status = $this->paramInt('status', 1) ?? 1;

        // Get translation if status is 1 (new word)
        $translation = '*';
        if ($status === 1) {
            $tl = $this->get('tl');
            $sl = $this->get('sl');

            if ($tl !== '' && $sl !== '') {
                $tl_array = GoogleTranslate::staticTranslate($text, $sl, $tl);
                if ($tl_array) {
                    $translation = $tl_array[0];
                }
                if ($translation === $text) {
                    $translation = '*';
                }
            }

            header('Pragma: no-cache');
            header('Expires: 0');
        }

        // Create the word
        $result = $this->wordService->createOnHover($textId, $text, $status, $translation);

        // Render page
        PageLayoutHelper::renderPageStart("New Term: " . $result['word'], false);

        // Prepare view variables (used by included view)
        /** @psalm-suppress UnusedVariable */
        $word = $result['word'];
        /** @psalm-suppress UnusedVariable */
        $wordRaw = $result['wordRaw'];
        /** @psalm-suppress UnusedVariable */
        $wid = $result['wid'];
        /** @psalm-suppress UnusedVariable */
        $hex = $result['hex'];
        /** @psalm-suppress UnusedVariable */
        $translation = $result['translation'];
        /** @psalm-suppress UnusedVariable */
        $textId = $textId;
        /** @psalm-suppress UnusedVariable */
        $status = $status;

        include __DIR__ . '/../Views/Word/hover_save_result.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Install demo page
     *
     * Handles:
     * - install=xxx: Install the demo database
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function installDemo(array $params): void
    {
        $message = '';

        // Handle install request
        if ($this->hasParam('install')) {
            $message = $this->demoService->installDemo();
        }

        // Get view data (used by included view)
        /** @psalm-suppress UnusedVariable */
        $prefinfo = $this->demoService->getPrefixInfo();
        /** @psalm-suppress UnusedVariable */
        $langcnt = $this->demoService->getLanguageCount();

        // Render page
        $this->render('Install LWT Demo Database', true);
        $this->message($message, true);

        include __DIR__ . '/../Views/Admin/install_demo.php';

        $this->endRender();
    }

    /**
     * Server data page
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function serverData(array $params): void
    {
        /** @psalm-suppress UnusedVariable - Used by included view */
        $data = $this->serverDataService->getServerData();

        // Render page
        $this->render("Server Data", true);

        include __DIR__ . '/../Views/Admin/server_data.php';

        $this->endRender();
    }

    /**
     * Save a setting and redirect to a URL.
     *
     * This endpoint replaces the legacy save_setting_redirect.php file.
     *
     * GET parameters:
     * - k: Setting key
     * - v: Setting value
     * - u: Redirect URL (optional)
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function saveSetting(array $params): void
    {
        $key = $this->param('k');
        $value = $this->param('v');
        $url = $this->param('u');

        // Save the setting if key is provided
        if ($key !== '') {
            $this->settingsService->saveAndClearSession($key, $value);
        }

        // Redirect if URL is provided
        if ($url !== '') {
            // Check if it's an absolute or relative URL
            $parsedUrl = parse_url($url);
            if (isset($parsedUrl['host'])) {
                // Absolute URL
                header("Location: " . $url);
            } else {
                // Relative URL - redirect to root-relative path
                header("Location: " . $url);
            }
            exit();
        }
    }
}
