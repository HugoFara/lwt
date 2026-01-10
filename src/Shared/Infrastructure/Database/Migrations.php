<?php declare(strict_types=1);
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

namespace Lwt\Shared\Infrastructure\Database;

use Lwt\Core\ApplicationInfo;
use Lwt\Core\Globals;
use Lwt\Core\Utils\ErrorHandler;
use Lwt\Modules\Vocabulary\Application\Services\TermStatusService;

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
        // Use DELETE instead of TRUNCATE to respect foreign key constraints
        // Delete word_occurrences first (child), then sentences (parent)
        // Use raw DELETE FROM to delete all records
        Connection::execute("DELETE FROM word_occurrences");
        Connection::execute("DELETE FROM sentences");
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        Maintenance::initWordCount();
        // Only reparse texts that have a valid language reference
        $rows = QueryBuilder::table('texts')
            ->select(['texts.TxID', 'texts.TxLgID'])
            ->join('languages', 'texts.TxLgID', '=', 'languages.LgID')
            ->getPrepared();
        foreach ($rows as $record) {
            $id = (int) $record['TxID'];
            $textValue = QueryBuilder::table('texts')
                ->where('TxID', '=', $id)
                ->valuePrepared('TxText');
            TextParsing::parseAndSave(
                (string)$textValue,
                (int)$record['TxLgID'],
                $id
            );
        }
    }

    /**
     * Get list of all migration files from the migrations directory.
     *
     * @return array<string> Sorted list of migration filenames
     */
    public static function getMigrationFiles(): array
    {
        $migrationsDir = __DIR__ . '/../../../../db/migrations/';
        $files = glob($migrationsDir . '*.sql');
        if ($files === false) {
            return [];
        }
        // Extract just the filenames and sort them
        $filenames = array_map('basename', $files);
        sort($filenames);
        return $filenames;
    }

    /**
     * Get list of migrations that have already been applied.
     *
     * @return array<string> List of applied migration filenames
     */
    public static function getAppliedMigrations(): array
    {
        try {
            $rows = Connection::fetchAll("SELECT filename FROM _migrations");
            return array_column($rows, 'filename');
        } catch (\RuntimeException $e) {
            // Table doesn't exist yet
            return [];
        }
    }

    /**
     * Record a migration as applied with its checksum.
     *
     * @param string $filename The migration filename
     * @param string $checksum SHA-256 hash of the migration file
     *
     * @return void
     */
    public static function recordMigration(string $filename, string $checksum = ''): void
    {
        Connection::preparedExecute(
            "INSERT IGNORE INTO _migrations (filename, applied_at, checksum) VALUES (?, NOW(), ?)",
            [$filename, $checksum]
        );
    }

    /**
     * Calculate SHA-256 checksum for a migration file.
     *
     * @param string $filepath Full path to the migration file
     *
     * @return string SHA-256 hash or empty string if file not readable
     */
    public static function calculateChecksum(string $filepath): string
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return '';
        }
        $hash = hash_file('sha256', $filepath);
        return $hash !== false ? $hash : '';
    }

    /**
     * Validate that applied migrations haven't been modified.
     *
     * Checks the checksum of each applied migration against its stored value.
     * This detects tampering or accidental modification of migration files.
     *
     * @return array{valid: bool, errors: array<string>} Validation result
     */
    public static function validateMigrationIntegrity(): array
    {
        $errors = [];
        $migrationsDir = __DIR__ . '/../../../../db/migrations/';

        try {
            $rows = Connection::fetchAll(
                "SELECT filename, checksum FROM _migrations WHERE checksum IS NOT NULL AND checksum != ''"
            );
        } catch (\RuntimeException $e) {
            // Table doesn't exist or no checksum column yet
            return ['valid' => true, 'errors' => []];
        }

        foreach ($rows as $row) {
            $filename = $row['filename'];
            $storedChecksum = $row['checksum'];
            $filepath = $migrationsDir . $filename;

            if (!file_exists($filepath)) {
                $errors[] = "Migration file missing: $filename";
                continue;
            }

            $currentChecksum = self::calculateChecksum($filepath);
            if ($currentChecksum !== $storedChecksum) {
                $errors[] = "Migration file integrity check failed: $filename (file was modified after being applied)";
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }

    /**
     * Upgrade the _migrations table from old schema to new schema.
     *
     * Old schema stored migrations to be run; new schema tracks applied migrations.
     * This method adds the applied_at and checksum columns.
     *
     * @return void
     */
    public static function upgradeMigrationsTable(): void
    {
        // Check which columns exist
        $dbname = Globals::getDatabaseName();
        $columns = Connection::preparedFetchAll(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = '_migrations'",
            [$dbname]
        );
        $columnNames = array_column($columns, 'COLUMN_NAME');

        if (!in_array('applied_at', $columnNames)) {
            // Add applied_at column
            Connection::execute(
                "ALTER TABLE _migrations ADD COLUMN applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
            );
        }

        if (!in_array('checksum', $columnNames)) {
            // Add checksum column for integrity validation
            Connection::execute(
                "ALTER TABLE _migrations ADD COLUMN checksum VARCHAR(64) DEFAULT NULL"
            );
        }
    }

    /**
     * Update the database if it is using an outdate version.
     *
     * @return void
     */
    public static function update(): void
    {
        $dbname = Globals::getDatabaseName();

        // DB Version
        $currversion = ApplicationInfo::getVersionNumber();

        try {
            $dbversion = QueryBuilder::table('settings')
                ->where('StKey', '=', 'dbversion')
                ->valuePrepared('StValue');
            if ($dbversion === null) {
                $dbversion = 'v001000000';
            }
        } catch (\RuntimeException $e) {
            ErrorHandler::die(
                'There is something wrong with your database ' . $dbname .
                '. Please reinstall.'
            );
        }

        // Do DB Updates if tables seem to be old versions

        if ($dbversion < $currversion) {
            if (
                'utf8utf8_general_ci' != Connection::preparedFetchValue(
                    'SELECT concat(default_character_set_name, default_collation_name) AS collation
                FROM information_schema.SCHEMATA
                WHERE schema_name = ?',
                    [$dbname],
                    'collation'
                )
            ) {
                Connection::query("SET collation_connection = 'utf8_general_ci'");
                // Note: ALTER DATABASE doesn't support prepared statements
                // Database name comes from trusted config, using backtick escaping
                $escapedDbName = '`' . str_replace('`', '``', $dbname) . '`';
                Connection::execute(
                    'ALTER DATABASE ' . $escapedDbName .
                    ' CHARACTER SET utf8 COLLATE utf8_general_ci'
                );
            }

            // Validate integrity of already-applied migrations
            $integrityCheck = self::validateMigrationIntegrity();
            if (!$integrityCheck['valid']) {
                // Log errors but don't block - allow admin to investigate
                foreach ($integrityCheck['errors'] as $error) {
                    error_log('Migration integrity warning: ' . $error);
                }
            }

            // Get pending migrations (not yet applied)
            $allMigrations = self::getMigrationFiles();
            $appliedMigrations = self::getAppliedMigrations();
            $pendingMigrations = array_diff($allMigrations, $appliedMigrations);
            $migrationsDir = __DIR__ . '/../../../../db/migrations/';

            foreach ($pendingMigrations as $filename) {
                $filepath = $migrationsDir . $filename;
                $queries = SqlFileParser::parseFile($filepath);
                foreach ($queries as $sql_query) {
                    Connection::execute($sql_query);
                }
                // Record migration as applied with checksum
                $checksum = self::calculateChecksum($filepath);
                self::recordMigration($filename, $checksum);
            }

            Connection::execute(
                "CREATE TABLE IF NOT EXISTS tts (
                    TtsID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
                    TtsTxt varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                    TtsLc varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
                    PRIMARY KEY (TtsID),
                    UNIQUE KEY TtsTxtLC (TtsTxt,TtsLc)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1"
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
        $tables = array();

        // Get database name for INFORMATION_SCHEMA query
        $dbname = Globals::getDatabaseName();

        // Get all core LWT tables (no prefix in multi-user system)
        $res = Connection::preparedFetchAll(
            "SELECT TABLE_NAME
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME IN (
                'languages', 'texts', 'archivedtexts', 'words', 'sentences',
                'word_occurrences', 'word_occurrences', 'textitems', 'tags', 'text_tags', 'text_tags', 'wordtags',
                'text_tag_map', 'archived_text_tag_map', 'newsfeeds', 'feedlinks',
                'settings', '_migrations', 'tts'
             )",
            [$dbname]
        );
        foreach ($res as $row) {
            $tables[] = $row['TABLE_NAME'];
        }

        /// counter for cache rebuild
        $count = 0;

        // Rebuild in missing table
        $queries = SqlFileParser::parseFile(__DIR__ . "/../../../../db/schema/baseline.sql");
        foreach ($queries as $query) {
            // Execute schema queries directly - no prefix in multi-user system
            $count += (int) Connection::execute($query);
        }

        // Ensure _migrations table has the new schema with applied_at column
        self::upgradeMigrationsTable();

        // Update the database (if necessary)
        self::update();

        if (!in_array("word_occurrences", $tables) && !in_array("word_occurrences", $tables)) {
            // Add data from the old database system
            if (in_array("textitems", $tables)) {
                // Complex migration query - use raw SQL
                Connection::execute(
                    "INSERT INTO word_occurrences (
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
                    FROM textitems
                    LEFT JOIN words ON TiTextLC=WoTextLC AND TiLgID=WoLgID
                    WHERE TiWordCount<2 OR WoID IS NOT NULL"
                );
                QueryBuilder::table('textitems')->truncate();
            }
            $count++;
        }

        if ($count > 0) {
            // Rebuild Text Cache if cache tables new
            self::reparseAllTexts();
        }


        // Do Scoring once per day, clean Word/Texttags, and optimize db
        $lastscorecalc = Settings::get('lastscorecalc');
        $today = date('Y-m-d');
        if ($lastscorecalc != $today) {
            // Update word scores - complex SQL expression, use raw query
            Connection::execute(
                "UPDATE words
                SET " . TermStatusService::makeScoreRandomInsertUpdate('u') . "
                WHERE WoTodayScore>=-100 AND WoStatus<98"
            );
            // Clean up orphaned wordtags (tags deleted)
            Connection::execute(
                "DELETE wordtags
                FROM (wordtags LEFT JOIN tags on WtTgID = TgID)
                WHERE TgID IS NULL"
            );
            // Clean up orphaned wordtags (words deleted)
            Connection::execute(
                "DELETE wordtags
                FROM (wordtags LEFT JOIN words ON WtWoID = WoID)
                WHERE WoID IS NULL"
            );
            // Clean up orphaned text_tag_map (text_tags deleted)
            Connection::execute(
                "DELETE text_tag_map
                FROM (text_tag_map LEFT JOIN text_tags ON TtT2ID = T2ID)
                WHERE T2ID IS NULL"
            );
            // Clean up orphaned text_tag_map (texts deleted)
            Connection::execute(
                "DELETE text_tag_map
                FROM (text_tag_map LEFT JOIN texts ON TtTxID = TxID)
                WHERE TxID IS NULL"
            );
            // Clean up orphaned archived_text_tag_map (text_tags deleted)
            Connection::execute(
                "DELETE archived_text_tag_map
                FROM (
                    archived_text_tag_map
                    LEFT JOIN text_tags ON AgT2ID = T2ID
                )
                WHERE T2ID IS NULL"
            );
            // Clean up orphaned archived_text_tag_map (archivedtexts deleted)
            Connection::execute(
                "DELETE archived_text_tag_map
                FROM (
                    archived_text_tag_map
                    LEFT JOIN archivedtexts ON AgAtID = AtID
                )
                WHERE AtID IS NULL"
            );
            Maintenance::optimizeDatabase();
            Settings::save('lastscorecalc', $today);
        }
    }
}
