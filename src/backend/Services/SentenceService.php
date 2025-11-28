<?php

/**
 * Sentence Service - Sentence operations and retrieval functions.
 *
 * This service contains functions for finding, formatting, and displaying
 * sentences containing specific words.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/sentence_operations.php
 */

namespace Lwt\Services {

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\Settings;

/**
 * Service class for sentence operations.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class SentenceService
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
     * Return a SQL string to find sentences containing a word.
     *
     * @param string $wordlc Word to look for in lowercase
     * @param int    $lid    Language ID
     *
     * @return string Query in SQL format
     */
    public function buildSentencesContainingWordQuery(string $wordlc, int $lid): string
    {
        $mecab_str = null;
        $res = Connection::query(
            "SELECT LgRegexpWordCharacters, LgRemoveSpaces
            FROM {$this->tbpref}languages
            WHERE LgID = $lid"
        );
        $record = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        $removeSpaces = $record["LgRemoveSpaces"];

        if ('MECAB' == strtoupper(trim((string) $record["LgRegexpWordCharacters"]))) {
            $mecab_file = sys_get_temp_dir() . "/" . $this->tbpref . "mecab_to_db.txt";
            $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n ';
            if (file_exists($mecab_file)) {
                unlink($mecab_file);
            }
            $fp = fopen($mecab_file, 'w');
            fwrite($fp, $wordlc . "\n");
            fclose($fp);
            $mecab = get_mecab_path($mecab_args);
            $handle = popen($mecab . $mecab_file, "r");
            if (!feof($handle)) {
                $row = fgets($handle, 256);
                $mecab_str = "\t" . preg_replace_callback(
                    '([2678]?)\t[0-9]+$',
                    function ($matches) {
                        return isset($matches[1]) ? "\t" : "";
                    },
                    $row
                );
            }
            pclose($handle);
            unlink($mecab_file);
            $sql = "SELECT SeID, SeText,
                concat(
                    '\\t',
                    group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR '\\t'),
                    '\\t'
                ) val
                FROM {$this->tbpref}sentences, {$this->tbpref}textitems2
                WHERE lower(SeText)
                LIKE " . Escaping::toSqlSyntax("%$wordlc%") . "
                AND SeID = Ti2SeID AND SeLgID = $lid AND Ti2WordCount<2
                GROUP BY SeID HAVING val
                LIKE " . Escaping::toSqlSyntaxNoTrimNoNull("%$mecab_str%") . "
                ORDER BY CHAR_LENGTH(SeText), SeText";
        } else {
            if ($removeSpaces == 1) {
                $pattern = Escaping::toSqlSyntax($wordlc);
            } else {
                $pattern = Escaping::regexpToSqlSyntax(
                    '(^|[^' . $record["LgRegexpWordCharacters"] . '])'
                     . remove_spaces($wordlc, $removeSpaces)
                     . '([^' . $record["LgRegexpWordCharacters"] . ']|$)'
                );
            }
            $sql = "SELECT DISTINCT SeID, SeText
                FROM {$this->tbpref}sentences
                WHERE SeText RLIKE $pattern AND SeLgID = $lid
                ORDER BY CHAR_LENGTH(SeText), SeText";
        }
        return $sql;
    }

    /**
     * Perform a SQL query to find sentences containing a word.
     *
     * @param int|null $wid    Word ID or mode
     *                         - null: use $wordlc instead, simple search
     *                         - -1: use $wordlc with a more complex search
     *                         - 0 or above: sentences containing $wid
     * @param string   $wordlc Word to look for in lowercase
     * @param int      $lid    Language ID
     * @param int      $limit  Maximum number of sentences to return
     *
     * @return \mysqli_result|false Query result or false on failure
     */
    public function findSentencesFromWord(?int $wid, string $wordlc, int $lid, int $limit = -1): \mysqli_result|false
    {
        if (empty($wid)) {
            $sql = "SELECT DISTINCT SeID, SeText
                FROM {$this->tbpref}sentences, {$this->tbpref}textitems2
                WHERE LOWER(Ti2Text) = " . Escaping::toSqlSyntax($wordlc) . "
                AND Ti2WoID = 0 AND SeID = Ti2SeID AND SeLgID = $lid
                ORDER BY CHAR_LENGTH(SeText), SeText";
        } elseif ($wid == -1) {
            $sql = $this->buildSentencesContainingWordQuery($wordlc, $lid);
        } else {
            $sql = "SELECT DISTINCT SeID, SeText
                FROM {$this->tbpref}sentences, {$this->tbpref}textitems2
                WHERE Ti2WoID = $wid AND SeID = Ti2SeID AND SeLgID = $lid
                ORDER BY CHAR_LENGTH(SeText), SeText";
        }
        if ($limit) {
            $sql .= " LIMIT 0,$limit";
        }
        return Connection::query($sql);
    }

    /**
     * Format the sentence(s) $seid containing $wordlc highlighting $wordlc.
     *
     * @param int    $seid   Sentence ID
     * @param string $wordlc Term text in lower case
     * @param int    $mode   * Up to 1: return only the current sentence
     *                       * Above 1: return previous sentence and current sentence
     *                       * Above 2: return previous, current and next sentence
     *
     * @return string[] [0]=html, word in bold, [1]=text, word in {}
     */
    public function formatSentence(int $seid, string $wordlc, int $mode): array
    {
        $res = Connection::query(
            "SELECT
            CONCAT(
                '​', group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR '​'), '​'
            ) AS SeText, Ti2TxID AS SeTxID, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar
            FROM {$this->tbpref}textitems2, {$this->tbpref}languages
            WHERE Ti2LgID = LgID AND Ti2WordCount < 2 AND Ti2SeID = $seid"
        );
        $record = mysqli_fetch_assoc($res);
        $removeSpaces = (int)$record["LgRemoveSpaces"] == 1;
        $splitEachChar = (int)$record['LgSplitEachChar'] != 0;
        $txtid = $record["SeTxID"];

        if (
            ($removeSpaces && !$splitEachChar)
            || 'MECAB' == strtoupper(trim((string) $record["LgRegexpWordCharacters"]))
        ) {
            $text = $record["SeText"];
            $wordlc = '[​]*' . preg_replace('/(.)/u', "$1[​]*", $wordlc);
            $pattern = "/(?<=[​])($wordlc)(?=[​])/ui";
        } else {
            $text = str_replace(array('​​','​','\r'), array('\r','','​'), $record["SeText"]);
            if ($splitEachChar) {
                $pattern = "/($wordlc)/ui";
            } else {
                $pattern = '/(?<![' . $record["LgRegexpWordCharacters"] . '])(' .
                remove_spaces($wordlc, $removeSpaces) . ')(?![' .
                $record["LgRegexpWordCharacters"] . '])/ui';
            }
        }

        $se = str_replace('​', '', preg_replace($pattern, '<b>$0</b>', $text));
        $sejs = str_replace('​', '', preg_replace($pattern, '{$0}', $text));

        if ($mode > 1) {
            if ($removeSpaces && !$splitEachChar) {
                $prevseSent = Connection::fetchValue(
                    "SELECT concat(
                        '​',
                        group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                        '​'
                    ) AS value
                    from {$this->tbpref}sentences, {$this->tbpref}textitems2
                    where Ti2SeID = SeID and SeID < $seid and SeTxID = $txtid
                    and trim(SeText) not in ('¶', '')
                    group by SeID
                    order by SeID desc"
                );
            } else {
                $prevseSent = Connection::fetchValue(
                    "SELECT SeText as value from {$this->tbpref}sentences
                    where SeID < $seid and SeTxID = $txtid
                    and trim(SeText) not in ('¶', '')
                    order by SeID desc"
                );
            }
            if (isset($prevseSent)) {
                $se = preg_replace($pattern, '<b>$0</b>', $prevseSent) . $se;
                $sejs = preg_replace($pattern, '{$0}', $prevseSent) . $sejs;
            }
        }

        if ($mode > 2) {
            if ($removeSpaces && !$splitEachChar) {
                $nextSent = Connection::fetchValue(
                    "SELECT concat(
                        '​',
                        group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                        '​'
                    ) as value
                    from {$this->tbpref}sentences, {$this->tbpref}textitems2
                    where Ti2SeID = SeID and SeID > $seid
                    and SeTxID = $txtid and trim(SeText) not in ('¶','')
                    group by SeID
                    order by SeID asc"
                );
            } else {
                $nextSent = Connection::fetchValue(
                    "SELECT SeText as value
                    FROM {$this->tbpref}sentences
                    where SeID > $seid AND SeTxID = $txtid
                    and trim(SeText) not in ('¶','')
                    order by SeID asc"
                );
            }
            if (isset($nextSent)) {
                $se .= preg_replace($pattern, '<b>$0</b>', $nextSent);
                $sejs .= preg_replace($pattern, '{$0}', $nextSent);
            }
        }

        mysqli_free_result($res);
        if ($removeSpaces) {
            $se = str_replace('​', '', $se);
            $sejs = str_replace('​', '', $sejs);
        }
        // [0]=html, word in bold, [1]=text, word in {}
        return array($se, $sejs);
    }

    /**
     * Return sentences containing a word.
     *
     * @param int      $lang   Language ID
     * @param string   $wordlc Word to look for in lowercase
     * @param int|null $wid    Word ID
     *                         - null: use $wordlc instead, simple search
     *                         - -1: use $wordlc with a more complex search
     *                         - 0 or above: find sentences containing $wid
     * @param int|null $mode   Sentences to get:
     *                         - Up to 1 is 1 sentence,
     *                         - 2 is previous and current sentence,
     *                         - 3 is previous, current and next one
     * @param int      $limit  Maximum number of sentences to return
     *
     * @return string[][] Array of sentences found
     */
    public function getSentencesWithWord(int $lang, string $wordlc, ?int $wid, ?int $mode = 0, int $limit = 20): array
    {
        $r = array();
        $res = $this->findSentencesFromWord($wid, $wordlc, $lang, $limit);
        $last = '';
        if (is_null($mode)) {
            $mode = (int) Settings::getWithDefault('set-term-sentence-count');
        }
        while ($record = mysqli_fetch_assoc($res)) {
            if ($last != $record['SeText']) {
                $sent = $this->formatSentence($record['SeID'], $wordlc, $mode);
                if (mb_strstr($sent[1], '}', false, 'UTF-8')) {
                    $r[] = $sent;
                }
            }
            $last = $record['SeText'];
        }
        mysqli_free_result($res);
        return $r;
    }

    /**
     * Show 20 sentences containing $wordlc.
     *
     * @param int      $lang      Language ID
     * @param string   $wordlc    Term in lower case.
     * @param int|null $wid       Word ID
     * @param string   $jsctlname Path for the textarea of the sentence of the word being
     *                            edited.
     * @param int      $mode      * Up to 1: return only the current sentence
     *                            * Above 1: return previous and current sentence
     *                            * Above 2: return previous, current and next sentence
     *
     * @return string HTML-formatted string of which elements are candidate sentences to use.
     */
    public function get20Sentences(int $lang, string $wordlc, ?int $wid, string $jsctlname, int $mode): string
    {
        $r = '<p><b>Sentences in active texts with <i>' . tohtml($wordlc) . '</i></b></p>
        <p>(Click on <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" />
        to copy sentence into above term)</p>';
        $sentences = $this->getSentencesWithWord($lang, $wordlc, $wid, $mode);
        foreach ($sentences as $sentence) {
            $r .= '<span class="click" onclick="{' . $jsctlname . '.value=' .
                Escaping::prepareTextdataJs($sentence[1]) . '; lwtFormCheck.makeDirty();}">
            <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" />
            </span> &nbsp;' . $sentence[0] . '<br />';
        }
        $r .= '</p>';
        return $r;
    }

    /**
     * Render the area for example sentences of a word.
     *
     * @param int    $lang     Language ID
     * @param string $termlc   Term text in lowercase
     * @param string $selector JS selector for target textarea
     * @param int    $wid      Word ID
     *
     * @return string HTML output
     */
    public function renderExampleSentencesArea(int $lang, string $termlc, string $selector, int $wid): string
    {
        ob_start();
        ?>
<div id="exsent">
    <!-- Interactable text -->
    <div id="exsent-interactable">
        <span class="click" onclick="do_ajax_show_sentences(
            <?php echo $lang; ?>, <?php echo Escaping::prepareTextdataJs($termlc); ?>,
            <?php echo htmlentities(json_encode($selector)); ?>, <?php echo $wid; ?>);">
            <img src="/assets/icons/sticky-notes-stack.png" title="Show Sentences" alt="Show Sentences" />
            Show Sentences
        </span>
    </div>
    <!-- Loading icon -->
    <img id="exsent-waiting" style="display: none;" src="/assets/icons/waiting2.gif" />
    <!-- Displayed output -->
    <div id="exsent-sentences" style="display: none;">
        <p><b>Sentences in active texts with <i><?php echo tohtml($termlc) ?></i></b></p>
        <p>
            (Click on
            <img src="/assets/icons/tick-button.png" title="Choose" alt="Choose" />
            to copy sentence into above term)
        </p>
    </div>
