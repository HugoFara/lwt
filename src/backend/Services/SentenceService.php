<?php declare(strict_types=1);
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
use Lwt\Core\StringUtils;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\View\Helper\IconHelper;

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
    private TextParsingService $textParsingService;

    /**
     * Constructor - initialize dependencies.
     *
     * @param TextParsingService|null $textParsingService Text parsing service (optional for BC)
     */
    public function __construct(?TextParsingService $textParsingService = null)
    {
        $this->textParsingService = $textParsingService ?? new TextParsingService();
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
        $record = QueryBuilder::table('languages')
            ->select(['LgRegexpWordCharacters', 'LgRemoveSpaces'])
            ->where('LgID', '=', $lid)
            ->firstPrepared();
        $removeSpaces = $record["LgRemoveSpaces"];

        if ('MECAB' == strtoupper(trim((string) $record["LgRegexpWordCharacters"]))) {
            $mecab_file = sys_get_temp_dir() . "/lwt_mecab_to_db.txt";
            $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n ';
            if (file_exists($mecab_file)) {
                unlink($mecab_file);
            }
            $fp = fopen($mecab_file, 'w');
            fwrite($fp, $wordlc . "\n");
            fclose($fp);
            $mecab = $this->textParsingService->getMecabPath($mecab_args);
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
            // Note: This method is deprecated and only kept for backward compatibility
            // It returns an unsafe SQL string. Use executeSentencesContainingWordQuery instead.
            $wordlc_escaped = mysqli_real_escape_string(Globals::getDbConnection(), "%$wordlc%");
            $mecab_str_escaped = mysqli_real_escape_string(Globals::getDbConnection(), "%$mecab_str%");
            $sql = "SELECT SeID, SeText,
                concat(
                    '\\t',
                    group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR '\\t'),
                    '\\t'
                ) val
                FROM sentences, textitems2
                WHERE lower(SeText)
                LIKE '$wordlc_escaped'
                AND SeID = Ti2SeID AND SeLgID = $lid AND Ti2WordCount<2
                GROUP BY SeID HAVING val
                LIKE '$mecab_str_escaped'
                ORDER BY CHAR_LENGTH(SeText), SeText";
        } else {
            // Note: This method is deprecated and only kept for backward compatibility
            // It returns an unsafe SQL string. Use executeSentencesContainingWordQuery instead.
            if ($removeSpaces == 1) {
                $pattern_value = $wordlc;
            } else {
                $pattern_value = '(^|[^' . $record["LgRegexpWordCharacters"] . '])'
                     . StringUtils::removeSpaces($wordlc, $removeSpaces)
                     . '([^' . $record["LgRegexpWordCharacters"] . ']|$)';
            }
            $pattern_escaped = mysqli_real_escape_string(Globals::getDbConnection(), $pattern_value);
            $sql = "SELECT DISTINCT SeID, SeText
                FROM sentences
                WHERE SeText RLIKE '$pattern_escaped' AND SeLgID = $lid
                ORDER BY CHAR_LENGTH(SeText), SeText";
        }
        return $sql;
    }

    /**
     * Execute a SQL query to find sentences containing a word (complex search).
     *
     * @param string $wordlc Word to look for in lowercase
     * @param int    $lid    Language ID
     * @param int    $limit  Maximum number of sentences to return
     *
     * @return array<int, array<string, mixed>> Query result rows
     */
    private function executeSentencesContainingWordQuery(string $wordlc, int $lid, int $limit = -1): array
    {
        $mecab_str = null;
        $record = QueryBuilder::table('languages')
            ->select(['LgRegexpWordCharacters', 'LgRemoveSpaces'])
            ->where('LgID', '=', $lid)
            ->firstPrepared();
        $removeSpaces = $record["LgRemoveSpaces"];

        if ('MECAB' == strtoupper(trim((string) $record["LgRegexpWordCharacters"]))) {
            $mecab_file = sys_get_temp_dir() . "/lwt_mecab_to_db.txt";
            $mecab_args = ' -F %m\\t%t\\t%h\\n -U %m\\t%t\\t%h\\n -E EOP\\t3\\t7\\n ';
            if (file_exists($mecab_file)) {
                unlink($mecab_file);
            }
            $fp = fopen($mecab_file, 'w');
            fwrite($fp, $wordlc . "\n");
            fclose($fp);
            $mecab = $this->textParsingService->getMecabPath($mecab_args);
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
                FROM sentences, textitems2
                WHERE lower(SeText) LIKE ?
                AND SeID = Ti2SeID AND SeLgID = ? AND Ti2WordCount<2
                GROUP BY SeID HAVING val LIKE ?
                ORDER BY CHAR_LENGTH(SeText), SeText";
            $params = ["%$wordlc%", $lid, "%$mecab_str%"];
        } else {
            if ($removeSpaces == 1) {
                $pattern = $wordlc;
            } else {
                $pattern = '(^|[^' . $record["LgRegexpWordCharacters"] . '])'
                     . StringUtils::removeSpaces($wordlc, $removeSpaces)
                     . '([^' . $record["LgRegexpWordCharacters"] . ']|$)';
            }
            $sql = "SELECT DISTINCT SeID, SeText
                FROM sentences
                WHERE SeText RLIKE ? AND SeLgID = ?
                ORDER BY CHAR_LENGTH(SeText), SeText";
            $params = [$pattern, $lid];
        }
        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        $stmt = Connection::prepare($sql);
        $stmt->bindValues($params);
        $stmt->execute();
        return $stmt->fetchAll();
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
     * @return array<int, array<string, mixed>> Query result rows
     */
    public function findSentencesFromWord(?int $wid, string $wordlc, int $lid, int $limit = -1): array
    {
        if (empty($wid)) {
            $sql = "SELECT DISTINCT SeID, SeText
                FROM sentences, textitems2
                WHERE LOWER(Ti2Text) = ?
                AND Ti2WoID IS NULL AND SeID = Ti2SeID AND SeLgID = ?
                ORDER BY CHAR_LENGTH(SeText), SeText";
            $params = [$wordlc, $lid];
        } elseif ($wid == -1) {
            // For complex search, build the query dynamically
            return $this->executeSentencesContainingWordQuery($wordlc, $lid, $limit);
        } else {
            $sql = "SELECT DISTINCT SeID, SeText
                FROM sentences, textitems2
                WHERE Ti2WoID = ? AND SeID = Ti2SeID AND SeLgID = ?
                ORDER BY CHAR_LENGTH(SeText), SeText";
            $params = [$wid, $lid];
        }
        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        $stmt = Connection::prepare($sql);
        $stmt->bindValues($params);
        $stmt->execute();
        return $stmt->fetchAll();
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
        $record = Connection::preparedFetchOne(
            "SELECT
            CONCAT(
                '​', group_concat(Ti2Text ORDER BY Ti2Order asc SEPARATOR '​'), '​'
            ) AS SeText, Ti2TxID AS SeTxID, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar
            FROM textitems2, languages
            WHERE Ti2LgID = LgID AND Ti2WordCount < 2 AND Ti2SeID = ?
            AND Ti2Text != '¶'",
            [$seid]
        );
        $removeSpaces = (int)$record["LgRemoveSpaces"] == 1;
        $splitEachChar = (int)$record['LgSplitEachChar'] != 0;
        $txtid = $record["SeTxID"];
        $termchar = (string) $record["LgRegexpWordCharacters"];

        if (
            ($removeSpaces && !$splitEachChar)
            || 'MECAB' == strtoupper(trim($termchar))
        ) {
            $text = $record["SeText"];
            $wordlc = '[​]*' . preg_replace('/(.)/u', "$1[​]*", $wordlc);
            $pattern = "/(?<=[​])($wordlc)(?=[​])/ui";
        } else {
            // Convert ZWS markers to proper spacing for non-remove-spaces languages
            $text = $this->convertZwsToSpacing($record["SeText"], $termchar);
            if ($splitEachChar) {
                $pattern = "/($wordlc)/ui";
            } else {
                $pattern = '/(?<![' . $termchar . '])(' .
                StringUtils::removeSpaces($wordlc, $removeSpaces) . ')(?![' .
                $termchar . '])/ui';
            }
        }

        $se = str_replace('​', '', preg_replace($pattern, '<b>$0</b>', $text));
        $sejs = str_replace('​', '', preg_replace($pattern, '{$0}', $text));

        if ($mode > 1) {
            // Always use textitems2 to get proper sentence content with word boundaries
            $prevseSent = Connection::preparedFetchValue(
                "SELECT concat(
                    '​',
                    group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                    '​'
                ) AS sentence_text
                from sentences, textitems2
                where Ti2SeID = SeID and SeID < ? and SeTxID = ?
                and trim(SeText) not in ('¶', '')
                and Ti2Text != '¶'
                group by SeID
                order by SeID desc",
                [$seid, $txtid],
                'sentence_text'
            );
            if (isset($prevseSent)) {
                if (!$removeSpaces && !($splitEachChar || 'MECAB' == strtoupper(trim($termchar)))) {
                    $prevseSent = $this->convertZwsToSpacing($prevseSent, $termchar);
                }
                $se = str_replace('​', '', preg_replace($pattern, '<b>$0</b>', $prevseSent)) . $se;
                $sejs = str_replace('​', '', preg_replace($pattern, '{$0}', $prevseSent)) . $sejs;
            }
        }

        if ($mode > 2) {
            // Always use textitems2 to get proper sentence content with word boundaries
            $nextSent = Connection::preparedFetchValue(
                "SELECT concat(
                    '​',
                    group_concat(Ti2Text order by Ti2Order asc SEPARATOR '​'),
                    '​'
                ) as value
                from sentences, textitems2
                where Ti2SeID = SeID and SeID > ?
                and SeTxID = ? and trim(SeText) not in ('¶','')
                and Ti2Text != '¶'
                group by SeID
                order by SeID asc",
                [$seid, $txtid]
            );
            if (isset($nextSent)) {
                if (!$removeSpaces && !($splitEachChar || 'MECAB' == strtoupper(trim($termchar)))) {
                    $nextSent = $this->convertZwsToSpacing($nextSent, $termchar);
                }
                $se .= str_replace('​', '', preg_replace($pattern, '<b>$0</b>', $nextSent));
                $sejs .= str_replace('​', '', preg_replace($pattern, '{$0}', $nextSent));
            }
        }

        if ($removeSpaces) {
            $se = str_replace('​', '', $se);
            $sejs = str_replace('​', '', $sejs);
        }
        // [0]=html, word in bold, [1]=text, word in {}
        return array($se, $sejs);
    }

    /**
     * Convert zero-width space (ZWS) markers to proper spacing.
     *
     * For languages that use spaces between words (LgRemoveSpaces = 0),
     * this method converts ZWS markers in the text to actual spaces where
     * appropriate (between words and after punctuation).
     *
     * @param string $text     Text with ZWS markers between tokens
     * @param string $termchar Language's word character regex pattern
     *
     * @return string Text with proper spacing
     */
    private function convertZwsToSpacing(string $text, string $termchar): string
    {
        // Step 1: Add space between consecutive word characters
        $pattern1 = "/([$termchar])​(?=[$termchar])/u";
        $result = preg_replace($pattern1, "$1 ", $text);

        // Step 2: Add space after sentence punctuation when followed by word char
        $pattern2 = "/([.!?,;:…])​(?=[$termchar])/u";
        $result = preg_replace($pattern2, "$1 ", $result);

        // Step 3: Add space after closing quotes/brackets when followed by word char
        $pattern3 = '/([\]})»›"\'」』])​(?=[' . $termchar . '])/u';
        $result = preg_replace($pattern3, '$1 ', $result);

        // Step 4: Remove remaining ZWS markers (preserving any actual space tokens)
        $result = str_replace("​", "", $result);

        return trim($result);
    }

    /**
     * Get the formatted text of a sentence by its ID.
     *
     * Reconstructs the sentence from textitems2 table with proper spacing.
     * Use this instead of reading SeText directly from sentences table.
     *
     * @param int $seid Sentence ID
     *
     * @return string|null Formatted sentence text, or null if not found
     */
    public function getSentenceText(int $seid): ?string
    {
        $record = Connection::preparedFetchOne(
            "SELECT
                CONCAT(
                    '​', GROUP_CONCAT(Ti2Text ORDER BY Ti2Order ASC SEPARATOR '​'), '​'
                ) AS SeText,
                LgRegexpWordCharacters,
                LgRemoveSpaces,
                LgSplitEachChar
            FROM textitems2
            JOIN languages ON Ti2LgID = LgID
            WHERE Ti2WordCount < 2
              AND Ti2SeID = ?
              AND Ti2Text != '¶'",
            [$seid]
        );

        if ($record === null || $record['SeText'] === null) {
            return null;
        }

        $removeSpaces = (int) $record['LgRemoveSpaces'] == 1;
        $splitEachChar = (int) $record['LgSplitEachChar'] != 0;
        $termchar = (string) $record['LgRegexpWordCharacters'];

        // For languages that don't remove spaces and don't split each char
        // (like most Western languages), apply spacing conversion
        if (!$removeSpaces && !$splitEachChar && strtoupper(trim($termchar)) !== 'MECAB') {
            $text = $this->convertZwsToSpacing($record['SeText'], $termchar);
        } else {
            // For Asian languages etc., just remove the ZWS markers
            $text = str_replace('​', '', $record['SeText']);
        }

        return trim($text);
    }

    /**
     * Get the sentence text at a specific position in a text.
     *
     * This method extracts the sentence containing the word at the given position.
     * It handles cases where texts weren't properly split into sentences during parsing
     * by finding sentence boundaries (punctuation) around the target position.
     *
     * @param int $textId   Text ID
     * @param int $position Word position (Ti2Order)
     *
     * @return string|null The sentence containing the word, or null if not found
     */
    public function getSentenceAtPosition(int $textId, int $position): ?string
    {
        // Get the sentence ID for this position
        $seid = Connection::preparedFetchValue(
            "SELECT Ti2SeID FROM textitems2 WHERE Ti2TxID = ? AND Ti2Order = ?",
            [$textId, $position],
            'Ti2SeID'
        );

        if ($seid === null) {
            return null;
        }

        // Get language settings
        $langRecord = Connection::preparedFetchOne(
            "SELECT LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar,
                    LgRegexpSplitSentences
             FROM textitems2
             JOIN languages ON Ti2LgID = LgID
             WHERE Ti2TxID = ? LIMIT 1",
            [$textId]
        );

        if ($langRecord === null) {
            return null;
        }

        $removeSpaces = (int) $langRecord['LgRemoveSpaces'] == 1;
        $splitEachChar = (int) $langRecord['LgSplitEachChar'] != 0;
        $termchar = (string) $langRecord['LgRegexpWordCharacters'];
        $splitSentence = (string) $langRecord['LgRegexpSplitSentences'];

        // Get tokens around the position (larger context to find sentence boundaries)
        // We'll get ~100 tokens before and after the target position
        $contextRange = 100;
        $minOrder = max(1, $position - $contextRange);
        $maxOrder = $position + $contextRange;

        $tokens = Connection::preparedFetchAll(
            "SELECT Ti2Order, Ti2Text, Ti2WordCount
             FROM textitems2
             WHERE Ti2TxID = ? AND Ti2SeID = ?
               AND Ti2Order >= ? AND Ti2Order <= ?
               AND Ti2Text != '¶'
             ORDER BY Ti2Order ASC",
            [$textId, $seid, $minOrder, $maxOrder]
        );

        if (empty($tokens)) {
            return null;
        }

        // Build the text with ZWS markers, tracking positions
        $textWithZws = '​';
        $positionMap = []; // Map token order to character position in text
        $currentPos = 1; // Start after initial ZWS

        foreach ($tokens as $token) {
            $order = (int) $token['Ti2Order'];
            $tokenText = (string) $token['Ti2Text'];

            $positionMap[$order] = $currentPos;
            $textWithZws .= $tokenText . '​';
            $currentPos += mb_strlen($tokenText) + 1; // +1 for ZWS
        }

        // Convert ZWS to proper spacing
        if (!$removeSpaces && !$splitEachChar && strtoupper(trim($termchar)) !== 'MECAB') {
            $text = $this->convertZwsToSpacing($textWithZws, $termchar);
        } else {
            $text = str_replace('​', '', $textWithZws);
        }

        // Get the target word text to locate it in the formatted string
        $targetWord = null;
        foreach ($tokens as $token) {
            if ((int) $token['Ti2Order'] === $position) {
                $targetWord = (string) $token['Ti2Text'];
                break;
            }
        }

        if ($targetWord === null) {
            return $this->extractCenteredPortion($text, 500);
        }

        // Find the position of the target word in the text (character position)
        $targetPos = mb_stripos($text, $targetWord);
        if ($targetPos === false) {
            return $this->extractCenteredPortion($text, 500);
        }

        // Build sentence boundary pattern - matches sentence-ending punctuation
        // followed by optional closing quotes/brackets and then whitespace or end
        $sentenceEndChars = '.!?…';
        if (!empty($splitSentence)) {
            $sentenceEndChars .= $splitSentence;
        }
        $sentenceEndPattern = '/[' . preg_quote($sentenceEndChars, '/') . ']+[\'\"\'\"»›」』\])]*(?:\s|$)/u';

        // Find the previous sentence boundary (before the target word)
        $textBefore = mb_substr($text, 0, $targetPos);
        $sentenceStart = 0;
        if (preg_match_all($sentenceEndPattern, $textBefore, $matches, PREG_OFFSET_CAPTURE)) {
            // Get the last match - this is the end of the previous sentence
            $lastMatch = end($matches[0]);
            if ($lastMatch !== false) {
                // PREG_OFFSET_CAPTURE returns byte offsets, convert to character offset
                $byteOffset = $lastMatch[1] + strlen($lastMatch[0]);
                $sentenceStart = mb_strlen(substr($textBefore, 0, $byteOffset));
            }
        }

        // Find the next sentence boundary (after the target word)
        $textAfter = mb_substr($text, $targetPos + mb_strlen($targetWord));
        $sentenceEnd = mb_strlen($text);
        if (preg_match($sentenceEndPattern, $textAfter, $match, PREG_OFFSET_CAPTURE)) {
            // PREG_OFFSET_CAPTURE returns byte offsets, convert to character offset
            $byteOffset = $match[0][1] + strlen(trim($match[0][0]));
            $charsAfterTarget = mb_strlen(substr($textAfter, 0, $byteOffset));
            $sentenceEnd = $targetPos + mb_strlen($targetWord) + $charsAfterTarget;
        }

        // Extract the sentence
        $result = trim(mb_substr($text, $sentenceStart, $sentenceEnd - $sentenceStart));

        // If still too long, extract a portion around the word
        if (mb_strlen($result) > 800) {
            $result = $this->extractPortionAroundWord($result, $targetWord, 400);
        }

        return $result ?: null;
    }

    /**
     * Extract a centered portion of text.
     *
     * @param string $text      The text to extract from
     * @param int    $maxLength Maximum length of the result
     *
     * @return string The extracted portion
     */
    private function extractCenteredPortion(string $text, int $maxLength): string
    {
        $length = mb_strlen($text);
        if ($length <= $maxLength) {
            return $text;
        }

        $start = (int) (($length - $maxLength) / 2);
        $result = mb_substr($text, $start, $maxLength);

        // Try to start/end at word boundaries
        $result = preg_replace('/^\S*\s/', '', $result);
        $result = preg_replace('/\s\S*$/', '', $result);

        return '...' . trim($result) . '...';
    }

    /**
     * Extract a portion of text centered around a specific word.
     *
     * @param string $text      The text to extract from
     * @param string $word      The word to center around
     * @param int    $maxLength Maximum characters on each side of the word
     *
     * @return string The extracted portion
     */
    private function extractPortionAroundWord(string $text, string $word, int $maxLength): string
    {
        $pos = mb_stripos($text, $word);
        if ($pos === false) {
            return $this->extractCenteredPortion($text, $maxLength * 2);
        }

        $start = max(0, $pos - $maxLength);
        $end = min(mb_strlen($text), $pos + mb_strlen($word) + $maxLength);

        $result = mb_substr($text, $start, $end - $start);

        // Try to start/end at word boundaries
        if ($start > 0) {
            $result = preg_replace('/^\S*\s/', '', $result);
            $result = '...' . trim($result);
        }
        if ($end < mb_strlen($text)) {
            $result = preg_replace('/\s\S*$/', '', $result);
            $result = trim($result) . '...';
        }

        return $result;
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
        foreach ($res as $record) {
            if ($last != $record['SeText']) {
                $sent = $this->formatSentence((int)$record['SeID'], $wordlc, $mode);
                if (mb_strstr($sent[1], '}', false, 'UTF-8')) {
                    $r[] = $sent;
                }
            }
            $last = $record['SeText'];
        }
        return $r;
    }

    /**
     * Show 20 sentences containing $wordlc.
     *
     * @param int      $lang       Language ID
     * @param string   $wordlc     Term in lower case.
     * @param int|null $wid        Word ID
     * @param string   $targetCtlId ID of the target textarea element
     * @param int      $mode       * Up to 1: return only the current sentence
     *                             * Above 1: return previous and current sentence
     *                             * Above 2: return previous, current and next sentence
     *
     * @return string HTML-formatted string of which elements are candidate sentences to use.
     */
    public function get20Sentences(int $lang, string $wordlc, ?int $wid, string $targetCtlId, int $mode): string
    {
        $r = '<p><b>Sentences in active texts with <i>' . htmlspecialchars($wordlc, ENT_QUOTES, 'UTF-8') . '</i></b></p>
        <p>(Click on ' . IconHelper::render('circle-check', ['title' => 'Choose', 'alt' => 'Choose']) . '
        to copy sentence into above term)</p>';
        $sentences = $this->getSentencesWithWord($lang, $wordlc, $wid, $mode);
        foreach ($sentences as $sentence) {
            $r .= '<span class="click" data-action="copy-sentence" ' .
                'data-target="' . htmlspecialchars($targetCtlId, ENT_QUOTES, 'UTF-8') . '" ' .
                'data-sentence="' . htmlspecialchars($sentence[1], ENT_QUOTES, 'UTF-8') . '">' .
            IconHelper::render('circle-check', ['title' => 'Choose', 'alt' => 'Choose']) .
            '</span> &nbsp;' . $sentence[0] . '<br />';
        }
        $r .= '</p>';
        return $r;
    }

    /**
     * Render the area for example sentences of a word.
     *
     * @param int    $lang        Language ID
     * @param string $termlc      Term text in lowercase
     * @param string $targetCtlId ID of the target textarea element
     * @param int    $wid         Word ID
     *
     * @return string HTML output
     */
    public function renderExampleSentencesArea(int $lang, string $termlc, string $targetCtlId, int $wid): string
    {
        ob_start();
        ?>
<div id="exsent">
    <!-- Interactable text -->
    <div id="exsent-interactable">
        <span class="click" data-action="show-sentences"
            data-lang="<?php echo $lang; ?>"
            data-termlc="<?php echo htmlspecialchars($termlc, ENT_QUOTES, 'UTF-8'); ?>"
            data-target="<?php echo htmlspecialchars($targetCtlId, ENT_QUOTES, 'UTF-8'); ?>"
            data-wid="<?php echo $wid; ?>">
            <?php echo IconHelper::render('layers', ['title' => 'Show Sentences', 'alt' => 'Show Sentences']); ?>
            Show Sentences
        </span>
    </div>
    <!-- Loading icon -->
    <?php echo IconHelper::render('loader-2', ['id' => 'exsent-waiting', 'alt' => 'Loading...', 'class' => 'icon-spin']); ?>
    <!-- Displayed output -->
    <div id="exsent-sentences">
        <p><b>Sentences in active texts with <i><?php echo htmlspecialchars($termlc, ENT_QUOTES, 'UTF-8') ?></i></b></p>
        <p>
            (Click on
            <?php echo IconHelper::render('circle-check', ['title' => 'Choose', 'alt' => 'Choose']); ?>
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
function sentencesContainingWordLcQuery(string $wordlc, int $lid): string
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
function sentencesFromWord(?int $wid, string $wordlc, int $lid, int $limit = -1): \mysqli_result|false
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
function sentencesWithWord(int $lang, string $wordlc, ?int $wid, ?int $mode = 0, int $limit = 20): array
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
function exampleSentencesArea(int $lang, string $termlc, string $selector, int $wid): void
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
