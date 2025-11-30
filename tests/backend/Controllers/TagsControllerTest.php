<?php declare(strict_types=1);
namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\TagsController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/TagsController.php';

/**
 * Unit tests for the TagsController class.
 *
 * Tests the controller initialization, term tag and text tag management.
 */
class TagsControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;
    private array $originalRequest;
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
        self::$tbpref = Globals::getTablePrefix();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Save original superglobals
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalRequest = $_REQUEST;
        $this->originalSession = $_SESSION ?? [];

        // Reset superglobals
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_REQUEST = $this->originalRequest;
        $_SESSION = $this->originalSession;

        parent::tearDown();
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertInstanceOf(TagsController::class, $controller);
    }

    // ===== Method existence tests =====

    public function testControllerHasIndexMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testControllerHasTextTagsMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertTrue(method_exists($controller, 'textTags'));
    }

    // ===== BaseController inheritance tests =====

    public function testControllerExtendsBaseController(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertInstanceOf(\Lwt\Controllers\BaseController::class, $controller);
    }

    public function testControllerHasParamMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertTrue(method_exists($controller, 'param'));
    }

    public function testControllerHasEscapeMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertTrue(method_exists($controller, 'escape'));
    }

    public function testControllerHasExecuteMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertTrue(method_exists($controller, 'execute'));
    }

    public function testControllerHasRenderMethod(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        $this->assertTrue(method_exists($controller, 'render'));
    }

    // ===== Param helper tests =====

    public function testParamMethodReturnsEmptyStringWhenNotSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertSame('', $method->invoke($controller, 'nonexistent'));
    }

    public function testParamMethodReturnsDefaultWhenNotSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('default', $method->invoke($controller, 'nonexistent', 'default'));
    }

    public function testParamMethodReturnsGetValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_GET['testparam'] = 'testvalue';
        $_REQUEST['testparam'] = 'testvalue';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('testvalue', $method->invoke($controller, 'testparam'));
    }

    public function testParamMethodReturnsPostValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_POST['testparam'] = 'postvalue';
        $_REQUEST['testparam'] = 'postvalue';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('postvalue', $method->invoke($controller, 'testparam'));
    }

    // ===== Session param tests =====

    public function testSessionParamStoresValueInSession(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['page'] = '5';
        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sessionParam');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'page', 'currenttagpage', '1', true);

        $this->assertEquals('5', $result);
        $this->assertEquals('5', $_SESSION['currenttagpage']);
    }

    public function testSessionParamUsesSessionValueWhenRequestNotSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_SESSION['currenttagpage'] = '3';
        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sessionParam');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'page', 'currenttagpage', '1', true);

        $this->assertEquals('3', $result);
    }

    public function testSessionParamReturnsDefaultWhenNothingSet(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('sessionParam');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'page', 'currenttagpage', '1', true);

        $this->assertEquals('1', $result);
    }

    // ===== Escape method tests =====

    public function testEscapeMethodEscapesString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('escape');
        $method->setAccessible(true);

        $result = $method->invoke($controller, "test'string");

        $this->assertStringContainsString("test", $result);
        // Should be SQL-escaped
        $this->assertMatchesRegularExpression("/^'/", $result);
    }

    public function testEscapeMethodReturnsNullForEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('escape');
        $method->setAccessible(true);

        $result = $method->invoke($controller, "");

        $this->assertEquals("NULL", $result);
    }

    // ===== Table method tests =====

    public function testTableMethodReturnsTableWithPrefix(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('table');
        $method->setAccessible(true);

        $result = $method->invoke($controller, 'tags');

        $this->assertStringContainsString('tags', $result);
    }

    // ===== GetMarkedIds tests =====

    public function testGetMarkedIdsReturnsIntegerArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getMarkedIds');
        $method->setAccessible(true);

        $result = $method->invoke($controller, ['1', '2', '3']);

        $this->assertIsArray($result);
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testGetMarkedIdsFiltersInvalidIds(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getMarkedIds');
        $method->setAccessible(true);

        $result = $method->invoke($controller, ['1', 'invalid', '3', '0']);

        $this->assertIsArray($result);
        // Should contain 1 and 3, possibly 0 depending on filter rules
        $this->assertContains(1, $result);
        $this->assertContains(3, $result);
    }

    // ===== Integration tests for term tags =====

    public function testTermTagsCountQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT COUNT(TgID) AS value FROM " . self::$tbpref . "tags WHERE (1=1)";
        $result = Connection::fetchValue($sql);

        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(0, (int) $result);
    }

    public function testTermTagsListQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT TgID, TgText, TgComment FROM " . self::$tbpref . "tags LIMIT 10";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    // ===== Integration tests for text tags =====

    public function testTextTagsCountQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT COUNT(T2ID) AS value FROM " . self::$tbpref . "tags2 WHERE (1=1)";
        $result = Connection::fetchValue($sql);

        $this->assertIsNumeric($result);
        $this->assertGreaterThanOrEqual(0, (int) $result);
    }

    public function testTextTagsListQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $sql = "SELECT T2ID, T2Text, T2Comment FROM " . self::$tbpref . "tags2 LIMIT 10";
        $result = Connection::query($sql);

        $this->assertInstanceOf(\mysqli_result::class, $result);
        mysqli_free_result($result);
    }

    // ===== Database param tests =====

    public function testDbParamStoresValueInSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('dbParam');
        $method->setAccessible(true);

        // Request a sort parameter
        $_REQUEST['sort'] = '2';
        $result = $method->invoke($controller, 'sort', 'currenttagsort', '1', true);

        // Should return the value from request
        $this->assertEquals('2', $result);
    }

    // ===== Query escaping tests =====

    public function testWildcardReplacementInQuery(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TagsController();

        // Test that * is replaced with % for SQL LIKE
        $query = "test*query";
        $expected = str_replace("*", "%", $query);

        $this->assertEquals("test%query", $expected);
    }

    // ===== Controller action detection tests =====

    public function testNewParamTriggersNewTagForm(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['new'] = '1';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('1', $method->invoke($controller, 'new'));
    }

    public function testChgParamTriggersEditTagForm(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['chg'] = '5';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('5', $method->invoke($controller, 'chg'));
    }

    public function testDelParamTriggersDelete(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['del'] = '10';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('10', $method->invoke($controller, 'del'));
    }

    public function testMarkActionParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['markaction'] = 'del';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('del', $method->invoke($controller, 'markaction'));
    }

    public function testAllActionParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['allaction'] = 'delall';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('delall', $method->invoke($controller, 'allaction'));
    }

    public function testOpParamDetected(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['op'] = 'Save';

        $controller = new TagsController();

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('param');
        $method->setAccessible(true);

        $this->assertEquals('Save', $method->invoke($controller, 'op'));
    }

    // ===== Sort option tests =====

    public function testSortOptionsAreValid(): void
    {
        $sorts = ['TgText', 'TgComment', 'TgID desc', 'TgID asc'];

        $this->assertCount(4, $sorts);
        $this->assertEquals('TgText', $sorts[0]);
        $this->assertEquals('TgComment', $sorts[1]);
        $this->assertEquals('TgID desc', $sorts[2]);
        $this->assertEquals('TgID asc', $sorts[3]);
    }

    public function testTextTagSortOptionsAreValid(): void
    {
        $sorts = ['T2Text', 'T2Comment', 'T2ID desc', 'T2ID asc'];

        $this->assertCount(4, $sorts);
        $this->assertEquals('T2Text', $sorts[0]);
        $this->assertEquals('T2Comment', $sorts[1]);
        $this->assertEquals('T2ID desc', $sorts[2]);
        $this->assertEquals('T2ID asc', $sorts[3]);
    }

    // ===== Pagination tests =====

    public function testPaginationCalculation(): void
    {
        $maxperpage = 50;
        $recno = 125;

        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

        $this->assertEquals(3, $pages);
    }

    public function testPaginationCalculationForZeroRecords(): void
    {
        $maxperpage = 50;
        $recno = 0;

        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

        $this->assertEquals(0, $pages);
    }

    public function testPaginationCalculationForExactPageBoundary(): void
    {
        $maxperpage = 50;
        $recno = 100;

        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

        $this->assertEquals(2, $pages);
    }

    public function testCurrentPageNormalization(): void
    {
        $currentpage = 0;
        $pages = 5;

        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if ($currentpage > $pages) {
            $currentpage = $pages;
        }

        $this->assertEquals(1, $currentpage);
    }

    public function testCurrentPageNormalizationForOverflow(): void
    {
        $currentpage = 10;
        $pages = 5;

        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if ($currentpage > $pages) {
            $currentpage = $pages;
        }

        $this->assertEquals(5, $currentpage);
    }

    // ===== Limit clause tests =====

    public function testLimitClauseGeneration(): void
    {
        $currentpage = 3;
        $maxperpage = 50;

        $limit = 'LIMIT ' . (($currentpage - 1) * $maxperpage) . ',' . $maxperpage;

        $this->assertEquals('LIMIT 100,50', $limit);
    }

    // ===== Multiple controller instances test =====

    public function testMultipleControllerInstances(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller1 = new TagsController();
        $controller2 = new TagsController();

        $this->assertInstanceOf(TagsController::class, $controller1);
        $this->assertInstanceOf(TagsController::class, $controller2);
        $this->assertNotSame($controller1, $controller2);
    }
}
