<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\UseCases;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Text\Application\UseCases\GetTextForReading;
use Lwt\Modules\Text\Domain\TextRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetTextForReading use case.
 *
 * Tests repository-delegated methods (getNavigation) via mocking
 * and verifies method signatures for DB-dependent methods.
 *
 * @covers \Lwt\Modules\Text\Application\UseCases\GetTextForReading
 */
class GetTextForReadingTest extends TestCase
{
    /** @var TextRepositoryInterface&MockObject */
    private TextRepositoryInterface $textRepository;

    private GetTextForReading $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
        $this->textRepository = $this->createMock(TextRepositoryInterface::class);
        $this->useCase = new GetTextForReading($this->textRepository);
    }

    protected function tearDown(): void
    {
        Globals::reset();
        parent::tearDown();
    }

    // =========================================================================
    // Instantiation Tests
    // =========================================================================

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(GetTextForReading::class, $this->useCase);
    }

    // =========================================================================
    // Method Existence Tests (DB-dependent methods)
    // =========================================================================

    public function testExecuteMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'execute'),
            'GetTextForReading should have an execute() method'
        );
    }

    public function testGetLanguageSettingsForReadingMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getLanguageSettingsForReading'),
            'GetTextForReading should have a getLanguageSettingsForReading() method'
        );
    }

    public function testGetTtsVoiceApiMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getTtsVoiceApi'),
            'GetTextForReading should have a getTtsVoiceApi() method'
        );
    }

    public function testGetLanguageIdByNameMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getLanguageIdByName'),
            'GetTextForReading should have a getLanguageIdByName() method'
        );
    }

    public function testGetLanguageTranslateUrisMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getLanguageTranslateUris'),
            'GetTextForReading should have a getLanguageTranslateUris() method'
        );
    }

    // =========================================================================
    // Method Signature / Reflection Tests
    // =========================================================================

    public function testExecuteAcceptsIntAndReturnsNullableArray(): void
    {
        $reflection = new \ReflectionMethod(GetTextForReading::class, 'execute');
        $params = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertEquals('textId', $params[0]->getName());
        $this->assertEquals('int', $params[0]->getType()->getName());

        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    public function testGetTtsVoiceApiReturnsString(): void
    {
        $reflection = new \ReflectionMethod(GetTextForReading::class, 'getTtsVoiceApi');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
        $this->assertFalse($returnType->allowsNull());
    }

    public function testGetLanguageIdByNameReturnsNullableInt(): void
    {
        $reflection = new \ReflectionMethod(GetTextForReading::class, 'getLanguageIdByName');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
    }

    // =========================================================================
    // getNavigation() Tests — Repository Delegation
    // =========================================================================

    public function testGetNavigationReturnsCorrectStructure(): void
    {
        $this->textRepository->method('getPreviousTextId')->willReturn(4);
        $this->textRepository->method('getNextTextId')->willReturn(6);

        $result = $this->useCase->getNavigation(5, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('previous', $result);
        $this->assertArrayHasKey('next', $result);
    }

    public function testGetNavigationReturnsPreviousAndNextIds(): void
    {
        $this->textRepository->expects($this->once())
            ->method('getPreviousTextId')
            ->with(5, 1)
            ->willReturn(4);

        $this->textRepository->expects($this->once())
            ->method('getNextTextId')
            ->with(5, 1)
            ->willReturn(6);

        $result = $this->useCase->getNavigation(5, 1);

        $this->assertEquals(4, $result['previous']);
        $this->assertEquals(6, $result['next']);
    }

    public function testGetNavigationReturnsNullWhenNoPreviousText(): void
    {
        $this->textRepository->method('getPreviousTextId')
            ->with(1, 1)
            ->willReturn(null);
        $this->textRepository->method('getNextTextId')
            ->with(1, 1)
            ->willReturn(2);

        $result = $this->useCase->getNavigation(1, 1);

        $this->assertNull($result['previous']);
        $this->assertEquals(2, $result['next']);
    }

    public function testGetNavigationReturnsNullWhenNoNextText(): void
    {
        $this->textRepository->method('getPreviousTextId')
            ->with(10, 1)
            ->willReturn(9);
        $this->textRepository->method('getNextTextId')
            ->with(10, 1)
            ->willReturn(null);

        $result = $this->useCase->getNavigation(10, 1);

        $this->assertEquals(9, $result['previous']);
        $this->assertNull($result['next']);
    }

    public function testGetNavigationReturnsNullForBothWhenOnlyText(): void
    {
        $this->textRepository->method('getPreviousTextId')->willReturn(null);
        $this->textRepository->method('getNextTextId')->willReturn(null);

        $result = $this->useCase->getNavigation(1, 1);

        $this->assertNull($result['previous']);
        $this->assertNull($result['next']);
    }

    public function testGetNavigationPassesCorrectLanguageId(): void
    {
        $this->textRepository->expects($this->once())
            ->method('getPreviousTextId')
            ->with(5, 42)
            ->willReturn(3);

        $this->textRepository->expects($this->once())
            ->method('getNextTextId')
            ->with(5, 42)
            ->willReturn(7);

        $result = $this->useCase->getNavigation(5, 42);

        $this->assertEquals(3, $result['previous']);
        $this->assertEquals(7, $result['next']);
    }

    /**
     * @dataProvider navigationProvider
     */
    public function testGetNavigationWithVariousScenarios(
        int $textId,
        int $languageId,
        ?int $expectedPrevious,
        ?int $expectedNext
    ): void {
        $this->textRepository->method('getPreviousTextId')
            ->with($textId, $languageId)
            ->willReturn($expectedPrevious);

        $this->textRepository->method('getNextTextId')
            ->with($textId, $languageId)
            ->willReturn($expectedNext);

        $result = $this->useCase->getNavigation($textId, $languageId);

        $this->assertEquals($expectedPrevious, $result['previous']);
        $this->assertEquals($expectedNext, $result['next']);
    }

    /**
     * @return array<string, array{int, int, int|null, int|null}>
     */
    public static function navigationProvider(): array
    {
        return [
            'first text in language' => [1, 1, null, 2],
            'middle text' => [5, 1, 4, 6],
            'last text in language' => [10, 1, 9, null],
            'only text in language' => [1, 2, null, null],
            'non-sequential ids' => [50, 3, 23, 87],
            'different language' => [5, 99, 3, 8],
        ];
    }
}
