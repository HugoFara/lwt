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
 */

namespace Lwt\Services {

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;

/**
 * Service class for similar terms calculation.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class SimilarTermsService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Get letter pairs from string
     *
     * @param string $str Input string
     *
     * @return string[]
     */
    public function letterPairs(string $str): array
    {
        $numPairs = mb_strlen($str) - 1;
        $pairs = array();
        for ($i = 0; $i < $numPairs; $i++) {
            $pairs[$i] = mb_substr($str, $i, 2);
        }
        return $pairs;
    }

    /**
     * Get word letter pairs from string
     *
     * @param string $str Input string
     *
     * @return string[]
     */
    public function wordLetterPairs(string $str): array
    {
        $allPairs = array();
        $words = explode(' ', $str);
        for ($w = 0; $w < count($words); $w++) {
            $pairsInWord = $this->letterPairs($words[$w]);
            for ($p = 0; $p < count($pairsInWord); $p++) {
                $allPairs[$pairsInWord[$p]] = $pairsInWord[$p];
            }
        }
        return array_values($allPairs);
    }

    /**
     * Similarity ranking of two UTF-8 strings
     *
     * Source http://www.catalysoft.com/articles/StrikeAMatch.html
     * Source http://stackoverflow.com/questions/653157
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     *
     * @return float SimilarityRanking
     */
    public function getSimilarityRanking(string $str1, string $str2): float
    {
        $pairs1 = $this->wordLetterPairs($str1);
        $pairs2 = $this->wordLetterPairs($str2);
        $union = count($pairs1) + count($pairs2);
        if ($union == 0) {
            return 0;
        }
        $intersection = count(array_intersect($pairs1, $pairs2));
        return 2 * $intersection / $union;
    }

    /**
     * For a language $langId and a term $comparedTerm (UTF-8).
     * If string is already in database, it will be excluded in results.
     *
     * @param int    $langId       Language ID
     * @param string $comparedTerm Term to compare with
     * @param int    $maxCount     Maximum number of terms to display
     * @param float  $minRanking   For terms to match
     *
     * @return int[] All $maxCount wordids with a similarity ranking > $minRanking,
     *               sorted descending
     */
    public function getSimilarTerms(
        int $langId,
        string $comparedTerm,
        int $maxCount,
        float $minRanking
    ): array {
        $comparedTermLc = mb_strtolower($comparedTerm, 'UTF-8');
        $sql = "SELECT WoID, WoTextLC FROM {$this->tbpref}words
        WHERE WoLgID = $langId
        AND WoTextLC <> " . Escaping::toSqlSyntax($comparedTermLc);
        $res = Connection::query($sql);
        $termlsd = array();
        while ($record = mysqli_fetch_assoc($res)) {
            $termlsd[(int)$record["WoID"]] = $this->getSimilarityRanking(
                $comparedTermLc,
                $record["WoTextLC"]
            );
        }
        mysqli_free_result($res);
        arsort($termlsd, SORT_NUMERIC);
        $r = array();
        $i = 0;
        foreach ($termlsd as $key => $val) {
            if ($i >= $maxCount || $val < $minRanking) {
                break;
            }
            $i++;
            $r[$i] = $key;
        }
        return $r;
    }

    /**
     * Prepare a field with a similar term to copy.
     *
     * @param int    $termId  Initial term ID
     * @param string $compare Similar term to copy.
     *
     * @return string HTML-formatted string with the similar term displayed.
     */
    public function formatTerm(int $termId, string $compare): string
    {
        $sql = "SELECT WoText, WoTranslation, WoRomanization
        FROM {$this->tbpref}words WHERE WoID = $termId";
        $res = Connection::query($sql);
        if ($record = mysqli_fetch_assoc($res)) {
            $term = tohtml($record["WoText"]);
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
            $output = '<img class="clickedit" src="/assets/icons/tick-button-small.png" ' .
            'title="Copy → Translation &amp; Romanization Field(s)" ' .
            'data-action="set-trans-roman" ' .
            'data-translation="' . tohtml($tra) . '" ' .
            'data-romanization="' . tohtml($rom) . '" /> ' .
            $term . tohtml($romd) . ' — ' . tohtml($tra) .
            '<br />';
            mysqli_free_result($res);
            return $output;
        }
        mysqli_free_result($res);
        return "";
    }

