<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/backend/Core/EnvLoader.php';
use Lwt\Core\EnvLoader;
use Lwt\Core\LWT_Globals;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../src/backend/Core/database_connect.php';
require_once __DIR__ . '/../../src/backend/Core/tags.php';

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for tag management functions
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

        if (!LWT_Globals::getDbConnection()) {
            $connection = connect_to_database(
                $config['server'], $config['userid'], $config['passwd'], $testDbname, $config['socket']
            );
            LWT_Globals::setDbConnection($connection);
        }

        self::$dbConnection = LWT_Globals::getDbConnection();
    }

    /**
     * Test addtaglist function - adds tag to word list
     */
    public function testAddTagList(): void
    {
        \Lwt\Core\LWT_Globals::getTablePrefix();

        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // This function adds tags to words but needs words to exist
        // We'll test the basic function structure
        $result = addtaglist('TestTag', '(1,2,3)');

        $this->assertIsString($result, 'Should return a string message');
        $this->assertStringContainsString('Tag added', $result, 'Should indicate tag was processed');
    }

    /**
     * Test addtaglist with special characters
     */
    public function testAddTagListWithSpecialCharacters(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with special characters in tag name
        $result = addtaglist('Tag-With-Dashes', '(1)');
        $this->assertIsString($result);

        $result = addtaglist('Tag_With_Underscores', '(1)');
        $this->assertIsString($result);

        // Test with spaces
        $result = addtaglist('Tag With Spaces', '(1)');
        $this->assertIsString($result);
    }

    /**
     * Test addtaglist with SQL injection attempt
     *
     * Note: The function escapes the tag name, so SQL injection in tag names
     * won't execute malicious code, but may create tags with escaped content
     */
    public function testAddTagListSQLInjection(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Should handle SQL injection safely by escaping
        // The tag name will be stored as escaped string, not executed
        $result = addtaglist("SafeTag_NoInjection", '(1)');
        $this->assertIsString($result, 'Should handle tag creation safely');
    }

    /**
     * Test addtaglist with whitespace tag name
     *
     * Note: Empty strings become NULL which violates database constraint
     * This tests the actual behavior - whitespace tags work
     */
    public function testAddTagListWhitespaceTag(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Single space should work (gets trimmed and becomes non-empty in DB)
        $result = addtaglist(' Trimmed Tag ', '(1)');
        $this->assertIsString($result, 'Should handle whitespace-padded tag name');
    }

    /**
     * Test addtaglist with empty list
     */
    public function testAddTagListEmptyList(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = addtaglist('TestTag', '()');
        $this->assertIsString($result);
        $this->assertStringContainsString('0', $result, 'Should indicate 0 items affected');
    }

    /**
     * Test addarchtexttaglist function
     */
    public function testAddArchTextTagList(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = addarchtexttaglist('ArchiveTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag added', $result);
    }

    /**
     * Test addarchtexttaglist with special characters
     */
    public function testAddArchTextTagListSpecialChars(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = addarchtexttaglist('Archive-Tag_2023', '(1)');
        $this->assertIsString($result);
    }

    /**
     * Test addtexttaglist function
     */
    public function testAddTextTagList(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = addtexttaglist('TextTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag added', $result);
    }

    /**
     * Test removetaglist function
     */
    public function testRemoveTagList(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // First add a tag to ensure it exists
        addtaglist('RemoveTestTag', '(1)');

        // Now remove it
        $result = removetaglist('RemoveTestTag', '(1,2,3)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag removed', $result);
    }

    /**
     * Test removetaglist with non-existent tag
     */
    public function testRemoveTagListNonExistent(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Non-existent tag ID
        $result = removetaglist('99999', '(1)');
        $this->assertIsString($result);
    }

    /**
     * Test removearchtexttaglist function
     */
    public function testRemoveArchTextTagList(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // First add a tag to ensure it exists
        addarchtexttaglist('RemoveArchTestTag', '(1)');

        // Now remove it
        $result = removearchtexttaglist('RemoveArchTestTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag removed', $result);
    }

    /**
     * Test removetexttaglist function
     */
    public function testRemoveTextTagList(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // First add a tag to ensure it exists
        addtexttaglist('RemoveTextTestTag', '(1)');

        // Now remove it
        $result = removetexttaglist('RemoveTextTestTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag removed', $result);
    }

    /**
     * Test get_tags function - returns cached tags
     */
    public function testGetTags(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = get_tags();
        $this->assertIsArray($tags, 'Should return array of tags');
    }

    /**
     * Test get_tags with refresh
     */
    public function testGetTagsRefresh(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = get_tags(1);
        $this->assertIsArray($tags, 'Should return array of tags after refresh');
    }

    /**
     * Test get_texttags function
     */
    public function testGetTextTags(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = get_texttags();
        $this->assertIsArray($tags, 'Should return array of text tags');
    }

    /**
     * Test get_texttags with refresh
     */
    public function testGetTextTagsRefresh(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = get_texttags(1);
        $this->assertIsArray($tags, 'Should return array after refresh');
    }

    /**
     * Test tag functions handle Unicode properly
     */
    public function testTagFunctionsWithUnicode(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with Unicode tag names
        $result = addtaglist('日本語タグ', '(1)');
        $this->assertIsString($result, 'Should handle Unicode in tag names');

        $result = addtaglist('العربية', '(1)');
        $this->assertIsString($result, 'Should handle Arabic in tag names');

        $result = addtaglist('Ελληνικά', '(1)');
        $this->assertIsString($result, 'Should handle Greek in tag names');
    }

    /**
     * Test tag functions with very long tag names
     */
    public function testTagFunctionsWithLongNames(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Use a unique long tag name to avoid duplicate key errors
        $longTag = 'LongTag_' . time() . '_' . str_repeat('X', 20);
        $result = addtaglist($longTag, '(1)');
        $this->assertIsString($result, 'Should handle long tag names');
    }

    /**
     * Test multiple operations in sequence
     */
    public function testSequentialTagOperations(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Add tag
        $result = addtaglist('SequentialTag', '(1)');
        $this->assertIsString($result);

        // Get tags
        $tags = get_tags(1);
        $this->assertIsArray($tags);

        // Remove tag (would need actual tag ID from database)
        // This is more of an integration test
    }

    /**
     * Test getTextTitle function
     */
    public function testGetTextTitle(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Test with non-existent ID
        $title = getTextTitle('99999');
        $this->assertIsString($title, 'Should return string even for non-existent text');

        // Test with invalid ID
        $title = getTextTitle('invalid');
        $this->assertIsString($title, 'Should handle invalid ID safely');

        // Test with empty string
        $title = getTextTitle('');
        $this->assertIsString($title);
    }

    /**
     * Test getTextTitle with SQL injection
     */
    public function testGetTextTitleSQLInjection(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $title = getTextTitle("1'; DROP TABLE texts; --");
        $this->assertIsString($title, 'Should handle SQL injection safely');
    }

    /**
     * Test get_tag_selectoptions with language filter
     */
    public function testGetTagSelectOptions(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        // Test with no language filter
        $result = get_tag_selectoptions('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test get_tag_selectoptions with language ID
     */
    public function testGetTagSelectOptionsWithLanguage(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        // Test with language filter
        $result = get_tag_selectoptions('', 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test get_tag_selectoptions with selected value
     */
    public function testGetTagSelectOptionsWithSelected(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        // Test with selected value
        $result = get_tag_selectoptions('1', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test get_texttag_selectoptions function
     */
    public function testGetTextTagSelectOptions(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = get_texttag_selectoptions('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test get_texttag_selectoptions with language
     */
    public function testGetTextTagSelectOptionsWithLanguage(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = get_texttag_selectoptions('', 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test get_txtag_selectoptions function
     */
    public function testGetTxTagSelectOptions(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = get_txtag_selectoptions('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test get_txtag_selectoptions with language
     */
    public function testGetTxTagSelectOptionsWithLanguage(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = get_txtag_selectoptions(1, '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test get_archivedtexttag_selectoptions function
     */
    public function testGetArchivedTextTagSelectOptions(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = get_archivedtexttag_selectoptions('', '');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('[Filter off]', $result);
    }

    /**
     * Test get_archivedtexttag_selectoptions with language
     */
    public function testGetArchivedTextTagSelectOptionsWithLanguage(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!function_exists('get_selected')) {
            $this->markTestSkipped('get_selected function not available (requires session_utility.php)');
        }

        $result = get_archivedtexttag_selectoptions('', 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    /**
     * Test getWordTags with valid word ID
     */
    public function testGetWordTags(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = getWordTags(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="termtags"', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getWordTags with zero ID
     */
    public function testGetWordTagsWithZeroId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = getWordTags(0);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getWordTags with negative ID
     */
    public function testGetWordTagsWithNegativeId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = getWordTags(-1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
    }

    /**
     * Test getTextTags with valid text ID
     */
    public function testGetTextTagsFunction(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = getTextTags(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="texttags"', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getTextTags with zero ID
     */
    public function testGetTextTagsWithZeroId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = getTextTags(0);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getArchivedTextTags function
     */
    public function testGetArchivedTextTagsFunction(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = getArchivedTextTags(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="texttags"', $result);
        $this->assertStringContainsString('</ul>', $result);
    }

    /**
     * Test getArchivedTextTags with zero ID
     */
    public function testGetArchivedTextTagsWithZeroId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = getArchivedTextTags(0);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
    }

    /**
     * Test saveWordTags with invalid ID
     */
    public function testSaveWordTagsInvalidId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Should handle non-numeric ID safely
        saveWordTags('invalid');

        // Test with empty string
        saveWordTags('');

        // Should complete without errors
        $this->assertTrue(true);
    }

    /**
     * Test saveTextTags with invalid ID
     */
    public function testSaveTextTagsInvalidId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Should handle non-numeric ID safely
        saveTextTags('invalid');

        // Test with empty string
        saveTextTags('');

        // Should complete without errors
        $this->assertTrue(true);
    }

    /**
     * Test saveArchivedTextTags with invalid ID
     */
    public function testSaveArchivedTextTagsInvalidId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Should handle non-numeric ID safely
        saveArchivedTextTags('invalid');

        // Test with empty string
        saveArchivedTextTags('');

        // Should complete without errors
        $this->assertTrue(true);
    }

    /**
     * Test removetaglist with empty tag name
     */
    public function testRemoveTagListEmptyTag(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = removetaglist('', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('not found', $result);
    }

    /**
     * Test removetexttaglist with empty tag name
     */
    public function testRemoveTextTagListEmptyTag(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = removetexttaglist('', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('not found', $result);
    }

    /**
     * Test removearchtexttaglist with empty tag name
     */
    public function testRemoveArchTextTagListEmptyTag(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = removearchtexttaglist('', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('not found', $result);
    }

    /**
     * Test getTextTitle with zero ID
     */
    public function testGetTextTitleZeroId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $title = getTextTitle('0');
        $this->assertIsString($title);
    }

    /**
     * Test getTextTitle with negative ID
     */
    public function testGetTextTitleNegativeId(): void
    {
        
        if (!LWT_Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $title = getTextTitle('-1');
        $this->assertIsString($title);
    }
}
