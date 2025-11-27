<?php

/**
 * Backup/Restore View
 *
 * Variables expected:
 * - $prefinfo: string HTML prefix info
 * - $dbname: string Database name
 * - $message: string Message to display (if any)
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
<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>"
method="post" onsubmit="return confirm('Are you sure?');">
<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 center">Backup</th>
        <td class="td1">
        <p class="smallgray2">
            The database <i><?php echo tohtml($dbname); ?></i>
            <?php echo $prefinfo; ?> will be exported to a gzipped SQL file.<br />
            Please keep this file in a safe place.<br />If necessary, you can
            recreate the database via the Restore function below.<br />
            The OFFICIAL LWT Backup doesn't include newsfeeds, saved text positions
            and settings.<br />
            Important: If the backup file is too large, the restore may not be
            possible (see limits below).
        </p>
        <p class="right">
            &nbsp;<br />
            <input type="submit" name="orig_backup"
            value="Download OFFICIAL LWT Backup" />
            <input type="submit" name="backup" value="Download LWT Backup" />
        </p>
    </td>
    </tr>
    <tr>
        <th class="th1 center">Restore</th>
        <td class="td1">
            <p class="smallgray2">
                The database <i><?php echo tohtml($dbname); ?></i>
                <?php echo $prefinfo; ?> will be <b>replaced</b> by the data in the
                specified backup file<br />
                (gzipped or normal SQL file, created above).<br /><br />
                <span class="smallgray">
                    Important: If the backup file is too large, the restore may not
                    be possible.<br />
                    Upload limits (in bytes):
                    <b>post_max_size = <?php echo ini_get('post_max_size'); ?> /
                    upload_max_filesize =
                    <?php echo ini_get('upload_max_filesize'); ?></b><br />
                    If needed, increase in
                    "<?php echo tohtml(php_ini_loaded_file()); ?>" and restart
                    server.<br />&nbsp;
                </span>
            </p>
            <p><input name="thefile" type="file" /></p>
            <p class="right">
                &nbsp;<br />
                <span class="red2">
                    YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;
                </span>
                <input type="submit" name="restore"
                value="Restore from LWT Backup" />
            </p>
        </td>
    </tr>
    <tr>
        <th class="th1 center">Install<br />LWT<br />Demo</th>
        <td class="td1">
            <p class="smallgray2">
                The database <i><?php echo tohtml($dbname); ?></i>
                <?php echo $prefinfo; ?> will be <b>replaced</b> by the LWT demo
                database.
            </p>
            <p class="right">
                &nbsp;<br />
                <input type="button" value="Install LWT Demo Database"
                onclick="location.href='/admin/install-demo';" />
            </p>
        </td>
    </tr>
    <tr>
        <th class="th1 center">Empty<br />Database</th>
        <td class="td1">
            <p class="smallgray2">
                Empty (= <b>delete</b> the contents of) all tables - except the
                Settings - of your database <i><?php echo tohtml($dbname); ?></i>
                <?php echo $prefinfo; ?>.
            </p>
            <p class="right">
                &nbsp;<br />
                <span class="red2">
                    YOU MAY LOSE DATA - BE CAREFUL: &nbsp; &nbsp; &nbsp;
                </span>
                <input type="submit" name="empty" value="Empty LWT Database" />
            </p>
        </td>
    </tr>
    <tr>
        <td class="td1 right" colspan="2">
            <input type="button" value="&lt;&lt; Back"
            onclick="location.href='index.php';" />
        </td>
    </tr>
</table>
</form>
