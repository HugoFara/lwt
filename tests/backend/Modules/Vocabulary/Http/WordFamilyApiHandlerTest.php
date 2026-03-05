<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Http\WordFamilyApiHandler;
use Lwt\Modules\Vocabulary\Application\Services\LemmaService;
use Lwt\Shared\Infrastructure\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for WordFamilyApiHandler.
 *
 * Tests word family API operations including term family lookups,
 * lemma-based queries, pagination, status updates, and statistics.
 */
class WordFamilyApiHandlerTest extends TestCase
{
    /** @var LemmaService&MockObject */
    private LemmaService $lemmaService;

    private WordFamilyApiHandler $handler;

    protected function setUp(): void
    {
        $this->lemmaService = $this->createMock(LemmaService::class);
        $this->handler = new WordFamilyApiHandler($this->lemmaService);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    #[Test]
    public function constructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(WordFamilyApiHandler::class, $this->handler);
    }

    #[Test]
    public function constructorAcceptsNullLemmaService(): void
    {
        $handler = new WordFamilyApiHandler(null);
        $this->assertInstanceOf(WordFamilyApiHandler::class, $handler);
    }

    #[Test]
    public function constructorDefaultsToNullParameter(): void
    {
        $handler = new WordFamilyApiHandler();
        $this->assertInstanceOf(WordFamilyApiHandler::class, $handler);
    }

    #[Test]
    public function classImplementsApiRoutableInterface(): void
    {
        $reflection = new ReflectionClass(WordFamilyApiHandler::class);
        $this->assertTrue(
            $reflection->implementsInterface(\Lwt\Shared\Http\ApiRoutableInterface::class)
        );
    }

    // =========================================================================
    // getTermFamily tests
    // =========================================================================

