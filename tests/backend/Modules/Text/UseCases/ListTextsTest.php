<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\UseCases;

use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\Text\Application\UseCases\ListTexts;
use Lwt\Modules\Text\Domain\TextRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the ListTexts use case.
 *
 * Tests pure logic methods (getPagination) thoroughly and verifies
 * repository delegation for getTextsForLanguage.
 *
 */
#[CoversClass(ListTexts::class)]
class ListTextsTest extends TestCase
{
    /** @var TextRepositoryInterface&MockObject */
    private TextRepositoryInterface $textRepository;

    private ListTexts $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        Globals::reset();
        $this->textRepository = $this->createMock(TextRepositoryInterface::class);
        $this->useCase = new ListTexts($this->textRepository);
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
        $this->assertInstanceOf(ListTexts::class, $this->useCase);
    }

    // =========================================================================
    // Method Existence Tests (for methods that need DB)
    // =========================================================================

    public function testGetTextsPerPageMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getTextsPerPage'),
            'ListTexts should have a getTextsPerPage() method'
        );
    }

    public function testGetArchivedTextsPerPageMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getArchivedTextsPerPage'),
            'ListTexts should have a getArchivedTextsPerPage() method'
        );
    }

    public function testGetTextCountMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getTextCount'),
            'ListTexts should have a getTextCount() method'
        );
    }

    public function testGetTextsListMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->useCase, 'getTextsList'),
            'ListTexts should have a getTextsList() method'
        );
    }

    // =========================================================================
    // getPagination() Tests — Pure Logic
    // =========================================================================

    public function testGetPaginationReturnsCorrectStructure(): void
    {
        $result = $this->useCase->getPagination(100, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('currentPage', $result);
        $this->assertArrayHasKey('limit', $result);
    }

    public function testGetPaginationCalculatesCorrectPageCount(): void
    {
        $result = $this->useCase->getPagination(100, 1, 10);
        $this->assertEquals(10, $result['pages']);
    }

    public function testGetPaginationRoundsUpPageCount(): void
    {
        $result = $this->useCase->getPagination(101, 1, 10);
        $this->assertEquals(11, $result['pages']);
    }

    public function testGetPaginationHandlesZeroTotalCount(): void
    {
        $result = $this->useCase->getPagination(0, 1, 10);

        $this->assertEquals(0, $result['pages']);
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals('LIMIT 0,10', $result['limit']);
    }

    public function testGetPaginationClampsPageBelowOne(): void
    {
        $result = $this->useCase->getPagination(100, 0, 10);
        $this->assertEquals(1, $result['currentPage']);

        $result = $this->useCase->getPagination(100, -5, 10);
        $this->assertEquals(1, $result['currentPage']);
    }

    public function testGetPaginationClampsPageAboveMaximum(): void
    {
        $result = $this->useCase->getPagination(50, 10, 10);
        // 50 items / 10 per page = 5 pages, requesting page 10 should clamp to 5
        $this->assertEquals(5, $result['currentPage']);
    }

    public function testGetPaginationDoesNotClampPageAboveWhenZeroPages(): void
    {
        // When totalCount=0, pages=0, the "clamp above max" guard requires pages > 0,
        // so currentPage stays at the requested value (not clamped down).
        $result = $this->useCase->getPagination(0, 5, 10);
        $this->assertEquals(5, $result['currentPage']);
    }

    public function testGetPaginationCalculatesCorrectOffset(): void
    {
        $result = $this->useCase->getPagination(100, 3, 10);
        // Page 3, 10 per page => offset = (3-1)*10 = 20
        $this->assertEquals('LIMIT 20,10', $result['limit']);
    }

    public function testGetPaginationFirstPage(): void
    {
        $result = $this->useCase->getPagination(50, 1, 10);

        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals('LIMIT 0,10', $result['limit']);
    }

    public function testGetPaginationLastPage(): void
    {
        $result = $this->useCase->getPagination(50, 5, 10);

        $this->assertEquals(5, $result['currentPage']);
        $this->assertEquals('LIMIT 40,10', $result['limit']);
    }

    public function testGetPaginationSingleItem(): void
    {
        $result = $this->useCase->getPagination(1, 1, 10);

        $this->assertEquals(1, $result['pages']);
        $this->assertEquals(1, $result['currentPage']);
        $this->assertEquals('LIMIT 0,10', $result['limit']);
    }

    public function testGetPaginationExactlyOneFullPage(): void
    {
        $result = $this->useCase->getPagination(10, 1, 10);

        $this->assertEquals(1, $result['pages']);
        $this->assertEquals(1, $result['currentPage']);
    }

    public function testGetPaginationOneMoreThanFullPage(): void
    {
        $result = $this->useCase->getPagination(11, 1, 10);

        $this->assertEquals(2, $result['pages']);
    }
    #[DataProvider('paginationProvider')]
    public function testGetPaginationWithVariousInputs(
        int $totalCount,
        int $currentPage,
        int $perPage,
        int $expectedPages,
        int $expectedCurrentPage,
        string $expectedLimit
    ): void {
        $result = $this->useCase->getPagination($totalCount, $currentPage, $perPage);

        $this->assertEquals($expectedPages, $result['pages']);
        $this->assertEquals($expectedCurrentPage, $result['currentPage']);
        $this->assertEquals($expectedLimit, $result['limit']);
    }

    /**
     * @return array<string, array{int, int, int, int, int, string}>
     */
    public static function paginationProvider(): array
    {
        return [
            'first page of many' => [100, 1, 10, 10, 1, 'LIMIT 0,10'],
            'middle page' => [100, 5, 10, 10, 5, 'LIMIT 40,10'],
            'last page' => [100, 10, 10, 10, 10, 'LIMIT 90,10'],
            'over max page clamps' => [100, 15, 10, 10, 10, 'LIMIT 90,10'],
            'negative page clamps to 1' => [100, -1, 10, 10, 1, 'LIMIT 0,10'],
            'zero page clamps to 1' => [100, 0, 10, 10, 1, 'LIMIT 0,10'],
            'zero items' => [0, 1, 10, 0, 1, 'LIMIT 0,10'],
            'single item per page' => [5, 3, 1, 5, 3, 'LIMIT 2,1'],
            'large per page' => [5, 1, 100, 1, 1, 'LIMIT 0,100'],
            'exact boundary' => [20, 2, 10, 2, 2, 'LIMIT 10,10'],
            'one over boundary' => [21, 3, 10, 3, 3, 'LIMIT 20,10'],
        ];
    }

    // =========================================================================
    // getTextsForLanguage() Tests — Repository Delegation
    // =========================================================================

    public function testGetTextsForLanguageDelegatesToRepository(): void
    {
        $expectedResult = [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 20,
            'total_pages' => 0,
        ];

        $this->textRepository->expects($this->once())
            ->method('findPaginated')
            ->with(1, 1, 20)
            ->willReturn($expectedResult);

        $result = $this->useCase->getTextsForLanguage(1);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetTextsForLanguagePassesCustomPagination(): void
    {
        $expectedResult = [
            'items' => [['id' => 1], ['id' => 2]],
            'total' => 50,
            'page' => 3,
            'per_page' => 10,
            'total_pages' => 5,
        ];

        $this->textRepository->expects($this->once())
            ->method('findPaginated')
            ->with(2, 3, 10)
            ->willReturn($expectedResult);

        $result = $this->useCase->getTextsForLanguage(2, 3, 10);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetTextsForLanguageReturnsRepositoryResult(): void
    {
        $items = [
            ['id' => 10, 'title' => 'Text A'],
            ['id' => 20, 'title' => 'Text B'],
        ];
        $expectedResult = [
            'items' => $items,
            'total' => 2,
            'page' => 1,
            'per_page' => 20,
            'total_pages' => 1,
        ];

        $this->textRepository->method('findPaginated')
            ->willReturn($expectedResult);

        $result = $this->useCase->getTextsForLanguage(5);

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('total_pages', $result);
        $this->assertCount(2, $result['items']);
    }

    public function testGetTextsForLanguageDefaultParameters(): void
    {
        $this->textRepository->expects($this->once())
            ->method('findPaginated')
            ->with(
                $this->identicalTo(1),
                $this->identicalTo(1),
                $this->identicalTo(20)
            )
            ->willReturn([
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => 20,
                'total_pages' => 0,
            ]);

        $this->useCase->getTextsForLanguage(1);
    }

    // =========================================================================
    // Parameterized Method Signature Tests (Sprint 12)
    // =========================================================================

    public function testGetTextCountAcceptsParamsParameter(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getTextCount');
        $params = $ref->getParameters();
        $this->assertCount(4, $params);
        $this->assertSame('params', $params[3]->getName());
        $this->assertTrue($params[3]->isOptional());
        $this->assertSame([], $params[3]->getDefaultValue());
    }

    public function testGetTextsListAcceptsParamsParameter(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getTextsList');
        $params = $ref->getParameters();
        $this->assertCount(7, $params);
        $this->assertSame('params', $params[6]->getName());
        $this->assertTrue($params[6]->isOptional());
    }

    public function testGetArchivedTextCountAcceptsParamsParameter(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getArchivedTextCount');
        $params = $ref->getParameters();
        $this->assertCount(4, $params);
        $this->assertSame('params', $params[3]->getName());
        $this->assertTrue($params[3]->isOptional());
    }

    public function testGetArchivedTextsListAcceptsParamsParameter(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getArchivedTextsList');
        $params = $ref->getParameters();
        $this->assertCount(7, $params);
        $this->assertSame('params', $params[6]->getName());
        $this->assertTrue($params[6]->isOptional());
    }

    public function testGetTextCountParamsDefaultIsEmptyArray(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getTextCount');
        $this->assertSame([], $ref->getParameters()[3]->getDefaultValue());
    }

    public function testGetTextsListParamsDefaultIsEmptyArray(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getTextsList');
        $this->assertSame([], $ref->getParameters()[6]->getDefaultValue());
    }

    public function testGetArchivedTextCountReturnTypeIsInt(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getArchivedTextCount');
        $this->assertSame('int', $ref->getReturnType()?->getName());
    }

    public function testGetArchivedTextsListReturnTypeIsArray(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getArchivedTextsList');
        $this->assertSame('array', $ref->getReturnType()?->getName());
    }

    public function testGetTextsListReturnTypeIsArray(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getTextsList');
        $this->assertSame('array', $ref->getReturnType()?->getName());
    }

    public function testGetTextCountReturnTypeIsInt(): void
    {
        $ref = new \ReflectionMethod(ListTexts::class, 'getTextCount');
        $this->assertSame('int', $ref->getReturnType()?->getName());
    }
}
