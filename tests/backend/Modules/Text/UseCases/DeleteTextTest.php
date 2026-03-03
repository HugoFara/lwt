<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\UseCases;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Text\Application\UseCases\DeleteText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DeleteText use case.
 *
 * Since DeleteText relies on static QueryBuilder/Connection calls that
 * cannot be mocked in unit tests, these tests verify class structure,
 * method signatures, return type contracts, and pure logic (e.g. the
 * empty-array guard in deleteMultiple/deleteArchivedTexts).
 *
 * @covers \Lwt\Modules\Text\Application\UseCases\DeleteText
 */
class DeleteTextTest extends TestCase
{
    private DeleteText $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
        $this->useCase = new DeleteText();
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    // =========================================================================
    // Instantiation / Structure Tests
    // =========================================================================

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(DeleteText::class, $this->useCase);
    }

    public function testExecuteMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'execute'),
            'DeleteText should have an execute() method'
        );
    }

    public function testDeleteMultipleMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'deleteMultiple'),
            'DeleteText should have a deleteMultiple() method'
        );
    }

    public function testDeleteArchivedTextMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'deleteArchivedText'),
            'DeleteText should have a deleteArchivedText() method'
        );
    }

    public function testDeleteArchivedTextsMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'deleteArchivedTexts'),
            'DeleteText should have a deleteArchivedTexts() method'
        );
    }

    // =========================================================================
    // Empty Array Guard Tests (pure logic, no DB needed)
    // =========================================================================

    public function testDeleteMultipleReturnsZeroCountForEmptyArray(): void
    {
        $result = $this->useCase->deleteMultiple([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(0, $result['count']);
    }

    public function testDeleteArchivedTextsReturnsZeroCountForEmptyArray(): void
    {
        $result = $this->useCase->deleteArchivedTexts([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(0, $result['count']);
    }

    // =========================================================================
    // Method Signature / Reflection Tests
    // =========================================================================

    public function testExecuteAcceptsIntParameter(): void
    {
        $reflection = new \ReflectionMethod(DeleteText::class, 'execute');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('textId', $params[0]->getName());
        $this->assertEquals('int', $params[0]->getType()->getName());
    }

    public function testExecuteReturnsArray(): void
    {
        $reflection = new \ReflectionMethod(DeleteText::class, 'execute');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    public function testDeleteMultipleAcceptsArrayParameter(): void
    {
        $reflection = new \ReflectionMethod(DeleteText::class, 'deleteMultiple');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('textIds', $params[0]->getName());
        $this->assertEquals('array', $params[0]->getType()->getName());
    }

    public function testDeleteArchivedTextAcceptsIntParameter(): void
    {
        $reflection = new \ReflectionMethod(DeleteText::class, 'deleteArchivedText');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('textId', $params[0]->getName());
        $this->assertEquals('int', $params[0]->getType()->getName());
    }

    public function testDeleteArchivedTextsAcceptsArrayParameter(): void
    {
        $reflection = new \ReflectionMethod(DeleteText::class, 'deleteArchivedTexts');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('textIds', $params[0]->getName());
        $this->assertEquals('array', $params[0]->getType()->getName());
    }

    // =========================================================================
    // Data Provider: Empty Input Variants
    // =========================================================================

    /**
     * @dataProvider emptyArrayProvider
     */
    public function testDeleteMultipleHandlesVariousEmptyInputs(array $input): void
    {
        $result = $this->useCase->deleteMultiple($input);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(0, $result['count']);
    }

    /**
     * @dataProvider emptyArrayProvider
     */
    public function testDeleteArchivedTextsHandlesVariousEmptyInputs(array $input): void
    {
        $result = $this->useCase->deleteArchivedTexts($input);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertEquals(0, $result['count']);
    }

    /**
     * @return array<string, array{array}>
     */
    public static function emptyArrayProvider(): array
    {
        return [
            'empty array' => [[]],
        ];
    }
}
