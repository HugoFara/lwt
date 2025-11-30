<?php

namespace Lwt\Api\V1\Handlers;

use Lwt\Core\StringUtils;
use Lwt\Database\Connection;
use Lwt\Database\Settings;

/**
 * Handler for improved/annotated text API operations.
 *
 * Extracted from api_v1.php lines 468-823 (namespace Lwt\Ajax\Improved_Text).
 */
class ImprovedTextHandler
{
    /**
     * Make the translations choices for a term.
     *
     * @param int      $i     Word unique index in the form
     * @param int|null $wid   Word ID or null
     * @param string   $trans Current translation set for the term, may be empty
     * @param string   $word  Term text
     * @param int      $lang  Language ID
     *
     * @return string HTML-formatted string
     */
    public function makeTrans(int $i, ?int $wid, string $trans, string $word, int $lang): string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $trans = trim($trans);
        $widset = is_numeric($wid);
        $r = "";
        $set = null;
        $setDefault = null;
        if ($widset) {
            $alltrans = (string) Connection::fetchValue(
                "SELECT WoTranslation AS value FROM {$tbpref}words
                WHERE WoID = $wid"
            );
            $transarr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $alltrans);
            $set = false;
            foreach ($transarr as $t) {
                $tt = trim($t);
                if ($tt == '*' || $tt == '') {
                    continue;
                }
                $set = $set || $tt == $trans;
                $r .= '<span class="nowrap">
                    <input class="impr-ann-radio" ' .
                    ($tt == $trans ? 'checked="checked" ' : '') . 'type="radio" name="rg' .
                    $i . '" value="' . \tohtml($tt) . '" />
                    &nbsp;' . \tohtml($tt) . '
                </span>
                <br />';
            }
        }
        $set = $set || $setDefault;
        $r .= '<span class="nowrap">
        <input class="impr-ann-radio" type="radio" name="rg' . $i . '" ' .
        ($set ? 'checked="checked" ' : '') . 'value="" />
        &nbsp;
        <input class="impr-ann-text" type="text" name="tx' . $i .
        '" id="tx' . $i . '" value="' . ($set ? \tohtml($trans) : '') .
        '" maxlength="50" size="40" />
         &nbsp;
        <img class="click" src="/assets/icons/eraser.png" title="Erase Text Field"
        alt="Erase Text Field"
        onclick="$(\'#tx' . $i . '\').val(\'\').trigger(\'change\');" />
         &nbsp;
        <img class="click" src="/assets/icons/star.png" title="* (Set to Term)"
        alt="* (Set to Term)"
        onclick="$(\'#tx' . $i . '\').val(\'*\').trigger(\'change\');" />
        &nbsp;';
        if ($widset) {
            $r .=
            '<img class="click" src="/assets/icons/plus-button.png"
            title="Save another translation to existent term"
            alt="Save another translation to existent term"
            onclick="updateTermTranslation(' . $wid . ', \'#tx' . $i . '\');" />';
        } else {
            $r .=
            '<img class="click" src="/assets/icons/plus-button.png"
            title="Save translation to new term"
            alt="Save translation to new term"
            onclick="addTermTranslation(\'#tx' . $i . '\',' . json_encode($word) . ',' . $lang . ');" />';
        }
        $r .= '&nbsp;&nbsp;
        <span id="wait' . $i . '">
            <img src="/assets/icons/empty.gif" />
        </span>
        </span>';
        return $r;
    }

    /**
     * Find the possible translations for a term.
     *
     * @param int $wordId Term ID
     *
     * @return string[] Return the possible translations.
     */
    public function getTranslations(int $wordId): array
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $translations = array();
        $alltrans = (string) Connection::fetchValue(
            "SELECT WoTranslation AS value FROM {$tbpref}words
            WHERE WoID = $wordId"
        );
        $transarr = preg_split('/[' . StringUtils::getSeparators() . ']/u', $alltrans);
        foreach ($transarr as $t) {
            $tt = trim($t);
            if ($tt == '*' || $tt == '') {
                continue;
            }
            $translations[] = $tt;
        }
        return $translations;
    }

    /**
     * Gather useful data to edit a term annotation on a specific text.
     *
     * @param string $wordlc Term in lower case
     * @param int    $textid Text ID
     *
     * @return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int, term_ord?: int, translations?: string[], lang_id?: int, error?: string}
     */
    public function getTermTranslations(string $wordlc, int $textid): array
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $sql = "SELECT TxLgID, TxAnnotatedText
        FROM {$tbpref}texts WHERE TxID = $textid";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        $langid = (int)$record['TxLgID'];
        $ann = (string)$record['TxAnnotatedText'];
        if (strlen($ann) > 0) {
            $ann = \recreate_save_ann($textid, $ann);
        }
        mysqli_free_result($res);

        $annotations = preg_split('/[\n]/u', $ann);
        $i = -1;
        foreach (array_values($annotations) as $index => $annotationLine) {
            $vals = preg_split('/[\t]/u', $annotationLine);
            if ($vals === false) {
                continue;
            }
            if ($vals[0] <= -1) {
                continue;
            }
            if (trim($wordlc) != mb_strtolower(trim($vals[1]), 'UTF-8')) {
                continue;
            }
            $i = $index;
            break;
        }

        $annData = array();
        if ($i == -1) {
            $annData["error"] = "Annotation not found";
            return $annData;
        }

        $annotationLine = $annotations[$i];
        $vals = preg_split('/[\t]/u', $annotationLine);
        if ($vals === false) {
            $annData["error"] = "Annotation line is ill-formatted";
            return $annData;
        }
        $annData["term_lc"] = trim($wordlc);
        $annData["wid"] = null;
        $annData["trans"] = '';
        $annData["ann_index"] = $i;
        $annData["term_ord"] = (int)$vals[0];

        $wid = null;
        if (count($vals) > 2 && ctype_digit($vals[2])) {
            $wid = (int)$vals[2];
            $tempWid = (int)Connection::fetchValue(
                "SELECT COUNT(WoID) AS value
                FROM {$tbpref}words
                WHERE WoID = $wid"
            );
            if ($tempWid < 1) {
                $wid = null;
            }
        }
        if ($wid !== null) {
            $annData["wid"] = $wid;
            $annData["translations"] = $this->getTranslations($wid);
        }
        if (count($vals) > 3) {
            $annData["trans"] = $vals[3];
        }
        $annData["lang_id"] = $langid;
        return $annData;
    }

    /**
     * Full form for terms edition in a given text.
     *
     * @param int $textid Text ID.
     *
     * @return string HTML table for all terms
     */
    public function editTermForm(int $textid): string
    {
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $sql = "SELECT TxLgID, TxAnnotatedText
        FROM {$tbpref}texts WHERE TxID = $textid";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        $langid = (int) $record['TxLgID'];
        $ann = (string) $record['TxAnnotatedText'];
        if (strlen($ann) > 0) {
            $ann = \recreate_save_ann($textid, $ann);
        }
        mysqli_free_result($res);

        $sql = "SELECT LgTextSize, LgRightToLeft
        FROM {$tbpref}languages WHERE LgID = $langid";
        $res = Connection::query($sql);
        $record = mysqli_fetch_assoc($res);
        $textsize = (int)$record['LgTextSize'];
        if ($textsize > 100) {
            $textsize = intval($textsize * 0.8);
        }
        $rtlScript = $record['LgRightToLeft'];
        mysqli_free_result($res);

        $r =
        '<form action="" method="post">
            <table class="tab2" cellspacing="0" cellpadding="5">
                <tr>
                    <th class="th1 center">Text</th>
                    <th class="th1 center">Dict.</th>
                    <th class="th1 center">Edit<br />Term</th>
                    <th class="th1 center">
                        Term Translations (Delim.: ' .
                        \tohtml(Settings::getWithDefault('set-term-translation-delimiters')) . ')
                        <br />
                        <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(0,\'\');" />
                    </th>
                </tr>';
        $items = preg_split('/[\n]/u', $ann);
        $nontermbuffer = '';
        foreach (array_values($items) as $i => $item) {
            $vals = preg_split('/[\t]/u', $item);
            if ((int)$vals[0] > -1) {
                if ($nontermbuffer != '') {
                    $r .= '<tr>
                        <td class="td1 center" style="font-size:' . $textsize . '%;">' .
                            $nontermbuffer .
                        '</td>
                        <td class="td1 right" colspan="3">
                        <img class="click" src="/assets/icons/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
                        </td>
                    </tr>';
                    $nontermbuffer = '';
                }
                $wid = null;
                $trans = '';
                if (count($vals) > 2) {
                    $strWid = $vals[2];
                    if (is_numeric($strWid)) {
                        $tempWid = (int)Connection::fetchValue(
                            "SELECT COUNT(WoID) AS value
                            FROM {$tbpref}words
                            WHERE WoID = $strWid"
                        );
                        if ($tempWid < 1) {
                            $wid = null;
                        } else {
                            $wid = (int) $strWid;
                        }
                    } else {
                        $wid = null;
                    }
                }
                if (count($vals) > 3) {
                    $trans = $vals[3];
                }
                $wordLink = "&nbsp;";
                if ($wid !== null) {
                    $wordLink = '<a name="rec' . $i . '"></a>
                    <span class="click"
                    onclick="oewin(\'/word/edit?fromAnn=\' + $(document).scrollTop() + \'&amp;wid=' .
                    $wid . '&amp;tid=' . $textid . '&amp;ord=' . (int)$vals[0] . '\');">
                        <img src="/assets/icons/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
                    </span>';
                }
                $r .= '<tr>
                    <td class="td1 center" style="font-size:' . $textsize . '%;"' .
                    ($rtlScript ? ' dir="rtl"' : '') . '>
                        <span id="term' . $i . '">' . \tohtml($vals[1]) .
                        '</span>
                    </td>
                    <td class="td1 center" nowrap="nowrap">' .
                        \makeDictLinks($langid, $vals[1]) .
                    '</td>
                    <td class="td1 center">
                        <span id="editlink' . $i . '">' . $wordLink . '</span>
                    </td>
                    <td class="td1" style="font-size:90%;">
                        <span id="transsel' . $i . '">' .
                            $this->makeTrans($i, $wid, $trans, $vals[1], $langid) . '
                        </span>
                    </td>
                </tr>';
            } else {
                $nontermbuffer .= str_replace(
                    "¶",
                    '<img src="/assets/icons/new_line.png" title="New Line" alt="New Line" />',
                    \tohtml(trim($vals[1]))
                );
            }
        }
        if ($nontermbuffer != '') {
            $r .= '<tr>
                <td class="td1 center" style="font-size:' . $textsize . '%;">' .
                $nontermbuffer .
                '</td>
                <td class="td1 right" colspan="3">
                    <img class="click" src="/assets/icons/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
                </td>
            </tr>';
        }
        $r .= '
                    <th class="th1 center">Text</th>
                    <th class="th1 center">Dict.</th>
                    <th class="th1 center">Edit<br />Term</th>
                    <th class="th1 center">
                        Term Translations (Delim.: ' .
                        \tohtml(Settings::getWithDefault('set-term-translation-delimiters')) . ')
                        <br />
                        <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(1e6,\'\');" />
                        <a name="bottom"></a>
                    </th>
                </tr>
            </table>
        </form>';
        return $r;
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

    /**
     * Format response for getting term translations.
     *
     * @param string $termLc Term in lowercase
     * @param int    $textId Text ID
     *
     * @return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int, term_ord?: int, translations?: string[], lang_id?: int, error?: string}
     */
    public function formatTermTranslations(string $termLc, int $textId): array
    {
        return $this->getTermTranslations($termLc, $textId);
    }
}
