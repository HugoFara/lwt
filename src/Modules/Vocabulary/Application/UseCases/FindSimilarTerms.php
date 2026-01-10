<?php declare(strict_types=1);
/**
 * Find Similar Terms Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Application\UseCases;

use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Vocabulary\Application\Services\SimilarityCalculator;
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;
use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * Use case for finding similar terms.
 *
 * @since 3.0.0
 */
class FindSimilarTerms
{
    private TermRepositoryInterface $repository;
    private SimilarityCalculator $calculator;

    /**
     * Constructor.
     *
     * @param TermRepositoryInterface|null $repository  Term repository
     * @param SimilarityCalculator|null    $calculator  Similarity calculator
     */
    public function __construct(
        ?TermRepositoryInterface $repository = null,
        ?SimilarityCalculator $calculator = null
    ) {
        $this->repository = $repository ?? new \Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository();
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
     * @return int[] Word IDs sorted by weighted similarity, descending
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

        $termlsd = [];
        foreach ($rows as $record) {
            // Calculate combined similarity (character pairs + phonetic)
            $baseSimilarity = $this->calculator->getCombinedSimilarityRanking(
                $comparedTermLc,
                (string)$record["WoTextLC"],
                $phoneticWeight
            );

            // Apply status weight to boost learned words
            $status = (int) $record["WoStatus"];
            $statusWeight = $this->calculator->getStatusWeight($status);
            $weightedSimilarity = $baseSimilarity * $statusWeight;

            // Only include if base similarity meets minimum threshold
            if ($baseSimilarity >= $minRanking) {
                $termlsd[(int) $record["WoID"]] = $weightedSimilarity;
            }
        }

        // Sort by weighted similarity descending
        arsort($termlsd, SORT_NUMERIC);

        // Return top N results
        $r = [];
        $i = 0;
        foreach ($termlsd as $key => $_val) {
            if ($i >= $maxCount) {
                break;
            }
            $i++;
            $r[$i] = $key;
        }
        return $r;
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
                $term = '<span class="red3">' . $term . '</span>';
            } else {
                $term = str_replace(
                    $compare,
                    '<span class="red3"><u>' . $compare . '</u></span>',
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
                <td class="td1 right">Similar<br />Terms:</td>
                <td class="td1"><span id="simwords" class="smaller">&nbsp;</span></td>
            </tr>';
        }
        return '';
    }
}
