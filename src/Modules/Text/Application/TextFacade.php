<?php declare(strict_types=1);
/**
 * Text Facade
 *
 * Backward-compatible facade for text operations.
 * Delegates to use case classes for actual implementation.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Text\Application;

use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\Escaping;
use Lwt\Shared\Infrastructure\Database\Maintenance;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Database\TextParsing;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Text\Application\UseCases\ArchiveText;
use Lwt\Modules\Text\Application\UseCases\BuildTextFilters;
use Lwt\Modules\Text\Application\UseCases\DeleteText;
use Lwt\Modules\Text\Application\UseCases\GetTextForEdit;
use Lwt\Modules\Text\Application\UseCases\GetTextForReading;
use Lwt\Modules\Text\Application\UseCases\ImportText;
use Lwt\Modules\Text\Application\UseCases\ListTexts;
use Lwt\Modules\Text\Application\UseCases\ParseText;
use Lwt\Modules\Text\Application\UseCases\UpdateText;
use Lwt\Modules\Text\Domain\TextRepositoryInterface;
use Lwt\Modules\Text\Infrastructure\MySqlTextRepository;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Text\Application\Services\SentenceService;
use Lwt\Modules\Tags\Application\TagsFacade;

/**
 * Facade for text module operations.
 *
 * Provides a unified interface to all text-related use cases.
 * Designed for backward compatibility with existing TextService callers.
 *
 * @since 3.0.0
 */
class TextFacade
{
    protected ArchiveText $archiveText;
    protected BuildTextFilters $buildTextFilters;
    protected DeleteText $deleteText;
    protected GetTextForEdit $getTextForEdit;
    protected GetTextForReading $getTextForReading;
    protected ImportText $importText;
    protected ListTexts $listTexts;
    protected ParseText $parseText;
    protected UpdateText $updateText;
    protected TextRepositoryInterface $textRepository;
    protected SentenceService $sentenceService;

    /**
     * Constructor.
     *
     * @param TextRepositoryInterface|null $textRepository    Text repository
     * @param ArchiveText|null            $archiveText       Archive use case
     * @param BuildTextFilters|null       $buildTextFilters  Filter builder use case
     * @param DeleteText|null             $deleteText        Delete use case
     * @param GetTextForEdit|null         $getTextForEdit    Get for edit use case
     * @param GetTextForReading|null      $getTextForReading Get for reading use case
     * @param ImportText|null             $importText        Import use case
     * @param ListTexts|null              $listTexts         List use case
     * @param ParseText|null              $parseText         Parse use case
     * @param UpdateText|null             $updateText        Update use case
     * @param SentenceService|null        $sentenceService   Sentence service
     */
    public function __construct(
        ?TextRepositoryInterface $textRepository = null,
        ?ArchiveText $archiveText = null,
        ?BuildTextFilters $buildTextFilters = null,
        ?DeleteText $deleteText = null,
        ?GetTextForEdit $getTextForEdit = null,
        ?GetTextForReading $getTextForReading = null,
        ?ImportText $importText = null,
        ?ListTexts $listTexts = null,
        ?ParseText $parseText = null,
        ?UpdateText $updateText = null,
        ?SentenceService $sentenceService = null
    ) {
        $this->textRepository = $textRepository ?? new MySqlTextRepository();
        $this->archiveText = $archiveText ?? new ArchiveText();
        $this->buildTextFilters = $buildTextFilters ?? new BuildTextFilters();
        $this->deleteText = $deleteText ?? new DeleteText();
        $this->getTextForEdit = $getTextForEdit ?? new GetTextForEdit($this->textRepository);
        $this->getTextForReading = $getTextForReading ?? new GetTextForReading($this->textRepository);
        $this->importText = $importText ?? new ImportText($this->textRepository);
        $this->listTexts = $listTexts ?? new ListTexts($this->textRepository, $this->buildTextFilters);
        $this->parseText = $parseText ?? new ParseText();
        $this->updateText = $updateText ?? new UpdateText($this->textRepository);
        $this->sentenceService = $sentenceService ?? new SentenceService();
    }

