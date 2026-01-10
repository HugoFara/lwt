<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Tests for inter-table foreign key constraints.
 *
 * These tests verify that:
 * 1. Ti2WoID is nullable (can store NULL for unknown words)
 * 2. CASCADE delete works correctly for all FK relationships
 * 3. SET NULL works for word_occurrences.Ti2WoID when words are deleted
 * 4. FK constraints prevent orphaned references
 */
class ForeignKeyTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private static bool $hasForeignKeys = false;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);

        if (self::$dbConnected) {
            // Create a test language for all tests
            Connection::query(
                "INSERT INTO languages (LgName, LgDict1URI, LgCharacterSubstitutions,
                 LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters)
                 VALUES ('FK_Test_Language', 'https://test.com/###', '', '.!?', '', 'a-zA-Z')"
            );
            self::$testLangId = (int) Connection::fetchValue(
                "SELECT LgID FROM languages WHERE LgName = 'FK_Test_Language'",
                'LgID'
            );

            // Check if FK constraints are present by querying INFORMATION_SCHEMA
            $fkCount = (int) Connection::fetchValue(
                "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = '$testDbname'
                 AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                 AND TABLE_NAME = 'word_occurrences'",
                'cnt'
            );
            self::$hasForeignKeys = ($fkCount > 0);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$dbConnected && self::$testLangId > 0) {
            // Cleanup - CASCADE should handle related records
            Connection::query("DELETE FROM languages WHERE LgName LIKE 'FK_Test_%'");
        }
    }

    protected function setUp(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Skip test if FK constraints are not present.
     */
    private function requireForeignKeys(): void
    {
        if (!self::$hasForeignKeys) {
            $this->markTestSkipped('FK constraints not present - run migrations to enable');
        }
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data after each test
        Connection::query("DELETE FROM word_occurrences WHERE Ti2Text LIKE 'fktest_%'");
        Connection::query("DELETE FROM sentences WHERE SeText LIKE 'FK Test%'");
        Connection::query("DELETE FROM texts WHERE TxTitle LIKE 'FK_Test_%'");
        Connection::query("DELETE FROM words WHERE WoText LIKE 'fktest_%'");
        Connection::query("DELETE FROM word_tag_map WHERE WtWoID NOT IN (SELECT WoID FROM words)");
        Connection::query("DELETE FROM tags WHERE TgText LIKE 'fktest_%'");
        Connection::query("DELETE FROM text_tags WHERE T2Text LIKE 'fktest_%'");
        Connection::query("DELETE FROM archived_texts WHERE AtTitle LIKE 'FK_Test_%'");
        Connection::query("DELETE FROM news_feeds WHERE NfName LIKE 'FK_Test_%'");
    }

    // ===== Ti2WoID Nullable Tests =====

    /**
     * Test that Ti2WoID can be NULL (for unknown words).
     */
    public function testTi2WoIDCanBeNull(): void
    {
        // Create text and sentence
        $textId = $this->createTestText('FK_Test_Nullable');
        $sentenceId = $this->createTestSentence($textId, 'FK Test sentence');

        // Insert text item with NULL Ti2WoID
        Connection::query(
            "INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES (NULL, " . self::$testLangId . ", $textId, $sentenceId, 1, 1, 'fktest_unknown')"
        );

        // Verify it was inserted with NULL
        $result = Connection::fetchValue(
            "SELECT Ti2WoID FROM word_occurrences WHERE Ti2Text = 'fktest_unknown'",
            'Ti2WoID'
        );

        $this->assertNull($result, 'Ti2WoID should be NULL for unknown words');
    }

    /**
     * Test that Ti2WoID can reference a valid word.
     */
    public function testTi2WoIDCanReferenceWord(): void
    {
        $textId = $this->createTestText('FK_Test_WordRef');
        $sentenceId = $this->createTestSentence($textId, 'FK Test sentence');
        $wordId = $this->createTestWord('fktest_known');

        Connection::query(
            "INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES ($wordId, " . self::$testLangId . ", $textId, $sentenceId, 1, 1, 'fktest_known')"
        );

        $result = Connection::fetchValue(
            "SELECT Ti2WoID FROM word_occurrences WHERE Ti2Text = 'fktest_known'",
            'Ti2WoID'
        );

        $this->assertEquals($wordId, (int) $result, 'Ti2WoID should reference the word');
    }

    // ===== CASCADE Delete Tests =====

    /**
     * Test that deleting a text cascades to sentences.
     */
    public function testTextDeleteCascadesToSentences(): void
    {
        $this->requireForeignKeys();
        $textId = $this->createTestText('FK_Test_SentenceCascade');
        $sentenceId = $this->createTestSentence($textId, 'FK Test cascade sentence');

        // Verify sentence exists
        $beforeCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeID = $sentenceId",
            'cnt'
        );
        $this->assertEquals(1, $beforeCount, 'Sentence should exist before delete');

        // Delete text
        Connection::query("DELETE FROM texts WHERE TxID = $textId");

        // Verify sentence was cascaded
        $afterCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeID = $sentenceId",
            'cnt'
        );
        $this->assertEquals(0, $afterCount, 'Sentence should be deleted via CASCADE');
    }

    /**
     * Test that deleting a text cascades to word_occurrences.
     */
    public function testTextDeleteCascadesToTextItems(): void
    {
        $this->requireForeignKeys();
        $textId = $this->createTestText('FK_Test_TextItemCascade');
        $sentenceId = $this->createTestSentence($textId, 'FK Test sentence');

        Connection::query(
            "INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES (NULL, " . self::$testLangId . ", $textId, $sentenceId, 1, 1, 'fktest_cascade')"
        );

        // Verify text item exists
        $beforeCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_occurrences WHERE Ti2TxID = $textId",
            'cnt'
        );
        $this->assertEquals(1, $beforeCount, 'TextItem should exist before delete');

        // Delete text
        Connection::query("DELETE FROM texts WHERE TxID = $textId");

        // Verify text item was cascaded
        $afterCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_occurrences WHERE Ti2TxID = $textId",
            'cnt'
        );
        $this->assertEquals(0, $afterCount, 'TextItem should be deleted via CASCADE');
    }

    /**
     * Test that deleting a word sets Ti2WoID to NULL (not cascade delete).
     */
    public function testWordDeleteSetsTextItemToNull(): void
    {
        $this->requireForeignKeys();
        $textId = $this->createTestText('FK_Test_SetNull');
        $sentenceId = $this->createTestSentence($textId, 'FK Test sentence');
        $wordId = $this->createTestWord('fktest_setnull');

        Connection::query(
            "INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES ($wordId, " . self::$testLangId . ", $textId, $sentenceId, 1, 1, 'fktest_setnull')"
        );

        // Verify Ti2WoID is set
        $beforeWoId = Connection::fetchValue(
            "SELECT Ti2WoID FROM word_occurrences WHERE Ti2Text = 'fktest_setnull'",
            'Ti2WoID'
        );
        $this->assertEquals($wordId, (int) $beforeWoId, 'Ti2WoID should be set before word delete');

        // Delete word
        Connection::query("DELETE FROM words WHERE WoID = $wordId");

        // Verify Ti2WoID is now NULL but text item still exists
        $count = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_occurrences WHERE Ti2Text = 'fktest_setnull'",
            'cnt'
        );
        $this->assertEquals(1, $count, 'TextItem should still exist after word deletion');

        $afterWoId = Connection::fetchValue(
            "SELECT Ti2WoID FROM word_occurrences WHERE Ti2Text = 'fktest_setnull'",
            'Ti2WoID'
        );
        $this->assertNull($afterWoId, 'Ti2WoID should be NULL after word deletion');
    }

    /**
     * Test that deleting a word cascades to word_tag_map.
     */
    public function testWordDeleteCascadesToWordTags(): void
    {
        $this->requireForeignKeys();
        $wordId = $this->createTestWord('fktest_tagged');

        Connection::query(
            "INSERT INTO tags (TgText, TgComment) VALUES ('fktest_tag', 'Test tag')"
        );
        $tagId = (int) Connection::fetchValue(
            "SELECT TgID FROM tags WHERE TgText = 'fktest_tag'",
            'TgID'
        );

        Connection::query("INSERT INTO word_tag_map (WtWoID, WtTgID) VALUES ($wordId, $tagId)");

        // Verify wordtag exists
        $beforeCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_tag_map WHERE WtWoID = $wordId",
            'cnt'
        );
        $this->assertEquals(1, $beforeCount, 'Wordtag should exist before delete');

        // Delete word
        Connection::query("DELETE FROM words WHERE WoID = $wordId");

        // Verify wordtag was cascaded
        $afterCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_tag_map WHERE WtWoID = $wordId",
            'cnt'
        );
        $this->assertEquals(0, $afterCount, 'Wordtag should be deleted via CASCADE');
    }

    /**
     * Test that deleting a tag cascades to word_tag_map.
     */
    public function testTagDeleteCascadesToWordTags(): void
    {
        $this->requireForeignKeys();
        $wordId = $this->createTestWord('fktest_tagged2');

        Connection::query(
            "INSERT INTO tags (TgText, TgComment) VALUES ('fktest_tag2', 'Test tag 2')"
        );
        $tagId = (int) Connection::fetchValue(
            "SELECT TgID FROM tags WHERE TgText = 'fktest_tag2'",
            'TgID'
        );

        Connection::query("INSERT INTO word_tag_map (WtWoID, WtTgID) VALUES ($wordId, $tagId)");

        // Verify wordtag exists
        $beforeCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_tag_map WHERE WtTgID = $tagId",
            'cnt'
        );
        $this->assertEquals(1, $beforeCount, 'Wordtag should exist before delete');

        // Delete tag
        Connection::query("DELETE FROM tags WHERE TgID = $tagId");

        // Verify wordtag was cascaded
        $afterCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_tag_map WHERE WtTgID = $tagId",
            'cnt'
        );
        $this->assertEquals(0, $afterCount, 'Wordtag should be deleted via tag CASCADE');
    }

    /**
     * Test that deleting a text cascades to text_tag_map.
     */
    public function testTextDeleteCascadesToTextTags(): void
    {
        $this->requireForeignKeys();
        $textId = $this->createTestText('FK_Test_TextTag');

        Connection::query(
            "INSERT INTO text_tags (T2Text, T2Comment) VALUES ('fktest_texttag', 'Test text tag')"
        );
        $tagId = (int) Connection::fetchValue(
            "SELECT T2ID FROM text_tags WHERE T2Text = 'fktest_texttag'",
            'T2ID'
        );

        Connection::query("INSERT INTO text_tag_map (TtTxID, TtT2ID) VALUES ($textId, $tagId)");

        // Verify texttag exists
        $beforeCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM text_tag_map WHERE TtTxID = $textId",
            'cnt'
        );
        $this->assertEquals(1, $beforeCount, 'Texttag should exist before delete');

        // Delete text
        Connection::query("DELETE FROM texts WHERE TxID = $textId");

        // Verify texttag was cascaded
        $afterCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM text_tag_map WHERE TtTxID = $textId",
            'cnt'
        );
        $this->assertEquals(0, $afterCount, 'Texttag should be deleted via CASCADE');
    }

    /**
     * Test full cascade chain: language -> text -> sentence -> textitem.
     */
    public function testFullCascadeChain(): void
    {
        $this->requireForeignKeys();
        // Create a separate language for this test
        Connection::query(
            "INSERT INTO languages (LgName, LgDict1URI, LgCharacterSubstitutions,
             LgRegexpSplitSentences, LgExceptionsSplitSentences, LgRegexpWordCharacters)
             VALUES ('FK_Test_Cascade_Lang', 'https://test.com/###', '', '.!?', '', 'a-zA-Z')"
        );
        $langId = (int) Connection::fetchValue(
            "SELECT LgID FROM languages WHERE LgName = 'FK_Test_Cascade_Lang'",
            'LgID'
        );

        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
             VALUES ($langId, 'FK_Test_FullCascade', 'Test', '')"
        );
        $textId = (int) Connection::fetchValue(
            "SELECT TxID FROM texts WHERE TxTitle = 'FK_Test_FullCascade'",
            'TxID'
        );

        Connection::query(
            "INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
             VALUES ($langId, $textId, 1, 'FK Test full cascade', 1)"
        );
        $sentenceId = (int) Connection::fetchValue(
            "SELECT SeID FROM sentences WHERE SeTxID = $textId",
            'SeID'
        );

        Connection::query(
            "INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES (NULL, $langId, $textId, $sentenceId, 1, 1, 'fktest_fullcascade')"
        );

        // Verify all exist
        $this->assertEquals(1, (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM texts WHERE TxID = $textId", 'cnt'
        ));
        $this->assertEquals(1, (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeID = $sentenceId", 'cnt'
        ));
        $this->assertEquals(1, (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_occurrences WHERE Ti2TxID = $textId", 'cnt'
        ));

        // Delete language - should cascade through entire chain
        Connection::query("DELETE FROM languages WHERE LgID = $langId");

        // Verify all were deleted
        $this->assertEquals(0, (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM texts WHERE TxID = $textId", 'cnt'
        ), 'Text should be deleted via CASCADE');
        $this->assertEquals(0, (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeID = $sentenceId", 'cnt'
        ), 'Sentence should be deleted via CASCADE');
        $this->assertEquals(0, (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM word_occurrences WHERE Ti2TxID = $textId", 'cnt'
        ), 'TextItem should be deleted via CASCADE');
    }

    /**
     * Test that deleting a newsfeed cascades to feed_links.
     */
    public function testNewsfeedDeleteCascadesToFeedlinks(): void
    {
        $this->requireForeignKeys();
        Connection::query(
            "INSERT INTO news_feeds (NfLgID, NfName, NfSourceURI, NfArticleSectionTags,
             NfFilterTags, NfUpdate, NfOptions)
             VALUES (" . self::$testLangId . ", 'FK_Test_Feed', 'https://test.com/feed',
             '', '', 0, '')"
        );
        $feedId = (int) Connection::fetchValue(
            "SELECT NfID FROM news_feeds WHERE NfName = 'FK_Test_Feed'",
            'NfID'
        );

        Connection::query(
            "INSERT INTO feed_links (FlNfID, FlTitle, FlLink, FlDescription, FlDate, FlAudio, FlText)
             VALUES ($feedId, 'FK_Test_Link', 'https://test.com/article', 'Test', NOW(), '', '')"
        );
        $linkId = (int) Connection::fetchValue(
            "SELECT FlID FROM feed_links WHERE FlTitle = 'FK_Test_Link'",
            'FlID'
        );

        // Verify feedlink exists
        $beforeCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM feed_links WHERE FlID = $linkId",
            'cnt'
        );
        $this->assertEquals(1, $beforeCount, 'Feedlink should exist before delete');

        // Delete newsfeed
        Connection::query("DELETE FROM news_feeds WHERE NfID = $feedId");

        // Verify feedlink was cascaded
        $afterCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM feed_links WHERE FlID = $linkId",
            'cnt'
        );
        $this->assertEquals(0, $afterCount, 'Feedlink should be deleted via CASCADE');
    }

    /**
     * Test that deleting an archived text cascades to archived_text_tag_map.
     */
    public function testArchivedTextDeleteCascadesToArchTextTags(): void
    {
        $this->requireForeignKeys();
        Connection::query(
            "INSERT INTO archived_texts (AtLgID, AtTitle, AtText, AtAnnotatedText)
             VALUES (" . self::$testLangId . ", 'FK_Test_Archived', 'Archived content', '')"
        );
        $archId = (int) Connection::fetchValue(
            "SELECT AtID FROM archived_texts WHERE AtTitle = 'FK_Test_Archived'",
            'AtID'
        );

        Connection::query(
            "INSERT INTO text_tags (T2Text, T2Comment) VALUES ('fktest_archtag', 'Arch tag')"
        );
        $tagId = (int) Connection::fetchValue(
            "SELECT T2ID FROM text_tags WHERE T2Text = 'fktest_archtag'",
            'T2ID'
        );

        Connection::query("INSERT INTO archived_text_tag_map (AgAtID, AgT2ID) VALUES ($archId, $tagId)");

        // Verify archtexttag exists
        $beforeCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM archived_text_tag_map WHERE AgAtID = $archId",
            'cnt'
        );
        $this->assertEquals(1, $beforeCount, 'ArchTextTag should exist before delete');

        // Delete archived text
        Connection::query("DELETE FROM archived_texts WHERE AtID = $archId");

        // Verify archtexttag was cascaded
        $afterCount = (int) Connection::fetchValue(
            "SELECT COUNT(*) AS cnt FROM archived_text_tag_map WHERE AgAtID = $archId",
            'cnt'
        );
        $this->assertEquals(0, $afterCount, 'ArchTextTag should be deleted via CASCADE');
    }

    // ===== FK Constraint Enforcement Tests =====

    /**
     * Test that inserting a text with invalid language ID fails.
     */
    public function testInvalidLanguageReferenceRejected(): void
    {
        $this->requireForeignKeys();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/foreign key constraint fails/i');

        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
             VALUES (255, 'FK_Test_Invalid', 'Test', '')"
        );
    }

    /**
     * Test that inserting a sentence with invalid text ID fails.
     */
    public function testInvalidTextReferenceRejected(): void
    {
        $this->requireForeignKeys();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/foreign key constraint fails/i');

        Connection::query(
            "INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
             VALUES (" . self::$testLangId . ", 65535, 1, 'Invalid', 1)"
        );
    }

    /**
     * Test that inserting a textitem with invalid word ID fails.
     */
    public function testInvalidWordReferenceRejected(): void
    {
        $this->requireForeignKeys();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/foreign key constraint fails/i');

        $textId = $this->createTestText('FK_Test_InvalidWord');
        $sentenceId = $this->createTestSentence($textId, 'FK Test sentence');

        Connection::query(
            "INSERT INTO word_occurrences (Ti2WoID, Ti2LgID, Ti2TxID, Ti2SeID, Ti2Order, Ti2WordCount, Ti2Text)
             VALUES (16777215, " . self::$testLangId . ", $textId, $sentenceId, 1, 1, 'fktest_invalid')"
        );
    }

    // ===== Helper Methods =====

    /**
     * Create a test text and return its ID.
     */
    private function createTestText(string $title): int
    {
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText)
             VALUES (" . self::$testLangId . ", '$title', 'Test content', '')"
        );
        return (int) Connection::fetchValue(
            "SELECT TxID FROM texts WHERE TxTitle = '$title'",
            'TxID'
        );
    }

    /**
     * Create a test sentence and return its ID.
     */
    private function createTestSentence(int $textId, string $text): int
    {
        Connection::query(
            "INSERT INTO sentences (SeLgID, SeTxID, SeOrder, SeText, SeFirstPos)
             VALUES (" . self::$testLangId . ", $textId, 1, '$text', 1)"
        );
        return (int) Connection::fetchValue(
            "SELECT SeID FROM sentences WHERE SeTxID = $textId AND SeText = '$text'",
            'SeID'
        );
    }

    /**
     * Create a test word and return its ID.
     */
    private function createTestWord(string $text): int
    {
        Connection::query(
            "INSERT INTO words (WoLgID, WoText, WoTextLC, WoStatus, WoTranslation, WoWordCount)
             VALUES (" . self::$testLangId . ", '$text', '$text', 1, 'test translation', 1)"
        );
        return (int) Connection::fetchValue(
            "SELECT WoID FROM words WHERE WoText = '$text'",
            'WoID'
        );
    }
}
