<?php

/**
 * Print a text
 *
 * Call: print_text.php?text=[textid]&...
 *      ... ann=[annotationcode] ... ann. filter
 *      ... status=[statuscode] ... status filter
 *
 * PHP version 8.1
 *
 * @category User_Interface
 */

require_once 'inc/session_utility.php';

function output_text($saveterm,$saverom,$savetrans,$savetags,
    $show_rom,$show_trans,$show_tags,$annplcmnt
): void {
    if ($show_tags) {
        if ($savetrans == '' && $savetags != '') {
            $savetrans = '* ' . $savetags;
        } else {
            $savetrans = trim($savetrans . ' ' . $savetags);
        }
    }
    if ($show_rom && $saverom == '') {
        $show_rom = 0;
    }
    if ($show_trans && $savetrans == '') {
        $show_trans = 0;
    }
    if ($annplcmnt == 1) {
        if ($show_rom || $show_trans) {
            echo ' ';
            if ($show_trans) {
                echo '<span class="anntrans">' . tohtml($savetrans) . '</span> ';
            }
            if ($show_rom  && (! $show_trans)) {
                echo '<span class="annrom">' . tohtml($saverom) . '</span> ';
            }
            if ($show_rom && $show_trans) {
                echo '<span class="annrom" dir="ltr">[' . tohtml($saverom) . ']</span> ';
            }
            echo ' <span class="annterm">';
        }
        echo tohtml($saveterm);
        if ($show_rom || $show_trans) {
            echo '</span> ';
        }
    } elseif ($annplcmnt == 2) {
        if ($show_rom || $show_trans) {
            echo ' <ruby><rb><span class="anntermruby">' . tohtml($saveterm) . '</span></rb><rt> ';
            if ($show_trans) {
                echo '<span class="anntransruby">' . tohtml($savetrans) . '</span> ';
            }
            if ($show_rom  && (! $show_trans)) {
                echo '<span class="annromrubysolo">' . tohtml($saverom) . '</span> ';
            }
            if ($show_rom && $show_trans) {
                echo '<span class="annromruby" dir="ltr">[' . tohtml($saverom) . ']</span> ';
            }
            echo '</rt></ruby> ';
        } else {
            echo tohtml($saveterm);
        }
    } else {
        /* 0 or other */
        if ($show_rom || $show_trans) {
            echo ' <span class="annterm">';
        }
        echo tohtml($saveterm);
        if ($show_rom || $show_trans) {
            echo '</span> ';
            if ($show_rom  && (! $show_trans)) {
                echo '<span class="annrom">' . tohtml($saverom) . '</span>';
            }
            if ($show_rom && $show_trans) {
                echo '<span class="annrom" dir="ltr">[' . tohtml($saverom) . ']</span> ';
            }
            if ($show_trans) {
                echo '<span class="anntrans">' . tohtml($savetrans) . '</span>';
            }
            echo ' ';
        }
    }
}

$textid = (int)getreq('text');
if ($textid==0) {
    header("Location: edit_texts.php");
    exit();
}

$ann = getreq('ann');
if ($ann == '') {
    $ann = getSetting('currentprintannotation');
}
if ($ann == '') {
    $ann = 3;
}
$show_rom = $ann & 2;
$show_trans = $ann & 1;
$show_tags = $ann & 4;

$statusrange = getreq('status');
if ($statusrange == '') {
    $statusrange = getSetting('currentprintstatus');
}
if ($statusrange == '') {
    $statusrange = 14;
}

$annplcmnt = getreq('annplcmnt');
if ($annplcmnt == '') {
    $annplcmnt = getSetting('currentprintannotationplacement');
}
if ($annplcmnt == '') {
    $annplcmnt = 0;
}

$sql = 'select TxLgID, TxTitle, TxSourceURI from ' . $tbpref . 'texts where TxID = ' . $textid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$title = (string) $record['TxTitle'];
$sourceURI = (string) $record['TxSourceURI'];
$langid = (int) $record['TxLgID'];
mysqli_free_result($res);

$sql = 'select LgTextSize, LgRemoveSpaces, LgRightToLeft from ' . $tbpref . 'languages where LgID = ' . $langid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$textsize = $record['LgTextSize'];
$rtlScript = $record['LgRightToLeft'];
mysqli_free_result($res);

saveSetting('currenttext', $textid);
saveSetting('currentprintannotation', $ann);
saveSetting('currentprintstatus', $statusrange);
saveSetting('currentprintannotationplacement', $annplcmnt);

pagestart_nobody('Print');

?>
<div class="noprint">
<div class="flex-header">
    <div>
        <?php echo_lwt_logo(); ?>
    </div>
    <div>
        <?php echo getPreviousAndNextTextLinks($textid, 'print_text.php?text=', false, ''); ?>
    </div>
<div>
<a href="do_text.php?start=<?php echo $textid; ?>" target="_top">
<img src="icn/book-open-bookmark.png" title="Read" alt="Read" /></a>
<a href="do_test.php?text=<?php echo $textid; ?>" target="_top">
<img src="icn/question-balloon.png" title="Test" alt="Test" />
</a>
<?php echo get_annotation_link($textid); ?>
<a target="_top" href="edit_texts.php?chg=<?php echo $textid; ?>">
<img src="icn/document--pencil.png" title="Edit Text" alt="Edit Text" />
</a>
</div>
<div>
<?php quickMenu(); ?>
</div>
</div>
<h1>PRINT ▶ <?php
echo tohtml($title);
(isset($record['TxSourceURI']) && substr(trim($sourceURI), 0, 1)!='#' ?
' <a href="' . $sourceURI . '" target="_blank">
<img src="'.get_file_path('icn/chain.png').'" title="Text Source" alt="Text Source" /></a>' :
'') ?></h1>
<p id="printoptions">
    Terms with <b>status(es)</b>
    <select id="status" onchange="{val=document.getElementById('status').options[document.getElementById('status').selectedIndex].value;location.href='print_text.php?text=<?php echo $textid; ?>&amp;status=' + val;}">";