    // =====================
    // ARCHIVED TEXT METHODS
    // =====================

    /**
     * Get count of archived texts matching filters.
     */
    public function getArchivedTextCount(string $whLang, string $whQuery, string $whTag): int
    {
        return $this->listTexts->getArchivedTextCount($whLang, $whQuery, $whTag);
    }

    /**
     * Get archived texts list with pagination.
     */
    public function getArchivedTextsList(
        string $whLang,
        string $whQuery,
        string $whTag,
        int $sort,
        int $page,
        int $perPage
    ): array {
        return $this->listTexts->getArchivedTextsList($whLang, $whQuery, $whTag, $sort, $page, $perPage);
    }

    /**
     * Get a single archived text by ID.
     */
    public function getArchivedTextById(int $textId): ?array
    {
        return $this->getTextForEdit->getArchivedTextById($textId);
    }

    /**
     * Delete an archived text.
     */
    public function deleteArchivedText(int $textId): string
    {
        return $this->deleteText->deleteArchivedText($textId);
    }

    /**
     * Delete multiple archived texts.
     */
    public function deleteArchivedTexts(array $textIds): string
    {
        return $this->deleteText->deleteArchivedTexts($textIds);
    }

    /**
     * Unarchive a text.
     */
    public function unarchiveText(int $archivedId): array
    {
        return $this->archiveText->unarchive($archivedId);
    }

    /**
     * Unarchive multiple texts.
     */
    public function unarchiveTexts(array $archivedIds): string
    {
        return $this->archiveText->unarchiveMultiple($archivedIds);
    }

    /**
     * Update an archived text.
     */
    public function updateArchivedText(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): string {
        return $this->updateText->updateArchivedText($textId, $lgId, $title, $text, $audioUri, $sourceUri);
    }

    // =======================
    // FILTER BUILDING METHODS
    // =======================

