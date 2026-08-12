<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\Http;

use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Modules\Feed\Http\FeedLoadApiHandler;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * `POST /feeds/{id}/load` must not write into a feed the caller does not own.
 *
 * `news_feeds` is user-scoped, so QueryBuilder filters it automatically — but
 * `feed_links` has no owner column. Every write to it therefore has to be
 * gated on the parent feed explicitly, the way `deleteArticles()` and
 * `createTextsFromEdited()` do with `getFeedById()`.
 *
 * `loadFeed()` did not: it took the feed ID straight from the URL, inserted
 * the fetched articles with that `FlNfID`, and then trimmed the feed to its
 * `max_links` with a `DELETE ... WHERE FlNfID = ?`. Both reached another
 * user's rows.
 */
class FeedLoadOwnershipTest extends TestCase
{
    /** @var FeedFacade&MockObject */
    private FeedFacade $feedFacade;

    private FeedLoadApiHandler $handler;

    /** @var list<int> */
    private array $createdFeeds = [];

    /** Somebody who is not the current caller. */
    private ?int $otherUserId = null;

    protected function setUp(): void
    {
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            $this->markTestSkipped('Database connection required');
        }
        $this->feedFacade = $this->createMock(FeedFacade::class);
        $this->handler = new FeedLoadApiHandler($this->feedFacade);

        // news_feeds.NfUsID has a foreign key on users, so the "other user"
        // this test hands feeds to has to be a real row.
        $this->otherUserId = (int) Connection::preparedInsert(
            'INSERT INTO users (UsUsername, UsPasswordHash) VALUES (?, ?)',
            ['feed-owner-' . uniqid(), 'x']
        );
    }

    protected function tearDown(): void
    {
        // tearDown still runs when setUp skipped the test, and CI has no
        // database, so nothing here may reach one unguarded.
        if (!defined('LWT_TEST_DB_AVAILABLE') || !LWT_TEST_DB_AVAILABLE) {
            return;
        }

        foreach ($this->createdFeeds as $feedId) {
            Connection::preparedExecute('DELETE FROM feed_links WHERE FlNfID = ?', [$feedId]);
            Connection::preparedExecute('DELETE FROM news_feeds WHERE NfID = ?', [$feedId]);
        }
        $this->createdFeeds = [];

        if ($this->otherUserId !== null) {
            Connection::preparedExecute('DELETE FROM users WHERE UsID = ?', [$this->otherUserId]);
            $this->otherUserId = null;
        }
    }

    /**
     * Create a feed row owned by a given user, bypassing the scoped builder.
     *
     * @param int|null $ownerId Owner user ID, or null for an unowned row
     *
     * @return int The new feed's ID
     */
    private function makeFeedOwnedBy(?int $ownerId): int
    {
        Connection::preparedExecute(
            'INSERT INTO news_feeds (NfLgID, NfName, NfSourceURI, NfArticleSectionTags,
             NfFilterTags, NfUpdate, NfOptions, NfUsID) VALUES (1, ?, ?, "", "", 0, "", ?)',
            ['victim feed ' . uniqid(), 'https://example.test/rss', $ownerId]
        );
        $row = Connection::preparedFetchAll('SELECT MAX(NfID) AS id FROM news_feeds', []);
        $id = (int) $row[0]['id'];
        $this->createdFeeds[] = $id;
        return $id;
    }

    /**
     * Count the articles attached to a feed, unscoped.
     *
     * @param int $feedId Feed ID
     */
    private function countArticles(int $feedId): int
    {
        $row = Connection::preparedFetchAll(
            'SELECT COUNT(*) AS cnt FROM feed_links WHERE FlNfID = ?',
            [$feedId]
        );
        return (int) $row[0]['cnt'];
    }

    #[Test]
    public function loadingAFeedTheCallerDoesNotOwnWritesNothing(): void
    {
        // The feed belongs to another user, so it is "someone else's" from
        // the current caller's point of view.
        $victimFeedId = $this->makeFeedOwnedBy($this->otherUserId);

        $this->feedFacade->method('getFeedOption')->willReturn(null);
        // getFeedById is user-scoped; for a feed owned by someone else it
        // returns null, which is the mock's default here.
        $this->feedFacade->method('getFeedById')->willReturn(null);
        $this->feedFacade->method('parseRssFeed')->willReturn([
            ['title' => 'injected', 'link' => 'https://evil.test/a', 'text' => 'x',
             'desc' => 'x', 'date' => '2026-01-01', 'audio' => ''],
        ]);

        $before = $this->countArticles($victimFeedId);

        $this->handler->loadFeed('anything', $victimFeedId, 'https://evil.test/rss', '');

        $this->assertSame(
            $before,
            $this->countArticles($victimFeedId),
            'POST /feeds/{id}/load wrote into a feed the caller does not own. '
            . 'feed_links has no owner column, so the feed ID must be checked '
            . 'against getFeedById() before any article is inserted.'
        );
    }

    #[Test]
    public function loadingAFeedTheCallerDoesNotOwnReportsFailure(): void
    {
        $victimFeedId = $this->makeFeedOwnedBy($this->otherUserId);

        $this->feedFacade->method('getFeedOption')->willReturn(null);
        // getFeedById is user-scoped; for a feed owned by someone else it
        // returns null, which is the mock's default here.
        $this->feedFacade->method('getFeedById')->willReturn(null);
        $this->feedFacade->method('parseRssFeed')->willReturn([
            ['title' => 'injected', 'link' => 'https://evil.test/a', 'text' => 'x',
             'desc' => 'x', 'date' => '2026-01-01', 'audio' => ''],
        ]);

        $result = $this->handler->loadFeed('anything', $victimFeedId, 'https://evil.test/rss', '');

        $this->assertArrayHasKey(
            'error',
            $result,
            'A foreign feed ID should be refused, not silently reported as a successful load.'
        );
    }

    #[Test]
    public function loadingAnOwnedFeedStillWorks(): void
    {
        // The gate must not break the normal path: a feed with no owner is
        // visible in single-user mode, which is how most installs run.
        $ownFeedId = $this->makeFeedOwnedBy(null);

        $this->feedFacade->method('getFeedOption')->willReturn(null);
        // A feed the caller owns: the scoped lookup finds it.
        $this->feedFacade->method('getFeedById')->willReturn(['NfID' => $ownFeedId]);
        $this->feedFacade->method('parseRssFeed')->willReturn([
            ['title' => 'legit', 'link' => 'https://example.test/a', 'text' => 'x',
             'desc' => 'x', 'date' => '2026-01-01', 'audio' => ''],
        ]);

        $result = $this->handler->loadFeed('mine', $ownFeedId, 'https://example.test/rss', '');

        $this->assertArrayNotHasKey('error', $result, (string) ($result['error'] ?? ''));
        $this->assertSame(1, $this->countArticles($ownFeedId));
    }
}
