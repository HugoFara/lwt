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
        ?UpdateText $updateText = null
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
    public function buildArchivedTagHavingClause($tag1, $tag2, string $tag12): string
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
    public function buildTextTagHavingClause($tag1, $tag2, string $tag12): string
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
}
