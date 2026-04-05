<?php

/**
 * Unit tests for JapaneseTextParser.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests\Shared\Infrastructure\Database
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Tests\Backend\Shared\Infrastructure\Database;

use Lwt\Shared\Infrastructure\Database\JapaneseTextParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for JapaneseTextParser static methods.
 *
 * @since  3.0.0
 */
#[CoversClass(JapaneseTextParser::class)]
class JapaneseTextParserTest extends TestCase
{
    // =========================================================================
    // splitJapaneseSentences
    // =========================================================================

    #[Test]
    public function splitJapaneseSentencesWithSimpleTextReturnsSingleElement(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences('Hello world');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('Hello world', $result[0]);
    }

    #[Test]
    public function splitJapaneseSentencesWithEmptyStringReturnsSingleEmptyElement(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences('');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('', $result[0]);
    }

    #[Test]
    public function splitJapaneseSentencesWithNewlinesSplitsIntoMultiple(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences("Line one\nLine two");

        $this->assertCount(2, $result);
        $this->assertSame('Line one', $result[0]);
        $this->assertStringContainsString('Line two', $result[1]);
    }

    #[Test]
    public function splitJapaneseSentencesInsertsPilcrowAtNewlines(): void
    {
        // preg_replace("/[\n]+/u", "\n¶", $text) then explode("\n")
        $result = JapaneseTextParser::splitJapaneseSentences("First\nSecond");

        $this->assertSame('First', $result[0]);
        // Second element starts with pilcrow (¶ = U+00B6)
        $this->assertStringStartsWith("\xC2\xB6", $result[1]);
    }

    #[Test]
    public function splitJapaneseSentencesCollapsesConsecutiveNewlines(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences("A\n\n\nB");

        // Multiple newlines collapse to single \n¶ via regex
        $this->assertCount(2, $result);
        $this->assertSame('A', $result[0]);
    }

    #[Test]
    public function splitJapaneseSentencesCollapsesSpacesAndTabs(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences("Hello   \t  world");

        $this->assertCount(1, $result);
        $this->assertSame('Hello world', $result[0]);
    }

    #[Test]
    public function splitJapaneseSentencesTrimsLeadingAndTrailingWhitespace(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences('  Hello  ');

        $this->assertSame('Hello', $result[0]);
    }

    #[Test]
    public function splitJapaneseSentencesHandlesJapaneseCharacters(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences('これはテストです');

        $this->assertCount(1, $result);
        $this->assertSame('これはテストです', $result[0]);
    }

    #[Test]
    public function splitJapaneseSentencesHandlesMultipleJapaneseParagraphs(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences("最初の文。\n二番目の文。");

        $this->assertCount(2, $result);
        $this->assertSame('最初の文。', $result[0]);
    }

    #[Test]
    public function splitJapaneseSentencesWithWhitespaceOnlyReturnsEmptyString(): void
    {
        $result = JapaneseTextParser::splitJapaneseSentences("   \t  ");

        $this->assertCount(1, $result);
        $this->assertSame('', $result[0]);
    }

    // =========================================================================
    // parseJapanese (split-only mode id=-2)
    // =========================================================================

    #[Test]
    public function parseJapaneseWithIdMinus2ReturnsArrayOfSentences(): void
    {
        $result = JapaneseTextParser::parseJapanese('Some text', -2);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function parseJapaneseWithIdMinus2MatchesSplitMethod(): void
    {
        $text = "Line A\nLine B";
        $splitResult = JapaneseTextParser::splitJapaneseSentences($text);
        $parseResult = JapaneseTextParser::parseJapanese($text, -2);

        $this->assertSame($splitResult, $parseResult);
    }

    #[Test]
    public function parseJapaneseWithIdMinus2DoesNotReturnNull(): void
    {
        $result = JapaneseTextParser::parseJapanese('test', -2);

        $this->assertNotNull($result);
    }

    // =========================================================================
    // displayJapanesePreview (output tests)
    // =========================================================================

    #[Test]
    public function displayJapanesePreviewOutputsCheckTextDiv(): void
    {
        ob_start();
        JapaneseTextParser::displayJapanesePreview('Test text');
        $output = ob_get_clean();

        $this->assertStringContainsString('<div id="check_text"', $output);
        $this->assertStringContainsString('<h2>Text</h2>', $output);
        $this->assertStringContainsString('Test text', $output);
    }

    #[Test]
    public function displayJapanesePreviewEscapesHtmlEntities(): void
    {
        ob_start();
        JapaneseTextParser::displayJapanesePreview('<script>alert("xss")</script>');
        $output = ob_get_clean();

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    #[Test]
    public function displayJapanesePreviewReplacesNewlinesWithBrTags(): void
    {
        ob_start();
        JapaneseTextParser::displayJapanesePreview("Line one\nLine two");
        $output = ob_get_clean();

        $this->assertStringContainsString('<br /><br />', $output);
    }

    #[Test]
    public function displayJapanesePreviewCollapsesWhitespace(): void
    {
        ob_start();
        JapaneseTextParser::displayJapanesePreview("Word1   \t   Word2");
        $output = ob_get_clean();

        $this->assertStringContainsString('Word1 Word2', $output);
    }

    // =========================================================================
    // parseJapanese / parseJapaneseToDatabase (requires MeCab + DB)
    // =========================================================================

    #[Test]
    public function parseJapaneseWithIdMinus1RequiresMecab(): void
    {
        $this->markTestSkipped(
            'MeCab installation required for display preview mode'
        );
    }

    #[Test]
    public function parseJapaneseWithPositiveIdRequiresMecabAndDatabase(): void
    {
        $this->markTestSkipped(
            'MeCab installation and database connection required for save mode'
        );
    }

    #[Test]
    public function parseJapaneseToDatabaseRequiresMecabAndDatabase(): void
    {
        $this->markTestSkipped(
            'MeCab installation and database connection required'
        );
    }
}
