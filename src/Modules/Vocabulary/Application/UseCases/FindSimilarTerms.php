<?php

/**
 * Find Similar Terms Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\UseCases
 * @author   HugoFara <git@hugofara.net>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\UseCases;

use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Vocabulary\Application\Services\SimilarityCalculator;
use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * Use case for finding similar terms.
 *
 * @since 3.0.0
 */
class FindSimilarTerms
{
    private SimilarityCalculator $calculator;

    /**
     * Constructor.
     *
     * @param SimilarityCalculator|null $calculator Similarity calculator
     */
    public function __construct(
        ?SimilarityCalculator $calculator = null
    ) {
        $this->calculator = $calculator ?? new SimilarityCalculator();
    }

    /**
     * Find similar terms for a given language and term.
     *
     * @param int    $languageId     Language ID
     * @param string $comparedTerm   Term to compare with
     * @param int    $maxCount       Maximum number of terms to return
     * @param float  $minRanking     Minimum similarity ranking (0-1)
     * @param float  $phoneticWeight Weight for phonetic similarity (0-1)
     *
     * @return list<int> Word IDs, most useful first
     */
    public function execute(
        int $languageId,
        string $comparedTerm,
        int $maxCount,
        float $minRanking,
        float $phoneticWeight = 0.3
    ): array {
        $comparedTermLc = mb_strtolower($comparedTerm, 'UTF-8');

        // Fetch words with their status for weighting
        $rows = QueryBuilder::table('words')
            ->select(['WoID', 'WoTextLC', 'WoStatus'])
            ->where('WoLgID', '=', $languageId)
            ->where('WoTextLC', '<>', $comparedTermLc)
            ->getPrepared();

        $candidates = [];
        foreach ($rows as $record) {
            $candidates[] = [
                'id' => (int) $record["WoID"],
                'textLc' => (string) $record["WoTextLC"],
                'status' => (int) $record["WoStatus"],
            ];
        }

        return $this->rankByCoverage(
            $candidates,
            $comparedTermLc,
            $maxCount,
            $minRanking,
            $phoneticWeight
        );
    }

    /**
     * Pick the terms that between them explain the most of the compared term.
     *
     * Ranking each candidate against the whole term independently — what this
     * used to do — makes a compound's siblings crowd out its parts: every word
     * sharing "geschwindigkeit" scores on that shared half, so the term that
     * would explain the *other* half never makes the list. So the picks are
     * made one at a time, and after each one the term shrinks to the part still
     * unexplained. A candidate that only repeats an earlier pick then scores
     * near zero, and a short term covering fresh ground wins on merit.
     *
     * The first pick is unchanged: with nothing covered yet, the score is the
     * plain pairwise similarity. Admission to the pool uses that same pairwise
     * score against the minimum ranking, so this changes which candidates are
     * chosen and in what order, never which ones were eligible.
     *
     * @param list<array{id: int, textLc: string, status: int}> $candidates     Candidate terms
     * @param string                                            $comparedTermLc Lowercased term
     * @param int                                               $maxCount       Maximum to return
     * @param float                                             $minRanking     Minimum ranking (0-1)
     * @param float                                             $phoneticWeight Phonetic weight (0-1)
     *
     * @return list<int> Word IDs, most useful first
     */
    public function rankByCoverage(
        array $candidates,
        string $comparedTermLc,
        int $maxCount,
        float $minRanking,
        float $phoneticWeight = 0.3
    ): array {
        if ($maxCount <= 0) {
            return [];
        }

        $term = $this->calculator->profile($comparedTermLc);

        $pool = [];
        foreach ($candidates as $candidate) {
            $profile = $this->calculator->profile($candidate['textLc']);
            $baseSimilarity = $this->calculator->getResidualCombinedRanking(
                $profile,
                $term,
                $phoneticWeight
            );

            // The threshold reads the unweighted score, as it always has
            if ($baseSimilarity < $minRanking) {
                continue;
            }

            $statusWeight = $this->calculator->getStatusWeight($candidate['status']);
            $pool[] = [
                'id' => $candidate['id'],
                'profile' => $profile,
                'weight' => $statusWeight,
                'weighted' => $baseSimilarity * $statusWeight,
            ];
        }

        $remaining = $term;
        $picked = [];
        $wanted = min($maxCount, count($pool));

        for ($i = 0; $i < $wanted; $i++) {
            $bestIndex = null;
            $bestGain = -1.0;
            $bestWeighted = -1.0;

            foreach ($pool as $index => $entry) {
                $gain = $this->calculator->getResidualCombinedRanking(
                    $entry['profile'],
                    $remaining,
                    $phoneticWeight
                ) * $entry['weight'];

                // Once the term is fully explained every gain is zero, so the
                // pairwise score decides the rest of the list
                $isBetter = $gain > $bestGain + 1e-9
                    || (abs($gain - $bestGain) <= 1e-9 && $entry['weighted'] > $bestWeighted);
                if ($isBetter) {
                    $bestGain = $gain;
                    $bestWeighted = $entry['weighted'];
                    $bestIndex = $index;
                }
            }

            if ($bestIndex === null) {
                break;
            }

            $picked[] = $pool[$bestIndex]['id'];
            $remaining = $remaining->minus($pool[$bestIndex]['profile']);
            unset($pool[$bestIndex]);
        }

        return $picked;
    }

