<?php

/**
 * \file
 * \brief PHP Utility Functions to calculate similar terms of a term.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-simterms.html
 */

require_once __DIR__ . '/session_utility.php';


/**
 * Get letter pairs from string
 *
 * @param string $str Input string
 *
 * @return string[]
 *
 * @psalm-return array<0|positive-int, string>
 */
function letterPairs($str): array
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
 *
 * @psalm-return list<string>
 */
function wordLetterPairs($str): array
{
    $allPairs = array();
    $words = explode(' ', $str);
    for ($w = 0; $w < count($words); $w++) {
        $pairsInWord = letterPairs($words[$w]);
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
function getSimilarityRanking($str1, $str2)
{
    $pairs1 = wordLetterPairs($str1);
    $pairs2 = wordLetterPairs($str2);
    $union = count($pairs1) + count($pairs2);
    if ($union == 0) {
        return 0;
    }
    $intersection = count(array_intersect($pairs1, $pairs2));
    return 2 * $intersection / $union;
}

/**
 * For a language $lang_id and a term $compared_term (UTF-8).
 * If string is already in database, it will be excluded in results.
 *
 * @param int    $lang_id       Language ID
 * @param string $compared_term Term to compare with
 * @param int    $max_count     Maximum number of terms to display
 * @param float  $min_ranking   For terms to match
 *
 * @return int[] All $max_count wordids with a similarity ranking > $min_ranking,
 *               sorted decending
 *
 * @psalm-return array<positive-int, int>
 */
function get_similar_terms(
    $lang_id,
    $compared_term,
    $max_count,
    $min_ranking
): array {
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $compared_term_lc = mb_strtolower($compared_term, 'UTF-8');
    $sql = "SELECT WoID, WoTextLC FROM {$tbpref}words
    WHERE WoLgID = $lang_id
    AND WoTextLC <> " . convert_string_to_sqlsyntax($compared_term_lc);
    $res = do_mysqli_query($sql);
    $termlsd = array();
    while ($record = mysqli_fetch_assoc($res)) {
        $termlsd[(int)$record["WoID"]] = getSimilarityRanking(
            $compared_term_lc,
            $record["WoTextLC"]
        );
    }
    mysqli_free_result($res);
    arsort($termlsd, SORT_NUMERIC);
    $r = array();
    $i = 0;
    foreach ($termlsd as $key => $val) {
        if ($i >= $max_count || $val < $min_ranking) {
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
 * @param int    $termid  Initial term ID
 * @param string $compare Similar term to copy.
 *
 * @return string HTNL-formatted string with the similar term displayed.
 *
 * @global string $tbpref
 */
function format_term($termid, $compare)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql = "SELECT WoText, WoTranslation, WoRomanization
    FROM {$tbpref}words WHERE WoID = $termid";
    $res = do_mysqli_query($sql);
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
        $js_event = "setTransRoman(" . prepare_textdata_js($tra) . ',' .
        prepare_textdata_js($rom) . ')';
        $output = '<img class="clickedit" src="/assets/icons/tick-button-small.png" ' .
        'title="Copy → Translation &amp; Romanization Field(s)" ' .
        'onclick="' . tohtml($js_event) . '" /> ' .
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
 * in function get_similar_terms(...)) as string for echo
 *
 * @param int    $lang_id       Language ID
 * @param string $compared_term Similar term we compare to
 */
function print_similar_terms($lang_id, $compared_term): string
{
    $max_count = (int)getSettingWithDefault("set-similar-terms-count");
    if ($max_count <= 0) {
        return '';
    }
    if (trim($compared_term) == '') {
        return '&nbsp;';
    }
    $compare = tohtml($compared_term);
    $termarr = get_similar_terms($lang_id, $compared_term, $max_count, 0.33);
    $rarr = array();
    foreach ($termarr as $termid) {
        $similar_term = format_term($termid, $compare);
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
 */
function print_similar_terms_tabrow(): void
{
    if ((int)getSettingWithDefault("set-similar-terms-count") > 0) {
        echo '<tr>
            <td class="td1 right">Similar<br />Terms:</td>
            <td class="td1"><span id="simwords" class="smaller">&nbsp;</span></td>
        </tr>';
    }
}
