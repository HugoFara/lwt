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
}