    #[Test]
    public function getTermFamilyReturnsErrorWhenTermNotFound(): void
    {
        $this->lemmaService->method('getWordFamilyDetails')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->getTermFamily(999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Term not found', $result['error']);
    }

    #[Test]
    public function getTermFamilyReturnsFamilyData(): void
    {
        $familyData = [
            'lemma' => 'run',
            'members' => [
                ['id' => 1, 'text' => 'running'],
                ['id' => 2, 'text' => 'runs'],
            ],
        ];
        $this->lemmaService->method('getWordFamilyDetails')
            ->with(1)
            ->willReturn($familyData);

        $result = $this->handler->getTermFamily(1);

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame('run', $result['lemma']);
        $this->assertCount(2, $result['members']);
    }

    // =========================================================================
    // getWordFamilyByLemma tests
    // =========================================================================

    #[Test]
    public function getWordFamilyByLemmaReturnsErrorWhenNotFound(): void
    {
        $this->lemmaService->method('getWordFamilyByLemma')
            ->with(1, 'nonexistent')
            ->willReturn(null);

        $result = $this->handler->getWordFamilyByLemma(1, 'nonexistent');

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Word family not found', $result['error']);
    }

    #[Test]
    public function getWordFamilyByLemmaReturnsFamilyData(): void
    {
        $familyData = ['lemma' => 'run', 'count' => 3];
        $this->lemmaService->method('getWordFamilyByLemma')
            ->with(1, 'run')
            ->willReturn($familyData);

        $result = $this->handler->getWordFamilyByLemma(1, 'run');

        $this->assertArrayNotHasKey('error', $result);
        $this->assertSame('run', $result['lemma']);
    }

    // =========================================================================
    // getWordFamilyListFromParams tests
    // =========================================================================

    #[Test]
    public function getWordFamilyListFromParamsUsesDefaults(): void
    {
        $expected = ['families' => [], 'pagination' => ['page' => 1]];
        $this->lemmaService->expects($this->once())
            ->method('getWordFamilyList')
            ->with(1, 1, 50, 'lemma', 'asc')
            ->willReturn($expected);

        $result = $this->handler->getWordFamilyListFromParams(1);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function getWordFamilyListFromParamsClampsPageToMinimumOne(): void
    {
        $expected = ['families' => [], 'pagination' => []];
        $this->lemmaService->expects($this->once())
            ->method('getWordFamilyList')
            ->with(1, 1, 50, 'lemma', 'asc')
            ->willReturn($expected);

        $this->handler->getWordFamilyListFromParams(1, ['page' => -5]);
    }

    #[Test]
    public function getWordFamilyListFromParamsClampsPerPageToMinimumOne(): void
    {
        $expected = ['families' => [], 'pagination' => []];
        $this->lemmaService->expects($this->once())
            ->method('getWordFamilyList')
            ->with(1, 1, 1, 'lemma', 'asc')
            ->willReturn($expected);

        $this->handler->getWordFamilyListFromParams(1, ['per_page' => 0]);
    }

    #[Test]
    public function getWordFamilyListFromParamsClampsPerPageToMaximumHundred(): void
    {
        $expected = ['families' => [], 'pagination' => []];
        $this->lemmaService->expects($this->once())
            ->method('getWordFamilyList')
            ->with(1, 1, 100, 'lemma', 'asc')
            ->willReturn($expected);

        $this->handler->getWordFamilyListFromParams(1, ['per_page' => 999]);
    }

    #[Test]
    public function getWordFamilyListFromParamsPassesSortParams(): void
    {
        $expected = ['families' => [], 'pagination' => []];
        $this->lemmaService->expects($this->once())
            ->method('getWordFamilyList')
            ->with(1, 2, 25, 'count', 'desc')
            ->willReturn($expected);

        $this->handler->getWordFamilyListFromParams(1, [
            'page' => 2,
            'per_page' => 25,
            'sort_by' => 'count',
            'sort_dir' => 'desc',
        ]);
    }

    // =========================================================================
    // updateWordFamilyStatus tests
    // =========================================================================

    #[Test]
    public function updateWordFamilyStatusRejectsInvalidStatus(): void
    {
        $result = $this->handler->updateWordFamilyStatus(1, 'run', 6);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    #[Test]
    public function updateWordFamilyStatusRejectsZeroStatus(): void
    {
        $result = $this->handler->updateWordFamilyStatus(1, 'run', 0);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid status', $result['error']);
    }

    #[Test]
    public function updateWordFamilyStatusRejectsNegativeStatus(): void
    {
        $result = $this->handler->updateWordFamilyStatus(1, 'run', -1);

        $this->assertFalse($result['success']);
    }

    #[Test]
    public function updateWordFamilyStatusAcceptsValidStatusAndReturnsCount(): void
    {
        $this->lemmaService->expects($this->once())
            ->method('updateWordFamilyStatus')
            ->with(1, 'run', 3)
            ->willReturn(5);

        $result = $this->handler->updateWordFamilyStatus(1, 'run', 3);

        $this->assertTrue($result['success']);
        $this->assertSame(5, $result['count']);
    }

    #[Test]
    public function updateWordFamilyStatusAcceptsStatus98(): void
    {
        $this->lemmaService->method('updateWordFamilyStatus')->willReturn(1);

        $result = $this->handler->updateWordFamilyStatus(1, 'run', 98);

        $this->assertTrue($result['success']);
    }

    #[Test]
    public function updateWordFamilyStatusAcceptsStatus99(): void
    {
        $this->lemmaService->method('updateWordFamilyStatus')->willReturn(2);

        $result = $this->handler->updateWordFamilyStatus(1, 'run', 99);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // applyFamilyUpdate tests
    // =========================================================================

    #[Test]
    public function applyFamilyUpdateRejectsInvalidStatus(): void
    {
        $result = $this->handler->applyFamilyUpdate([1, 2], 10);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['count']);
    }

    #[Test]
    public function applyFamilyUpdateWithValidStatusReturnsCount(): void
    {
        $this->lemmaService->expects($this->once())
            ->method('bulkUpdateTermStatus')
            ->with([1, 2, 3], 5)
            ->willReturn(3);

        $result = $this->handler->applyFamilyUpdate([1, 2, 3], 5);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['count']);
    }

    #[Test]
    public function applyFamilyUpdateWithEmptyTermIds(): void
    {
        $this->lemmaService->expects($this->once())
            ->method('bulkUpdateTermStatus')
            ->with([], 1)
            ->willReturn(0);

        $result = $this->handler->applyFamilyUpdate([], 1);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['count']);
    }

    // =========================================================================
    // getFamilyUpdateSuggestion tests
    // =========================================================================

    #[Test]
    public function getFamilyUpdateSuggestionDelegatesToLemmaService(): void
    {
        $expected = [
            'suggestion' => 'Update 3 related terms',
            'affected_count' => 3,
            'term_ids' => [10, 11, 12],
        ];
        $this->lemmaService->expects($this->once())
            ->method('getSuggestedFamilyUpdate')
            ->with(1, 3)
            ->willReturn($expected);

        $result = $this->handler->getFamilyUpdateSuggestion(1, 3);

        $this->assertSame($expected, $result);
    }

    // =========================================================================
    // getLemmaStatistics tests
    // =========================================================================

    #[Test]
    public function getLemmaStatisticsReturnsBothBasicAndAggregate(): void
    {
        $basicStats = ['total_lemmas' => 100];
        $aggregateStats = ['avg_family_size' => 2.5];

        $this->lemmaService->method('getLemmaStatistics')
            ->with(1)
            ->willReturn($basicStats);
        $this->lemmaService->method('getLemmaAggregateStats')
            ->with(1)
            ->willReturn($aggregateStats);

        $result = $this->handler->getLemmaStatistics(1);

        $this->assertArrayHasKey('basic', $result);
        $this->assertArrayHasKey('aggregate', $result);
        $this->assertSame($basicStats, $result['basic']);
        $this->assertSame($aggregateStats, $result['aggregate']);
    }

    // =========================================================================
    // routeGet tests
    // =========================================================================

    #[Test]
    public function routeGetStatsWithoutLanguageIdReturnsError(): void
    {
        $response = $this->handler->routeGet(['word-families', 'stats'], []);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(400, $response->getStatusCode());
        $data = $response->getData();
        $this->assertSame('language_id is required', $data['error']);
    }

    #[Test]
    public function routeGetStatsWithZeroLanguageIdReturnsError(): void
    {
        $response = $this->handler->routeGet(
            ['word-families', 'stats'],
            ['language_id' => 0]
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    #[Test]
    public function routeGetListWithoutLanguageIdReturnsError(): void
    {
        $response = $this->handler->routeGet(['word-families'], []);

        $this->assertSame(400, $response->getStatusCode());
        $data = $response->getData();
        $this->assertSame('language_id is required', $data['error']);
    }

    #[Test]
    public function routeGetWithLemmaLcDelegatesToGetWordFamilyByLemma(): void
    {
        $familyData = ['lemma' => 'run', 'count' => 2];
        $this->lemmaService->method('getWordFamilyByLemma')
            ->with(1, 'run')
            ->willReturn($familyData);

        $response = $this->handler->routeGet(
            ['word-families'],
            ['language_id' => 1, 'lemma_lc' => 'run']
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($familyData, $response->getData());
    }

    #[Test]
    public function routeGetWithoutLemmaLcDelegatesToGetWordFamilyList(): void
    {
        $listData = ['families' => [], 'pagination' => ['page' => 1]];
        $this->lemmaService->method('getWordFamilyList')
            ->willReturn($listData);

        $response = $this->handler->routeGet(
            ['word-families'],
            ['language_id' => 1]
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($listData, $response->getData());
    }

    #[Test]
    public function routeGetStatsWithValidLanguageIdReturnsSuccess(): void
    {
        $this->lemmaService->method('getLemmaStatistics')->willReturn(['total' => 10]);
        $this->lemmaService->method('getLemmaAggregateStats')->willReturn(['avg' => 2.0]);

        $response = $this->handler->routeGet(
            ['word-families', 'stats'],
            ['language_id' => 5]
        );

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData();
        $this->assertArrayHasKey('basic', $data);
        $this->assertArrayHasKey('aggregate', $data);
    }

    // =========================================================================
    // Reflection-based method signature tests
    // =========================================================================

    #[Test]
    public function publicMethodsHaveExpectedSignatures(): void
    {
        $reflection = new ReflectionClass(WordFamilyApiHandler::class);

        // getTermFamily(int): array
        $method = $reflection->getMethod('getTermFamily');
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('int', $params[0]->getType()->getName());
        $this->assertSame('array', $method->getReturnType()->getName());

        // updateWordFamilyStatus(int, string, int): array
        $method = $reflection->getMethod('updateWordFamilyStatus');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('int', $params[0]->getType()->getName());
        $this->assertSame('string', $params[1]->getType()->getName());
        $this->assertSame('int', $params[2]->getType()->getName());

        // applyFamilyUpdate(array, int): array
        $method = $reflection->getMethod('applyFamilyUpdate');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('array', $params[0]->getType()->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
    }

    #[Test]
    public function routeGetReturnsJsonResponse(): void
    {
        $method = new ReflectionMethod(WordFamilyApiHandler::class, 'routeGet');
        $this->assertSame(
            JsonResponse::class,
            $method->getReturnType()->getName()
        );
    }
}
