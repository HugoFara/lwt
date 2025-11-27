<?php

/**
 * Call: inline_edit.php?...
 *  ...
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 */

require_once 'Core/Bootstrap/db_bootstrap.php';
require_once 'Core/Http/param_helpers.php';

use Lwt\Database\Connection;
use Lwt\Database\Escaping;

$value = (isset($_POST['value'])) ? $_POST['value'] : "";
$value = trim($value);
$id = (isset($_POST['id'])) ? $_POST['id'] : "";

if (substr($id, 0, 5) == "trans") {
    $id = substr($id, 5);
    if ($value == '') {
        $value = '*';
    }
    Connection::execute(
        'update ' . $tbpref . 'words set WoTranslation = ' .
        Escaping::toSqlSyntax(repl_tab_nl($value)) . ' where WoID = ' . $id,
        ""
    );
    echo Connection::fetchValue("select WoTranslation as value from " . $tbpref . "words where WoID = " . $id);
    exit;
}

if (substr($id, 0, 5) == "roman") {
    if ($value == '*') {
        $value = '';
    }
    $id = substr($id, 5);
    Connection::execute(
        'update ' . $tbpref . 'words set WoRomanization = ' .
        Escaping::toSqlSyntax(repl_tab_nl($value)) . ' where WoID = ' . $id,
        ""
    );
    $value = Connection::fetchValue("select WoRomanization as value from " . $tbpref . "words where WoID = " . $id);
    if ($value == '') {
        echo '*';
    } else {
        echo $value;
    }
    exit;
}

echo "ERROR - please refresh page!";
