<?php declare(strict_types=1);
/**
 * Admin Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Http;

use Lwt\Core\Http\InputValidator;
use Lwt\Modules\Admin\Application\AdminFacade;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;
use Lwt\Services\TtsService;

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
 *
 * @since 3.0.0
 */
class AdminController
{
    /**
     * View base path.
     */
    private string $viewPath;

    /**
     * Constructor.
     *
     * @param AdminFacade $adminFacade Admin facade
     * @param TtsService  $ttsService  TTS service (shared)
     */
    public function __construct(
        private AdminFacade $adminFacade,
        private TtsService $ttsService
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
    }

    /**
     * Get the AdminFacade instance.
     *
     * @return AdminFacade
     */
    public function getFacade(): AdminFacade
    {
        return $this->adminFacade;
    }

    /**
     * Set custom view path.
     *
     * @param string $path View path
     *
     * @return void
     */
    public function setViewPath(string $path): void
    {
        $this->viewPath = rtrim($path, '/') . '/';
    }

    // =========================================================================
    // Backup Operations
    // =========================================================================

    /**
     * Handle backup page operations and get view data.
     *
     * @param bool  $hasRestore     Has restore param
     * @param bool  $hasBackup      Has backup param
     * @param bool  $hasOrigBackup  Has orig_backup param
     * @param bool  $hasEmpty       Has empty param
     * @param array $files          $_FILES data
     *
     * @return array{message: string, prefinfo: string}
     */
    public function handleBackup(
        bool $hasRestore,
        bool $hasBackup,
        bool $hasOrigBackup,
        bool $hasEmpty,
        array $files
    ): array {
        $message = '';

        if ($hasRestore) {
            $message = $this->adminFacade->restoreFromUpload($files);
        } elseif ($hasBackup) {
            $this->adminFacade->downloadBackup();
            // downloadBackup exits, so we never reach here
        } elseif ($hasOrigBackup) {
            $this->adminFacade->downloadOfficialBackup();
            // downloadOfficialBackup exits, so we never reach here
        } elseif ($hasEmpty) {
            $message = $this->adminFacade->emptyDatabase();
        }

        return [
            'message' => $message,
            'prefinfo' => $this->adminFacade->getPrefixInfo(),
        ];
    }

    /**
     * Get database name for backup page.
     *
     * @return string Database name
     */
    public function getDatabaseName(): string
    {
        return $this->adminFacade->getDatabaseName();
    }

    // =========================================================================
    // Wizard Operations
    // =========================================================================

    /**
     * Handle wizard page operations.
     *
     * @param string $operation Operation (Autocomplete, Check, Change)
     * @param array  $formData  Form data
     *
     * @return array{conn: mixed, errorMessage: string|null}
     */
    public function handleWizard(string $operation, array $formData): array
    {
        $conn = null;
        $errorMessage = null;

        if ($operation === 'Autocomplete') {
            $conn = $this->adminFacade->autocompleteConnection();
        } elseif ($operation === 'Check') {
            $conn = $this->adminFacade->createConnectionFromForm($formData);
            $errorMessage = $this->adminFacade->testConnection($conn);
        } elseif ($operation === 'Change') {
            $conn = $this->adminFacade->createConnectionFromForm($formData);
            $this->adminFacade->saveConnectionToEnv($conn);
            // Controller should handle redirect
        } elseif ($this->adminFacade->envFileExists()) {
            $conn = $this->adminFacade->loadConnection();
        } else {
            $conn = $this->adminFacade->createEmptyConnection();
        }

        return [
            'conn' => $conn,
            'errorMessage' => $errorMessage,
        ];
    }

    /**
     * Check if .env file exists.
     *
     * @return bool True if exists
     */
    public function envFileExists(): bool
    {
        return $this->adminFacade->envFileExists();
    }

    /**
     * Get .env file path.
     *
     * @return string Path to .env
     */
    public function getEnvPath(): string
    {
        return $this->adminFacade->getEnvPath();
    }

    // =========================================================================
    // Statistics Operations
    // =========================================================================

    /**
     * Get statistics data for the page.
     *
     * @return array{intensityStats: array, frequencyStats: array}
     */
    public function getStatistics(): array
    {
        return [
            'intensityStats' => $this->adminFacade->getIntensityStatistics(),
            'frequencyStats' => $this->adminFacade->getFrequencyStatistics(),
        ];
    }

    // =========================================================================
    // Settings Operations
    // =========================================================================

    /**
     * Handle settings page operations.
     *
     * @param string $operation Operation (Save or Reset)
     *
     * @return string Status message
     */
    public function handleSettingsOperation(string $operation): string
    {
        if ($operation === 'Save') {
            return $this->adminFacade->saveAllSettings();
        } elseif ($operation !== '') {
            return $this->adminFacade->resetAllSettings();
        }

        return '';
    }

    /**
     * Get settings form data.
     *
     * @return array Form data
     */
    public function getSettingsFormData(): array
    {
        return [
            'settings' => $this->adminFacade->getAllSettings(),
            'themes' => $this->adminFacade->getAvailableThemes(),
            'languageOptions' => $this->ttsService->getLanguageOptions(LanguagePresets::getAll()),
            'currentLanguageCode' => json_encode(
                $this->ttsService->getCurrentLanguageCode(LanguagePresets::getAll())
            ),
        ];
    }

    /**
     * Save a single setting and optionally clear session.
     *
     * @param string $key   Setting key
     * @param string $value Setting value
     *
     * @return void
     */
    public function saveSetting(string $key, string $value): void
    {
        if ($key !== '') {
            $this->adminFacade->saveAndClearSession($key, $value);
        }
    }

    // =========================================================================
    // Demo Operations
    // =========================================================================

    /**
     * Handle install demo page operations.
     *
     * @param bool $hasInstall Has install param
     *
     * @return array{message: string, prefinfo: string, langcnt: int}
     */
    public function handleInstallDemo(bool $hasInstall): array
    {
        $message = '';

        if ($hasInstall) {
            $message = $this->adminFacade->installDemo();
        }

        return [
            'message' => $message,
            'prefinfo' => $this->adminFacade->getPrefixInfo(),
            'langcnt' => $this->adminFacade->getLanguageCount(),
        ];
    }

    // =========================================================================
    // Server Data Operations
    // =========================================================================

    /**
     * Get server data.
     *
     * @return array Server information
     */
    public function getServerData(): array
    {
        return $this->adminFacade->getServerData();
    }

    // =========================================================================
    // View Rendering
    // =========================================================================

    /**
     * Render a view.
     *
     * @param string $view View name (without .php)
     * @param array  $data View data
     *
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract($data);
        require $viewFile;
    }
}
