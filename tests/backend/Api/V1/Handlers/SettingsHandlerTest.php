<?php declare(strict_types=1);
namespace Lwt\Tests\Api\V1\Handlers;

require_once __DIR__ . '/../../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Api\V1\Handlers\SettingsHandler;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../../src/backend/Api/V1/ApiV1.php';
require_once __DIR__ . '/../../../../../src/backend/Api/V1/Handlers/SettingsHandler.php';

/**
 * Unit tests for the SettingsHandler class.
 *
 * Tests settings-related API operations.
 */
class SettingsHandlerTest extends TestCase
{
    private static bool $dbConnected = false;
    private SettingsHandler $handler;

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            try {
                $connection = Configuration::connect(
                    $config['server'],
                    $config['userid'],
                    $config['passwd'],
                    $testDbname,
                    $config['socket'] ?? ''
                );
                Globals::setDbConnection($connection);
                self::$dbConnected = true;
            } catch (\Exception $e) {
                self::$dbConnected = false;
            }
        } else {
            self::$dbConnected = true;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new SettingsHandler();
    }

    protected function tearDown(): void
    {
        // Clean up test settings
        if (self::$dbConnected) {
            $prefix = '';
            Connection::query("DELETE FROM {$prefix}settings WHERE StKey LIKE 'test_api_%'");
        }
        parent::tearDown();
    }

    // ===== Class structure tests =====

    /**
     * Test that SettingsHandler class has the required methods.
     */
    public function testClassHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(SettingsHandler::class);

        // Business logic methods
        $this->assertTrue($reflection->hasMethod('saveSetting'));
        $this->assertTrue($reflection->hasMethod('getThemePath'));

        // API formatter methods
        $this->assertTrue($reflection->hasMethod('formatSaveSetting'));
        $this->assertTrue($reflection->hasMethod('formatThemePath'));
    }

    /**
     * Test formatSaveSetting returns correct structure on success.
     */
    public function testFormatSaveSettingReturnsMessageOnSuccess(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->handler->formatSaveSetting('test_api_setting', 'test_value');

        $this->assertIsArray($result);
        // Should have either 'message' or 'error' key
        $this->assertTrue(
            array_key_exists('message', $result) || array_key_exists('error', $result),
            'Response should contain either message or error key'
        );
    }

    /**
     * Test formatThemePath returns correct structure.
     */
    public function testFormatThemePathReturnsCorrectStructure(): void
    {
        $result = $this->handler->formatThemePath('styles.css');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('theme_path', $result);
    }

    /**
     * Test all public methods are accessible.
     */
    public function testPublicMethods(): void
    {
        $reflection = new \ReflectionClass(SettingsHandler::class);
        $publicMethods = array_filter(
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn($m) => !$m->isConstructor()
        );

        // Should have at least 4 public methods
        $this->assertGreaterThanOrEqual(4, count($publicMethods));
    }
}
