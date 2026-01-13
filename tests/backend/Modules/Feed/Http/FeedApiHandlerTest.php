<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Http;

use Lwt\Modules\Feed\Http\FeedApiHandler;
use Lwt\Modules\Feed\Application\FeedFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for FeedApiHandler.
 *
 * Tests feed API operations including CRUD, articles, and import functionality.
 */
class FeedApiHandlerTest extends TestCase
{
    /** @var FeedFacade&MockObject */
    private FeedFacade $feedFacade;

    private FeedApiHandler $handler;

    protected function setUp(): void
    {
        $this->feedFacade = $this->createMock(FeedFacade::class);
        $this->handler = new FeedApiHandler($this->feedFacade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(FeedApiHandler::class, $this->handler);
    }

    // =========================================================================
    // getFeedsList tests
    // =========================================================================

    public function testGetFeedsListReturnsZerosForEmptyFeed(): void
    {
        $result = $this->handler->getFeedsList([], 1);

        $this->assertSame([0, 0], $result);
    }

    // =========================================================================
    // loadFeed tests
    // =========================================================================

    public function testLoadFeedReturnsErrorWhenParsingFails(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturn('');
        $this->feedFacade->method('parseRssFeed')
            ->willReturn(false);

        $result = $this->handler->loadFeed('Test Feed', 1, 'http://example.com/feed', '');

        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Could not load', $result['error']);
    }

    public function testLoadFeedReturnsErrorWhenParsingReturnsEmptyArray(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturn('');
        $this->feedFacade->method('parseRssFeed')
            ->willReturn([]);

        $result = $this->handler->loadFeed('Test Feed', 1, 'http://example.com/feed', '');

        $this->assertArrayHasKey('error', $result);
    }

    // =========================================================================
    // createFeed tests
    // =========================================================================

    public function testCreateFeedReturnsErrorWhenLanguageIdMissing(): void
    {
        $result = $this->handler->createFeed([
            'name' => 'Test Feed',
            'sourceUri' => 'http://example.com/feed'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedReturnsErrorWhenLanguageIdZero(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 0,
            'name' => 'Test Feed',
            'sourceUri' => 'http://example.com/feed'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedReturnsErrorWhenNameEmpty(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => '',
            'sourceUri' => 'http://example.com/feed'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed name is required', $result['error']);
    }

    public function testCreateFeedReturnsErrorWhenNameOnlyWhitespace(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => '   ',
            'sourceUri' => 'http://example.com/feed'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed name is required', $result['error']);
    }

    public function testCreateFeedReturnsErrorWhenSourceUriEmpty(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Test Feed',
            'sourceUri' => ''
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Source URI is required', $result['error']);
    }

    public function testCreateFeedReturnsErrorWhenSourceUriMissing(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Test Feed'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Source URI is required', $result['error']);
    }

    // =========================================================================
    // updateFeed tests
    // =========================================================================

    public function testUpdateFeedReturnsErrorWhenFeedNotFound(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn(null);

        $result = $this->handler->updateFeed(999, ['name' => 'Updated']);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed not found', $result['error']);
    }

    // =========================================================================
    // deleteFeeds tests
    // =========================================================================

    public function testDeleteFeedsReturnsFailureForEmptyArray(): void
    {
        $result = $this->handler->deleteFeeds([]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['deleted']);
    }

    public function testDeleteFeedsCallsFacadeWithFormattedIds(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('1,2,3')
            ->willReturn(['feeds' => 3]);

        $result = $this->handler->deleteFeeds([1, 2, 3]);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['deleted']);
    }

    public function testDeleteFeedsSanitizesIdValues(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('1,2,0')
            ->willReturn(['feeds' => 2]);

        $result = $this->handler->deleteFeeds(['1', '2', 'invalid']);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // getFeed tests
    // =========================================================================

    public function testGetFeedReturnsErrorWhenNotFound(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn(null);

        $result = $this->handler->getFeed(999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed not found', $result['error']);
    }

    // =========================================================================
    // getArticles tests
    // =========================================================================

    public function testGetArticlesReturnsErrorWhenFeedIdMissing(): void
    {
        $result = $this->handler->getArticles([]);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed ID is required', $result['error']);
    }

    public function testGetArticlesReturnsErrorWhenFeedIdZero(): void
    {
        $result = $this->handler->getArticles(['feed_id' => 0]);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed ID is required', $result['error']);
    }

    public function testGetArticlesReturnsErrorWhenFeedNotFound(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn(null);

        $result = $this->handler->getArticles(['feed_id' => 999]);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed not found', $result['error']);
    }

    // =========================================================================
    // importArticles tests
    // =========================================================================

    public function testImportArticlesReturnsErrorWhenNoArticlesSelected(): void
    {
        $result = $this->handler->importArticles([]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertContains('No articles selected', $result['errors']);
    }

    public function testImportArticlesReturnsErrorWhenArticleIdsEmpty(): void
    {
        $result = $this->handler->importArticles(['article_ids' => []]);

        $this->assertFalse($result['success']);
        $this->assertContains('No articles selected', $result['errors']);
    }

    public function testImportArticlesReturnsErrorWhenArticleIdsNotArray(): void
    {
        $result = $this->handler->importArticles(['article_ids' => 'not-array']);

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // resetErrorArticles tests
    // =========================================================================

    public function testResetErrorArticlesCallsFacade(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('resetUnloadableArticles')
            ->with('5')
            ->willReturn(3);

        $result = $this->handler->resetErrorArticles(5);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['reset']);
    }

    // =========================================================================
    // parseFeed tests
    // =========================================================================

    public function testParseFeedReturnsNullOnFailure(): void
    {
        $this->feedFacade->method('parseRssFeed')
            ->willReturn(false);

        $result = $this->handler->parseFeed('http://example.com/feed');

        $this->assertNull($result);
    }

    public function testParseFeedReturnsArrayOnSuccess(): void
    {
        $feedData = [
            ['title' => 'Article 1', 'link' => 'http://example.com/1'],
            ['title' => 'Article 2', 'link' => 'http://example.com/2']
        ];
        $this->feedFacade->method('parseRssFeed')
            ->willReturn($feedData);

        $result = $this->handler->parseFeed('http://example.com/feed');

        $this->assertSame($feedData, $result);
    }

    public function testParseFeedPassesArticleSectionToFacade(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('parseRssFeed')
            ->with('http://example.com/feed', 'article.content')
            ->willReturn([]);

        $this->handler->parseFeed('http://example.com/feed', 'article.content');
    }

    // =========================================================================
    // detectFeed tests
    // =========================================================================

    public function testDetectFeedReturnsNullOnFailure(): void
    {
        $this->feedFacade->method('detectAndParseFeed')
            ->willReturn(false);

        $result = $this->handler->detectFeed('http://example.com/feed');

        $this->assertNull($result);
    }

    public function testDetectFeedReturnsArrayOnSuccess(): void
    {
        $feedData = ['type' => 'rss', 'items' => []];
        $this->feedFacade->method('detectAndParseFeed')
            ->willReturn($feedData);

        $result = $this->handler->detectFeed('http://example.com/feed');

        $this->assertSame($feedData, $result);
    }

    // =========================================================================
    // getFeeds tests
    // =========================================================================

    public function testGetFeedsCallsFacadeWithoutLanguageId(): void
    {
        $feeds = [['NfID' => 1, 'NfName' => 'Feed 1']];
        $this->feedFacade->expects($this->once())
            ->method('getFeeds')
            ->with(null)
            ->willReturn($feeds);

        $result = $this->handler->getFeeds();

        $this->assertSame($feeds, $result);
    }

    public function testGetFeedsCallsFacadeWithLanguageId(): void
    {
        $feeds = [['NfID' => 1, 'NfName' => 'Feed 1']];
        $this->feedFacade->expects($this->once())
            ->method('getFeeds')
            ->with(5)
            ->willReturn($feeds);

        $result = $this->handler->getFeeds(5);

        $this->assertSame($feeds, $result);
    }

    // =========================================================================
    // getFeedsNeedingAutoUpdate tests
    // =========================================================================

    public function testGetFeedsNeedingAutoUpdateCallsFacade(): void
    {
        $feeds = [['NfID' => 1], ['NfID' => 2]];
        $this->feedFacade->expects($this->once())
            ->method('getFeedsNeedingAutoUpdate')
            ->willReturn($feeds);

        $result = $this->handler->getFeedsNeedingAutoUpdate();

        $this->assertSame($feeds, $result);
    }

    // =========================================================================
    // getFeedLoadConfig tests
    // =========================================================================

    public function testGetFeedLoadConfigCallsFacadeWithDefaults(): void
    {
        $config = ['feedId' => 1, 'autoUpdate' => false];
        $this->feedFacade->expects($this->once())
            ->method('getFeedLoadConfig')
            ->with(1, false)
            ->willReturn($config);

        $result = $this->handler->getFeedLoadConfig(1);

        $this->assertSame($config, $result);
    }

    public function testGetFeedLoadConfigPassesAutoUpdateFlag(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getFeedLoadConfig')
            ->with(1, true)
            ->willReturn([]);

        $this->handler->getFeedLoadConfig(1, true);
    }

    // =========================================================================
    // Format method tests (thin wrappers)
    // =========================================================================

    public function testFormatLoadFeedDelegatesToLoadFeed(): void
    {
        $this->feedFacade->method('getNfOption')->willReturn('');
        $this->feedFacade->method('parseRssFeed')->willReturn(false);

        $result = $this->handler->formatLoadFeed('Test', 1, 'http://test.com', '');

        $this->assertArrayHasKey('error', $result);
    }

    public function testFormatDeleteFeedsDelegatesToDeleteFeeds(): void
    {
        $result = $this->handler->formatDeleteFeeds([]);

        $this->assertFalse($result['success']);
    }

    public function testFormatImportArticlesDelegatesToImportArticles(): void
    {
        $result = $this->handler->formatImportArticles([]);

        $this->assertFalse($result['success']);
    }

    public function testFormatResetErrorArticlesDelegatesToResetErrorArticles(): void
    {
        $this->feedFacade->method('resetUnloadableArticles')->willReturn(0);

        $result = $this->handler->formatResetErrorArticles(1);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // deleteArticles tests
    // =========================================================================

    public function testDeleteArticlesCallsFacadeWhenArticleIdsEmpty(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteArticles')
            ->with('5')
            ->willReturn(10);

        $result = $this->handler->deleteArticles(5, []);

        $this->assertTrue($result['success']);
        $this->assertSame(10, $result['deleted']);
    }

    // =========================================================================
    // Additional format method tests
    // =========================================================================

    public function testFormatGetFeedListDelegatesToGetFeedList(): void
    {
        // This will fail without DB, but tests structure
        $result = $this->handler->formatGetFeedList([]);

        $this->assertIsArray($result);
    }

    public function testFormatGetFeedDelegatesToGetFeed(): void
    {
        $this->feedFacade->method('getFeedById')->willReturn(null);

        $result = $this->handler->formatGetFeed(999);

        $this->assertArrayHasKey('error', $result);
    }

    public function testFormatCreateFeedDelegatesToCreateFeed(): void
    {
        $result = $this->handler->formatCreateFeed([]);

        $this->assertFalse($result['success']);
    }

    public function testFormatUpdateFeedDelegatesToUpdateFeed(): void
    {
        $this->feedFacade->method('getFeedById')->willReturn(null);

        $result = $this->handler->formatUpdateFeed(999, []);

        $this->assertFalse($result['success']);
    }

    public function testFormatGetArticlesDelegatesToGetArticles(): void
    {
        $result = $this->handler->formatGetArticles([]);

        $this->assertArrayHasKey('error', $result);
    }

    public function testFormatDeleteArticlesDelegatesToDeleteArticles(): void
    {
        $this->feedFacade->method('deleteArticles')->willReturn(0);

        $result = $this->handler->formatDeleteArticles(1, []);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // createFeed additional validation tests
    // =========================================================================

    public function testCreateFeedCallsFacadeWithValidData(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('createFeed')
            ->willReturn(123);
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => 123,
                'NfName' => 'Test Feed',
                'NfSourceURI' => 'http://example.com/feed',
                'NfLgID' => 1,
                'NfArticleSectionTags' => '',
                'NfFilterTags' => '',
                'NfOptions' => '',
                'NfUpdate' => 0,
            ]);
        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('never');

        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Test Feed',
            'sourceUri' => 'http://example.com/feed'
        ]);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // updateFeed additional tests
    // =========================================================================

    public function testUpdateFeedCallsFacadeWithPartialData(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => 1,
                'NfName' => 'Old Name',
                'NfSourceURI' => 'http://old.com',
                'NfLgID' => 1,
                'NfArticleSectionTags' => '',
                'NfFilterTags' => '',
                'NfOptions' => '',
                'NfUpdate' => 0,
            ]);
        $this->feedFacade->expects($this->once())
            ->method('updateFeed');
        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('never');

        $result = $this->handler->updateFeed(1, ['name' => 'New Name']);

        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // importArticles additional tests
    // =========================================================================

    public function testImportArticlesCallsFacadeWithArticleIds(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getMarkedFeedLinks')
            ->with('1,2,3')
            ->willReturn([]);

        $result = $this->handler->importArticles(['article_ids' => [1, 2, 3]]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['imported']);
    }

}
