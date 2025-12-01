<?php declare(strict_types=1);
namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Globals;
use Lwt\Database\Connection;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Services\FeedService;

/**
 * Handler for RSS feed-related API operations.
 *
 * Extracted from api_v1.php lines 827-954 (namespace Lwt\Ajax\Feed).
 */
class FeedHandler
{
    private FeedService $feedService;

    public function __construct()
    {
        $this->feedService = new FeedService();
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
        $tbpref = Globals::getTablePrefix();

        if (empty($feed)) {
            return [0, 0];
        }

        // Build parameterized query with placeholders
        $placeholderRow = '(?, ?, ?, ?, ?, ?, ?)';
        $placeholders = array_fill(0, count($feed), $placeholderRow);

        $sql = 'INSERT IGNORE INTO ' . $tbpref . 'feedlinks
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
        $tbpref = Globals::getTablePrefix();

        // Update feed timestamp using prepared statement
        Connection::preparedExecute(
            "UPDATE {$tbpref}newsfeeds SET NfUpdate = ? WHERE NfID = ?",
            [time(), $nfid]
        );

        $nfMaxLinks = $this->feedService->getNfOption($nfoptions, 'max_links');
        if (!$nfMaxLinks) {
            if ($this->feedService->getNfOption($nfoptions, 'article_source')) {
                $nfMaxLinks = Settings::getWithDefault('set-max-articles-with-text');
            } else {
                $nfMaxLinks = Settings::getWithDefault('set-max-articles-without-text');
            }
        }

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

        // Count total feedlinks using prepared statement
        $row = Connection::preparedFetchOne(
            "SELECT COUNT(*) AS total FROM {$tbpref}feedlinks WHERE FlNfID = ?",
            [$nfid]
        );

        $to = ((int)$row['total'] - $nfMaxLinks);
        if ($to > 0) {
            QueryBuilder::table('feedlinks')
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
        $articleSource = $this->feedService->getNfOption($nfoptions, 'article_source');
        $feed = $this->feedService->parseRssFeed($nfsourceuri, $articleSource ?? '');
        if (empty($feed)) {
            return [
                "error" => 'Could not load "' . $nfname . '"'
            ];
        }
        list($importedFeed, $nif) = $this->getFeedsList($feed, $nfid);
        $msg = $this->getFeedResult($importedFeed, $nif, $nfname, $nfid, $nfoptions);
        return [
            "success" => true,
            "message" => $msg,
            "imported" => $importedFeed,
            "duplicates" => $nif
        ];
    }

    // =========================================================================
    // API Response Formatters
    // =========================================================================

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
}
