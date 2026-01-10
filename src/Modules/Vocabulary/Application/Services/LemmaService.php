<?php declare(strict_types=1);
/**
 * Lemma Service
 *
 * Provides lemmatization functionality for vocabulary items.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Domain\LemmatizerInterface;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Infrastructure\Lemmatizers\DictionaryLemmatizer;
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

/**
 * Service for managing lemmatization of vocabulary items.
 *
 * Provides methods for:
 * - Suggesting lemmas for new words
 * - Batch lemmatization of existing vocabulary
 * - Word family queries
 *
 * @since 3.0.0
 */
class LemmaService
{
    private LemmatizerInterface $lemmatizer;
    private MySqlTermRepository $repository;

    /**
     * Constructor.
     *
     * @param LemmatizerInterface|null  $lemmatizer Lemmatizer implementation
     * @param MySqlTermRepository|null  $repository Term repository
     */
    public function __construct(
        ?LemmatizerInterface $lemmatizer = null,
        ?MySqlTermRepository $repository = null
    ) {
        $this->lemmatizer = $lemmatizer ?? new DictionaryLemmatizer();
        $this->repository = $repository ?? new MySqlTermRepository();
    }

    /**
     * Suggest a lemma for a word.
     *
     * @param string $word         The word to lemmatize
     * @param string $languageCode ISO language code (e.g., 'en', 'de')
     *
     * @return string|null The suggested lemma, or null if not found
     */
    public function suggestLemma(string $word, string $languageCode): ?string
    {
        if ($word === '' || $languageCode === '') {
            return null;
        }

        return $this->lemmatizer->lemmatize($word, $languageCode);
    }

    /**
     * Suggest lemmas for multiple words.
     *
     * @param string[] $words        Array of words
     * @param string   $languageCode ISO language code
     *
     * @return array<string, string|null> Word => lemma mapping
     */
    public function suggestLemmasBatch(array $words, string $languageCode): array
    {
        if (empty($words) || $languageCode === '') {
            return [];
        }

        return $this->lemmatizer->lemmatizeBatch($words, $languageCode);
    }

    /**
     * Check if lemmatization is available for a language.
     *
     * @param string $languageCode ISO language code
     *
     * @return bool True if lemmatization is available
     */
    public function isAvailableForLanguage(string $languageCode): bool
    {
        return $this->lemmatizer->supportsLanguage($languageCode);
    }

    /**
     * Get all languages with available lemmatization support.
     *
     * @return string[] Array of language codes
     */
    public function getAvailableLanguages(): array
    {
        return $this->lemmatizer->getSupportedLanguages();
    }

    /**
     * Get the word family (all words sharing a lemma).
     *
     * @param int    $languageId Language ID
     * @param string $lemmaLc    Lowercase lemma
     *
     * @return Term[] Array of terms in the word family
     */
    public function getWordFamily(int $languageId, string $lemmaLc): array
    {
        return $this->repository->findByLemma($languageId, $lemmaLc);
    }

