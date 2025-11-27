<?php

/**
 * Analyse DB tables, and manage Table Sets
 *
 * Call: table_set_management.php
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

require_once 'Core/database_connect.php';
require_once 'Core/UI/ui_helpers.php';

$message = "";

$tbpref = null;
if (isset($_REQUEST['delpref'])) {
    if ($_REQUEST['delpref'] !== '-') {
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_archivedtexts', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_archtexttags', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_languages', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_sentences', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_tags', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_tags2', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_temptextitems', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_tempwords', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_textitems2', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_texts', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_texttags', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_words', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_newsfeeds', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_feedlinks', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_wordtags', '');
        runsql('DROP TABLE ' . $_REQUEST['delpref'] . '_settings', '');
        $message = 'Table Set "' . $_REQUEST['delpref'] . '" deleted';
        if ($_REQUEST['delpref'] == substr($tbpref, 0, -1)) {
            $tbpref = "";
            LWTTableSet("current_table_prefix", $tbpref);
        }
    }
} elseif (isset($_REQUEST['newpref'])) {
    if (in_array($_REQUEST['newpref'], getprefixes())) {
        $message = 'Table Set "' . $_REQUEST['newpref'] . '" already exists';
    } else {
        $tbpref = $_REQUEST['newpref'];
        LWTTableSet("current_table_prefix", $tbpref);
        header("Location: /");
        exit();
    }
} elseif (isset($_REQUEST['prefix'])) {
    if ($_REQUEST['prefix'] !== '-') {
        $tbpref = $_REQUEST['prefix'];
        LWTTableSet("current_table_prefix", $tbpref);
        header("Location: /");
        exit();
    }
}

pagestart('Select, Create or Delete a Table Set', false);
echo error_message_with_hide($message, false);

if ($fixed_tbpref) {
    ?>

<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
<td class="td1">
    <p>These features are not currently not available.<br /><br />
    Reason:<br /><b>DB_TABLE_PREFIX</b> is set to a fixed value in <i>.env</i>.<br />
    Please remove the definition<br /><span class="red"><b>DB_TABLE_PREFIX=<?php echo substr($tbpref, 0, -1); ?></b></span></br />
    in <i>.env</i> to make these features available.<br />
    Then try again.</p>
    <p class="right">
        &nbsp;<br />
        <input type="button" value="&lt;&lt; Back" onclick="history.back();" />
    </p>
</td>
</tr>
</table>

    <?php
} else {
    $prefix = getprefixes();

    ?>

<table class="tab2" style="width: auto;" cellspacing="0" cellpadding="5">

<tr>
<th class="th1 center">Select</th>
<td class="td1">
<form name="f1" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<p>Table Set: <select name="prefix">
<option value="-" selected="selected">[Choose...]</option>
<option value="">Default Table Set</option>
    <?php
    foreach ($prefix as $value) {
        ?>
<option value="<?php echo tohtml($value); ?>"><?php echo tohtml($value); ?></option>
        <?php
    }
    ?>
</select>
</p>
<p class="right">&nbsp;<br /><input type="submit" name="op" value="Start LWT with selected Table Set" />
</p>
</form>
</td>
</tr>

<tr>
<th class="th1 center">Create</th>
<td class="td1">
<form name="f2" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return check_table_prefix(document.f2.newpref.value);">
<p>New Table Set: <input type="text" name="newpref" value="" maxlength="20" size="20" />
</p>
<p class="right">&nbsp;<br /><input type="submit" name="op" value="Create New Table Set &amp; Start LWT" />
</p>
</form>
</td>
</tr>

<tr>
<th class="th1 center">Delete</th>
<td class="td1">
<form name="f3" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="if (document.f3.delpref.selectedIndex > 0) { return confirm('\n*** DELETING TABLE SET: ' + document.f3.delpref.options[document.f3.delpref.selectedIndex].text + ' ***\n\n*** ALL DATA IN THIS TABLE SET WILL BE LOST! ***\n\n*** ARE YOU SURE ?? ***'); } else { return true; }">
<p>Table Set: <select name="delpref">
<option value="-" selected="selected">[Choose...]</option>
    <?php
    foreach ($prefix as $value) {
        if ($value != '') {
            ?>
   <option value="<?php echo tohtml($value); ?>"><?php echo tohtml($value); ?></option>
            <?php
        }
    }
    ?>
</select>
<br />
(You cannot delete the Default Table Set.)
</p>
<p class="right">&nbsp;<br /><span class="red2">YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;</span><input type="submit" name="op" value="DELETE Table Set" />
</p>
</form>
</td>
</tr>

<tr>
<td class="td1 right" colspan="2">
<input type="button" value="&lt;&lt; Back" onclick="location.href='index.php';" /></td>
</tr>

</table>

    <?php
}

pageend();

?>
