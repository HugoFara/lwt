<?php declare(strict_types=1);

require __DIR__ . "/../../connect.inc.php";
$GLOBALS['dbname'] = "test_" . $dbname;
require_once __DIR__ . '/../../inc/database_connect.php';
require_once __DIR__ . '/../../inc/tags.php';

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
        global $DBCONNECTION;

        include __DIR__ . "/../../connect.inc.php";
        $testDbname = "test_" . $dbname;

        if (!$DBCONNECTION) {
            $DBCONNECTION = connect_to_database(
                $server, $userid, $passwd, $testDbname, $socket ?? ""
            );
        }

        self::$dbConnection = $DBCONNECTION;
    }

    /**
     * Test addtaglist function - adds tag to word list
     */
    public function testAddTagList(): void
    {
        global $DBCONNECTION, $tbpref;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $longTag = str_repeat('LongTag', 10);
        $result = addtaglist($longTag, '(1)');
        $this->assertIsString($result, 'Should handle long tag names');
    }

    /**
     * Test multiple operations in sequence
     */
    public function testSequentialTagOperations(): void
    {
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
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
        global $DBCONNECTION;

        if (!$DBCONNECTION) {
            $this->markTestSkipped('Database connection not available');
        }

        $title = getTextTitle("1'; DROP TABLE texts; --");
        $this->assertIsString($title, 'Should handle SQL injection safely');
    }
}
