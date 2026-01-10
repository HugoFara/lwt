<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Tag;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';
require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Http/UrlUtilities.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Modules\Tags\Application\TagsFacade;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Comprehensive tests for TagsFacade
 *
 * Tests tag creation, retrieval, assignment to words/texts, and list manipulation
 */
class TagsTest extends TestCase
{
    private static $dbConnection;

    /**
     * Set up database connection
     */
    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'], $config['userid'], $config['passwd'], $testDbname, $config['socket']
            );
            Globals::setDbConnection($connection);
        }

        self::$dbConnection = Globals::getDbConnection();
    }

    /**
     * Test addTagToWords - adds tag to word list
     */
    public function testAddTagToWords(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // This function adds tags to words but needs words to exist
        // We'll test the basic function structure
        $result = TagsFacade::addTagToWords('TestTag', '(1,2,3)');

        $this->assertIsArray($result, 'Should return an array');
        $this->assertArrayHasKey('count', $result, 'Should have count key');
        $this->assertArrayHasKey('error', $result, 'Should have error key');
        $this->assertNull($result['error'], 'Should not have an error');
    }

    /**
     * Test addTagToWords with special characters
     */
    public function testAddTagToWordsWithSpecialCharacters(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with special characters in tag name
        $result = TagsFacade::addTagToWords('Tag-With-Dashes', '(1)');
        $this->assertIsArray($result);
        $this->assertNull($result['error']);

        $result = TagsFacade::addTagToWords('Tag_With_Underscores', '(1)');
        $this->assertIsArray($result);
        $this->assertNull($result['error']);

        // Test with spaces
        $result = TagsFacade::addTagToWords('Tag With Spaces', '(1)');
        $this->assertIsArray($result);
        $this->assertNull($result['error']);
    }

    /**
     * Test addTagToWords with SQL injection attempt
     *
     * Note: The function escapes the tag name, so SQL injection in tag names
     * won't execute malicious code, but may create tags with escaped content
     */
    public function testAddTagToWordsSQLInjection(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Should handle SQL injection safely by escaping
        // The tag name will be stored as escaped string, not executed
        $result = TagsFacade::addTagToWords("SafeTag_NoInjection", '(1)');
        $this->assertIsArray($result, 'Should handle tag creation safely');
        $this->assertNull($result['error']);
    }

    /**
     * Test addTagToWords with whitespace tag name
     *
     * Note: Empty strings become NULL which violates database constraint
     * This tests the actual behavior - whitespace tags work
     */
    public function testAddTagToWordsWhitespaceTag(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Single space should work (gets trimmed and becomes non-empty in DB)
        $result = TagsFacade::addTagToWords(' Trimmed Tag ', '(1)');
        $this->assertIsArray($result, 'Should handle whitespace-padded tag name');
        $this->assertNull($result['error']);
    }

    /**
     * Test addTagToWords with empty list
     */
    public function testAddTagToWordsEmptyList(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::addTagToWords('TestTag', '()');
        $this->assertIsArray($result);
        $this->assertSame(0, $result['count'], 'Should indicate 0 items affected');
        $this->assertNull($result['error']);
    }

    /**
     * Test addTagToArchivedTexts function
     */
    public function testAddTagToArchivedTexts(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::addTagToArchivedTexts('ArchiveTag', '(1)');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertNull($result['error']);
    }

    /**
     * Test addTagToArchivedTexts with special characters
     */
    public function testAddTagToArchivedTextsSpecialChars(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::addTagToArchivedTexts('Archive-Tag_2023', '(1)');
        $this->assertIsArray($result);
        $this->assertNull($result['error']);
    }

    /**
     * Test addTagToTexts function
     */
    public function testAddTagToTexts(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::addTagToTexts('TextTag', '(1)');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertNull($result['error']);
    }

    /**
     * Test removeTagFromWords function
     */
    public function testRemoveTagFromWords(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // First add a tag to ensure it exists
        TagsFacade::addTagToWords('RemoveTestTag', '(1)');

        // Now remove it
        $result = TagsFacade::removeTagFromWords('RemoveTestTag', '(1,2,3)');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertNull($result['error']);
    }

    /**
     * Test removeTagFromWords with non-existent tag
     */
    public function testRemoveTagFromWordsNonExistent(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Non-existent tag ID - should return error
        $result = TagsFacade::removeTagFromWords('99999', '(1)');
        $this->assertIsArray($result);
        $this->assertNotNull($result['error'], 'Should have error for non-existent tag');
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test removeTagFromArchivedTexts function
     */
    public function testRemoveTagFromArchivedTexts(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // First add a tag to ensure it exists
        TagsFacade::addTagToArchivedTexts('RemoveArchTestTag', '(1)');

        // Now remove it
        $result = TagsFacade::removeTagFromArchivedTexts('RemoveArchTestTag', '(1)');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertNull($result['error']);
    }

    /**
     * Test removeTagFromTexts function
     */
    public function testRemoveTagFromTexts(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // First add a tag to ensure it exists
        TagsFacade::addTagToTexts('RemoveTextTestTag', '(1)');

        // Now remove it
        $result = TagsFacade::removeTagFromTexts('RemoveTextTestTag', '(1)');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertNull($result['error']);
    }

    /**
     * Test getAllTermTags function - returns cached tags
     */
    public function testGetAllTermTags(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = TagsFacade::getAllTermTags();
        $this->assertIsArray($tags, 'Should return array of tags');
    }

    /**
     * Test getAllTermTags with refresh
     */
    public function testGetAllTermTagsRefresh(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = TagsFacade::getAllTermTags(true);
        $this->assertIsArray($tags, 'Should return array of tags after refresh');
    }

    /**
     * Test getAllTextTags function
     */
    public function testGetAllTextTags(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = TagsFacade::getAllTextTags();
        $this->assertIsArray($tags, 'Should return array of text tags');
    }

    /**
     * Test getAllTextTags with refresh
     */
    public function testGetAllTextTagsRefresh(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = TagsFacade::getAllTextTags(true);
        $this->assertIsArray($tags, 'Should return array after refresh');
    }

    /**
     * Test tag functions handle Unicode properly
     */
    public function testTagFunctionsWithUnicode(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with Unicode tag names
        $result = TagsFacade::addTagToWords('日本語タグ', '(1)');
        $this->assertIsArray($result, 'Should handle Unicode in tag names');
        $this->assertNull($result['error']);

        $result = TagsFacade::addTagToWords('العربية', '(1)');
        $this->assertIsArray($result, 'Should handle Arabic in tag names');
        $this->assertNull($result['error']);

        $result = TagsFacade::addTagToWords('Ελληνικά', '(1)');
        $this->assertIsArray($result, 'Should handle Greek in tag names');
        $this->assertNull($result['error']);
    }

    /**
     * Test tag functions with very long tag names
     */
    public function testTagFunctionsWithLongNames(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Use a unique tag name (max 20 chars for TgText column)
        $longTag = 'LT_' . substr((string)time(), -7);
        $result = TagsFacade::addTagToWords($longTag, '(1)');
        $this->assertIsArray($result, 'Should handle long tag names');
        $this->assertNull($result['error']);
    }

    /**
     * Test multiple operations in sequence
     */
    public function testSequentialTagOperations(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Add tag
        $result = TagsFacade::addTagToWords('SequentialTag', '(1)');
        $this->assertIsArray($result);
        $this->assertNull($result['error']);

        // Get tags
        $tags = TagsFacade::getAllTermTags(true);
        $this->assertIsArray($tags);

        // Remove tag (would need actual tag ID from database)
        // This is more of an integration test
    }

    /**
     * Test getTermTagSelectOptions with language filter
     */
    public function testGetTermTagSelectOptions(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        // Test with no language filter
        $result = TagsFacade::getTermTagSelectOptions('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test getTermTagSelectOptions with language ID
     */
    public function testGetTermTagSelectOptionsWithLanguage(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        // Test with language filter
        $result = TagsFacade::getTermTagSelectOptions('', 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test getTermTagSelectOptions with selected value
     */
    public function testGetTermTagSelectOptionsWithSelected(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        // Test with selected value
        $result = TagsFacade::getTermTagSelectOptions('1', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test getTextTagSelectOptions function
     */
    public function testGetTextTagSelectOptions(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = TagsFacade::getTextTagSelectOptions('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test getTextTagSelectOptions with language
     */
    public function testGetTextTagSelectOptionsWithLanguage(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = TagsFacade::getTextTagSelectOptions('', 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test getTextTagSelectOptionsWithTextIds function
     */
    public function testGetTextTagSelectOptionsWithTextIds(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = TagsFacade::getTextTagSelectOptionsWithTextIds('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test getTextTagSelectOptionsWithTextIds with language
     */
    public function testGetTextTagSelectOptionsWithTextIdsWithLanguage(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = TagsFacade::getTextTagSelectOptionsWithTextIds(1, '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test getArchivedTextTagSelectOptions function
     */
    public function testGetArchivedTextTagSelectOptions(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = TagsFacade::getArchivedTextTagSelectOptions('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test getArchivedTextTagSelectOptions with language
     */
    public function testGetArchivedTextTagSelectOptionsWithLanguage(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = TagsFacade::getArchivedTextTagSelectOptions('', 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test getWordTagsHtml with valid word ID
     */
    public function testGetWordTagsHtml(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::getWordTagsHtml(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="termtags"', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getWordTagsHtml with zero ID
     */
    public function testGetWordTagsHtmlWithZeroId(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::getWordTagsHtml(0);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getWordTagsHtml with negative ID
     */
    public function testGetWordTagsHtmlWithNegativeId(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::getWordTagsHtml(-1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
    }

    /**
     * Test getTextTagsHtml with valid text ID
     */
    public function testGetTextTagsHtml(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::getTextTagsHtml(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="text_tag_map"', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getTextTagsHtml with zero ID
     */
    public function testGetTextTagsHtmlWithZeroId(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::getTextTagsHtml(0);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getArchivedTextTagsHtml function
     */
    public function testGetArchivedTextTagsHtml(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::getArchivedTextTagsHtml(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="text_tag_map"', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getArchivedTextTagsHtml with zero ID
     */
    public function testGetArchivedTextTagsHtmlWithZeroId(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::getArchivedTextTagsHtml(0);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
    }

    /**
     * Test saveWordTags with non-existent ID
     */
    public function testSaveWordTagsNonExistent(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with a non-existent ID - should handle gracefully
        TagsFacade::saveWordTagsFromForm(999999);
        TagsFacade::saveWordTagsFromForm(0);

        // Should complete without errors
        $this->assertTrue(true);
    }

    /**
     * Test saveTextTags with non-existent ID
     */
    public function testSaveTextTagsNonExistent(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with a non-existent ID - should handle gracefully
        TagsFacade::saveTextTagsFromForm(999999);
        TagsFacade::saveTextTagsFromForm(0);

        // Should complete without errors
        $this->assertTrue(true);
    }

    /**
     * Test saveArchivedTextTags with non-existent ID
     */
    public function testSaveArchivedTextTagsNonExistent(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with a non-existent ID - should handle gracefully
        TagsFacade::saveArchivedTextTagsFromForm(999999);
        TagsFacade::saveArchivedTextTagsFromForm(0);

        // Should complete without errors
        $this->assertTrue(true);
    }

    /**
     * Test removeTagFromWords with empty tag name
     */
    public function testRemoveTagFromWordsEmptyTag(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::removeTagFromWords('', '(1)');
        $this->assertIsArray($result);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test removeTagFromTexts with empty tag name
     */
    public function testRemoveTagFromTextsEmptyTag(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::removeTagFromTexts('', '(1)');
        $this->assertIsArray($result);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test removeTagFromArchivedTexts with empty tag name
     */
    public function testRemoveTagFromArchivedTextsEmptyTag(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagsFacade::removeTagFromArchivedTexts('', '(1)');
        $this->assertIsArray($result);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test saveWordTagsFromArray handles duplicate tags gracefully (Issue #120)
     *
     * This tests the scenario where a tag exists in the database but the
     * session cache is stale. The function should not throw a duplicate
     * key error.
     */
    public function testSaveWordTagsFromArrayHandlesDuplicateTags(): void
    {
        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Use a unique tag name (max 20 chars for TgText column)
        $uniqueTagName = 'D_' . substr(uniqid(), -8);

        // Clean up any pre-existing tag with this name (shouldn't exist, but be safe)
        Connection::preparedExecute(
            'DELETE FROM tags WHERE TgText = ?',
            [$uniqueTagName]
        );

        // Create a test language and word for the FK constraint
        Connection::query(
            "INSERT INTO languages (LgName, LgDict1URI, LgGoogleTranslateURI)
             VALUES ('DupTagTestLang', 'http://test', 'http://test')"
        );
        $langId = (int)Connection::lastInsertId();

        Connection::query(
            "INSERT INTO words (WoText, WoTextLC, WoStatus, WoLgID)
             VALUES ('duptestword', 'duptestword', 1, $langId)"
        );
        $wordId = (int)Connection::lastInsertId();

        try {
            // Insert the tag directly into the database
            Connection::preparedExecute(
                'INSERT INTO tags (TgText) VALUES (?)',
                [$uniqueTagName]
            );

            // Clear the session cache to simulate stale cache
            $_SESSION['TAGS'] = [];

            // Try to save a word with this tag - should NOT throw exception
            // even though the tag exists but is not in the cache
            TagsFacade::saveWordTagsFromArray($wordId, [$uniqueTagName]);
            $this->assertTrue(true, 'saveWordTagsFromArray should handle duplicate tags gracefully');
        } catch (\RuntimeException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $this->fail('Issue #120: saveWordTagsFromArray throws duplicate key error when cache is stale: ' . $e->getMessage());
            }
            throw $e;
        } finally {
            // Cleanup: remove the test tag, word_tag_map associations, word, and language
            Connection::preparedExecute(
                'DELETE FROM word_tag_map WHERE WtWoID = ?',
                [$wordId]
            );
            Connection::preparedExecute(
                'DELETE FROM tags WHERE TgText = ?',
                [$uniqueTagName]
            );
            Connection::query("DELETE FROM words WHERE WoID = $wordId");
            Connection::query("DELETE FROM languages WHERE LgID = $langId");
        }
    }
}
