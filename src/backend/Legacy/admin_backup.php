<?php

/**
 * \file
 * \brief Backup/Restore/Empty LWT Database
 *
 * Call: backup_restore.php?....
 *  ... restore=xxx ... do restore
 *  ... backup=xxx ... do backup
 * ... empty=xxx ... do truncate
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/backup-restore.html
 * @since    1.0.3
 */

require_once 'Core/database_connect.php';
require_once 'Core/UI/ui_helpers.php';
require_once 'Core/Text/text_helpers.php';
require_once 'Core/Http/param_helpers.php';

$message = '';

if ($tbpref == '') {
    $pref = "";
} else {
    $pref = substr($tbpref, 0, -1) . "-";
}


$num_fields = null;
$result = null;
if (isset($_REQUEST['restore'])) {
    // RESTORE
    if (
        isset($_FILES["thefile"]) && $_FILES["thefile"]["tmp_name"] != ""
        && $_FILES["thefile"]["error"] == 0
    ) {
        $handle = gzopen($_FILES["thefile"]["tmp_name"], "r");
        if ($handle === false) {
            // $handle not OK
            $message = "Error: Restore file could not be opened";
        } else {
            // $handle OK
            $message = restore_file($handle, "Database");
        }
    } else {
        // restore file specified
        $message = "Error: No Restore file specified";
    }
} elseif (isset($_REQUEST['backup'])) {
    // BACKUP
    $tables = array(
        'archivedtexts', 'archtexttags', 'feedlinks', 'languages', 'textitems2',
        'newsfeeds', 'sentences', 'settings', 'tags', 'tags2', 'texts', 'texttags',
        'words', 'wordtags'
    );
    $fname = "lwt-backup-exp_version-" . $pref . date('Y-m-d-H-i-s') . ".sql.gz";
    $out = "-- " . $fname . "\n";
    foreach ($tables as $table) {
        // foreach table
        $result = do_mysqli_query('SELECT * FROM ' . $tbpref . $table);
        $num_fields = mysqli_num_fields($result);
        $out .= "\nDROP TABLE IF EXISTS " . $table . ";\n";
        $row2 = mysqli_fetch_row(do_mysqli_query("SHOW CREATE TABLE $tbpref$table"));
        $out .= str_replace(
            $tbpref . $table,
            $table,
            str_replace("\n", " ", $row2[1])
        ) . ";\n";
        if ($table !== 'sentences' && $table !== 'textitems2') {
            while ($row = mysqli_fetch_row($result)) {
                // foreach record
                $return = 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    // foreach field
                    $return .= convert_string_to_sqlsyntax_nonull($row[$j]);
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                } // foreach field
                $out .= $return . ");\n";
            } // foreach record
        } // if
    } // foreach table
    header('Content-Type: application/plain');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    echo gzencode($out);
    exit();
} elseif (isset($_REQUEST['orig_backup'])) {
    $tables = array(
        'archivedtexts', 'archtexttags', 'languages', 'sentences', 'settings',
        'tags', 'tags2', 'textitems', 'texts', 'texttags', 'words', 'wordtags'
    );
    $fname = "lwt-backup-" . $pref . date('Y-m-d-H-i-s') . ".sql.gz";
    $out = "-- " . $fname . "\n";

    foreach ($tables as $table) {
        if ($table == 'texts') {
            $result = do_mysqli_query(
                'SELECT TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI,
                TxSourceURI
                FROM ' . $tbpref . $table
            );
            $num_fields = 7;
        } elseif ($table == 'words') {
            $result = do_mysqli_query(
                'SELECT WoID, WoLgID, WoText, WoTextLC, WoStatus, WoTranslation,
                WoRomanization, WoSentence, WoCreated, WoStatusChanged, WoTodayScore,
                WoTomorrowScore, WoRandom
                FROM ' . $tbpref . $table
            );
            $num_fields = 13;
        } elseif ($table == 'languages') {
            $result = do_mysqli_query(
                'SELECT LgID, LgName, LgDict1URI, LgDict2URI,
                REPLACE(
                    LgGoogleTranslateURI, "ggl.php", "http://translate.google.com"
                ) AS LgGoogleTranslateURI,
                LgExportTemplate, LgTextSize, LgCharacterSubstitutions,
                LgRegexpSplitSentences, LgExceptionsSplitSentences,
                LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar,
                LgRightToLeft
                FROM ' . $tbpref . 'languages where LgName<>""'
            );
            $num_fields = mysqli_num_fields($result);
        } elseif (
            $table !== 'sentences' && $table !== 'textitems'
            && $table !== 'settings'
        ) {
            $result = do_mysqli_query('SELECT * FROM ' . $tbpref . $table);
            $num_fields = mysqli_num_fields($result);
        }
        $out .= "\nDROP TABLE IF EXISTS " . $table . ";\n";

        switch ($table) {
            case 'archivedtexts':
                $out .= "CREATE TABLE `archivedtexts` (
                `AtID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `AtLgID` int(11) unsigned NOT NULL,
                `AtTitle` varchar(200) NOT NULL,
                `AtText` text NOT NULL,
                `AtAnnotatedText` longtext NOT NULL,
                `AtAudioURI` varchar(200) DEFAULT NULL,
                `AtSourceURI` varchar(1000) DEFAULT NULL,
                PRIMARY KEY (`AtID`),
                KEY `AtLgID` (`AtLgID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'archtexttags':
                $out .= "CREATE TABLE `archtexttags` (
                `AgAtID` int(11) unsigned NOT NULL,
                `AgT2ID` int(11) unsigned NOT NULL,
                PRIMARY KEY (`AgAtID`,`AgT2ID`),
                KEY `AgAtID` (`AgAtID`),
                KEY `AgT2ID` (`AgT2ID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'languages':
                $out .= "CREATE TABLE `languages` (
                `LgID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `LgName` varchar(40) NOT NULL,
                `LgDict1URI` varchar(200) NOT NULL,
                `LgDict2URI` varchar(200) DEFAULT NULL,
                `LgGoogleTranslateURI` varchar(200) DEFAULT NULL,
                `LgExportTemplate` varchar(1000) DEFAULT NULL,
                `LgTextSize` int(5) unsigned NOT NULL DEFAULT '100',
                `LgCharacterSubstitutions` varchar(500) NOT NULL,
                `LgRegexpSplitSentences` varchar(500) NOT NULL,
                `LgExceptionsSplitSentences` varchar(500) NOT NULL,
                `LgRegexpWordCharacters` varchar(500) NOT NULL,
                `LgRemoveSpaces` int(1) unsigned NOT NULL DEFAULT '0',
                `LgSplitEachChar` int(1) unsigned NOT NULL DEFAULT '0',
                `LgRightToLeft` int(1) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`LgID`),
                UNIQUE KEY `LgName` (`LgName`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'sentences':
                $out .= "CREATE TABLE `sentences` (
                `SeID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `SeLgID` int(11) unsigned NOT NULL,
                `SeTxID` int(11) unsigned NOT NULL,
                `SeOrder` int(11) unsigned NOT NULL,
                `SeText` text,
                PRIMARY KEY (`SeID`),
                KEY `SeLgID` (`SeLgID`),
                KEY `SeTxID` (`SeTxID`),
                KEY `SeOrder` (`SeOrder`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'settings':
                $out .= "CREATE TABLE `settings` (
                `StKey` varchar(40) NOT NULL,
                `StValue` varchar(40) DEFAULT NULL,
                PRIMARY KEY (`StKey`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'tags':
                $out .= "CREATE TABLE `tags` (
                `TgID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `TgText` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                `TgComment` varchar(200) NOT NULL DEFAULT '',
                PRIMARY KEY (`TgID`),
                UNIQUE KEY `TgText` (`TgText`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'tags2':
                $out .= "CREATE TABLE `tags2` (
                `T2ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `T2Text` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                `T2Comment` varchar(200) NOT NULL DEFAULT '',
                PRIMARY KEY (`T2ID`),
                UNIQUE KEY `T2Text` (`T2Text`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'textitems':
                $out .= "CREATE TABLE `textitems` (
                `TiID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `TiLgID` int(11) unsigned NOT NULL,
                `TiTxID` int(11) unsigned NOT NULL,
                `TiSeID` int(11) unsigned NOT NULL,
                `TiOrder` int(11) unsigned NOT NULL,
                `TiWordCount` int(1) unsigned NOT NULL,
                `TiText` varchar(250) NOT NULL,
                `TiTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                `TiIsNotWord` tinyint(1) NOT NULL,
                PRIMARY KEY (`TiID`),
                KEY `TiLgID` (`TiLgID`),
                KEY `TiTxID` (`TiTxID`),
                KEY `TiSeID` (`TiSeID`),
                KEY `TiOrder` (`TiOrder`),
                KEY `TiTextLC` (`TiTextLC`),
                KEY `TiIsNotWord` (`TiIsNotWord`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'texts':
                $out .= "CREATE TABLE `texts` (
                `TxID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `TxLgID` int(11) unsigned NOT NULL,
                `TxTitle` varchar(200) NOT NULL,
                `TxText` text NOT NULL,
                `TxAnnotatedText` longtext NOT NULL,
                `TxAudioURI` varchar(200) DEFAULT NULL,
                `TxSourceURI` varchar(1000) DEFAULT NULL,
                PRIMARY KEY (`TxID`),
                KEY `TxLgID` (`TxLgID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'texttags':
                $out .= "CREATE TABLE `texttags` (
                `TtTxID` int(11) unsigned NOT NULL,
                `TtT2ID` int(11) unsigned NOT NULL,
                PRIMARY KEY (`TtTxID`,`TtT2ID`),
                KEY `TtTxID` (`TtTxID`),
                KEY `TtT2ID` (`TtT2ID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'words':
                $out .= "CREATE TABLE `words` (
                `WoID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `WoLgID` int(11) unsigned NOT NULL,
                `WoText` varchar(250) NOT NULL,
                `WoTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                `WoStatus` tinyint(4) NOT NULL,
                `WoTranslation` varchar(500) NOT NULL DEFAULT '*',
                `WoRomanization` varchar(100) DEFAULT NULL,
                `WoSentence` varchar(1000) DEFAULT NULL,
                `WoCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `WoStatusChanged` timestamp NOT NULL DEFAULT '1970-01-01 01:00:01',
                `WoTodayScore` double NOT NULL DEFAULT '0',
                `WoTomorrowScore` double NOT NULL DEFAULT '0',
                `WoRandom` double NOT NULL DEFAULT '0',
                PRIMARY KEY (`WoID`),
                UNIQUE KEY `WoLgIDTextLC` (`WoLgID`,`WoTextLC`),
                KEY `WoLgID` (`WoLgID`),
                KEY `WoStatus` (`WoStatus`),
                KEY `WoTextLC` (`WoTextLC`),
                KEY `WoTranslation` (`WoTranslation`(333)),
                KEY `WoCreated` (`WoCreated`),
                KEY `WoStatusChanged` (`WoStatusChanged`),
                KEY `WoTodayScore` (`WoTodayScore`),
                KEY `WoTomorrowScore` (`WoTomorrowScore`),
                KEY `WoRandom` (`WoRandom`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
            case 'wordtags':
                $out .= "CREATE TABLE `wordtags` (
                `WtWoID` int(11) unsigned NOT NULL,
                `WtTgID` int(11) unsigned NOT NULL,
                PRIMARY KEY (`WtWoID`,`WtTgID`),
                KEY `WtTgID` (`WtTgID`),
                KEY `WtWoID` (`WtWoID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n";
                break;
        }

        if (
            $table !== 'sentences' && $table !== 'textitems' && $table !== 'settings'
        ) {
            while ($row = mysqli_fetch_row($result)) { // foreach record
                $return = 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) { // foreach field
                    $return .= convert_string_to_sqlsyntax_nonull($row[$j]);
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                } // foreach field
                $out .= $return . ");\n";
            } // foreach record
        } // if
    } // foreach table

    header('Content-Type: application/plain');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    echo gzencode($out);
    exit();
} elseif (isset($_REQUEST['empty'])) {
    // EMPTY
    truncateUserDatabase();
    $message = "Database content has been deleted (but settings have been kept)";
}

pagestart('Backup/Restore/Empty Database', true);

echo error_message_with_hide($message, true);

if ($tbpref == '') {
    $prefinfo = "(Default Table Set)";
} else {
    $prefinfo = "(Table Set: <i>" . tohtml(substr($tbpref, 0, -1)) . "</i>)";
}

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

<?php

pageend();

?>