    /**
     * Build WHERE clause for archived text query.
     */
    public function buildArchivedQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode
    ): array {
        return $this->buildTextFilters->buildArchivedQueryWhereClause($query, $queryMode, $regexMode);
    }

    /**
     * Build HAVING clause for archived text tag filtering.
     */
    public function buildArchivedTagHavingClause(string|int $tag1, string|int $tag2, string $tag12): string
    {
        return $this->buildTextFilters->buildArchivedTagHavingClause($tag1, $tag2, $tag12);
    }

    /**
     * Build WHERE clause for text query.
     */
    public function buildTextQueryWhereClause(
        string $query,
        string $queryMode,
        string $regexMode
    ): array {
        return $this->buildTextFilters->buildQueryWhereClause($query, $queryMode, $regexMode, 'Tx');
    }

    /**
     * Build HAVING clause for text tag filtering.
     */
    public function buildTextTagHavingClause(string|int $tag1, string|int $tag2, string $tag12): string
    {
        return $this->buildTextFilters->buildTextTagHavingClause($tag1, $tag2, $tag12);
    }

    /**
     * Validate regex query.
     */
    public function validateRegexQuery(string $query, string $regexMode): bool
    {
        return $this->buildTextFilters->validateRegexQuery($query, $regexMode);
    }

    // ==================
    // PAGINATION METHODS
    // ==================

    /**
     * Get archived texts per page setting.
     */
    public function getArchivedTextsPerPage(): int
    {
        return $this->listTexts->getArchivedTextsPerPage();
    }

    /**
     * Get texts per page setting.
     */
    public function getTextsPerPage(): int
    {
        return $this->listTexts->getTextsPerPage();
    }

    /**
     * Calculate pagination info.
     */
    public function getPagination(int $totalCount, int $currentPage, int $perPage): array
    {
        return $this->listTexts->getPagination($totalCount, $currentPage, $perPage);
    }

    // =====================
    // ACTIVE TEXT METHODS
    // =====================

    /**
     * Get a single active text by ID.
     */
    public function getTextById(int $textId): ?array
    {
        return $this->getTextForEdit->getTextById($textId);
    }

    /**
     * Delete an active text.
     */
    public function deleteText(int $textId): string
    {
        return $this->deleteText->execute($textId);
    }

    /**
     * Archive an active text.
     */
    public function archiveText(int $textId): string
    {
        return $this->archiveText->execute($textId);
    }

    /**
     * Get count of active texts matching filters.
     */
    public function getTextCount(string $whLang, string $whQuery, string $whTag): int
    {
        return $this->listTexts->getTextCount($whLang, $whQuery, $whTag);
    }

    /**
     * Get active texts list with pagination.
     */
    public function getTextsList(
        string $whLang,
        string $whQuery,
        string $whTag,
        int $sort,
        int $page,
        int $perPage
    ): array {
        return $this->listTexts->getTextsList($whLang, $whQuery, $whTag, $sort, $page, $perPage);
    }

    /**
     * Get texts for a specific language (basic version without sort).
     *
     * Note: TextService::getTextsForLanguage() has an additional sort parameter
     * for BC. Use that method if you need sorting.
     */
    public function getBasicTextsForLanguage(int $languageId, int $page = 1, int $perPage = 20): array
    {
        return $this->listTexts->getTextsForLanguage($languageId, $page, $perPage);
    }

    /**
     * Create a new text.
     */
    public function createText(
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): array {
        return $this->importText->execute($lgId, $title, $text, $audioUri, $sourceUri);
    }

    /**
     * Update an active text.
     */
    public function updateText(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): array {
        return $this->updateText->execute($textId, $lgId, $title, $text, $audioUri, $sourceUri);
    }

    /**
     * Delete multiple active texts.
     */
    public function deleteTexts(array $textIds): string
    {
        return $this->deleteText->deleteMultiple($textIds);
    }

    /**
     * Archive multiple texts.
     */
    public function archiveTexts(array $textIds): string
    {
        return $this->archiveText->archiveMultiple($textIds);
    }

    /**
     * Rebuild/reparse multiple texts.
     */
    public function rebuildTexts(array $textIds): string
    {
        return $this->updateText->rebuildTexts($textIds);
    }

    // ====================
    // TEXT CHECK METHODS
    // ====================

    /**
     * Get text parsing preview (sentences, words, unknown percent).
     *
     * Returns parsing statistics without saving. Use this for new code.
     * TextService::checkText() is kept for BC (outputs HTML directly).
     */
    public function getParsingPreview(string $text, int $languageId): array
    {
        return $this->parseText->execute($text, $languageId);
    }

    /**
     * Validate text length.
     */
    public function validateTextLength(string $text): bool
    {
        return $this->parseText->validateTextLength($text);
    }

    // =======================
    // LONG TEXT IMPORT METHODS
    // =======================

    /**
     * Prepare simple long text data from pasted text or uploaded file.
     *
     * Use this for new code. TextService::prepareLongTextData() has different
     * signature for BC with existing controllers.
     */
    public function prepareSimpleLongTextData(?string $pastedText, ?array $uploadedFile): ?string
    {
        return $this->importText->prepareLongTextData($pastedText, $uploadedFile);
    }

    /**
     * Split text into chunks by paragraph.
     *
     * Use this for new code. TextService::splitLongText() has different
     * signature for BC (uses language ID and max sentences).
     */
    public function splitTextIntoChunks(string $text, int $maxLength = 60000): array
    {
        return $this->importText->splitLongText($text, $maxLength);
    }

    /**
     * Import long text as multiple chunks.
     *
     * Use this for new code. TextService::saveLongTextImport() has different
     * signature for BC with existing controllers.
     */
    public function importLongTextChunks(
        int $languageId,
        string $baseTitle,
        array $chunks,
        string $audioUri = '',
        string $sourceUri = '',
        array $tagIds = []
    ): array {
        return $this->importText->saveLongTextImport($languageId, $baseTitle, $chunks, $audioUri, $sourceUri, $tagIds);
    }

    // ======================
    // TEXT READING METHODS
    // ======================

    /**
     * Get text data for reading interface.
     */
    public function getTextForReading(int $textId): ?array
    {
        return $this->getTextForReading->execute($textId);
    }

    /**
     * Get language settings for reading.
     */
    public function getLanguageSettingsForReading(int $languageId): ?array
    {
        return $this->getTextForReading->getLanguageSettingsForReading($languageId);
    }

    /**
     * Get TTS voice API for language.
     */
    public function getTtsVoiceApi(int $languageId): ?string
    {
        $result = $this->getTextForReading->getTtsVoiceApi($languageId);
        return $result === '' ? null : $result;
    }

    /**
     * Get language ID by name.
     */
    public function getLanguageIdByName(string $languageName): ?int
    {
        return $this->getTextForReading->getLanguageIdByName($languageName);
    }

    /**
     * Get Google Translate URIs by language.
     */
    public function getLanguageTranslateUris(): array
    {
        return $this->getTextForReading->getLanguageTranslateUris();
    }

    // =======================
    // TEXT EDIT PAGE METHODS
    // =======================

    /**
     * Set term sentences for words from texts.
     */
    public function setTermSentences(array $textIds, bool $activeOnly = false): string
    {
        return $this->parseText->setTermSentences($textIds, $activeOnly);
    }

    /**
     * Get text for edit form.
     */
    public function getTextForEdit(int $textId): ?array
    {
        return $this->getTextForEdit->getTextForEdit($textId);
    }

    /**
     * Get language data for form.
     */
    public function getLanguageDataForForm(): array
    {
        return $this->getTextForEdit->getLanguageDataForForm();
    }

    /**
     * Save text and reparse (returns message only).
     *
     * Use this for new code. TextService::saveTextAndReparse() returns
     * additional data (textId, redirect) for BC with existing controllers.
     */
    public function saveAndReparseText(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): string {
        return $this->updateText->saveTextAndReparse($textId, $lgId, $title, $text, $audioUri, $sourceUri);
    }

    /**
     * Get texts formatted for select dropdown.
     */
    public function getTextsForSelect(int $languageId = 0, int $maxNameLength = 30): array
    {
        return $this->getTextForEdit->getTextsForSelect($languageId, $maxNameLength);
    }

    // ===========================
    // BC METHODS FROM TextService
    // ===========================

    /**
     * Get paginated texts for a specific language (with sort).
     *
     * @param int $langId  Language ID
     * @param int $page    Page number
     * @param int $perPage Items per page
     * @param int $sort    Sort option (1=title, 2=newest, 3=oldest)
     *
     * @return array{texts: array, pagination: array}
     */
    public function getTextsForLanguage(
        int $langId,
        int $page = 1,
        int $perPage = 20,
        int $sort = 1
    ): array {
        $sorts = ['TxTitle', 'TxID DESC', 'TxID ASC'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;

        $bindings1 = [$langId];
        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM texts WHERE TxLgID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings1),
            $bindings1,
            'cnt'
        );
        $totalPages = (int) ceil($total / $perPage);

        $bindings2 = [$langId, $offset, $perPage];
        $records = Connection::preparedFetchAll(
            "SELECT TxID, TxTitle, TxAudioURI, TxSourceURI,
            LENGTH(TxAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (texts LEFT JOIN texttags ON TxID = TtTxID)
                LEFT JOIN tags2 ON T2ID = TtT2ID
            )
            WHERE TxLgID = ?
            GROUP BY TxID
            ORDER BY {$sortColumn}
            LIMIT ?, ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings2)
            . UserScopedQuery::forTablePrepared('tags2', $bindings2),
            $bindings2
        );

        $texts = [];
        foreach ($records as $record) {
            $texts[] = [
                'id' => (int) $record['TxID'],
                'title' => (string) $record['TxTitle'],
                'has_audio' => !empty($record['TxAudioURI']),
                'source_uri' => (string) ($record['TxSourceURI'] ?? ''),
                'has_source' => !empty($record['TxSourceURI']) && substr($record['TxSourceURI'], 0, 1) !== '#',
                'annotated' => !empty($record['annotlen']),
                'taglist' => (string) $record['taglist']
            ];
        }

        return [
            'texts' => $texts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Get paginated archived texts for a specific language.
     *
     * @param int $langId  Language ID
     * @param int $page    Page number
     * @param int $perPage Items per page
     * @param int $sort    Sort option (1=title, 2=newest, 3=oldest)
     *
     * @return array{texts: array, pagination: array}
     */
    public function getArchivedTextsForLanguage(
        int $langId,
        int $page,
        int $perPage,
        int $sort
    ): array {
        $sorts = ['AtTitle', 'AtID DESC', 'AtID ASC'];
        $sortColumn = $sorts[max(0, min($sort - 1, count($sorts) - 1))];
        $offset = ($page - 1) * $perPage;

        $bindings1 = [$langId];
        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM archivedtexts WHERE AtLgID = ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings1),
            $bindings1,
            'cnt'
        );
        $totalPages = (int) ceil($total / $perPage);

        $bindings2 = [$langId, $offset, $perPage];
        $records = Connection::preparedFetchAll(
            "SELECT AtID, AtTitle, AtAudioURI, AtSourceURI,
            LENGTH(AtAnnotatedText) AS annotlen,
            IFNULL(GROUP_CONCAT(DISTINCT T2Text ORDER BY T2Text SEPARATOR ','), '') AS taglist
            FROM (
                (archivedtexts LEFT JOIN archtexttags ON AtID = AgAtID)
                LEFT JOIN tags2 ON T2ID = AgT2ID
            )
            WHERE AtLgID = ?
            GROUP BY AtID
            ORDER BY {$sortColumn}
            LIMIT ?, ?"
            . UserScopedQuery::forTablePrepared('archivedtexts', $bindings2)
            . UserScopedQuery::forTablePrepared('tags2', $bindings2),
            $bindings2
        );

        $texts = [];
        foreach ($records as $record) {
            $texts[] = [
                'id' => (int) $record['AtID'],
                'title' => (string) $record['AtTitle'],
                'has_audio' => !empty($record['AtAudioURI']),
                'source_uri' => (string) ($record['AtSourceURI'] ?? ''),
                'has_source' => !empty($record['AtSourceURI']),
                'annotated' => !empty($record['annotlen']),
                'taglist' => (string) $record['taglist']
            ];
        }

        return [
            'texts' => $texts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ];
    }

    /**
     * Check text for parsing without saving (outputs HTML).
     *
     * @param string $text Text to check
     * @param int    $lgId Language ID
     *
     * @return void
     */
    public function checkText(string $text, int $lgId): void
    {
        if (strlen(Escaping::prepareTextdata($text)) > 65000) {
            echo "<p>Error: Text too long, must be below 65000 Bytes.</p>";
        } else {
            TextParsing::parseAndDisplayPreview($text, $lgId);
        }
    }

    /**
     * Prepare text data for long text import.
     *
     * @param array  $files             $_FILES array
     * @param string $uploadText        Pasted text content
     * @param int    $paragraphHandling Paragraph handling mode
     *
     * @return string Prepared text data
     */
    public function prepareLongTextData(
        array $files,
        string $uploadText,
        int $paragraphHandling
    ): string {
        if (
            isset($files["thefile"])
            && $files["thefile"]["tmp_name"] != ""
            && $files["thefile"]["error"] == 0
        ) {
            $data = file_get_contents($files["thefile"]["tmp_name"]);
            $data = str_replace("\r\n", "\n", $data);
        } else {
            $data = Escaping::prepareTextdata($uploadText);
        }
        $data = preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\x96\x88", $data);
        $data = trim($data);

        if ($paragraphHandling == 2) {
            $data = preg_replace('/\n\s*?\n/u', '¶', $data);
            $data = str_replace("\n", ' ', $data);
        } else {
            $data = str_replace("\n", '¶', $data);
        }
        $data = preg_replace('/\s{2,}/u', ' ', $data);
        $data = str_replace('¶ ', '¶', $data);
        $data = str_replace('¶', "\n", $data);

        return $data;
    }

    /**
     * Split long text into smaller texts.
     *
     * @param string $data    Text data to split
     * @param int    $langId  Language ID
     * @param int    $maxSent Maximum sentences per text
     *
     * @return array Array of text chunks
     */
    public function splitLongText(string $data, int $langId, int $maxSent): array
    {
        $sentArray = TextParsing::splitIntoSentences($data, $langId);
        $texts = [];
        $textIndex = 0;
        $texts[$textIndex] = [];
        $cnt = 0;
        $bytes = 0;

        foreach ($sentArray as $item) {
            $itemLen = strlen($item) + 1;
            if ($item != '¶') {
                $cnt++;
            }
            if ($cnt <= $maxSent && $bytes + $itemLen < 65000) {
                $texts[$textIndex][] = $item;
                $bytes += $itemLen;
            } else {
                $textIndex++;
                $texts[$textIndex] = [$item];
                $cnt = 1;
                $bytes = $itemLen;
            }
        }

        return $texts;
    }

    /**
     * Save long text import (multiple texts).
     *
     * @param int        $langId    Language ID
     * @param string     $title     Base title
     * @param string     $sourceUri Source URI
     * @param array      $texts     Array of text chunks
     * @param int        $textCount Expected text count
     * @param array|null $textTags  Tags to apply
     *
     * @return array{success: bool, message: string, imported: int}
     */
    public function saveLongTextImport(
        int $langId,
        string $title,
        string $sourceUri,
        array $texts,
        int $textCount,
        ?array $textTags = null
    ): array {
        if (count($texts) != $textCount) {
            return [
                'success' => false,
                'message' => "Error: Number of texts wrong: " . count($texts) . " != " . $textCount,
                'imported' => 0
            ];
        }

        $imported = 0;
        for ($i = 0; $i < $textCount; $i++) {
            $texts[$i] = str_replace("\xC2\xAD", "", $texts[$i]);
            $counter = StringUtils::makeCounterWithTotal($textCount, $i + 1);
            $thisTitle = $title . ($counter == '' ? '' : (' (' . $counter . ')'));

            $bindings = [$langId, $thisTitle, $texts[$i], $sourceUri];
            $affected = Connection::preparedExecute(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertColumn('texts')
                . ") VALUES (?, ?, ?, '', '', ?"
                    . UserScopedQuery::insertValuePrepared('texts', $bindings)
                . ")",
                $bindings
            );
            $imported += $affected;
            $id = (int)Connection::lastInsertId();
            TagsFacade::saveTextTagsFromForm($id, $textTags);
            $textContent = is_string($texts[$i]) ? $texts[$i] : '';
            TextParsing::parseAndSave($textContent, $langId, $id);
        }

        return [
            'success' => true,
            'message' => $imported . " Text(s) imported!",
            'imported' => $imported
        ];
    }

    /**
     * Get text data for text content display.
     *
     * @param int $textId Text ID
     *
     * @return array|null Text data or null if not found
     */
    public function getTextDataForContent(int $textId): ?array
    {
        $bindings = [$textId];
        return Connection::preparedFetchOne(
            "SELECT TxLgID, TxTitle, TxAnnotatedText, TxPosition
                FROM texts
                WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings),
            $bindings
        );
    }

    /**
     * Set term sentences from texts (with SentenceService).
     *
     * Overrides the base implementation to use SentenceService for formatting.
     *
     * @param array $textIds    Text IDs to process
     * @param bool  $activeOnly Only process active terms (status != 98, 99)
     *
     * @return string Result message
     */
    public function setTermSentencesWithService(array $textIds, bool $activeOnly = false): string
    {
        if (empty($textIds)) {
            return "Multiple Actions: 0";
        }

        $ids = array_map('intval', $textIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $count = 0;

        $statusFilter = $activeOnly
            ? " AND WoStatus != 98 AND WoStatus != 99"
            : "";

        $sql = "SELECT WoID, WoTextLC, MIN(Ti2SeID) AS SeID
            FROM words, textitems2
            WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID IN ({$placeholders})
            {$statusFilter}
            AND IFNULL(WoSentence,'') NOT LIKE CONCAT('%{',WoText,'}%')
            GROUP BY WoID
            ORDER BY WoID, MIN(Ti2SeID)"
            . UserScopedQuery::forTablePrepared('words', $ids)
            . UserScopedQuery::forTablePrepared('textitems2', $ids, '', 'texts');

        $records = Connection::preparedFetchAll($sql, $ids);
        $sentenceCount = (int) Settings::getWithDefault('set-term-sentence-count');

        foreach ($records as $record) {
            $sent = $this->sentenceService->formatSentence(
                $record['SeID'],
                $record['WoTextLC'],
                $sentenceCount
            );
            $bindings = [ExportService::replaceTabNewline($sent[1]), $record['WoID']];
            $count += Connection::preparedExecute(
                "UPDATE words SET WoSentence = ? WHERE WoID = ?"
                . UserScopedQuery::forTablePrepared('words', $bindings),
                $bindings
            );
        }

        return "Term Sentences set from Text(s): {$count}";
    }

    /**
     * Save text and reparse it (with additional return data).
     *
     * @param int    $textId    Text ID (0 for new)
     * @param int    $lgId      Language ID
     * @param string $title     Text title
     * @param string $text      Text content
     * @param string $audioUri  Audio URI
     * @param string $sourceUri Source URI
     *
     * @return array{message: string, textId: int, redirect: bool}
     */
    public function saveTextAndReparse(
        int $textId,
        int $lgId,
        string $title,
        string $text,
        string $audioUri,
        string $sourceUri
    ): array {
        $cleanText = str_replace("\xC2\xAD", "", $text);
        $audioValue = $audioUri === '' ? null : $audioUri;

        if ($textId === 0) {
            $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri];
            $textId = (int) Connection::preparedInsert(
                "INSERT INTO texts (
                    TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI"
                    . UserScopedQuery::insertColumn('texts')
                . ") VALUES (?, ?, ?, '', ?, ?"
                    . UserScopedQuery::insertValuePrepared('texts', $bindings1)
                . ")",
                $bindings1
            );
        } else {
            $bindings1 = [$lgId, $title, $cleanText, $audioValue, $sourceUri, $textId];
            Connection::preparedExecute(
                "UPDATE texts SET
                    TxLgID = ?, TxTitle = ?, TxText = ?, TxAudioURI = ?, TxSourceURI = ?
                 WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings1),
                $bindings1
            );
        }

        TagsFacade::saveTextTagsFromForm($textId);

        $sentencesDeleted = QueryBuilder::table('sentences')
            ->where('SeTxID', '=', $textId)
            ->delete();
        $textitemsDeleted = QueryBuilder::table('textitems2')
            ->where('Ti2TxID', '=', $textId)
            ->delete();
        Maintenance::adjustAutoIncrement('sentences', 'SeID');

        $bindings2 = [$textId];
        TextParsing::parseAndSave(
            Connection::preparedFetchValue(
                "SELECT TxText FROM texts WHERE TxID = ?"
                . UserScopedQuery::forTablePrepared('texts', $bindings2),
                $bindings2,
                'TxText'
            ),
            $lgId,
            $textId
        );

        $bindings3 = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings3, '', 'texts'),
            $bindings3,
            'cnt'
        );
        $bindings4 = [$textId];
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings4, '', 'texts'),
            $bindings4,
            'cnt'
        );

        $message = "Sentences deleted: {$sentencesDeleted} / Textitems deleted: {$textitemsDeleted} / Sentences added: {$sentenceCount} / Text items added: {$itemCount}";

        return [
            'message' => $message,
            'textId' => $textId,
            'redirect' => false
        ];
    }
}
