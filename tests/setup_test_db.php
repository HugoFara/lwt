<?php

/**
 * Set up test database for integration tests.
 *
 * This script creates a test database, applies the baseline schema,
 * and runs all migrations. Run this before running integration tests.
 *
 * Usage:
 *   php tests/setup_test_db.php           # Setup test database
 *   php tests/setup_test_db.php --drop    # Drop and recreate test database
 *   php tests/setup_test_db.php --status  # Show test database status
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Tests;

use Lwt\Core\Bootstrap\EnvLoader;

// Load environment configuration
require_once __DIR__ . '/../src/backend/Core/Bootstrap/EnvLoader.php';

// Parse command line arguments
$drop = in_array('--drop', $argv ?? []);
$statusOnly = in_array('--status', $argv ?? []);
$quiet = in_array('--quiet', $argv ?? []) || in_array('-q', $argv ?? []);

// Load .env configuration
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    fwrite(STDERR, "Error: .env file not found at $envFile\n");
    fwrite(STDERR, "Copy .env.example to .env and configure your database credentials.\n");
    exit(1);
}

EnvLoader::load($envFile);
$config = EnvLoader::getDatabaseConfig();

$testDbName = 'test_' . $config['dbname'];

// Connect without database to create it
$conn = @mysqli_connect(
    $config['server'],
    $config['userid'],
    $config['passwd'],
    '',
    socket: $config['socket'] ?? ''
);

if (!$conn) {
    fwrite(STDERR, "Error: Could not connect to MySQL server.\n");
    fwrite(STDERR, "Check your .env database credentials.\n");
    fwrite(STDERR, "MySQL error: " . mysqli_connect_error() . "\n");
    exit(1);
}

// Disable mysqli exception mode to allow graceful error handling
mysqli_report(MYSQLI_REPORT_OFF);

/**
 * Output a message unless quiet mode is enabled.
 */
function output(string $message, bool $quiet): void
{
    if (!$quiet) {
        echo $message;
    }
}

/**
 * Get the count of tables in the test database.
 */
function getTableCount(\mysqli $conn, string $dbName): int
{
    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$dbName'"
    );
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return (int) ($row['cnt'] ?? 0);
    }
    return 0;
}

/**
 * Get the count of applied migrations.
 */
function getMigrationCount(\mysqli $conn, string $dbName): int
{
    $result = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `$dbName`._migrations");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return (int) ($row['cnt'] ?? 0);
    }
    return 0;
}

/**
 * Check if database exists.
 */
function databaseExists(\mysqli $conn, string $dbName): bool
{
    $result = mysqli_query(
        $conn,
        "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'"
    );
    if ($result) {
        $exists = mysqli_num_rows($result) > 0;
        mysqli_free_result($result);
        return $exists;
    }
    return false;
}

/**
 * Check if foreign keys are present.
 */
function hasForeignKeys(\mysqli $conn, string $dbName): bool
{
    $result = mysqli_query(
        $conn,
        "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = '$dbName'
         AND CONSTRAINT_TYPE = 'FOREIGN KEY'
         AND TABLE_NAME = 'word_occurrences'"
    );
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return ((int) ($row['cnt'] ?? 0)) > 0;
    }
    return false;
}

// Status only mode
if ($statusOnly) {
    echo "Test Database Status\n";
    echo "====================\n";
    echo "Database name: $testDbName\n";

    if (!databaseExists($conn, $testDbName)) {
        echo "Status: NOT EXISTS\n";
        echo "\nRun 'composer test:setup-db' to create the test database.\n";
        mysqli_close($conn);
        exit(0);
    }

    mysqli_select_db($conn, $testDbName);
    $tableCount = getTableCount($conn, $testDbName);
    $migrationCount = getMigrationCount($conn, $testDbName);
    $hasFk = hasForeignKeys($conn, $testDbName);

    echo "Status: EXISTS\n";
    echo "Tables: $tableCount\n";
    echo "Applied migrations: $migrationCount\n";
    echo "Foreign keys: " . ($hasFk ? "YES" : "NO") . "\n";

    // Count available migrations
    $migrationsDir = __DIR__ . '/../db/migrations/';
    $migrationFiles = glob($migrationsDir . '*.sql');
    $totalMigrations = $migrationFiles ? count($migrationFiles) : 0;

    if ($migrationCount < $totalMigrations) {
        echo "\nWarning: $migrationCount of $totalMigrations migrations applied.\n";
        echo "Run 'composer test:setup-db' to apply pending migrations.\n";
    }

    mysqli_close($conn);
    exit(0);
}

