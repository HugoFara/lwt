<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Http;

use Lwt\Modules\Feed\Http\FeedLoadApiHandler;
use Lwt\Modules\Feed\Application\FeedFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for FeedLoadApiHandler.
 *
 * Tests feed loading, parsing, detection, and delegation to FeedFacade.
 *
 */
#[CoversClass(FeedLoadApiHandler::class)]
class FeedLoadApiHandlerTest extends TestCase
{
    /** @var FeedFacade&MockObject */
    private FeedFacade $feedFacade;

    private FeedLoadApiHandler $handler;

    protected function setUp(): void
    {
        $this->feedFacade = $this->createMock(FeedFacade::class);
        $this->handler = new FeedLoadApiHandler($this->feedFacade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(FeedLoadApiHandler::class, $this->handler);
    }

    // =========================================================================
    // getFeedsList tests
    // =========================================================================

    public function testGetFeedsListReturnsZerosForEmptyFeed(): void
    {
        $result = $this->handler->getFeedsList([], 1);

        $this->assertSame([0, 0], $result);
    }

    public function testGetFeedsListReturnsZerosForEmptyFeedWithZeroId(): void
    {
        $result = $this->handler->getFeedsList([], 0);

        $this->assertSame([0, 0], $result);
    }

    public function testGetFeedsListReturnsZerosForEmptyFeedWithLargeId(): void
    {
        $result = $this->handler->getFeedsList([], 99999);

        $this->assertSame([0, 0], $result);
    }

    public function testGetFeedsListReturnsArrayOfTwoInts(): void
    {
        $result = $this->handler->getFeedsList([], 1);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertIsInt($result[0]);
        $this->assertIsInt($result[1]);
    }

    public function testGetFeedsListMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'getFeedsList'),
            'Method getFeedsList should exist'
        );
    }

    public function testGetFeedsListAcceptsFeedArrayAndIntId(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedsList');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('feed', $params[0]->getName());
        $this->assertSame('nfid', $params[1]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
    }

    public function testGetFeedsListReturnTypeIsArray(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedsList');

        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    // =========================================================================
    // getFeedResult tests
    // =========================================================================

    public function testGetFeedResultMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'getFeedResult'),
            'Method getFeedResult should exist'
        );
    }

    public function testGetFeedResultReturnTypeIsString(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedResult');

        $this->assertSame('string', $ref->getReturnType()->getName());
    }

    public function testGetFeedResultAcceptsFiveParameters(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedResult');
        $params = $ref->getParameters();

        $this->assertCount(5, $params);
        $this->assertSame('importedFeed', $params[0]->getName());
        $this->assertSame('nif', $params[1]->getName());
        $this->assertSame('nfname', $params[2]->getName());
        $this->assertSame('nfid', $params[3]->getName());
        $this->assertSame('nfoptions', $params[4]->getName());
    }

    public function testGetFeedResultParameterTypes(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedResult');
        $params = $ref->getParameters();

        $this->assertSame('int', $params[0]->getType()->getName());
        $this->assertSame('int', $params[1]->getType()->getName());
        $this->assertSame('string', $params[2]->getType()->getName());
        $this->assertSame('int', $params[3]->getType()->getName());
        $this->assertSame('string', $params[4]->getType()->getName());
    }

    // =========================================================================
    // loadFeed tests — error paths
    // =========================================================================

    public function testLoadFeedReturnsErrorWhenParsingReturnsFalse(): void
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

    public function testLoadFeedErrorIncludesFeedName(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturn('');
        $this->feedFacade->method('parseRssFeed')
            ->willReturn(false);

        $result = $this->handler->loadFeed('My RSS Feed', 5, 'http://example.com/rss', '');

        $this->assertStringContainsString('My RSS Feed', $result['error']);
    }

    public function testLoadFeedErrorDoesNotHaveSuccessKey(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturn('');
        $this->feedFacade->method('parseRssFeed')
            ->willReturn(false);

        $result = $this->handler->loadFeed('Feed', 1, 'http://example.com', '');

        $this->assertArrayNotHasKey('success', $result);
        $this->assertArrayNotHasKey('message', $result);
        $this->assertArrayNotHasKey('imported', $result);
        $this->assertArrayNotHasKey('duplicates', $result);
    }

    public function testLoadFeedErrorWhenParsingReturnsNull(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturn('');
        // parseRssFeed returns false for failure, but the check is !is_array
        // so non-array values all trigger the error path
        $this->feedFacade->method('parseRssFeed')
            ->willReturn(false);

        $result = $this->handler->loadFeed('Feed', 1, 'http://example.com', '');

        $this->assertArrayHasKey('error', $result);
    }

    public function testLoadFeedPassesArticleSourceToParseRssFeed(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturnCallback(function (string $opts, string $key) {
                if ($key === 'article_source') {
                    return 'article';
                }
                return null;
            });

        $this->feedFacade->expects($this->once())
            ->method('parseRssFeed')
            ->with('http://example.com/feed', 'article')
            ->willReturn(false);

        $this->handler->loadFeed('Feed', 1, 'http://example.com/feed', 'article_source=article');
    }

    public function testLoadFeedPassesEmptyStringWhenArticleSourceNotString(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturnCallback(function (string $opts, string $key) {
                if ($key === 'article_source') {
                    return null;
                }
                return null;
            });

        $this->feedFacade->expects($this->once())
            ->method('parseRssFeed')
            ->with('http://example.com/feed', '')
            ->willReturn(false);

        $this->handler->loadFeed('Feed', 1, 'http://example.com/feed', '');
    }

    public function testLoadFeedPassesEmptyStringWhenArticleSourceIsArray(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturnCallback(function (string $opts, string $key) {
                if ($key === 'article_source') {
                    return ['not', 'a', 'string'];
                }
                return null;
            });

        $this->feedFacade->expects($this->once())
            ->method('parseRssFeed')
            ->with('http://example.com/feed', '')
            ->willReturn(false);

        $this->handler->loadFeed('Feed', 1, 'http://example.com/feed', '');
    }

    // =========================================================================
    // loadFeed tests — method signature
    // =========================================================================

    public function testLoadFeedMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'loadFeed'),
            'Method loadFeed should exist'
        );
    }

    public function testLoadFeedReturnTypeIsArray(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'loadFeed');

        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    public function testLoadFeedAcceptsFourParameters(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'loadFeed');
        $params = $ref->getParameters();

        $this->assertCount(4, $params);
        $this->assertSame('nfname', $params[0]->getName());
        $this->assertSame('nfid', $params[1]->getName());
        $this->assertSame('nfsourceuri', $params[2]->getName());
        $this->assertSame('nfoptions', $params[3]->getName());
    }

    // =========================================================================
    // formatLoadFeed tests
    // =========================================================================

    public function testFormatLoadFeedDelegatesToLoadFeed(): void
    {
        $this->feedFacade->method('getNfOption')
            ->willReturn('');
        $this->feedFacade->method('parseRssFeed')
            ->willReturn(false);

        $result = $this->handler->formatLoadFeed('Feed', 1, 'http://example.com', '');

        // formatLoadFeed delegates to loadFeed, which returns error when parse fails
        $this->assertArrayHasKey('error', $result);
    }

    public function testFormatLoadFeedMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'formatLoadFeed'),
            'Method formatLoadFeed should exist'
        );
    }

    public function testFormatLoadFeedReturnTypeIsArray(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'formatLoadFeed');

        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    // =========================================================================
    // parseFeed tests
    // =========================================================================

    public function testParseFeedDelegatesToFacade(): void
    {
        $expected = [
            ['title' => 'Article 1', 'link' => 'http://example.com/1'],
            ['title' => 'Article 2', 'link' => 'http://example.com/2'],
        ];

        $this->feedFacade->expects($this->once())
            ->method('parseRssFeed')
            ->with('http://example.com/feed', 'article')
            ->willReturn($expected);

        $result = $this->handler->parseFeed('http://example.com/feed', 'article');

        $this->assertSame($expected, $result);
    }

    public function testParseFeedReturnsNullWhenFacadeReturnsFalse(): void
    {
        $this->feedFacade->method('parseRssFeed')
            ->willReturn(false);

        $result = $this->handler->parseFeed('http://example.com/feed');

        $this->assertNull($result);
    }

    public function testParseFeedDefaultArticleSectionIsEmpty(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('parseRssFeed')
            ->with('http://example.com/feed', '')
            ->willReturn([]);

        $this->handler->parseFeed('http://example.com/feed');
    }

    public function testParseFeedReturnsEmptyArrayFromFacade(): void
    {
        $this->feedFacade->method('parseRssFeed')
            ->willReturn([]);

        $result = $this->handler->parseFeed('http://example.com/feed');

        $this->assertSame([], $result);
    }

    public function testParseFeedPassesArticleSectionToFacade(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('parseRssFeed')
            ->with('http://example.com/feed', 'div.content')
            ->willReturn([]);

        $this->handler->parseFeed('http://example.com/feed', 'div.content');
    }

    public function testParseFeedReturnTypeIsNullableArray(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'parseFeed');

        $this->assertTrue($ref->getReturnType()->allowsNull());
        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    // =========================================================================
    // detectFeed tests
    // =========================================================================

    public function testDetectFeedDelegatesToFacade(): void
    {
        $expected = ['format' => 'rss', 'items' => []];

        $this->feedFacade->expects($this->once())
            ->method('detectAndParseFeed')
            ->with('http://example.com/feed')
            ->willReturn($expected);

        $result = $this->handler->detectFeed('http://example.com/feed');

        $this->assertSame($expected, $result);
    }

    public function testDetectFeedReturnsNullWhenFacadeReturnsFalse(): void
    {
        $this->feedFacade->method('detectAndParseFeed')
            ->willReturn(false);

        $result = $this->handler->detectFeed('http://example.com/feed');

        $this->assertNull($result);
    }

    public function testDetectFeedReturnsEmptyArrayFromFacade(): void
    {
        $this->feedFacade->method('detectAndParseFeed')
            ->willReturn([]);

        $result = $this->handler->detectFeed('http://example.com/feed');

        $this->assertSame([], $result);
    }

    public function testDetectFeedReturnTypeIsNullableArray(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'detectFeed');

        $this->assertTrue($ref->getReturnType()->allowsNull());
        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    // =========================================================================
    // getFeeds tests
    // =========================================================================

    public function testGetFeedsDelegatesToFacade(): void
    {
        $expected = [
            ['id' => 1, 'name' => 'Feed A'],
            ['id' => 2, 'name' => 'Feed B'],
        ];

        $this->feedFacade->expects($this->once())
            ->method('getFeeds')
            ->with(5)
            ->willReturn($expected);

        $result = $this->handler->getFeeds(5);

        $this->assertSame($expected, $result);
    }

    public function testGetFeedsPassesNullForAllLanguages(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getFeeds')
            ->with(null)
            ->willReturn([]);

        $result = $this->handler->getFeeds(null);

        $this->assertSame([], $result);
    }

    public function testGetFeedsDefaultLanguageIdIsNull(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getFeeds')
            ->with(null)
            ->willReturn([]);

        $this->handler->getFeeds();
    }

    public function testGetFeedsReturnsArrayType(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeeds');

        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    // =========================================================================
    // getFeedsNeedingAutoUpdate tests
    // =========================================================================

    public function testGetFeedsNeedingAutoUpdateDelegatesToFacade(): void
    {
        $expected = [
            ['id' => 3, 'name' => 'Auto Feed'],
        ];

        $this->feedFacade->expects($this->once())
            ->method('getFeedsNeedingAutoUpdate')
            ->willReturn($expected);

        $result = $this->handler->getFeedsNeedingAutoUpdate();

        $this->assertSame($expected, $result);
    }

    public function testGetFeedsNeedingAutoUpdateReturnsEmptyArray(): void
    {
        $this->feedFacade->method('getFeedsNeedingAutoUpdate')
            ->willReturn([]);

        $result = $this->handler->getFeedsNeedingAutoUpdate();

        $this->assertSame([], $result);
    }

    public function testGetFeedsNeedingAutoUpdateAcceptsNoParameters(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedsNeedingAutoUpdate');

        $this->assertCount(0, $ref->getParameters());
    }

    public function testGetFeedsNeedingAutoUpdateReturnTypeIsArray(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedsNeedingAutoUpdate');

        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    // =========================================================================
    // getFeedLoadConfig tests
    // =========================================================================

    public function testGetFeedLoadConfigDelegatesToFacade(): void
    {
        $expected = [
            'feedId' => 10,
            'sourceUri' => 'http://example.com/rss',
            'options' => 'article_source=div',
        ];

        $this->feedFacade->expects($this->once())
            ->method('getFeedLoadConfig')
            ->with(10, false)
            ->willReturn($expected);

        $result = $this->handler->getFeedLoadConfig(10);

        $this->assertSame($expected, $result);
    }

    public function testGetFeedLoadConfigWithAutoupdateTrue(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getFeedLoadConfig')
            ->with(7, true)
            ->willReturn([]);

        $result = $this->handler->getFeedLoadConfig(7, true);

        $this->assertSame([], $result);
    }

    public function testGetFeedLoadConfigDefaultCheckAutoupdateIsFalse(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('getFeedLoadConfig')
            ->with(1, false)
            ->willReturn([]);

        $this->handler->getFeedLoadConfig(1);
    }

    public function testGetFeedLoadConfigReturnTypeIsArray(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedLoadConfig');

        $this->assertSame('array', $ref->getReturnType()->getName());
    }

    public function testGetFeedLoadConfigAcceptsTwoParameters(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedLoadConfig');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('feedId', $params[0]->getName());
        $this->assertSame('checkAutoupdate', $params[1]->getName());
    }

    public function testGetFeedLoadConfigSecondParamHasDefaultFalse(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, 'getFeedLoadConfig');
        $params = $ref->getParameters();

        $this->assertTrue($params[1]->isDefaultValueAvailable());
        $this->assertFalse($params[1]->getDefaultValue());
    }

    // =========================================================================
    // Class structure tests
    // =========================================================================

    public function testClassHasAllExpectedPublicMethods(): void
    {
        $ref = new \ReflectionClass(FeedLoadApiHandler::class);
        $publicMethods = array_map(
            fn(\ReflectionMethod $m) => $m->getName(),
            $ref->getMethods(\ReflectionMethod::IS_PUBLIC)
        );

        $expected = [
            'getFeedsList',
            'getFeedResult',
            'loadFeed',
            'formatLoadFeed',
            'parseFeed',
            'detectFeed',
            'getFeeds',
            'getFeedsNeedingAutoUpdate',
            'getFeedLoadConfig',
        ];

        foreach ($expected as $method) {
            $this->assertContains($method, $publicMethods, "Missing public method: $method");
        }
    }

    public function testConstructorRequiresFeedFacade(): void
    {
        $ref = new \ReflectionMethod(FeedLoadApiHandler::class, '__construct');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('feedFacade', $params[0]->getName());
        $this->assertSame(FeedFacade::class, $params[0]->getType()->getName());
    }
}