    /**
     * Get Term and translation of terms in termid array (calculated
     * in function getSimilarTerms(...)) as string for echo
     *
     * @param int    $langId       Language ID
     * @param string $comparedTerm Similar term we compare to
     *
     * @return string HTML output
     */
    public function printSimilarTerms(int $langId, string $comparedTerm): string
    {
        $maxCount = (int)Settings::getWithDefault("set-similar-terms-count");
        if ($maxCount <= 0) {
            return '';
        }
        if (trim($comparedTerm) == '') {
            return '&nbsp;';
        }
        $compare = tohtml($comparedTerm);
        $termarr = $this->getSimilarTerms($langId, $comparedTerm, $maxCount, 0.33);
        $rarr = array();
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
     * Print a row for similar terms if the feature is enabled.
     *
     * @return string HTML output or empty string
     */
    public function printSimilarTermsTabRow(): string
    {
        if ((int)Settings::getWithDefault("set-similar-terms-count") > 0) {
            return '<tr>
                <td class="td1 right">Similar<br />Terms:</td>
                <td class="td1"><span id="simwords" class="smaller">&nbsp;</span></td>
            </tr>';
        }
        return '';
    }
}

} // End namespace Lwt\Services

namespace {

// =============================================================================
// GLOBAL FUNCTION WRAPPERS (for backward compatibility)
// =============================================================================

use Lwt\Services\SimilarTermsService;

/**
 * Get letter pairs from string
 *
 * @param string $str Input string
 *
 * @return string[]
 *
 * @see SimilarTermsService::letterPairs()
 */
function letterPairs(string $str): array
{
    $service = new SimilarTermsService();
    return $service->letterPairs($str);
}

/**
 * Get word letter pairs from string
 *
 * @param string $str Input string
 *
 * @return string[]
 *
 * @see SimilarTermsService::wordLetterPairs()
 */
function wordLetterPairs(string $str): array
{
    $service = new SimilarTermsService();
    return $service->wordLetterPairs($str);
}

/**
 * Similarity ranking of two UTF-8 strings
 *
 * @param string $str1 First string
 * @param string $str2 Second string
 *
 * @return float SimilarityRanking
 *
 * @see SimilarTermsService::getSimilarityRanking()
 */
function getSimilarityRanking(string $str1, string $str2): float
{
    $service = new SimilarTermsService();
    return $service->getSimilarityRanking($str1, $str2);
}

/**
 * For a language $lang_id and a term $compared_term (UTF-8).
 *
 * @param int    $lang_id       Language ID
 * @param string $compared_term Term to compare with
 * @param int    $max_count     Maximum number of terms to display
 * @param float  $min_ranking   For terms to match
 *
 * @return int[] All $max_count wordids with a similarity ranking > $min_ranking
 *
 * @see SimilarTermsService::getSimilarTerms()
 */
function get_similar_terms(
    int $lang_id,
    string $compared_term,
    int $max_count,
    float $min_ranking
): array {
    $service = new SimilarTermsService();
    return $service->getSimilarTerms($lang_id, $compared_term, $max_count, $min_ranking);
}

/**
 * Prepare a field with a similar term to copy.
 *
 * @param int    $termid  Initial term ID
 * @param string $compare Similar term to copy.
 *
 * @return string HTML-formatted string
 *
 * @see SimilarTermsService::formatTerm()
 */
function format_term(int $termid, string $compare): string
{
    $service = new SimilarTermsService();
    return $service->formatTerm($termid, $compare);
}

/**
 * Get Term and translation of terms in termid array as string for echo
 *
 * @param int    $lang_id       Language ID
 * @param string $compared_term Similar term we compare to
 *
 * @return string HTML output
 *
 * @see SimilarTermsService::printSimilarTerms()
 */
function print_similar_terms(int $lang_id, string $compared_term): string
{
    $service = new SimilarTermsService();
    return $service->printSimilarTerms($lang_id, $compared_term);
}

/**
 * Print a row for similar terms if the feature is enabled.
 *
 * @see SimilarTermsService::printSimilarTermsTabRow()
 */
function print_similar_terms_tabrow(): void
{
    $service = new SimilarTermsService();
    echo $service->printSimilarTermsTabRow();
}

} // End global namespace
