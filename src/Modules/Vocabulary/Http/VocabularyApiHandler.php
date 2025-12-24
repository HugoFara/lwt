<?php declare(strict_types=1);
/**
 * Vocabulary API Handler
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
use Lwt\Database\QueryBuilder;
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;
use Lwt\Services\TagService;

/**
 * Handler for vocabulary/term-related API operations.
 *
 * Provides a clean API layer for the Vocabulary module.
 *
 * @since 3.0.0
 */
class VocabularyApiHandler
{
    private VocabularyFacade $facade;
    private FindSimilarTerms $findSimilarTerms;
    private DictionaryAdapter $dictionaryAdapter;

    /**
     * Constructor.
     *
     * @param VocabularyFacade|null   $facade            Vocabulary facade
     * @param FindSimilarTerms|null   $findSimilarTerms  Find similar terms use case
     * @param DictionaryAdapter|null  $dictionaryAdapter Dictionary adapter
     */
    public function __construct(
        ?VocabularyFacade $facade = null,
        ?FindSimilarTerms $findSimilarTerms = null,
        ?DictionaryAdapter $dictionaryAdapter = null
    ) {
        $this->facade = $facade ?? new VocabularyFacade();
        $this->findSimilarTerms = $findSimilarTerms ?? new FindSimilarTerms();
        $this->dictionaryAdapter = $dictionaryAdapter ?? new DictionaryAdapter();
    }

    // =========================================================================
    // Term CRUD Operations
    // =========================================================================

    /**
     * Get a term by ID.
     *
     * @param int $termId Term ID
     *
     * @return array Term data or error
     */
    public function getTerm(int $termId): array
    {
        $term = $this->facade->getTerm($termId);

        if ($term === null) {
            return ['error' => 'Term not found'];
        }

        return [
            'id' => $term->id()->toInt(),
            'text' => $term->text(),
            'textLc' => $term->textLowercase(),
            'translation' => $term->translation(),
            'romanization' => $term->romanization(),
            'sentence' => $term->sentence(),
            'status' => $term->status()->toInt(),
            'statusLabel' => TermStatusService::getStatusName($term->status()->toInt()),
            'langId' => $term->languageId(),
            'wordCount' => $term->wordCount(),
        ];
    }

