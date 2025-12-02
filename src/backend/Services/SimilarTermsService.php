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
use Lwt\View\Helper\IconHelper;

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
     * Weight multiplier for learned words (status 5).
     */
    private const STATUS_WEIGHT_LEARNED = 1.3;

    /**
     * Weight multiplier for words in progress (status 2-4).
     */
    private const STATUS_WEIGHT_IN_PROGRESS = 1.15;

    /**
     * Weight multiplier for new words (status 1).
     */
    private const STATUS_WEIGHT_NEW = 1.0;

    /**
     * Weight multiplier for well-known words (status 99).
     */
    private const STATUS_WEIGHT_WELL_KNOWN = 1.25;

    /**
     * Weight multiplier for ignored words (status 98).
     */
    private const STATUS_WEIGHT_IGNORED = 0.5;

    /**
     * Phonetic character mapping for normalization.
     * Maps similar-sounding characters to a common representation.
     *
     * @var array<string, string>
     */
    private static array $phoneticMap = [
        // Vowel groups
        'a' => 'a', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'å' => 'a', 'ā' => 'a', 'ă' => 'a', 'ą' => 'a', 'æ' => 'ae',
        'e' => 'e', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e',
        'ĕ' => 'e', 'ė' => 'e', 'ę' => 'e', 'ě' => 'e',
        'i' => 'i', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ĩ' => 'i',
        'ī' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'y' => 'i',
        'o' => 'o', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o', 'ø' => 'o', 'œ' => 'oe',
        'u' => 'u', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u',
        'ū' => 'u', 'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u',
        // Consonant groups - similar sounds
        'b' => 'b', 'p' => 'p',
        'c' => 'k', 'k' => 'k', 'q' => 'k', 'ç' => 's', 'ć' => 'c', 'č' => 'c',
        'd' => 'd', 't' => 't', 'ð' => 'd', 'þ' => 't',
        'f' => 'f', 'v' => 'v', 'ph' => 'f',
        'g' => 'g', 'ğ' => 'g', 'ģ' => 'g', 'j' => 'j',
        'h' => 'h',
        'l' => 'l', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l',
        'm' => 'm', 'n' => 'n', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n',
        'r' => 'r', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r',
        's' => 's', 'z' => 's', 'ś' => 's', 'š' => 's', 'ş' => 's', 'ź' => 's',
        'ż' => 's', 'ž' => 's', 'ß' => 'ss',
        'w' => 'w',
        'x' => 'ks',
        // Double letters simplified
    ];

    /**
     * Constructor - initialize table prefix.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
    }

    /**
     * Normalize a string for phonetic comparison.
     *
     * Applies phonetic transformations to make similar-sounding words
     * more likely to match. This includes:
     * - Converting accented characters to base forms
     * - Mapping similar-sounding consonants
     * - Removing double letters
     *
     * @param string $str Input string (should be lowercase)
     *
     * @return string Phonetically normalized string
     */
    public function phoneticNormalize(string $str): string
    {
        $result = '';
        $length = mb_strlen($str, 'UTF-8');
        $prevChar = '';

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($str, $i, 1, 'UTF-8');

            // Check for multi-character mappings first (like 'ph' -> 'f')
            if ($i < $length - 1) {
                $twoChars = mb_substr($str, $i, 2, 'UTF-8');
                if (isset(self::$phoneticMap[$twoChars])) {
                    $mapped = self::$phoneticMap[$twoChars];
                    // Skip double letters
                    if ($mapped !== $prevChar) {
                        $result .= $mapped;
                        $prevChar = $mapped;
                    }
                    $i++; // Skip next character
                    continue;
                }
            }

            // Single character mapping
            $mapped = self::$phoneticMap[$char] ?? $char;

            // Skip consecutive duplicate characters (reduces "ll" to "l", etc.)
            if ($mapped !== $prevChar) {
                $result .= $mapped;
                $prevChar = $mapped;
            }
        }

        return $result;
    }

    /**
     * Get weight multiplier based on word status.
     *
     * Higher status words (learned, well-known) get boosted rankings
     * because users are more likely to want translations from words
     * they've already learned.
     *
     * @param int $status Word status (1-5, 98=ignored, 99=well-known)
     *
     * @return float Weight multiplier
     */
    public function getStatusWeight(int $status): float
    {
        return match ($status) {
            5 => self::STATUS_WEIGHT_LEARNED,
            2, 3, 4 => self::STATUS_WEIGHT_IN_PROGRESS,
            99 => self::STATUS_WEIGHT_WELL_KNOWN,
            98 => self::STATUS_WEIGHT_IGNORED,
            default => self::STATUS_WEIGHT_NEW,
        };
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
     * Combined similarity ranking using both character pairs and phonetic matching.
     *
     * Computes similarity as a weighted average of:
     * - Standard character pair similarity (Sørensen–Dice coefficient)
     * - Phonetic similarity (after normalization)
     *
     * This helps find cognates and similar-sounding words that might be
     * spelled differently (e.g., "colour" vs "color", "café" vs "cafe").
     *
     * @param string $str1           First string (lowercase)
     * @param string $str2           Second string (lowercase)
     * @param float  $phoneticWeight Weight for phonetic similarity (0-1)
     *
     * @return float Combined similarity ranking (0-1)
     */
    public function getCombinedSimilarityRanking(
        string $str1,
        string $str2,
        float $phoneticWeight = 0.3
    ): float {
        // Standard character pair similarity
        $charSimilarity = $this->getSimilarityRanking($str1, $str2);

        // Phonetic similarity
        $phonetic1 = $this->phoneticNormalize($str1);
        $phonetic2 = $this->phoneticNormalize($str2);
        $phoneticSimilarity = $this->getSimilarityRanking($phonetic1, $phonetic2);

        // Weighted average
        return (1 - $phoneticWeight) * $charSimilarity
            + $phoneticWeight * $phoneticSimilarity;
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
     *
     * @deprecated 3.0.0 Use getSimilarTermsWeighted() for better results
     */
    public function getSimilarTerms(
        int $langId,
        string $comparedTerm,
        int $maxCount,
        float $minRanking
    ): array {
        return $this->getSimilarTermsWeighted(
            $langId,
            $comparedTerm,
            $maxCount,
            $minRanking
        );
    }

    /**
     * Find similar terms with phonetic matching and status weighting.
     *
     * Enhanced version of getSimilarTerms that:
     * - Uses combined character pair + phonetic similarity
     * - Weights results by word status (learned words ranked higher)
     *
     * @param int   $langId         Language ID
     * @param string $comparedTerm  Term to compare with
     * @param int    $maxCount      Maximum number of terms to display
     * @param float  $minRanking    Minimum similarity ranking (before weighting)
     * @param float  $phoneticWeight Weight for phonetic similarity (0-1, default 0.3)
     *
     * @return int[] Word IDs sorted by weighted similarity, descending
     */
    public function getSimilarTermsWeighted(
        int $langId,
        string $comparedTerm,
        int $maxCount,
        float $minRanking,
        float $phoneticWeight = 0.3
    ): array {
        $comparedTermLc = mb_strtolower($comparedTerm, 'UTF-8');

        // Fetch words with their status for weighting
        $sql = "SELECT WoID, WoTextLC, WoStatus FROM {$this->tbpref}words
        WHERE WoLgID = $langId
        AND WoTextLC <> " . Escaping::toSqlSyntax($comparedTermLc);
        $res = Connection::query($sql);

        $termlsd = array();
        while ($record = mysqli_fetch_assoc($res)) {
            // Calculate combined similarity (character pairs + phonetic)
            $baseSimilarity = $this->getCombinedSimilarityRanking(
                $comparedTermLc,
                $record["WoTextLC"],
                $phoneticWeight
            );

            // Apply status weight to boost learned words
            $status = (int)$record["WoStatus"];
            $statusWeight = $this->getStatusWeight($status);
            $weightedSimilarity = $baseSimilarity * $statusWeight;

            // Only include if base similarity meets minimum threshold
            if ($baseSimilarity >= $minRanking) {
                $termlsd[(int)$record["WoID"]] = $weightedSimilarity;
            }
        }
        mysqli_free_result($res);

        // Sort by weighted similarity descending
        arsort($termlsd, SORT_NUMERIC);

        // Return top N results
        $r = array();
        $i = 0;
        foreach ($termlsd as $key => $val) {
            if ($i >= $maxCount) {
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
            $term = htmlspecialchars($record["WoText"] ?? '', ENT_QUOTES, 'UTF-8');
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
        $compare = htmlspecialchars($comparedTerm, ENT_QUOTES, 'UTF-8');
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
