<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Home\UseCases;

use Lwt\Modules\Home\Application\UseCases\GetTextStatistics;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetTextStatistics use case.
 */
class GetTextStatisticsTest extends TestCase
{
    /** @var TextStatisticsService&MockObject */
    private TextStatisticsService $statsService;

    private GetTextStatistics $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statsService = $this->createMock(TextStatisticsService::class);
        $this->useCase = new GetTextStatistics($this->statsService);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsStatisticsForText(): void
    {
        $textInfo = [
            'title' => 'My Test Text',
            'language_id' => 1,
            'language_name' => 'French',
            'annotated' => false
        ];

        $this->statsService->expects($this->once())
            ->method('getTextWordCount')
            ->with('42')
            ->willReturn([
                'total' => [42 => 100],
                'expr' => [42 => 10],
                'stat' => [42 => [1 => 5, 2 => 3]],
                'totalu' => [42 => 80],
                'expru' => [42 => 8],
                'statu' => [42 => [1 => 10, 2 => 5, 3 => 3, 4 => 2, 5 => 1, 98 => 5, 99 => 20]]
            ]);

        $this->statsService->expects($this->once())
            ->method('getTodoWordsCount')
            ->with(42)
            ->willReturn(15);

        $result = $this->useCase->execute(42, $textInfo);

        $this->assertNotNull($result);
        $this->assertEquals(42, $result['id']);
        $this->assertEquals('My Test Text', $result['title']);
        $this->assertEquals(1, $result['language_id']);
        $this->assertEquals('French', $result['language_name']);
        $this->assertFalse($result['annotated']);
        $this->assertArrayHasKey('stats', $result);
    }

    public function testExecuteCalculatesUnknownFromTodo(): void
    {
        $textInfo = [
            'title' => 'Text',
            'language_id' => 1,
            'language_name' => 'English',
            'annotated' => false
        ];

        $this->statsService->method('getTextWordCount')
            ->willReturn([
                'total' => [],
                'expr' => [],
                'stat' => [],
                'totalu' => [],
                'expru' => [],
                'statu' => []
            ]);

        $this->statsService->method('getTodoWordsCount')
            ->willReturn(25);

        $result = $this->useCase->execute(1, $textInfo);

        $this->assertEquals(25, $result['stats']['unknown']);
    }

    public function testExecutePopulatesAllStatusCounts(): void
    {
        $textInfo = [
            'title' => 'Text',
            'language_id' => 1,
            'language_name' => 'German',
            'annotated' => true
        ];

        $this->statsService->method('getTextWordCount')
            ->willReturn([
                'total' => [5 => 100],
                'expr' => [5 => 10],
                'stat' => [5 => []],
                'totalu' => [5 => 80],
                'expru' => [5 => 8],
                'statu' => [
                    5 => [
                        1 => 10,
                        2 => 8,
                        3 => 6,
                        4 => 4,
                        5 => 2,
                        98 => 15,
                        99 => 30
                    ]
                ]
            ]);

        $this->statsService->method('getTodoWordsCount')
            ->willReturn(5);

        $result = $this->useCase->execute(5, $textInfo);

        $this->assertEquals(5, $result['stats']['unknown']);
        $this->assertEquals(10, $result['stats']['s1']);
        $this->assertEquals(8, $result['stats']['s2']);
        $this->assertEquals(6, $result['stats']['s3']);
        $this->assertEquals(4, $result['stats']['s4']);
        $this->assertEquals(2, $result['stats']['s5']);
        $this->assertEquals(15, $result['stats']['s98']);
        $this->assertEquals(30, $result['stats']['s99']);
    }

    public function testExecuteCalculatesTotalCorrectly(): void
    {
        $textInfo = [
            'title' => 'Text',
            'language_id' => 1,
            'language_name' => 'Spanish',
            'annotated' => false
        ];

        $this->statsService->method('getTextWordCount')
            ->willReturn([
                'total' => [],
                'expr' => [],
                'stat' => [],
                'totalu' => [],
                'expru' => [],
                'statu' => [
                    10 => [
                        1 => 10,
                        2 => 10,
                        3 => 10,
                        4 => 10,
                        5 => 10,
                        98 => 10,
                        99 => 10
                    ]
                ]
            ]);

        $this->statsService->method('getTodoWordsCount')
            ->willReturn(30);

        $result = $this->useCase->execute(10, $textInfo);

        // Total should be: 30 (unknown) + 10*7 (statuses) = 100
        $this->assertEquals(100, $result['stats']['total']);
    }

