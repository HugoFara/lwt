<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\UseCases;

require_once __DIR__ . '/../../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Modules\Language\Domain\ValueObject\LanguageId;
use Lwt\Modules\Text\Application\UseCases\ImportText;
use Lwt\Modules\Text\Domain\Text;
use Lwt\Modules\Text\Domain\TextRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the ImportText use case.
 *
 * Tests the text import pipeline including text validation,
 * creation, and long text splitting.
 *
 * @covers \Lwt\Modules\Text\Application\UseCases\ImportText
 */
class ImportTextUseCaseTest extends TestCase
{
    /** @var TextRepositoryInterface&MockObject */
    private TextRepositoryInterface $textRepository;

    private ImportText $importText;

    protected function setUp(): void
    {
        $this->textRepository = $this->createMock(TextRepositoryInterface::class);
        $this->importText = new ImportText($this->textRepository);
    }

    // =====================
    // TEXT VALIDATION TESTS
    // =====================

    public function testValidateTextLengthReturnsTrueForValidLength(): void
    {
        $text = str_repeat('a', 65000);
        $this->assertTrue($this->importText->validateTextLength($text));
    }

    public function testValidateTextLengthReturnsFalseForTooLongText(): void
    {
        $text = str_repeat('a', 65001);
        $this->assertFalse($this->importText->validateTextLength($text));
    }

    public function testValidateTextLengthReturnsTrueForEmptyText(): void
    {
        $this->assertTrue($this->importText->validateTextLength(''));
    }

    public function testValidateTextLengthHandlesMultibyteCharacters(): void
    {
        // UTF-8: Each character can be 1-4 bytes
        // 日本 = 6 bytes (2 characters * 3 bytes each)
        $text = str_repeat('日本', 10000); // 60000 bytes
        $this->assertTrue($this->importText->validateTextLength($text));
    }

    // ========================
    // LONG TEXT SPLITTING TESTS
    // ========================

    public function testSplitLongTextReturnsSingleChunkForShortText(): void
    {
        $text = "This is a short paragraph.\n\nAnother paragraph.";

        $chunks = $this->importText->splitLongText($text, 1000);

        $this->assertCount(1, $chunks);
        $this->assertEquals('', $chunks[0]['title']);
        $this->assertStringContainsString('This is a short', $chunks[0]['text']);
    }

    public function testSplitLongTextCreatesMultipleChunks(): void
    {
        $paragraph = str_repeat('word ', 100); // ~500 chars per paragraph
        $text = "$paragraph\n\n$paragraph\n\n$paragraph";

        $chunks = $this->importText->splitLongText($text, 600);

        $this->assertGreaterThan(1, count($chunks));
        $this->assertEquals('Part 1', $chunks[0]['title']);
        $this->assertEquals('Part 2', $chunks[1]['title']);
    }

    public function testSplitLongTextPreservesParagraphBoundaries(): void
    {
        $para1 = "First paragraph content here.";
        $para2 = "Second paragraph content here.";
        $para3 = "Third paragraph content here.";
        $text = "$para1\n\n$para2\n\n$para3";

        // Set max length to force split between paragraphs
        $chunks = $this->importText->splitLongText($text, 40);

        // Each chunk should contain complete sentences (no partial words)
        foreach ($chunks as $chunk) {
            // Text should end with a complete word (letter or punctuation)
            $this->assertMatchesRegularExpression('/[a-zA-Z\.\!\?]$/', $chunk['text']);
            // Text should start with a capital letter or word character
            $this->assertMatchesRegularExpression('/^[A-Z]/', $chunk['text']);
        }
    }

    public function testSplitLongTextHandlesEmptyParagraphs(): void
    {
        $text = "Content here.\n\n\n\n\n\nMore content.";

        $chunks = $this->importText->splitLongText($text, 1000);

        // Empty paragraphs should be ignored
        $this->assertCount(1, $chunks);
        $this->assertStringContainsString('Content here', $chunks[0]['text']);
        $this->assertStringContainsString('More content', $chunks[0]['text']);
    }

    public function testSplitLongTextHandlesSinglePartCorrectly(): void
    {
        $text = "Single paragraph that fits.";

        $chunks = $this->importText->splitLongText($text, 1000);

        $this->assertCount(1, $chunks);
        // Single part should have empty title (no "Part 1")
        $this->assertEquals('', $chunks[0]['title']);
    }

    // ============================
    // PREPARE LONG TEXT DATA TESTS
    // ============================

    public function testPrepareLongTextDataFromPastedText(): void
    {
        $text = "Line 1\r\nLine 2\rLine 3\n\n\n\nLine 4";

        $result = $this->importText->prepareLongTextData($text, null);

        // Should normalize line endings and collapse multiple newlines
        $this->assertStringNotContainsString("\r", $result);
        $this->assertStringNotContainsString("\n\n\n", $result);
    }