<?php echo get_wordstatus_selectoptions($statusrange, true, true, false); ?>
</select> ...<br />
will be <b>annotated</b> with
<select id="ann" onchange="{val=document.getElementById('ann').options[document.getElementById('ann').selectedIndex].value;location.href='print_text.php?text=<?php echo $textid; ?>&amp;ann=' + val;}">
<option value="0"<?php echo get_selected(0, $ann); ?>>Nothing</option>
<option value="1"<?php echo get_selected(1, $ann); ?>>Translation</option>
<option value="5"<?php echo get_selected(5, $ann); ?>>Translation &amp; Tags</option>
<option value="2"<?php echo get_selected(2, $ann); ?>>Romanization</option>
<option value="3"<?php echo get_selected(3, $ann); ?>>Romanization &amp; Translation</option>
<option value="7"<?php echo get_selected(7, $ann); ?>>Romanization, Translation &amp; Tags</option>
</select>
<select id="annplcmnt" onchange="{val=document.getElementById('annplcmnt').options[document.getElementById('annplcmnt').selectedIndex].value;location.href='print_text.php?text=<?php echo $textid; ?>&amp;annplcmnt=' + val;}">
<option value="0"<?php echo get_selected(0, $annplcmnt); ?>>behind</option>
<option value="1"<?php echo get_selected(1, $annplcmnt); ?>>in front of</option>
<option value="2"<?php echo get_selected(2, $annplcmnt); ?>>above (ruby)</option>
</select> the term.<br />
<input type="button" value="Print it!" onclick="window.print();" />
(only the text below the line)
<span class="nowrap"></span>
<?php
if (((int)get_first_value("select length(TxAnnotatedText) as value from {$tbpref}texts where TxID = $textid")) > 0) {
    ?> Or
    <input type="button" value="Print/Edit/Delete"
    onclick="location.href='print_impr_text.php?text=<?php echo $textid; ?>';" /> your
    <b>Improved Annotated Text</b> <?php echo get_annotation_link($textid) ?>.
    <?php
} else {
    ?>
    <input type="button" value="Create"
    onclick="location.href='print_impr_text.php?edit=1&amp;text=<?php echo $textid; ?>';" /> an
    <b>Improved Annotated Text</b> [<img src="icn/tick.png" title="Annotated Text" alt="Annotated Text" />]."
    <?php
}
?>
</p></div>
<!-- noprint -->
<div id="print" <?php echo ($rtlScript ? 'dir="rtl"' : '') ?>>
<h2><?php echo tohtml($title); ?></h2>
<p style="font-size: <?php echo $textsize; ?>%; line-height: 1.35; margin-bottom: 10px; ">
<?php

$sql =
'SELECT
CASE WHEN Ti2WordCount>0 THEN Ti2WordCount ELSE 1 END AS Code,
CASE WHEN CHAR_LENGTH(Ti2Text)>0 THEN Ti2Text ELSE WoText END AS TiText,
Ti2Order,
CASE WHEN Ti2WordCount > 0 THEN 0 ELSE 1 END as TiIsNotWord,
WoID, WoTranslation, WoRomanization, WoStatus
FROM (
    ' . $tbpref . 'textitems2
    LEFT JOIN ' . $tbpref . 'words ON (Ti2WoID = WoID) AND (Ti2LgID = WoLgID)
)
WHERE Ti2TxID = ' . $textid . '
ORDER BY Ti2Order asc, Ti2WordCount desc';

$saveterm = '';
$savetrans = '';
$saverom = '';
$savetags = '';
$until = 0;

$res = do_mysqli_query($sql);

while ($record = mysqli_fetch_assoc($res)) {

    $actcode = (int)$record['Code'];
    $order = (int)$record['Ti2Order'];

    if ($order <= $until) {
        continue;
    }
    if ($order > $until) {
        output_text(
            $saveterm, $saverom, $savetrans, $savetags,
            $show_rom, $show_trans, $show_tags, $annplcmnt
        );
        $saveterm = '';
        $savetrans = '';
        $saverom = '';
        $savetags = '';
        $until = $order;
    }
    if ($record['TiIsNotWord'] != 0) {
        echo str_replace(
            "¶",
            '</p><p style="font-size:' . $textsize . '%;line-height: 1.3; margin-bottom: 10px;">',
            tohtml($record['TiText'])
        );
    } else {
        $until = $order + 2 * ($actcode-1);
        $saveterm = $record['TiText'];
        $savetrans = '';
        $savetags = '';
        $saverom = '';
        if (isset($record['WoID'])) {
            if (checkStatusRange((int)$record['WoStatus'], $statusrange)) {
                $savetrans = $record['WoTranslation'];
                $savetags = getWordTagList($record['WoID'], '', 1, 0);
                if ($savetrans == '*') {
                    $savetrans = '';
                }
                $saverom = trim((string) $record['WoRomanization']);
            }
        }
    }
} // while
mysqli_free_result($res);
output_text(
    $saveterm, $saverom, $savetrans, $savetags,
    $show_rom, $show_trans, $show_tags, $annplcmnt
);
echo "</p></div>";

pageend();
?>
