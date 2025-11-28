<?php

/**
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package  Lwt
 */

use Lwt\Classes\GoogleTranslate;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Http/param_helpers.php';
require_once 'Core/Entity/GoogleTranslate.php' ;

$translation = '*';
if ($_REQUEST['status'] == 1) {
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

$word = Escaping::toSqlSyntax($_REQUEST['text']);
$wordlc = Escaping::toSqlSyntax(mb_strtolower($_REQUEST['text'], 'UTF-8'));

$langid = Connection::fetchValue(
    "SELECT TxLgID AS value FROM {$tbpref}texts WHERE TxID = " .
    $_REQUEST['tid']
);

Connection::execute(
    "INSERT INTO {$tbpref}words (
        WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
        WoRomanization, WoStatusChanged," .
        make_score_random_insert_update('iv') . ") values(
            $langid, $wordlc, $word, " . $_REQUEST["status"] . ', ' .
            Escaping::toSqlSyntax($translation) . ', "", "", NOW(), ' .
            make_score_random_insert_update('id') . '
        )',
    "Term saved"
);
$wid = Connection::lastInsertId();
Connection::query(
    "UPDATE {$tbpref}textitems2 SET Ti2WoID = $wid
    WHERE Ti2LgID = $langid AND LOWER(Ti2Text) = $wordlc"
);
$hex = strToClassName(
    Escaping::prepareTextdata(mb_strtolower($_REQUEST['text'], 'UTF-8'))
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
            <?php echo Escaping::prepareTextdataJs($_REQUEST['text']); ?>,
            <?php echo Escaping::prepareTextdataJs($translation); ?>,
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