    public function testPrepareLongTextDataReturnsNullForEmptyInput(): void
    {
        $result = $this->importText->prepareLongTextData(null, null);
        $this->assertNull($result);
    }

    public function testPrepareLongTextDataReturnsNullForWhitespaceOnly(): void
    {
        $result = $this->importText->prepareLongTextData('   ', null);
        $this->assertNull($result);
    }

    public function testPrepareLongTextDataTrimsResult(): void
    {
        $text = "  \n\nContent here\n\n  ";

        $result = $this->importText->prepareLongTextData($text, null);

        $this->assertEquals('Content here', $result);
    }

    public function testPrepareLongTextDataPrefersPastedTextOverNull(): void
    {
        $pastedText = "Pasted content";

        $result = $this->importText->prepareLongTextData($pastedText, null);

        $this->assertEquals($pastedText, $result);
    }

    // ========================
    // SOFT HYPHEN REMOVAL TESTS
    // ========================

    public function testSplitLongTextHandlesTextWithSoftHyphens(): void
    {
        // Soft hyphens (U+00AD) are invisible characters used for line-break hints
        $textWithSoftHyphen = "con\xC2\xADtent with soft\xC2\xADhyphen"; // Unicode soft hyphen (U+00AD)

        // splitLongText should handle text with soft hyphens without crashing
        $chunks = $this->importText->splitLongText($textWithSoftHyphen, 1000);

        // The text should be processed successfully
        $this->assertNotEmpty($chunks);
        $this->assertCount(1, $chunks);
        // The chunk should contain the text (soft hyphens may or may not be preserved)
        $this->assertNotEmpty($chunks[0]['text']);
    }

    // ========================
    // TEXT LENGTH BOUNDARY TESTS
    // ========================

    public function testValidateTextLengthAtExactBoundary(): void
    {
        $text = str_repeat('a', 65000);
        $this->assertTrue($this->importText->validateTextLength($text));

        $text = str_repeat('a', 65000) . 'b';
        $this->assertFalse($this->importText->validateTextLength($text));
    }

    // ========================
    // SPLIT EDGE CASES
    // ========================

    public function testSplitLongTextWithVeryLongSingleParagraph(): void
    {
        // Single paragraph that exceeds max length
        $text = str_repeat('word ', 500); // ~2500 chars

        $chunks = $this->importText->splitLongText($text, 1000);

        // Should handle gracefully - paragraph will be in its own chunk
        $this->assertGreaterThanOrEqual(1, count($chunks));
    }

    public function testSplitLongTextWithEmptyText(): void
    {
        $chunks = $this->importText->splitLongText('', 1000);
        $this->assertEmpty($chunks);
    }

    public function testSplitLongTextWithOnlyWhitespace(): void
    {
        $chunks = $this->importText->splitLongText("   \n\n  \n\n  ", 1000);
        $this->assertEmpty($chunks);
    }

    public function testSplitLongTextWithUnicodeContent(): void
    {
        $para1 = "日本語のテキストです。";
        $para2 = "これは二番目の段落です。";
        $text = "$para1\n\n$para2";

        $chunks = $this->importText->splitLongText($text, 100);

        // Should handle Unicode correctly
        $this->assertGreaterThanOrEqual(1, count($chunks));
        foreach ($chunks as $chunk) {
            $this->assertIsString($chunk['text']);
        }
    }

    public function testSplitLongTextMaintainsPartNumbering(): void
    {
        $paragraph = str_repeat('word ', 50);
        $text = implode("\n\n", array_fill(0, 10, $paragraph));

        $chunks = $this->importText->splitLongText($text, 300);

        // Check part numbering
        for ($i = 0; $i < count($chunks); $i++) {
            if (count($chunks) === 1) {
                $this->assertEquals('', $chunks[$i]['title']);
            } else {
                $this->assertEquals('Part ' . ($i + 1), $chunks[$i]['title']);
            }
        }
    }

    // ================================
    // DATA PROVIDER FOR BOUNDARY TESTS
    // ================================

    /**
     * @dataProvider textLengthProvider
     */
    public function testValidateTextLengthWithVariousLengths(int $length, bool $expected): void
    {
        $text = str_repeat('x', $length);
        $this->assertEquals($expected, $this->importText->validateTextLength($text));
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

    /**
     * @dataProvider lineEndingProvider
     */
    public function testPrepareLongTextDataNormalizesLineEndings(string $input, string $expected): void
    {
        $result = $this->importText->prepareLongTextData($input, null);
        $this->assertEquals($expected, $result);
    }

    public static function lineEndingProvider(): array
    {
        return [
            'windows_crlf' => ["line1\r\nline2", "line1\nline2"],
            'old_mac_cr' => ["line1\rline2", "line1\nline2"],
            'unix_lf' => ["line1\nline2", "line1\nline2"],
            'mixed' => ["a\r\nb\rc\nd", "a\nb\nc\nd"],
            'triple_newlines_collapsed' => ["a\n\n\n\nb", "a\n\nb"],
        ];
    }
}
