<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Exception;

use Lwt\Core\Exception\LwtException;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the base LwtException class.
 *
 * @covers \Lwt\Core\Exception\LwtException
 */
class LwtExceptionTest extends TestCase
{
    public function testConstructorWithMessage(): void
    {
        $exception = new LwtException('Test error message');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithCodeAndPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new LwtException('Test error', 42, $previous);

        $this->assertSame('Test error', $exception->getMessage());
        $this->assertSame(42, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConstructorWithContext(): void
    {
        $context = ['user_id' => 123, 'action' => 'delete'];
        $exception = new LwtException('Test error', 0, null, $context);

        $this->assertSame($context, $exception->getContext());
    }

    public function testWithContext(): void
    {
        $exception = new LwtException('Test error');
        $result = $exception->withContext('key', 'value');

        $this->assertSame($exception, $result);
        $this->assertSame(['key' => 'value'], $exception->getContext());
    }

    public function testWithContextMultipleValues(): void
    {
        $exception = new LwtException('Test error');
        $exception->withContext('key1', 'value1')
                  ->withContext('key2', 'value2');

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $exception->getContext());
    }

    public function testDefaultHttpStatusCode(): void
    {
        $exception = new LwtException('Test error');

        $this->assertSame(500, $exception->getHttpStatusCode());
    }

    public function testSetHttpStatusCode(): void
    {
        $exception = new LwtException('Test error');
        $result = $exception->setHttpStatusCode(404);

        $this->assertSame($exception, $result);
        $this->assertSame(404, $exception->getHttpStatusCode());
    }

    public function testShouldLogDefaultsToTrue(): void
    {
        $exception = new LwtException('Test error');

        $this->assertTrue($exception->shouldLog());
    }

    public function testGetUserMessage(): void
    {
        $exception = new LwtException('Internal error details');

        // User message should be generic for safety
        $this->assertSame(
            'An unexpected error occurred. Please try again later.',
            $exception->getUserMessage()
        );
    }

    public function testToArrayWithoutTrace(): void
    {
        $exception = new LwtException('Test error', 42, null, ['key' => 'value']);
        $exception->setHttpStatusCode(400);

        $array = $exception->toArray(false);

        $this->assertSame(LwtException::class, $array['type']);
        $this->assertSame('Test error', $array['message']);
        $this->assertSame(42, $array['code']);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
        $this->assertSame(['key' => 'value'], $array['context']);
        $this->assertSame(400, $array['http_status']);
        $this->assertArrayNotHasKey('trace', $array);
    }

    public function testToArrayWithTrace(): void
    {
        $exception = new LwtException('Test error');

        $array = $exception->toArray(true);

        $this->assertArrayHasKey('trace', $array);
        $this->assertIsString($array['trace']);
    }

    public function testToArrayWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new LwtException('Test error', 0, $previous);

        $array = $exception->toArray(false);

        $this->assertArrayHasKey('previous', $array);
        $this->assertSame(\RuntimeException::class, $array['previous']['type']);
        $this->assertSame('Previous error', $array['previous']['message']);
    }
}
