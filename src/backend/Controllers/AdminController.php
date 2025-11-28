<?php

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

use Lwt\Classes\GoogleTranslate;
use Lwt\Core\Http\InputValidator;
use Lwt\Services\BackupService;
use Lwt\Services\DemoService;
use Lwt\Services\ServerDataService;
use Lwt\Services\SettingsService;
use Lwt\Services\StatisticsService;
use Lwt\Services\TableSetService;
use Lwt\Services\TtsService;
use Lwt\Services\WordService;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../Core/UI/ui_helpers.php';
require_once __DIR__ . '/../Core/Http/param_helpers.php';
require_once __DIR__ . '/../Core/Text/text_helpers.php';
require_once __DIR__ . '/../Core/Media/media_helpers.php';
require_once __DIR__ . '/../Core/Language/language_utilities.php';
require_once __DIR__ . '/../Core/Language/langdefs.php';
require_once __DIR__ . '/../Core/Entity/GoogleTranslate.php';
require_once __DIR__ . '/../Services/BackupService.php';
require_once __DIR__ . '/../Services/DemoService.php';
require_once __DIR__ . '/../Services/ServerDataService.php';
require_once __DIR__ . '/../Services/SettingsService.php';
require_once __DIR__ . '/../Services/StatisticsService.php';
require_once __DIR__ . '/../Services/TableSetService.php';
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
 * - Table management
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
        $backupService = new BackupService();
        $message = '';

        // Handle operations
        if ($this->hasParam('restore')) {
            $message = $backupService->restoreFromUpload($_FILES);
        } elseif ($this->hasParam('backup')) {
            $backupService->downloadBackup();
            // downloadBackup exits, so we never reach here
        } elseif ($this->hasParam('orig_backup')) {
            $backupService->downloadOfficialBackup();
            // downloadOfficialBackup exits, so we never reach here
        } elseif ($this->hasParam('empty')) {
            $message = $backupService->emptyDatabase();
        }

        // Get view data (used by included view)
        /** @psalm-suppress UnusedVariable */
        $prefinfo = $backupService->getPrefixInfo();
        /** @psalm-suppress UnusedVariable */
        $dbname = $backupService->getDatabaseName();

        // Render page
        $this->render('Backup/Restore/Empty Database', true);
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
        $statisticsService = new StatisticsService();
        /** @psalm-suppress UnusedVariable - Used by included view */
        $intensityStats = $statisticsService->getIntensityStatistics();
        /** @psalm-suppress UnusedVariable - Used by included view */
        $frequencyStats = $statisticsService->getFrequencyStatistics();

        // Render page
        $this->render('My Statistics', true);

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
        $settingsService = new SettingsService();
        $message = '';

        // Handle form submission
        $op = $this->param('op');
        if ($op !== '') {
            if ($op === 'Save') {
                // Settings are saved via $settingsService which reads from $_REQUEST
                $message = $settingsService->saveAll();
            } else {
                $message = $settingsService->resetAll();
            }
        }

        // Load current settings for the form (used by included view)
        /** @psalm-suppress UnusedVariable */
        $settings = $settingsService->getAll();

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
        $wordService = new WordService();

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
        $result = $wordService->createOnHover($textId, $text, $status, $translation);

        // Render page
        \pagestart("New Term: " . $result['word'], false);

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

        \pageend();
    }

    /**
     * TTS settings page
     *
     * Handles:
     * - op=Save: Save TTS settings
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function settingsTts(array $params): void
    {
        $ttsService = new TtsService();
        $message = '';

        // Handle save request
        if ($this->param('op') === 'Save') {
            // TTS settings service reads from InputValidator
            $ttsService->saveSettings();
            $message = "Settings saved!";
        }

        // Get view data (used by included view)
        /** @psalm-suppress UnusedVariable */
        $languageOptions = $ttsService->getLanguageOptions(LWT_LANGUAGES_ARRAY);
        /** @psalm-suppress UnusedVariable */
        $currentLanguageCode = json_encode(
            $ttsService->getCurrentLanguageCode(LWT_LANGUAGES_ARRAY)
        );

        // Render page
        $this->render('Text-to-Speech Settings', true);

        if ($message != '') {
            $this->message($message, false);
        }

        include __DIR__ . '/../Views/Admin/tts_settings.php';

        $this->endRender();
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
        $demoService = new DemoService();
        $message = '';

        // Handle install request
        if ($this->hasParam('install')) {
            $message = $demoService->installDemo();
        }

        // Get view data (used by included view)
        /** @psalm-suppress UnusedVariable */
        $prefinfo = $demoService->getPrefixInfo();
        /** @psalm-suppress UnusedVariable */
        $dbname = $demoService->getDatabaseName();
        /** @psalm-suppress UnusedVariable */
        $langcnt = $demoService->getLanguageCount();

        // Render page
        $this->render('Install LWT Demo Database', true);
        $this->message($message, true);

        include __DIR__ . '/../Views/Admin/install_demo.php';

        $this->endRender();
    }

    /**
     * Table management page
     *
     * Handles:
     * - delpref=xxx: Delete a table set
     * - newpref=xxx: Create a new table set
     * - prefix=xxx: Select a table set
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function tables(array $params): void
    {
        $tableSetService = new TableSetService();
        $message = "";

        // Handle operations
        if ($this->hasParam('delpref')) {
            $message = $tableSetService->deleteTableSet($this->param('delpref'));
        } elseif ($this->hasParam('newpref')) {
            $result = $tableSetService->createTableSet($this->param('newpref'));
            if ($result['redirect']) {
                $this->redirect('/');
            }
            $message = $result['message'];
        } elseif ($this->hasParam('prefix')) {
            $result = $tableSetService->selectTableSet($this->param('prefix'));
            if ($result['redirect']) {
                $this->redirect('/');
            }
        }

        // Get view data (used by included view)
        /** @psalm-suppress UnusedVariable */
        $fixedTbpref = $tableSetService->isFixedPrefix();
        /** @psalm-suppress UnusedVariable */
        $tbpref = $tableSetService->getCurrentPrefix();
        /** @psalm-suppress UnusedVariable */
        $prefixes = $tableSetService->getPrefixes();

        // Render page
        $this->render('Select, Create or Delete a Table Set', false);
        $this->message($message, false);

        include __DIR__ . '/../Views/Admin/table_management.php';

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
        $serverDataService = new ServerDataService();
        /** @psalm-suppress UnusedVariable - Used by included view */
        $data = $serverDataService->getServerData();

        // Render page
        $this->render("Server Data", true);

        include __DIR__ . '/../Views/Admin/server_data.php';

        $this->endRender();
    }
}
