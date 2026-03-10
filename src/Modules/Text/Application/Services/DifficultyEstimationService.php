<?php

/**
 * Text Difficulty Estimation Service
 *
 * Provides two tiers of difficulty estimation for Gutenberg texts:
 * 1. Quick tier: heuristic based on user vocabulary size + subject categories
 * 2. Accurate coverage: samples text and computes known-word percentage
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Text\Application\Services;

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Shared\Infrastructure\Http\WebPageExtractor;

/**
 * Estimates text difficulty relative to a user's known vocabulary.
 *
 * @since 3.0.0
 */
class DifficultyEstimationService
{
    /**
     * Maximum number of words to sample for accurate coverage.
     */
    private const SAMPLE_WORD_COUNT = 2000;

    /**
     * Maximum number of words per vocabulary lookup batch.
     */
    private const LOOKUP_BATCH_SIZE = 500;

    /**
     * Subject keywords that indicate easier texts.
     *
     * @var list<string>
     */
    private const EASY_SUBJECTS = [
        'children',
        'juvenile',
        'fairy tale',
        'nursery',
        'picture book',
        'fable',
        'primer',
        'easy reading',
    ];

    /**
     * Subject keywords that indicate harder texts.
     *
     * @var list<string>
     */
    private const HARD_SUBJECTS = [
        'philosophy',
        'science',
        'law',
        'economics',
        'political science',
        'mathematics',
        'psychology',
        'theology',
        'metaphysics',
        'logic',
        'jurisprudence',
        'historiography',
    ];

    /**
     * Estimate quick difficulty tiers for a batch of books.
     *
     * Performs a single DB query for vocabulary size, then classifies
     * each book based on its subjects.
     *
     * @param int                        $languageId    Language ID
     * @param array<int, list<string>>   $booksSubjects Map of bookId => subjects
     *
     * @return array<int, string> Map of bookId => 'easy'|'medium'|'hard'
     */
    public function estimateQuickTiers(int $languageId, array $booksSubjects): array
    {
        $knownCount = $this->getKnownWordCount($languageId);

        $tiers = [];
        foreach ($booksSubjects as $bookId => $subjects) {
            $tiers[$bookId] = $this->computeQuickTier($knownCount, $subjects);
        }

        return $tiers;
    }

    /**
     * Analyze a text sample for accurate vocabulary coverage.
     *
     * Fetches the text, extracts a sample, tokenizes it, and computes
     * the percentage of unique words the user already knows.
     *
     * @param string $textUrl    URL of the plain text
     * @param int    $languageId Language ID (for tokenization and vocab lookup)
     *
     * @return array{
     *     total_unique_words: int,
     *     known_words: int,
     *     unknown_words: int,
     *     coverage_percent: float,
     *     difficulty_label: string,
     *     sample_unknown_words: list<string>
     * }|array{error: string}
     */
    public function analyzeTextSample(string $textUrl, int $languageId): array
    {
        // Fetch text via WebPageExtractor (reuses SSRF protection + boilerplate strip)
        $extractor = new WebPageExtractor();
        $result = $extractor->extractFromUrl($textUrl);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $text = $result['text'] ?? '';
        if ($text === '') {
            return ['error' => 'No text content could be extracted.'];
        }

        // Get word character regex for this language
        $wordRegex = $this->getWordCharRegex($languageId);
        if ($wordRegex === null) {
            return ['error' => 'Language not found.'];
        }

        // Tokenize and sample
        $tokens = $this->tokenize($text, $wordRegex, self::SAMPLE_WORD_COUNT);
        $uniqueWords = array_unique(array_map('mb_strtolower', $tokens));
        $uniqueWords = array_values($uniqueWords);

        $totalUnique = count($uniqueWords);
        if ($totalUnique === 0) {
            return ['error' => 'No words could be extracted from the text sample.'];
        }

        // Look up which words the user knows
        $knownSet = $this->lookupKnownWords($languageId, $uniqueWords);
        $knownCount = count($knownSet);
        $unknownCount = $totalUnique - $knownCount;
        $coveragePercent = round(($knownCount / $totalUnique) * 100, 1);

        // Collect a sample of unknown words
        $unknownWords = array_values(array_diff($uniqueWords, $knownSet));
        $sampleUnknown = array_slice($unknownWords, 0, 20);

        return [
            'total_unique_words' => $totalUnique,
            'known_words' => $knownCount,
            'unknown_words' => $unknownCount,
            'coverage_percent' => $coveragePercent,
            'difficulty_label' => $this->labelFromCoverage($coveragePercent),
            'sample_unknown_words' => $sampleUnknown,
        ];
    }

    /**
     * Count words the user knows for a language.
     *
     * "Known" = status 5 (learned), 98 (ignored), 99 (well-known).
     *
     * @param int $languageId Language ID
     *
     * @return int Number of known words
     */
    private function getKnownWordCount(int $languageId): int
    {
        $bindings = [$languageId];
        $sql = "SELECT COUNT(*) FROM words
                WHERE WoLgID = ? AND WoStatus IN (5, 98, 99)"
            . UserScopedQuery::forTablePrepared('words', $bindings);

        /** @var int|string|false $count */
        $count = Connection::preparedFetchValue($sql, $bindings);

        return (int) $count;
    }

