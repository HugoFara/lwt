<?php

/**
 * \file
 * \brief Database migrations and initialization utilities.
 *
 * PHP version 8.1
 *
 * @category Database
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-database-migrations.html
 * @since    3.0.0
 */

namespace Lwt\Database;

use Lwt\Core\Globals;

/**
 * Database migrations and initialization utilities.
 *
 * Provides methods for updating database schema, running migrations,
 * and initializing the database.
 *
 * @since 3.0.0
 */
class Migrations
{
    /**
     * Add a prefix to table in a SQL query string.
     *
     * @param string $sql_line SQL string to prefix.
     * @param string $prefix   Prefix to add
     *
     * @return string Prefixed SQL query
     */
    public static function prefixQuery(string $sql_line, string $prefix): string
    {
        // Handle INSERT INTO (case-insensitive)
        if (strcasecmp(substr($sql_line, 0, 12), "INSERT INTO ") === 0) {
            return substr($sql_line, 0, 12) . $prefix . substr($sql_line, 12);
        }
        // Handle DROP/CREATE/ALTER TABLE with optional IF [NOT] EXISTS (case-insensitive)
        $res = preg_match(
            '/^(?:DROP|CREATE|ALTER) TABLE (?:IF (?:NOT )?EXISTS )?`?/i',
            $sql_line,
            $matches
        );
        if ($res) {
            return $matches[0] . $prefix .
            substr($sql_line, strlen($matches[0]));
        }
        return $sql_line;
    }

    /**
     * Reparse all texts in order.
     *
     * @return void
     */
    public static function reparseAllTexts(): void
    {
        $tbpref = Globals::getTablePrefix();
        runsql("TRUNCATE {$tbpref}sentences", '');
        runsql("TRUNCATE {$tbpref}textitems2", '');
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        Maintenance::initWordCount();
        $sql = "SELECT TxID, TxLgID FROM {$tbpref}texts";
        $res = do_mysqli_query($sql);
        while ($record = mysqli_fetch_assoc($res)) {
            $id = (int) $record['TxID'];
            TextParsing::splitCheck(
                (string)get_first_value(
                    "SELECT TxText AS value
                    FROM {$tbpref}texts
                    WHERE TxID = $id"
                ),
                (string)$record['TxLgID'],
                $id
            );
        }
        mysqli_free_result($res);
    }

    /**
     * Update the database if it is using an outdate version.
     *
     * @return void
     */
    public static function update(): void
    {
        $tbpref = Globals::getTablePrefix();
        $dbname = Globals::getDatabaseName();

        // DB Version
        $currversion = get_version_number();
        $connection = Globals::getDbConnection();

        $res = mysqli_query(
            $connection,
            "SELECT StValue AS value
            FROM {$tbpref}settings
            WHERE StKey = 'dbversion'"
        );
        if (mysqli_errno($connection) != 0) {
            my_die(
                'There is something wrong with your database ' . $dbname .
                '. Please reinstall.'
            );
        }
        $record = mysqli_fetch_assoc($res);
        if ($record) {
            $dbversion = $record["value"];
        } else {
            $dbversion = 'v001000000';
        }
        mysqli_free_result($res);

        // Do DB Updates if tables seem to be old versions

        if ($dbversion < $currversion) {
            if (Globals::isDebug()) {
                echo "<p>DEBUG: check DB collation: ";
            }
            if (
                'utf8utf8_general_ci' != get_first_value(
                    'SELECT concat(default_character_set_name, default_collation_name) AS value
                FROM information_schema.SCHEMATA
                WHERE schema_name = "' . $dbname . '"'
                )
            ) {
                runsql("SET collation_connection = 'utf8_general_ci'", '');
                runsql(
                    'ALTER DATABASE `' . $dbname .
                    '` CHARACTER SET utf8 COLLATE utf8_general_ci',
                    ''
                );
                if (Globals::isDebug()) {
                    echo 'changed to utf8_general_ci</p>';
                }
            } elseif (Globals::isDebug()) {
                echo 'OK</p>';
            }

            if (Globals::isDebug()) {
                echo "<p>DEBUG: do DB updates: $dbversion --&gt; $currversion</p>";
            }

            $res = do_mysqli_query("SELECT filename FROM _migrations");
            while ($record = mysqli_fetch_assoc($res)) {
                $queries = parseSQLFile(
                    __DIR__ . '/../../../../db/migrations/' . $record["filename"]
                );
                foreach ($queries as $sql_query) {
                    (int) runsql($sql_query, '', false);
                }
            }

            if (Globals::isDebug()) {
                echo '<p>DEBUG: rebuilding tts</p>';
            }
            runsql(
                "CREATE TABLE IF NOT EXISTS tts (
                    TtsID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                    TtsTxt varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                    TtsLc varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                    PRIMARY KEY (TtsID),
                    UNIQUE KEY TtsTxtLC (TtsTxt,TtsLc)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1",
                ''
            );

            // Set database to current version
            Settings::save('dbversion', $currversion);
            Settings::save('lastscorecalc', '');  // do next section, too
        }
    }

