<?php declare(strict_types=1);
/**
 * Expression Service - Multi-word expression handling
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Database\UserScopedQuery;
use Lwt\Services\TextParsingService;

/**
 * Service class for multi-word expression handling.
 *
 * Contains functions for finding and inserting multi-word expressions,
 * including MeCab integration for Japanese text processing.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class ExpressionService
{
    /**
     * Find all occurrences of an expression using MeCab.
     *
     * @param string     $text Text to insert
     * @param string|int $lid  Language ID
     *
     * @return array<int, array{SeID: int, TxID: int, position: int, term: string}>
     */
    public function findMecabExpression(string $text, string|int $lid): array
    {
        $db_to_mecab = tempnam(sys_get_temp_dir(), "lwt_db_to_mecab");
        $mecab_args = " -F %m\\t%t\\t\\n -U %m\\t%t\\t\\n -E \\t\\n ";

        $parsingService = new TextParsingService();
        $mecab = $parsingService->getMecabPath($mecab_args);
        $likeText = "%$text%";
        $rows = QueryBuilder::table('sentences')
            ->select(['SeID', 'SeTxID', 'SeFirstPos', 'SeText'])
            ->where('SeLgID', '=', $lid)
            ->where('SeText', 'LIKE', $likeText)
            ->getPrepared();

        $parsed_text = '';
        $fp = fopen($db_to_mecab, 'w');
        fwrite($fp, $text);
        fclose($fp);
        $handle = popen($mecab . $db_to_mecab, "r");
        while (!feof($handle)) {
            $row = fgets($handle, 16132);
            $arr = explode("\t", $row, 4);
            // Not a word (punctuation)
            if (
                !empty($arr[0]) && $arr[0] != "EOP"
                && in_array($arr[1], ["2", "6", "7"])
            ) {
                $parsed_text .= $arr[0] . ' ';
            }
        }

        $occurrences = [];
        // For each sentence in database containing $text
        foreach ($rows as $record) {
            $sent = trim((string) $record['SeText']);
            $fp = fopen($db_to_mecab, 'w');
            fwrite($fp, $sent . "\n");
            fclose($fp);

            $handle = popen($mecab . $db_to_mecab, "r");
            $parsed_sentence = '';
            // For each word in sentence
            while (!feof($handle)) {
                $row = fgets($handle, 16132);
                $arr = explode("\t", $row, 4);
                // Not a word (punctuation)
                if (
                    !empty($arr[0]) && $arr[0] != "EOP"
                    && in_array($arr[1], ["2", "6", "7"])
                ) {
                    $parsed_sentence .= $arr[0] . ' ';
                }
            }

            // Finally we check if parsed text is in parsed sentence
            $seek = mb_strpos($parsed_sentence, $parsed_text);
            // For each occurrence of multi-word in sentence
            while ($seek !== false) {
                // pos = Number of words * 2 + initial position
                $pos = preg_match_all('/ /', mb_substr($parsed_sentence, 0, $seek)) * 2 +
                (int) $record['SeFirstPos'];
                $occurrences[] = [
                    "SeID" => (int) $record['SeID'],
                    "TxID" => (int) $record['SeTxID'],
                    "position" => $pos,
                    "term" => $text
                ];
                $seek = mb_strpos($parsed_sentence, $parsed_text, $seek + 1);
            }
            pclose($handle);
        }
        unlink($db_to_mecab);

        return $occurrences;
    }

    /**
     * Find all occurrences of an expression, do not use parsers like MeCab.
     *
     * @param string     $textlc Text to insert in lower case
     * @param string|int $lid    Language ID
     *
     * @return array<int, array{SeID: int, SeTxID: int, position: int, term: ?string, term_display: ?string}>
     */
    public function findStandardExpression(string $textlc, string|int $lid): array
    {
        $occurrences = [];
        $record = QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->getPrepared()[0] ?? null;

        $removeSpaces = $record["LgRemoveSpaces"] == 1;
        $splitEachChar = $record['LgSplitEachChar'] != 0;
        $termchar = $record['LgRegexpWordCharacters'];
        $likeTextlc = "%$textlc%";
        if ($removeSpaces && !$splitEachChar) {
            // Complex JOIN query - use raw SQL with UserScopedQuery
            $bindings = [$lid, $likeTextlc];
            $sql = "SELECT
            GROUP_CONCAT(Ti2Text ORDER BY Ti2Order SEPARATOR ' ') AS SeText, SeID,
            SeTxID, SeFirstPos, SeTxID
            FROM textitems2
            JOIN sentences
            ON SeID=Ti2SeID AND SeLgID = Ti2LgID
            WHERE Ti2LgID = ?
            AND SeText LIKE ?
            AND Ti2WordCount < 2
            GROUP BY SeID";
            $rows = Connection::preparedFetchAll($sql, $bindings);
        } else {
            $rows = QueryBuilder::table('sentences')
                ->where('SeLgID', '=', $lid)
                ->where('SeText', 'LIKE', $likeTextlc)
                ->getPrepared();
        }

        if ($splitEachChar) {
            $textlc = (string) preg_replace('/([^\s])/u', "$1 ", $textlc);
        }
        $wis = $textlc;
        $notermchar = "/[^$termchar]($textlc)[^$termchar]/ui";
        // For each sentence in the language containing the query
        $matches = null;
        $rSflag = false; // Flag to prevent repeat space-removal processing
        foreach ($rows as $record) {
            $string = ' ' . $record['SeText'] . ' ';
            if ($splitEachChar) {
                $string = preg_replace('/([^\s])/u', "$1 ", $string);
            } elseif ($removeSpaces && !$rSflag) {
                preg_match(
                    '/(?<=[ ])(' . preg_replace('/(.)/ui', "$1[ ]*", $textlc) .
                    ')(?=[ ])/ui',
                    $string,
                    $ma
                );
                if (!empty($ma[1])) {
                    $textlc = trim($ma[1]);
                    $notermchar = "/[^$termchar]($textlc)[^$termchar]/ui";
                    $rSflag = true; // Pattern found, stop further processing
                }
            }
            $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
            // For each occurrence of query in sentence
            while ($last_pos !== false) {
                if (
                    $splitEachChar || $removeSpaces
                    || preg_match($notermchar, " $string ", $matches, 0, $last_pos - 1)
                ) {
                    // Number of terms before group
                    $cnt = preg_match_all(
                        "/([$termchar]+)/u",
                        mb_substr($string, 0, $last_pos, 'UTF-8'),
                        $_
                    );
                    $pos = 2 * $cnt + (int) $record['SeFirstPos'];
                    $txt = '';
                    if ($matches[1] != $textlc) {
                        $txt = $splitEachChar ? $wis : $matches[1];
                    }
                    if ($splitEachChar || $removeSpaces) {
                        $display = $wis;
                    } else {
                        $display = $matches[1];
                    }
                    $occurrences[] = [
                        "SeID" => (int) $record['SeID'],
                        "SeTxID" => (int) $record['SeTxID'],
                        "position" => $pos,
                        "term" => $txt,
                        "term_display" => $display
                    ];
                }
                // Cut the sentence to before the right-most term starts
                $string = mb_substr($string, 0, $last_pos, 'UTF-8');
                $last_pos = mb_strripos($string, $textlc, 0, 'UTF-8');
            }
        }
        return $occurrences;
    }

    /**
     * Alter the database to add a new word.
     *
     * @param string $textlc Text in lower case
     * @param int    $lid    Language ID
     * @param int    $wid    Word ID
     * @param int    $len    Number of words in the expression
     * @param int    $mode   Function mode
     *                       - 0: Default mode, do nothing special
     *                       - 1: Runs an expression inserter interactable
     *                       - 2: Return the sql output
     *
     * @return string|null If $mode == 2 return values to insert in textitems2, nothing otherwise.
     */
    public function insertExpressions(string $textlc, int $lid, int $wid, int $len, int $mode): string|null
    {
        $regexp = (string)(QueryBuilder::table('languages')
            ->where('LgID', '=', $lid)
            ->valuePrepared('LgRegexpWordCharacters') ?? '');

        if ('MECAB' == strtoupper(trim($regexp))) {
            $occurrences = $this->findMecabExpression($textlc, $lid);
        } else {
            $occurrences = $this->findStandardExpression($textlc, $lid);
        }

        // Update the term visually through JS
        if ($mode == 0) {
            /** @var array<int, array<int, string>> $appendtext */
            $appendtext = [];
            foreach ($occurrences as $occ) {
                $txId = $occ['SeTxID'] ?? $occ['TxID'] ?? 0;
                $appendtext[$txId] = [];
                if (Settings::getZeroOrOne('showallwords', 1)) {
                    $appendtext[$txId][$occ['position']] = "&nbsp;$len&nbsp";
                } else {
                    if ('MECAB' == strtoupper(trim($regexp))) {
                        $appendtext[$txId][$occ['position']] = (string)($occ['term'] ?? '');
                    } else {
                        $appendtext[$txId][$occ['position']] = (string)($occ['term_display'] ?? $occ['term'] ?? '');
                    }
                }
            }
            $hex = StringUtils::toClassName(Escaping::prepareTextdata($textlc));
            $this->newMultiWordInteractable($hex, $appendtext, $wid, $len);
        }
        $sqltext = null;
        if (!empty($occurrences)) {
            $placeholders = [];
            $params = [];
            foreach ($occurrences as $occ) {
                $txId = $occ["SeTxID"] ?? $occ["TxID"] ?? 0;
                $placeholders[] = "(?, ?, ?, ?, ?, ?, ?)";
                $params[] = $wid;
                $params[] = $lid;
                $params[] = $txId;
                $params[] = $occ["SeID"];
                $params[] = $occ["position"];
                $params[] = $len;
                $params[] = $occ["term"];
            }

            if ($mode == 2) {
                // Legacy mode: return SQL text for external use
                // Build the VALUES portion with escaped data
                $sqlarr = [];
                foreach ($occurrences as $occ) {
                    $txId = $occ["SeTxID"] ?? $occ["TxID"] ?? 0;
                    $term = $occ["term"] === null ? 'NULL' : "'" . mysqli_real_escape_string(Globals::getDbConnection(), $occ["term"]) . "'";
                    $sqlarr[] = "($wid, $lid, $txId, {$occ["SeID"]}, {$occ["position"]}, $len, $term)";
                }
                return implode(',', $sqlarr);
            }

            $sql = "INSERT INTO textitems2
                 (Ti2WoID,Ti2LgID,Ti2TxID,Ti2SeID,Ti2Order,Ti2WordCount,Ti2Text)
                 VALUES " . implode(',', $placeholders);
            Connection::preparedExecute($sql, $params);
        }
        return null;
    }

    /**
     * Prepare a JavaScript dialog to insert a new expression.
     *
     * @param string     $hex        Lowercase text, formatted version of the text.
     * @param array<int, array<int, string>> $multiwords Multi-words to append, format [textid][position][text]
     * @param int        $wid        Term ID
     * @param int        $len        Words count.
     *
     * @return void
     */
    public function newMultiWordInteractable(string $hex, array $multiwords, int $wid, int $len): void
    {
        $showAll = (bool)Settings::getZeroOrOne('showallwords', 1);
        $showType = $showAll ? "m" : "";

        $record = QueryBuilder::table('words')
            ->where('WoID', '=', $wid)
            ->getPrepared()[0] ?? null;

        $attrs = [
            "class" => "click mword {$showType}wsty TERM$hex word$wid status" .
            $record["WoStatus"],
            "data_trans" => $record["WoTranslation"],
            "data_rom" => $record["WoRomanization"],
            "data_code" => $len,
            "data_status" => $record["WoStatus"],
            "data_wid" => $wid
        ];

        ?>
<script type="application/json" data-lwt-multiword-config>
<?php echo json_encode([
    'attrs' => $attrs,
    'multiWords' => $multiwords,
    'hex' => $hex,
    'showAll' => $showAll
]); ?>
</script>
        <?php
        flush();
    }

    /**
     * Prepare a JavaScript dialog to insert a new expression (version 2).
     *
     * @param string   $hex        Lowercase text, formatted version of the text.
     * @param string[] $appendtext Text to append
     * @param int      $wid        Term ID
     * @param int      $len        Words count.
     *
     * @return void
     */
    public function newExpressionInteractable2(string $hex, array $appendtext, int $wid, int $len): void
    {
        $showAll = (bool)Settings::getZeroOrOne('showallwords', 1);
        $showType = $showAll ? "m" : "";

        $record = QueryBuilder::table('words')
            ->where('WoID', '=', $wid)
            ->getPrepared()[0] ?? null;

        $attrs = [
            "class" => "click mword {$showType}wsty TERM$hex word$wid status" .
            $record["WoStatus"],
            "data_trans" => $record["WoTranslation"],
            "data_rom" => $record["WoRomanization"],
            "data_code" => $len,
            "data_status" => $record["WoStatus"],
            "data_wid" => $wid
        ];

        $term = array_values($appendtext)[0];

        ?>
<script type="application/json" data-lwt-expression-config>
<?php echo json_encode([
    'attrs' => $attrs,
    'appendText' => $appendtext,
    'term' => $term,
    'len' => $len,
    'hex' => $hex,
    'showAll' => $showAll
]); ?>
</script>
        <?php
        flush();
    }
}
