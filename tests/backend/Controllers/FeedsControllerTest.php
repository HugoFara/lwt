<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\FeedsController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Modules\Feed\FeedServiceProvider;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/FeedsController.php';

/**
 * Unit tests for the FeedsController class.
 *
 * Tests controller initialization, service integration,
 * and verifies the MVC pattern implementation.
 */
class FeedsControllerTest extends TestCase
{
    private static bool $dbConnected = false;
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

        if (self::$dbConnected) {
            // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
            $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM languages");
            Connection::query("ALTER TABLE languages AUTO_INCREMENT = " . ((int)$maxId + 1));

            // Reset auto_increment for newsfeeds table (NfID is tinyint max 255)
            $maxNfId = Connection::fetchValue("SELECT COALESCE(MAX(NfID), 0) AS value FROM newsfeeds");
            Connection::query("ALTER TABLE newsfeeds AUTO_INCREMENT = " . ((int)$maxNfId + 1));

            // Create a test language if it doesn't exist
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM languages WHERE LgName = 'FeedsControllerTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
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

        // Clean up test feeds
        Connection::query("DELETE FROM feedlinks WHERE FlNfID IN (SELECT NfID FROM newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%')");
        Connection::query("DELETE FROM newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%'");
        Connection::query("DELETE FROM languages WHERE LgName = 'FeedsControllerTestLang'");

        // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
        $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM languages");
        Connection::query("ALTER TABLE languages AUTO_INCREMENT = " . ((int)$maxId + 1));

        // Reset auto_increment for newsfeeds table
        $maxNfId = Connection::fetchValue("SELECT COALESCE(MAX(NfID), 0) AS value FROM newsfeeds");
        Connection::query("ALTER TABLE newsfeeds AUTO_INCREMENT = " . ((int)$maxNfId + 1));
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
        Connection::query("DELETE FROM feedlinks WHERE FlNfID IN (SELECT NfID FROM newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%')");
        Connection::query("DELETE FROM newsfeeds WHERE NfName LIKE 'Ctrl Test Feed%'");

        // Reset auto_increment for newsfeeds table to prevent overflow
        $maxNfId = Connection::fetchValue("SELECT COALESCE(MAX(NfID), 0) AS value FROM newsfeeds");
        Connection::query("ALTER TABLE newsfeeds AUTO_INCREMENT = " . ((int)$maxNfId + 1));
    }

    /**
     * Helper method to get FeedFacade from container.
     *
     * @return FeedFacade
     */
    private function getFeedFacade(): FeedFacade
    {
        $container = Container::getInstance();
        $provider = new FeedServiceProvider();
        $provider->register($container);
        $provider->boot($container);

        return $container->get(FeedFacade::class);
    }

    /**
     * Helper method to create a FeedsController with its dependencies.
     *
     * @return FeedsController
     */
    private function createController(): FeedsController
    {
        return new FeedsController($this->getFeedFacade(), new LanguageFacade());
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertInstanceOf(FeedsController::class, $controller);
    }

    // ===== Service integration tests =====

