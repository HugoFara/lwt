<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Services;

use Lwt\Modules\Vocabulary\Application\Services\ExportService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ExportService class.
 *
 * Tests export functionality including text normalization, term masking,
 * and various export formats (Anki, TSV, Flexible).
 *
 * @covers \Lwt\Modules\Vocabulary\Application\Services\ExportService
 */
class ExportServiceTest extends TestCase
{
    private ExportService $exportService;

    protected function setUp(): void
    {
        $this->exportService = new ExportService();
    }

    // ====================================
    // replaceTabNewline() Tests
    // ====================================

    public function testReplaceTabNewlineRemovesTabs(): void
    {
        $input = "word1\tword2\tword3";
        $result = ExportService::replaceTabNewline($input);

        $this->assertStringNotContainsString("\t", $result);
        $this->assertEquals('word1 word2 word3', $result);
    }

    public function testReplaceTabNewlineRemovesNewlines(): void
    {
        $input = "line1\nline2\nline3";
        $result = ExportService::replaceTabNewline($input);

        $this->assertStringNotContainsString("\n", $result);
        $this->assertEquals('line1 line2 line3', $result);
    }

    public function testReplaceTabNewlineRemovesCarriageReturns(): void
    {
        $input = "line1\rline2\r\nline3";
        $result = ExportService::replaceTabNewline($input);

        $this->assertStringNotContainsString("\r", $result);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function testReplaceTabNewlineCollapsesMultipleSpaces(): void
    {
        $input = "word1    word2     word3";
        $result = ExportService::replaceTabNewline($input);

        $this->assertStringNotContainsString('  ', $result);
        $this->assertEquals('word1 word2 word3', $result);
    }

    public function testReplaceTabNewlineTrimsResult(): void
    {
        $input = "  word  ";
        $result = ExportService::replaceTabNewline($input);

        $this->assertEquals('word', $result);
    }

    public function testReplaceTabNewlineHandlesEmptyString(): void
    {
        $result = ExportService::replaceTabNewline('');
        $this->assertEquals('', $result);
    }

    public function testReplaceTabNewlineHandlesUnicode(): void
    {
        $input = "日本語\tテキスト\n文章";
        $result = ExportService::replaceTabNewline($input);

        $this->assertEquals('日本語 テキスト 文章', $result);
    }

    public function testReplaceTabNewlineHandlesNonBreakingSpace(): void
    {
        $input = "word1\xC2\xA0word2"; // Non-breaking space (UTF-8)
        $result = ExportService::replaceTabNewline($input);

        // Non-breaking space should be converted to regular space
        $this->assertEquals('word1 word2', $result);
    }

    // ====================================
    // maskTermInSentence() Tests
    // ====================================

    public function testMaskTermInSentenceBasicMasking(): void
    {
        $sentence = "This is a {test} sentence.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // 'test' has 4 letters, should be 4 bullets
        $this->assertEquals("This is a {••••} sentence.", $result);
    }

    public function testMaskTermInSentencePreservesPunctuation(): void
    {
        $sentence = "It's a {word's} possessive.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // Apostrophe should be preserved, letters masked
        $this->assertEquals("It's a {••••'•} possessive.", $result);
    }

    public function testMaskTermInSentencePreservesHyphen(): void
    {
        $sentence = "A {well-known} fact.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // Hyphen should be preserved
        $this->assertEquals("A {••••-•••••} fact.", $result);
    }

    public function testMaskTermInSentenceWithNoTermMarker(): void
    {
        $sentence = "No term markers here.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // No change expected
        $this->assertEquals($sentence, $result);
    }

    public function testMaskTermInSentenceWithEmptyBraces(): void
    {
        $sentence = "Empty {} braces.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("Empty {} braces.", $result);
    }

    public function testMaskTermInSentenceWithUnicode(): void
    {
        $sentence = "Japanese: {漢字} character.";
        $regexword = '一-龥'; // CJK character range

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("Japanese: {••} character.", $result);
    }

    public function testMaskTermInSentenceWithCyrillicRegex(): void
    {
        $sentence = "Russian {слово} here.";
        $regexword = 'а-яА-ЯёЁ';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("Russian {•••••} here.", $result);
    }

    public function testMaskTermInSentenceWithNumbersInTerm(): void
    {
        $sentence = "The {test123} value.";
        $regexword = 'a-zA-Z0-9';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("The {•••••••} value.", $result);
    }

    public function testMaskTermInSentenceOnlyMasksWithinBraces(): void
    {
        $sentence = "Outside {inside} outside.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // Only 'inside' should be masked, not 'Outside' or 'outside'
        $this->assertEquals("Outside {••••••} outside.", $result);
    }

    // ====================================
    // maskTermInSentenceV2() Tests
    // ====================================

    public function testMaskTermInSentenceV2ReplacesWithBrackets(): void
    {
        $sentence = "This is a {test} sentence.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("This is a [...] sentence.", $result);
    }

    public function testMaskTermInSentenceV2HandlesLongTerm(): void
    {
        $sentence = "A {verylongword} here.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("A [...] here.", $result);
    }

    public function testMaskTermInSentenceV2HandlesMultipleBraces(): void
    {
        // Note: In practice sentences usually have one term marked
        $sentence = "First {word} and second {term}.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("First [...] and second [...].", $result);
    }

    public function testMaskTermInSentenceV2HandlesUnicode(): void
    {
        $sentence = "Japanese: {日本語} text.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("Japanese: [...] text.", $result);
    }

    public function testMaskTermInSentenceV2WithNoBraces(): void
    {
        $sentence = "No braces here.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals($sentence, $result);
    }

    public function testMaskTermInSentenceV2WithEmptyBraces(): void
    {
        $sentence = "Empty {} braces.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("Empty [...] braces.", $result);
    }

    // ====================================
    // Edge Cases Tests
    // ====================================

    public function testMaskTermHandlesNestedSpecialChars(): void
    {
        $sentence = "Term with {test!@#} special.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // Only letters masked, special chars preserved
        $this->assertEquals("Term with {••••!@#} special.", $result);
    }

    public function testMaskTermAtSentenceStart(): void
    {
        $sentence = "{Word} at start.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("{••••} at start.", $result);
    }

    public function testMaskTermAtSentenceEnd(): void
    {
        $sentence = "End with {term}.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("End with {••••}.", $result);
    }

    public function testMaskTermWithOnlyTerm(): void
    {
        $sentence = "{alone}";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("{•••••}", $result);
    }

    // ====================================
    // Whitespace Normalization Data Provider Tests
    // ====================================

    /**
     * @dataProvider whitespaceInputProvider
     */
    public function testReplaceTabNewlineWithVariousInputs(string $input, string $expected): void
    {
        $result = ExportService::replaceTabNewline($input);
        $this->assertEquals($expected, $result);
    }

    public static function whitespaceInputProvider(): array
    {
        return [
            'single_tab' => ["a\tb", 'a b'],
            'single_newline' => ["a\nb", 'a b'],
            'crlf' => ["a\r\nb", 'a b'],
            'cr_only' => ["a\rb", 'a b'],
            'multiple_tabs' => ["a\t\t\tb", 'a b'],
            'tab_and_space' => ["a\t b", 'a b'],
            'leading_whitespace' => ["\t\n  text", 'text'],
            'trailing_whitespace' => ["text  \t\n", 'text'],
            'only_whitespace' => ["\t\n  \r\n", ''],
            'mixed_whitespace' => ["a \t\n b  c", 'a b c'],
        ];
    }

    /**
     * @dataProvider termMaskingProvider
     */
    public function testMaskTermInSentenceWithVariousTerms(
        string $sentence,
        string $regex,
        string $expected
    ): void {
        $result = ExportService::maskTermInSentence($sentence, $regex);
        $this->assertEquals($expected, $result);
    }

    public static function termMaskingProvider(): array
    {
        return [
            'simple_word' => [
                'The {cat} sat.',
                'a-zA-Z',
                'The {•••} sat.'
            ],
            'long_word' => [
                'A {supercalifragilistic} term.',
                'a-zA-Z',
                'A {••••••••••••••••••••} term.'
            ],
            'single_char' => [
                'Letter {a} here.',
                'a-zA-Z',
                'Letter {•} here.'
            ],
            'digits_preserved' => [
                'Code {test123} here.',
                'a-zA-Z', // digits not in regex
                'Code {••••123} here.'
            ],
            'all_digits' => [
                'Number {12345} value.',
                '0-9',
                'Number {•••••} value.'
            ],
        ];
    }

    // ====================================
    // MECAB Regex Pattern Test
    // ====================================

    public function testMaskTermWithMecabPattern(): void
    {
        // MECAB uses Japanese character range
        $sentence = "日本語の{単語}です。";
        $mecabRegex = '一-龥ぁ-ヾ';

        $result = ExportService::maskTermInSentence($sentence, $mecabRegex);

        // The two kanji in 単語 should be masked
        $this->assertEquals("日本語の{••}です。", $result);
    }

    // ====================================
    // RTL Language Considerations
    // ====================================

    public function testMaskTermWithArabicText(): void
    {
        $sentence = "Arabic: {مرحبا} word.";
        $arabicRegex = '\x{0600}-\x{06FF}'; // Arabic Unicode range

        // Note: The regex might need adjustment for actual Arabic handling
        // This test documents expected behavior
        $result = ExportService::maskTermInSentence($sentence, $arabicRegex);

        // Arabic characters should be masked
        $this->assertStringContainsString('•', $result);
    }

    public function testMaskTermWithHebrewText(): void
    {
        $sentence = "Hebrew: {שלום} word.";
        $hebrewRegex = '\x{0590}-\x{05FF}'; // Hebrew Unicode range

        $result = ExportService::maskTermInSentence($sentence, $hebrewRegex);

        // Hebrew characters should be masked
        $this->assertStringContainsString('•', $result);
    }

    // ====================================
    // Empty and Boundary Cases
    // ====================================

    public function testMaskTermWithEmptyRegex(): void
    {
        $sentence = "Test {word} here.";

        // Empty regex - nothing should match
        $result = ExportService::maskTermInSentence($sentence, '');

        // When regex is empty, no characters match so none are masked
        // The braces and content remain, but nothing is replaced with bullets
        $this->assertEquals("Test {word} here.", $result);
    }

    public function testReplaceTabNewlineWithVeryLongString(): void
    {
        $input = str_repeat("word\t", 1000);
        $result = ExportService::replaceTabNewline($input);

        $this->assertStringNotContainsString("\t", $result);
        $this->assertIsString($result);
    }
}
