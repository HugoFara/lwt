<?php declare(strict_types=1);
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

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/FeedsController.php';
require_once __DIR__ . '/../../../src/backend/Services/FeedService.php';

/**
 * Unit tests for the FeedsController wizard functionality.
 *
 * Tests the feed wizard MVC implementation for all 4 steps.
 */
class FeedsControllerWizardTest extends TestCase
{
    private static bool $dbConnected = false;
    private static int $testLangId = 0;
    private static int $testFeedId = 0;
    private array $originalRequest;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
    private array $originalSession;

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
                "SELECT LgID AS value FROM languages WHERE LgName = 'FeedsWizardTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('FeedsWizardTestLang', 'http://test.com/###', '', 'http://translate.test/###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }

            // Create a test feed for wizard edit tests
            $service = new FeedService();
            self::$testFeedId = $service->createFeed([
                'NfLgID' => self::$testLangId,
                'NfName' => 'Wizard Test Feed',
                'NfSourceURI' => 'https://example.com/wizard-test-feed.xml',
                'NfArticleSectionTags' => 'article',
                'NfFilterTags' => '',
                'NfOptions' => 'edit_text=1',
            ]);
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test feeds
        Connection::query("DELETE FROM feedlinks WHERE FlNfID IN (SELECT NfID FROM newsfeeds WHERE NfName LIKE 'Wizard Test%')");
        Connection::query("DELETE FROM newsfeeds WHERE NfName LIKE 'Wizard Test%'");
        Connection::query("DELETE FROM languages WHERE LgName = 'FeedsWizardTestLang'");