    public function testFeedServiceCanCreateFeedThroughController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Create a service instance directly (controller uses FeedFacade internally)
        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();

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
                "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
                 VALUES ($feedId, 'Ctrl Article $i', 'https://example.com/ctrl-art$i', 'Desc', " . time() . ")"
            );
        }

        // Delete articles
        $count = $service->deleteArticles((string)$feedId);
        $this->assertEquals(3, $count);

        // Verify articles are gone but feed remains
        $articleCount = (int)Connection::fetchValue(
            "SELECT COUNT(*) AS value FROM feedlinks WHERE FlNfID = $feedId"
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

        $controller = $this->createController();

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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();
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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();
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

        $service = $this->getFeedFacade();

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
                "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
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

        $service = $this->getFeedFacade();

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

        $service = $this->getFeedFacade();

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
                "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
                 VALUES ($feedId, 'Count Article $i', 'https://example.com/count-art$i', 'Desc', " . time() . ")"
            );
        }

        // Now 5 articles
        $count = $service->countFeedLinks((string)$feedId);
        $this->assertEquals(5, $count);
    }

    // ===== Controller getFeedService tests =====

    public function testControllerHasGetFeedServiceMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();

        $this->assertTrue(method_exists($controller, 'getFeedService'));
    }

    public function testGetFeedServiceReturnsFeedFacade(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $service = $controller->getFeedService();

        $this->assertInstanceOf(FeedFacade::class, $service);
    }

    public function testGetFeedServiceReturnsConsistentInstance(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = $this->createController();
        $service1 = $controller->getFeedService();
        $service2 = $controller->getFeedService();

        $this->assertSame($service1, $service2);
    }

    // ===== FeedService getFeeds tests =====

    public function testGetFeedsReturnsArrayForLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        // Create a feed for testing
        $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed GetFeeds',
            'NfSourceURI' => 'https://example.com/getfeeds',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        $feeds = $service->getFeeds(self::$testLangId);

        $this->assertIsArray($feeds);
        $this->assertNotEmpty($feeds);

        // Verify our feed is in the list
        $found = false;
        foreach ($feeds as $feed) {
            if ($feed['NfName'] === 'Ctrl Test Feed GetFeeds') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetFeedsReturnsEmptyArrayForNonExistentLanguage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();
        $feeds = $service->getFeeds(99999);

        $this->assertIsArray($feeds);
        $this->assertEmpty($feeds);
    }

    public function testGetFeedsReturnsAllFeedsWhenLanguageIsNull(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        // Create a feed for testing
        $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed AllFeeds',
            'NfSourceURI' => 'https://example.com/allfeeds',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        $feeds = $service->getFeeds(null);

        $this->assertIsArray($feeds);

        // Should contain our test feed
        $found = false;
        foreach ($feeds as $feed) {
            if ($feed['NfName'] === 'Ctrl Test Feed AllFeeds') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ===== FeedService getFeedLinks tests =====

    public function testGetFeedLinksReturnsArticles(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed GetLinks',
            'NfSourceURI' => 'https://example.com/getlinks',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        // Add articles
        Connection::execute(
            "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate, FlText)
             VALUES ($feedId, 'Link Article 1', 'https://example.com/link1', 'Description 1', " . time() . ", 'Text content')"
        );

        $links = $service->getFeedLinks((string)$feedId);

        $this->assertIsArray($links);
        $this->assertCount(1, $links);
        $this->assertEquals('Link Article 1', $links[0]['FlTitle']);
    }

    public function testGetFeedLinksWithPagination(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Pagination',
            'NfSourceURI' => 'https://example.com/pagination',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        // Add 10 articles
        for ($i = 1; $i <= 10; $i++) {
            Connection::execute(
                "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
                 VALUES ($feedId, 'Page Article $i', 'https://example.com/page$i', 'Desc $i', " . (time() + $i) . ")"
            );
        }

        // Test limit
        $links = $service->getFeedLinks((string)$feedId, '', 'FlDate DESC', 0, 5);
        $this->assertCount(5, $links);

        // Test offset
        $links = $service->getFeedLinks((string)$feedId, '', 'FlDate DESC', 5, 5);
        $this->assertCount(5, $links);
    }

    // ===== FeedService getMarkedFeedLinks tests =====

    public function testGetMarkedFeedLinksReturnsCorrectData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Marked',
            'NfSourceURI' => 'https://example.com/marked',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => 'edit_text=1',
        ]);

        Connection::execute(
            "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate, FlText)
             VALUES ($feedId, 'Marked Article', 'https://example.com/marked-art', 'Desc', " . time() . ", 'Text content')"
        );

        $linkId = (int)Connection::fetchValue("SELECT LAST_INSERT_ID() AS value");

        $links = $service->getMarkedFeedLinks((string)$linkId);

        $this->assertIsArray($links);
        $this->assertCount(1, $links);
        $this->assertEquals('Marked Article', $links[0]['FlTitle']);
        $this->assertEquals('Ctrl Test Feed Marked', $links[0]['NfName']);
        $this->assertEquals('edit_text=1', $links[0]['NfOptions']);
    }

    // ===== FeedService getNfOption tests =====

    public function testGetNfOptionReturnsCorrectValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $optionsStr = 'edit_text=1,max_texts=10,tag=News';

        $this->assertEquals('1', $service->getNfOption($optionsStr, 'edit_text'));
        $this->assertEquals('10', $service->getNfOption($optionsStr, 'max_texts'));
        $this->assertEquals('News', $service->getNfOption($optionsStr, 'tag'));
        $this->assertNull($service->getNfOption($optionsStr, 'nonexistent'));
    }

    public function testGetNfOptionReturnsAllOptionsWhenKeyIsAll(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $optionsStr = 'edit_text=1,max_texts=10';
        $result = $service->getNfOption($optionsStr, 'all');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('edit_text', $result);
        $this->assertArrayHasKey('max_texts', $result);
    }

    public function testGetNfOptionReturnsNullForEmptyKey(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $optionsStr = 'edit_text=1,max_texts=10';
        $result = $service->getNfOption($optionsStr, '');

        $this->assertNull($result);
    }

    // ===== FeedService markLinkAsError tests =====

    public function testMarkLinkAsErrorPrependsSpace(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed ErrorMark',
            'NfSourceURI' => 'https://example.com/errormark',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        $linkUrl = 'https://example.com/error-link';
        Connection::execute(
            "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
             VALUES ($feedId, 'Error Article', '$linkUrl', 'Desc', " . time() . ")"
        );

        $service->markLinkAsError($linkUrl);

        $result = Connection::fetchValue(
            "SELECT FlLink AS value FROM feedlinks WHERE FlNfID = $feedId"
        );

        $this->assertStringStartsWith(' ', $result);
        $this->assertEquals(' ' . $linkUrl, $result);
    }

    // ===== FeedService resetUnloadableArticles tests =====

    public function testResetUnloadableArticlesTrimsSpace(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = $this->getFeedFacade();

        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Feed Reset',
            'NfSourceURI' => 'https://example.com/reset',
            'NfArticleSectionTags' => 'article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        // Insert article with leading space (error state)
        Connection::execute(
            "INSERT INTO feedlinks (FlNfID, FlTitle, FlLink, FlDescription, FlDate)
             VALUES ($feedId, 'Reset Article', ' https://example.com/reset-link', 'Desc', " . time() . ")"
        );

        $count = $service->resetUnloadableArticles((string)$feedId);

        $this->assertGreaterThanOrEqual(0, $count);

        $result = Connection::fetchValue(
            "SELECT FlLink AS value FROM feedlinks WHERE FlNfID = $feedId"
        );

        $this->assertEquals('https://example.com/reset-link', $result);
    }
}
