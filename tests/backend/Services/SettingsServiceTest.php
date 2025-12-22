<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\SettingsService;
use Lwt\Database\Settings;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/SettingsService.php';

/**
 * Unit tests for the SettingsService class.
 *
 * Tests application settings management through the service layer.
 */
class SettingsServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private SettingsService $service;

    /** @var array<string, mixed> Original $_REQUEST for cleanup */
    private array $originalRequest;

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
        $this->originalRequest = $_REQUEST;
        $_REQUEST = [];
        $this->service = new SettingsService();
    }

    protected function tearDown(): void
    {
        $_REQUEST = $this->originalRequest;

        if (!self::$dbConnected) {
            return;
        }

        // Clean up test settings after each test
        $settingsTable = Globals::table('settings');
        Connection::query("DELETE FROM {$settingsTable} WHERE StKey LIKE 'test_%'");
        // Reset known settings to defaults
        Connection::query("DELETE FROM {$settingsTable} WHERE StKey = 'set-texts-per-page'");
    }

    // ===== get() tests =====

    public function testGetReturnsSettingValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        Settings::save('test_service_key', 'test_value');
        $result = $this->service->get('test_service_key');
        $this->assertEquals('test_value', $result);
    }

    public function testGetReturnsDefaultForMissingSetting(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // 'set-texts-per-page' has a default of '10'
        $result = $this->service->get('set-texts-per-page');
        $this->assertNotEmpty($result);
    }

    // ===== getAll() tests =====

    public function testGetAllReturnsAllSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getAll();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('set-theme-dir', $result);
        $this->assertArrayHasKey('set-texts-per-page', $result);
        $this->assertArrayHasKey('set-terms-per-page', $result);
        $this->assertArrayHasKey('set-tooltip-mode', $result);
    }

    public function testGetAllContainsAllExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getAll();

        $expectedKeys = [
            'set-theme-dir',
            'set-words-to-do-buttons',
            'set-tooltip-mode',
            'set-tts',
            'set-hts',
            'set-texts-per-page',
            'set-terms-per-page',
            'set-tags-per-page',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: $key");
        }
    }

    // ===== saveAll() tests =====

    public function testSaveAllSavesMultipleSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['set-texts-per-page'] = '25';
        $_REQUEST['set-terms-per-page'] = '50';

        $message = $this->service->saveAll();

        $this->assertEquals('Settings saved', $message);
        $this->assertEquals('25', Settings::get('set-texts-per-page'));
        $this->assertEquals('50', Settings::get('set-terms-per-page'));
    }

    public function testSaveAllHandlesCheckbox(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Checkbox checked (value = 1)
        $_REQUEST['set-tts'] = '1';
        $this->service->saveAll();
        $this->assertEquals('1', Settings::get('set-tts'));

        // Checkbox unchecked (key not present)
        $_REQUEST = [];
        $this->service->saveAll();
        $this->assertEquals('0', Settings::get('set-tts'));
    }

    public function testSaveAllIgnoresUnknownKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['unknown-key'] = 'value';
        $_REQUEST['another-unknown'] = 'value2';
        $_REQUEST['set-texts-per-page'] = '30';

        $message = $this->service->saveAll();

        $this->assertEquals('Settings saved', $message);
        // Unknown keys should not cause errors
        $this->assertEquals('30', Settings::get('set-texts-per-page'));
    }

    public function testSaveAllReturnsSuccessMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['set-texts-per-page'] = '20';
        $message = $this->service->saveAll();
        $this->assertEquals('Settings saved', $message);
    }

    // ===== resetAll() tests =====

    public function testResetAllDeletesSettings(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // First save some settings
        Settings::save('set-texts-per-page', '99');
        Settings::save('set-terms-per-page', '88');

        // Verify they were saved
        $this->assertEquals('99', Settings::get('set-texts-per-page'));

        // Reset all
        $message = $this->service->resetAll();

        $this->assertEquals('All Settings reset to default values', $message);

        // After reset, getWithDefault should return the default value
        $value = Settings::getWithDefault('set-texts-per-page');
        $this->assertEquals('10', $value, 'Should return default after reset');
    }

    public function testResetAllReturnsSuccessMessage(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $message = $this->service->resetAll();
        $this->assertEquals('All Settings reset to default values', $message);
    }

    // ===== Integration tests =====

    public function testSaveAndGetAllRoundTrip(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $_REQUEST['set-texts-per-page'] = '15';
        $_REQUEST['set-terms-per-page'] = '25';
        $_REQUEST['set-tooltip-mode'] = '1';

        $this->service->saveAll();

        $allSettings = $this->service->getAll();

        $this->assertEquals('15', $allSettings['set-texts-per-page']);
        $this->assertEquals('25', $allSettings['set-terms-per-page']);
        $this->assertEquals('1', $allSettings['set-tooltip-mode']);
    }

    public function testResetThenGetAllReturnsDefaults(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Save custom values
        $_REQUEST['set-texts-per-page'] = '99';
        $this->service->saveAll();

        // Reset
        $this->service->resetAll();

        // Get all should return defaults
        $allSettings = $this->service->getAll();
        $this->assertEquals('10', $allSettings['set-texts-per-page']);
    }
}
