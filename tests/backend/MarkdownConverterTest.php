<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/tools/markdown_converter.php';

use PHPUnit\Framework\TestCase;

final class MarkdownConverterTest extends TestCase
{
    
    /**
     * Test the conversion from Markdown to HTML
     */
    public function testMarkdownConversion(): void
    {
        $initialMarkdown = '# Test markdown';
        $expectedHTML = '<h1>Test markdown</h1>';
        $temp = tmpfile();
        $path = stream_get_meta_data($temp)['uri'];
        fwrite($temp, $initialMarkdown);
        fseek($temp, 0);
        $outputText = trim(markdown_converter($path));
        fclose($temp);
        $this->assertSame($expectedHTML, $outputText);
    }

}
