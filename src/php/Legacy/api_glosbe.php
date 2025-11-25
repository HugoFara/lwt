<?php

/**
 * Call Glosbe Translation API, analyze and present JSON results
 * for easily filling the "new word form"
 *
 * Call: glosbe_api.php?from=...&dest=...&phrase=...
 *  ... from=L2 language code (see Glosbe)
 *  ... dest=L1 language code (see Glosbe)
 *  ... phrase=... word or expression to be translated by
 *                  Glosbe API (see http://glosbe.com/a-api)
 *
 * PHP version 8.1
 */

require_once 'inc/session_utility.php';

$from = trim($_REQUEST["from"]);
$dest = trim($_REQUEST["dest"]);
$destorig = $dest;
$phrase = mb_strtolower(trim($_REQUEST["phrase"]), 'UTF-8');

pagestart_nobody('');
$titletext = '<a href="http://glosbe.com/' . $from . '/' . $dest . '/' . $phrase . '">Glosbe Dictionary (' . tohtml($from) . "-" . tohtml($dest) . "):  &nbsp; <span class=\"red2\">" . tohtml($phrase) . "</span></a>";
echo '<h3>' . $titletext . ' <img id="del_translation" src="/icn/broom.png" title="Empty Translation Field" style="cursor:pointer" onclick="deleteTranslation ();"></img></h3>';
echo '<p>(Click on <img src="/icn/tick-button.png" title="Choose" alt="Choose" /> to copy word(s) into above term)<br />&nbsp;</p>';

?>
<script type="text/javascript">
    //<![CDATA[
    $(document).ready( function() {
    <?php
    if($from=='' or $dest=='') {
        echo '$("body").html("<p class=\"red\">There seems to be something wrong with the Glosbe API!</p><p class=\"red\">Please check the dictionaries in the Language Settings!</p>"); ';
    } else if($phrase=='') {
        echo '$("body").html("<p class=\"msgblue\">Term is not set!</p>");';
    } else {
        ?>
        var w = window.parent.frames['ro'];
        if (typeof w == 'undefined') w = window.opener;
        if (typeof w == 'undefined')$('#del_translation').remove();
        getGlosbeTranslation(<?php echo "'" ,urlencode($phrase) ,"','",$from,"','",$dest,"'"; ?>);
        <?php
    }     ?>
    });
    //]]>
</script>
<p id="translations"></p>
<?php

echo '&nbsp;<form action="glosbe_api.php" method="get">Unhappy?<br/>Change term:
<input type="text" name="phrase" maxlength="250" size="15" value="' . tohtml($phrase) . '">
<input type="hidden" name="from" value="' . tohtml($from) . '">
<input type="hidden" name="dest" value="' . tohtml($destorig) . '">
<input type="submit" value="Translate via Glosbe">
</form>';

pageend();

?>
