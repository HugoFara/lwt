<?php

/**
 * Unit tests for TextStatisticsService.
 *
 * The service methods rely heavily on database queries and static methods
 * (Connection::query, Connection::fetchValue, Settings::getWithDefault).
 * This test file validates instantiation, method existence, and return
 * type contracts. Integration tests with a real database are needed for
 * full coverage of getTextWordCount, getTodoWordsCount, getTodoWordsContent.
 *
 * PHP version 8.1
 *
 * @category Testing
 * @package  Lwt\Tests\Modules\Text\Application\Services
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Tests\Modules\Text\Application\Services;

use Lwt\Modules\Text\Application\Services\TextStatisticsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TextStatisticsService.
 *
 * @covers \Lwt\Modules\Text\Application\Services\TextStatisticsService
 * @since  3.0.0
 */
class TextStatisticsServiceTest extends TestCase
{
    // =========================================================================
    // Instantiation and method existence
    // =========================================================================

    #[Test]
    public function canBeInstantiated(): void
    {
        $service = new TextStatisticsService();

        $this->assertInstanceOf(TextStatisticsService::class, $service);
    }

    #[Test]
    public function getTextWordCountMethodExists(): void
    {
        $service = new TextStatisticsService();

        $this->assertTrue(
            method_exists($service, 'getTextWordCount'),
            'getTextWordCount method should exist'
        );
    }

    #[Test]
    public function getTodoWordsCountMethodExists(): void
    {
        $service = new TextStatisticsService();

        $this->assertTrue(
            method_exists($service, 'getTodoWordsCount'),
            'getTodoWordsCount method should exist'
        );
    }

    #[Test]
    public function getTodoWordsContentMethodExists(): void
    {
        $service = new TextStatisticsService();

        $this->assertTrue(
            method_exists($service, 'getTodoWordsContent'),
            'getTodoWordsContent method should exist'
        );
    }

    #[Test]
    public function getTextWordCountAcceptsStringParameter(): void
    {
        $method = new \ReflectionMethod(TextStatisticsService::class, 'getTextWordCount');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('textsId', $params[0]->getName());
        $this->assertSame('string', $params[0]->getType()->getName());
    }

    #[Test]
    public function getTodoWordsCountAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(TextStatisticsService::class, 'getTodoWordsCount');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('textId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    #[Test]
    public function getTodoWordsContentAcceptsIntParameter(): void
    {
        $method = new \ReflectionMethod(TextStatisticsService::class, 'getTodoWordsContent');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('textId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()->getName());
    }

    #[Test]
    public function getTextWordCountReturnsArray(): void
    {
        $method = new \ReflectionMethod(TextStatisticsService::class, 'getTextWordCount');

        $this->assertSame('array', $method->getReturnType()->getName());
    }

    #[Test]
    public function getTodoWordsCountReturnsInt(): void
    {
        $method = new \ReflectionMethod(TextStatisticsService::class, 'getTodoWordsCount');

        $this->assertSame('int', $method->getReturnType()->getName());
    }

    #[Test]
    public function getTodoWordsContentReturnsString(): void
    {
        $method = new \ReflectionMethod(TextStatisticsService::class, 'getTodoWordsContent');

        $this->assertSame('string', $method->getReturnType()->getName());
    }
}
