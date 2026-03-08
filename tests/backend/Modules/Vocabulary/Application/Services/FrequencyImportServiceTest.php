<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Application\Services;

use Lwt\Modules\Vocabulary\Application\Services\FrequencyImportService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FrequencyImportServiceTest extends TestCase
{
    private FrequencyImportService $service;

    protected function setUp(): void
    {
        $this->service = new FrequencyImportService();
    }

    // =========================================================================
    // isAvailableForLanguage
    // =========================================================================

    #[Test]
    public function isAvailableForSpanish(): void
    {
        $this->assertTrue($this->service->isAvailableForLanguage('Spanish'));
    }

    #[Test]
    public function isAvailableForFrench(): void
    {
        $this->assertTrue($this->service->isAvailableForLanguage('French'));
    }

    #[Test]
    public function isNotAvailableForKlingon(): void
    {
        $this->assertFalse($this->service->isAvailableForLanguage('Klingon'));
    }

    #[Test]
    public function isNotAvailableForEmpty(): void
    {
        $this->assertFalse($this->service->isAvailableForLanguage(''));
    }

    // =========================================================================
    // parseFrequencyList (via reflection)
    // =========================================================================

    #[Test]
    public function parseFrequencyListParsesStandardFormat(): void
    {
        $content = "de 14459520\nque 14421005\nel 10728567\n";
        $result = $this->invokeParseFrequencyList($content);

        $this->assertSame(['de', 'que', 'el'], $result);
    }

    #[Test]
    public function parseFrequencyListSkipsBlankLines(): void
    {
        $content = "hello 100\n\nworld 50\n\n";
        $result = $this->invokeParseFrequencyList($content);

        $this->assertSame(['hello', 'world'], $result);
    }

    #[Test]
    public function parseFrequencyListHandlesUnicodeWords(): void
    {
        $content = "привет 500\nмир 300\n";
        $result = $this->invokeParseFrequencyList($content);

        $this->assertSame(['привет', 'мир'], $result);
    }

    #[Test]
    public function parseFrequencyListHandlesWordWithSpaces(): void
    {
        // Multi-word expression: "ice cream 100" — last space splits
        $content = "ice cream 100\nhello 50\n";
        $result = $this->invokeParseFrequencyList($content);

        $this->assertSame(['ice cream', 'hello'], $result);
    }

    #[Test]
    public function parseFrequencyListHandlesNoFrequency(): void
    {
        $content = "hello\nworld\n";
        $result = $this->invokeParseFrequencyList($content);

        $this->assertSame(['hello', 'world'], $result);
    }

    #[Test]
    public function parseFrequencyListReturnsEmptyForEmptyContent(): void
    {
        $result = $this->invokeParseFrequencyList('');
        $this->assertSame([], $result);
    }

    #[Test]
    public function parseFrequencyListHandlesWindowsLineEndings(): void
    {
        $content = "hello 100\r\nworld 50\r\n";
        $result = $this->invokeParseFrequencyList($content);

        $this->assertSame(['hello', 'world'], $result);
    }

    #[Test]
    public function parseFrequencyListPreservesOrder(): void
    {
        $content = "the 100\nis 90\na 80\nof 70\n";
        $result = $this->invokeParseFrequencyList($content);

        $this->assertSame(['the', 'is', 'a', 'of'], $result);
    }

    // =========================================================================
    // fetchFrequencyList error cases
    // =========================================================================

    #[Test]
    public function fetchFrequencyListThrowsForUnsupportedLanguage(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Frequency data not available');

        $this->service->fetchFrequencyList('Klingon');
    }

    // =========================================================================
    // importWords error case
    // =========================================================================

    #[Test]
    public function importWordsThrowsForUnsupportedLanguage(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->importWords(1, 'Klingon', 500);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * @return list<string>
     */
    private function invokeParseFrequencyList(string $content): array
    {
        $method = new \ReflectionMethod(FrequencyImportService::class, 'parseFrequencyList');
        $method->setAccessible(true);
        return $method->invoke($this->service, $content);
    }
}
