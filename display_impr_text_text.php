<?php

/**
 * \file
 * \brief Display an improved annotated text (text frame)
 * 
 * Call: display_impr_text_text.php?text=[textid]
 * 
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/html/display__impr__text__text_8php.html
 * @since   1.5.0
 */

require_once 'inc/session_utility.php';

$textid = (int)getreq('text');
$ann = get_first_value("SELECT TxAnnotatedText AS value FROM " . $tbpref . "texts WHERE TxID = " . $textid);
$ann_exists = (strlen($ann) > 0);

if ($textid==0 || !$ann_exists) {
    header("Location: edit_texts.php");
    exit();
}

$sql = 'SELECT TxLgID, TxTitle FROM ' . $tbpref . 'texts WHERE TxID = ' . $textid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$langid = $record['TxLgID'];
mysqli_free_result($res);

$sql = 'SELECT LgTextSize, LgRemoveSpaces, LgRightToLeft FROM ' . $tbpref . 'languages WHERE LgID = ' . $langid;
$res = do_mysqli_query($sql);
$record = mysqli_fetch_assoc($res);
$textsize = $record['LgTextSize'];
$rtlScript = $record['LgRightToLeft'];
mysqli_free_result($res);

saveSetting('currenttext', $textid);


function do_diplay_impr_text_text_js() {  
?>
<script type="text/javascript">
    //<![CDATA[
    function click_ann() {var attr = $(this).attr('style');
        if(typeof attr !== 'undefined' && attr !== false && attr !== '') {
            $(this).removeAttr( 'style' );
        }
        else {
            $(this).css('color','#C8DCF0');
            $(this).css('background-color','#C8DCF0');
        }
    }

    function click_text() {bc=$('body').css('color');
        if($(this).css('color') != bc) {
            $(this).css('color','inherit');
            $(this).css('background-color','');
        }
        else {
            $(this).css('color','#E5E4E2');
            $(this).css('background-color','#E5E4E2');
        }
    }

    $(document).ready(function(){
    $('.anntransruby2').click(click_ann);
    $('.anntermruby').click(click_text);
    });
    //]]>
</script>

<?php

}

//pagestart_nobody('Display');
do_diplay_impr_text_text_js();
function do_diplay_impr_text_text_area($ann, $textsize, $rtlScript) {
    echo "<div id=\"print\"" . ($rtlScript ? ' dir="rtl"' : '') . ">";

    echo '<p style="font-size:' . $textsize . '%;line-height: 1.35; margin-bottom: 10px; ">';

    $items = preg_split('/[\n]/u', $ann);
    foreach ($items as $item) {
        do_display_impr_text_text_word($item, $textsize);
    }
    echo "</p></div>";
}

function do_display_impr_text_text_word($item, $textsize) {
    global $tbpref;
    $vals = preg_split('/[\t]/u', $item);
    if ((int)$vals[0] > -1) {
        $trans = '';
        $c = count($vals);
        $rom = '';
        if ($c > 2) {
            if ($vals[2] !== '') {
                $wid = (int)$vals[2];
                $rom = get_first_value("SELECT WoRomanization AS value FROM " . $tbpref . "words WHERE WoID = " . $wid);
                if (!isset($rom)) {
                    $rom = ''; 
                }
            }
        }
        if ($c > 3) { 
            $trans = $vals[3]; 
        }
        if ($trans == '*') { 
            $trans = $vals[1] . " "; // <- U+200A HAIR SPACE
        }     
        echo ' <ruby><rb><span class="click anntermruby" style="color:black;"' . ($rom == '' ? '' : (' title="' . tohtml($rom) . '"')) . '>' . tohtml($vals[1]) . '</span></rb><rt><span class="click anntransruby2">' . tohtml($trans) . '</span></rt></ruby> ';
    } else {
        if (count($vals) >= 2) { 
            echo str_replace(
                "¶",
                '</p><p style="font-size:' . $textsize . '%;line-height: 1.3; margin-bottom: 10px;">',
                " " . tohtml($vals[1])
            ); 
        }
    }
}

do_diplay_impr_text_text_area($ann, $textsize, $rtlScript);
//pageend();

?>