    /**
     * Create a new term.
     *
     * @param array $data Term data:
     *                    - langId: int Language ID
     *                    - text: string Term text
     *                    - status: int Status (1-5, 98, 99)
     *                    - translation: string Translation
     *                    - romanization: string Romanization (optional)
     *                    - sentence: string Example sentence (optional)
     *
     * @return array{success: bool, id?: int, textLc?: string, hex?: string, error?: string}
     */
    public function createTerm(array $data): array
    {
        $langId = (int) ($data['langId'] ?? 0);
        $text = trim($data['text'] ?? '');
        $status = (int) ($data['status'] ?? 1);
        $translation = trim($data['translation'] ?? '*');
        $romanization = trim($data['romanization'] ?? '');
        $sentence = trim($data['sentence'] ?? '');

        if ($langId === 0 || $text === '') {
            return ['success' => false, 'error' => 'Language ID and text are required'];
        }

        if (!TermStatusService::isValidStatus($status)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }

        try {
            $term = $this->facade->createTerm(
                $langId,
                $text,
                $status,
                $translation ?: '*',
                $romanization,
                $sentence
            );

            $textLc = $term->textLowercase();

            return [
                'success' => true,
                'id' => $term->id()->toInt(),
                'textLc' => $textLc,
                'hex' => StringUtils::toClassName($textLc),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update a term.
     *
     * @param int   $termId Term ID
     * @param array $data   Fields to update
     *
     * @return array{success: bool, error?: string}
     */
    public function updateTerm(int $termId, array $data): array
    {
        $term = $this->facade->getTerm($termId);
        if ($term === null) {
            return ['success' => false, 'error' => 'Term not found'];
        }

        $updates = [];
        if (isset($data['translation'])) {
            $updates['translation'] = trim($data['translation']) ?: '*';
        }
        if (isset($data['romanization'])) {
            $updates['romanization'] = trim($data['romanization']);
        }
        if (isset($data['sentence'])) {
            $updates['sentence'] = trim($data['sentence']);
        }

        try {
            $status = isset($data['status']) ? (int) $data['status'] : null;
            if ($status !== null && !TermStatusService::isValidStatus($status)) {
                $status = null;
            }

            $this->facade->updateTerm(
                $termId,
                $status,
                $updates['translation'] ?? null,
                $updates['sentence'] ?? null,
                null // notes
            );

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a term.
     *
     * @param int $termId Term ID
     *
     * @return array{deleted: bool, error?: string}
     */
    public function deleteTerm(int $termId): array
    {
        $term = $this->facade->getTerm($termId);
        if ($term === null) {
            return ['deleted' => false, 'error' => 'Term not found'];
        }

        $result = $this->facade->deleteTerm($termId);
        return ['deleted' => $result];
    }

    /**
     * Delete multiple terms.
     *
     * @param int[] $termIds Term IDs
     *
     * @return array{deleted: int, error?: string}
     */
    public function deleteTerms(array $termIds): array
    {
        if (empty($termIds)) {
            return ['deleted' => 0, 'error' => 'No term IDs provided'];
        }

        $count = $this->facade->deleteTerms($termIds);
        return ['deleted' => $count];
    }

    // =========================================================================
    // Status Operations
    // =========================================================================

    /**
     * Update term status.
     *
     * @param int $termId Term ID
     * @param int $status New status
     *
     * @return array{success: bool, status?: int, error?: string}
     */
    public function updateStatus(int $termId, int $status): array
    {
        if (!TermStatusService::isValidStatus($status)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }

        $result = $this->facade->updateStatus($termId, $status);

        if ($result) {
            return ['success' => true, 'status' => $status];
        }

        return ['success' => false, 'error' => 'Failed to update status'];
    }

    /**
     * Increment or decrement term status.
     *
     * @param int  $termId Term ID
     * @param bool $up     True to increment, false to decrement
     *
     * @return array{success: bool, status?: int, error?: string}
     */
    public function incrementStatus(int $termId, bool $up): array
    {
        $term = $this->facade->getTerm($termId);
        if ($term === null) {
            return ['success' => false, 'error' => 'Term not found'];
        }

        $result = $up
            ? $this->facade->advanceStatus($termId)
            : $this->facade->decreaseStatus($termId);

        if ($result) {
            // Fetch updated status
            $updatedTerm = $this->facade->getTerm($termId);
            $newStatus = $updatedTerm !== null ? $updatedTerm->status()->toInt() : 0;
            return ['success' => true, 'status' => $newStatus];
        }

        return ['success' => false, 'error' => 'Failed to update status'];
    }

    /**
     * Bulk update status for multiple terms.
     *
     * @param int[] $termIds Term IDs
     * @param int   $status  New status
     *
     * @return array{count: int, error?: string}
     */
    public function bulkUpdateStatus(array $termIds, int $status): array
    {
        if (!TermStatusService::isValidStatus($status)) {
            return ['count' => 0, 'error' => 'Invalid status'];
        }

        if (empty($termIds)) {
            return ['count' => 0, 'error' => 'No term IDs provided'];
        }

        $count = $this->facade->bulkUpdateStatus($termIds, $status);
        return ['count' => $count];
    }

    // =========================================================================
    // Similar Terms
    // =========================================================================

    /**
     * Get similar terms.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array{similar_terms: string}
     */
    public function getSimilarTerms(int $langId, string $term): array
    {
        return [
            'similar_terms' => $this->findSimilarTerms->getFormattedTerms($langId, $term)
        ];
    }

    // =========================================================================
    // Dictionary
    // =========================================================================

    /**
     * Get dictionary links for a term.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array Dictionary URLs
     */
    public function getDictionaryLinks(int $langId, string $term): array
    {
        $dicts = $this->dictionaryAdapter->getLanguageDictionaries($langId);

        return [
            'dict1' => $dicts['dict1'] !== ''
                ? DictionaryAdapter::createDictLink($dicts['dict1'], $term)
                : '',
            'dict2' => $dicts['dict2'] !== ''
                ? DictionaryAdapter::createDictLink($dicts['dict2'], $term)
                : '',
            'translator' => $dicts['translator'] !== ''
                ? DictionaryAdapter::createDictLink($dicts['translator'], $term)
                : '',
        ];
    }

    // =========================================================================
    // Tags
    // =========================================================================

    /**
     * Get tags for a term.
     *
     * @param int $termId Term ID
     *
     * @return array{tags: string[]}
     */
    public function getTermTags(int $termId): array
    {
        $tagsResult = QueryBuilder::table('wordtags')
            ->select(['tags.TgText'])
            ->join('tags', 'tags.TgID', '=', 'wordtags.WtTgID')
            ->where('wordtags.WtWoID', '=', $termId)
            ->orderBy('tags.TgText')
            ->getPrepared();

        $tags = array_map(fn($row) => (string)$row['TgText'], $tagsResult);
        return ['tags' => $tags];
    }

    /**
     * Set tags for a term.
     *
     * @param int      $termId Term ID
     * @param string[] $tags   Tag names
     *
     * @return array{success: bool}
     */
    public function setTermTags(int $termId, array $tags): array
    {
        TagService::saveWordTagsFromArray($termId, $tags);
        return ['success' => true];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for getting a term.
     *
     * @param int $termId Term ID
     *
     * @return array
     */
    public function formatGetTerm(int $termId): array
    {
        return $this->getTerm($termId);
    }

    /**
     * Format response for creating a term.
     *
     * @param array $data Term data
     *
     * @return array
     */
    public function formatCreateTerm(array $data): array
    {
        return $this->createTerm($data);
    }

    /**
     * Format response for updating a term.
     *
     * @param int   $termId Term ID
     * @param array $data   Term data
     *
     * @return array
     */
    public function formatUpdateTerm(int $termId, array $data): array
    {
        return $this->updateTerm($termId, $data);
    }

    /**
     * Format response for deleting a term.
     *
     * @param int $termId Term ID
     *
     * @return array
     */
    public function formatDeleteTerm(int $termId): array
    {
        return $this->deleteTerm($termId);
    }

    /**
     * Format response for updating status.
     *
     * @param int $termId Term ID
     * @param int $status New status
     *
     * @return array
     */
    public function formatUpdateStatus(int $termId, int $status): array
    {
        return $this->updateStatus($termId, $status);
    }

    /**
     * Format response for incrementing status.
     *
     * @param int  $termId Term ID
     * @param bool $up     True to increment, false to decrement
     *
     * @return array
     */
    public function formatIncrementStatus(int $termId, bool $up): array
    {
        return $this->incrementStatus($termId, $up);
    }

    /**
     * Format response for bulk status update.
     *
     * @param int[] $termIds Term IDs
     * @param int   $status  New status
     *
     * @return array
     */
    public function formatBulkUpdateStatus(array $termIds, int $status): array
    {
        return $this->bulkUpdateStatus($termIds, $status);
    }

    /**
     * Format response for similar terms.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array
     */
    public function formatSimilarTerms(int $langId, string $term): array
    {
        return $this->getSimilarTerms($langId, $term);
    }

    /**
     * Format response for dictionary links.
     *
     * @param int    $langId Language ID
     * @param string $term   Term text
     *
     * @return array
     */
    public function formatDictionaryLinks(int $langId, string $term): array
    {
        return $this->getDictionaryLinks($langId, $term);
    }

    /**
     * Format response for getting all statuses.
     *
     * @return array{statuses: array<int, array{abbr: string, name: string}>}
     */
    public function formatGetStatuses(): array
    {
        return ['statuses' => TermStatusService::getStatuses()];
    }
}
