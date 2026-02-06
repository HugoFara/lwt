<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\UseCases;

require_once __DIR__ . '/../../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
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
    // TEXT LENGTH BOUNDARY TESTS
    // ========================

    public function testValidateTextLengthAtExactBoundary(): void
    {
        $text = str_repeat('a', 65000);
        $this->assertTrue($this->importText->validateTextLength($text));

        $text = str_repeat('a', 65000) . 'b';
        $this->assertFalse($this->importText->validateTextLength($text));
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
}
