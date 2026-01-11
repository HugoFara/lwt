<?php

/**
 * Text Reading Service - Functions for displaying text in reading view.
 *
 * Functions for displaying text with word statuses, translations, and annotations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0 Migrated from Core/Text/text_display.php
 */

declare(strict_types=1);

namespace Lwt\Modules\Text\Application\Services;

use Lwt\Core\Globals;
use Lwt\Core\StringUtils;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use Lwt\Modules\Tags\Application\TagsFacade;

/**
 * Service class for text reading display.
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UnusedClass - Service class for text reading functionality
 */
class TextReadingService
{
    /**
     * Print the output when the word is a term.
     *
     * @param int                                          $actcode       Action code, > 1 for multiword
     * @param bool                                         $showAll       Show all words or not
     * @param string                                       $spanid        ID for this span element
     * @param string                                       $hidetag       Hide tag string
     * @param int                                          $currcharcount Current number of characters
     * @param array<string, mixed>                         $record        Various data from database
     * @param array<int, array{0: int, 1: string, 2: int}> $exprs         Current expressions (passed by reference)
     *
     * @return void
     */
    public function echoTerm(
        int $actcode,
        bool $showAll,
        string $spanid,
        string $hidetag,
        int $currcharcount,
        array $record,
        array &$exprs = array()
    ): void {
        $actcode = (int)$record['Code'];
        if ($actcode > 1) {
            // A multiword, $actcode is the number of words composing it
            $tiText = (string)($record['TiText'] ?? '');
            $lastExpr = !empty($exprs) ? $exprs[sizeof($exprs) - 1] : null;
            if ($lastExpr === null || $lastExpr[1] != $tiText) {
                $exprs[] = array($actcode, $tiText, $actcode);
            }

            if (isset($record['WoID'])) {
                $woId = (int)$record['WoID'];
                $woStatus = (int)$record['WoStatus'];
                $ti2Order = (int)$record['Ti2Order'];
                $tiTextLC = (string)($record['TiTextLC'] ?? '');
                $attributes = array(
                    'id' => $spanid,
                    'class' => implode(
                        " ",
                        [
                            $hidetag, "click", "mword", ($showAll ? 'mwsty' : 'wsty'),
                            "order" . $ti2Order,
                            'word' . $woId, 'status' . $woStatus,
                            'TERM' . StringUtils::toClassName($tiTextLC)
                        ]
                    ),
                    'data_pos' => $currcharcount,
                    'data_order' => $ti2Order,
                    'data_wid' => $woId,
                    'data_trans' => htmlspecialchars(
                        ExportService::replaceTabNewline((string)($record['WoTranslation'] ?? '')) .
                        (($tags = TagsFacade::getWordTagList($woId, false)) ? ' [' . $tags . ']' : ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ),
                    'data_rom' => htmlspecialchars((string)($record['WoRomanization'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'data_status' => $woStatus,
                    'data_code' => $actcode,
                    'data_text' => htmlspecialchars($tiText, ENT_QUOTES, 'UTF-8')
                );
                $span = '<span';
                foreach ($attributes as $attr_name => $val) {
                    $span .= ' ' . $attr_name . '="' . (string)$val . '"';
                }
                $span .= '>';
                if ($showAll) {
                    $span .= $actcode;
                } else {
                    $span .= htmlspecialchars($tiText, ENT_QUOTES, 'UTF-8');
                }
                $span .= '</span>';
                echo $span;
            }
        } else {
            // Single word
            $tiText = (string)($record['TiText'] ?? '');
            $tiTextLC = (string)($record['TiTextLC'] ?? '');
            $ti2Order = (int)$record['Ti2Order'];
            if (isset($record['WoID'])) {
                // Word found status 1-5|98|99
                $woId = (int)$record['WoID'];
                $woStatus = (int)$record['WoStatus'];
                $attributes = array(
                    'id' => $spanid,
                    'class' => implode(
                        " ",
                        [
                            $hidetag, "click", "word", "wsty", "word" . $woId,
                            'status' . $woStatus,
                            'TERM' . StringUtils::toClassName($tiTextLC)
                        ]
                    ),
                    'data_pos' => $currcharcount,
                    'data_order' => $ti2Order,
                    'data_wid' => $woId,
                    'data_trans' => htmlspecialchars(
                        ExportService::replaceTabNewline((string)($record['WoTranslation'] ?? '')) .
                        (($tags = TagsFacade::getWordTagList($woId, false)) ? ' [' . $tags . ']' : ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ),
                    'data_rom' => htmlspecialchars((string)($record['WoRomanization'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'data_status' => $woStatus
                );
            } else {
                // Not registered word (status 0)
                $attributes = array(
                    'id' => $spanid,
                    'class' => implode(
                        " ",
                        [
                            $hidetag, "click", "word", "wsty", "status0",
                            "TERM" . StringUtils::toClassName($tiTextLC)
                        ]
                    ),
                    'data_pos' => $currcharcount,
                    'data_order' => $ti2Order,
                    'data_trans' => '',
                    'data_rom' => '',
                    'data_status' => '0',
                    'data_wid' => ''
                );
            }
            foreach ($exprs as $expr) {
                $attributes['data_mw' . $expr[0]] = htmlspecialchars($expr[1], ENT_QUOTES, 'UTF-8');
            }
            $span = '<span';
            foreach ($attributes as $attr_name => $val) {
                $span .= ' ' . $attr_name . '="' . (string)$val . '"';
            }
            $span .= '>' . htmlspecialchars($tiText, ENT_QUOTES, 'UTF-8') . '</span>';
            echo $span;
            for ($i = sizeof($exprs) - 1; $i >= 0; $i--) {
                /**
 * @var array{0: int, 1: string, 2: int} $currentExpr
*/
                $currentExpr = $exprs[$i];
                $currentExpr[2]--;
                $exprs[$i] = $currentExpr;
                if ($currentExpr[2] < 1) {
                    unset($exprs[$i]);
                    $exprs = array_values($exprs);
                }
            }
        }
    }

    /**
     * Check if a new sentence SPAN should be started.
     *
     * @param int $sid     Sentence ID
     * @param int $old_sid Old sentence ID
     *
     * @return int Sentence ID
     */
    public function parseSentence(int $sid, int $old_sid): int
    {
        if ($sid == $old_sid) {
            return $sid;
        }
        if ($sid != 0) {
            echo '</span>';
        }
        $sid = $old_sid;
        echo '<span id="sent_', $sid, '">';
        return $sid;
    }

    /**
     * Process each text item (can be punctuation, term, etc...)
     *
     * @param array<string, mixed>                         $record        Text item information
     * @param int                                          $showAll       Show all words or not (0 or 1)
     * @param int                                          $currcharcount Current number of characters
     * @param bool                                         $hide          Should some item be hidden,
     *                                                                     depends on $showAll
     * @param array<int, array{0: int, 1: string, 2: int}> $exprs         Current expressions
     *
     * @return void
     */
    public function parseItem(
        array $record,
        int $showAll,
        int $currcharcount,
        bool $hide,
        array &$exprs = array()
    ): void {
        $actcode = (int)$record['Code'];
        $order = (int)$record['Ti2Order'];
        $spanid = 'ID-' . $order . '-' . $actcode;

        // Check if item should be hidden
        $hidetag = $hide ? ' hide' : '';

        if ($record['TiIsNotWord'] != 0) {
            // The current item is not a term (likely punctuation)
            $text = (string)($record['TiText'] ?? '');
            // Add 'punc' class for punctuation (non-whitespace non-words)
            $puncClass = (trim($text) !== '' && !ctype_space($text)) ? 'punc' : '';
            $classes = trim($hidetag . ' ' . $puncClass);
            echo "<span id=\"$spanid\" class=\"$classes\">" .
            str_replace("¶", '<br />', htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) . '</span>';
        } else {
            // A term (word or multi-word)
            $this->echoTerm(
                $actcode,
                (bool)$showAll,
                $spanid,
                $hidetag,
                $currcharcount,
                $record,
                $exprs
            );
        }
    }

    /**
     * Get all words and start the iterate over them.
     *
     * @param int $textId  ID of the text
     * @param int $showAll Show all words or not (0 or 1)
     *
     * @return void
     */
    public function mainWordLoop(int $textId, int $showAll): void
    {
        $res = QueryBuilder::table('word_occurrences')
            ->selectRaw('CASE WHEN `Ti2WordCount`>0 THEN Ti2WordCount ELSE 1 END AS Code')
            ->selectRaw('CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE `WoText` END AS TiText')
            ->selectRaw('CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN LOWER(Ti2Text) ELSE `WoTextLC` END AS TiTextLC')
            ->select(['Ti2Order', 'Ti2SeID'])
            ->selectRaw('CASE WHEN `Ti2WordCount`>0 THEN 0 ELSE 1 END AS TiIsNotWord')
            ->selectRaw(
                'CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN CHAR_LENGTH(Ti2Text) ' .
                'ELSE CHAR_LENGTH(`WoTextLC`) END AS TiTextLength'
            )
            ->select(['WoID', 'WoText', 'WoStatus', 'WoTranslation', 'WoRomanization'])
            ->leftJoin('words', 'Ti2WoID', '=', 'WoID')
            ->where('Ti2TxID', '=', $textId)
            ->orderBy('Ti2Order', 'ASC')
            ->orderBy('Ti2WordCount', 'DESC')
            ->getPrepared();
        $currcharcount = 0;
        $hidden_items = array();
        $exprs = array();
        $cnt = 1;
        $sid = 0;
        $last = -1;

        // Loop over words and punctuation
        foreach ($res as $record) {
            $sid = $this->parseSentence($sid, (int) $record['Ti2SeID']);
            if ($cnt < $record['Ti2Order']) {
                echo '<span id="ID-' . $cnt++ . '-1"></span>';
            }
            if ($showAll) {
                $hide = isset($record['WoID'])
                && array_key_exists((int) $record['WoID'], $hidden_items);
            } else {
                $hide = $record['Ti2Order'] <= $last;
            }

            $this->parseItem($record, $showAll, $currcharcount, $hide, $exprs);
            if ((int)$record['Code'] == 1) {
                $currcharcount += (int)$record['TiTextLength'];
                $cnt++;
            }
            $last = max(
                $last,
                (int) $record['Ti2Order'] + ((int)$record['Code'] - 1) * 2
            );
            if ($showAll) {
                if (
                    isset($record['WoID'])
                    && !array_key_exists((int) $record['WoID'], $hidden_items)
                ) {
                    $hidden_items[(int) $record['WoID']] = (int) $record['Ti2Order']
                    + ((int)$record['Code'] - 1) * 2;
                }
                // Clean the already finished items
                $hidden_items = array_filter(
                    $hidden_items,
                    fn ($val) => $val >= $record['Ti2Order'],
                );
            }
        }

        echo '<span id="totalcharcount" class="is-hidden">' . $currcharcount . '</span>';
    }
}
