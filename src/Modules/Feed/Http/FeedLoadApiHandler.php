<?php

/**
 * Feed Load API Handler
 *
 * Handles feed loading, parsing, and auto-update operations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Feed\Application\FeedFacade;

/**
 * Sub-handler for feed loading API operations.
 *
 * @since 3.0.0
 */
class FeedLoadApiHandler
{
    private FeedFacade $feedFacade;

    public function __construct(FeedFacade $feedFacade)
    {
        $this->feedFacade = $feedFacade;
    }

    /**
     * Get the list of feeds and insert them into the database.
     *
     * @param array<array<string, string>> $feed A feed with articles
     * @param int                          $nfid News feed ID
     *
     * @return array{0: int, 1: int} Number of imported feeds and number of duplicated feeds.
     */
    public function getFeedsList(array $feed, int $nfid): array
    {
        if (empty($feed)) {
            return [0, 0];
        }

        // Build parameterized query with placeholders
        $placeholderRow = '(?, ?, ?, ?, ?, ?, ?)';
        $placeholders = array_fill(0, count($feed), $placeholderRow);

        $sql = 'INSERT IGNORE INTO feed_links
                (FlTitle, FlLink, FlText, FlDescription, FlDate, FlAudio, FlNfID)
                VALUES ' . implode(', ', $placeholders);

        // Collect all parameters
        $params = [];
        foreach ($feed as $data) {
            $params[] = $data['title'] ?? '';
            $params[] = $data['link'] ?? '';
            $params[] = $data['text'] ?? null;
            $params[] = $data['desc'] ?? '';
            $params[] = $data['date'] ?? '';
            $params[] = $data['audio'] ?? '';
            $params[] = $nfid;
        }

        $stmt = Connection::prepare($sql);
        $stmt->bindValues($params);
        $stmt->execute();

        $importedFeed = $stmt->affectedRows();
        $nif = count($feed) - $importedFeed;

        return [$importedFeed, $nif];
    }

    /**
     * Update the feeds database and return a result message.
     *
     * @param int    $importedFeed Number of imported feeds
     * @param int    $nif          Number of duplicated feeds
     * @param string $nfname       News feed name
     * @param int    $nfid         News feed ID
     * @param string $nfoptions    News feed options
     *
     * @return string Result message
     */
    public function getFeedResult(int $importedFeed, int $nif, string $nfname, int $nfid, string $nfoptions): string
    {
        // Update feed timestamp using QueryBuilder
        QueryBuilder::table('news_feeds')
            ->where('NfID', '=', $nfid)
            ->updatePrepared(['NfUpdate' => time()]);

        $nfMaxLinksRaw = $this->feedFacade->getNfOption($nfoptions, 'max_links');
        if ($nfMaxLinksRaw === null || $nfMaxLinksRaw === '' || is_array($nfMaxLinksRaw)) {
            $articleSource = $this->feedFacade->getNfOption($nfoptions, 'article_source');
            if ($articleSource !== null && $articleSource !== '' && !is_array($articleSource)) {
                $nfMaxLinksRaw = Settings::getWithDefault('set-max-articles-with-text');
            } else {
                $nfMaxLinksRaw = Settings::getWithDefault('set-max-articles-without-text');
            }
        }
        $nfMaxLinks = (int)$nfMaxLinksRaw;

        $msg = $nfname . ": ";
        if (!$importedFeed) {
            $msg .= "no";
        } else {
            $msg .= $importedFeed;
        }
        $msg .= " new article";
        if ($importedFeed > 1) {
            $msg .= "s";
        }
        $msg .= " imported";
        if ($nif > 1) {
            $msg .= ", $nif articles are dublicates";
        } elseif ($nif == 1) {
            $msg .= ", $nif dublicated article";
        }

        // Count total feed_links using QueryBuilder
        $row = QueryBuilder::table('feed_links')
            ->select(['COUNT(*) AS total'])
            ->where('FlNfID', '=', $nfid)
            ->firstPrepared();

        $to = ($row !== null ? (int)$row['total'] : 0) - $nfMaxLinks;
        if ($to > 0) {
            QueryBuilder::table('feed_links')
                ->whereIn('FlNfID', [$nfid])
                ->orderBy('FlDate', 'ASC')
                ->limit($to)
                ->deletePrepared();
            $msg .= ", $to old article(s) deleted";
        }
        return $msg;
    }

    /**
     * Load a feed and return result.
     *
     * @param string $nfname      Newsfeed name
     * @param int    $nfid        News feed ID
     * @param string $nfsourceuri News feed source
     * @param string $nfoptions   News feed options
     *
     * @return array{success?: true, message?: string, imported?: int, duplicates?: int, error?: string}
     */
    public function loadFeed(string $nfname, int $nfid, string $nfsourceuri, string $nfoptions): array
    {
        $articleSource = $this->feedFacade->getNfOption($nfoptions, 'article_source');
        $feed = $this->feedFacade->parseRssFeed($nfsourceuri, is_string($articleSource) ? $articleSource : '');
        if (!is_array($feed) || count($feed) === 0) {
            return [
                "error" => 'Could not load "' . $nfname . '"'
            ];
        }
        /** @var array<array-key, array<string, string>> $feed */
        list($importedFeed, $nif) = $this->getFeedsList($feed, $nfid);
        $msg = $this->getFeedResult($importedFeed, $nif, $nfname, $nfid, $nfoptions);
        return [
            "success" => true,
            "message" => $msg,
            "imported" => $importedFeed,
            "duplicates" => $nif
        ];
    }

    /**
     * Format response for loading a feed.
     *
     * @param string $name      Feed name
     * @param int    $feedId    Feed ID
     * @param string $sourceUri Feed source URI
     * @param string $options   Feed options
     *
     * @return array{success?: true, message?: string, imported?: int, duplicates?: int, error?: string}
     */
    public function formatLoadFeed(string $name, int $feedId, string $sourceUri, string $options): array
    {
        return $this->loadFeed($name, $feedId, $sourceUri, $options);
    }

    /**
     * Parse an RSS feed for preview.
     *
     * @param string $sourceUri      Feed URL
     * @param string $articleSection Article section tag
     *
     * @return array|null Feed data or null on error
     */
    public function parseFeed(string $sourceUri, string $articleSection = ''): ?array
    {
        $result = $this->feedFacade->parseRssFeed($sourceUri, $articleSection);
        return $result !== false ? $result : null;
    }

    /**
     * Detect feed format and parse.
     *
     * @param string $sourceUri Feed URL
     *
     * @return array|null Feed data with metadata or null on error
     */
    public function detectFeed(string $sourceUri): ?array
    {
        $result = $this->feedFacade->detectAndParseFeed($sourceUri);
        return $result !== false ? $result : null;
    }

    /**
     * Get list of feeds (simple version).
     *
     * @param int|null $languageId Language ID filter (null for all)
     *
     * @return array Array of feeds
     */
    public function getFeeds(?int $languageId = null): array
    {
        return $this->feedFacade->getFeeds($languageId);
    }

    /**
     * Get feeds needing auto-update.
     *
     * @return array Array of feeds
     */
    public function getFeedsNeedingAutoUpdate(): array
    {
        return $this->feedFacade->getFeedsNeedingAutoUpdate();
    }

    /**
     * Get feed load configuration for frontend.
     *
     * @param int  $feedId         Feed ID
     * @param bool $checkAutoupdate Check auto-update feeds
     *
     * @return array Configuration
     */
    public function getFeedLoadConfig(int $feedId, bool $checkAutoupdate = false): array
    {
        return $this->feedFacade->getFeedLoadConfig($feedId, $checkAutoupdate);
    }
}
