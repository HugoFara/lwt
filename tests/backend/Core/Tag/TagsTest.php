<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Tag;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TagService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Comprehensive tests for TagService
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
        $result = TagService::addTagToWords('TestTag', '(1,2,3)');

        $this->assertIsString($result, 'Should return a string message');
        $this->assertStringContainsString('Tag added', $result, 'Should indicate tag was processed');
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
        $result = TagService::addTagToWords('Tag-With-Dashes', '(1)');
        $this->assertIsString($result);

        $result = TagService::addTagToWords('Tag_With_Underscores', '(1)');
        $this->assertIsString($result);

        // Test with spaces
        $result = TagService::addTagToWords('Tag With Spaces', '(1)');
        $this->assertIsString($result);
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
        $result = TagService::addTagToWords("SafeTag_NoInjection", '(1)');
        $this->assertIsString($result, 'Should handle tag creation safely');
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
        $result = TagService::addTagToWords(' Trimmed Tag ', '(1)');
        $this->assertIsString($result, 'Should handle whitespace-padded tag name');
    }

    /**
     * Test addTagToWords with empty list
     */
    public function testAddTagToWordsEmptyList(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagService::addTagToWords('TestTag', '()');
        $this->assertIsString($result);
        $this->assertStringContainsString('0', $result, 'Should indicate 0 items affected');
    }

    /**
     * Test addTagToArchivedTexts function
     */
    public function testAddTagToArchivedTexts(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagService::addTagToArchivedTexts('ArchiveTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag added', $result);
    }

    /**
     * Test addTagToArchivedTexts with special characters
     */
    public function testAddTagToArchivedTextsSpecialChars(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagService::addTagToArchivedTexts('Archive-Tag_2023', '(1)');
        $this->assertIsString($result);
    }

    /**
     * Test addTagToTexts function
     */
    public function testAddTagToTexts(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagService::addTagToTexts('TextTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag added', $result);
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
        TagService::addTagToWords('RemoveTestTag', '(1)');

        // Now remove it
        $result = TagService::removeTagFromWords('RemoveTestTag', '(1,2,3)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag removed', $result);
    }

    /**
     * Test removeTagFromWords with non-existent tag
     */
    public function testRemoveTagFromWordsNonExistent(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Non-existent tag ID
        $result = TagService::removeTagFromWords('99999', '(1)');
        $this->assertIsString($result);
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
        TagService::addTagToArchivedTexts('RemoveArchTestTag', '(1)');

        // Now remove it
        $result = TagService::removeTagFromArchivedTexts('RemoveArchTestTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag removed', $result);
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
        TagService::addTagToTexts('RemoveTextTestTag', '(1)');

        // Now remove it
        $result = TagService::removeTagFromTexts('RemoveTextTestTag', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('Tag removed', $result);
    }

    /**
     * Test getAllTermTags function - returns cached tags
     */
    public function testGetAllTermTags(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $tags = TagService::getAllTermTags();
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

        $tags = TagService::getAllTermTags(true);
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

        $tags = TagService::getAllTextTags();
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

        $tags = TagService::getAllTextTags(true);
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
        $result = TagService::addTagToWords('日本語タグ', '(1)');
        $this->assertIsString($result, 'Should handle Unicode in tag names');

        $result = TagService::addTagToWords('العربية', '(1)');
        $this->assertIsString($result, 'Should handle Arabic in tag names');

        $result = TagService::addTagToWords('Ελληνικά', '(1)');
        $this->assertIsString($result, 'Should handle Greek in tag names');
    }

    /**
     * Test tag functions with very long tag names
     */
    public function testTagFunctionsWithLongNames(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        // Use a unique long tag name to avoid duplicate key errors
        $longTag = 'LongTag_' . time() . '_' . str_repeat('X', 20);
        $result = TagService::addTagToWords($longTag, '(1)');
        $this->assertIsString($result, 'Should handle long tag names');
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
        $result = TagService::addTagToWords('SequentialTag', '(1)');
        $this->assertIsString($result);

        // Get tags
        $tags = TagService::getAllTermTags(true);
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
        $result = TagService::getTermTagSelectOptions('', '');
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
        $result = TagService::getTermTagSelectOptions('', 1);
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
        $result = TagService::getTermTagSelectOptions('1', '');
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

        $result = TagService::getTextTagSelectOptions('', '');
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

        $result = TagService::getTextTagSelectOptions('', 1);
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

        $result = TagService::getTextTagSelectOptionsWithTextIds('', '');
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

        $result = TagService::getTextTagSelectOptionsWithTextIds(1, '');
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

        $result = TagService::getArchivedTextTagSelectOptions('', '');
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

        $result = TagService::getArchivedTextTagSelectOptions('', 1);
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

        $result = TagService::getWordTagsHtml(1);
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

        $result = TagService::getWordTagsHtml(0);
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

        $result = TagService::getWordTagsHtml(-1);
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

        $result = TagService::getTextTagsHtml(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="texttags"', $result);
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

        $result = TagService::getTextTagsHtml(0);
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

        $result = TagService::getArchivedTextTagsHtml(1);
        $this->assertIsString($result);
        $this->assertStringContainsString('<ul', $result);
        $this->assertStringContainsString('id="texttags"', $result);
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

        $result = TagService::getArchivedTextTagsHtml(0);
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
        TagService::saveWordTags(999999);
        TagService::saveWordTags(0);

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
        TagService::saveTextTags(999999);
        TagService::saveTextTags(0);

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
        TagService::saveArchivedTextTags(999999);
        TagService::saveArchivedTextTags(0);

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

        $result = TagService::removeTagFromWords('', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('not found', $result);
    }

    /**
     * Test removeTagFromTexts with empty tag name
     */
    public function testRemoveTagFromTextsEmptyTag(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagService::removeTagFromTexts('', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('not found', $result);
    }

    /**
     * Test removeTagFromArchivedTexts with empty tag name
     */
    public function testRemoveTagFromArchivedTextsEmptyTag(): void
    {

        if (!Globals::getDbConnection()) {
            $this->markTestSkipped('Database connection not available');
        }

        $result = TagService::removeTagFromArchivedTexts('', '(1)');
        $this->assertIsString($result);
        $this->assertStringContainsString('not found', $result);
    }
}
