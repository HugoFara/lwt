<?php

/**
 * Install Demo View
 *
 * Variables expected:
 * - $prefinfo: string HTML prefix info
 * - $dbname: string Database name
 * - $langcnt: int Count of existing languages
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Admin;

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
                <?php if ($langcnt > 0): ?>
                <br />The existent database will be <b>overwritten!</b>
                <?php endif; ?>
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
