<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Utils;

require_once __DIR__ . '/../../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../../src/backend/Core/Utils/string_utilities.php';

use Lwt\Core\Globals;
use Lwt\Database\Escaping;
use PHPUnit\Framework\TestCase;

Globals::initialize();

/**
 * Tests for string_utilities.php functions
 */
final class StringUtilitiesTest extends TestCase
{
    /**
     * Helper function using same signature as production code
     */
    private function escapeHtml(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Test HTML escaping with various inputs
     */
    public function testHtmlEscaping(): void
    {
        // Basic HTML escaping
        $this->assertEquals('&lt;script&gt;', $this->escapeHtml('<script>'));
        $this->assertEquals('&lt;div&gt;Test&lt;/div&gt;', $this->escapeHtml('<div>Test</div>'));

        // Special characters
        $this->assertEquals('&amp;', $this->escapeHtml('&'));
        $this->assertEquals('&quot;', $this->escapeHtml('"'));
        // Single quotes are escaped by htmlspecialchars with ENT_QUOTES
        $this->assertEquals('&#039;', $this->escapeHtml("'"));

        // Empty and null values
        $this->assertEquals('', $this->escapeHtml(null));
        $this->assertEquals('', $this->escapeHtml(''));

        // Normal text should pass through
        $this->assertEquals('Hello World', $this->escapeHtml('Hello World'));

        // UTF-8 characters should be preserved
        $this->assertEquals('日本語', $this->escapeHtml('日本語'));
        $this->assertEquals('Ελληνικά', $this->escapeHtml('Ελληνικά'));
    }

    /**
     * Test HTML escaping with various edge cases
     */
    public function testHtmlEscapingEdgeCases(): void
    {
        // Already escaped HTML
        $this->assertEquals('&amp;lt;script&amp;gt;', $this->escapeHtml('&lt;script&gt;'));

        // Multiple special characters
        $this->assertEquals('&lt;&amp;&gt;&quot;', $this->escapeHtml('<&>"'));

        // Long string with special characters
        $longString = str_repeat('<div>&amp;</div>', 100);
        $result = $this->escapeHtml($longString);
        $this->assertStringContainsString('&lt;div&gt;', $result);
        $this->assertStringNotContainsString('<div>', $result);

        // Newlines and tabs should be preserved
        $this->assertEquals("line1\nline2\tindented", $this->escapeHtml("line1\nline2\tindented"));
    }

    /**
     * Test line ending normalization
     */
    public function testPrepareTextdata(): void
    {
        // Windows line endings to Unix
        $this->assertEquals("line1\nline2", Escaping::prepareTextdata("line1\r\nline2"));
        $this->assertEquals("a\nb\nc", Escaping::prepareTextdata("a\r\nb\r\nc"));

        // Unix line endings should remain unchanged
        $this->assertEquals("line1\nline2", Escaping::prepareTextdata("line1\nline2"));

        // Mac line endings should remain unchanged
        $this->assertEquals("line1\rline2", Escaping::prepareTextdata("line1\rline2"));

        // Empty string
        $this->assertEquals('', Escaping::prepareTextdata(''));

        // No line endings
        $this->assertEquals('single line', Escaping::prepareTextdata('single line'));
    }

    /**
     * Test space removal function
     */
    public function testRemoveSpaces(): void
    {
        // Remove spaces when requested
        $this->assertEquals('test', remove_spaces('t e s t', true));
        $this->assertEquals('hello', remove_spaces('h e l l o', true));
        $this->assertEquals('nospaceshere', remove_spaces('n o s p a c e s h e r e', true));

        // Don't remove spaces when not requested
        $this->assertEquals('t e s t', remove_spaces('t e s t', false));
        $this->assertEquals('hello world', remove_spaces('hello world', false));

        // Empty string handling
        $this->assertEquals('', remove_spaces('', true));
        $this->assertEquals('', remove_spaces('', false));

        // String with no spaces
        $this->assertEquals('test', remove_spaces('test', true));
        $this->assertEquals('test', remove_spaces('test', false));
    }

    /**
     * Test zero-width space handling in remove_spaces function
     *
     * Note: Current implementation only removes regular spaces (U+0020)
     * The comment in the code mentions zero-width space but doesn't actually remove it
     */
    public function testRemoveSpacesZeroWidth(): void
    {
        // Zero-width space (U+200B) - current implementation does NOT remove it
        $text_with_zwsp = "test\u{200B}word";

        // When remove is true, only regular spaces are removed (not zero-width)
        $result = remove_spaces($text_with_zwsp, true);
        $this->assertEquals($text_with_zwsp, $result, 'Current implementation does not remove zero-width spaces');

        // When remove is false, everything remains
        $result = remove_spaces($text_with_zwsp, false);
        $this->assertEquals($text_with_zwsp, $result, 'Should keep all characters when not removing');

        // Multiple regular spaces are removed, but zero-width spaces remain
        $complex = "a b\u{200B}c d";
        $result = remove_spaces($complex, true);
        $this->assertEquals("ab\u{200B}cd", $result, 'Should remove regular spaces but keep zero-width');
    }

    /**
     * Test remove_spaces with Unicode characters
     */
    public function testRemoveSpacesUnicode(): void
    {
        // Chinese characters with spaces
        $this->assertEquals('你好世界', remove_spaces('你 好 世 界', true));
        $this->assertEquals('你 好 世 界', remove_spaces('你 好 世 界', false));

        // Japanese with spaces
        $this->assertEquals('こんにちは', remove_spaces('こ ん に ち は', true));

        // Arabic with spaces
        $this->assertEquals('مرحبا', remove_spaces('م ر ح ب ا', true));

        // Mixed languages
        $this->assertEquals('Hello世界', remove_spaces('Hello 世界', true));
    }

    /**
     * Test string replacement (first occurrence only)
     */
    public function testStrReplaceFirst(): void
    {
        // Basic replacement (only first occurrence should be replaced)
        $this->assertEquals('goodbye world hello', str_replace_first('hello', 'goodbye', 'hello world hello'));
        $this->assertEquals('xbc abc', str_replace_first('a', 'x', 'abc abc'));

        // No match
        $this->assertEquals('hello world', str_replace_first('goodbye', 'hi', 'hello world'));

        // Empty needle
        $this->assertEquals('test', str_replace_first('', 'x', 'test'));

        // Empty haystack
        $this->assertEquals('', str_replace_first('a', 'b', ''));

        // Needle at start
        $this->assertEquals('replaced test', str_replace_first('original', 'replaced', 'original test'));

        // Needle at end
        $this->assertEquals('test replaced', str_replace_first('original', 'replaced', 'test original'));
    }

    /**
     * Test str_replace_first with special regex characters
     */
    public function testStrReplaceFirstRegexCharacters(): void
    {
        // Needle with regex special characters - str_replace_first is NOT regex based
        // so special characters should be treated literally
        $this->assertEquals('[bcd]efg[abc]', str_replace_first('[abc]', '[bcd]', '[abc]efg[abc]'));
        $this->assertEquals('testworld...', str_replace_first('...', 'test', '...world...'));

        // Replacement with regex special characters
        $this->assertEquals('$test world', str_replace_first('hello', '$test', 'hello world'));
        $this->assertEquals('\\test world', str_replace_first('hello', '\\test', 'hello world'));
    }
}
