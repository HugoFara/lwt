<?php

/**
 * Install LWT Demo Database
 *
 * Call: install_demo.php
 *
 * PHP version 8.1
 */

require_once 'inc/session_utility.php';

$message = '';

// RESTORE DEMO

/**
 * Restore demo database from file.
 *
 * @return string Status message
 */
function restore_demo_db()
{
    $file = getcwd() . '/install_demo_db.sql';
    if (file_exists($file) ) {
        $handle = fopen($file, "r");
        if ($handle === false) {
            return "Error: File ' . $file . ' could not be opened";
            // $handle not OK
        } else {
            // $handle OK
            return restore_file($handle, "Demo Database");
        } // $handle OK
    } else {
        // restore file specified
        return "Error: File ' . $file . ' does not exist";
    }
}

if (isset($_REQUEST['install'])) {
    $message = restore_demo_db();
}

pagestart('Install LWT Demo Database', true);

echo error_message_with_hide($message, true);

$langcnt = get_first_value("SELECT COUNT(*) AS value FROM {$tbpref}languages");

if ($tbpref == '') {
    $prefinfo = "(Default Table Set)";
} else {
    $prefinfo = "(Table Set: <i>" . tohtml(substr($tbpref, 0, -1)) . "</i>)";
}

?>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure?');">
<table class="tab3" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 center">Install Demo</th>
        <td class="td1">
            <p class="smallgray2">
                The database <i><?php echo tohtml($dbname); ?></i>
                <?php echo $prefinfo; ?> will be <b>replaced</b> by the LWT
                demo database.
                <?php
                if ($langcnt > 0) {
                    ?>
                    <br />The existent database will be <b>overwritten!</b>
                    <?php
                }
                ?>

            </p>
            <p class="right">
                &nbsp;<br /><span class="red2">
                    YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;
                </span>
            <input type="submit" name="install" value="Install LWT demo database" /></p>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="&lt;&lt; Back to LWT Main Menu" onclick="location.href='index.php';" />
        </td>
    </tr>
</table>
</form>

<?php

pageend();

?>