</div>
        <?php
        return ob_get_clean();
    }
}

} // End namespace Lwt\Services

namespace {

// =============================================================================
// GLOBAL FUNCTION WRAPPERS (for backward compatibility)
// =============================================================================

use Lwt\Services\SentenceService;

/**
 * Return a SQL string to find sentences containing a word.
 *
 * @param string $wordlc Word to look for in lowercase
 * @param int    $lid    Language ID
 *
 * @return string Query in SQL format
 *
 * @see SentenceService::buildSentencesContainingWordQuery()
 */
function sentences_containing_word_lc_query(string $wordlc, int $lid): string
{
    $service = new SentenceService();
    return $service->buildSentencesContainingWordQuery($wordlc, $lid);
}

/**
 * Perform a SQL query to find sentences containing a word.
 *
 * @param int|null $wid    Word ID or mode
 * @param string   $wordlc Word to look for in lowercase
 * @param int      $lid    Language ID
 * @param int      $limit  Maximum number of sentences to return
 *
 * @return \mysqli_result|false Query result or false on failure
 *
 * @see SentenceService::findSentencesFromWord()
 */
function sentences_from_word(?int $wid, string $wordlc, int $lid, int $limit = -1): \mysqli_result|false
{
    $service = new SentenceService();
    return $service->findSentencesFromWord($wid, $wordlc, $lid, $limit);
}

/**
 * Format the sentence(s) $seid containing $wordlc highlighting $wordlc.
 *
 * @param int    $seid   Sentence ID
 * @param string $wordlc Term text in lower case
 * @param int    $mode   Mode for sentence context
 *
 * @return string[] [0]=html, word in bold, [1]=text, word in {}
 *
 * @see SentenceService::formatSentence()
 */
function getSentence(int $seid, string $wordlc, int $mode): array
{
    $service = new SentenceService();
    return $service->formatSentence($seid, $wordlc, $mode);
}

/**
 * Return sentences containing a word.
 *
 * @param int      $lang   Language ID
 * @param string   $wordlc Word to look for in lowercase
 * @param int|null $wid    Word ID
 * @param int|null $mode   Sentences to get
 * @param int      $limit  Maximum number of sentences to return
 *
 * @return string[][] Array of sentences found
 *
 * @see SentenceService::getSentencesWithWord()
 */
function sentences_with_word(int $lang, string $wordlc, ?int $wid, ?int $mode = 0, int $limit = 20): array
{
    $service = new SentenceService();
    return $service->getSentencesWithWord($lang, $wordlc, $wid, $mode, $limit);
}

/**
 * Prepare the area for example sentences of a word.
 *
 * @param int    $lang     Language ID
 * @param string $termlc   Term text in lowercase
 * @param string $selector JS selector for target textarea
 * @param int    $wid      Word ID
 *
 * @return void Outputs HTML directly
 */
function example_sentences_area(int $lang, string $termlc, string $selector, int $wid): void
{
    $service = new SentenceService();
    echo $service->renderExampleSentencesArea($lang, $termlc, $selector, $wid);
}

/**
 * Show 20 sentences containing $wordlc.
 *
 * @param int      $lang      Language ID
 * @param string   $wordlc    Term in lower case.
 * @param int|null $wid       Word ID
 * @param string   $jsctlname Path for the textarea
 * @param int      $mode      Mode for sentence context
 *
 * @return string HTML-formatted string
 *
 * @see SentenceService::get20Sentences()
 */
function get20Sentences(int $lang, string $wordlc, ?int $wid, string $jsctlname, int $mode): string
{
    $service = new SentenceService();
    return $service->get20Sentences($lang, $wordlc, $wid, $jsctlname, $mode);
}

} // End global namespace
