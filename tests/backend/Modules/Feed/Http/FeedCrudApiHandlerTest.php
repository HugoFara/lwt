<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Http;

use Lwt\Modules\Feed\Http\FeedCrudApiHandler;
use Lwt\Modules\Feed\Application\FeedFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for FeedCrudApiHandler.
 *
 * @covers \Lwt\Modules\Feed\Http\FeedCrudApiHandler
 */
class FeedCrudApiHandlerTest extends TestCase
{
    /** @var FeedFacade&MockObject */
    private FeedFacade $feedFacade;

    private FeedCrudApiHandler $handler;

    protected function setUp(): void
    {
        $this->feedFacade = $this->createMock(FeedFacade::class);
        $this->handler = new FeedCrudApiHandler($this->feedFacade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(FeedCrudApiHandler::class, $this->handler);
    }

    public function testConstructorAcceptsFeedFacade(): void
    {
        $facade = $this->createMock(FeedFacade::class);
        $handler = new FeedCrudApiHandler($facade);
        $this->assertInstanceOf(FeedCrudApiHandler::class, $handler);
    }

    // =========================================================================
    // formatFeedRecord tests
    // =========================================================================

    public function testFormatFeedRecordBasicFields(): void
    {
        $row = $this->makeFeedRow();

        $this->feedFacade->method('getNfOption')
            ->willReturn(['opt1' => 'val1']);
        $this->feedFacade->method('formatLastUpdate')
            ->willReturn('last update: 5 minutes ago');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame(42, $result['id']);
        $this->assertSame('Test Feed', $result['name']);
        $this->assertSame('https://example.com/feed', $result['sourceUri']);
        $this->assertSame(1, $result['langId']);
        $this->assertSame('English', $result['langName']);
        $this->assertSame('article', $result['articleSectionTags']);
        $this->assertSame('div.content', $result['filterTags']);
        $this->assertSame(['opt1' => 'val1'], $result['options']);
        $this->assertSame('charset=utf8', $result['optionsString']);
        $this->assertSame(1700000000, $result['updateTimestamp']);
        $this->assertSame('last update: 5 minutes ago', $result['lastUpdate']);
        $this->assertSame(15, $result['articleCount']);
    }

    public function testFormatFeedRecordZeroUpdateTimestamp(): void
    {
        $row = $this->makeFeedRow(['NfUpdate' => 0]);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        // formatLastUpdate should NOT be called when timestamp is 0
        $this->feedFacade->expects($this->never())
            ->method('formatLastUpdate');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame('never', $result['lastUpdate']);
        $this->assertSame(0, $result['updateTimestamp']);
    }

    public function testFormatFeedRecordNullLangName(): void
    {
        $row = $this->makeFeedRow(['LgName' => null]);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('last update: 1 hour ago');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame('', $result['langName']);
    }

    public function testFormatFeedRecordNullArticleCount(): void
    {
        $row = $this->makeFeedRow();
        unset($row['articleCount']);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame(0, $result['articleCount']);
    }

    public function testFormatFeedRecordOptionsNonArray(): void
    {
        $row = $this->makeFeedRow();

        // When getNfOption returns a string instead of array
        $this->feedFacade->method('getNfOption')
            ->willReturn('some_string');
        $this->feedFacade->method('formatLastUpdate')
            ->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame([], $result['options']);
    }

    public function testFormatFeedRecordOptionsNull(): void
    {
        $row = $this->makeFeedRow();

        $this->feedFacade->method('getNfOption')
            ->willReturn(null);
        $this->feedFacade->method('formatLastUpdate')
            ->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame([], $result['options']);
    }

    public function testFormatFeedRecordEmptyOptions(): void
    {
        $row = $this->makeFeedRow(['NfOptions' => '']);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame([], $result['options']);
        $this->assertSame('', $result['optionsString']);
    }

    public function testFormatFeedRecordCallsGetNfOptionWithAllParam(): void
    {
        $row = $this->makeFeedRow(['NfOptions' => 'charset=utf8,max=10']);

        $this->feedFacade->expects($this->once())
            ->method('getNfOption')
            ->with('charset=utf8,max=10', 'all')
            ->willReturn(['charset' => 'utf8', 'max' => '10']);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertSame(['charset' => 'utf8', 'max' => '10'], $result['options']);
    }

    public function testFormatFeedRecordCastsIdToInt(): void
    {
        $row = $this->makeFeedRow(['NfID' => '99']);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertIsInt($result['id']);
        $this->assertSame(99, $result['id']);
    }

    public function testFormatFeedRecordCastsLangIdToInt(): void
    {
        $row = $this->makeFeedRow(['NfLgID' => '7']);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $this->assertIsInt($result['langId']);
        $this->assertSame(7, $result['langId']);
    }

    public function testFormatFeedRecordReturnKeys(): void
    {
        $row = $this->makeFeedRow();

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $result = $this->handler->formatFeedRecord($row);

        $expectedKeys = [
            'id', 'name', 'sourceUri', 'langId', 'langName',
            'articleSectionTags', 'filterTags', 'options',
            'optionsString', 'updateTimestamp', 'lastUpdate', 'articleCount'
        ];
        $this->assertSame($expectedKeys, array_keys($result));
    }

    // =========================================================================
    // getFeed tests
    // =========================================================================

    public function testGetFeedReturnsErrorWhenNotFound(): void
    {
        $this->feedFacade->method('getFeedById')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->getFeed(999);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed not found', $result['error']);
    }

    // =========================================================================
    // createFeed tests
    // =========================================================================

    public function testCreateFeedFailsWhenLangIdMissing(): void
    {
        $result = $this->handler->createFeed([
            'name' => 'Test',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedFailsWhenLangIdZero(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 0,
            'name' => 'Test',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedFailsWhenLangIdNegative(): void
    {
        $result = $this->handler->createFeed([
            'langId' => -1,
            'name' => 'Test',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedFailsWhenNameEmpty(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => '',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed name is required', $result['error']);
    }

    public function testCreateFeedFailsWhenNameMissing(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed name is required', $result['error']);
    }

    public function testCreateFeedFailsWhenNameWhitespaceOnly(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => '   ',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed name is required', $result['error']);
    }

    public function testCreateFeedFailsWhenSourceUriEmpty(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Test Feed',
            'sourceUri' => ''
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Source URI is required', $result['error']);
    }

    public function testCreateFeedFailsWhenSourceUriMissing(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Test Feed'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Source URI is required', $result['error']);
    }

    public function testCreateFeedFailsWhenSourceUriWhitespaceOnly(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Test Feed',
            'sourceUri' => '   '
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Source URI is required', $result['error']);
    }

    public function testCreateFeedFailsWhenAllFieldsMissing(): void
    {
        $result = $this->handler->createFeed([]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedCallsFacadeWithCorrectData(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('createFeed')
            ->with([
                'NfLgID' => 3,
                'NfName' => 'My Feed',
                'NfSourceURI' => 'https://example.com/rss',
                'NfArticleSectionTags' => 'article',
                'NfFilterTags' => '.content',
                'NfOptions' => 'charset=utf8'
            ])
            ->willReturn(10);

        // getFeed is called after creation; needs getFeedById mock
        $this->feedFacade->method('getFeedById')
            ->with(10)
            ->willReturn(null);

        $result = $this->handler->createFeed([
            'langId' => 3,
            'name' => 'My Feed',
            'sourceUri' => 'https://example.com/rss',
            'articleSectionTags' => 'article',
            'filterTags' => '.content',
            'options' => 'charset=utf8'
        ]);

        $this->assertTrue($result['success']);
    }

    public function testCreateFeedUsesDefaultsForOptionalFields(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('createFeed')
            ->with([
                'NfLgID' => 1,
                'NfName' => 'Feed',
                'NfSourceURI' => 'https://example.com',
                'NfArticleSectionTags' => '',
                'NfFilterTags' => '',
                'NfOptions' => ''
            ])
            ->willReturn(1);

        $this->feedFacade->method('getFeedById')->willReturn(null);

        $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Feed',
            'sourceUri' => 'https://example.com'
        ]);
    }

    public function testCreateFeedTrimsName(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('createFeed')
            ->with($this->callback(function (array $data) {
                return $data['NfName'] === 'Trimmed Name';
            }))
            ->willReturn(1);

        $this->feedFacade->method('getFeedById')->willReturn(null);

        $this->handler->createFeed([
            'langId' => 1,
            'name' => '  Trimmed Name  ',
            'sourceUri' => 'https://example.com'
        ]);
    }

    public function testCreateFeedTrimsSourceUri(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('createFeed')
            ->with($this->callback(function (array $data) {
                return $data['NfSourceURI'] === 'https://example.com/rss';
            }))
            ->willReturn(1);

        $this->feedFacade->method('getFeedById')->willReturn(null);

        $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Test',
            'sourceUri' => '  https://example.com/rss  '
        ]);
    }

    public function testCreateFeedReturnsSuccessTrue(): void
    {
        $this->feedFacade->method('createFeed')->willReturn(5);
        $this->feedFacade->method('getFeedById')->willReturn(null);

        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Feed',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('feed', $result);
    }

    // =========================================================================
    // updateFeed tests
    // =========================================================================

    public function testUpdateFeedReturnsErrorWhenNotFound(): void
    {
        $this->feedFacade->method('getFeedById')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->updateFeed(999, ['name' => 'Updated']);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed not found', $result['error']);
    }

    public function testUpdateFeedCallsFacadeWithMergedData(): void
    {
        $existing = $this->makeFeedRow();
        $this->feedFacade->method('getFeedById')
            ->willReturn($existing);

        $this->feedFacade->expects($this->once())
            ->method('updateFeed')
            ->with(42, [
                'NfLgID' => 1,
                'NfName' => 'Updated Name',
                'NfSourceURI' => 'https://example.com/feed',
                'NfArticleSectionTags' => 'article',
                'NfFilterTags' => 'div.content',
                'NfOptions' => 'charset=utf8'
            ]);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $this->handler->updateFeed(42, ['name' => 'Updated Name']);
    }

    public function testUpdateFeedPreservesExistingFieldsWhenNotProvided(): void
    {
        $existing = $this->makeFeedRow();
        $this->feedFacade->method('getFeedById')
            ->willReturn($existing);

        $this->feedFacade->expects($this->once())
            ->method('updateFeed')
            ->with(42, [
                'NfLgID' => 1,
                'NfName' => 'Test Feed',
                'NfSourceURI' => 'https://example.com/feed',
                'NfArticleSectionTags' => 'article',
                'NfFilterTags' => 'div.content',
                'NfOptions' => 'charset=utf8'
            ]);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $this->handler->updateFeed(42, []);
    }

    public function testUpdateFeedCanUpdateAllFields(): void
    {
        $existing = $this->makeFeedRow();
        $this->feedFacade->method('getFeedById')
            ->willReturn($existing);

        $this->feedFacade->expects($this->once())
            ->method('updateFeed')
            ->with(42, [
                'NfLgID' => 5,
                'NfName' => 'New Name',
                'NfSourceURI' => 'https://new.example.com',
                'NfArticleSectionTags' => 'section',
                'NfFilterTags' => 'span.text',
                'NfOptions' => 'max=20'
            ]);

        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $this->handler->updateFeed(42, [
            'langId' => 5,
            'name' => 'New Name',
            'sourceUri' => 'https://new.example.com',
            'articleSectionTags' => 'section',
            'filterTags' => 'span.text',
            'options' => 'max=20'
        ]);
    }

    public function testUpdateFeedReturnsSuccess(): void
    {
        $existing = $this->makeFeedRow();
        $this->feedFacade->method('getFeedById')
            ->willReturn($existing);
        $this->feedFacade->method('getNfOption')->willReturn([]);
        $this->feedFacade->method('formatLastUpdate')->willReturn('up to date');

        $result = $this->handler->updateFeed(42, ['name' => 'X']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('feed', $result);
    }

    // =========================================================================
    // deleteFeeds tests
    // =========================================================================

    public function testDeleteFeedsEmptyArray(): void
    {
        $result = $this->handler->deleteFeeds([]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['deleted']);
    }

    public function testDeleteFeedsSingleId(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('5')
            ->willReturn(['feeds' => 1]);

        $result = $this->handler->deleteFeeds([5]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['deleted']);
    }

    public function testDeleteFeedsMultipleIds(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('1,2,3')
            ->willReturn(['feeds' => 3]);

        $result = $this->handler->deleteFeeds([1, 2, 3]);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['deleted']);
    }

    public function testDeleteFeedsCastsIdsToInt(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteFeeds')
            ->with('7,8')
            ->willReturn(['feeds' => 2]);

        $result = $this->handler->deleteFeeds(['7', '8']);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['deleted']);
    }

    public function testDeleteFeedsReturnsDeletedCount(): void
    {
        $this->feedFacade->method('deleteFeeds')
            ->willReturn(['feeds' => 5]);

        $result = $this->handler->deleteFeeds([10, 20, 30, 40, 50]);

        $this->assertSame(5, $result['deleted']);
    }

    public function testDeleteFeedsDoesNotCallFacadeForEmptyArray(): void
    {
        $this->feedFacade->expects($this->never())
            ->method('deleteFeeds');

        $this->handler->deleteFeeds([]);
    }

    // =========================================================================
    // Format wrapper tests
    // =========================================================================

    public function testFormatGetFeedListMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'formatGetFeedList')
        );
    }

    public function testFormatGetFeedMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'formatGetFeed')
        );
    }

    public function testFormatCreateFeedDelegatesToCreateFeed(): void
    {
        // Validation error case: no DB calls needed
        $result = $this->handler->formatCreateFeed([]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testFormatUpdateFeedDelegatesToUpdateFeed(): void
    {
        $this->feedFacade->method('getFeedById')->willReturn(null);

        $result = $this->handler->formatUpdateFeed(999, []);

        $this->assertFalse($result['success']);
        $this->assertSame('Feed not found', $result['error']);
    }

    public function testFormatDeleteFeedsDelegatesToDeleteFeeds(): void
    {
        $result = $this->handler->formatDeleteFeeds([]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['deleted']);
    }

    public function testFormatGetFeedDelegatesToGetFeed(): void
    {
        $this->feedFacade->method('getFeedById')->willReturn(null);

        $result = $this->handler->formatGetFeed(404);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed not found', $result['error']);
    }

    // =========================================================================
    // getFeedList parameter handling tests (unit-testable logic)
    // =========================================================================

    public function testGetFeedListMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'getFeedList')
        );
    }

    public function testGetFeedListReturnTypeIsArray(): void
    {
        $reflection = new \ReflectionMethod($this->handler, 'getFeedList');
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    public function testGetFeedListAcceptsArrayParameter(): void
    {
        $reflection = new \ReflectionMethod($this->handler, 'getFeedList');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('params', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
    }

    public function testGetLanguagesForSelectMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->handler, 'getLanguagesForSelect')
        );
    }

    public function testGetLanguagesForSelectReturnTypeIsArray(): void
    {
        $reflection = new \ReflectionMethod($this->handler, 'getLanguagesForSelect');
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertSame('array', $returnType->getName());
    }

    // =========================================================================
    // createFeed edge cases
    // =========================================================================

    public function testCreateFeedLangIdStringCastToInt(): void
    {
        // langId as string "0" should fail
        $result = $this->handler->createFeed([
            'langId' => '0',
            'name' => 'Test',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertFalse($result['success']);
        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedLangIdStringPositive(): void
    {
        // langId as string "3" should pass validation
        $this->feedFacade->method('createFeed')->willReturn(1);
        $this->feedFacade->method('getFeedById')->willReturn(null);

        $result = $this->handler->createFeed([
            'langId' => '3',
            'name' => 'Test',
            'sourceUri' => 'https://example.com'
        ]);

        $this->assertTrue($result['success']);
    }

    public function testCreateFeedValidationOrderLangIdFirst(): void
    {
        // When all fields are missing, langId error should come first
        $result = $this->handler->createFeed([
            'langId' => 0,
            'name' => '',
            'sourceUri' => ''
        ]);

        $this->assertSame('Language is required', $result['error']);
    }

    public function testCreateFeedValidationOrderNameSecond(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => '',
            'sourceUri' => ''
        ]);

        $this->assertSame('Feed name is required', $result['error']);
    }

    public function testCreateFeedValidationOrderSourceUriThird(): void
    {
        $result = $this->handler->createFeed([
            'langId' => 1,
            'name' => 'Valid',
            'sourceUri' => ''
        ]);

        $this->assertSame('Source URI is required', $result['error']);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a standard feed row for testing.
     *
     * @param array $overrides Fields to override
     *
     * @return array Feed database row
     */
    private function makeFeedRow(array $overrides = []): array
    {
        return array_merge([
            'NfID' => 42,
            'NfName' => 'Test Feed',
            'NfSourceURI' => 'https://example.com/feed',
            'NfLgID' => 1,
            'LgName' => 'English',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => 'div.content',
            'NfOptions' => 'charset=utf8',
            'NfUpdate' => 1700000000,
            'articleCount' => 15,
        ], $overrides);
    }
}
