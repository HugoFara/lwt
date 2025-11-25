<?php

/**
 * \file
 *
 * \brief Analyse DB tables, select Table Set, start LWT
 *
 * Call: start.php
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/start.html
 * @since   1.5.4
 */

require_once 'Core/session_utility.php';

/**
 * Save a database prefix.
 *
 * @param string $pref Database prefix to save.
 *
 * @return void
 */
function start_save_prefix($pref)
{
    $tbpref = $pref;
    LWTTableSet("current_table_prefix", $tbpref);
}

/**
 * Do a short page to edit the database prefix.
 *
 * @global string $tbpref       Database table prefix
 * @global int    $fixed_tbpref If the table prefix is fixed and cannot be changed
 *
 * @return void
 */
function start_do_page()
{
    global $tbpref, $fixed_tbpref;
    $prefix = getprefixes();

    pagestart('Select Table Set', false);

    ?>

<table class="tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1">
            <form name="f1" class="inline" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <p>
                Select:
                <select name="prefix" <?php
                if ($fixed_tbpref) {
                    echo 'disabled title="Database prefix is fixed and cannot be changed!"';
                }?> >
                    <option value="" <?php echo ($tbpref == '' ? 'selected="selected"': ''); ?>>
                        Default Table Set
                    </option>
                    <?php foreach ($prefix as $value) { ?>
                    <option value="<?php echo tohtml($value); ?>" <?php echo (substr($tbpref, 0, -1) == $value ? 'selected="selected"': ''); ?>>
                        <?php echo tohtml($value); ?>
                    </option>
                    <?php } ?>
                </select>
            </p>
            <p class="center">
                <input type="submit" name="op" value="Start LWT" />
            </p>
            </form>
        </th>
    </tr>
</table>

    <?php
    pageend();
}


if (isset($_REQUEST['prefix']) && $_REQUEST['prefix'] !== '-') {
    start_save_prefix(getreq('prefix'));
    header("Location: /");
    exit();
}
start_do_page();

?>