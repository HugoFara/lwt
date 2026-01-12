<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\UseCases;

use Lwt\Modules\Text\Application\UseCases\ParseText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ParseText use case.
 *
 * Tests text parsing preview and validation including sentence/word counts,
 * text length validation, and term sentence linking.
 *
 * @covers \Lwt\Modules\Text\Application\UseCases\ParseText
 */
class ParseTextUseCaseTest extends TestCase
{
    private ParseText $parseText;

    protected function setUp(): void
    {
        $this->parseText = new ParseText();
    }

    // =====================
    // TEXT LENGTH VALIDATION TESTS
    // =====================

    public function testValidateTextLengthReturnsTrueForValidLength(): void
    {
        $text = str_repeat('a', 65000);
        $this->assertTrue($this->parseText->validateTextLength($text));
    }

    public function testValidateTextLengthReturnsFalseForTooLongText(): void
    {
        $text = str_repeat('a', 65001);
        $this->assertFalse($this->parseText->validateTextLength($text));
    }

    public function testValidateTextLengthReturnsTrueForEmptyText(): void
    {
        $this->assertTrue($this->parseText->validateTextLength(''));
    }

    public function testValidateTextLengthHandlesMultibyteCharacters(): void
    {
        // UTF-8: Each Japanese character is 3 bytes
        // 21000 characters * 3 bytes = 63000 bytes (within limit)
        $text = str_repeat('日', 21000);
        $this->assertTrue($this->parseText->validateTextLength($text));
    }

    public function testValidateTextLengthAtExactBoundary(): void
    {
        $text = str_repeat('a', 65000);
        $this->assertTrue($this->parseText->validateTextLength($text));

        $text = str_repeat('a', 65000) . 'b';
        $this->assertFalse($this->parseText->validateTextLength($text));
    }

    /**
     * @dataProvider textLengthProvider
     */
    public function testValidateTextLengthWithVariousLengths(int $length, bool $expected): void
    {
        $text = str_repeat('x', $length);
        $this->assertEquals($expected, $this->parseText->validateTextLength($text));
    }

    public static function textLengthProvider(): array
    {
        return [
            'empty' => [0, true],
            'short' => [100, true],
            'medium' => [10000, true],
            'at_limit' => [65000, true],
            'over_limit' => [65001, false],
            'way_over_limit' => [100000, false],
        ];
    }

    // =====================
    // TEXT LENGTH INFO TESTS
    // =====================

    public function testGetTextLengthInfoReturnsCorrectBytes(): void
    {
        $text = 'Hello World';

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(11, $info['bytes']);
    }

    public function testGetTextLengthInfoReturnsCorrectCharacters(): void
    {
        $text = 'Hello World';

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(11, $info['characters']);
    }

    public function testGetTextLengthInfoReturnsCorrectWords(): void
    {
        $text = 'Hello World Test';

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(3, $info['words']);
    }

    public function testGetTextLengthInfoReturnsValidFlag(): void
    {
        $shortText = 'Valid text';
        $longText = str_repeat('a', 65001);

        $this->assertTrue($this->parseText->getTextLengthInfo($shortText)['valid']);
        $this->assertFalse($this->parseText->getTextLengthInfo($longText)['valid']);
    }

    public function testGetTextLengthInfoHandlesMultibyteCharacters(): void
    {
        // UTF-8: Each Japanese character is 3 bytes
        $text = '日本語'; // 3 characters, 9 bytes

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(9, $info['bytes']);
        $this->assertEquals(3, $info['characters']);
    }

    public function testGetTextLengthInfoHandlesEmptyText(): void
    {
        $info = $this->parseText->getTextLengthInfo('');

        $this->assertEquals(0, $info['bytes']);
        $this->assertEquals(0, $info['characters']);
        $this->assertEquals(0, $info['words']);
        $this->assertTrue($info['valid']);
    }

    public function testGetTextLengthInfoHandlesMixedContent(): void
    {
        $text = "Hello 世界! This is a test.";

        $info = $this->parseText->getTextLengthInfo($text);

        // bytes: "Hello " = 6, "世界" = 6, "! This is a test." = 17 = 29
        $this->assertIsInt($info['bytes']);
        $this->assertGreaterThan(0, $info['bytes']);
        $this->assertIsInt($info['characters']);
        $this->assertTrue($info['valid']);
    }

    public function testGetTextLengthInfoHandlesNewlines(): void
    {
        $text = "Line 1\nLine 2\nLine 3";

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(20, $info['bytes']);
        $this->assertEquals(20, $info['characters']);
    }

    // =====================
    // SET TERM SENTENCES TESTS (Returns count without database access)
    // =====================

    public function testSetTermSentencesWithEmptyArrayReturnsZero(): void
    {
        $result = $this->parseText->setTermSentences([]);

        $this->assertEquals(0, $result);
    }

    // =====================
    // EDGE CASES TESTS
    // =====================

    public function testGetTextLengthInfoWithOnlyWhitespace(): void
    {
        $text = "   \n\t   ";

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(8, $info['bytes']);
        $this->assertEquals(8, $info['characters']);
        $this->assertEquals(0, $info['words']); // str_word_count counts only words
    }

    public function testGetTextLengthInfoWithSpecialCharacters(): void
    {
        $text = "Special !@#$%^&*()";

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(18, $info['bytes']);
        $this->assertEquals(18, $info['characters']);
    }

    public function testGetTextLengthInfoWithEmoji(): void
    {
        // Emoji are typically 4 bytes in UTF-8
        $text = "Hello 👋 World";

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertIsInt($info['bytes']);
        $this->assertGreaterThan(12, $info['bytes']); // At least 12 (chars) + emoji bytes
        $this->assertIsInt($info['characters']);
    }

    public function testGetTextLengthInfoWithRtlText(): void
    {
        // Arabic text (RTL)
        $text = "مرحبا بالعالم";

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertIsInt($info['bytes']);
        $this->assertGreaterThan(0, $info['bytes']);
        $this->assertEquals(13, $info['characters']); // 13 Arabic characters
        $this->assertTrue($info['valid']);
    }

    public function testGetTextLengthInfoWithCyrillicText(): void
    {
        // Russian text (Cyrillic)
        $text = "Привет мир";

        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals(10, $info['characters']); // 10 Cyrillic characters (including space)
        $this->assertIsInt($info['words']); // str_word_count doesn't count Cyrillic as words
        $this->assertTrue($info['valid']);
    }

    /**
     * @dataProvider textLengthInfoProvider
     */
    public function testGetTextLengthInfoWithVariousTexts(
        string $text,
        int $expectedChars,
        int $expectedWords
    ): void {
        $info = $this->parseText->getTextLengthInfo($text);

        $this->assertEquals($expectedChars, $info['characters']);
        $this->assertEquals($expectedWords, $info['words']);
    }

    public static function textLengthInfoProvider(): array
    {
        return [
            'simple' => ['Hello', 5, 1],
            'two_words' => ['Hello World', 11, 2],
            'with_punctuation' => ['Hello, World!', 13, 2],
            'with_numbers' => ['Test 123', 8, 1], // str_word_count doesn't count numbers
            'hyphenated' => ['well-known', 10, 1], // PHP str_word_count treats hyphenated as 1 word
            'contractions' => ["don't", 5, 1], // PHP str_word_count treats contractions as 1 word
        ];
    }
}