    /**
     * Check and/or update the database.
     *
     * @return void
     */
    public static function checkAndUpdate(): void
    {
        $tbpref = Globals::getTablePrefix();
        $tables = array();

        $res = do_mysqli_query(
            str_replace(
                '_',
                "\\_",
                "SHOW TABLES LIKE " . Escaping::toSqlSyntaxNoNull($tbpref . '%')
            )
        );
        while ($row = mysqli_fetch_row($res)) {
            $tables[] = $row[0];
        }
        mysqli_free_result($res);

        /// counter for cache rebuild
        $count = 0;

        // Rebuild in missing table
        $queries = parseSQLFile(__DIR__ . "/../../../../db/schema/baseline.sql");
        foreach ($queries as $query) {
            if (str_contains($query, "_migrations")) {
                // Do not prefix meta tables
                runsql($query, "");
            } else {
                $prefixed_query = self::prefixQuery($query, $tbpref);
                // Increment count for new tables only
                $count += (int) runsql($prefixed_query, "");
            }
        }

        // Update the database (if necessary)
        self::update();

        if (!in_array("{$tbpref}textitems2", $tables)) {
            // Add data from the old database system
            if (in_array("{$tbpref}textitems", $tables)) {
                runsql(
                    "INSERT INTO {$tbpref}textitems2 (
                        Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount,
                        Ti2Text
                    )
                    SELECT IFNULL(WoID,0), TiLgID, TiTxID, TiSeID, TiOrder,
                    CASE WHEN TiIsNotWord = 1 THEN 0 ELSE TiWordCount END as WordCount,
                    CASE
                        WHEN STRCMP(TiText COLLATE utf8_bin,TiTextLC)!=0 OR TiWordCount=1
                        THEN TiText
                        ELSE ''
                    END AS Text
                    FROM {$tbpref}textitems
                    LEFT JOIN {$tbpref}words ON TiTextLC=WoTextLC AND TiLgID=WoLgID
                    WHERE TiWordCount<2 OR WoID IS NOT NULL",
                    ''
                );
                runsql("TRUNCATE {$tbpref}textitems", '');
            }
            $count++;
        }

        if ($count > 0) {
            // Rebuild Text Cache if cache tables new
            if (Globals::isDebug()) {
                echo '<p>DEBUG: rebuilding cache tables</p>';
            }
            self::reparseAllTexts();
        }


        // Do Scoring once per day, clean Word/Texttags, and optimize db
        $lastscorecalc = Settings::get('lastscorecalc');
        $today = date('Y-m-d');
        if ($lastscorecalc != $today) {
            if (Globals::isDebug()) {
                echo '<p>DEBUG: Doing score recalc. Today: ' . $today .
                ' / Last: ' . $lastscorecalc . '</p>';
            }
            runsql(
                "UPDATE {$tbpref}words
                SET " . make_score_random_insert_update('u') . "
                WHERE WoTodayScore>=-100 AND WoStatus<98",
                ''
            );
            runsql(
                "DELETE {$tbpref}wordtags
                FROM ({$tbpref}wordtags LEFT JOIN {$tbpref}tags on WtTgID = TgID)
                WHERE TgID IS NULL",
                ''
            );
            runsql(
                "DELETE {$tbpref}wordtags
                FROM ({$tbpref}wordtags LEFT JOIN {$tbpref}words ON WtWoID = WoID)
                WHERE WoID IS NULL",
                ''
            );
            runsql(
                "DELETE {$tbpref}texttags
                FROM ({$tbpref}texttags LEFT JOIN {$tbpref}tags2 ON TtT2ID = T2ID)
                WHERE T2ID IS NULL",
                ''
            );
            runsql(
                "DELETE {$tbpref}texttags
                FROM ({$tbpref}texttags LEFT JOIN {$tbpref}texts ON TtTxID = TxID)
                WHERE TxID IS NULL",
                ''
            );
            runsql(
                "DELETE {$tbpref}archtexttags
                FROM (
                    {$tbpref}archtexttags
                    LEFT JOIN {$tbpref}tags2 ON AgT2ID = T2ID
                )
                WHERE T2ID IS NULL",
                ''
            );
            runsql(
                "DELETE {$tbpref}archtexttags
                FROM (
                    {$tbpref}archtexttags
                    LEFT JOIN {$tbpref}archivedtexts ON AgAtID = AtID
                )
                WHERE AtID IS NULL",
                ''
            );
            Maintenance::optimizeDatabase();
            Settings::save('lastscorecalc', $today);
        }
    }
}
