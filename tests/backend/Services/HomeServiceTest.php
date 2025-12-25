<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\Settings;
use Lwt\Services\HomeService;
use Lwt\Modules\Admin\Application\AdminFacade;
use Lwt\Modules\Admin\Infrastructure\MySqlSettingsRepository;
use Lwt\Modules\Admin\Infrastructure\MySqlBackupRepository;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/HomeService.php';

/**
 * Unit tests for the HomeService class.
 *
 * Tests the business logic for the home/dashboard page.
 */
class HomeServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private HomeService $service;
    private AdminFacade $adminFacade;

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
        $this->service = new HomeService();
        $settingsRepo = new MySqlSettingsRepository();
        $backupRepo = new MySqlBackupRepository();
        $this->adminFacade = new AdminFacade($settingsRepo, $backupRepo);
    }

    // ===== getCurrentTextInfo() tests =====

    public function testGetCurrentTextInfoReturnsNullForNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getCurrentTextInfo(999999);

        $this->assertNull($result);
    }

    public function testGetCurrentTextInfoReturnsArrayForExistingText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // First check if there's any text in the database
        $textId = Connection::fetchValue(
            "SELECT TxID AS value FROM texts LIMIT 1"
        );

        if ($textId === null) {
            $this->markTestSkipped('No texts in database to test');
        }

        $result = $this->service->getCurrentTextInfo((int)$textId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('exists', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('language_id', $result);
        $this->assertArrayHasKey('language_name', $result);
        $this->assertArrayHasKey('annotated', $result);
    }

    public function testGetCurrentTextInfoExistsIsTrue(): void
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

        $result = $this->service->getCurrentTextInfo((int)$textId);

        $this->assertTrue($result['exists']);
    }

    public function testGetCurrentTextInfoTitleIsString(): void
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

        $result = $this->service->getCurrentTextInfo((int)$textId);

        $this->assertIsString($result['title']);
    }

    public function testGetCurrentTextInfoLanguageIdIsInt(): void
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

        $result = $this->service->getCurrentTextInfo((int)$textId);

        $this->assertIsInt($result['language_id']);
    }

    public function testGetCurrentTextInfoAnnotatedIsBool(): void
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

        $result = $this->service->getCurrentTextInfo((int)$textId);

        $this->assertIsBool($result['annotated']);
    }

    // ===== getLanguageName() tests =====

    public function testGetLanguageNameReturnsEmptyForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageName(999999);

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

        $result = $this->service->getLanguageName((int)$langId);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ===== getLanguageCount() tests =====

    public function testGetLanguageCountReturnsInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageCount();

        $this->assertIsInt($result);
    }

    public function testGetLanguageCountIsNonNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageCount();

        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== getCurrentLanguageId() tests =====

    public function testGetCurrentLanguageIdReturnsNullOrInt(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getCurrentLanguageId();

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

        $result = $this->service->getCurrentTextId();

        $this->assertTrue(
            $result === null || is_int($result),
            'Expected null or int, got ' . gettype($result)
        );
    }

    // ===== isWordPressSession() tests =====

    public function testIsWordPressSessionReturnsBool(): void
    {
        $result = $this->service->isWordPressSession();

        $this->assertIsBool($result);
    }

    public function testIsWordPressSessionReturnsFalseByDefault(): void
    {
        // Make sure $_SESSION['LWT-WP-User'] is not set
        unset($_SESSION['LWT-WP-User']);

        $result = $this->service->isWordPressSession();

        $this->assertFalse($result);
    }

    public function testIsWordPressSessionReturnsTrueWhenSet(): void
    {
        $_SESSION['LWT-WP-User'] = 'test_user';

        $result = $this->service->isWordPressSession();

        $this->assertTrue($result);

        // Clean up
        unset($_SESSION['LWT-WP-User']);
    }

    // ===== getDatabaseSize() tests =====

    public function testGetDatabaseSizeReturnsFloat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDatabaseSize();

        $this->assertIsFloat($result);
    }

    public function testGetDatabaseSizeIsNonNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDatabaseSize();

        $this->assertGreaterThanOrEqual(0.0, $result);
    }

    // ===== getServerData() tests =====

    public function testGetServerDataReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Set up server environment variables for testing
        $_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4.0 (Test)';
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $result = $this->adminFacade->getServerData();

        $this->assertIsArray($result);
    }

    public function testGetServerDataHasExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Set up server environment variables for testing
        $_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4.0 (Test)';
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $result = $this->adminFacade->getServerData();

        $expectedKeys = ['db_name', 'db_size', 'server_soft', 'apache', 'php', 'mysql', 'lwt_version', 'server_location'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: $key");
        }
    }

    public function testGetServerDataDbSizeIsFloat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Set up server environment variables for testing
        $_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4.0 (Test)';
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $result = $this->adminFacade->getServerData();

        $this->assertIsFloat($result['db_size']);
    }

    public function testGetServerDataServerSoftwareIsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Set up server environment variables for testing
        $_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4.0 (Test)';
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $result = $this->adminFacade->getServerData();

        $this->assertIsString($result['server_soft']);
    }

    public function testGetServerDataMysqlIsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Set up server environment variables for testing
        $_SERVER['SERVER_SOFTWARE'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4.0 (Test)';
        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $result = $this->adminFacade->getServerData();

        $this->assertIsString($result['mysql']);
    }

    // ===== getDashboardData() tests =====

    public function testGetDashboardDataReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDashboardData();

        $this->assertIsArray($result);
    }

    public function testGetDashboardDataHasExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDashboardData();

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

        $result = $this->service->getDashboardData();

        $this->assertIsInt($result['language_count']);
    }

    public function testGetDashboardDataIsWordpressIsBool(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDashboardData();

        $this->assertIsBool($result['is_wordpress']);
    }

    public function testGetDashboardDataCurrentTextInfoMatchesCurrentTextId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getDashboardData();

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

    // ===== Integration tests =====

    public function testLanguageCountConsistentWithGetLanguageName(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $count = $this->service->getLanguageCount();

        if ($count > 0) {
            // If there are languages, we should be able to get at least one name
            $langId = Connection::fetchValue(
                "SELECT LgID AS value FROM languages LIMIT 1"
            );

            if ($langId !== null) {
                $name = $this->service->getLanguageName((int)$langId);
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

        $dashboardData = $this->service->getDashboardData();

        $this->assertSame(
            $this->service->getLanguageCount(),
            $dashboardData['language_count'],
            'Language count should match'
        );

        $this->assertSame(
            $this->service->isWordPressSession(),
            $dashboardData['is_wordpress'],
            'WordPress session flag should match'
        );
    }
}
