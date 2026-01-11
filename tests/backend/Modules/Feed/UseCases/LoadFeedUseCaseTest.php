<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\UseCases;

use Lwt\Modules\Feed\Application\Services\RssParser;
use Lwt\Modules\Feed\Application\UseCases\LoadFeed;
use Lwt\Modules\Feed\Domain\ArticleRepositoryInterface;
use Lwt\Modules\Feed\Domain\Feed;
use Lwt\Modules\Feed\Domain\FeedRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the LoadFeed use case.
 *
 * Tests feed loading/refreshing including RSS parsing, article insertion,
 * duplicate handling, and timestamp updates.
 *
 * @covers \Lwt\Modules\Feed\Application\UseCases\LoadFeed
 */
class LoadFeedUseCaseTest extends TestCase
{
    /** @var FeedRepositoryInterface&MockObject */
    private FeedRepositoryInterface $feedRepository;

    /** @var ArticleRepositoryInterface&MockObject */
    private ArticleRepositoryInterface $articleRepository;

    /** @var RssParser&MockObject */
    private RssParser $rssParser;

    private LoadFeed $loadFeed;

    protected function setUp(): void
    {
        $this->feedRepository = $this->createMock(FeedRepositoryInterface::class);
        $this->articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $this->rssParser = $this->createMock(RssParser::class);

        $this->loadFeed = new LoadFeed(
            $this->feedRepository,
            $this->articleRepository,
            $this->rssParser
        );
    }

    // =====================
    // FEED NOT FOUND TESTS
    // =====================