// Drop database if requested
if ($drop) {
    output("Dropping test database '$testDbName'...\n", $quiet);
    if (!mysqli_query($conn, "DROP DATABASE IF EXISTS `$testDbName`")) {
        fwrite(STDERR, "Error dropping database: " . mysqli_error($conn) . "\n");
        mysqli_close($conn);
        exit(1);
    }
    output("Database dropped.\n", $quiet);
}

// Create database if it doesn't exist
output("Creating test database '$testDbName'...\n", $quiet);
if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$testDbName` CHARACTER SET utf8 COLLATE utf8_general_ci")) {
    fwrite(STDERR, "Error creating database: " . mysqli_error($conn) . "\n");
    mysqli_close($conn);
    exit(1);
}

// Select the test database
if (!mysqli_select_db($conn, $testDbName)) {
    fwrite(STDERR, "Error selecting database: " . mysqli_error($conn) . "\n");
    mysqli_close($conn);
    exit(1);
}

output("Database created.\n", $quiet);

// Apply baseline schema
output("Applying baseline schema...\n", $quiet);
$baselineFile = __DIR__ . '/../db/schema/baseline.sql';
if (!file_exists($baselineFile)) {
    fwrite(STDERR, "Error: Baseline schema file not found at $baselineFile\n");
    mysqli_close($conn);
    exit(1);
}

$baselineSql = file_get_contents($baselineFile);
if ($baselineSql === false) {
    fwrite(STDERR, "Error reading baseline schema file.\n");
    mysqli_close($conn);
    exit(1);
}

// Execute baseline schema (multi-statement)
mysqli_multi_query($conn, $baselineSql);
do {
    if ($result = mysqli_store_result($conn)) {
        mysqli_free_result($result);
    }
} while (mysqli_next_result($conn));

if (mysqli_errno($conn)) {
    fwrite(STDERR, "Error applying baseline schema: " . mysqli_error($conn) . "\n");
    mysqli_close($conn);
    exit(1);
}

output("Baseline schema applied.\n", $quiet);

// Get list of migration files
$migrationsDir = __DIR__ . '/../db/migrations/';
$migrationFiles = glob($migrationsDir . '*.sql');
if ($migrationFiles === false) {
    $migrationFiles = [];
}
sort($migrationFiles);

// The baseline schema already includes all table structures from migrations.
// We need to:
// 1. Mark most migrations as "applied" (since baseline incorporates their changes)
// 2. Actually run specific migrations that need explicit execution:
//    - FK migration (adds inter-table foreign keys)
//    - Column defaults migration (mysqli_multi_query doesn't handle DEFAULT '' correctly)
$fkMigration = '20251221_120000_add_inter_table_foreign_keys.sql';
$columnDefaultsMigration = '20260107_120000_add_language_column_defaults.sql';

output("Marking migrations as applied (baseline includes these changes)...\n", $quiet);
foreach ($migrationFiles as $migrationFile) {
    $filename = basename($migrationFile);

    // Skip migrations that need to be run explicitly
    if ($filename === $fkMigration || $filename === $columnDefaultsMigration) {
        continue;
    }

    $escapedFilename = mysqli_real_escape_string($conn, $filename);
    mysqli_query($conn, "INSERT IGNORE INTO _migrations (filename, applied_at) VALUES ('$escapedFilename', NOW())");
}

// Get applied migrations (to check if FK migration was already applied)
$appliedMigrations = [];
$result = mysqli_query($conn, "SELECT filename FROM _migrations");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $appliedMigrations[] = $row['filename'];
    }
    mysqli_free_result($result);
}

// Apply FK constraints directly (baseline has matching column types)
// The FK migration file modifies column types which breaks fresh installs
// So we apply FK constraints directly here
$appliedCount = 0;
if (!in_array($fkMigration, $appliedMigrations)) {
    output("Applying foreign key constraints...\n", $quiet);

    // FK constraints to add (baseline already has matching column types)
    $fkConstraints = [
        // Language references
        "ALTER TABLE texts ADD CONSTRAINT fk_texts_language FOREIGN KEY (TxLgID) REFERENCES languages(LgID) ON DELETE CASCADE",
        "ALTER TABLE archivedtexts ADD CONSTRAINT fk_archivedtexts_language FOREIGN KEY (AtLgID) REFERENCES languages(LgID) ON DELETE CASCADE",
        "ALTER TABLE words ADD CONSTRAINT fk_words_language FOREIGN KEY (WoLgID) REFERENCES languages(LgID) ON DELETE CASCADE",
        "ALTER TABLE sentences ADD CONSTRAINT fk_sentences_language FOREIGN KEY (SeLgID) REFERENCES languages(LgID) ON DELETE CASCADE",
        "ALTER TABLE newsfeeds ADD CONSTRAINT fk_newsfeeds_language FOREIGN KEY (NfLgID) REFERENCES languages(LgID) ON DELETE CASCADE",
        // Text references
        "ALTER TABLE sentences ADD CONSTRAINT fk_sentences_text FOREIGN KEY (SeTxID) REFERENCES texts(TxID) ON DELETE CASCADE",
        "ALTER TABLE word_occurrences ADD CONSTRAINT fk_word_occurrences_text FOREIGN KEY (Ti2TxID) REFERENCES texts(TxID) ON DELETE CASCADE",
        "ALTER TABLE text_tag_map ADD CONSTRAINT fk_text_tag_map_text FOREIGN KEY (TtTxID) REFERENCES texts(TxID) ON DELETE CASCADE",
        // Sentence reference
        "ALTER TABLE word_occurrences ADD CONSTRAINT fk_word_occurrences_sentence FOREIGN KEY (Ti2SeID) REFERENCES sentences(SeID) ON DELETE CASCADE",
        // Word reference (SET NULL for unknown words)
        "ALTER TABLE word_occurrences MODIFY COLUMN Ti2WoID mediumint(8) unsigned DEFAULT NULL",
        "ALTER TABLE word_occurrences ADD CONSTRAINT fk_word_occurrences_word FOREIGN KEY (Ti2WoID) REFERENCES words(WoID) ON DELETE SET NULL",
        // Word tags
        "ALTER TABLE wordtags ADD CONSTRAINT fk_wordtags_word FOREIGN KEY (WtWoID) REFERENCES words(WoID) ON DELETE CASCADE",
        "ALTER TABLE wordtags ADD CONSTRAINT fk_wordtags_tag FOREIGN KEY (WtTgID) REFERENCES tags(TgID) ON DELETE CASCADE",
        // Text tags
        "ALTER TABLE text_tag_map ADD CONSTRAINT fk_text_tag_map_text_tag FOREIGN KEY (TtT2ID) REFERENCES text_tags(T2ID) ON DELETE CASCADE",
        // Archived text tags
        "ALTER TABLE archived_text_tag_map ADD CONSTRAINT fk_archived_text_tag_map_archivedtext FOREIGN KEY (AgAtID) REFERENCES archivedtexts(AtID) ON DELETE CASCADE",
        "ALTER TABLE archived_text_tag_map ADD CONSTRAINT fk_archived_text_tag_map_text_tag FOREIGN KEY (AgT2ID) REFERENCES text_tags(T2ID) ON DELETE CASCADE",
        // Feed links
        "ALTER TABLE feedlinks ADD CONSTRAINT fk_feedlinks_newsfeed FOREIGN KEY (FlNfID) REFERENCES newsfeeds(NfID) ON DELETE CASCADE",
    ];

    $fkCount = 0;
    $fkErrors = 0;
    foreach ($fkConstraints as $sql) {
        if (@mysqli_query($conn, $sql)) {
            $fkCount++;
        } else {
            $error = mysqli_error($conn);
            // Ignore "duplicate key" errors (constraint already exists)
            if (strpos($error, 'Duplicate') === false && strpos($error, 'already exists') === false) {
                $fkErrors++;
                if (!$quiet) {
                    fwrite(STDERR, "  Warning: " . $error . "\n");
                }
            }
        }
    }

    // Record migration as applied
    $escapedFilename = mysqli_real_escape_string($conn, $fkMigration);
    mysqli_query($conn, "INSERT IGNORE INTO _migrations (filename, applied_at) VALUES ('$escapedFilename', NOW())");

    output("Applied $fkCount FK constraint(s)" . ($fkErrors > 0 ? " ($fkErrors warnings)" : "") . ".\n", $quiet);
    $appliedCount = 1;
} else {
    output("FK constraints already applied.\n", $quiet);
}

// Apply column defaults migration (mysqli_multi_query doesn't handle DEFAULT '' correctly in baseline.sql)
if (!in_array($columnDefaultsMigration, $appliedMigrations)) {
    output("Applying column defaults for strict SQL mode...\n", $quiet);

    // These columns need explicit defaults for STRICT_ALL_TABLES mode
    $columnDefaults = [
        "ALTER TABLE languages MODIFY COLUMN LgCharacterSubstitutions varchar(500) NOT NULL DEFAULT ''",
        "ALTER TABLE languages MODIFY COLUMN LgRegexpSplitSentences varchar(500) NOT NULL DEFAULT '.!?'",
        "ALTER TABLE languages MODIFY COLUMN LgExceptionsSplitSentences varchar(500) NOT NULL DEFAULT ''",
        "ALTER TABLE languages MODIFY COLUMN LgRegexpWordCharacters varchar(500) NOT NULL DEFAULT 'a-zA-ZÀ-ÖØ-öø-ȳ'",
        "ALTER TABLE texts MODIFY COLUMN TxAnnotatedText longtext NOT NULL DEFAULT ''",
        "ALTER TABLE archivedtexts MODIFY COLUMN AtAnnotatedText longtext NOT NULL DEFAULT ''",
        "ALTER TABLE feedlinks MODIFY COLUMN FlAudio varchar(200) NOT NULL DEFAULT ''",
        "ALTER TABLE feedlinks MODIFY COLUMN FlText longtext NOT NULL DEFAULT ''",
    ];

    foreach ($columnDefaults as $sql) {
        @mysqli_query($conn, $sql);
    }

    // Record migration as applied
    $escapedFilename = mysqli_real_escape_string($conn, $columnDefaultsMigration);
    mysqli_query($conn, "INSERT IGNORE INTO _migrations (filename, applied_at) VALUES ('$escapedFilename', NOW())");

    output("Column defaults applied.\n", $quiet);
} else {
    output("Column defaults already applied.\n", $quiet);
}

// Verify setup
$tableCount = getTableCount($conn, $testDbName);
$migrationCount = getMigrationCount($conn, $testDbName);
$hasFk = hasForeignKeys($conn, $testDbName);

output("\n", $quiet);
output("Test database setup complete!\n", $quiet);
output("  Tables: $tableCount\n", $quiet);
output("  Migrations: $migrationCount\n", $quiet);
output("  Foreign keys: " . ($hasFk ? "Yes" : "No") . "\n", $quiet);

if (!$hasFk) {
    output("\nNote: Foreign key constraints not detected.\n", $quiet);
    output("Some integration tests may be skipped.\n", $quiet);
}

mysqli_close($conn);
exit(0);