    /**
     * Compute quick difficulty tier from vocabulary size and subjects.
     *
     * @param int          $knownCount Number of known words
     * @param list<string> $subjects   Gutenberg subject categories
     *
     * @return string 'easy'|'medium'|'hard'
     */
    private function computeQuickTier(int $knownCount, array $subjects): string
    {
        $subjectTier = $this->classifySubjects($subjects);

        // Shift tier based on vocabulary size
        if ($knownCount === 0) {
            return 'hard';
        }

        if ($knownCount < 500) {
            // Shift up: easy→medium, medium→hard, hard→hard
            return match ($subjectTier) {
                'easy' => 'medium',
                default => 'hard',
            };
        }

        if ($knownCount > 2000) {
            // Shift down: hard→medium, medium→easy, easy→easy
            return match ($subjectTier) {
                'hard' => 'medium',
                default => 'easy',
            };
        }

        // 500–2000 words: use subject tier directly
        return $subjectTier;
    }

    /**
     * Classify subjects into a difficulty tier (public API).
     *
     * @param list<string> $subjects Subject categories
     *
     * @return string 'easy'|'medium'|'hard'
     */
    public function classifySubjectsPublic(array $subjects): string
    {
        return $this->classifySubjects($subjects);
    }

    /**
     * Classify subject list into a difficulty tier.
     *
     * Picks the most favorable (lowest difficulty) match.
     *
     * @param list<string> $subjects Subject categories
     *
     * @return string 'easy'|'medium'|'hard'
     */
    private function classifySubjects(array $subjects): string
    {
        $subjectsLower = implode(' | ', array_map('strtolower', $subjects));

        foreach (self::EASY_SUBJECTS as $keyword) {
            if (str_contains($subjectsLower, $keyword)) {
                return 'easy';
            }
        }

        foreach (self::HARD_SUBJECTS as $keyword) {
            if (str_contains($subjectsLower, $keyword)) {
                return 'hard';
            }
        }

        return 'medium';
    }

    /**
     * Get the word character regex for a language.
     *
     * @param int $languageId Language ID
     *
     * @return string|null Regex character class content, or null if not found
     */
    private function getWordCharRegex(int $languageId): ?string
    {
        $row = QueryBuilder::table('languages')
            ->select(['LgRegexpWordCharacters'])
            ->where('LgID', '=', $languageId)
            ->firstPrepared();

        if ($row === null) {
            return null;
        }

        $regex = (string) ($row['LgRegexpWordCharacters'] ?? '');

        // Default fallback: Unicode letters and combining marks
        return $regex !== '' ? $regex : '\\w';
    }

    /**
     * Tokenize text into words using the language's word character regex.
     *
     * @param string $text      Text to tokenize
     * @param string $wordRegex Word character regex class content
     * @param int    $maxWords  Maximum number of words to return
     *
     * @return list<string> Word tokens
     */
    private function tokenize(string $text, string $wordRegex, int $maxWords): array
    {
        // Split on non-word characters
        $pattern = '/[^' . $wordRegex . ']+/u';
        $tokens = preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY);

        if ($tokens === false) {
            return [];
        }

        // Filter out very short tokens (single characters unless CJK)
        $filtered = [];
        foreach ($tokens as $token) {
            if (mb_strlen($token) >= 1) {
                $filtered[] = $token;
                if (count($filtered) >= $maxWords) {
                    break;
                }
            }
        }

        return $filtered;
    }

    /**
     * Look up which words from a list the user already knows.
     *
     * Words with any status (1-5, 98, 99) are considered "encountered".
     *
     * @param int          $languageId Language ID
     * @param list<string> $words      Lowercase words to look up
     *
     * @return list<string> Words that exist in the user's vocabulary
     */
    private function lookupKnownWords(int $languageId, array $words): array
    {
        if (empty($words)) {
            return [];
        }

        $known = [];

        // Batch lookups to avoid huge IN clauses
        foreach (array_chunk($words, self::LOOKUP_BATCH_SIZE) as $batch) {
            $placeholders = implode(',', array_fill(0, count($batch), '?'));
            $params = array_merge([$languageId], $batch);
            $userScope = UserScopedQuery::forTablePrepared('words', $params);

            $sql = "SELECT WoTextLC FROM words
                    WHERE WoLgID = ? AND WoTextLC IN ($placeholders)"
                . $userScope;

            $rows = Connection::preparedFetchAll($sql, $params);
            foreach ($rows as $row) {
                $known[] = (string) $row['WoTextLC'];
            }
        }

        return $known;
    }

    /**
     * Map coverage percentage to a human-readable difficulty label.
     *
     * Based on research: 95%+ coverage = comfortable reading,
     * 90-95% = challenging but feasible, below 90% = frustrating.
     *
     * @param float $percent Coverage percentage
     *
     * @return string Difficulty label
     */
    private function labelFromCoverage(float $percent): string
    {
        if ($percent >= 95.0) {
            return 'easy';
        }

        if ($percent >= 85.0) {
            return 'medium';
        }

        return 'hard';
    }
}
