<?php

declare(strict_types=1);

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

    // ====================================
    // Additional Edge Cases for replaceTabNewline()
    // ====================================

    public function testReplaceTabNewlineWithVerticalTab(): void
    {
        $input = "word1\x0Bword2"; // Vertical tab
        $result = ExportService::replaceTabNewline($input);

        // Vertical tab is whitespace and should be converted
        $this->assertEquals('word1 word2', $result);
    }

    public function testReplaceTabNewlineWithFormFeed(): void
    {
        $input = "word1\x0Cword2"; // Form feed
        $result = ExportService::replaceTabNewline($input);

        $this->assertEquals('word1 word2', $result);
    }

    public function testReplaceTabNewlineWithMixedLineEndings(): void
    {
        $input = "line1\nline2\r\nline3\rline4";
        $result = ExportService::replaceTabNewline($input);

        $this->assertEquals('line1 line2 line3 line4', $result);
    }

    public function testReplaceTabNewlineWithOnlySpaces(): void
    {
        $input = '     ';
        $result = ExportService::replaceTabNewline($input);

        $this->assertEquals('', $result);
    }

    public function testReplaceTabNewlinePreservesWords(): void
    {
        $input = "  first   second  \t third \n fourth  ";
        $result = ExportService::replaceTabNewline($input);

        $this->assertEquals('first second third fourth', $result);
    }

    public function testReplaceTabNewlineWithZeroWidthSpaces(): void
    {
        // Zero-width space U+200B is NOT regular whitespace
        // The \s pattern in preg_replace matches: space, tab, newline, carriage return, form feed
        // Zero-width space is a different Unicode category and is preserved
        $input = "word1\xE2\x80\x8Bword2";
        $result = ExportService::replaceTabNewline($input);

        // Zero-width space is NOT converted - it's preserved as-is
        $this->assertEquals("word1\xE2\x80\x8Bword2", $result);
    }

    // ====================================
    // Additional maskTermInSentence() Tests
    // ====================================

    public function testMaskTermWithMultipleTermsInSentence(): void
    {
        $sentence = "First {term1} and second {term2}.";
        $regexword = 'a-zA-Z0-9';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("First {•••••} and second {•••••}.", $result);
    }

    public function testMaskTermWithAdjacentBraces(): void
    {
        $sentence = "Words {first}{second} together.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("Words {•••••}{••••••} together.", $result);
    }

    public function testMaskTermWithUnmatchedOpenBrace(): void
    {
        $sentence = "Open {brace without close";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // Everything after { should be masked
        $this->assertStringContainsString('•', $result);
    }

    public function testMaskTermWithUnmatchedCloseBrace(): void
    {
        $sentence = "Close brace} without open";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // No masking should occur before the close brace
        $this->assertEquals("Close brace} without open", $result);
    }

    public function testMaskTermWithSpacesInTerm(): void
    {
        $sentence = "A {multi word expression} here.";
        $regexword = 'a-zA-Z';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        // Spaces preserved, letters masked
        $this->assertEquals("A {••••• •••• ••••••••••} here.", $result);
    }

    public function testMaskTermWithAccentedCharacters(): void
    {
        $sentence = "French: {café} word.";
        $regexword = 'a-zA-ZÀ-ÿ';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("French: {••••} word.", $result);
    }

    public function testMaskTermWithGreekCharacters(): void
    {
        $sentence = "Greek: {λόγος} word.";
        $regexword = 'α-ωΑ-Ωά-ώ';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertStringContainsString('•', $result);
    }

    public function testMaskTermWithDigitsOnly(): void
    {
        $sentence = "Number {123} here.";
        $regexword = '0-9';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertEquals("Number {•••} here.", $result);
    }

    public function testMaskTermWithSpecialRegexChars(): void
    {
        $sentence = "Test {word} here.";
        $regexword = 'a-z\\-';

        $result = ExportService::maskTermInSentence($sentence, $regexword);

        $this->assertIsString($result);
    }

    // ====================================
    // Additional maskTermInSentenceV2() Tests
    // ====================================

    public function testMaskTermV2WithTermAtStart(): void
    {
        $sentence = "{Word} at start.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("[...] at start.", $result);
    }

    public function testMaskTermV2WithTermAtEnd(): void
    {
        $sentence = "End with {term}";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("End with [...]", $result);
    }

    public function testMaskTermV2OnlyTerm(): void
    {
        $sentence = "{alone}";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("[...]", $result);
    }

    public function testMaskTermV2WithNestedPunctuation(): void
    {
        $sentence = "Text {a-b-c!} more.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("Text [...] more.", $result);
    }

    public function testMaskTermV2WithVeryLongTerm(): void
    {
        $longTerm = str_repeat('a', 100);
        $sentence = "Test {{$longTerm}} end.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertEquals("Test [...] end.", $result);
    }

    public function testMaskTermV2PreservesRtlMarkers(): void
    {
        // RTL markers should be preserved outside braces
        $sentence = "Hebrew: \u{200F}{שלום}\u{200F} word.";

        $result = ExportService::maskTermInSentenceV2($sentence);

        $this->assertStringContainsString('[...]', $result);
        $this->assertStringContainsString("\u{200F}", $result);
    }

    // ====================================
    // Private Method Tests via Reflection
    // ====================================

    public function testFormatAnkiRowViaReflection(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatAnkiRow');
        $method->setAccessible(true);

        $record = [
            'LgRegexpWordCharacters' => 'a-zA-Z',
            'LgRightToLeft' => 0,
            'WoSentence' => 'This is a {test} sentence.',
            'WoText' => 'test',
            'WoTranslation' => 'prueba',
            'WoRomanization' => '',
            'LgName' => 'English',
            'WoID' => '123',
            'taglist' => 'tag1, tag2',
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('prueba', $result);
        $this->assertStringContainsString('English', $result);
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString("\t", $result);
        $this->assertStringEndsWith("\r\n", $result);
    }

    public function testFormatAnkiRowWithRtlLanguage(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatAnkiRow');
        $method->setAccessible(true);

        $record = [
            'LgRegexpWordCharacters' => '\x{0600}-\x{06FF}',
            'LgRightToLeft' => 1,
            'WoSentence' => 'This is a {مرحبا} sentence.',
            'WoText' => 'مرحبا',
            'WoTranslation' => 'hello',
            'WoRomanization' => 'marhaba',
            'LgName' => 'Arabic',
            'WoID' => '456',
            'taglist' => '',
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertStringContainsString('<span dir="rtl">', $result);
        $this->assertStringContainsString('</span>', $result);
        $this->assertStringContainsString(']', $result); // RTL brackets reversed
    }

    public function testFormatAnkiRowWithMecabRegex(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatAnkiRow');
        $method->setAccessible(true);

        $record = [
            'LgRegexpWordCharacters' => 'MECAB',
            'LgRightToLeft' => 0,
            'WoSentence' => '日本語の{単語}です。',
            'WoText' => '単語',
            'WoTranslation' => 'word',
            'WoRomanization' => 'tango',
            'LgName' => 'Japanese',
            'WoID' => '789',
            'taglist' => 'japanese',
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertStringContainsString('単語', $result);
        $this->assertStringContainsString('word', $result);
        $this->assertStringContainsString('•', $result); // Masked characters
    }

    public function testFormatTsvRowViaReflection(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatTsvRow');
        $method->setAccessible(true);

        $record = [
            'WoText' => "test\tword",
            'WoTranslation' => "prueba\nnewline",
            'WoSentence' => 'This is a {test} sentence.',
            'WoRomanization' => '',
            'WoStatus' => '2',
            'LgName' => 'English',
            'WoID' => '123',
            'taglist' => 'tag1',
        ];

        $result = $method->invoke($this->exportService, $record);

        // Tab and newline in WoText/WoTranslation should be converted to spaces
        $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $result));
        $this->assertStringContainsString('test word', $result);
        $this->assertStringContainsString('prueba newline', $result);
        $this->assertStringContainsString('123', $result);
        $this->assertStringEndsWith("\r\n", $result);
    }

    public function testFormatTsvRowWithMissingFields(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatTsvRow');
        $method->setAccessible(true);

        $record = [
            'WoText' => 'test',
            'WoTranslation' => 'prueba',
            'WoSentence' => '',
            'WoRomanization' => '',
            'LgName' => 'English',
            // Missing WoStatus, WoID, taglist
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertIsString($result);
        $this->assertStringContainsString('test', $result);
    }

    public function testFormatFlexibleRowViaReflection(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '%w\t%t\t%s\n',
            'WoID' => '123',
            'LgName' => 'English',
            'LgRightToLeft' => 0,
            'WoText' => 'test',
            'WoTextLC' => 'test',
            'WoTranslation' => 'prueba',
            'WoRomanization' => '',
            'WoSentence' => 'A {test} here.',
            'WoStatus' => '2',
            'taglist' => 'tag1',
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('prueba', $result);
        $this->assertStringContainsString("\t", $result);
        $this->assertStringContainsString("\n", $result);
    }

    public function testFormatFlexibleRowWithHtmlPlaceholders(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '$w\t$t',
            'WoID' => '123',
            'LgName' => 'English',
            'LgRightToLeft' => 0,
            'WoText' => '<script>alert(1)</script>',
            'WoTextLC' => '<script>alert(1)</script>',
            'WoTranslation' => '"quoted"',
            'WoRomanization' => '',
            'WoSentence' => 'A {test} here.',
            'WoStatus' => '2',
            'taglist' => '',
        ];

        $result = $method->invoke($this->exportService, $record);

        // HTML should be escaped
        $this->assertStringContainsString('&lt;script&gt;', $result);
        $this->assertStringContainsString('&quot;quoted&quot;', $result);
    }

    public function testFormatFlexibleRowWithEscapeSequences(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '%w\\t%t\\n%r\\r%%',
            'WoID' => '123',
            'LgName' => 'English',
            'LgRightToLeft' => 0,
            'WoText' => 'test',
            'WoTextLC' => 'test',
            'WoTranslation' => 'prueba',
            'WoRomanization' => 'romanized',
            'WoSentence' => 'A {test} here.',
            'WoStatus' => '2',
            'taglist' => '',
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertStringContainsString("\t", $result);
        $this->assertStringContainsString("\n", $result);
        $this->assertStringContainsString("\r", $result);
        $this->assertStringContainsString('%', $result);
    }

    public function testFormatFlexibleRowWithMissingTemplate(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'WoID' => '123',
            'LgName' => 'English',
            // Missing LgExportTemplate
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertEquals('', $result);
    }

    public function testFormatFlexibleRowClozeFormats(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '$x\t$y',
            'WoID' => '123',
            'LgName' => 'English',
            'LgRightToLeft' => 0,
            'WoText' => 'test',
            'WoTextLC' => 'test',
            'WoTranslation' => 'prueba',
            'WoRomanization' => '',
            'WoSentence' => 'A {test} here.',
            'WoStatus' => '2',
            'taglist' => '',
        ];

        $result = $method->invoke($this->exportService, $record);

        // $x format: {{c1::term}}
        $this->assertStringContainsString('{{c1::', $result);
        $this->assertStringContainsString('}}', $result);
        // $y format includes translation hint
        $this->assertStringContainsString('prueba', $result);
    }

    public function testFormatFlexibleRowWithCMask(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '%c',
            'WoID' => '123',
            'LgName' => 'English',
            'LgRightToLeft' => 0,
            'WoText' => 'test',
            'WoTextLC' => 'test',
            'WoTranslation' => 'prueba',
            'WoRomanization' => '',
            'WoSentence' => 'A {test} here.',
            'WoStatus' => '2',
            'taglist' => '',
        ];

        $result = $method->invoke($this->exportService, $record);

        // %c uses maskTermInSentenceV2 - replaces with [...]
        $this->assertStringContainsString('[...]', $result);
    }

    public function testFormatFlexibleRowWithDMask(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '%d',
            'WoID' => '123',
            'LgName' => 'English',
            'LgRightToLeft' => 0,
            'WoText' => 'test',
            'WoTextLC' => 'test',
            'WoTranslation' => 'prueba',
            'WoRomanization' => '',
            'WoSentence' => 'A {test} here.',
            'WoStatus' => '2',
            'taglist' => '',
        ];

        $result = $method->invoke($this->exportService, $record);

        // %d replaces braces with brackets
        $this->assertStringContainsString('[test]', $result);
    }

    public function testFormatFlexibleRowWithRtlLanguage(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '$w\t$s',
            'WoID' => '123',
            'LgName' => 'Arabic',
            'LgRightToLeft' => 1,
            'WoText' => 'مرحبا',
            'WoTextLC' => 'مرحبا',
            'WoTranslation' => 'hello',
            'WoRomanization' => '',
            'WoSentence' => 'A {مرحبا} here.',
            'WoStatus' => '2',
            'taglist' => '',
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertStringContainsString('<span dir="rtl">', $result);
        $this->assertStringContainsString('</span>', $result);
    }

    public function testFormatFlexibleRowAllPlaceholders(): void
    {
        $method = new \ReflectionMethod(ExportService::class, 'formatFlexibleRow');
        $method->setAccessible(true);

        $record = [
            'LgExportTemplate' => '%w|%t|%s|%r|%a|%k|%z|%l|%n',
            'WoID' => '999',
            'LgName' => 'English',
            'LgRightToLeft' => 0,
            'WoText' => 'TEST',
            'WoTextLC' => 'test',
            'WoTranslation' => 'TRANSLATION',
            'WoRomanization' => 'ROMAN',
            'WoSentence' => 'A {test} sentence.',
            'WoStatus' => '3',
            'taglist' => 'tag1,tag2',
        ];

        $result = $method->invoke($this->exportService, $record);

        $this->assertStringContainsString('TEST', $result); // %w
        $this->assertStringContainsString('TRANSLATION', $result); // %t
        $this->assertStringContainsString('A test sentence.', $result); // %s (braces removed)
        $this->assertStringContainsString('ROMAN', $result); // %r
        $this->assertStringContainsString('3', $result); // %a
        $this->assertStringContainsString('test', $result); // %k
        $this->assertStringContainsString('tag1,tag2', $result); // %z
        $this->assertStringContainsString('English', $result); // %l
        $this->assertStringContainsString('999', $result); // %n
    }

    // ====================================
    // Data Provider for Complex Scenarios
    // ====================================

    /**
     * @dataProvider complexMaskingProvider
     */
    public function testMaskTermWithComplexPatterns(
        string $sentence,
        string $regex,
        string $expectedSubstring
    ): void {
        $result = ExportService::maskTermInSentence($sentence, $regex);
        $this->assertStringContainsString($expectedSubstring, $result);
    }

    public static function complexMaskingProvider(): array
    {
        return [
            'mixed_case_term' => [
                'The {MixedCase} word.',
                'a-zA-Z',
                '{•••••••••}',
            ],
            'term_with_number_prefix' => [
                'Code {2nd} place.',
                'a-zA-Z',
                '{2••}',
            ],
            'term_with_underscore' => [
                'Variable {var_name} here.',
                'a-zA-Z_',
                '{••••••••}',
            ],
            'emoji_preserved' => [
                'Happy {word😀} here.',
                'a-zA-Z',
                '😀',
            ],
        ];
    }
}