    public function testExecuteHandlesMissingStatusCounts(): void
    {
        $textInfo = [
            'title' => 'Text',
            'language_id' => 1,
            'language_name' => 'Italian',
            'annotated' => false
        ];

        // Empty stats - no words in any status
        $this->statsService->method('getTextWordCount')
            ->willReturn([
                'total' => [],
                'expr' => [],
                'stat' => [],
                'totalu' => [],
                'expru' => [],
                'statu' => []  // No status data
            ]);

        $this->statsService->method('getTodoWordsCount')
            ->willReturn(0);

        $result = $this->useCase->execute(1, $textInfo);

        // All status counts should default to 0
        $this->assertEquals(0, $result['stats']['unknown']);
        $this->assertEquals(0, $result['stats']['s1']);
        $this->assertEquals(0, $result['stats']['s2']);
        $this->assertEquals(0, $result['stats']['s3']);
        $this->assertEquals(0, $result['stats']['s4']);
        $this->assertEquals(0, $result['stats']['s5']);
        $this->assertEquals(0, $result['stats']['s98']);
        $this->assertEquals(0, $result['stats']['s99']);
        $this->assertEquals(0, $result['stats']['total']);
    }

    public function testExecutePreservesTextInfo(): void
    {
        $textInfo = [
            'title' => 'Complex Title with "Quotes"',
            'language_id' => 42,
            'language_name' => 'Portuguese (Brazil)',
            'annotated' => true
        ];

        $this->statsService->method('getTextWordCount')
            ->willReturn(['total' => [], 'expr' => [], 'stat' => [], 'totalu' => [], 'expru' => [], 'statu' => []]);

        $this->statsService->method('getTodoWordsCount')
            ->willReturn(0);

        $result = $this->useCase->execute(99, $textInfo);

        $this->assertEquals('Complex Title with "Quotes"', $result['title']);
        $this->assertEquals(42, $result['language_id']);
        $this->assertEquals('Portuguese (Brazil)', $result['language_name']);
        $this->assertTrue($result['annotated']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteHandlesPartialStatusData(): void
    {
        $textInfo = [
            'title' => 'Text',
            'language_id' => 1,
            'language_name' => 'Japanese',
            'annotated' => false
        ];

        // Only some statuses have data
        $this->statsService->method('getTextWordCount')
            ->willReturn([
                'total' => [],
                'expr' => [],
                'stat' => [],
                'totalu' => [],
                'expru' => [],
                'statu' => [
                    1 => [
                        1 => 5,
                        // 2, 3, 4 missing
                        5 => 10,
                        // 98 missing
                        99 => 50
                    ]
                ]
            ]);

        $this->statsService->method('getTodoWordsCount')
            ->willReturn(20);

        $result = $this->useCase->execute(1, $textInfo);

        $this->assertEquals(20, $result['stats']['unknown']);
        $this->assertEquals(5, $result['stats']['s1']);
        $this->assertEquals(0, $result['stats']['s2']);  // Missing, should be 0
        $this->assertEquals(0, $result['stats']['s3']);  // Missing, should be 0
        $this->assertEquals(0, $result['stats']['s4']);  // Missing, should be 0
        $this->assertEquals(10, $result['stats']['s5']);
        $this->assertEquals(0, $result['stats']['s98']); // Missing, should be 0
        $this->assertEquals(50, $result['stats']['s99']);
    }

    public function testExecuteCallsServiceWithCorrectTextId(): void
    {
        $textInfo = [
            'title' => 'Text',
            'language_id' => 1,
            'language_name' => 'Test',
            'annotated' => false
        ];

        $this->statsService->expects($this->once())
            ->method('getTextWordCount')
            ->with('12345')  // String conversion of text ID
            ->willReturn(['total' => [], 'expr' => [], 'stat' => [], 'totalu' => [], 'expru' => [], 'statu' => []]);

        $this->statsService->expects($this->once())
            ->method('getTodoWordsCount')
            ->with(12345)  // Integer text ID
            ->willReturn(0);

        $this->useCase->execute(12345, $textInfo);
    }

    public function testExecuteReturnsArrayWithExpectedKeys(): void
    {
        $textInfo = [
            'title' => 'Text',
            'language_id' => 1,
            'language_name' => 'Test',
            'annotated' => false
        ];

        $this->statsService->method('getTextWordCount')
            ->willReturn(['total' => [], 'expr' => [], 'stat' => [], 'totalu' => [], 'expru' => [], 'statu' => []]);

        $this->statsService->method('getTodoWordsCount')
            ->willReturn(0);

        $result = $this->useCase->execute(1, $textInfo);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('language_id', $result);
        $this->assertArrayHasKey('language_name', $result);
        $this->assertArrayHasKey('annotated', $result);
        $this->assertArrayHasKey('stats', $result);

        // Stats sub-array keys
        $this->assertArrayHasKey('unknown', $result['stats']);
        $this->assertArrayHasKey('s1', $result['stats']);
        $this->assertArrayHasKey('s2', $result['stats']);
        $this->assertArrayHasKey('s3', $result['stats']);
        $this->assertArrayHasKey('s4', $result['stats']);
        $this->assertArrayHasKey('s5', $result['stats']);
        $this->assertArrayHasKey('s98', $result['stats']);
        $this->assertArrayHasKey('s99', $result['stats']);
        $this->assertArrayHasKey('total', $result['stats']);
    }
}
