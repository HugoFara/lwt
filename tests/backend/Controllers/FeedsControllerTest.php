<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\FeedsController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\FeedService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/FeedsController.php';
require_once __DIR__ . '/../../../src/backend/Services/FeedService.php';

/**
 * Unit tests for the FeedsController class.
 *
 * Tests controller initialization, service integration,
 * and verifies the MVC pattern implementation.
 */
class FeedsControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static int $testLangId = 0;
    private array $originalRequest;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);
        self::$tbpref = Globals::getTablePrefix();

        if (self::$dbConnected) {
            $tbpref = self::$tbpref;

            // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
            $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$tbpref}languages");
            Connection::query("ALTER TABLE {$tbpref}languages AUTO_INCREMENT = " . ((int)$maxId + 1));

            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'FeedsControllerTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('FeedsControllerTestLang', 'http://test.com/###', '', 'http://translate.test/###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $tbpref = self::$tbpref;
        // Clean up test feeds
        Connection::query("DELETE FROM {$tbpref}feedlinks WHERE FlNfID IN (SELECT NfID FROM {$tbpref}newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%')");
        Connection::query("DELETE FROM {$tbpref}newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%'");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName = 'FeedsControllerTestLang'");

        // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
        $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM {$tbpref}languages");
        Connection::query("ALTER TABLE {$tbpref}languages AUTO_INCREMENT = " . ((int)$maxId + 1));
    }

    protected function setUp(): void
    {
        // Save original superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;

        // Reset superglobals
        $_REQUEST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;

        if (!self::$dbConnected) {
            return;
        }

        // Clean up test feeds
        $tbpref = self::$tbpref;
        Connection::query("DELETE FROM {$tbpref}feedlinks WHERE FlNfID IN (SELECT NfID FROM {$tbpref}newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%')");
        Connection::query("DELETE FROM {$tbpref}newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%'");
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new FeedsController();

        $this->assertInstanceOf(FeedsController::class, $controller);
    }

    // ===== Service integration tests =====

    public function testFeedServiceCanCreateFeedThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a service instance directly (controller uses FeedService internally)
        $service = new FeedService();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Create',
            'NfSourceURI' => 'https://example.com/ctrl-feed',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        $this->assertIsInt($feedId);
        $this->assertGreaterThan(0, $feedId);

        // Verify feed exists
        $feed = $service->getFeedById($feedId);
        $this->assertNotNull($feed);
        $this->assertEquals('Ctrl Test Feed Create', $feed['NfName']);
    }

    public function testFeedServiceCanUpdateFeedThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Update Original',
            'NfSourceURI' => 'https://example.com/ctrl-update',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        $service->updateFeed($feedId, [
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Update Modified',
            'NfSourceURI' => 'https://example.com/ctrl-update-new',
            'NfArticleSectionTags' => 'section',
            'NfFilterTags' => 'div.content',
            'NfOptions' => 'autoupdate=1h',
        ]);

        $feed = $service->getFeedById($feedId);
        $this->assertEquals('Ctrl Test Feed Update Modified', $feed['NfName']);
        $this->assertEquals('https://example.com/ctrl-update-new', $feed['NfSourceURI']);
        $this->assertEquals('autoupdate=1h', $feed['NfOptions']);
    }

    public function testFeedServiceCanDeleteFeedThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Delete',
            'NfSourceURI' => 'https://example.com/ctrl-delete',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        // Verify it exists
        $this->assertNotNull($service->getFeedById($feedId));

        // Delete
        $result = $service->deleteFeeds((string)$feedId);
        $this->assertEquals(1, $result['feeds']);

        // Verify deleted
        $this->assertNull($service->getFeedById($feedId));
    }

    public function testFeedServiceCanDeleteArticlesThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();
        $tbpref = self::$tbpref;

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed DeleteArticles',
            'NfSourceURI' => 'https://example.com/ctrl-del-art',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        // Add articles
        for ($i = 1; $i <= 3; $i++) {
            Connection::execute(
                "INSERT INTO {$tbpref}feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
                 VALUES ($feedId, 'Ctrl Article $i', 'https://example.com/ctrl-art$i', 'Desc', " . time() . ")"
            );
        }

        // Delete articles
        $count = $service->deleteArticles((string)$feedId);
        $this->assertEquals(3, $count);

        // Verify articles are gone but feed remains
        $articleCount = (int)Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM {$tbpref}feedlinks WHERE FlNfID = $feedId"
        );
        $this->assertEquals(0, $articleCount);
        $this->assertNotNull($service->getFeedById($feedId));
    }

    // ===== Method existence tests =====

    public function testControllerHasRequiredMethods(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new FeedsController();

        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'edit'));
        $this->assertTrue(method_exists($controller, 'wizard'));
    }

    // ===== FeedService utility methods tests =====

    public function testFeedServiceParseAutoUpdateInterval(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        // Test hours
        $this->assertEquals(3600, $service->parseAutoUpdateInterval('1h'));
        $this->assertEquals(7200, $service->parseAutoUpdateInterval('2h'));

        // Test days
        $this->assertEquals(86400, $service->parseAutoUpdateInterval('1d'));
        $this->assertEquals(172800, $service->parseAutoUpdateInterval('2d'));

        // Test weeks
        $this->assertEquals(604800, $service->parseAutoUpdateInterval('1w'));

        // Test invalid (string without h, d, or w returns null)
        $this->assertNull($service->parseAutoUpdateInterval('xyz'));
    }

    public function testFeedServiceFormatLastUpdate(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        $this->assertEquals('up to date', $service->formatLastUpdate(0));
        $this->assertEquals('last update: 1 minute ago', $service->formatLastUpdate(60));
        $this->assertEquals('last update: 5 minutes ago', $service->formatLastUpdate(300));
        $this->assertEquals('last update: 1 hour ago', $service->formatLastUpdate(3600));
        $this->assertEquals('last update: 1 day ago', $service->formatLastUpdate(86400));
    }

    public function testFeedServiceGetSortOptions(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();
        $options = $service->getSortOptions();

        $this->assertIsArray($options);
        $this->assertCount(3, $options);
        $this->assertEquals('Title A-Z', $options[0]['text']);
        $this->assertEquals('Date Newest First', $options[1]['text']);
        $this->assertEquals('Date Oldest First', $options[2]['text']);
    }

    public function testFeedServiceGetSortColumn(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        // Feedlinks prefix
        $this->assertEquals('FlTitle', $service->getSortColumn(1, 'Fl'));
        $this->assertEquals('FlDate DESC', $service->getSortColumn(2, 'Fl'));
        $this->assertEquals('FlDate ASC', $service->getSortColumn(3, 'Fl'));

        // Newsfeeds prefix
        $this->assertEquals('NfName', $service->getSortColumn(1, 'Nf'));
        $this->assertEquals('NfUpdate DESC', $service->getSortColumn(2, 'Nf'));
        $this->assertEquals('NfUpdate ASC', $service->getSortColumn(3, 'Nf'));
    }

    public function testFeedServiceBuildQueryFilter(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        // Empty query returns empty string
        $this->assertEquals('', $service->buildQueryFilter('', 'title,desc,text', ''));

        // Title only filter
        $result = $service->buildQueryFilter('test', 'title', '');
        $this->assertStringContainsString('FlTitle', $result);
        $this->assertStringNotContainsString('FlDescription', $result);

        // Full filter
        $result = $service->buildQueryFilter('test', 'title,desc,text', '');
        $this->assertStringContainsString('FlTitle', $result);
        $this->assertStringContainsString('FlDescription', $result);
        $this->assertStringContainsString('FlText', $result);
    }

    public function testFeedServiceValidateRegexPattern(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        // Empty is valid
        $this->assertTrue($service->validateRegexPattern(''));

        // Valid patterns
        $this->assertTrue($service->validateRegexPattern('test'));
        $this->assertTrue($service->validateRegexPattern('test.*'));

        // Invalid pattern
        $this->assertFalse($service->validateRegexPattern('[invalid'));
    }

    public function testFeedServiceGetLanguages(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();
        $languages = $service->getLanguages();

        $this->assertIsArray($languages);

        // Should find our test language
        $found = false;
        foreach ($languages as $lang) {
            if ($lang['LgName'] === 'FeedsControllerTestLang') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ===== Integration test =====

    public function testFeedServiceCRUDOperationsIntegration(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();
        $tbpref = self::$tbpref;

        // CREATE
        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Integration',
            'NfSourceURI' => 'https://example.com/integration',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => 'div.content',
            'NfOptions' => 'autoupdate=2h,max_texts=10',
        ]);

        $this->assertGreaterThan(0, $feedId);

        // READ
        $feed = $service->getFeedById($feedId);
        $this->assertNotNull($feed);
        $this->assertEquals('Ctrl Test Feed Integration', $feed['NfName']);
        $this->assertEquals('div.content', $feed['NfFilterTags']);

        // UPDATE
        $service->updateFeed($feedId, [
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Integration Updated',
            'NfSourceURI' => 'https://example.com/integration-new',
            'NfArticleSectionTags' => 'section',
            'NfFilterTags' => 'div.new-content',
            'NfOptions' => 'autoupdate=1d',
        ]);

        $updatedFeed = $service->getFeedById($feedId);
        $this->assertEquals('Ctrl Test Feed Integration Updated', $updatedFeed['NfName']);
        $this->assertEquals('autoupdate=1d', $updatedFeed['NfOptions']);

        // Add articles
        for ($i = 1; $i <= 2; $i++) {
            Connection::execute(
                "INSERT INTO {$tbpref}feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
                 VALUES ($feedId, 'Integration Article $i', 'https://example.com/int-art$i', 'Desc', " . time() . ")"
            );
        }

        // DELETE articles only
        $deleted = $service->deleteArticles((string)$feedId);
        $this->assertEquals(2, $deleted);

        // Verify feed still exists
        $this->assertNotNull($service->getFeedById($feedId));

        // DELETE feed
        $result = $service->deleteFeeds((string)$feedId);
        $this->assertEquals(1, $result['feeds']);

        // Verify feed is gone
        $this->assertNull($service->getFeedById($feedId));
    }

    // ===== Count methods tests =====

    public function testFeedServiceCountFeeds(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        $initialCount = $service->countFeeds(self::$testLangId);

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Count',
            'NfSourceURI' => 'https://example.com/count',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        $newCount = $service->countFeeds(self::$testLangId);
        $this->assertEquals($initialCount + 1, $newCount);

        // All languages count
        $allCount = $service->countFeeds();
        $this->assertGreaterThanOrEqual($newCount, $allCount);
    }

    public function testFeedServiceCountFeedLinks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();
        $tbpref = self::$tbpref;

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed CountLinks',
            'NfSourceURI' => 'https://example.com/count-links',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        // Initially 0 articles
        $count = $service->countFeedLinks((string)$feedId);
        $this->assertEquals(0, $count);

        // Add articles
        for ($i = 1; $i <= 5; $i++) {
            Connection::execute(
                "INSERT INTO {$tbpref}feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
                 VALUES ($feedId, 'Count Article $i', 'https://example.com/count-art$i', 'Desc', " . time() . ")"
            );
        }

        // Now 5 articles
        $count = $service->countFeedLinks((string)$feedId);
        $this->assertEquals(5, $count);
    }
}
