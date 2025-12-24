<?php declare(strict_types=1);
/**
 * \file
 * \brief Local Dictionary Controller - Dictionary management
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\View\Helper\PageLayoutHelper;
use Lwt\Database\Validation;
use Lwt\Core\Http\InputValidator;
use Lwt\Services\LocalDictionaryService;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Services\DictionaryImport\CsvImporter;
use Lwt\Services\DictionaryImport\JsonImporter;
use Lwt\Services\DictionaryImport\StarDictImporter;
use RuntimeException;

require_once __DIR__ . '/../Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../View/Helper/PageLayoutHelper.php';
// LanguageFacade loaded via autoloader

/**
 * Controller for local dictionary management.
 *
 * Handles:
 * - Dictionary listing and browsing
 * - Dictionary creation and deletion
 * - Import wizard for dictionary files
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class LocalDictionaryController extends BaseController
{
    private LocalDictionaryService $dictService;
    private LanguageFacade $languageService;

    /**
     * Create a new LocalDictionaryController.
     *
     * @param LocalDictionaryService $dictService     Dictionary service
     * @param LanguageFacade         $languageService Language facade
     */
    public function __construct(LocalDictionaryService $dictService, LanguageFacade $languageService)
    {
        parent::__construct();
        $this->dictService = $dictService;
        $this->languageService = $languageService;
    }

    /**
     * Index page - list dictionaries for a language.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        $langId = (int)Validation::language(
            InputValidator::getStringWithDb('lang', 'currentlanguage')
        );

        $langName = $this->languageService->getLanguageName($langId);
        PageLayoutHelper::renderPageStart($langName . ' - Local Dictionaries', true);

        // Handle form submissions
        $this->handleFormSubmissions($langId);

        // Get dictionaries
        $dictionaries = $this->dictService->getAllForLanguage($langId);
        $localDictMode = $this->dictService->getLocalDictMode($langId);

        // Get languages for dropdown
        $languages = $this->languageService->getLanguagesForSelect();

        // Include view
        include __DIR__ . '/../Views/LocalDictionary/index.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Import wizard - show import form or process upload.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function import(array $params): void
    {
        $langId = (int)Validation::language(
            InputValidator::getStringWithDb('lang', 'currentlanguage')
        );
        $dictId = $this->paramInt('dict_id');

        $langName = $this->languageService->getLanguageName($langId);
        PageLayoutHelper::renderPageStart($langName . ' - Import Dictionary', true);

        // Get dictionary if specified
        $dictionary = null;
        if ($dictId !== null) {
            $dictionary = $this->dictService->getById($dictId);
        }

        // Get or create dictionaries for this language
        $dictionaries = $this->dictService->getAllForLanguage($langId);

        // Include view
        include __DIR__ . '/../Views/LocalDictionary/import.php';

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Process import form submission.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function processImport(array $params): void
    {
        $langId = $this->paramInt('lang_id') ?? 0;
        $dictId = $this->paramInt('dict_id');
        $format = $this->param('format', 'csv');
        $dictName = $this->param('dict_name');

        if ($langId <= 0) {
            $this->redirect('/dictionaries?error=invalid_language');
        }

        // Create new dictionary if needed
        if ($dictId === null || $dictId <= 0) {
            if (empty($dictName)) {
                $dictName = 'Imported Dictionary ' . date('Y-m-d H:i');
            }
            $dictId = $this->dictService->create($langId, $dictName, $format);
        }

        // Process uploaded file
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->redirect("/dictionaries/import?lang=$langId&dict_id=$dictId&error=upload_failed");
        }

        $filePath = $_FILES['file']['tmp_name'];
        $originalName = $_FILES['file']['name'];

        try {
            $importer = $this->getImporter($format, $originalName);

            if (!$importer->canImport($filePath)) {
                $this->redirect("/dictionaries/import?lang=$langId&dict_id=$dictId&error=invalid_file");
            }

            // Get import options from form
            $options = $this->getImportOptions($format);

            // Perform import
            $entries = $importer->parse($filePath, $options);
            $count = $this->dictService->addEntriesBatch($dictId, $entries);

            $this->redirect("/dictionaries?lang=$langId&message=imported_$count");
        } catch (RuntimeException $e) {
            $errorMsg = urlencode($e->getMessage());
            $this->redirect("/dictionaries/import?lang=$langId&dict_id=$dictId&error=$errorMsg");
        }
    }

    /**
     * Delete a dictionary.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function delete(array $params): void
    {
        $dictId = $this->paramInt('dict_id');
        $langId = $this->paramInt('lang_id') ?? 0;

        if ($dictId !== null && $dictId > 0) {
            $this->dictService->delete($dictId);
        }

        $this->redirect("/dictionaries?lang=$langId&message=deleted");
    }

    /**
     * Preview file contents via AJAX.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function preview(array $params): void
    {
        header('Content-Type: application/json');

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        $format = $this->param('format', 'csv');
        $filePath = $_FILES['file']['tmp_name'];
        $originalName = $_FILES['file']['name'];

        try {
            $importer = $this->getImporter($format, $originalName);

            if (!$importer->canImport($filePath)) {
                echo json_encode(['error' => 'Invalid file format']);
                return;
            }

            $entries = $importer->preview($filePath, 10);

            $result = ['success' => true, 'entries' => $entries];

            // Add structure info for CSV
            if ($format === 'csv' && $importer instanceof CsvImporter) {
                $delimiter = $importer->detectDelimiter($filePath);
                $headers = $importer->detectHeaders($filePath, $delimiter);
                $result['structure'] = [
                    'delimiter' => $delimiter,
                    'headers' => $headers,
                    'suggested_mapping' => $importer->suggestColumnMap($headers),
                ];
            }

            echo json_encode($result);
        } catch (RuntimeException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle form submissions on the index page.
     *
     * @param int $langId Language ID
     *
     * @return void
     */
    private function handleFormSubmissions(int $langId): void
    {
        // Handle quick create
        if ($this->isPost() && $this->hasParam('create_dictionary')) {
            $name = $this->param('dict_name');
            if (!empty($name)) {
                $this->dictService->create($langId, $name, 'csv');
            }
        }

        // Handle quick delete
        if ($this->isPost() && $this->hasParam('delete_dictionary')) {
            $dictId = $this->paramInt('dict_id');
            if ($dictId !== null && $dictId > 0) {
                $this->dictService->delete($dictId);
            }
        }

        // Handle enable/disable toggle
        if ($this->isPost() && $this->hasParam('toggle_enabled')) {
            $dictId = $this->paramInt('dict_id');
            if ($dictId !== null) {
                $dict = $this->dictService->getById($dictId);
                if ($dict !== null) {
                    if ($dict->isEnabled()) {
                        $dict->disable();
                    } else {
                        $dict->enable();
                    }
                    $this->dictService->update($dict);
                }
            }
        }
    }

    /**
     * Get the appropriate importer for a format.
     *
     * @param string $format       Import format
     * @param string $originalName Original filename for detection
     *
     * @return \Lwt\Services\DictionaryImport\ImporterInterface
     *
     * @throws RuntimeException If format is unsupported
     */
    private function getImporter(string $format, string $originalName = ''): \Lwt\Services\DictionaryImport\ImporterInterface
    {
        // Auto-detect format from extension if not specified
        if ($format === 'auto' && !empty($originalName)) {
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $format = match ($ext) {
                'csv', 'tsv', 'txt' => 'csv',
                'json' => 'json',
                'ifo', 'idx', 'dict', 'dz' => 'stardict',
                default => 'csv',
            };
        }

        return match ($format) {
            'csv', 'tsv' => new CsvImporter(),
            'json' => new JsonImporter(),
            'stardict', 'ifo' => new StarDictImporter(),
            default => throw new RuntimeException("Unsupported format: $format"),
        };
    }

    /**
     * Get import options from form parameters.
     *
     * @param string $format Import format
     *
     * @return array<string, mixed>
     */
    private function getImportOptions(string $format): array
    {
        $options = [];

        if ($format === 'csv' || $format === 'tsv') {
            $delimiter = $this->param('delimiter', ',');
            if ($delimiter === 'tab') {
                $delimiter = "\t";
            }
            $options['delimiter'] = $delimiter;
            $options['hasHeader'] = $this->param('has_header', 'yes') === 'yes';

            // Column mapping
            $termCol = $this->paramInt('term_column');
            $defCol = $this->paramInt('definition_column');
            $readingCol = $this->paramInt('reading_column');
            $posCol = $this->paramInt('pos_column');

            $options['columnMap'] = [
                'term' => $termCol ?? 0,
                'definition' => $defCol ?? 1,
                'reading' => $readingCol,
                'pos' => $posCol,
            ];
        } elseif ($format === 'json') {
            // Field mapping for JSON
            $termField = $this->param('term_field');
            $defField = $this->param('definition_field');
            $readingField = $this->param('reading_field');
            $posField = $this->param('pos_field');

            if (!empty($termField) || !empty($defField)) {
                $options['fieldMap'] = [
                    'term' => !empty($termField) ? $termField : null,
                    'definition' => !empty($defField) ? $defField : null,
                    'reading' => !empty($readingField) ? $readingField : null,
                    'pos' => !empty($posField) ? $posField : null,
                ];
            }
        }

        return $options;
    }
}
