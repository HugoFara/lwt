<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Domain\Parser;

use InvalidArgumentException;
use Lwt\Modules\Language\Domain\Parser\ExternalParserConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the ExternalParserConfig class.
 */
class ExternalParserConfigTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $config = new ExternalParserConfig(
            'jieba',
            'Jieba (Chinese)',
            '/usr/bin/python3',
            ['/opt/parsers/jieba.py', '--mode=cut'],
            'stdin',
            'line'
        );

        $this->assertEquals('jieba', $config->getType());
        $this->assertEquals('Jieba (Chinese)', $config->getName());
        $this->assertEquals('/usr/bin/python3', $config->getBinary());
        $this->assertEquals(['/opt/parsers/jieba.py', '--mode=cut'], $config->getArgs());
        $this->assertEquals('stdin', $config->getInputMode());
        $this->assertEquals('line', $config->getOutputFormat());
    }

    public function testUsesStdinReturnsCorrectly(): void
    {
        $stdinConfig = new ExternalParserConfig(
            'test',
            'Test',
            '/bin/test',
            [],
            'stdin',
            'line'
        );

        $fileConfig = new ExternalParserConfig(
            'test',
            'Test',
            '/bin/test',
            [],
            'file',
            'line'
        );

        $this->assertTrue($stdinConfig->usesStdin());
        $this->assertFalse($stdinConfig->usesFile());

        $this->assertFalse($fileConfig->usesStdin());
        $this->assertTrue($fileConfig->usesFile());
    }

    public function testFromArrayCreatesConfig(): void
    {
        $array = [
            'name' => 'Sudachi (Japanese)',
            'binary' => 'sudachipy',
            'args' => ['-m', 'C'],
            'input_mode' => 'stdin',
            'output_format' => 'wakati',
        ];

        $config = ExternalParserConfig::fromArray('sudachi', $array);

        $this->assertEquals('sudachi', $config->getType());
        $this->assertEquals('Sudachi (Japanese)', $config->getName());
        $this->assertEquals('sudachipy', $config->getBinary());
        $this->assertEquals(['-m', 'C'], $config->getArgs());
        $this->assertEquals('stdin', $config->getInputMode());
        $this->assertEquals('wakati', $config->getOutputFormat());
    }

    public function testFromArrayUsesDefaults(): void
    {
        $array = [
            'name' => 'Simple Parser',
            'binary' => '/usr/bin/parse',
        ];

        $config = ExternalParserConfig::fromArray('simple', $array);

        $this->assertEquals('simple', $config->getType());
        $this->assertEquals([], $config->getArgs());
        $this->assertEquals('stdin', $config->getInputMode());
        $this->assertEquals('line', $config->getOutputFormat());
    }

    public function testFromArrayThrowsOnMissingName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("missing required 'name' field");

        ExternalParserConfig::fromArray('test', [
            'binary' => '/usr/bin/test',
        ]);
    }

    public function testFromArrayThrowsOnMissingBinary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("missing required 'binary' field");

        ExternalParserConfig::fromArray('test', [
            'name' => 'Test Parser',
        ]);
    }

    public function testConstructorThrowsOnEmptyType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parser type cannot be empty');

        new ExternalParserConfig(
            '',
            'Test',
            '/bin/test',
            [],
            'stdin',
            'line'
        );
    }

    public function testConstructorThrowsOnEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parser name cannot be empty');

        new ExternalParserConfig(
            'test',
            '',
            '/bin/test',
            [],
            'stdin',
            'line'
        );
    }

    public function testConstructorThrowsOnEmptyBinary(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parser binary cannot be empty');

        new ExternalParserConfig(
            'test',
            'Test',
            '',
            [],
            'stdin',
            'line'
        );
    }

    public function testConstructorThrowsOnInvalidInputMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid input mode 'invalid'");

        new ExternalParserConfig(
            'test',
            'Test',
            '/bin/test',
            [],
            'invalid',
            'line'
        );
    }

    public function testConstructorThrowsOnInvalidOutputFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid output format 'invalid'");

        new ExternalParserConfig(
            'test',
            'Test',
            '/bin/test',
            [],
            'stdin',
            'invalid'
        );
    }
}
