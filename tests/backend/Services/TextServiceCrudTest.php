<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Modules\Text\Application\TextFacade;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/TextFacade.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/Services/TextStatisticsService.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/Services/SentenceService.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/Services/AnnotationService.php';
require_once __DIR__ . '/../../../src/Modules/Vocabulary/Application/UseCases/FindSimilarTerms.php';
require_once __DIR__ . '/../../../src/Modules/Text/Application/Services/TextNavigationService.php';
require_once __DIR__ . '/../../../src/Modules/Language/Application/Services/TextParsingService.php';
require_once __DIR__ . '/../../../src/Modules/Vocabulary/Application/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/Shared/Infrastructure/Database/Restore.php';
// LanguageFacade loaded via autoloader

/**
 * CRUD tests for the TextFacade class.
 *
 * Tests create, read, update, delete, archive, and unarchive operations.
 */
class TextServiceCrudTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private TextFacade $service;
    private array $createdTextIds = [];
    private array $createdArchivedTextIds = [];

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
            // Create a test language
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM languages WHERE LgName = 'TextServiceCrudTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('TextServiceCrudTestLang', 'http://dict.test/###', '', 'http://translate.test/###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 0)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        Connection::query("DELETE FROM languages WHERE LgName = 'TextServiceCrudTestLang'");
    }

    protected function setUp(): void
    {
        $this->service = new TextFacade();
        $this->createdTextIds = [];
        $this->createdArchivedTextIds = [];
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up created texts
        foreach ($this->createdTextIds as $id) {
            Connection::query("DELETE FROM textitems2 WHERE Ti2TxID = " . $id);
            Connection::query("DELETE FROM sentences WHERE SeTxID = " . $id);
            Connection::query("DELETE FROM texts WHERE TxID = " . $id);
        }

        // Clean up created archived texts
        foreach ($this->createdArchivedTextIds as $id) {
            Connection::query("DELETE FROM archivedtexts WHERE AtID = " . $id);
        }
    }

    /**
     * Helper method to create a test text.
     *
     * @param string $title Text title
     * @param string $text  Text content
     *
     * @return int Text ID
     */
    private function createTestText(string $title, string $text): int
    {
        Connection::query(
            "INSERT INTO texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) " .
            "VALUES (" . self::$testLangId . ", " .
            "'" . mysqli_real_escape_string(Globals::getDbConnection(), $title) . "', " .
            "'" . mysqli_real_escape_string(Globals::getDbConnection(), $text) . "', " .
            "'', '', '')"
        );
        $id = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");
        $this->createdTextIds[] = $id;
        return $id;
    }

    /**
     * Helper method to create a test archived text.
     *
     * @param string $title Text title
     * @param string $text  Text content
     *
     * @return int Archived text ID
     */
    private function createTestArchivedText(string $title, string $text): int
    {
        Connection::query(
            "INSERT INTO archivedtexts (AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI) " .
            "VALUES (" . self::$testLangId . ", " .
            "'" . mysqli_real_escape_string(Globals::getDbConnection(), $title) . "', " .
            "'" . mysqli_real_escape_string(Globals::getDbConnection(), $text) . "', " .
            "'', '', '')"
        );
        $id = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");
        $this->createdArchivedTextIds[] = $id;
        return $id;
    }

    // ===== Read tests =====

    public function testGetTextByIdReturnsCorrectText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('CRUD Test Text', 'Test content for CRUD.');

        $result = $this->service->getTextById($textId);

        $this->assertIsArray($result);
        $this->assertEquals('CRUD Test Text', $result['TxTitle']);
        $this->assertEquals('Test content for CRUD.', $result['TxText']);
        $this->assertEquals(self::$testLangId, (int)$result['TxLgID']);
    }

    public function testGetTextByIdReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTextById(999999);

        $this->assertNull($result);
    }

    public function testGetTextForReadingReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Reading Test', 'Content for reading.');

        $result = $this->service->getTextForReading($textId);

        $this->assertIsArray($result);
        $this->assertEquals('Reading Test', $result['TxTitle']);
        $this->assertArrayHasKey('LgName', $result);
        $this->assertEquals('TextServiceCrudTestLang', $result['LgName']);
    }

    public function testGetTextForEditReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Edit Test', 'Content for editing.');

        $result = $this->service->getTextForEdit($textId);

        $this->assertIsArray($result);
        $this->assertEquals('Edit Test', $result['TxTitle']);
        $this->assertEquals('Content for editing.', $result['TxText']);
        $this->assertArrayHasKey('annot_exists', $result);
    }

    public function testGetTextDataForContentReturnsAnnotatedText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Content Test', 'Content data test.');

        $result = $this->service->getTextDataForContent($textId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('TxAnnotatedText', $result);
        $this->assertArrayHasKey('TxPosition', $result);
    }

    // ===== Delete tests =====

    public function testDeleteTextRemovesText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Delete Test', 'To be deleted.');

        // Delete the text
        $message = $this->service->deleteText($textId);

        $this->assertIsString($message);

        // Verify deletion
        $result = $this->service->getTextById($textId);
        $this->assertNull($result);

        // Remove from cleanup list since it's already deleted
        $this->createdTextIds = array_diff($this->createdTextIds, [$textId]);
    }

    public function testDeleteTextsRemovesMultipleTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId1 = $this->createTestText('Delete Multi 1', 'First to delete.');
        $textId2 = $this->createTestText('Delete Multi 2', 'Second to delete.');

        // Delete both texts
        $message = $this->service->deleteTexts([$textId1, $textId2]);

        $this->assertIsString($message);
        // Message format is "deleted_count / archived_count / sentence_count"
        $this->assertStringContainsString('2', $message);

        // Verify deletion
        $this->assertNull($this->service->getTextById($textId1));
        $this->assertNull($this->service->getTextById($textId2));

        // Remove from cleanup list
        $this->createdTextIds = array_diff($this->createdTextIds, [$textId1, $textId2]);
    }

    public function testDeleteTextsWithEmptyArrayReturnsZero(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $message = $this->service->deleteTexts([]);

        $this->assertEquals('Multiple Actions: 0', $message);
    }

    // ===== Archive tests =====

    public function testArchiveTextMovesTextToArchive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Archive Test', 'Content to archive.');

        // Archive the text
        $message = $this->service->archiveText($textId);

        $this->assertIsString($message);

        // Original text should be gone
        $this->assertNull($this->service->getTextById($textId));

        // Find in archived texts
        $archivedId = Connection::fetchValue(
            "SELECT AtID AS value FROM archivedtexts WHERE AtTitle = 'Archive Test' LIMIT 1"
        );

        $this->assertNotNull($archivedId);

        // Add to cleanup
        $this->createdArchivedTextIds[] = (int)$archivedId;

        // Remove from text cleanup list
        $this->createdTextIds = array_diff($this->createdTextIds, [$textId]);
    }

    public function testArchiveTextsArchivesMultipleTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId1 = $this->createTestText('Archive Multi 1', 'First to archive.');
        $textId2 = $this->createTestText('Archive Multi 2', 'Second to archive.');

        // Archive both
        $message = $this->service->archiveTexts([$textId1, $textId2]);

        $this->assertIsString($message);
        // Message format is "Archived Text(s): N"
        $this->assertStringContainsStringIgnoringCase('archived', $message);

        // Original texts should be gone
        $this->assertNull($this->service->getTextById($textId1));
        $this->assertNull($this->service->getTextById($textId2));

        // Find and cleanup archived texts
        $archivedIds = [];
        $res = Connection::query(
            "SELECT AtID FROM archivedtexts WHERE AtTitle LIKE 'Archive Multi%'"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $archivedIds[] = (int)$row['AtID'];
        }
        mysqli_free_result($res);

        $this->createdArchivedTextIds = array_merge($this->createdArchivedTextIds, $archivedIds);
        $this->createdTextIds = array_diff($this->createdTextIds, [$textId1, $textId2]);
    }

    // ===== Unarchive tests =====

    public function testUnarchiveTextMovesTextBack(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $archivedId = $this->createTestArchivedText('Unarchive Test', 'Content to unarchive.');

        // Unarchive
        $result = $this->service->unarchiveText($archivedId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('textId', $result);

        // If successfully unarchived, check new text exists
        if ($result['textId'] !== null) {
            $this->createdTextIds[] = $result['textId'];

            $text = $this->service->getTextById($result['textId']);
            $this->assertIsArray($text);
            $this->assertEquals('Unarchive Test', $text['TxTitle']);

            // Archived text should be gone
            $this->assertNull($this->service->getArchivedTextById($archivedId));
            $this->createdArchivedTextIds = array_diff($this->createdArchivedTextIds, [$archivedId]);
        }
    }

    public function testUnarchiveTextsUnarchivesMultipleTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $archivedId1 = $this->createTestArchivedText('Unarchive Multi 1', 'First to unarchive.');
        $archivedId2 = $this->createTestArchivedText('Unarchive Multi 2', 'Second to unarchive.');

        // Unarchive both
        $message = $this->service->unarchiveTexts([$archivedId1, $archivedId2]);

        $this->assertIsString($message);

        // Find created texts
        $res = Connection::query(
            "SELECT TxID FROM texts WHERE TxTitle LIKE 'Unarchive Multi%'"
        );
        while ($row = mysqli_fetch_assoc($res)) {
            $this->createdTextIds[] = (int)$row['TxID'];
        }
        mysqli_free_result($res);

        // Remove from archived cleanup
        $this->createdArchivedTextIds = array_diff(
            $this->createdArchivedTextIds,
            [$archivedId1, $archivedId2]
        );
    }

    public function testUnarchiveTextReturnsNullTextIdForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->unarchiveText(999999);

        $this->assertIsArray($result);
        $this->assertNull($result['textId']);
    }

    // ===== Archived text CRUD tests =====

    public function testGetArchivedTextByIdReturnsCorrectText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $archivedId = $this->createTestArchivedText('Archived CRUD Test', 'Archived content.');

        $result = $this->service->getArchivedTextById($archivedId);

        $this->assertIsArray($result);
        $this->assertEquals('Archived CRUD Test', $result['AtTitle']);
        $this->assertEquals('Archived content.', $result['AtText']);
    }

    public function testGetArchivedTextByIdReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getArchivedTextById(999999);

        $this->assertNull($result);
    }

    public function testDeleteArchivedTextRemovesText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $archivedId = $this->createTestArchivedText('Delete Archived Test', 'To be deleted.');

        $message = $this->service->deleteArchivedText($archivedId);

        $this->assertIsString($message);

        // Verify deletion
        $this->assertNull($this->service->getArchivedTextById($archivedId));

        // Remove from cleanup
        $this->createdArchivedTextIds = array_diff($this->createdArchivedTextIds, [$archivedId]);
    }

    public function testDeleteArchivedTextsRemovesMultiple(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id1 = $this->createTestArchivedText('Delete Archived Multi 1', 'First.');
        $id2 = $this->createTestArchivedText('Delete Archived Multi 2', 'Second.');

        $message = $this->service->deleteArchivedTexts([$id1, $id2]);

        $this->assertIsString($message);

        // Verify deletion
        $this->assertNull($this->service->getArchivedTextById($id1));
        $this->assertNull($this->service->getArchivedTextById($id2));

        // Remove from cleanup
        $this->createdArchivedTextIds = array_diff($this->createdArchivedTextIds, [$id1, $id2]);
    }

    public function testUpdateArchivedText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $archivedId = $this->createTestArchivedText('Update Archived Test', 'Original content.');

        // Update the archived text
        $message = $this->service->updateArchivedText(
            $archivedId,
            self::$testLangId,
            'Updated Archived Title',
            'Updated content.',
            'http://new-audio.test/file.mp3',
            'http://new-source.test/article'
        );

        $this->assertIsString($message);

        // Verify update
        $result = $this->service->getArchivedTextById($archivedId);
        $this->assertEquals('Updated Archived Title', $result['AtTitle']);
        $this->assertEquals('Updated content.', $result['AtText']);
        $this->assertEquals('http://new-audio.test/file.mp3', $result['AtAudioURI']);
        $this->assertEquals('http://new-source.test/article', $result['AtSourceURI']);
    }

    // ===== Count tests =====

    public function testGetTextCountReturnsCorrectCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create some texts
        $this->createTestText('Count Test 1', 'Content 1');
        $this->createTestText('Count Test 2', 'Content 2');

        $count = $this->service->getTextCount(
            ' AND TxLgID = ' . self::$testLangId,
            '',
            ''
        );

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testGetArchivedTextCountReturnsCorrectCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create archived texts
        $this->createTestArchivedText('Archived Count 1', 'Content 1');
        $this->createTestArchivedText('Archived Count 2', 'Content 2');

        $count = $this->service->getArchivedTextCount(
            ' AND AtLgID = ' . self::$testLangId,
            '',
            ''
        );

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(2, $count);
    }

    // ===== List tests =====

    public function testGetTextsListReturnsCorrectTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestText('List Test Text', 'List content.');

        $texts = $this->service->getTextsList(
            ' AND TxLgID = ' . self::$testLangId,
            '',
            '',
            1, // sort
            1, // page
            50 // perPage
        );

        $this->assertIsArray($texts);

        $titles = array_column($texts, 'TxTitle');
        $this->assertContains('List Test Text', $titles);
    }

    public function testGetArchivedTextsListReturnsCorrectTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestArchivedText('Archived List Test', 'Archived content.');

        $texts = $this->service->getArchivedTextsList(
            ' AND AtLgID = ' . self::$testLangId,
            '',
            '',
            1, // sort
            1, // page
            50 // perPage
        );

        $this->assertIsArray($texts);

        $titles = array_column($texts, 'AtTitle');
        $this->assertContains('Archived List Test', $titles);
    }

    // ===== Language settings tests =====

    public function testGetLanguageSettingsForReading(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageSettingsForReading(self::$testLangId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('LgTextSize', $result);
        $this->assertArrayHasKey('LgDict1URI', $result);
        $this->assertArrayHasKey('LgDict2URI', $result);
        $this->assertArrayHasKey('LgGoogleTranslateURI', $result);
        $this->assertArrayHasKey('LgRightToLeft', $result);
        $this->assertArrayHasKey('LgRemoveSpaces', $result);
        $this->assertArrayHasKey('LgRegexpWordCharacters', $result);
    }

    public function testGetTtsVoiceApi(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getTtsVoiceApi(self::$testLangId);

        // May be null if TTS not configured
        $this->assertTrue($result === null || is_string($result));
    }

    public function testGetLanguageIdByName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageIdByName('TextServiceCrudTestLang');

        $this->assertEquals(self::$testLangId, $result);
    }

    public function testGetLanguageIdByNameReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageIdByName('NonExistentLanguage12345');

        $this->assertNull($result);
    }

    // ===== Rebuild tests =====

    public function testRebuildTexts(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Rebuild Test', 'Content for rebuilding.');

        $message = $this->service->rebuildTexts([$textId]);

        $this->assertIsString($message);
        // Message format is "Rebuilt Text(s): N" or similar
        $this->assertStringContainsStringIgnoringCase('rebuilt', $message);
    }

    public function testRebuildTextsWithEmptyArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $message = $this->service->rebuildTexts([]);

        $this->assertEquals('Multiple Actions: 0', $message);
    }

    // ===== Set term sentences tests =====

    public function testSetTermSentences(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Term Sentence Test', 'Test for term sentences.');

        $message = $this->service->setTermSentences([$textId], false);

        $this->assertIsString($message);
    }

    public function testSetTermSentencesWithActiveOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = $this->createTestText('Active Term Test', 'Active terms test.');

        $message = $this->service->setTermSentences([$textId], true);

        $this->assertIsString($message);
    }
}
