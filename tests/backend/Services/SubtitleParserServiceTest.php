<?php declare(strict_types=1);

namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/Modules/Text/Application/Services/SubtitleParserService.php';

use Lwt\Modules\Text\Application\Services\SubtitleParserService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SubtitleParserService class.
 */
class SubtitleParserServiceTest extends TestCase
{
    private SubtitleParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubtitleParserService();
    }

    // =========================================================================
    // SRT Parsing Tests
    // =========================================================================

    public function testParseSrtBasic(): void
    {
        $srt = <<<SRT
1
00:00:01,000 --> 00:00:05,000
Hello, world!

2
00:00:05,500 --> 00:00:10,000
This is a test.
SRT;

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Hello, world!', $result['text']);
        $this->assertStringContainsString('This is a test.', $result['text']);
        $this->assertStringNotContainsString('00:00', $result['text']);
        $this->assertStringNotContainsString('-->', $result['text']);
        $this->assertEquals(2, $result['cueCount']);
    }

    public function testParseSrtMultiLine(): void
    {
        $srt = <<<SRT
1
00:00:01,000 --> 00:00:05,000
First line
Second line
Third line

2
00:00:06,000 --> 00:00:10,000
Another cue
SRT;

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('First line', $result['text']);
        $this->assertStringContainsString('Second line', $result['text']);
        $this->assertStringContainsString('Third line', $result['text']);
        $this->assertEquals(2, $result['cueCount']);
    }

    public function testParseSrtWithHtmlTags(): void
    {
        $srt = <<<SRT
1
00:00:01,000 --> 00:00:05,000
<i>Italic text</i>

2
00:00:06,000 --> 00:00:10,000
<b>Bold text</b>
SRT;

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Italic text', $result['text']);
        $this->assertStringContainsString('Bold text', $result['text']);
        // HTML tags should be stripped
        $this->assertStringNotContainsString('<i>', $result['text']);
        $this->assertStringNotContainsString('<b>', $result['text']);
    }

    public function testParseSrtWithSpecialCharacters(): void
    {
        $srt = <<<SRT
1
00:00:01,000 --> 00:00:05,000
Café résumé naïve

2
00:00:06,000 --> 00:00:10,000
日本語テキスト
SRT;

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Café résumé naïve', $result['text']);
        $this->assertStringContainsString('日本語テキスト', $result['text']);
    }

    public function testParseSrtStripsSequenceNumbers(): void
    {
        $srt = <<<SRT
1
00:00:01,000 --> 00:00:05,000
First cue

2
00:00:06,000 --> 00:00:10,000
Second cue

100
00:00:11,000 --> 00:00:15,000
Cue number 100
SRT;

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        // Sequence numbers should not appear as standalone numbers
        $lines = explode("\n", $result['text']);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Ensure no line is just a sequence number
            $this->assertFalse(
                preg_match('/^\d+$/', $trimmed) === 1 && (int) $trimmed <= 100,
                "Sequence number '$trimmed' should not appear in output"
            );
        }
    }

    // =========================================================================
    // VTT Parsing Tests
    // =========================================================================

    public function testParseVttBasic(): void
    {
        $vtt = <<<VTT
WEBVTT

00:00:01.000 --> 00:00:05.000
Hello, world!

00:00:05.500 --> 00:00:10.000
This is a test.
VTT;

        $result = $this->service->parse($vtt, 'vtt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Hello, world!', $result['text']);
        $this->assertStringContainsString('This is a test.', $result['text']);
        $this->assertStringNotContainsString('WEBVTT', $result['text']);
        $this->assertStringNotContainsString('-->', $result['text']);
        $this->assertEquals(2, $result['cueCount']);
    }

    public function testParseVttWithCueIdentifiers(): void
    {
        $vtt = <<<VTT
WEBVTT

cue-1
00:00:01.000 --> 00:00:05.000
First cue

cue-2
00:00:06.000 --> 00:00:10.000
Second cue
VTT;

        $result = $this->service->parse($vtt, 'vtt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('First cue', $result['text']);
        $this->assertStringContainsString('Second cue', $result['text']);
        // Cue identifiers should not appear
        $this->assertStringNotContainsString('cue-1', $result['text']);
        $this->assertStringNotContainsString('cue-2', $result['text']);
    }

    public function testParseVttWithStyling(): void
    {
        $vtt = <<<VTT
WEBVTT

00:00:01.000 --> 00:00:05.000
<b>Bold</b> and <i>italic</i> text

00:00:06.000 --> 00:00:10.000
<c.highlight>Highlighted</c> text

00:00:11.000 --> 00:00:15.000
<v Speaker>Dialogue text
VTT;

        $result = $this->service->parse($vtt, 'vtt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Bold', $result['text']);
        $this->assertStringContainsString('italic', $result['text']);
        $this->assertStringContainsString('Highlighted', $result['text']);
        $this->assertStringContainsString('Dialogue text', $result['text']);
        // Tags should be stripped
        $this->assertStringNotContainsString('<b>', $result['text']);
        $this->assertStringNotContainsString('<c.', $result['text']);
        $this->assertStringNotContainsString('<v ', $result['text']);
    }

    public function testParseVttSkipsNoteBlocks(): void
    {
        $vtt = <<<VTT
WEBVTT

NOTE
This is a comment that should be ignored.

00:00:01.000 --> 00:00:05.000
Actual subtitle text

NOTE Another comment

00:00:06.000 --> 00:00:10.000
More subtitle text
VTT;

        $result = $this->service->parse($vtt, 'vtt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Actual subtitle text', $result['text']);
        $this->assertStringContainsString('More subtitle text', $result['text']);
        $this->assertStringNotContainsString('comment', $result['text']);
        $this->assertStringNotContainsString('ignored', $result['text']);
    }

    public function testParseVttWithMetadata(): void
    {
        $vtt = <<<VTT
WEBVTT - Title of the video
Kind: captions
Language: en

00:00:01.000 --> 00:00:05.000
First subtitle
VTT;

        $result = $this->service->parse($vtt, 'vtt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('First subtitle', $result['text']);
        // Metadata should not appear
        $this->assertStringNotContainsString('Kind:', $result['text']);
        $this->assertStringNotContainsString('Language:', $result['text']);
    }

    // =========================================================================
    // Format Detection Tests
    // =========================================================================

    public function testDetectFormatFromSrtExtension(): void
    {
        $format = $this->service->detectFormat('movie.srt', 'any content');
        $this->assertEquals('srt', $format);
    }

    public function testDetectFormatFromVttExtension(): void
    {
        $format = $this->service->detectFormat('movie.vtt', 'any content');
        $this->assertEquals('vtt', $format);
    }

    public function testDetectFormatFromVttExtensionUppercase(): void
    {
        $format = $this->service->detectFormat('MOVIE.VTT', 'any content');
        $this->assertEquals('vtt', $format);
    }

    public function testDetectFormatFromWebvttHeader(): void
    {
        $vttContent = "WEBVTT\n\n00:00:01.000 --> 00:00:05.000\nText";
        $format = $this->service->detectFormat('unknown.txt', $vttContent);
        $this->assertEquals('vtt', $format);
    }

    public function testDetectFormatFromSrtPattern(): void
    {
        $srtContent = "1\n00:00:01,000 --> 00:00:05,000\nText";
        $format = $this->service->detectFormat('unknown.txt', $srtContent);
        $this->assertEquals('srt', $format);
    }

    public function testDetectFormatReturnsNullForUnknown(): void
    {
        $format = $this->service->detectFormat('readme.txt', 'Just some text content');
        $this->assertNull($format);
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    public function testIsValidSubtitleSrt(): void
    {
        $srtContent = "1\n00:00:01,000 --> 00:00:05,000\nText";
        $this->assertTrue($this->service->isValidSubtitle($srtContent, 'srt'));
    }

    public function testIsValidSubtitleVtt(): void
    {
        $vttContent = "WEBVTT\n\n00:00:01.000 --> 00:00:05.000\nText";
        $this->assertTrue($this->service->isValidSubtitle($vttContent, 'vtt'));
    }

    public function testIsValidSubtitleReturnsFalseForInvalidSrt(): void
    {
        $this->assertFalse($this->service->isValidSubtitle('Just plain text', 'srt'));
    }

    public function testIsValidSubtitleReturnsFalseForInvalidVtt(): void
    {
        // VTT without WEBVTT header
        $this->assertFalse($this->service->isValidSubtitle('00:00:01.000 --> 00:00:05.000\nText', 'vtt'));
    }

    public function testIsValidSubtitleReturnsFalseForEmpty(): void
    {
        $this->assertFalse($this->service->isValidSubtitle('', 'srt'));
        $this->assertFalse($this->service->isValidSubtitle('', 'vtt'));
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    public function testParseEmptyContent(): void
    {
        $result = $this->service->parse('', 'srt');

        $this->assertFalse($result['success']);
        $this->assertEquals('Subtitle file is empty', $result['error']);
    }

    public function testParseWhitespaceOnlyContent(): void
    {
        $result = $this->service->parse("   \n\n   ", 'srt');

        $this->assertFalse($result['success']);
        $this->assertEquals('Subtitle file is empty', $result['error']);
    }

    public function testParseUnsupportedFormat(): void
    {
        $result = $this->service->parse('Some content', 'ass');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unsupported format', $result['error']);
    }

    public function testParseNoTextContent(): void
    {
        $srt = <<<SRT
1
00:00:01,000 --> 00:00:05,000

2
00:00:06,000 --> 00:00:10,000

SRT;

        $result = $this->service->parse($srt, 'srt');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No text content', $result['error']);
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    public function testParseSrtWithWindowsLineEndings(): void
    {
        $srt = "1\r\n00:00:01,000 --> 00:00:05,000\r\nHello\r\n\r\n2\r\n00:00:06,000 --> 00:00:10,000\r\nWorld";

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Hello', $result['text']);
        $this->assertStringContainsString('World', $result['text']);
    }

    public function testParseSrtWithMixedLineEndings(): void
    {
        $srt = "1\r\n00:00:01,000 --> 00:00:05,000\nHello\r\n\n2\n00:00:06,000 --> 00:00:10,000\rWorld";

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Hello', $result['text']);
        $this->assertStringContainsString('World', $result['text']);
    }

    public function testParseSrtWithHtmlEntities(): void
    {
        $srt = <<<SRT
1
00:00:01,000 --> 00:00:05,000
&lt;Hello&gt; &amp; &quot;World&quot;
SRT;

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        // HTML entities should be decoded
        $this->assertStringContainsString('<Hello>', $result['text']);
        $this->assertStringContainsString('&', $result['text']);
        $this->assertStringContainsString('"World"', $result['text']);
    }

    public function testParseLargeFile(): void
    {
        // Generate a large SRT with many cues
        $srt = '';
        for ($i = 1; $i <= 500; $i++) {
            $start = sprintf('%02d:%02d:%02d,000', floor($i / 3600), floor(($i % 3600) / 60), $i % 60);
            $end = sprintf('%02d:%02d:%02d,000', floor(($i + 1) / 3600), floor((($i + 1) % 3600) / 60), ($i + 1) % 60);
            $srt .= "$i\n$start --> $end\nSubtitle number $i\n\n";
        }

        $result = $this->service->parse($srt, 'srt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Subtitle number 1', $result['text']);
        $this->assertStringContainsString('Subtitle number 500', $result['text']);
        $this->assertEquals(500, $result['cueCount']);
    }

    public function testParseVttWithPositionCues(): void
    {
        $vtt = <<<VTT
WEBVTT

00:00:01.000 --> 00:00:05.000 align:start position:10%
Text with positioning
VTT;

        $result = $this->service->parse($vtt, 'vtt');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Text with positioning', $result['text']);
        $this->assertStringNotContainsString('align:', $result['text']);
        $this->assertStringNotContainsString('position:', $result['text']);
    }
}
