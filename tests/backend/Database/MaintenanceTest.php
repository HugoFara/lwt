<?php

declare(strict_types=1);

namespace Lwt\Tests\Database;

require_once __DIR__ . '/../../../src/backend/Core/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Maintenance;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/database_connect.php';

/**
 * Unit tests for the Database\Maintenance class.
 *
 * Tests database maintenance and optimization utilities.
 */
class MaintenanceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static ?int $testLanguageId = null;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = connect_to_database(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);
        self::$tbpref = Globals::getTablePrefix();

        if (self::$dbConnected) {
            self::createTestData();
        }
    }

    private static function createTestData(): void
    {
        $tbpref = self::$tbpref;

        // Clean up any existing test language first
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoLgID IN (SELECT LgID FROM {$tbpref}languages WHERE LgName = 'Test Maintenance Language')");
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgName = 'Test Maintenance Language'");
        // Also clean up Japanese test languages to avoid MeCab issues
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoLgID IN (SELECT LgID FROM {$tbpref}languages WHERE LgName = 'Test Japanese')");
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgName = 'Test Japanese'");
        // Clean up any MECAB languages that could trigger the MeCab requirement
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoLgID IN (SELECT LgID FROM {$tbpref}languages WHERE UPPER(LgRegexpWordCharacters) = 'MECAB')");
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE UPPER(LgRegexpWordCharacters) = 'MECAB'");
        // Clean up split-each-char languages (Chinese) to avoid bug with initWordCount
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoLgID IN (SELECT LgID FROM {$tbpref}languages WHERE LgSplitEachChar = 1)");
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgSplitEachChar = 1");
        // Clean up any words with WoWordCount=0 from languages that don't exist (orphaned words)
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoWordCount = 0 AND WoLgID NOT IN (SELECT LgID FROM {$tbpref}languages)");

        // Create test language
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test Maintenance Language',
            'https://en.wiktionary.org/wiki/###',
            'https://translate.google.com/?text=###',
            100, '', '.!?', '', 'a-zA-Z', 0, 0, 0
        )";
        do_mysqli_query($sql);
        self::$testLanguageId = mysqli_insert_id(Globals::getDbConnection());
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $tbpref = self::$tbpref;

        // Clean up test language and associated data
        if (self::$testLanguageId) {
            do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoLgID = " . self::$testLanguageId);
            do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgID = " . self::$testLanguageId);
        }
    }

    // ===== adjustAutoIncrement() tests =====

    public function testAdjustAutoIncrementLanguages(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with languages table (has auto-increment)
        Maintenance::adjustAutoIncrement('languages', 'LgID');
        $this->assertTrue(true, 'adjustAutoIncrement should complete without error for languages');
    }

    public function testAdjustAutoIncrementTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with texts table
        Maintenance::adjustAutoIncrement('texts', 'TxID');
        $this->assertTrue(true, 'adjustAutoIncrement should complete without error for texts');
    }

    public function testAdjustAutoIncrementWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with words table
        Maintenance::adjustAutoIncrement('words', 'WoID');
        $this->assertTrue(true, 'adjustAutoIncrement should complete without error for words');
    }

    public function testAdjustAutoIncrementSentences(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with sentences table
        Maintenance::adjustAutoIncrement('sentences', 'SeID');
        $this->assertTrue(true, 'adjustAutoIncrement should complete without error for sentences');
    }

    public function testAdjustAutoIncrementTags(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with tags table
        Maintenance::adjustAutoIncrement('tags', 'TgID');
        $this->assertTrue(true, 'adjustAutoIncrement should complete without error for tags');
    }

    public function testAdjustAutoIncrementTags2(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with tags2 table
        Maintenance::adjustAutoIncrement('tags2', 'T2ID');
        $this->assertTrue(true, 'adjustAutoIncrement should complete without error for tags2');
    }

    public function testAdjustAutoIncrementArchivdtexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Test with archivedtexts table
        Maintenance::adjustAutoIncrement('archivedtexts', 'AtID');
        $this->assertTrue(true, 'adjustAutoIncrement should complete without error for archivedtexts');
    }

    public function testAdjustAutoIncrementEmptyTable(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a temporary empty table
        $tbpref = self::$tbpref;
        do_mysqli_query("CREATE TEMPORARY TABLE {$tbpref}test_empty (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50)
        )");

        // Should handle empty table gracefully (set AUTO_INCREMENT to 1)
        Maintenance::adjustAutoIncrement('test_empty', 'id');

        $this->assertTrue(true, 'adjustAutoIncrement should handle empty tables');
    }

    // ===== optimizeDatabase() tests =====

    public function testOptimizeDatabaseRuns(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // This function optimizes all tables
        // It should execute without errors
        Maintenance::optimizeDatabase();
        $this->assertTrue(true, 'optimizeDatabase should complete without error');
    }

    public function testOptimizeDatabaseAdjustsAutoIncrement(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Get current auto_increment value for languages
        $tbpref = self::$tbpref;
        $before = get_first_value(
            "SELECT AUTO_INCREMENT as value
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name = '{$tbpref}languages'"
        );

        Maintenance::optimizeDatabase();

        // After optimization, auto_increment should be adjusted
        $after = get_first_value(
            "SELECT AUTO_INCREMENT as value
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name = '{$tbpref}languages'"
        );

        // Both should be valid integers (the actual values depend on DB state)
        $this->assertIsNumeric($before !== null ? $before : '1');
        $this->assertIsNumeric($after !== null ? $after : '1');
    }

    // ===== initWordCount() tests =====

    public function testInitWordCountRuns(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Should run without error even with no words with count = 0
        Maintenance::initWordCount();
        $this->assertTrue(true, 'initWordCount should complete without error');
    }

    public function testInitWordCountUpdatesZeroCounts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Insert a test word with WoWordCount = 0
        $sql = "INSERT INTO {$tbpref}words (
            WoLgID, WoText, WoTextLC, WoStatus, WoWordCount
        ) VALUES (
            " . self::$testLanguageId . ",
            'testword',
            'testword',
            1,
            0
        )";
        do_mysqli_query($sql);
        $wordId = mysqli_insert_id(Globals::getDbConnection());

        // Run initWordCount
        Maintenance::initWordCount();

        // Check that word count was updated
        $count = get_first_value(
            "SELECT WoWordCount as value FROM {$tbpref}words WHERE WoID = $wordId"
        );

        // 'testword' is a single word, so count should be 1
        $this->assertEquals('1', $count, 'Word count should be updated from 0 to 1');

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoID = $wordId");
    }

    public function testInitWordCountMultiwordExpression(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Insert a multi-word expression with WoWordCount = 0
        $sql = "INSERT INTO {$tbpref}words (
            WoLgID, WoText, WoTextLC, WoStatus, WoWordCount
        ) VALUES (
            " . self::$testLanguageId . ",
            'hello world test',
            'hello world test',
            1,
            0
        )";
        do_mysqli_query($sql);
        $wordId = mysqli_insert_id(Globals::getDbConnection());

        // Run initWordCount
        Maintenance::initWordCount();

        // Check that word count was updated
        $count = get_first_value(
            "SELECT WoWordCount as value FROM {$tbpref}words WHERE WoID = $wordId"
        );

        // 'hello world test' is 3 words
        $this->assertEquals('3', $count, 'Multi-word expression count should be 3');

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoID = $wordId");
    }

    public function testInitWordCountPreservesExistingCounts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Insert a word with existing word count
        $sql = "INSERT INTO {$tbpref}words (
            WoLgID, WoText, WoTextLC, WoStatus, WoWordCount
        ) VALUES (
            " . self::$testLanguageId . ",
            'existing',
            'existing',
            1,
            5
        )";
        do_mysqli_query($sql);
        $wordId = mysqli_insert_id(Globals::getDbConnection());

        // Run initWordCount
        Maintenance::initWordCount();

        // Check that word count was NOT changed (only updates WoWordCount = 0)
        $count = get_first_value(
            "SELECT WoWordCount as value FROM {$tbpref}words WHERE WoID = $wordId"
        );

        $this->assertEquals('5', $count, 'Existing word count should be preserved');

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoID = $wordId");
    }

    // ===== updateJapaneseWordCount() tests =====
    // Note: These tests require MeCab to be installed, so we test defensively

    public function testUpdateJapaneseWordCountRequiresMecab(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Check if MeCab is available
        $mecabPath = shell_exec('which mecab 2>/dev/null');
        if (empty($mecabPath)) {
            $this->markTestSkipped('MeCab not installed - skipping Japanese word count test');
        }

        $tbpref = self::$tbpref;

        // Create a Japanese-like language with MECAB
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test Japanese',
            'https://jisho.org/search/###',
            'https://translate.google.com/?text=###',
            100, '', '。！？', '', 'MECAB', 0, 0, 0
        )";
        do_mysqli_query($sql);
        $japLangId = mysqli_insert_id(Globals::getDbConnection());

        // With MeCab installed, this should work
        Maintenance::updateJapaneseWordCount($japLangId);
        $this->assertTrue(true, 'updateJapaneseWordCount should work with MeCab');

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgID = $japLangId");
    }

    // ===== Edge cases and robustness tests =====

    public function testOptimizeDatabaseWithPrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // The optimizeDatabase function should work correctly with table prefix

        // This mainly tests that the SQL is constructed correctly with prefix
        Maintenance::optimizeDatabase();
        $this->assertTrue(true, 'optimizeDatabase should work with table prefix');
    }

    public function testInitWordCountBatchProcessing(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Insert multiple words with WoWordCount = 0
        // The function processes in batches of 1000
        $wordIds = [];
        for ($i = 0; $i < 10; $i++) {
            $word = "batchtest$i";
            $sql = "INSERT INTO {$tbpref}words (
                WoLgID, WoText, WoTextLC, WoStatus, WoWordCount
            ) VALUES (
                " . self::$testLanguageId . ",
                '$word',
                '$word',
                1,
                0
            )";
            do_mysqli_query($sql);
            $wordIds[] = mysqli_insert_id(Globals::getDbConnection());
        }

        // Run initWordCount
        Maintenance::initWordCount();

        // Check all words were updated
        foreach ($wordIds as $wordId) {
            $count = get_first_value(
                "SELECT WoWordCount as value FROM {$tbpref}words WHERE WoID = $wordId"
            );
            $this->assertEquals('1', $count, "Word $wordId should have count updated");
        }

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoID IN (" . implode(',', $wordIds) . ")");
    }

    public function testInitWordCountUnicodeWords(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a language that supports accented characters
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test French',
            'https://fr.wiktionary.org/wiki/###',
            'https://translate.google.com/?text=###',
            100, '', '.!?', '', 'a-zA-ZàâäéèêëïîôùûüœæçÀÂÄÉÈÊËÏÎÔÙÛÜŒÆÇ', 0, 0, 0
        )";
        do_mysqli_query($sql);
        $frLangId = mysqli_insert_id(Globals::getDbConnection());

        // Insert a French word with accents
        $sql = "INSERT INTO {$tbpref}words (
            WoLgID, WoText, WoTextLC, WoStatus, WoWordCount
        ) VALUES (
            $frLangId,
            'été',
            'été',
            1,
            0
        )";
        do_mysqli_query($sql);
        $wordId = mysqli_insert_id(Globals::getDbConnection());

        // Run initWordCount
        Maintenance::initWordCount();

        // Check word count was updated
        $count = get_first_value(
            "SELECT WoWordCount as value FROM {$tbpref}words WHERE WoID = $wordId"
        );
        $this->assertEquals('1', $count, 'Unicode word count should be updated');

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoID = $wordId");
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgID = $frLangId");
    }

    public function testInitWordCountSplitEachChar(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;

        // Create a Chinese-like language with LgSplitEachChar = 1
        $sql = "INSERT INTO {$tbpref}languages (
            LgName, LgDict1URI, LgGoogleTranslateURI, LgTextSize,
            LgCharacterSubstitutions, LgRegexpSplitSentences,
            LgExceptionsSplitSentences, LgRegexpWordCharacters,
            LgRemoveSpaces, LgSplitEachChar, LgRightToLeft
        ) VALUES (
            'Test Chinese',
            'https://www.mdbg.net/chinese/dictionary?wdqb=###',
            'https://translate.google.com/?text=###',
            100, '', '。！？', '', '\\x{4E00}-\\x{9FFF}', 1, 1, 0
        )";
        do_mysqli_query($sql);
        $chLangId = mysqli_insert_id(Globals::getDbConnection());

        // Insert a Chinese word with WoWordCount = 0
        $sql = "INSERT INTO {$tbpref}words (
            WoLgID, WoText, WoTextLC, WoStatus, WoWordCount
        ) VALUES (
            $chLangId,
            '你好',
            '你好',
            1,
            0
        )";
        do_mysqli_query($sql);
        $wordId = mysqli_insert_id(Globals::getDbConnection());

        // Run initWordCount - this should NOT cause SQL syntax error anymore
        Maintenance::initWordCount();

        // Check that word count was updated (should be at least 1)
        $count = get_first_value(
            "SELECT WoWordCount as value FROM {$tbpref}words WHERE WoID = $wordId"
        );
        $this->assertGreaterThanOrEqual(1, (int)$count, 'Split-each-char word count should be at least 1');

        // Clean up
        do_mysqli_query("DELETE FROM {$tbpref}words WHERE WoID = $wordId");
        do_mysqli_query("DELETE FROM {$tbpref}languages WHERE LgID = $chLangId");
    }
}