    /**
     * Format a similar term for display.
     *
     * @param int    $termId  Term ID
     * @param string $compare Similar term to compare with
     *
     * @return string HTML-formatted string
     */
    public function formatTerm(int $termId, string $compare): string
    {
        $record = QueryBuilder::table('words')
            ->select(['WoText', 'WoTranslation', 'WoRomanization'])
            ->where('WoID', '=', $termId)
            ->firstPrepared();
        if ($record !== null) {
            $term = htmlspecialchars((string)($record["WoText"] ?? ''), ENT_QUOTES, 'UTF-8');
            if (stripos($compare, $term) !== false) {
                $term = '<span class="has-text-danger">' . $term . '</span>';
            } else {
                $term = str_replace(
                    $compare,
                    '<span class="has-text-danger"><u>' . $compare . '</u></span>',
                    $term
                );
            }
            $tra = (string) $record["WoTranslation"];
            if ($tra == "*") {
                $tra = "???";
            }
            if (trim((string) $record["WoRomanization"]) !== '') {
                $rom = (string) $record["WoRomanization"];
                $romd = " [$rom]";
            } else {
                $rom = "";
                $romd = "";
            }
            $output = IconHelper::render('check-circle', [
                'class' => 'clickedit',
                'title' => 'Copy → Translation & Romanization Field(s)',
                'data-action' => 'set-trans-roman',
                'data-translation' => htmlspecialchars($tra, ENT_QUOTES, 'UTF-8'),
                'data-romanization' => htmlspecialchars($rom, ENT_QUOTES, 'UTF-8')
            ]) . ' ' .
            $term . htmlspecialchars($romd, ENT_QUOTES, 'UTF-8') . ' — ' . htmlspecialchars($tra, ENT_QUOTES, 'UTF-8') .
            '<br />';
            return $output;
        }
        return "";
    }

    /**
     * Get formatted HTML for similar terms.
     *
     * @param int    $languageId   Language ID
     * @param string $comparedTerm Term to compare with
     *
     * @return string HTML output
     */
    public function getFormattedTerms(int $languageId, string $comparedTerm): string
    {
        $maxCount = (int) Settings::getWithDefault("set-similar-terms-count");
        if ($maxCount <= 0) {
            return '';
        }
        if (trim($comparedTerm) == '') {
            return '&nbsp;';
        }
        $compare = htmlspecialchars($comparedTerm, ENT_QUOTES, 'UTF-8');
        $termarr = $this->execute($languageId, $comparedTerm, $maxCount, 0.33);
        $rarr = [];
        foreach ($termarr as $termid) {
            $similar_term = $this->formatTerm($termid, $compare);
            if ($similar_term != "") {
                $rarr[] = $similar_term;
            }
        }
        if (count($rarr) == 0) {
            return "(none)";
        }
        return implode($rarr);
    }

    /**
     * Get HTML for similar terms table row.
     *
     * @return string HTML output or empty string
     */
    public function getTableRow(): string
    {
        if ((int) Settings::getWithDefault("set-similar-terms-count") > 0) {
            return '<tr>
                <td class="has-text-right">Similar<br />Terms:</td>
                <td><span id="simwords" class="is-size-7">&nbsp;</span></td>
            </tr>';
        }
        return '';
    }
}