    /**
     * Apply lemmas to existing vocabulary for a language.
     *
     * @param int    $languageId   Language ID
     * @param string $languageCode ISO language code for lemmatizer
     * @param int    $batchSize    Number of words to process per batch
     *
     * @return array{processed: int, updated: int, skipped: int}
     */
    public function applyLemmasToVocabulary(
        int $languageId,
        string $languageCode,
        int $batchSize = 100
    ): array {
        if (!$this->lemmatizer->supportsLanguage($languageCode)) {
            return ['processed' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $offset = 0;

        while (true) {
            // Fetch batch of terms without lemmas
            $terms = $this->fetchTermsWithoutLemma($languageId, $batchSize, $offset);

            if (empty($terms)) {
                break;
            }

            // Collect words for batch lemmatization
            /** @var array<int, string> $words */
            $words = [];
            foreach ($terms as $term) {
                $wordId = (int)($term['WoID'] ?? 0);
                $textLc = (string)($term['WoTextLC'] ?? '');
                $words[$wordId] = $textLc;
            }

            // Get lemmas for all words in batch
            $lemmas = $this->lemmatizer->lemmatizeBatch(array_values($words), $languageCode);

            // Update terms with found lemmas
            foreach ($terms as $term) {
                $stats['processed']++;
                $textLc = (string)($term['WoTextLC'] ?? '');
                $lemma = $lemmas[$textLc] ?? null;

                if ($lemma !== null) {
                    $this->updateTermLemma((int)($term['WoID'] ?? 0), $lemma);
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }
            }

            $offset += $batchSize;

            // Safety limit to prevent infinite loops
            if ($offset > 100000) {
                break;
            }
        }

        return $stats;
    }

    /**
     * Fetch terms without a lemma.
     *
     * @param int $languageId Language ID
     * @param int $limit      Maximum number to fetch
     * @param int $offset     Starting offset
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchTermsWithoutLemma(int $languageId, int $limit, int $offset): array
    {
        $bindings = [$languageId, $limit, $offset];

        /** @var array<int, array<string, mixed>> */
        return Connection::preparedFetchAll(
            "SELECT WoID, WoText, WoTextLC
             FROM words
             WHERE WoLgID = ?
               AND WoWordCount = 1
               AND (WoLemma IS NULL OR WoLemma = '')
             ORDER BY WoID
             LIMIT ? OFFSET ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );
    }

    /**
     * Update the lemma for a term.
     *
     * @param int    $termId Term ID
     * @param string $lemma  The lemma to set
     *
     * @return bool True if updated
     */
    private function updateTermLemma(int $termId, string $lemma): bool
    {
        $lemmaLc = mb_strtolower($lemma, 'UTF-8');
        $bindings = [$lemma, $lemmaLc, $termId];

        $affected = Connection::preparedExecute(
            "UPDATE words SET WoLemma = ?, WoLemmaLC = ? WHERE WoID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );

        return $affected > 0;
    }

    /**
     * Get lemma statistics for a language.
     *
     * @param int $languageId Language ID
     *
     * @return array{total_terms: int, with_lemma: int, without_lemma: int, unique_lemmas: int}
     */
    public function getLemmaStatistics(int $languageId): array
    {
        $bindings = [$languageId];

        $total = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) as cnt FROM words WHERE WoLgID = ? AND WoWordCount = 1"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'cnt'
        );

        $bindings = [$languageId];
        $withLemma = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) as cnt FROM words
             WHERE WoLgID = ? AND WoWordCount = 1 AND WoLemma IS NOT NULL AND WoLemma != ''"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'cnt'
        );

        $bindings = [$languageId];
        $uniqueLemmas = (int) Connection::preparedFetchValue(
            "SELECT COUNT(DISTINCT WoLemmaLC) as cnt FROM words
             WHERE WoLgID = ? AND WoWordCount = 1 AND WoLemmaLC IS NOT NULL AND WoLemmaLC != ''"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings,
            'cnt'
        );

        return [
            'total_terms' => $total,
            'with_lemma' => $withLemma,
            'without_lemma' => $total - $withLemma,
            'unique_lemmas' => $uniqueLemmas,
        ];
    }

    /**
     * Clear all lemmas for a language.
     *
     * @param int $languageId Language ID
     *
     * @return int Number of terms affected
     */
    public function clearLemmas(int $languageId): int
    {
        $bindings = [$languageId];

        return Connection::preparedExecute(
            "UPDATE words SET WoLemma = NULL, WoLemmaLC = NULL WHERE WoLgID = ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );
    }

    /**
     * Get words grouped by their lemma.
     *
     * @param int $languageId Language ID
     * @param int $limit      Maximum number of lemma groups to return
     *
     * @return array<string, array{lemma: string, count: int, terms: string[]}>
     */
    public function getWordFamilies(int $languageId, int $limit = 50): array
    {
        $bindings = [$languageId, $limit];

        $groups = Connection::preparedFetchAll(
            "SELECT WoLemma, WoLemmaLC, COUNT(*) as family_size,
                    GROUP_CONCAT(WoText ORDER BY WoText SEPARATOR ', ') as terms
             FROM words
             WHERE WoLgID = ?
               AND WoLemmaLC IS NOT NULL
               AND WoLemmaLC != ''
             GROUP BY WoLemmaLC
             HAVING family_size > 1
             ORDER BY family_size DESC, WoLemma
             LIMIT ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            $bindings
        );

        $result = [];
        foreach ($groups as $group) {
            $lemmaLc = (string) $group['WoLemmaLC'];
            $result[$lemmaLc] = [
                'lemma' => (string) $group['WoLemma'],
                'count' => (int) $group['family_size'],
                'terms' => explode(', ', (string) $group['terms']),
            ];
        }

        return $result;
    }

    /**
     * Find terms that might benefit from lemmatization.
     *
     * Identifies terms with similar text that could share a lemma.
     *
     * @param int $languageId Language ID
     * @param int $limit      Maximum suggestions
     *
     * @return array<int, array{base: string, variants: string[]}>
     */
    public function findPotentialLemmaGroups(int $languageId, int $limit = 20): array
    {
        // Find words without lemma that share common prefixes
        $bindings = [$languageId, $languageId, $limit];

        $results = Connection::preparedFetchAll(
            "SELECT w1.WoText as base_word, w1.WoTextLC as base_lc,
                    GROUP_CONCAT(DISTINCT w2.WoText ORDER BY w2.WoText SEPARATOR ', ') as variants
             FROM words w1
             JOIN words w2 ON w2.WoLgID = w1.WoLgID
                          AND w2.WoID != w1.WoID
                          AND LEFT(w2.WoTextLC, CHAR_LENGTH(w1.WoTextLC)) = w1.WoTextLC
                          AND CHAR_LENGTH(w2.WoTextLC) > CHAR_LENGTH(w1.WoTextLC)
                          AND CHAR_LENGTH(w2.WoTextLC) <= CHAR_LENGTH(w1.WoTextLC) + 5
             WHERE w1.WoLgID = ?
               AND w1.WoWordCount = 1
               AND w1.WoLemma IS NULL
               AND w2.WoLemma IS NULL
               AND w2.WoLgID = ?
               AND w2.WoWordCount = 1
             GROUP BY w1.WoID
             HAVING COUNT(DISTINCT w2.WoID) >= 1
             ORDER BY COUNT(DISTINCT w2.WoID) DESC
             LIMIT ?"
            . UserScopedQuery::forTablePrepared('words', $bindings, 'w1'),
            $bindings
        );

        $suggestions = [];
        foreach ($results as $row) {
            $suggestions[] = [
                'base' => (string) $row['base_word'],
                'variants' => explode(', ', (string) $row['variants']),
            ];
        }

        return $suggestions;
    }

    /**
     * Set lemma for a specific term.
     *
     * @param int    $termId Term ID
     * @param string $lemma  The lemma to set
     *
     * @return bool True if updated
     */
    public function setLemma(int $termId, string $lemma): bool
    {
        return $this->repository->updateLemma($termId, $lemma);
    }

    /**
     * Copy lemma from one term to all related terms.
     *
     * When a user sets a lemma for "running", this can propagate
     * the lemma "run" to other forms like "runs", "ran" if they
     * match the lemmatizer's suggestions.
     *
     * @param int    $termId       Source term ID
     * @param int    $languageId   Language ID
     * @param string $languageCode Language code for lemmatizer
     *
     * @return int Number of terms updated
     */
    public function propagateLemma(int $termId, int $languageId, string $languageCode): int
    {
        $term = $this->repository->find($termId);
        if ($term === null) {
            return 0;
        }

        $lemma = $term->lemma();
        $lemmaLc = $term->lemmaLc();

        if ($lemma === null || $lemmaLc === null) {
            return 0;
        }

        // Find other terms in the same language without a lemma
        // that the lemmatizer suggests should have the same lemma
        $bindings = [$languageId];
        $candidates = Connection::preparedFetchAll(
            "SELECT WoID, WoTextLC FROM words
             WHERE WoLgID = ?
               AND WoWordCount = 1
               AND (WoLemma IS NULL OR WoLemma = '')
               AND WoID != ?"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            array_merge($bindings, [$termId])
        );

        $updated = 0;
        foreach ($candidates as $candidate) {
            $wordText = (string)($candidate['WoTextLC'] ?? '');
            $suggestedLemma = $this->lemmatizer->lemmatize($wordText, $languageCode);
            if ($suggestedLemma !== null && mb_strtolower($suggestedLemma, 'UTF-8') === $lemmaLc) {
                $this->updateTermLemma((int)($candidate['WoID'] ?? 0), $lemma);
                $updated++;
            }
        }

        return $updated;
    }

    // =========================================================================
    // Phase 4: Smart Matching - Lemma-aware text item linking
    // =========================================================================

    /**
     * Link unmatched text items to words by lemma.
     *
     * When a text item doesn't have an exact word match (Ti2WoID IS NULL),
     * this method tries to find a word whose lemma matches the text item's
     * lemmatized form.
     *
     * Example: Text item "runs" with no exact match → lemmatize to "run"
     * → find word with WoLemmaLC = "run" → link text item to that word
     *
     * @param int    $languageId   Language ID
     * @param string $languageCode ISO language code for lemmatizer
     * @param int|null $textId     Optional: limit to specific text
     *
     * @return array{linked: int, unmatched: int, errors: int}
     */
    public function linkTextItemsByLemma(
        int $languageId,
        string $languageCode,
        ?int $textId = null
    ): array {
        if (!$this->lemmatizer->supportsLanguage($languageCode)) {
            return ['linked' => 0, 'unmatched' => 0, 'errors' => 0];
        }

        $stats = ['linked' => 0, 'unmatched' => 0, 'errors' => 0];

        // Get unmatched single-word text items
        $unmatchedItems = $this->fetchUnmatchedTextItems($languageId, $textId);

        if (empty($unmatchedItems)) {
            return $stats;
        }

        // Group items by their lowercase text for efficient processing
        $itemsByText = [];
        foreach ($unmatchedItems as $item) {
            $textLc = (string)($item['Ti2TextLC'] ?? mb_strtolower((string)$item['Ti2Text'], 'UTF-8'));
            if (!isset($itemsByText[$textLc])) {
                $itemsByText[$textLc] = [];
            }
            $itemsByText[$textLc][] = $item;
        }

        // Batch lemmatize all unique words
        $lemmas = $this->lemmatizer->lemmatizeBatch(array_keys($itemsByText), $languageCode);

        // Find words by lemma and link
        foreach ($itemsByText as $textLc => $items) {
            $lemmaLc = $lemmas[$textLc] ?? null;

            if ($lemmaLc === null) {
                // Can't lemmatize this word - try matching text directly to lemma
                $lemmaLc = $textLc;
            }

            $lemmaLc = mb_strtolower($lemmaLc, 'UTF-8');

            // Find a word with this lemma
            $wordId = $this->findWordIdByLemma($languageId, $lemmaLc);

            if ($wordId !== null) {
                // Link all text items with this text to the found word
                $linkedCount = $this->linkItemsToWord($items, $wordId);
                $stats['linked'] += $linkedCount;
            } else {
                $stats['unmatched'] += count($items);
            }
        }

        return $stats;
    }

    /**
     * Fetch unmatched text items (Ti2WoID IS NULL) for a language.
     *
     * @param int      $languageId Language ID
     * @param int|null $textId     Optional text ID filter
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchUnmatchedTextItems(int $languageId, ?int $textId = null): array
    {
        $sql = "SELECT ti.Ti2ID, ti.Ti2Text, LOWER(ti.Ti2Text) as Ti2TextLC, ti.Ti2TxID
                FROM textitems2 ti
                WHERE ti.Ti2LgID = ?
                  AND ti.Ti2WoID IS NULL
                  AND ti.Ti2WordCount = 1";
        $bindings = [$languageId];

        if ($textId !== null) {
            $sql .= " AND ti.Ti2TxID = ?";
            $bindings[] = $textId;
        }

        $sql .= " ORDER BY ti.Ti2Text";

        return Connection::preparedFetchAll($sql, $bindings);
    }

    /**
     * Find a word ID by its lemma.
     *
     * Returns the word that has this lemma (preferring the base form).
     *
     * @param int    $languageId Language ID
     * @param string $lemmaLc    Lowercase lemma to match
     *
     * @return int|null Word ID or null if not found
     */
    public function findWordIdByLemma(int $languageId, string $lemmaLc): ?int
    {
        $bindings = [$languageId, $lemmaLc, $lemmaLc];

        // Prefer words where WoTextLC equals the lemma (base form),
        // otherwise any word with matching lemma
        $row = Connection::preparedFetchOne(
            "SELECT WoID FROM words
             WHERE WoLgID = ?
               AND WoWordCount = 1
               AND (WoLemmaLC = ? OR WoTextLC = ?)
             ORDER BY CASE WHEN WoTextLC = ? THEN 0 ELSE 1 END, WoID
             LIMIT 1"
            . UserScopedQuery::forTablePrepared('words', $bindings),
            array_merge($bindings, [$lemmaLc])
        );

        return $row !== null ? (int)$row['WoID'] : null;
    }

    /**
     * Link text items to a word.
     *
     * @param array<int, array<string, mixed>> $items  Text items to link
     * @param int                              $wordId Word ID to link to
     *
     * @return int Number of items linked
     */
    private function linkItemsToWord(array $items, int $wordId): int
    {
        if (empty($items)) {
            return 0;
        }

        $itemIds = array_map(
            fn(array $item) => (int)($item['Ti2ID'] ?? 0),
            $items
        );
        $itemIds = array_filter($itemIds, fn(int $id) => $id > 0);

        if (empty($itemIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $bindings = array_merge([$wordId], $itemIds);

        return Connection::preparedExecute(
            "UPDATE textitems2 SET Ti2WoID = ? WHERE Ti2ID IN ({$placeholders})",
            $bindings
        );
    }

    /**
     * Get statistics about unmatched text items that could benefit from lemma linking.
     *
     * @param int $languageId Language ID
     *
     * @return array{unmatched_count: int, unique_words: int, matchable_by_lemma: int}
     */
    public function getUnmatchedStatistics(int $languageId): array
    {
        $bindings = [$languageId];

        // Count unmatched items
        $unmatchedCount = (int) Connection::preparedFetchValue(
            "SELECT COUNT(*) as cnt FROM textitems2
             WHERE Ti2LgID = ? AND Ti2WoID IS NULL AND Ti2WordCount = 1",
            $bindings,
            'cnt'
        );

        // Count unique unmatched words
        $bindings = [$languageId];
        $uniqueWords = (int) Connection::preparedFetchValue(
            "SELECT COUNT(DISTINCT LOWER(Ti2Text)) as cnt FROM textitems2
             WHERE Ti2LgID = ? AND Ti2WoID IS NULL AND Ti2WordCount = 1",
            $bindings,
            'cnt'
        );

        // Count how many unique unmatched words have a potential lemma match
        $bindings = [$languageId, $languageId];
        $matchableByLemma = (int) Connection::preparedFetchValue(
            "SELECT COUNT(DISTINCT LOWER(ti.Ti2Text)) as cnt
             FROM textitems2 ti
             JOIN words w ON w.WoLgID = ? AND LOWER(ti.Ti2Text) = w.WoLemmaLC
             WHERE ti.Ti2LgID = ?
               AND ti.Ti2WoID IS NULL
               AND ti.Ti2WordCount = 1
               AND w.WoWordCount = 1"
            . UserScopedQuery::forTablePrepared('words', $bindings, 'w'),
            $bindings,
            'cnt'
        );

        return [
            'unmatched_count' => $unmatchedCount,
            'unique_words' => $uniqueWords,
            'matchable_by_lemma' => $matchableByLemma,
        ];
    }

    /**
     * Link text items directly using SQL (efficient for large datasets).
     *
     * This method links text items to words where the text item's lowercase text
     * matches a word's lemma. It's more efficient than the PHP-based approach
     * for large datasets.
     *
     * @param int      $languageId Language ID
     * @param int|null $textId     Optional text ID filter
     *
     * @return int Number of text items linked
     */
    public function linkTextItemsByLemmaSql(int $languageId, ?int $textId = null): int
    {
        // This uses a subquery to find the best matching word for each unmatched text item
        // Preference: exact text match > lemma match > first word with matching lemma
        $sql = "UPDATE textitems2 ti
                JOIN (
                    SELECT ti2.Ti2ID,
                           (SELECT w.WoID FROM words w
                            WHERE w.WoLgID = ?
                              AND w.WoWordCount = 1
                              AND (w.WoLemmaLC = LOWER(ti2.Ti2Text) OR w.WoTextLC = LOWER(ti2.Ti2Text))
                            ORDER BY CASE
                                WHEN w.WoTextLC = LOWER(ti2.Ti2Text) THEN 0
                                WHEN w.WoLemmaLC = LOWER(ti2.Ti2Text) THEN 1
                                ELSE 2
                            END, w.WoID
                            LIMIT 1
                           ) as MatchedWoID
                    FROM textitems2 ti2
                    WHERE ti2.Ti2LgID = ?
                      AND ti2.Ti2WoID IS NULL
                      AND ti2.Ti2WordCount = 1";

        $bindings = [$languageId, $languageId];

        if ($textId !== null) {
            $sql .= " AND ti2.Ti2TxID = ?";
            $bindings[] = $textId;
        }

        $sql .= ") AS matches ON ti.Ti2ID = matches.Ti2ID
                SET ti.Ti2WoID = matches.MatchedWoID
                WHERE matches.MatchedWoID IS NOT NULL";

        return Connection::preparedExecute($sql, $bindings);
    }
}
