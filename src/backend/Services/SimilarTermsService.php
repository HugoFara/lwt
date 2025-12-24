<?php declare(strict_types=1);
/**
 * Similar Terms Service - Calculate similar terms of a term.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/simterms.php
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms instead
 */

namespace Lwt\Services;

use Lwt\Modules\Vocabulary\Application\Services\SimilarityCalculator;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;

/**
 * Service class for similar terms calculation.
 *
 * This is a backward-compatibility wrapper.
 * Use FindSimilarTerms use case directly for new code.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms instead
 */
class SimilarTermsService
{
    /**
     * Weight multiplier for learned words (status 5).
     *
     * @deprecated Use SimilarityCalculator::STATUS_WEIGHT_LEARNED instead
     */
    private const STATUS_WEIGHT_LEARNED = SimilarityCalculator::STATUS_WEIGHT_LEARNED;

    /**
     * Weight multiplier for words in progress (status 2-4).
     *
     * @deprecated Use SimilarityCalculator::STATUS_WEIGHT_IN_PROGRESS instead
     */
    private const STATUS_WEIGHT_IN_PROGRESS = SimilarityCalculator::STATUS_WEIGHT_IN_PROGRESS;

    /**
     * Weight multiplier for new words (status 1).
     *
     * @deprecated Use SimilarityCalculator::STATUS_WEIGHT_NEW instead
     */
    private const STATUS_WEIGHT_NEW = SimilarityCalculator::STATUS_WEIGHT_NEW;

    /**
     * Weight multiplier for well-known words (status 99).
     *
     * @deprecated Use SimilarityCalculator::STATUS_WEIGHT_WELL_KNOWN instead
     */
    private const STATUS_WEIGHT_WELL_KNOWN = SimilarityCalculator::STATUS_WEIGHT_WELL_KNOWN;

    /**
     * Weight multiplier for ignored words (status 98).
     *
     * @deprecated Use SimilarityCalculator::STATUS_WEIGHT_IGNORED instead
     */
    private const STATUS_WEIGHT_IGNORED = SimilarityCalculator::STATUS_WEIGHT_IGNORED;

    private SimilarityCalculator $calculator;
    private FindSimilarTerms $findSimilarTerms;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->calculator = new SimilarityCalculator();
        $this->findSimilarTerms = new FindSimilarTerms(null, $this->calculator);
    }

    /**
     * Normalize a string for phonetic comparison.
     *
     * @param string $str Input string (should be lowercase)
     *
     * @return string Phonetically normalized string
     *
     * @deprecated Use SimilarityCalculator::phoneticNormalize() instead
     */
    public function phoneticNormalize(string $str): string
    {
        return $this->calculator->phoneticNormalize($str);
    }

    /**
     * Get weight multiplier based on word status.
     *
     * @param int $status Word status (1-5, 98=ignored, 99=well-known)
     *
     * @return float Weight multiplier
     *
     * @deprecated Use SimilarityCalculator::getStatusWeight() instead
     */
    public function getStatusWeight(int $status): float
    {
        return $this->calculator->getStatusWeight($status);
    }

    /**
     * Get letter pairs from string.
     *
     * @param string $str Input string
     *
     * @return string[]
     *
     * @deprecated Use SimilarityCalculator::letterPairs() instead
     */
    public function letterPairs(string $str): array
    {
        return $this->calculator->letterPairs($str);
    }

    /**
     * Get word letter pairs from string.
     *
     * @param string $str Input string
     *
     * @return string[]
     *
     * @deprecated Use SimilarityCalculator::wordLetterPairs() instead
     */
    public function wordLetterPairs(string $str): array
    {
        return $this->calculator->wordLetterPairs($str);
    }

    /**
     * Similarity ranking of two UTF-8 strings.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     *
     * @return float SimilarityRanking
     *
     * @deprecated Use SimilarityCalculator::getSimilarityRanking() instead
     */
    public function getSimilarityRanking(string $str1, string $str2): float
    {
        return $this->calculator->getSimilarityRanking($str1, $str2);
    }

    /**
     * Combined similarity ranking.
     *
     * @param string $str1           First string (lowercase)
     * @param string $str2           Second string (lowercase)
     * @param float  $phoneticWeight Weight for phonetic similarity (0-1)
     *
     * @return float Combined similarity ranking (0-1)
     *
     * @deprecated Use SimilarityCalculator::getCombinedSimilarityRanking() instead
     */
    public function getCombinedSimilarityRanking(
        string $str1,
        string $str2,
        float $phoneticWeight = 0.3
    ): float {
        return $this->calculator->getCombinedSimilarityRanking($str1, $str2, $phoneticWeight);
    }

    /**
     * For a language $langId and a term $comparedTerm.
     *
     * @param int    $langId       Language ID
     * @param string $comparedTerm Term to compare with
     * @param int    $maxCount     Maximum number of terms to display
     * @param float  $minRanking   For terms to match
     *
     * @return int[] All $maxCount wordids with a similarity ranking > $minRanking
     *
     * @deprecated Use FindSimilarTerms::execute() instead
     */
    public function getSimilarTerms(
        int $langId,
        string $comparedTerm,
        int $maxCount,
        float $minRanking
    ): array {
        return $this->findSimilarTerms->execute($langId, $comparedTerm, $maxCount, $minRanking);
    }

    /**
     * Find similar terms with phonetic matching and status weighting.
     *
     * @param int    $langId         Language ID
     * @param string $comparedTerm   Term to compare with
     * @param int    $maxCount       Maximum number of terms to display
     * @param float  $minRanking     Minimum similarity ranking (before weighting)
     * @param float  $phoneticWeight Weight for phonetic similarity (0-1, default 0.3)
     *
     * @return int[] Word IDs sorted by weighted similarity, descending
     *
     * @deprecated Use FindSimilarTerms::execute() instead
     */
    public function getSimilarTermsWeighted(
        int $langId,
        string $comparedTerm,
        int $maxCount,
        float $minRanking,
        float $phoneticWeight = 0.3
    ): array {
        return $this->findSimilarTerms->execute(
            $langId,
            $comparedTerm,
            $maxCount,
            $minRanking,
            $phoneticWeight
        );
    }

    /**
     * Prepare a field with a similar term to copy.
     *
     * @param int    $termId  Initial term ID
     * @param string $compare Similar term to copy.
     *
     * @return string HTML-formatted string with the similar term displayed.
     *
     * @deprecated Use FindSimilarTerms::formatTerm() instead
     */
    public function formatTerm(int $termId, string $compare): string
    {
        return $this->findSimilarTerms->formatTerm($termId, $compare);
    }

    /**
     * Get Term and translation of terms as string for echo.
     *
     * @param int    $langId       Language ID
     * @param string $comparedTerm Similar term we compare to
     *
     * @return string HTML output
     *
     * @deprecated Use FindSimilarTerms::getFormattedTerms() instead
     */
    public function printSimilarTerms(int $langId, string $comparedTerm): string
    {
        return $this->findSimilarTerms->getFormattedTerms($langId, $comparedTerm);
    }

    /**
     * Print a row for similar terms if the feature is enabled.
     *
     * @return string HTML output or empty string
     *
     * @deprecated Use FindSimilarTerms::getTableRow() instead
     */
    public function printSimilarTermsTabRow(): string
    {
        return $this->findSimilarTerms->getTableRow();
    }
}
