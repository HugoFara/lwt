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
    // buildTokensFromMecab (pure MeCab-output -> tokens, no binary needed)
    // =========================================================================

    #[Test]
    public function buildTokensFromMecabProducesExpectedTokens(): void
    {
        // Captured MeCab output for "東京は大きいです。" in the parser's
        // "-F %m\t%t\t%h" format, terminated with the EOP sentence marker.
        $mecabed = "東京\t2\t46\nは\t6\t16\n大きい\t2\t10\n"
            . "です\t6\t25\n。\t3\t7\nEOP\t3\t7\n";

        $tokens = JapaneseTextParser::buildTokensFromMecab($mecabed);

        $this->assertCount(5, $tokens);
        $this->assertSame(
            ['東京', 'は', '大きい', 'です', '。'],
            array_map(fn($t) => $t->text, $tokens)
        );
        // Punctuation is a non-word; the rest are words.
        $this->assertSame([1, 1, 1, 1, 0], array_map(fn($t) => $t->wordCount, $tokens));
        // All in the first sentence (the trailing EOP order group is dropped).
        foreach ($tokens as $t) {
            $this->assertSame(1, $t->sentence);
        }
        // Global order is strictly increasing.
        $orders = array_map(fn($t) => $t->order, $tokens);
        $sorted = $orders;
        sort($sorted);
        $this->assertSame($sorted, $orders);
    }

    #[Test]
    public function buildTokensFromMecabHandlesEmptyOutput(): void
    {
        $this->assertSame([], JapaneseTextParser::buildTokensFromMecab(''));
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
}