    public function testExecuteWithNonExistentFeedReturnsError(): void
    {
        $this->feedRepository
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->loadFeed->execute(999);

        $this->assertFalse($result['success']);
        $this->assertNull($result['feed']);
        $this->assertEquals('Feed not found', $result['error']);
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['duplicates']);
    }

    // =====================
    // RSS PARSING ERROR TESTS
    // =====================

    public function testExecuteWithParsingFailureReturnsError(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');

        $this->feedRepository
            ->method('find')
            ->with(1)
            ->willReturn($feed);

        // RSS parser returns null (failure)
        $this->rssParser
            ->method('parse')
            ->willReturn(null);

        $result = $this->loadFeed->execute(1);

        $this->assertFalse($result['success']);
        $this->assertSame($feed, $result['feed']);
        $this->assertEquals('Failed to parse RSS feed', $result['error']);
    }

    // =====================
    // SUCCESSFUL LOAD TESTS
    // =====================

    public function testExecuteWithEmptyFeedReturnsSuccess(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        // RSS parser returns empty array
        $this->rssParser
            ->method('parse')
            ->willReturn([]);

        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 0, 'duplicates' => 0]);

        $this->feedRepository
            ->expects($this->once())
            ->method('updateTimestamp')
            ->with(1, $this->anything());

        $result = $this->loadFeed->execute(1);

        $this->assertTrue($result['success']);
        $this->assertSame($feed, $result['feed']);
        $this->assertEquals(0, $result['inserted']);
        $this->assertEquals(0, $result['duplicates']);
        $this->assertNull($result['error']);
    }

    public function testExecuteWithArticlesInsertsThem(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->rssParser
            ->method('parse')
            ->willReturn([
                ['title' => 'Article 1', 'link' => 'http://example.com/1', 'desc' => 'Desc 1', 'date' => '2024-01-01', 'audio' => '', 'text' => ''],
                ['title' => 'Article 2', 'link' => 'http://example.com/2', 'desc' => 'Desc 2', 'date' => '2024-01-02', 'audio' => '', 'text' => ''],
                ['title' => 'Article 3', 'link' => 'http://example.com/3', 'desc' => 'Desc 3', 'date' => '2024-01-03', 'audio' => '', 'text' => ''],
            ]);

        $this->articleRepository
            ->expects($this->once())
            ->method('insertBatch')
            ->willReturn(['inserted' => 3, 'duplicates' => 0]);

        $this->feedRepository
            ->expects($this->once())
            ->method('updateTimestamp');

        $result = $this->loadFeed->execute(1);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['inserted']);
        $this->assertEquals(0, $result['duplicates']);
    }

    public function testExecuteWithDuplicatesCountsThem(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->rssParser
            ->method('parse')
            ->willReturn([
                ['title' => 'Article 1', 'link' => 'http://example.com/1', 'desc' => '', 'date' => '', 'audio' => '', 'text' => ''],
                ['title' => 'Article 2', 'link' => 'http://example.com/2', 'desc' => '', 'date' => '', 'audio' => '', 'text' => ''],
            ]);

        // One new, one duplicate
        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 1, 'duplicates' => 1]);

        $result = $this->loadFeed->execute(1);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['inserted']);
        $this->assertEquals(1, $result['duplicates']);
    }

    // =====================
    // FEED TEXT SECTION TESTS
    // =====================

    public function testExecutePassesFeedTextToParser(): void
    {
        $feedText = 'item_content';
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss', $feedText);

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->rssParser
            ->expects($this->once())
            ->method('parse')
            ->with('http://example.com/feed.rss', $feedText)
            ->willReturn([]);

        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 0, 'duplicates' => 0]);

        $this->loadFeed->execute(1);
    }

    // =====================
    // MULTIPLE FEEDS TESTS
    // =====================

    public function testExecuteMultipleLoadsMultipleFeeds(): void
    {
        $feed1 = $this->createMockFeed(1, 1, 'http://example.com/feed1.rss');
        $feed2 = $this->createMockFeed(2, 2, 'http://example.com/feed2.rss');

        $this->feedRepository
            ->method('find')
            ->willReturnCallback(fn($id) => $id === 1 ? $feed1 : ($id === 2 ? $feed2 : null));

        $this->rssParser
            ->method('parse')
            ->willReturn([
                ['title' => 'Article', 'link' => 'http://example.com/a', 'desc' => '', 'date' => '', 'audio' => '', 'text' => ''],
            ]);

        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 1, 'duplicates' => 0]);

        $results = $this->loadFeed->executeMultiple([1, 2]);

        $this->assertCount(2, $results);
        $this->assertTrue($results[1]['success']);
        $this->assertTrue($results[2]['success']);
    }

    public function testExecuteMultipleHandlesPartialFailures(): void
    {
        $feed1 = $this->createMockFeed(1, 1, 'http://example.com/feed1.rss');

        $this->feedRepository
            ->method('find')
            ->willReturnCallback(fn($id) => $id === 1 ? $feed1 : null);

        $this->rssParser
            ->method('parse')
            ->willReturn([]);

        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 0, 'duplicates' => 0]);

        $results = $this->loadFeed->executeMultiple([1, 999]);

        $this->assertTrue($results[1]['success']);
        $this->assertFalse($results[999]['success']);
    }

    // =====================
    // AUTO UPDATE TESTS
    // =====================

    public function testExecuteAutoUpdateLoadsNeedingUpdate(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');

        $this->feedRepository
            ->expects($this->once())
            ->method('findNeedingAutoUpdate')
            ->willReturn([$feed]);

        $this->rssParser
            ->method('parse')
            ->willReturn([]);

        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 0, 'duplicates' => 0]);

        $results = $this->loadFeed->executeAutoUpdate();

        $this->assertCount(1, $results);
        $this->assertTrue($results[1]['success']);
    }

    public function testExecuteAutoUpdateReturnsEmptyForNoUpdates(): void
    {
        $this->feedRepository
            ->method('findNeedingAutoUpdate')
            ->willReturn([]);

        $results = $this->loadFeed->executeAutoUpdate();

        $this->assertEmpty($results);
    }

    // =====================
    // TIMESTAMP UPDATE TESTS
    // =====================

    public function testExecuteUpdatesTimestampAfterLoad(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');
        $beforeTime = time();

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->rssParser
            ->method('parse')
            ->willReturn([]);

        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 0, 'duplicates' => 0]);

        $this->feedRepository
            ->expects($this->once())
            ->method('updateTimestamp')
            ->with(1, $this->callback(function ($timestamp) use ($beforeTime) {
                return $timestamp >= $beforeTime && $timestamp <= time();
            }));

        $this->loadFeed->execute(1);
    }

    // =====================
    // LOAD FEED ENTITY TESTS
    // =====================

    public function testLoadFeedDirectlyWithFeedEntity(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');

        $this->rssParser
            ->method('parse')
            ->willReturn([
                ['title' => 'Test', 'link' => 'http://example.com/test', 'desc' => '', 'date' => '', 'audio' => '', 'text' => ''],
            ]);

        $this->articleRepository
            ->method('insertBatch')
            ->willReturn(['inserted' => 1, 'duplicates' => 0]);

        $this->feedRepository
            ->expects($this->once())
            ->method('updateTimestamp');

        $result = $this->loadFeed->loadFeed($feed);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['inserted']);
    }

    // =====================
    // ARTICLE CONVERSION TESTS
    // =====================

    public function testExecuteConvertsRssItemsToArticles(): void
    {
        $feed = $this->createMockFeed(1, 1, 'http://example.com/feed.rss');

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->rssParser
            ->method('parse')
            ->willReturn([
                [
                    'title' => 'Test Article',
                    'link' => 'http://example.com/article',
                    'desc' => 'Article description',
                    'date' => '2024-01-15',
                    'audio' => 'http://example.com/audio.mp3',
                    'text' => 'Full article text',
                ],
            ]);

        $this->articleRepository
            ->expects($this->once())
            ->method('insertBatch')
            ->with(
                $this->callback(function ($articles) {
                    if (count($articles) !== 1) {
                        return false;
                    }
                    $article = $articles[0];
                    return $article->title() === 'Test Article'
                        && $article->link() === 'http://example.com/article'
                        && $article->description() === 'Article description';
                }),
                1
            )
            ->willReturn(['inserted' => 1, 'duplicates' => 0]);

        $this->loadFeed->execute(1);
    }

    // =====================
    // HELPER METHODS
    // =====================

    /**
     * Create a mock Feed object.
     */
    private function createMockFeed(
        int $id,
        int $langId,
        string $sourceUri,
        string $feedText = ''
    ): Feed {
        return Feed::reconstitute(
            $id,
            $langId,
            'Test Feed',
            $sourceUri,
            '//article',
            '',
            0,
            $feedText ? "feed_text={$feedText}" : ''
        );
    }
}
