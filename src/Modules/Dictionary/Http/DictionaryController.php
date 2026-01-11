<?php

declare(strict_types=1);

/**
 * Dictionary Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Dictionary\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Dictionary\Http;

use Lwt\Controllers\BaseController;
use Lwt\Modules\Dictionary\Application\DictionaryFacade;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Database\Validation;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use RuntimeException;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for local dictionary management.
 *
 * Handles:
 * - Dictionary listing and browsing
 * - Dictionary creation and deletion
 * - Import wizard for dictionary files
 *
 * @since 3.0.0
 */
class DictionaryController extends BaseController
{
    private DictionaryFacade $dictionaryFacade;
    private LanguageFacade $languageFacade;

    /**
     * Create a new DictionaryController.
     *
     * @param DictionaryFacade $dictionaryFacade Dictionary facade
     * @param LanguageFacade   $languageFacade   Language facade
     */
    public function __construct(DictionaryFacade $dictionaryFacade, LanguageFacade $languageFacade)
    {
        parent::__construct();
        $this->dictionaryFacade = $dictionaryFacade;
        $this->languageFacade = $languageFacade;
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

        $langName = $this->languageFacade->getLanguageName($langId);
        PageLayoutHelper::renderPageStart($langName . ' - Local Dictionaries', true);

        // Handle form submissions
        $this->handleFormSubmissions($langId);

        // Get dictionaries
        $dictionaries = $this->dictionaryFacade->getAllForLanguage($langId);
        $localDictMode = $this->dictionaryFacade->getLocalDictMode($langId);

        // Get languages for dropdown
        $languages = $this->languageFacade->getLanguagesForSelect();

        // Include view
        include __DIR__ . '/../Views/index.php';

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

        $langName = $this->languageFacade->getLanguageName($langId);
        PageLayoutHelper::renderPageStart($langName . ' - Import Dictionary', true);

        // Get dictionary if specified
        $dictionary = null;
        if ($dictId !== null) {
            $dictionary = $this->dictionaryFacade->getById($dictId);
        }

        // Get or create dictionaries for this language
        $dictionaries = $this->dictionaryFacade->getAllForLanguage($langId);

        // Include view
        include __DIR__ . '/../Views/import.php';

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
            $dictId = $this->dictionaryFacade->create($langId, $dictName, $format);
        }

        // Process uploaded file
        $uploadedFile = InputValidator::getUploadedFile('file');
        if ($uploadedFile === null) {
            $this->redirect("/dictionaries/import?lang=$langId&dict_id=$dictId&error=upload_failed");
        }

        $filePath = $uploadedFile['tmp_name'];
        $originalName = $uploadedFile['name'];

        try {
            $importer = $this->dictionaryFacade->getImporter($format, $originalName);

            /** @psalm-suppress UndefinedClass Psalm incorrectly resolves namespace */
            if (!$importer->canImport($filePath)) {
                $this->redirect("/dictionaries/import?lang=$langId&dict_id=$dictId&error=invalid_file");
            }

            // Get import options from form
            $options = $this->getImportOptions($format);

            // Perform import
            /** @psalm-suppress UndefinedClass Psalm incorrectly resolves namespace */
            $entries = $importer->parse($filePath, $options);
            $count = $this->dictionaryFacade->addEntriesBatch($dictId, $entries);

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
            $this->dictionaryFacade->delete($dictId);
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

        $uploadedFile = InputValidator::getUploadedFile('file');
        if ($uploadedFile === null) {
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        $format = $this->param('format', 'csv');
        $filePath = $uploadedFile['tmp_name'];
        $originalName = $uploadedFile['name'];

        try {
            $importer = $this->dictionaryFacade->getImporter($format, $originalName);

            if (!$importer->canImport($filePath)) {
                echo json_encode(['error' => 'Invalid file format']);
                return;
            }

            $entries = $importer->preview($filePath, 10);

            $result = ['success' => true, 'entries' => $entries];

            // Add structure info for CSV
            if ($format === 'csv') {
                $csvImporter = $this->dictionaryFacade->getImporter('csv', '');
                if ($csvImporter instanceof \Lwt\Modules\Dictionary\Infrastructure\Import\CsvImporter) {
                    $delimiter = $csvImporter->detectDelimiter($filePath);
                    $headers = $csvImporter->detectHeaders($filePath, $delimiter);
                    $result['structure'] = [
                        'delimiter' => $delimiter,
                        'headers' => $headers,
                        'suggested_mapping' => $csvImporter->suggestColumnMap($headers),
                    ];
                }
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
                $this->dictionaryFacade->create($langId, $name, 'csv');
            }
        }

        // Handle quick delete
        if ($this->isPost() && $this->hasParam('delete_dictionary')) {
            $dictId = $this->paramInt('dict_id');
            if ($dictId !== null && $dictId > 0) {
                $this->dictionaryFacade->delete($dictId);
            }
        }

        // Handle enable/disable toggle
        if ($this->isPost() && $this->hasParam('toggle_enabled')) {
            $dictId = $this->paramInt('dict_id');
            if ($dictId !== null) {
                $dict = $this->dictionaryFacade->getById($dictId);
                if ($dict !== null) {
                    if ($dict->isEnabled()) {
                        $dict->disable();
                    } else {
                        $dict->enable();
                    }
                    $this->dictionaryFacade->update($dict);
                }
            }
        }
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
