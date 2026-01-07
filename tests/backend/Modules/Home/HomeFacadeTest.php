<?php declare(strict_types=1);
namespace Lwt\Tests\Modules\Home;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Modules\Home\Application\HomeFacade;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the HomeFacade class.
 *
 * Tests the business logic for the home/dashboard page.
 */
class HomeFacadeTest extends TestCase
{
    private static bool $dbConnected = false;
    private HomeFacade $facade;

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
    }

    protected function setUp(): void
    {
        $this->facade = new HomeFacade();
    }

    // ===== getDashboardData() tests =====

    public function testGetDashboardDataReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDashboardData();

        $this->assertIsArray($result);
    }

    public function testGetDashboardDataHasExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDashboardData();

        $expectedKeys = [
            'language_count',
            'current_language_id',
            'current_text_id',
            'current_text_info',
            'is_wordpress',
            'is_multi_user'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: $key");
        }
    }

    public function testGetDashboardDataLanguageCountIsInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDashboardData();

        $this->assertIsInt($result['language_count']);
    }

    public function testGetDashboardDataIsWordpressIsBool(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDashboardData();

        $this->assertIsBool($result['is_wordpress']);
    }

    public function testGetDashboardDataCurrentTextInfoMatchesCurrentTextId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDashboardData();

        if ($result['current_text_id'] === null) {
            $this->assertNull($result['current_text_info']);
        } else {
            // If there's a current text ID, there should be text info (or null if text was deleted)
            $this->assertTrue(
                $result['current_text_info'] === null || is_array($result['current_text_info']),
                'current_text_info should be null or array'
            );
        }
    }

    // ===== getLanguageCount() tests =====

    public function testGetLanguageCountReturnsInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageCount();

        $this->assertIsInt($result);
    }

    public function testGetLanguageCountIsNonNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageCount();

        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== getCurrentLanguageId() tests =====

    public function testGetCurrentLanguageIdReturnsNullOrInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getCurrentLanguageId();

        $this->assertTrue(
            $result === null || is_int($result),
            'Expected null or int, got ' . gettype($result)
        );
    }

    // ===== getCurrentTextId() tests =====

    public function testGetCurrentTextIdReturnsNullOrInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getCurrentTextId();

        $this->assertTrue(
            $result === null || is_int($result),
            'Expected null or int, got ' . gettype($result)
        );
    }

    // ===== getDatabaseSize() tests =====

    public function testGetDatabaseSizeReturnsFloat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDatabaseSize();

        $this->assertIsFloat($result);
    }

    public function testGetDatabaseSizeIsNonNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDatabaseSize();

        $this->assertGreaterThanOrEqual(0.0, $result);
    }

    // ===== getLanguageName() tests =====

    public function testGetLanguageNameReturnsEmptyForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageName(999999);

        $this->assertSame('', $result);
    }

    public function testGetLanguageNameReturnsStringForExisting(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $langId = Connection::fetchValue(
            "SELECT LgID AS value FROM languages LIMIT 1"
        );

        if ($langId === null) {
            $this->markTestSkipped('No languages in database to test');
        }

        $result = $this->facade->getLanguageName((int)$langId);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ===== getLastTextInfo() tests =====

    public function testGetLastTextInfoReturnsNullForNullTextId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLastTextInfo(null, null);

        $this->assertNull($result);
    }

    public function testGetLastTextInfoReturnsArrayForExistingText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $textId = Connection::fetchValue(
            "SELECT TxID AS value FROM texts LIMIT 1"
        );

        if ($textId === null) {
            $this->markTestSkipped('No texts in database to test');
        }

        $dashboardData = $this->facade->getDashboardData();

        // Skip if no current text info
        if ($dashboardData['current_text_info'] === null) {
            $this->markTestSkipped('No current text info in database');
        }

        $result = $this->facade->getLastTextInfo(
            $dashboardData['current_text_id'],
            $dashboardData['current_text_info']
        );

        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('id', $result);
            $this->assertArrayHasKey('title', $result);
            $this->assertArrayHasKey('stats', $result);
        }
    }

    // ===== Integration tests =====

    public function testLanguageCountConsistentWithGetLanguageName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $count = $this->facade->getLanguageCount();

        if ($count > 0) {
            // If there are languages, we should be able to get at least one name
            $langId = Connection::fetchValue(
                "SELECT LgID AS value FROM languages LIMIT 1"
            );

            if ($langId !== null) {
                $name = $this->facade->getLanguageName((int)$langId);
                $this->assertNotEmpty($name, 'Should get name for existing language');
            }
        }

        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testDashboardDataConsistentWithIndividualMethods(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $dashboardData = $this->facade->getDashboardData();

        $this->assertSame(
            $this->facade->getLanguageCount(),
            $dashboardData['language_count'],
            'Language count should match'
        );
    }
}
