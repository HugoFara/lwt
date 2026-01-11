<?php

declare(strict_types=1);

/**
 * Text Scoring Service - Comprehensibility and difficulty scoring.
 *
 * Calculates how readable a text is for a user based on their vocabulary knowledge.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Text\Application\Services;

use Lwt\Modules\Text\Domain\TextScore;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;

/**
 * Service for calculating text difficulty scores.
 *
 * Analyzes texts against the user's known vocabulary to determine
 * comprehensibility - useful for recommending appropriate reading material.
 *
 * @since 3.0.0
 */
class TextScoringService
{
    /**
     * Calculate the difficulty score for a single text.
     *
     * @param int $textId            The text ID to score
     * @param int $unknownWordsLimit Maximum unknown words to return in preview
     *
     * @return TextScore The calculated score
     */
    public function scoreText(int $textId, int $unknownWordsLimit = 20): TextScore
    {
        $stats = $this->calculateVocabularyStats($textId);
        $unknownWordsList = [];

        if ($stats['unknown'] > 0) {
            $unknownWordsList = $this->getUnknownWords($textId, $unknownWordsLimit);
        }

        return new TextScore(
            textId: $textId,
            totalUniqueWords: $stats['total'],
            knownWords: $stats['known'],
            learningWords: $stats['learning'],
            unknownWords: $stats['unknown'],
            unknownWordsList: $unknownWordsList
        );
    }

    /**
     * Score multiple texts at once (for listing/recommendations).
     *
     * @param int[] $textIds Array of text IDs to score
     *
     * @return array<int, TextScore> Map of textId => TextScore
     */
    public function scoreTexts(array $textIds): array
    {
        if (empty($textIds)) {
            return [];
        }

        $scores = [];
        $statsMap = $this->calculateVocabularyStatsForTexts($textIds);

        foreach ($textIds as $textId) {
            $stats = $statsMap[$textId] ?? [
                'total' => 0,
                'known' => 0,
                'learning' => 0,
                'unknown' => 0
            ];

            $scores[$textId] = new TextScore(
                textId: $textId,
                totalUniqueWords: $stats['total'],
                knownWords: $stats['known'],
                learningWords: $stats['learning'],
                unknownWords: $stats['unknown'],
                unknownWordsList: [] // Skip word list for bulk scoring
            );
        }

        return $scores;
    }

    /**
     * Get texts recommended for reading based on comprehensibility.
     *
     * Returns texts ordered by proximity to optimal comprehensibility (95%).
     *
     * @param int   $languageId              The language to filter by
     * @param float $targetComprehensibility Target comprehensibility (default 0.95)
     * @param int   $limit                   Maximum number of texts to return
     *
     * @return TextScore[] Array of TextScore objects, best matches first
     */
    public function getRecommendedTexts(
        int $languageId,
        float $targetComprehensibility = 0.95,
        int $limit = 10
    ): array {
        // Get all text IDs for this language
        $bindings = [$languageId];
        $sql = "SELECT TxID FROM texts WHERE TxLgID = ?"
            . UserScopedQuery::forTablePrepared('texts', $bindings, 'texts');

        $rows = Connection::preparedFetchAll($sql, $bindings);
        $textIds = array_map(
            fn(array $row): int => (int) $row['TxID'],
            $rows
        );

        if (empty($textIds)) {
            return [];
        }

        // Score all texts
        $scores = $this->scoreTexts($textIds);

        // Sort by proximity to target comprehensibility
        usort(
            $scores,
            function (TextScore $a, TextScore $b) use ($targetComprehensibility): int {
                $diffA = abs($a->comprehensibility() - $targetComprehensibility);
                $diffB = abs($b->comprehensibility() - $targetComprehensibility);
                return $diffA <=> $diffB;
            }
        );

        // Return top N
        return array_slice($scores, 0, $limit);
    }

    /**
     * Calculate vocabulary statistics for a single text.
     *
     * @param int $textId The text ID
     *
     * @return array{total: int, known: int, learning: int, unknown: int}
     */
    private function calculateVocabularyStats(int $textId): array
    {
        $stats = [
            'total' => 0,
            'known' => 0,
            'learning' => 0,
            'unknown' => 0
        ];

        // Count total unique words in text
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        $totalQuery = "SELECT COUNT(DISTINCT LOWER(Ti2Text)) AS cnt
            FROM word_occurrences
            WHERE Ti2WordCount = 1 AND Ti2TxID = ?";

        /**
 * @var int|string|null $total
*/
        $total = Connection::preparedFetchValue($totalQuery, [$textId], 'cnt');
        $stats['total'] = $total !== null ? (int) $total : 0;

        // Count unknown words (not in vocabulary)
        $unknownQuery = "SELECT COUNT(DISTINCT LOWER(Ti2Text)) AS cnt
            FROM word_occurrences
            WHERE Ti2WordCount = 1 AND Ti2WoID IS NULL AND Ti2TxID = ?";

        /**
 * @var int|string|null $unknown
*/
        $unknown = Connection::preparedFetchValue($unknownQuery, [$textId], 'cnt');
        $stats['unknown'] = $unknown !== null ? (int) $unknown : 0;

        // Count words by status (known vs learning)
        // Note: textIds must be integers so direct interpolation is safe
        $bindings = [];
        $statusQuery = "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS unique_cnt, WoStatus AS status
            FROM word_occurrences, words
            WHERE Ti2WoID IS NOT NULL AND Ti2TxID = {$textId} AND Ti2WoID = WoID"
            . UserScopedQuery::forTablePrepared('words', $bindings, 'words')
            . " GROUP BY Ti2TxID, WoStatus";

        $rows = Connection::preparedFetchAll($statusQuery, $bindings);
        foreach ($rows as $row) {
            $status = (int) $row['status'];
            $count = (int) $row['unique_cnt'];

            if ($status === TermStatus::LEARNED || $status === TermStatus::WELL_KNOWN) {
                $stats['known'] += $count;
            } elseif ($status >= TermStatus::NEW && $status <= TermStatus::LEARNING_4) {
                $stats['learning'] += $count;
            }
            // Ignored words (98) are counted but not classified
        }

        return $stats;
    }

