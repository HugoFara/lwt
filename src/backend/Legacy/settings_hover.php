<?php

/**
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package Lwt
 */

use Lwt\Classes\GoogleTranslate;

require_once 'Core/session_utility.php';
require_once 'Core/classes/GoogleTranslate.php' ;

$translation = '*';
if ($_REQUEST['status']==1) {
    $tl = $_GET["tl"];
    $sl = $_GET["sl"];
    $text = $_GET["text"];

    $tl_array = GoogleTranslate::staticTranslate($text, $sl, $tl);
    if ($tl_array) {
        $translation = $tl_array[0];
    }
    if ($translation == $_GET["text"]) {
        $translation = '*';
    }

    header('Pragma: no-cache');
    header('Expires: 0');
}

$word = convert_string_to_sqlsyntax($_REQUEST['text']);
$wordlc = convert_string_to_sqlsyntax(mb_strtolower($_REQUEST['text'], 'UTF-8'));

$langid = get_first_value(
    "SELECT TxLgID AS value FROM {$tbpref}texts WHERE TxID = " .
    $_REQUEST['tid']
);

runsql(
    "INSERT INTO {$tbpref}words (
        WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
        WoRomanization, WoStatusChanged," .
        make_score_random_insert_update('iv') . ") values(
            $langid, $wordlc, $word, " . $_REQUEST["status"] . ', ' .
            convert_string_to_sqlsyntax($translation) . ', "", "", NOW(), ' .
            make_score_random_insert_update('id') . '
        )',
    "Term saved"
);
$wid = get_last_key();
do_mysqli_query(
    "UPDATE {$tbpref}textitems2 SET Ti2WoID = $wid
    WHERE Ti2LgID = $langid AND LOWER(Ti2Text) = $wordlc"
);
$hex = strToClassName(
    prepare_textdata(mb_strtolower($_REQUEST['text'], 'UTF-8'))
);

pagestart("New Term: " . $word, false);

echo '<p>Status: ' . get_colored_status_msg($_REQUEST['status']) . '</p><br />';
if ($translation != '*') {
    echo '<p>Translation: <b>' . tohtml($translation)  . '</b></p>';
}

?>
<script type="text/javascript">
    const context = window.parent.document;
    let title = '';
    if (window.parent.LWT_DATA.settings.jQuery_tooltip)
        title = make_tooltip(
            <?php echo prepare_textdata_js($_REQUEST['text']); ?>,
            <?php echo prepare_textdata_js($translation); ?>,
            '',
            '<?php echo $_REQUEST["status"]; ?>'
        );
    $('.TERM<?php echo $hex; ?>', context)
    .removeClass('status0')
    .addClass('status<?php echo $_REQUEST["status"]; ?> word<?php echo $wid; ?>')
    .attr('data_status', '<?php echo $_REQUEST["status"]; ?>')
    .attr('data_wid', '<?php echo $wid; ?>')
    .attr('title', title)
    .attr('data_trans','<?php echo tohtml($translation); ?>');
    $('#learnstatus', context)
    .html('<?php echo addslashes(todo_words_content((int) $_REQUEST['tid'])); ?>');
    cleanupRightFrames();
</script>
<?php

pageend();

?>
