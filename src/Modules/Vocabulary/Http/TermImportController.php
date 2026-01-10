<?php declare(strict_types=1);
/**
 * Term Import Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Vocabulary\Application\Services\WordUploadService;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Controller for bulk translate and file import operations.
 *
 * Handles:
 * - /word/upload - Import terms from file
 * - /word/bulk-translate - Bulk translate terms
 *
 * @since 3.0.0
 */
class TermImportController extends VocabularyBaseController
{
    /**
     * Language facade.
     */
    private LanguageFacade $languageFacade;

    /**
     * Constructor.
     *
     * @param LanguageFacade|null $languageFacade Language facade
     */
    public function __construct(?LanguageFacade $languageFacade = null)
    {
        parent::__construct();
        $this->languageFacade = $languageFacade ?? new LanguageFacade();
    }

    /**
     * Bulk translate words.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function bulkTranslate(array $params): void
    {
        $tid = InputValidator::getInt('tid', 0) ?? 0;
        $pos = InputValidator::getInt('offset');

        // Handle form submission (save terms)
        $termsArray = InputValidator::getArray('term');
        if (!empty($termsArray)) {
            /** @var array<int, array{lg: int, text: string, status: int, trans?: string}> $terms */
            $terms = $termsArray;
            $cnt = count($terms);

            if ($pos !== null) {
                $pos -= $cnt;
            }

