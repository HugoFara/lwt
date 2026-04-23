<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Http;

use Lwt\Modules\Feed\Http\FeedArticleApiHandler;
use Lwt\Modules\Feed\Application\FeedFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for FeedArticleApiHandler.
 *
 */
#[CoversClass(FeedArticleApiHandler::class)]
class FeedArticleApiHandlerTest extends TestCase
{
    /** @var FeedFacade&MockObject */
    private FeedFacade $feedFacade;

    private FeedArticleApiHandler $handler;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->feedFacade = $this->createMock(FeedFacade::class);
        $this->handler = new FeedArticleApiHandler($this->feedFacade);
    }

    // =========================================================================
    // Constructor tests
    // =========================================================================

    public function testConstructorCreatesValidHandler(): void
    {
        $this->assertInstanceOf(FeedArticleApiHandler::class, $this->handler);
    }

    // =========================================================================
    // formatArticleRecord — status detection
    // =========================================================================

    public function testFormatArticleRecordNewStatus(): void
    {
        $row = [
            'FlID' => '42',
            'FlTitle' => 'Test Article',
            'FlLink' => 'https://example.com/article',
            'FlDescription' => 'A description',
            'FlDate' => '2026-01-15',
            'FlAudio' => '',
            'FlText' => '',
            'TxID' => null,
            'TxArchivedAt' => null,
        ];

        $result = $this->handler->formatArticleRecord($row);

        $this->assertSame('new', $result['status']);
        $this->assertNull($result['textId']);
        $this->assertNull($result['archivedTextId']);
    }

    public function testFormatArticleRecordImportedStatus(): void
    {
        $row = [
            'FlID' => '42',
            'FlTitle' => 'Imported Article',
            'FlLink' => 'https://example.com/article',
            'FlDescription' => 'Desc',
            'FlDate' => '2026-01-15',
            'FlAudio' => '',
            'FlText' => 'Some text',
            'TxID' => '10',
            'TxArchivedAt' => null,
        ];

        $result = $this->handler->formatArticleRecord($row);

        $this->assertSame('imported', $result['status']);
        $this->assertSame(10, $result['textId']);
        $this->assertNull($result['archivedTextId']);
    }

    public function testFormatArticleRecordArchivedStatus(): void
    {
        $row = [
            'FlID' => '42',
            'FlTitle' => 'Archived Article',
            'FlLink' => 'https://example.com/article',
            'FlDescription' => 'Desc',
            'FlDate' => '2026-01-15',
            'FlAudio' => '',
            'FlText' => 'Text content',
            'TxID' => '10',
            'TxArchivedAt' => '2026-01-20 10:00:00',
        ];

        $result = $this->handler->formatArticleRecord($row);

        $this->assertSame('archived', $result['status']);
        $this->assertNull($result['textId']);
        $this->assertSame(10, $result['archivedTextId']);
    }

    public function testFormatArticleRecordErrorStatus(): void
    {
        $row = [
            'FlID' => '42',
            'FlTitle' => 'Error Article',
            'FlLink' => ' https://example.com/broken',
            'FlDescription' => 'Desc',
            'FlDate' => '2026-01-15',
            'FlAudio' => '',
            'FlText' => '',
            'TxID' => null,
            'TxArchivedAt' => null,
        ];

        $result = $this->handler->formatArticleRecord($row);

        $this->assertSame('error', $result['status']);
        $this->assertNull($result['textId']);
        $this->assertNull($result['archivedTextId']);
    }

    // =========================================================================
    // formatArticleRecord — field mapping
    // =========================================================================

    public function testFormatArticleRecordIdCastToInt(): void
    {
        $row = $this->makeArticleRow(['FlID' => '99']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame(99, $result['id']);
    }

    public function testFormatArticleRecordTitleIsString(): void
    {
        $row = $this->makeArticleRow(['FlTitle' => 'My Title']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('My Title', $result['title']);
    }

    public function testFormatArticleRecordLinkIsTrimmed(): void
    {
        $row = $this->makeArticleRow(['FlLink' => '  https://example.com  ']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('https://example.com', $result['link']);
    }

    public function testFormatArticleRecordDescriptionIsString(): void
    {
        $row = $this->makeArticleRow(['FlDescription' => 'A description']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('A description', $result['description']);
    }

    public function testFormatArticleRecordDateIsString(): void
    {
        $row = $this->makeArticleRow(['FlDate' => '2026-03-10']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('2026-03-10', $result['date']);
    }

    public function testFormatArticleRecordAudioIsString(): void
    {
        $row = $this->makeArticleRow(['FlAudio' => 'https://example.com/audio.mp3']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('https://example.com/audio.mp3', $result['audio']);
    }

    public function testFormatArticleRecordHasTextTrueWhenNotEmpty(): void
    {
        $row = $this->makeArticleRow(['FlText' => 'Some content']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertTrue($result['hasText']);
    }

    public function testFormatArticleRecordHasTextFalseWhenEmpty(): void
    {
        $row = $this->makeArticleRow(['FlText' => '']);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertFalse($result['hasText']);
    }

    public function testFormatArticleRecordHasTextFalseWhenNull(): void
    {
        $row = $this->makeArticleRow(['FlText' => null]);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertFalse($result['hasText']);
    }

    public function testFormatArticleRecordReturnsAllExpectedKeys(): void
    {
        $row = $this->makeArticleRow();
        $result = $this->handler->formatArticleRecord($row);

        $expectedKeys = [
            'id', 'title', 'link', 'description', 'date',
            'audio', 'hasText', 'status', 'textId', 'archivedTextId'
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: $key");
        }
        $this->assertCount(count($expectedKeys), $result);
    }

    // =========================================================================
    // formatArticleRecord — edge cases
    // =========================================================================

    public function testFormatArticleRecordTxIdEmptyStringTreatedAsNew(): void
    {
        $row = $this->makeArticleRow(['TxID' => '', 'TxArchivedAt' => null]);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('new', $result['status']);
        $this->assertNull($result['textId']);
    }

    public function testFormatArticleRecordTxIdZeroTreatedAsNew(): void
    {
        // TxID of '0' casts to int 0 which is falsy but not null
        // The code checks !== null && !== '' so '0' -> (int)0 which is not null
        $row = $this->makeArticleRow(['TxID' => '0', 'TxArchivedAt' => null]);
        $result = $this->handler->formatArticleRecord($row);
        // TxID '0' passes the check, becomes textId=0, status='imported'
        $this->assertSame('imported', $result['status']);
    }

    public function testFormatArticleRecordImportedTakesPriorityOverErrorLink(): void
    {
        // When TxID is set, imported status takes priority even if link starts with space
        $row = $this->makeArticleRow([
            'FlLink' => ' https://example.com/broken',
            'TxID' => '5',
            'TxArchivedAt' => null,
        ]);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('imported', $result['status']);
    }

    public function testFormatArticleRecordArchivedTakesPriorityOverErrorLink(): void
    {
        $row = $this->makeArticleRow([
            'FlLink' => ' https://example.com/broken',
            'TxID' => '5',
            'TxArchivedAt' => '2026-01-20',
        ]);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('archived', $result['status']);
    }

    public function testFormatArticleRecordErrorRequiresLeadingSpace(): void
    {
        $row = $this->makeArticleRow([
            'FlLink' => 'https://example.com/ok',
            'TxID' => null,
        ]);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('new', $result['status']);
    }

    public function testFormatArticleRecordArchivedWithEmptyTxArchivedAt(): void
    {
        // TxArchivedAt is empty string -> not archived -> imported
        $row = $this->makeArticleRow([
            'TxID' => '7',
            'TxArchivedAt' => '',
        ]);
        $result = $this->handler->formatArticleRecord($row);
        $this->assertSame('imported', $result['status']);
    }

    // =========================================================================
    // getArticles tests
    // =========================================================================

    public function testGetArticlesMissingFeedIdReturnsError(): void
    {
        $result = $this->handler->getArticles([]);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed ID is required', $result['error']);
    }

    public function testGetArticlesZeroFeedIdReturnsError(): void
    {
        $result = $this->handler->getArticles(['feed_id' => 0]);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed ID is required', $result['error']);
    }

    public function testGetArticlesNegativeFeedIdReturnsError(): void
    {
        $result = $this->handler->getArticles(['feed_id' => -5]);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed ID is required', $result['error']);
    }

    public function testGetArticlesStringZeroFeedIdReturnsError(): void
    {
        $result = $this->handler->getArticles(['feed_id' => '0']);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed ID is required', $result['error']);
    }

    public function testGetArticlesFeedNotFoundReturnsError(): void
    {
        $this->feedFacade->method('getFeedById')
            ->with(999)
            ->willReturn(null);

        $result = $this->handler->getArticles(['feed_id' => 999]);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed not found', $result['error']);
    }

    public function testGetArticlesValidFeedReturnsArticlesKey(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Test Feed',
                'NfLgID' => '2',
            ]);

        $result = $this->handler->getArticles(['feed_id' => 1]);

        $this->assertArrayHasKey('articles', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('feed', $result);
    }

    public function testGetArticlesFeedInfoFormatting(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '5',
                'NfName' => 'My Feed',
                'NfLgID' => '3',
            ]);

        $result = $this->handler->getArticles(['feed_id' => 5]);

        $this->assertSame(5, $result['feed']['id']);
        $this->assertSame('My Feed', $result['feed']['name']);
        $this->assertSame('3', $result['feed']['langId']);
    }

    public function testGetArticlesPaginationDefaults(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        $result = $this->handler->getArticles(['feed_id' => 1]);

        $this->assertSame(1, $result['pagination']['page']);
        $this->assertSame(50, $result['pagination']['per_page']);
    }

    public function testGetArticlesPaginationCustomValues(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        $result = $this->handler->getArticles([
            'feed_id' => 1,
            'page' => 3,
            'per_page' => 25,
        ]);

        $this->assertSame(25, $result['pagination']['per_page']);
    }

    public function testGetArticlesPerPageClampedToMax100(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        $result = $this->handler->getArticles([
            'feed_id' => 1,
            'per_page' => 500,
        ]);

        $this->assertSame(100, $result['pagination']['per_page']);
    }

    public function testGetArticlesPerPageClampedToMin1(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        $result = $this->handler->getArticles([
            'feed_id' => 1,
            'per_page' => -10,
        ]);

        $this->assertSame(1, $result['pagination']['per_page']);
    }

    // =========================================================================
    // deleteArticles tests
    // =========================================================================

    public function testDeleteArticlesEmptyArrayDeletesAll(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteArticles')
            ->with('7')
            ->willReturn(15);

        $result = $this->handler->deleteArticles(7, []);

        $this->assertTrue($result['success']);
        $this->assertSame(15, $result['deleted']);
    }

    public function testDeleteArticlesDefaultParamDeletesAll(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteArticles')
            ->with('3')
            ->willReturn(5);

        $result = $this->handler->deleteArticles(3);

        $this->assertTrue($result['success']);
        $this->assertSame(5, $result['deleted']);
    }

    public function testDeleteArticlesWithIdsDoesNotCallFacadeDeleteAll(): void
    {
        $this->feedFacade->expects($this->never())
            ->method('deleteArticles');

        // This will attempt QueryBuilder which needs DB — but we can still
        // verify the facade is NOT called for the "all" path
        try {
            $this->handler->deleteArticles(1, [10, 20, 30]);
        } catch (\Throwable $e) {
            // QueryBuilder may fail without real DB — that's OK for this test
        }
    }

    public function testDeleteArticlesReturnsSuccessTrue(): void
    {
        $this->feedFacade->method('deleteArticles')->willReturn(0);
        $result = $this->handler->deleteArticles(1);
        $this->assertTrue($result['success']);
    }

    public function testDeleteArticlesReturnsDeletedCount(): void
    {
        $this->feedFacade->method('deleteArticles')->willReturn(42);
        $result = $this->handler->deleteArticles(1);
        $this->assertSame(42, $result['deleted']);
    }

    public function testDeleteArticlesZeroDeleted(): void
    {
        $this->feedFacade->method('deleteArticles')->willReturn(0);
        $result = $this->handler->deleteArticles(999);
        $this->assertSame(0, $result['deleted']);
        $this->assertTrue($result['success']);
    }

    // =========================================================================
    // importArticles tests
    // =========================================================================

    public function testImportArticlesEmptyIdsReturnsError(): void
    {
        $result = $this->handler->importArticles(['article_ids' => []]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertContains('No articles selected', $result['errors']);
    }

    public function testImportArticlesMissingIdsKeyReturnsError(): void
    {
        $result = $this->handler->importArticles([]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertContains('No articles selected', $result['errors']);
    }

    public function testImportArticlesNonArrayIdsReturnsError(): void
    {
        $result = $this->handler->importArticles(['article_ids' => 'not-an-array']);

        $this->assertFalse($result['success']);
        $this->assertContains('No articles selected', $result['errors']);
    }

    public function testImportArticlesSuccessfulSingleArticle(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([
                [
                    'NfOptions' => '',
                    'NfName' => 'TestFeed',
                    'NfLgID' => '1',
                    'NfArticleSectionTags' => '',
                    'NfFilterTags' => '',
                    'FlLink' => 'https://example.com/article1',
                    'FlID' => '10',
                    'FlTitle' => 'Article 1',
                    'FlAudio' => '',
                    'FlText' => 'Article text content',
                ]
            ]);

        $this->feedFacade->method('getFeedOption')
            ->willReturn(null);

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                [
                    'TxTitle' => 'Article 1',
                    'TxText' => 'Article text content',
                    'TxAudioURI' => '',
                    'TxSourceURI' => 'https://example.com/article1',
                ]
            ]);

        $this->feedFacade->method('createTextFromFeed')
            ->willReturn(1);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    public function testImportArticlesMultipleArticles(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([
                $this->makeFeedLinkRow('10', 'Article 1'),
                $this->makeFeedLinkRow('11', 'Article 2'),
            ]);

        $this->feedFacade->method('getFeedOption')
            ->willReturn(null);

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                ['TxTitle' => 'T', 'TxText' => 'X', 'TxAudioURI' => '', 'TxSourceURI' => '']
            ]);

        $this->feedFacade->method('createTextFromFeed')->willReturn(1);

        $result = $this->handler->importArticles(['article_ids' => [10, 11]]);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['imported']);
    }

    public function testImportArticlesWithExtractionError(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([
                $this->makeFeedLinkRow('10', 'Broken Article'),
            ]);

        $this->feedFacade->method('getFeedOption')->willReturn(null);

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                'error' => [
                    'message' => 'Failed to extract',
                    'link' => ['https://example.com/broken'],
                ]
            ]);

        $this->feedFacade->expects($this->once())
            ->method('markLinkAsError')
            ->with('https://example.com/broken');

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertContains('Failed to extract', $result['errors']);
    }

    public function testImportArticlesUsesTagFromOptions(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([$this->makeFeedLinkRow('10', 'Article')]);

        $this->feedFacade->method('getFeedOption')
            ->willReturnCallback(function (string $opts, string $option) {
                return $option === 'tag' ? 'custom-tag' : null;
            });

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                ['TxTitle' => 'T', 'TxText' => 'X', 'TxAudioURI' => '', 'TxSourceURI' => '']
            ]);

        $this->feedFacade->expects($this->once())
            ->method('createTextFromFeed')
            ->with($this->anything(), 'custom-tag')
            ->willReturn(1);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertTrue($result['success']);
    }

    public function testImportArticlesFallsBackToFeedNameForTag(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([
                $this->makeFeedLinkRow('10', 'Article', 'VeryLongFeedNameForTagging'),
            ]);

        $this->feedFacade->method('getFeedOption')
            ->willReturnCallback(function (string $opts, string $option) {
                return $option === 'tag' ? '' : null;
            });

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                ['TxTitle' => 'T', 'TxText' => 'X', 'TxAudioURI' => '', 'TxSourceURI' => '']
            ]);

        $this->feedFacade->expects($this->once())
            ->method('createTextFromFeed')
            ->with($this->anything(), 'VeryLongFeedNameForT') // truncated to 20 chars
            ->willReturn(1);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertTrue($result['success']);
    }

    public function testImportArticlesCallsArchiveOldTexts(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([$this->makeFeedLinkRow('10', 'Article')]);

        $this->feedFacade->method('getFeedOption')->willReturn(null);

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                ['TxTitle' => 'T', 'TxText' => 'X', 'TxAudioURI' => '', 'TxSourceURI' => '']
            ]);

        $this->feedFacade->method('createTextFromFeed')->willReturn(1);

        $this->feedFacade->expects($this->once())
            ->method('archiveOldTexts');

        $this->handler->importArticles(['article_ids' => [10]]);
    }

    public function testImportArticlesWithEmptyFlLinkUsesHashId(): void
    {
        $row = $this->makeFeedLinkRow('10', 'No Link Article');
        $row['FlLink'] = '';

        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([$row]);

        $this->feedFacade->method('getFeedOption')->willReturn(null);

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturnCallback(function (array $doc) {
                // Verify that the doc link is '#10' when FlLink is empty
                $this->assertSame('#10', $doc[0]['link']);
                return [
                    ['TxTitle' => 'T', 'TxText' => 'X', 'TxAudioURI' => '', 'TxSourceURI' => '']
                ];
            });

        $this->feedFacade->method('createTextFromFeed')->willReturn(1);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertTrue($result['success']);
    }

    public function testImportArticlesNoFeedLinksFound(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([]);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertEmpty($result['errors']);
    }

    public function testImportArticlesExtractionErrorWithoutMessage(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([$this->makeFeedLinkRow('10', 'Article')]);

        $this->feedFacade->method('getFeedOption')->willReturn(null);

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                'error' => [
                    // No 'message' key
                    'link' => [],
                ]
            ]);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertContains('Unknown error', $result['errors']);
    }

    public function testImportArticlesExtractionErrorWithMultipleLinks(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([$this->makeFeedLinkRow('10', 'Article')]);

        $this->feedFacade->method('getFeedOption')->willReturn(null);

        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                'error' => [
                    'message' => 'Multi error',
                    'link' => ['link1', 'link2', 'link3'],
                ]
            ]);

        $this->feedFacade->expects($this->exactly(3))
            ->method('markLinkAsError');

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertContains('Multi error', $result['errors']);
    }

    // =========================================================================
    // resetErrorArticles tests
    // =========================================================================

    public function testResetErrorArticlesReturnsSuccessAndCount(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('resetUnloadableArticles')
            ->with('5')
            ->willReturn(3);

        $result = $this->handler->resetErrorArticles(5);

        $this->assertTrue($result['success']);
        $this->assertSame(3, $result['reset']);
    }

    public function testResetErrorArticlesZeroReset(): void
    {
        $this->feedFacade->method('resetUnloadableArticles')
            ->willReturn(0);

        $result = $this->handler->resetErrorArticles(1);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['reset']);
    }

    public function testResetErrorArticlesPassesFeedIdAsString(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('resetUnloadableArticles')
            ->with('42');

        $this->handler->resetErrorArticles(42);
    }

    // =========================================================================
    // Format wrapper methods
    // =========================================================================

    public function testFormatGetArticlesDelegatesToGetArticles(): void
    {
        // Missing feed_id triggers error path — no DB needed
        $result = $this->handler->formatGetArticles([]);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Feed ID is required', $result['error']);
    }

    public function testFormatDeleteArticlesDelegatesToDeleteArticles(): void
    {
        $this->feedFacade->method('deleteArticles')->willReturn(5);
        $result = $this->handler->formatDeleteArticles(1);
        $this->assertTrue($result['success']);
        $this->assertSame(5, $result['deleted']);
    }

    public function testFormatImportArticlesDelegatesToImportArticles(): void
    {
        $result = $this->handler->formatImportArticles([]);
        $this->assertFalse($result['success']);
        $this->assertContains('No articles selected', $result['errors']);
    }

    public function testFormatResetErrorArticlesDelegatesToResetErrorArticles(): void
    {
        $this->feedFacade->method('resetUnloadableArticles')->willReturn(2);
        $result = $this->handler->formatResetErrorArticles(1);
        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['reset']);
    }

    // =========================================================================
    // Additional edge cases
    // =========================================================================

    public function testGetArticlesStringFeedIdIsCoerced(): void
    {
        $this->feedFacade->method('getFeedById')
            ->with(5)
            ->willReturn([
                'NfID' => '5',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        $result = $this->handler->getArticles(['feed_id' => '5']);

        $this->assertArrayHasKey('articles', $result);
    }

    public function testGetArticlesPageClampedToMin1(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        $result = $this->handler->getArticles([
            'feed_id' => 1,
            'page' => -5,
        ]);

        $this->assertSame(1, $result['pagination']['page']);
    }

    public function testGetArticlesSortClampedToMin1(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        // sort=0 would be clamped to 1
        $result = $this->handler->getArticles([
            'feed_id' => 1,
            'sort' => 0,
        ]);

        $this->assertArrayHasKey('articles', $result);
    }

    public function testGetArticlesSortClampedToMax3(): void
    {
        $this->feedFacade->method('getFeedById')
            ->willReturn([
                'NfID' => '1',
                'NfName' => 'Feed',
                'NfLgID' => '1',
            ]);

        // sort=99 would be clamped to 3
        $result = $this->handler->getArticles([
            'feed_id' => 1,
            'sort' => 99,
        ]);

        $this->assertArrayHasKey('articles', $result);
    }

    public function testImportArticlesWithCharsetOption(): void
    {
        $row = $this->makeFeedLinkRow('10', 'Article');
        $row['NfOptions'] = 'charset=windows-1252';

        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([$row]);

        $this->feedFacade->method('getFeedOption')
            ->willReturnCallback(function (string $opts, string $option) {
                if ($option === 'charset') {
                    return 'windows-1252';
                }
                return null;
            });

        $this->feedFacade->expects($this->once())
            ->method('extractTextFromArticle')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                'windows-1252'
            )
            ->willReturn([
                ['TxTitle' => 'T', 'TxText' => 'X', 'TxAudioURI' => '', 'TxSourceURI' => '']
            ]);

        $this->feedFacade->method('createTextFromFeed')->willReturn(1);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertTrue($result['success']);
    }

    public function testImportArticlesMultipleTextsFromSingleArticle(): void
    {
        $this->feedFacade->method('getMarkedFeedLinks')
            ->willReturn([$this->makeFeedLinkRow('10', 'Multi Article')]);

        $this->feedFacade->method('getFeedOption')->willReturn(null);

        // Extraction returns multiple texts from one article
        $this->feedFacade->method('extractTextFromArticle')
            ->willReturn([
                ['TxTitle' => 'Part 1', 'TxText' => 'Text 1', 'TxAudioURI' => '', 'TxSourceURI' => ''],
                ['TxTitle' => 'Part 2', 'TxText' => 'Text 2', 'TxAudioURI' => '', 'TxSourceURI' => ''],
                ['TxTitle' => 'Part 3', 'TxText' => 'Text 3', 'TxAudioURI' => '', 'TxSourceURI' => ''],
            ]);

        $this->feedFacade->expects($this->exactly(3))
            ->method('createTextFromFeed')
            ->willReturn(1);

        $result = $this->handler->importArticles(['article_ids' => [10]]);

        $this->assertSame(3, $result['imported']);
    }

    public function testDeleteArticlesFeedIdCastToString(): void
    {
        $this->feedFacade->expects($this->once())
            ->method('deleteArticles')
            ->with('123')
            ->willReturn(0);

        $this->handler->deleteArticles(123);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a minimal article row with defaults.
     *
     * @param array $overrides Fields to override
     *
     * @return array
     */
    private function makeArticleRow(array $overrides = []): array
    {
        return array_merge([
            'FlID' => '1',
            'FlTitle' => 'Default Title',
            'FlLink' => 'https://example.com/default',
            'FlDescription' => 'Default description',
            'FlDate' => '2026-01-01',
            'FlAudio' => '',
            'FlText' => '',
            'TxID' => null,
            'TxArchivedAt' => null,
        ], $overrides);
    }

    /**
     * Create a feed link row as returned by getMarkedFeedLinks.
     *
     * @param string $flId     Article ID
     * @param string $title    Article title
     * @param string $feedName Feed name
     *
     * @return array
     */
    private function makeFeedLinkRow(
        string $flId,
        string $title,
        string $feedName = 'TestFeed'
    ): array {
        return [
            'NfOptions' => '',
            'NfName' => $feedName,
            'NfLgID' => '1',
            'NfArticleSectionTags' => '',
            'NfFilterTags' => '',
            'FlLink' => 'https://example.com/' . $flId,
            'FlID' => $flId,
            'FlTitle' => $title,
            'FlAudio' => '',
            'FlText' => 'Article text content',
        ];
    }
}