    /**
     * Calculate vocabulary statistics for multiple texts.
     *
     * @param int[] $textIds Array of text IDs
     *
     * @return array<int, array{total: int, known: int, learning: int, unknown: int}>
     */
    private function calculateVocabularyStatsForTexts(array $textIds): array
    {
        /**
 * @var array<int, array{total: int, known: int, learning: int, unknown: int}> $results
*/
        $results = [];
        foreach ($textIds as $textId) {
            $results[$textId] = [
                'total' => 0,
                'known' => 0,
                'learning' => 0,
                'unknown' => 0
            ];
        }

        if (empty($textIds)) {
            return $results;
        }

        // Build IN clause with integer IDs (safe for SQL)
        $inClause = implode(',', array_map('intval', $textIds));

        // Count total unique words per text
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        $totalQuery = "SELECT Ti2TxID AS text, COUNT(DISTINCT LOWER(Ti2Text)) AS cnt
            FROM word_occurrences
            WHERE Ti2WordCount = 1 AND Ti2TxID IN ($inClause)
            GROUP BY Ti2TxID";

        $rows = Connection::fetchAll($totalQuery);
        foreach ($rows as $row) {
            $textId = (int) $row['text'];
            if (isset($results[$textId])) {
                $results[$textId]['total'] = (int) $row['cnt'];
            }
        }

        // Count unknown words per text
        $unknownQuery = "SELECT Ti2TxID AS text, COUNT(DISTINCT LOWER(Ti2Text)) AS cnt
            FROM word_occurrences
            WHERE Ti2WordCount = 1 AND Ti2WoID IS NULL AND Ti2TxID IN ($inClause)
            GROUP BY Ti2TxID";

        $rows = Connection::fetchAll($unknownQuery);
        foreach ($rows as $row) {
            $textId = (int) $row['text'];
            if (isset($results[$textId])) {
                $results[$textId]['unknown'] = (int) $row['cnt'];
            }
        }

        // Count words by status
        // Note: Using raw query with integer IDs which is safe
        $bindings = [];
        $statusQuery = "SELECT Ti2TxID AS text, COUNT(DISTINCT Ti2WoID) AS unique_cnt, WoStatus AS status
            FROM word_occurrences, words
            WHERE Ti2WoID IS NOT NULL AND Ti2TxID IN ({$inClause}) AND Ti2WoID = WoID"
            . UserScopedQuery::forTablePrepared('words', $bindings, 'words')
            . " GROUP BY Ti2TxID, WoStatus";

        $rows = Connection::preparedFetchAll($statusQuery, $bindings);
        foreach ($rows as $row) {
            $textId = (int) $row['text'];
            $status = (int) $row['status'];
            $count = (int) $row['unique_cnt'];

            if (!isset($results[$textId])) {
                continue;
            }

            if ($status === TermStatus::LEARNED || $status === TermStatus::WELL_KNOWN) {
                $results[$textId]['known'] += $count;
            } elseif ($status >= TermStatus::NEW && $status <= TermStatus::LEARNING_4) {
                $results[$textId]['learning'] += $count;
            }
        }

        return $results;
    }

    /**
     * Get the list of unknown words in a text.
     *
     * @param int $textId The text ID
     * @param int $limit  Maximum number of words to return
     *
     * @return string[] Array of unknown word texts
     */
    private function getUnknownWords(int $textId, int $limit): array
    {
        // Get unique unknown words, ordered by frequency in text (most common first)
        // word_occurrences inherits user context via Ti2TxID -> texts FK
        $sql = "SELECT LOWER(Ti2Text) AS word, COUNT(*) AS freq
            FROM word_occurrences
            WHERE Ti2WordCount = 1 AND Ti2WoID IS NULL AND Ti2TxID = ?
            GROUP BY LOWER(Ti2Text)
            ORDER BY freq DESC, word ASC
            LIMIT ?";

        $rows = Connection::preparedFetchAll($sql, [$textId, $limit]);

        return array_map(
            fn(array $row): string => (string) $row['word'],
            $rows
        );
    }
}