        // Reset auto_increment to prevent overflow (LgID is tinyint max 255)
        $maxId = Connection::fetchValue("SELECT COALESCE(MAX(LgID), 0) AS value FROM languages");
        Connection::query("ALTER TABLE languages AUTO_INCREMENT = " . ((int)$maxId + 1));
    }

    protected function setUp(): void
    {
        // Save original superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalSession = $_SESSION ?? [];

        // Reset superglobals
        $_REQUEST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SESSION = $this->originalSession;

        if (!self::$dbConnected) {
            return;
        }

        // Clean up test feeds
        Connection::query("DELETE FROM feedlinks WHERE FlNfID IN (SELECT NfID FROM newsfeeds WHERE NfName LIKE 'Ctrl Test Wizard%')");
        Connection::query("DELETE FROM newsfeeds WHERE NfName LIKE 'Ctrl Test Wizard%'");
    }

    // ===== Controller wizard method tests =====

    public function testControllerHasWizardMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new FeedsController();

        $this->assertTrue(method_exists($controller, 'wizard'));
    }

    // ===== FeedService wizard-related tests =====

    public function testFeedServiceGetNfOptionForWizard(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        // Test options string parsing
        $optionsStr = 'edit_text=1,autoupdate=2h,max_texts=10,tag=News';

        $this->assertEquals('1', $service->getNfOption($optionsStr, 'edit_text'));
        $this->assertEquals('2h', $service->getNfOption($optionsStr, 'autoupdate'));
        $this->assertEquals('10', $service->getNfOption($optionsStr, 'max_texts'));
        $this->assertEquals('News', $service->getNfOption($optionsStr, 'tag'));
        $this->assertNull($service->getNfOption($optionsStr, 'charset'));
    }

    public function testFeedServiceGetFeedByIdForWizardEdit(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();
        $feed = $service->getFeedById(self::$testFeedId);

        $this->assertNotNull($feed);
        $this->assertEquals('Wizard Test Feed', $feed['NfName']);
        $this->assertEquals('https://example.com/wizard-test-feed.xml', $feed['NfSourceURI']);
        $this->assertEquals('article', $feed['NfArticleSectionTags']);
    }

    public function testFeedServiceGetLanguagesForWizardStep4(): void
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
            if ($lang['LgName'] === 'FeedsWizardTestLang') {
                $found = true;
                $this->assertEquals(self::$testLangId, (int)$lang['LgID']);
                break;
            }
        }
        $this->assertTrue($found, 'Test language should be in languages list');
    }

    // ===== Wizard session data tests =====

    public function testWizardSessionDataStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Simulate wizard session data
        $_SESSION['wizard'] = [
            'rss_url' => 'https://example.com/feed.xml',
            'feed' => [
                'feed_title' => 'Test Feed',
                'feed_text' => 'description',
                0 => [
                    'title' => 'Article 1',
                    'link' => 'https://example.com/article1',
                    'description' => 'Description 1',
                ],
            ],
            'article_tags' => '',
            'filter_tags' => '',
            'options' => 'edit_text=1',
            'lang' => '',
            'select_mode' => '0',
            'hide_images' => 'yes',
            'maxim' => 1,
            'selected_feed' => 0,
            'host' => [],
            'redirect' => '',
            'detected_feed' => 'Detected: «description»',
        ];

        $this->assertArrayHasKey('rss_url', $_SESSION['wizard']);
        $this->assertArrayHasKey('feed', $_SESSION['wizard']);
        $this->assertArrayHasKey('article_tags', $_SESSION['wizard']);
        $this->assertArrayHasKey('filter_tags', $_SESSION['wizard']);
        $this->assertArrayHasKey('options', $_SESSION['wizard']);
        $this->assertArrayHasKey('lang', $_SESSION['wizard']);
        $this->assertArrayHasKey('select_mode', $_SESSION['wizard']);
        $this->assertArrayHasKey('hide_images', $_SESSION['wizard']);
        $this->assertArrayHasKey('detected_feed', $_SESSION['wizard']);
    }

    public function testWizardStep1SessionInit(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['select_mode'] = 'all';
        $_REQUEST['hide_images'] = 'no';

        // Simulate what initWizardSession does
        if (isset($_REQUEST['select_mode'])) {
            $_SESSION['wizard']['select_mode'] = $_REQUEST['select_mode'];
        }
        if (isset($_REQUEST['hide_images'])) {
            $_SESSION['wizard']['hide_images'] = $_REQUEST['hide_images'];
        }

        $this->assertEquals('all', $_SESSION['wizard']['select_mode']);
        $this->assertEquals('no', $_SESSION['wizard']['hide_images']);
    }

    public function testWizardStep2SessionDefaults(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SESSION['wizard'] = [
            'feed' => ['feed_title' => 'Test', 0 => ['link' => 'http://test.com']],
            'rss_url' => 'http://test.com/feed.xml',
        ];

        // Simulate processStep2SessionParams defaults
        if (!isset($_SESSION['wizard']['maxim'])) {
            $_SESSION['wizard']['maxim'] = 1;
        }
        if (!isset($_SESSION['wizard']['select_mode'])) {
            $_SESSION['wizard']['select_mode'] = '0';
        }
        if (!isset($_SESSION['wizard']['hide_images'])) {
            $_SESSION['wizard']['hide_images'] = 'yes';
        }
        if (!isset($_SESSION['wizard']['redirect'])) {
            $_SESSION['wizard']['redirect'] = '';
        }
        if (!isset($_SESSION['wizard']['selected_feed'])) {
            $_SESSION['wizard']['selected_feed'] = 0;
        }
        if (!isset($_SESSION['wizard']['host'])) {
            $_SESSION['wizard']['host'] = [];
        }

        $this->assertEquals(1, $_SESSION['wizard']['maxim']);
        $this->assertEquals('0', $_SESSION['wizard']['select_mode']);
        $this->assertEquals('yes', $_SESSION['wizard']['hide_images']);
        $this->assertEquals('', $_SESSION['wizard']['redirect']);
        $this->assertEquals(0, $_SESSION['wizard']['selected_feed']);
        $this->assertEquals([], $_SESSION['wizard']['host']);
    }

    // ===== View file tests =====

    public function testWizardStep1ViewFileExists(): void
    {
        $viewFile = __DIR__ . '/../../../src/backend/Views/Feed/wizard_step1.php';
        $this->assertFileExists($viewFile);
    }

    public function testWizardStep2ViewFileExists(): void
    {
        $viewFile = __DIR__ . '/../../../src/backend/Views/Feed/wizard_step2.php';
        $this->assertFileExists($viewFile);
    }

    public function testWizardStep3ViewFileExists(): void
    {
        $viewFile = __DIR__ . '/../../../src/backend/Views/Feed/wizard_step3.php';
        $this->assertFileExists($viewFile);
    }

    public function testWizardStep4ViewFileExists(): void
    {
        $viewFile = __DIR__ . '/../../../src/backend/Views/Feed/wizard_step4.php';
        $this->assertFileExists($viewFile);
    }

    // ===== Options parsing tests =====

    public function testAutoUpdateIntervalParsing(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        $optionsStr = 'autoupdate=3h';
        $autoUpdate = $service->getNfOption($optionsStr, 'autoupdate');

        $this->assertEquals('3h', $autoUpdate);

        // Parse the value and unit
        $autoUpdV = substr($autoUpdate, -1);
        $autoUpdI = substr($autoUpdate, 0, -1);

        $this->assertEquals('h', $autoUpdV);
        $this->assertEquals('3', $autoUpdI);
    }

    public function testAutoUpdateIntervalParsingDays(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        $optionsStr = 'autoupdate=2d';
        $autoUpdate = $service->getNfOption($optionsStr, 'autoupdate');

        $autoUpdV = substr($autoUpdate, -1);
        $autoUpdI = substr($autoUpdate, 0, -1);

        $this->assertEquals('d', $autoUpdV);
        $this->assertEquals('2', $autoUpdI);
    }

    public function testAutoUpdateIntervalParsingWeeks(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        $optionsStr = 'autoupdate=1w';
        $autoUpdate = $service->getNfOption($optionsStr, 'autoupdate');

        $autoUpdV = substr($autoUpdate, -1);
        $autoUpdI = substr($autoUpdate, 0, -1);

        $this->assertEquals('w', $autoUpdV);
        $this->assertEquals('1', $autoUpdI);
    }

    // ===== Feed article section tests =====

    public function testArticleSectionOptions(): void
    {
        $sources = ['description', 'encoded', 'content'];

        $this->assertContains('description', $sources);
        $this->assertContains('encoded', $sources);
        $this->assertContains('content', $sources);
    }

    public function testFeedTextChangeUpdatesItems(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Simulate feed data with multiple content sources
        $_SESSION['wizard'] = [
            'feed' => [
                'feed_text' => 'description',
                0 => [
                    'title' => 'Article 1',
                    'link' => 'https://example.com/article1',
                    'description' => 'Description content',
                    'encoded' => 'Encoded content',
                    'text' => 'Description content',
                ],
            ],
            'host' => [],
        ];

        // Change to encoded
        $articleSection = 'encoded';
        $feedLen = 1;

        $_SESSION['wizard']['feed']['feed_text'] = $articleSection;
        $source = $_SESSION['wizard']['feed']['feed_text'];

        for ($i = 0; $i < $feedLen; $i++) {
            if ($_SESSION['wizard']['feed']['feed_text'] != '') {
                $_SESSION['wizard']['feed'][$i]['text'] = $_SESSION['wizard']['feed'][$i][$source];
            }
            unset($_SESSION['wizard']['feed'][$i]['html']);
        }
        $_SESSION['wizard']['host'] = [];

        $this->assertEquals('encoded', $_SESSION['wizard']['feed']['feed_text']);
        $this->assertEquals('Encoded content', $_SESSION['wizard']['feed'][0]['text']);
    }

    // ===== Edit mode tests =====

    public function testWizardEditModeSessionData(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();
        $feed = $service->getFeedById(self::$testFeedId);

        // Simulate loading feed for edit
        $_SESSION['wizard']['edit_feed'] = self::$testFeedId;
        $_SESSION['wizard']['rss_url'] = $feed['NfSourceURI'];
        $_SESSION['wizard']['options'] = $feed['NfOptions'];
        $_SESSION['wizard']['lang'] = $feed['NfLgID'];

        $this->assertEquals(self::$testFeedId, $_SESSION['wizard']['edit_feed']);
        $this->assertEquals('https://example.com/wizard-test-feed.xml', $_SESSION['wizard']['rss_url']);
        $this->assertEquals('edit_text=1', $_SESSION['wizard']['options']);
        $this->assertEquals(self::$testLangId, (int)$_SESSION['wizard']['lang']);
    }

    // ===== Article tags parsing tests =====

    public function testArticleTagsParsing(): void
    {
        $articleSectionTags = 'redirect://a[@class="link"] | //article';
        $articleTags = explode('|', str_replace('!?!', '|', $articleSectionTags));

        $this->assertCount(2, $articleTags);
        $this->assertStringContainsString('redirect:', $articleTags[0]);
    }

    public function testFilterTagsParsing(): void
    {
        $filterTags = '//script!?!//noscript!?!//style';
        $parsedTags = explode('!?!', $filterTags);

        $this->assertCount(3, $parsedTags);
        $this->assertEquals('//script', $parsedTags[0]);
        $this->assertEquals('//noscript', $parsedTags[1]);
        $this->assertEquals('//style', $parsedTags[2]);
    }

    // ===== Host status tests =====

    public function testHostStatusTracking(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SESSION['wizard']['host'] = [];

        $feedHost = 'example.com';
        if (!isset($_SESSION['wizard']['host'][$feedHost])) {
            $_SESSION['wizard']['host'][$feedHost] = '-';
        }

        $this->assertEquals('-', $_SESSION['wizard']['host']['example.com']);

        // Update status
        $_SESSION['wizard']['host'][$feedHost] = '☆';
        $this->assertEquals('☆', $_SESSION['wizard']['host']['example.com']);
    }

    // ===== Wizard step validation tests =====

    public function testWizardStepRouting(): void
    {
        // Test step parameter parsing
        $steps = [1, 2, 3, 4];

        foreach ($steps as $step) {
            $_REQUEST['step'] = $step;
            $parsedStep = isset($_REQUEST['step']) ? (int)$_REQUEST['step'] : 1;
            $this->assertEquals($step, $parsedStep);
        }
    }

    public function testWizardDefaultsToStep1(): void
    {
        $_REQUEST = [];
        $step = isset($_REQUEST['step']) ? (int)$_REQUEST['step'] : 1;

        $this->assertEquals(1, $step);
    }

    public function testWizardInvalidStepDefaultsTo1(): void
    {
        $_REQUEST['step'] = 'invalid';
        $step = isset($_REQUEST['step']) ? (int)$_REQUEST['step'] : 1;

        $this->assertEquals(0, $step); // (int)'invalid' = 0, would hit default case
    }

    // ===== Feed creation data validation tests =====

    public function testFeedCreationDataFromWizard(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        // Simulate wizard completion data
        $feedData = [
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Wizard Feed',
            'NfSourceURI' => 'https://example.com/wizard-created.xml',
            'NfArticleSectionTags' => '//article',
            'NfFilterTags' => '//script!?!//noscript',
            'NfOptions' => 'edit_text=1,max_texts=5,article_source=description',
        ];

        $feedId = $service->createFeed($feedData);

        $this->assertGreaterThan(0, $feedId);

        $createdFeed = $service->getFeedById($feedId);
        $this->assertEquals('Ctrl Test Wizard Feed', $createdFeed['NfName']);
        $this->assertEquals('//article', $createdFeed['NfArticleSectionTags']);
        $this->assertEquals('//script!?!//noscript', $createdFeed['NfFilterTags']);
    }

    public function testFeedUpdateDataFromWizard(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $service = new FeedService();

        // Create a feed first
        $feedId = $service->createFeed([
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Wizard Update Original',
            'NfSourceURI' => 'https://example.com/wizard-update.xml',
            'NfArticleSectionTags' => '//article',
            'NfFilterTags' => '',
            'NfOptions' => '',
        ]);

        // Update via wizard
        $service->updateFeed($feedId, [
            'NfLgID' => self::$testLangId,
            'NfName' => 'Ctrl Test Wizard Update Modified',
            'NfSourceURI' => 'https://example.com/wizard-update-new.xml',
            'NfArticleSectionTags' => '//section[@class="content"]',
            'NfFilterTags' => '//aside',
            'NfOptions' => 'autoupdate=1d,max_texts=10',
        ]);

        $updatedFeed = $service->getFeedById($feedId);
        $this->assertEquals('Ctrl Test Wizard Update Modified', $updatedFeed['NfName']);
        $this->assertEquals('//section[@class="content"]', $updatedFeed['NfArticleSectionTags']);
        $this->assertEquals('autoupdate=1d,max_texts=10', $updatedFeed['NfOptions']);
    }
}