            PageLayoutHelper::renderPageStart($cnt . ' New Word' . ($cnt == 1 ? '' : 's') . ' Saved', false);
            $this->handleBulkSave($terms, $tid, $pos === null);
        } else {
            PageLayoutHelper::renderPageStartNobody('Translate New Words');
        }

        // Show next page of terms if there are more
        if ($pos !== null) {
            $sl = InputValidator::getString('sl');
            $tl = InputValidator::getString('tl');
            $this->displayBulkTranslateForm($tid, $sl !== '' ? $sl : null, $tl !== '' ? $tl : null, $pos);
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Handle saving bulk translated terms.
     *
     * @param array<int, array{lg: int, text: string, status: int, trans?: string}> $terms Array of term data
     * @param int  $tid     Text ID
     * @param bool $cleanUp Whether to clean up right frames after save
     *
     * @return void
     *
     * @psalm-suppress UnusedParam $tid and $cleanUp are used in included view file
     * @psalm-suppress UnresolvableInclude Path computed from viewPath property
     */
    private function handleBulkSave(array $terms, int $tid, bool $cleanUp): void
    {
        $bulkService = $this->getBulkService();
        $maxWoId = $bulkService->bulkSaveTerms($terms);

        $tooltipMode = Settings::getWithDefault('set-tooltip-mode');
        $res = $bulkService->getNewWordsAfter($maxWoId);

        // Link new words to text items
        $linkingService = new \Lwt\Modules\Vocabulary\Application\Services\WordLinkingService();
        $linkingService->linkNewWordsToTextItems($maxWoId);

        // Prepare data for view
        /** @var list<array<string, mixed>> $newWords */
        $newWords = [];
        foreach ($res as $record) {
            $record['hex'] = StringUtils::toClassName(
                Escaping::prepareTextdata((string)$record['WoTextLC'])
            );
            $record['translation'] = (string)$record['WoTranslation'];
            $newWords[] = $record;
        }

        include $this->viewPath . 'bulk_save_result.php';
    }

    /**
     * Display the bulk translate form.
     *
     * @param int         $tid Text ID
     * @param string|null $sl  Source language code
     * @param string|null $tl  Target language code
     * @param int         $pos Offset position
     *
     * @psalm-suppress UnresolvableInclude Path computed from viewPath property
     *
     * @return void
     */
    private function displayBulkTranslateForm(int $tid, ?string $sl, ?string $tl, int $pos): void
    {
        $contextService = $this->getContextService();
        $discoveryService = $this->getDiscoveryService();
        $limit = (int) Settings::getWithDefault('set-ggl-translation-per-page') + 1;
        $dictionaries = $contextService->getLanguageDictionaries($tid);

        $res = $discoveryService->getUnknownWordsForBulkTranslate($tid, $pos, $limit);

        // Collect terms and check if there are more
        $terms = [];
        $hasMore = false;
        $cnt = 0;
        foreach ($res as $record) {
            $cnt++;
            if ($cnt < $limit) {
                $terms[] = $record;
            } else {
                $hasMore = true;
            }
        }

        // Calculate next offset if there are more terms
        $nextOffset = $hasMore ? $pos + $limit - 1 : null;

        include $this->viewPath . 'bulk_translate_form.php';
    }

    /**
     * Upload words from file.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function upload(array $params): void
    {
        PageLayoutHelper::renderPageStart('Import Terms', true);

        $op = InputValidator::getString('op');
        if ($op === 'Import') {
            $this->handleUploadImport();
        } else {
            $this->displayUploadForm();
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Display the word upload form.
     *
     * @psalm-suppress UnresolvableInclude Path computed from viewPath property
     *
     * @return void
     */
    private function displayUploadForm(): void
    {
        $currentLanguage = Settings::get('currentlanguage');
        $languages = $this->languageFacade->getLanguagesForSelect();
        include $this->viewPath . 'upload_form.php';
    }

    /**
     * Handle the word import operation.
     *
     * @psalm-suppress UnresolvableInclude Path computed from viewPath property
     *
     * @return void
     */
    private function handleUploadImport(): void
    {
        $uploadService = $this->getUploadService();
        $tabType = InputValidator::getString("Tab");
        if ($tabType === '') {
            $tabType = 'c';
        }
        $langId = InputValidator::getInt("LgID", 0) ?? 0;

        if ($langId === 0) {
            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                'Error: No language selected</div>';
            return;
        }

        $langData = $uploadService->getLanguageData($langId);
        if ($langData === null) {
            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                'Error: Invalid language</div>';
            return;
        }

        $removeSpaces = (bool) $langData['LgRemoveSpaces'];

        // Parse column mapping
        $columns = [
            1 => InputValidator::getString("Col1"),
            2 => InputValidator::getString("Col2"),
            3 => InputValidator::getString("Col3"),
            4 => InputValidator::getString("Col4"),
            5 => InputValidator::getString("Col5"),
        ];
        $columns = array_unique($columns);

        $parsed = $uploadService->parseColumnMapping($columns, $removeSpaces);
        /** @var array<int, string> $col */
        $col = $parsed['columns'];
        /** @var array{txt: int, tr: int, ro: int, se: int, tl: int} $fields */
        $fields = $parsed['fields'];

        // Check for file upload vs text input
        $uploadedFile = InputValidator::getUploadedFile('thefile');

        // Get or create the input file
        $uploadText = InputValidator::getString("Upload");
        $createdTempFile = false;
        if ($uploadedFile !== null) {
            $fileName = $uploadedFile["tmp_name"];
        } else {
            if ($uploadText === '') {
                echo '<div class="notification is-danger">' .
                    '<button class="delete" aria-label="close"></button>' .
                    'Error: No data to import</div>';
                return;
            }
            $fileName = $uploadService->createTempFile($uploadText);
            $createdTempFile = true;
        }

        try {
            $ignoreFirst = InputValidator::getString("IgnFirstLine") === '1';
            $overwrite = InputValidator::getInt("Over", 0) ?? 0;
            $status = InputValidator::getInt("WoStatus", 1) ?? 1;
            $translDelim = InputValidator::getString("transl_delim");

            // Get last update timestamp before import
            $lastUpdate = $uploadService->getLastWordUpdate() ?? '';

            if ($fields["txt"] > 0) {
                // Import terms
                $this->importTerms(
                    $uploadService,
                    $langId,
                    $fields,
                    $col,
                    $tabType,
                    $fileName,
                    $status,
                    $overwrite,
                    $ignoreFirst,
                    $translDelim,
                    $lastUpdate
                );

                // Display results
                $rtl = $uploadService->isRightToLeft($langId) ? 1 : 0;
                $recno = $uploadService->countImportedTerms($lastUpdate);
                include $this->viewPath . 'upload_result.php';
            } elseif ($fields["tl"] > 0) {
                // Import tags only
                $uploadService->importTagsOnly(['tl' => $fields['tl']], $tabType, $fileName, $ignoreFirst);
                echo '<p>Tags imported successfully.</p>';
            } else {
                echo '<div class="notification is-danger">' .
                    '<button class="delete" aria-label="close"></button>' .
                    'Error: No term column specified</div>';
            }
        } finally {
            // Clean up temp file if we created it
            if ($createdTempFile && file_exists($fileName)) {
                unlink($fileName);
            }
        }
    }

    /**
     * Import terms from the uploaded file.
     *
     * @param WordUploadService       $uploadService  The upload service
     * @param int                     $langId         Language ID
     * @param array{txt: int, tr: int, ro: int, se: int, tl: int} $fields Field indexes
     * @param array<int, string>      $col            Column mapping
     * @param string                  $tabType        Tab type (c, t, h)
     * @param string                  $fileName       Path to input file
     * @param int                     $status         Word status
     * @param int                     $overwrite      Overwrite mode
     * @param bool                    $ignoreFirst    Ignore first line
     * @param string                  $translDelim    Translation delimiter
     * @param string                  $lastUpdate     Last update timestamp
     *
     * @return void
     */
    private function importTerms(
        WordUploadService $uploadService,
        int $langId,
        array $fields,
        array $col,
        string $tabType,
        string $fileName,
        int $status,
        int $overwrite,
        bool $ignoreFirst,
        string $translDelim,
        string $lastUpdate
    ): void {
        $columnsClause = '(' . rtrim(implode(',', $col), ',') . ')';
        $delimiter = $uploadService->getSqlDelimiter($tabType);

        // Use simple import for no tags and no overwrite, complete import otherwise
        if ($fields["tl"] == 0 && $overwrite == 0) {
            $uploadService->importSimple(
                $langId,
                $fields,
                $columnsClause,
                $delimiter,
                $fileName,
                $status,
                $ignoreFirst
            );
        } else {
            $uploadService->importComplete(
                $langId,
                $fields,
                $columnsClause,
                $delimiter,
                $fileName,
                $status,
                $overwrite,
                $ignoreFirst,
                $translDelim,
                $tabType
            );
        }

        // Post-import processing
        \Lwt\Shared\Infrastructure\Database\Maintenance::initWordCount();
        $uploadService->linkWordsToTextItems();
        $uploadService->handleMultiwords($langId, $lastUpdate);
    }
}
