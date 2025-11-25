<?php
/**
 * \file
 * \brief Display table for Improved Annotation (Edit Mode),
 *
 * Ajax call in print_impr_text.php
 * Call: inc/ajax_edit_impr_text.php?id=[textid]
 *
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-edit-impr-text.html
 * @since   1.5.0
 */

namespace Lwt\Ajax\Improved_Text;

require_once __DIR__ . '/session_utility.php';


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
function make_trans($i, $wid, $trans, $word, $lang): string
{
    global $tbpref;
    $trans = trim($trans);
    $widset = is_numeric($wid);
    $r = "";
    if ($widset) {
        $alltrans = (string) get_first_value(
            "SELECT WoTranslation AS value FROM {$tbpref}words
            WHERE WoID = $wid"
        );
        $transarr = preg_split('/[' . get_sepas()  . ']/u', $alltrans);
        $set = false;
        $set_default = true;
        foreach ($transarr as $t) {
            $tt = trim($t);
            if ($tt == '*' || $tt == '') {
                continue;
            }
            $set_default = false;
            // true if the translation should be checked (this translation is set)
            $set = $set || $tt == $trans;
            // Add a candidate annotation
            $r .= '<span class="nowrap">
                <input class="impr-ann-radio" ' .
                ($tt == $trans ? 'checked="checked" ' : '') . 'type="radio" name="rg' .
                $i . '" value="' . tohtml($tt) . '" />
                &nbsp;' . tohtml($tt) . '
            </span>
            <br />';
        }
        ;
    }
    // Set the empty translation if no translation have been set yet
    $set = $set || $set_default;
    // Empty radio button and text field after the list of translations
    $r .= '<span class="nowrap">
    <input class="impr-ann-radio" type="radio" name="rg' . $i . '" ' .
    ($set ? 'checked="checked" ' : '') . 'value="" />
    &nbsp;
    <input class="impr-ann-text" type="text" name="tx' . $i .
    '" id="tx' . $i . '" value="' . ($set ? tohtml($trans) : '') .
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
    // Add the "plus button" to add a translation
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
        onclick="addTermTranslation(\'#tx' . $i . '\',' . prepare_textdata_js($word) . ',' . $lang . ');" />';
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
 * @param int $word_id Term ID
 *
 * @return string[] Return the possible translations.
 *
 * @psalm-return list<non-empty-string>
 */
function get_translations($word_id): array
{
    global $tbpref;
    $translations = array();
    $alltrans = (string) get_first_value(
        "SELECT WoTranslation AS value FROM {$tbpref}words
        WHERE WoID = $word_id"
    );
    $transarr = preg_split('/[' . get_sepas()  . ']/u', $alltrans);
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
 * @return (array|int|null|string)[] Return the useful data to edit a term annotation on a specific text.
 *
 * @psalm-return array{term_lc?: string, wid?: int|null, trans?: string, ann_index?: int<0, max>, term_ord?: int, translations?: array, lang_id?: int, error?: 'Annotation line is ill-formatted'|'Annotation not found'}
 */
function get_term_translations($wordlc, $textid): array
{
    global $tbpref;
    $sql = "SELECT TxLgID, TxAnnotatedText
    FROM {$tbpref}texts WHERE TxID = $textid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $langid = (int)$record['TxLgID'];
    $ann = (string)$record['TxAnnotatedText'];
    if (strlen($ann) > 0) {
        $ann = recreate_save_ann($textid, $ann);
    }
    mysqli_free_result($res);

    /*
    Unused as of LWT 2.9.0

    $textsize = (int)get_first_value(
        "SELECT LgTextSize AS value
        FROM {$tbpref}languages WHERE LgID = $langid"
    );
    if ($textsize > 100) {
        $textsize = intval($textsize * 0.8);
    }
    */

    // Get the first annotation containing the input word
    $annotations = preg_split('/[\n]/u', $ann);
    $i = -1;
    foreach (array_values($annotations) as $index => $annotation_line) {
        $vals = preg_split('/[\t]/u', $annotation_line);
        // Check if annotation could be split
        if ($vals === false) {
            continue;
        }
        // Skip when term is punctuation
        if ($vals[0] <= -1) {
            continue;
        }
        // Check if the input word is the same as the annotation
        if (trim($wordlc) != mb_strtolower(trim($vals[1]), 'UTF-8')) {
            continue;
        }
        $i = $index;
        break;
    }

    $ann_data = array();
    if ($i == -1) {
        $ann_data["error"] = "Annotation not found";
        return $ann_data;
    }

    // Get the line conatining the annotation
    $annotation_line = $annotations[$i];
    $vals = preg_split('/[\t]/u', $annotation_line);
    if ($vals === false) {
        $ann_data["error"] = "Annotation line is ill-formatted";
        return $ann_data;
    }
    $ann_data["term_lc"] = trim($wordlc);
    $ann_data["wid"] = null;
    $ann_data["trans"] = '';
    $ann_data["ann_index"] = $i;
    $ann_data["term_ord"] = (int)$vals[0];
    // Annotation should be in format "pos   term text   term ID    translation"
    $wid = null;
    // Word exists and has an ID
    if (count($vals) > 2 && ctype_digit($vals[2])) {
        $wid = (int)$vals[2];
        $temp_wid = (int)get_first_value(
            "SELECT COUNT(WoID) AS value
            FROM {$tbpref}words
            WHERE WoID = $wid"
        );
        if ($temp_wid < 1) {
            $wid = null;
        }
    }
    if ($wid !== null) {
        $ann_data["wid"] = $wid;
        // Add other translation choices
        $ann_data["translations"] = get_translations($wid);
    }
    // Current translation
    if (count($vals) > 3) {
        $ann_data["trans"] = $vals[3];
    }
    $ann_data["lang_id"] = $langid;
    return $ann_data;
}

/**
 * Prepare the HTML content for the interaction with a term
 *
 * @param string $wordlc Term in lower case
 * @param int    $textid Text ID
 *
 * @return string JS string of the different fields of the term
 *
 * @deprecated 2.9.0 Use AJAX instead
 */
function edit_term_interaction($wordlc, $textid): string
{
    $rr = "";
    $trans_data = get_term_translations($wordlc, $textid);
    if ($trans_data["wid"] !== null) {
        $plus = '<a name="rec' . $trans_data["ann_index"] . '"></a>
        <span class="click" onclick="oewin(\'edit_word.php?fromAnn=\' + $(document).scrollTop() + \'&amp;wid=' .
        $trans_data["wid"] . '\');">
            <img src="/assets/icons/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
        </span>';
    } else {
        $plus = '&nbsp;';
    }
    $rr .= "$('#editlink" . $trans_data["ann_index"] . "').html(" .
    prepare_textdata_js($plus) . ");";
    foreach ($trans_data["translations"] as $candidate_trans) {
        $plus = make_trans(
            $trans_data["ann_index"], $trans_data["wid"], $candidate_trans,
            $trans_data["term_lc"], $trans_data["lang_id"]
        );
        $rr .= "$('#transsel" . $trans_data["ann_index"] . "').html(" .
        prepare_textdata_js($plus) . ");";
    }
    return $rr;
}

/**
 * Full form for terms edition in a given text.
 *
 * @param int $textid Text ID.
 *
 * @return string HTML table for all terms
 */
function edit_term_form($textid): string
{
    global $tbpref;
    $sql = "SELECT TxLgID, TxAnnotatedText
    FROM {$tbpref}texts WHERE TxID = $textid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $langid = (int) $record['TxLgID'];
    $ann = (string) $record['TxAnnotatedText'];
    if (strlen($ann) > 0) {
        $ann = recreate_save_ann($textid, $ann);
    }
    mysqli_free_result($res);

    $sql = "SELECT LgTextSize, LgRightToLeft
    FROM {$tbpref}languages WHERE LgID = $langid";
    $res = do_mysqli_query($sql);
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
                    tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(0,\'\');" />
                </th>
            </tr>';
    $items = preg_split('/[\n]/u', $ann);
    $nontermbuffer ='';
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
                $str_wid = $vals[2];
                if (is_numeric($str_wid)) {
                    $temp_wid = (int)get_first_value(
                        "SELECT COUNT(WoID) AS value
                        FROM {$tbpref}words
                        WHERE WoID = $str_wid"
                    );
                    if ($temp_wid < 1) {
                        $wid = null;
                    } else {
                        $wid = (int) $str_wid;
                    }
                } else {
                    $wid = null;
                }
            }
            if (count($vals) > 3) {
                $trans = $vals[3];
            }
            $word_link = "&nbsp;";
            if ($wid !== null) {
                $word_link = '<a name="rec' . $i . '"></a>
                <span class="click"
                onclick="oewin(\'edit_word.php?fromAnn=\' + $(document).scrollTop() + \'&amp;wid=' .
                $wid . '&amp;tid=' . $textid . '&amp;ord=' . (int)$vals[0] . '\');">
                    <img src="/assets/icons/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
                </span>';
            }
            $r .= '<tr>
                <td class="td1 center" style="font-size:' . $textsize . '%;"' .
                ($rtlScript ? ' dir="rtl"' : '') . '>
                    <span id="term' . $i . '">' . tohtml($vals[1]) .
                    '</span>
                </td>
                <td class="td1 center" nowrap="nowrap">' .
                    makeDictLinks($langid, prepare_textdata_js($vals[1])) .
                '</td>
                <td class="td1 center">
                    <span id="editlink' . $i . '">' . $word_link . '</span>
                </td>
                <td class="td1" style="font-size:90%;">
                    <span id="transsel' . $i . '">' .
                        make_trans($i, $wid, $trans, $vals[1], $langid) . '
                    </span>
                </td>
            </tr>';
        } else {
            // Not a term, may add a new line
            $nontermbuffer .= str_replace(
                "¶",
                '<img src="/assets/icons/new_line.png" title="New Line" alt="New Line" />',
                tohtml(trim($vals[1]))
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
                    tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(1e6,\'\');" />
                    <a name="bottom"></a>
                </th>
            </tr>
        </table>
    </form>';
    return $r;
}

/**
 * Prepare the form for printed text.
 *
 * @param int    $textid Text ID
 * @param string $wordlc Lowercase word
 *
 * @return string[] HTML output and JS output
 *
 * @global string $tbpref Database table prefix.
 *
 * @psalm-return list{string, string}
 * @deprecated 2.9.0 Use AJAX instead
 */
function make_form($textid, $wordlc): array
{
    global $tbpref;
    $sql = 'SELECT TxLgID, TxAnnotatedText
    FROM ' . $tbpref . 'texts WHERE TxID = ' . $textid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $langid = (int) $record['TxLgID'];
    $ann = (string) $record['TxAnnotatedText'];
    if (strlen($ann) > 0) {
        $ann = recreate_save_ann($textid, $ann);
    }
    mysqli_free_result($res);

    $sql = 'SELECT LgTextSize, LgRightToLeft
    FROM ' . $tbpref . 'languages WHERE LgID = ' . $langid;
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $textsize = (int)$record['LgTextSize'];
    if ($textsize > 100) {
        $textsize = intval($textsize * 0.8);
    }
    $rtlScript = $record['LgRightToLeft'];
    mysqli_free_result($res);

    $rr = "";
    $r =
    '<form action="" method="post">
        <table class="tab2" cellspacing="0" cellpadding="5">
            <tr>
                <th class="th1 center">Text</th>
                <th class="th1 center">Dict.</th>
                <th class="th1 center">Edit<br />Term</th>
                <th class="th1 center">
                    Term Translations (Delim.: ' .
                    tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(0,\'\');" />
                </th>
            </tr>';
    $items = preg_split('/[\n]/u', $ann);
    $i = 0;
    $nontermbuffer ='';
    foreach ($items as $item) {
        $i++;
        $vals = preg_split('/[\t]/u', $item);
        if ($vals[0] > -1) {
            if ($nontermbuffer != '') {
                $r .= '<tr>
                    <td class="td1 center" style="font-size:' . $textsize . '%;">' .
                        $nontermbuffer .
                    '</td>
                    <td class="td1 right" colspan="3">
                    <img class="click" src="/assets/icons/tick.png" title="Back to \'Display/Print Mode\'" alt="Back to \'Display/Print Mode\'" onclick="location.href=\'print_impr_text.php?text=' . $textid . '\';" />
                    </td>
                </tr>';
                $nontermbuffer ='';
            }
            $wid = null;
            $trans = '';
            if (count($vals) > 2) {
                $str_wid = $vals[2];
                if (is_numeric($str_wid)) {
                    $temp_wid = (int)get_first_value(
                        "SELECT COUNT(WoID) AS value
                        FROM {$tbpref}words
                        WHERE WoID = $str_wid"
                    );
                    if ($temp_wid < 1) {
                        $wid = null;
                    } else {
                        $wid = (int) $str_wid;
                    }
                } else {
                    $wid = null;
                }
            }
            if (count($vals) > 3) {
                $trans = $vals[3];
            }
            $r .= '<tr>
            <td class="td1 center" style="font-size:' . $textsize . '%;"' .
            ($rtlScript ? ' dir="rtl"' : '') . '>
            <span id="term' . $i . '">' . tohtml($vals[1]) .
            '</span>
            </td>
            <td class="td1 center" nowrap="nowrap">' .
            makeDictLinks($langid, prepare_textdata_js($vals[1])) .
            '</td>
            <td class="td1 center">
            <span id="editlink' . $i . '">';
            if ($wid === null) {
                $plus = '&nbsp;';
            } else {
                $plus = '<a name="rec' . $i . '"></a>
                <span class="click" onclick="oewin(\'edit_word.php?fromAnn=\' + $(document).scrollTop() + \'&amp;wid=' . $wid . '\');">
                    <img src="/assets/icons/sticky-note--pencil.png" title="Edit Term" alt="Edit Term" />
                </span>';
            }
            $mustredo = trim($wordlc) == mb_strtolower(trim($vals[1]), 'UTF-8');
            if ($mustredo) {
                $rr .= "$('#editlink" . $i . "').html(" .
                prepare_textdata_js($plus) . ");";
            }
            $r .= $plus;
            $r .= '</span>
            </td>
            <td class="td1" style="font-size:90%;">
            <span id="transsel' . $i . '">';
            $plus = make_trans($i, $wid, $trans, $vals[1], $langid);
            if ($mustredo) {
                $rr .= "$('#transsel" . $i . "').html(" .
                prepare_textdata_js($plus) . ");";
            }
            $r .= $plus;
            $r .= '</span></td></tr>';
        } else {
            if (trim($vals[1]) != '') {
                $nontermbuffer .= str_replace(
                    "¶",
                    '<img src="/assets/icons/new_line.png" title="New Line" alt="New Line" />',
                    tohtml($vals[1])
                );
            }
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
                    tohtml(getSettingWithDefault('set-term-translation-delimiters')) . ')
                    <br />
                    <input type="button" value="Reload" onclick="do_ajax_edit_impr_text(1e6,\'\');" />
                    <a name="bottom"></a>
                </th>
            </tr>
        </table>
    </form>';
    return array($r, $rr);
}

/**
 * Do the AJAX modification for editing a printed text.
 *
 * @param int    $textid Text ID
 * @param string $wordlc Word lowercase. Can be left empty.
 *
 * @return void
 */
function do_ajax_edit_impr_text($textid, $wordlc)
{
    chdir('..');

    if ($wordlc == '') {
        // Load page, deprecated (function should be called directly)
        $html_content = edit_term_form($textid);
        echo "$('#editimprtextdata').html(" . prepare_textdata_js($html_content) . ");";
    } else {
        // Load the possible translations for a word, use AJAX instead
        $js_content = edit_term_interaction($wordlc, $textid);
        echo $js_content;
    }
}

if (isset($_POST["id"]) && isset($_POST['word'])) {
    do_ajax_edit_impr_text((int)$_POST["id"], $_POST['word']);
}

?>
