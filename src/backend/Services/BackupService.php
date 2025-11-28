<?php

/**
 * Backup Service - Business logic for backup/restore operations
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Services;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\Escaping;

/**
 * Service class for database backup and restore operations.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class BackupService
{
    /**
     * Database table prefix.
     *
     * @var string
     */
    private string $tbpref;

    /**
     * Database name.
     *
     * @var string
     */
    private string $dbname;

    /**
     * Tables to include in backup.
     *
     * @var string[]
     */
    private const BACKUP_TABLES = [
        'archivedtexts', 'archtexttags', 'feedlinks', 'languages', 'textitems2',
        'newsfeeds', 'sentences', 'settings', 'tags', 'tags2', 'texts', 'texttags',
        'words', 'wordtags'
    ];

    /**
     * Tables for official LWT backup format.
     *
     * @var string[]
     */
    private const OFFICIAL_BACKUP_TABLES = [
        'archivedtexts', 'archtexttags', 'languages', 'sentences', 'settings',
        'tags', 'tags2', 'textitems', 'texts', 'texttags', 'words', 'wordtags'
    ];

    /**
     * Constructor - initialize database settings.
     */
    public function __construct()
    {
        $this->tbpref = Globals::getTablePrefix();
        $this->dbname = Globals::getDatabaseName();
    }

    /**
     * Get prefix string for filenames.
     *
     * @return string Prefix for backup filename
     */
    public function getFilePrefix(): string
    {
        if ($this->tbpref == '') {
            return "";
        }
        return substr($this->tbpref, 0, -1) . "-";
    }

    /**
     * Get prefix info for display.
     *
     * @return string HTML-safe prefix info
     */
    public function getPrefixInfo(): string
    {
        if ($this->tbpref == '') {
            return "(Default Table Set)";
        }
        return "(Table Set: <i>" . tohtml(substr($this->tbpref, 0, -1)) . "</i>)";
    }

    /**
     * Get database name.
     *
     * @return string Database name
     */
    public function getDatabaseName(): string
    {
        return $this->dbname;
    }

    /**
     * Restore database from uploaded file.
     *
     * @param array $fileData $_FILES data for uploaded file
     *
     * @return string Status message
     */
    public function restoreFromUpload(array $fileData): string
    {
        if (
            !isset($fileData["thefile"]) ||
            $fileData["thefile"]["tmp_name"] == "" ||
            $fileData["thefile"]["error"] != 0
        ) {
            return "Error: No Restore file specified";
        }

        $handle = @gzopen($fileData["thefile"]["tmp_name"], "r");
        if ($handle === false) {
            return "Error: Restore file could not be opened";
        }

        return restore_file($handle, "Database");
    }

    /**
     * Generate and output LWT backup file.
     *
     * @return void Outputs file and exits
     */
    public function downloadBackup(): void
    {
        $pref = $this->getFilePrefix();
        $fname = "lwt-backup-exp_version-" . $pref . date('Y-m-d-H-i-s') . ".sql.gz";
        $out = "-- " . $fname . "\n";

        foreach (self::BACKUP_TABLES as $table) {
            $result = Connection::query('SELECT * FROM ' . $this->tbpref . $table);
            $num_fields = mysqli_num_fields($result);
            $out .= "\nDROP TABLE IF EXISTS " . $table . ";\n";
            $row2 = mysqli_fetch_row(
                Connection::query("SHOW CREATE TABLE {$this->tbpref}$table")
            );
            $out .= str_replace(
                $this->tbpref . $table,
                $table,
                str_replace("\n", " ", $row2[1])
            ) . ";\n";

            if ($table !== 'sentences' && $table !== 'textitems2') {
                while ($row = mysqli_fetch_row($result)) {
                    $return = 'INSERT INTO ' . $table . ' VALUES(';
                    for ($j = 0; $j < $num_fields; $j++) {
                        $return .= Escaping::toSqlSyntaxNoNull($row[$j]);
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $out .= $return . ");\n";
                }
            }
        }

        header('Content-Type: application/plain');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        echo gzencode($out);
        exit();
    }

    /**
     * Generate and output official LWT backup file.
     *
     * @return void Outputs file and exits
     */
    public function downloadOfficialBackup(): void
    {
        $pref = $this->getFilePrefix();
        $fname = "lwt-backup-" . $pref . date('Y-m-d-H-i-s') . ".sql.gz";
        $out = "-- " . $fname . "\n";

        foreach (self::OFFICIAL_BACKUP_TABLES as $table) {
            $result = null;
            $num_fields = 0;

            if ($table == 'texts') {
                $result = Connection::query(
                    'SELECT TxID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI,
                    TxSourceURI FROM ' . $this->tbpref . $table
                );
                $num_fields = 7;
            } elseif ($table == 'words') {
                $result = Connection::query(
                    'SELECT WoID, WoLgID, WoText, WoTextLC, WoStatus, WoTranslation,
                    WoRomanization, WoSentence, WoCreated, WoStatusChanged, WoTodayScore,
                    WoTomorrowScore, WoRandom FROM ' . $this->tbpref . $table
                );
                $num_fields = 13;
            } elseif ($table == 'languages') {
                $result = Connection::query(
                    'SELECT LgID, LgName, LgDict1URI, LgDict2URI,
                    REPLACE(
                        LgGoogleTranslateURI, "ggl.php", "http://translate.google.com"
                    ) AS LgGoogleTranslateURI,
                    LgExportTemplate, LgTextSize, LgCharacterSubstitutions,
                    LgRegexpSplitSentences, LgExceptionsSplitSentences,
                    LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar,
                    LgRightToLeft FROM ' . $this->tbpref . 'languages WHERE LgName<>""'
                );
                $num_fields = mysqli_num_fields($result);
            } elseif (
                $table !== 'sentences' && $table !== 'textitems' &&
                $table !== 'settings'
            ) {
                $result = Connection::query('SELECT * FROM ' . $this->tbpref . $table);
                $num_fields = mysqli_num_fields($result);
            }

            $out .= "\nDROP TABLE IF EXISTS " . $table . ";\n";
            $out .= $this->getOfficialTableSchema($table);

            if (
                $table !== 'sentences' && $table !== 'textitems' &&
                $table !== 'settings' && $result !== null
            ) {
                while ($row = mysqli_fetch_row($result)) {
                    $return = 'INSERT INTO ' . $table . ' VALUES(';
                    for ($j = 0; $j < $num_fields; $j++) {
                        $return .= Escaping::toSqlSyntaxNoNull($row[$j]);
                        if ($j < ($num_fields - 1)) {
                            $return .= ',';
                        }
                    }
                    $out .= $return . ");\n";
                }
            }
        }

        header('Content-Type: application/plain');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        echo gzencode($out);
        exit();
    }

    /**
     * Empty the database (truncate all tables except settings).
     *
     * @return string Status message
     */
    public function emptyDatabase(): string
    {
        truncateUserDatabase();
        return "Database content has been deleted (but settings have been kept)";
    }

    /**
     * Get the official table schema for a given table.
     *
     * @param string $table Table name
     *
     * @return string SQL CREATE TABLE statement
     */
    private function getOfficialTableSchema(string $table): string
    {
        $schemas = [
            'archivedtexts' => "CREATE TABLE `archivedtexts` (
                `AtID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `AtLgID` int(11) unsigned NOT NULL,
                `AtTitle` varchar(200) NOT NULL,
                `AtText` text NOT NULL,
                `AtAnnotatedText` longtext NOT NULL,
                `AtAudioURI` varchar(200) DEFAULT NULL,
                `AtSourceURI` varchar(1000) DEFAULT NULL,
                PRIMARY KEY (`AtID`),
                KEY `AtLgID` (`AtLgID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'archtexttags' => "CREATE TABLE `archtexttags` (
                `AgAtID` int(11) unsigned NOT NULL,
                `AgT2ID` int(11) unsigned NOT NULL,
                PRIMARY KEY (`AgAtID`,`AgT2ID`),
                KEY `AgAtID` (`AgAtID`),
                KEY `AgT2ID` (`AgT2ID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'languages' => "CREATE TABLE `languages` (
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'sentences' => "CREATE TABLE `sentences` (
                `SeID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `SeLgID` int(11) unsigned NOT NULL,
                `SeTxID` int(11) unsigned NOT NULL,
                `SeOrder` int(11) unsigned NOT NULL,
                `SeText` text,
                PRIMARY KEY (`SeID`),
                KEY `SeLgID` (`SeLgID`),
                KEY `SeTxID` (`SeTxID`),
                KEY `SeOrder` (`SeOrder`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'settings' => "CREATE TABLE `settings` (
                `StKey` varchar(40) NOT NULL,
                `StValue` varchar(40) DEFAULT NULL,
                PRIMARY KEY (`StKey`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'tags' => "CREATE TABLE `tags` (
                `TgID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `TgText` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                `TgComment` varchar(200) NOT NULL DEFAULT '',
                PRIMARY KEY (`TgID`),
                UNIQUE KEY `TgText` (`TgText`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'tags2' => "CREATE TABLE `tags2` (
                `T2ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `T2Text` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                `T2Comment` varchar(200) NOT NULL DEFAULT '',
                PRIMARY KEY (`T2ID`),
                UNIQUE KEY `T2Text` (`T2Text`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'textitems' => "CREATE TABLE `textitems` (
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'texts' => "CREATE TABLE `texts` (
                `TxID` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `TxLgID` int(11) unsigned NOT NULL,
                `TxTitle` varchar(200) NOT NULL,
                `TxText` text NOT NULL,
                `TxAnnotatedText` longtext NOT NULL,
                `TxAudioURI` varchar(200) DEFAULT NULL,
                `TxSourceURI` varchar(1000) DEFAULT NULL,
                PRIMARY KEY (`TxID`),
                KEY `TxLgID` (`TxLgID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'texttags' => "CREATE TABLE `texttags` (
                `TtTxID` int(11) unsigned NOT NULL,
                `TtT2ID` int(11) unsigned NOT NULL,
                PRIMARY KEY (`TtTxID`,`TtT2ID`),
                KEY `TtTxID` (`TtTxID`),
                KEY `TtT2ID` (`TtT2ID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'words' => "CREATE TABLE `words` (
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
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
            'wordtags' => "CREATE TABLE `wordtags` (
                `WtWoID` int(11) unsigned NOT NULL,
                `WtTgID` int(11) unsigned NOT NULL,
                PRIMARY KEY (`WtWoID`,`WtTgID`),
                KEY `WtTgID` (`WtTgID`),
                KEY `WtWoID` (`WtWoID`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;\n",
        ];

        return $schemas[$table] ?? "";
    }
}
