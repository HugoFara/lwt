<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Modules\Admin\Application\AdminFacade;
use Lwt\Modules\Admin\Application\DTO\DatabaseConnectionDTO;
use Lwt\Modules\Admin\Domain\BackupRepositoryInterface;
use Lwt\Modules\Admin\Domain\SettingsRepositoryInterface;
use Lwt\Modules\Admin\Infrastructure\MySqlBackupRepository;
use Lwt\Modules\Admin\Infrastructure\MySqlSettingsRepository;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';

/**
 * Unit tests for the AdminFacade class.
 *
 * Tests admin operations including settings, statistics, themes, and wizard.
 */
class AdminFacadeTest extends TestCase
{
    private static bool $dbConnected = false;
    private AdminFacade $facade;

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
        if (self::$dbConnected) {
            $settingsRepo = new MySqlSettingsRepository();
            $backupRepo = new MySqlBackupRepository();
            $this->facade = new AdminFacade($settingsRepo, $backupRepo);
        }
    }

    // ===== Constructor tests =====

    public function testConstructorCreatesValidFacade(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);

        $facade = new AdminFacade($settingsRepo, $backupRepo);
        $this->assertInstanceOf(AdminFacade::class, $facade);
    }

    public function testConstructorAcceptsMockRepositories(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);

        $facade = new AdminFacade($settingsRepo, $backupRepo);
        $this->assertInstanceOf(AdminFacade::class, $facade);
    }

    // ===== DatabaseConnectionDTO tests =====

    public function testCreateEmptyConnectionReturnsDTO(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $connection = $facade->createEmptyConnection();
        $this->assertInstanceOf(DatabaseConnectionDTO::class, $connection);
        $this->assertTrue($connection->isEmpty());
    }

    public function testCreateConnectionFromFormReturnsDTO(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $formData = [
            'server' => 'localhost',
            'userid' => 'testuser',
            'passwd' => 'testpass',
            'dbname' => 'testdb',
            'socket' => '',
        ];

        $connection = $facade->createConnectionFromForm($formData);
        $this->assertInstanceOf(DatabaseConnectionDTO::class, $connection);
        $this->assertEquals('localhost', $connection->server);
        $this->assertEquals('testuser', $connection->userid);
        $this->assertEquals('testpass', $connection->passwd);
        $this->assertEquals('testdb', $connection->dbname);
        $this->assertFalse($connection->isEmpty());
    }

    public function testCreateConnectionFromFormHandlesEmptyData(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $connection = $facade->createConnectionFromForm([]);
        $this->assertInstanceOf(DatabaseConnectionDTO::class, $connection);
        $this->assertTrue($connection->isEmpty());
    }

    public function testDatabaseConnectionDTOToArray(): void
    {
        $dto = new DatabaseConnectionDTO('host', 'user', 'pass', 'db', '/sock');
        $array = $dto->toArray();

        $this->assertEquals('host', $array['server']);
        $this->assertEquals('user', $array['userid']);
        $this->assertEquals('pass', $array['passwd']);
        $this->assertEquals('db', $array['dbname']);
        $this->assertEquals('/sock', $array['socket']);
    }

    public function testDatabaseConnectionDTOFromFormData(): void
    {
        $dto = DatabaseConnectionDTO::fromFormData([
            'server' => 'myhost',
            'userid' => 'myuser',
            'passwd' => 'mypass',
            'dbname' => 'mydb',
        ]);

        $this->assertEquals('myhost', $dto->server);
        $this->assertEquals('myuser', $dto->userid);
        $this->assertEquals('mypass', $dto->passwd);
        $this->assertEquals('mydb', $dto->dbname);
        $this->assertEquals('', $dto->socket);
    }

    public function testDatabaseConnectionDTOIsEmpty(): void
    {
        $emptyDto = new DatabaseConnectionDTO();
        $this->assertTrue($emptyDto->isEmpty());

        $partialDto = new DatabaseConnectionDTO('localhost');
        $this->assertFalse($partialDto->isEmpty());
    }

    // ===== Settings tests =====

    public function testGetSettingReturnsEmptyForUnknownKey(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Note: Current implementation ignores the default parameter
        $result = $this->facade->getSetting('nonexistent_setting_xyz', 'default_value');
        $this->assertEquals('', $result);
    }

    public function testGetSettingReturnsEmptyStringAsDefault(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getSetting('nonexistent_setting_xyz');
        $this->assertEquals('', $result);
    }

    public function testGetAllSettingsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getAllSettings();
        $this->assertIsArray($result);
    }

    public function testGetAllSettingsReturnsStringValues(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getAllSettings();
        foreach ($result as $key => $value) {
            $this->assertIsString($key);
            $this->assertIsString($value);
        }
    }

    // ===== Statistics tests =====

    public function testGetIntensityStatisticsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getIntensityStatistics();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('languages', $result);
        $this->assertArrayHasKey('totals', $result);
    }

    public function testGetIntensityStatisticsLanguagesIsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getIntensityStatistics();
        $this->assertIsArray($result['languages']);
    }

    public function testGetIntensityStatisticsTotalsIsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getIntensityStatistics();
        $this->assertIsArray($result['totals']);
    }

    public function testGetFrequencyStatisticsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getFrequencyStatistics();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('languages', $result);
        $this->assertArrayHasKey('totals', $result);
    }

    public function testGetFrequencyStatisticsLanguagesIsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getFrequencyStatistics();
        $this->assertIsArray($result['languages']);
    }

    // ===== Demo tests =====

    public function testGetLanguageCountReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getLanguageCount();
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    // ===== Server Data tests =====

    public function testGetServerDataReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getServerData();
        $this->assertIsArray($result);
    }

    public function testGetServerDataContainsPhpVersion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getServerData();
        $this->assertArrayHasKey('php', $result);
        $this->assertNotEmpty($result['php']);
    }

    public function testGetServerDataContainsMysqlVersion(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getServerData();
        $this->assertArrayHasKey('mysql', $result);
    }

    // ===== Theme tests =====

    public function testGetAvailableThemesReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getAvailableThemes();
        $this->assertIsArray($result);
    }

    public function testGetAvailableThemesContainsDefaultTheme(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getAvailableThemes();
        // Default theme should always exist
        $this->assertNotEmpty($result);
    }

    // ===== Database Info tests =====

    public function testGetDatabaseNameReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->facade->getDatabaseName();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetPrefixInfoReturnsEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Prefix has been removed from LWT
        $result = $this->facade->getPrefixInfo();
        $this->assertEquals('', $result);
    }

    // ===== Wizard tests =====

    public function testEnvFileExistsReturnsBoolean(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $result = $facade->envFileExists();
        $this->assertIsBool($result);
    }

    public function testGetEnvPathReturnsString(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $result = $facade->getEnvPath();
        $this->assertIsString($result);
        $this->assertStringContainsString('.env', $result);
    }

    public function testLoadConnectionReturnsDTO(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $result = $facade->loadConnection();
        $this->assertInstanceOf(DatabaseConnectionDTO::class, $result);
    }

    public function testAutocompleteConnectionReturnsDTO(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $result = $facade->autocompleteConnection();
        $this->assertInstanceOf(DatabaseConnectionDTO::class, $result);
    }

    public function testTestConnectionReturnsArray(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        // Test with invalid connection
        $connection = new DatabaseConnectionDTO(
            'invalid_host_xyz',
            'invalid_user',
            'invalid_pass',
            'invalid_db'
        );

        $result = $facade->testConnection($connection);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        // Should return failed connection result
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    // ===== Method existence tests =====

    public function testSaveAllSettingsMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'saveAllSettings'),
            'saveAllSettings method should exist'
        );
    }

    public function testResetAllSettingsMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'resetAllSettings'),
            'resetAllSettings method should exist'
        );
    }

    public function testSaveAndClearSessionMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'saveAndClearSession'),
            'saveAndClearSession method should exist'
        );
    }

    public function testRestoreFromUploadMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'restoreFromUpload'),
            'restoreFromUpload method should exist'
        );
    }

    public function testDownloadBackupMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'downloadBackup'),
            'downloadBackup method should exist'
        );
    }

    public function testDownloadOfficialBackupMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'downloadOfficialBackup'),
            'downloadOfficialBackup method should exist'
        );
    }

    public function testEmptyDatabaseMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'emptyDatabase'),
            'emptyDatabase method should exist'
        );
    }

    public function testInstallDemoMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'installDemo'),
            'installDemo method should exist'
        );
    }

    public function testSaveConnectionToEnvMethodExists(): void
    {
        $settingsRepo = $this->createMock(SettingsRepositoryInterface::class);
        $backupRepo = $this->createMock(BackupRepositoryInterface::class);
        $facade = new AdminFacade($settingsRepo, $backupRepo);

        $this->assertTrue(
            method_exists($facade, 'saveConnectionToEnv'),
            'saveConnectionToEnv method should exist'
        );
    }

    // ===== Integration: Settings round-trip =====

    public function testSettingsRoundTrip(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $testKey = 'test_setting_' . uniqid();
        $testValue = 'test_value_' . uniqid();

        // Save a setting directly via repository to verify retrieval
        $settingsRepo = new MySqlSettingsRepository();
        $settingsRepo->save($testKey, $testValue);

        // Verify we can get it back - need fresh repository read
        $result = $settingsRepo->get($testKey, '');
        $this->assertEquals($testValue, $result);

        // Verify it's in all settings from repository
        $allSettings = $settingsRepo->getAll();
        $this->assertArrayHasKey($testKey, $allSettings);
        $this->assertEquals($testValue, $allSettings[$testKey]);

        // Clean up - use exact match pattern
        $settingsRepo->deleteByPattern($testKey);
    }

    // ===== Theme validation =====

    public function testGetAvailableThemesReturnsValidStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $themes = $this->facade->getAvailableThemes();

        // Each theme should be an array with expected keys
        foreach ($themes as $theme) {
            $this->assertIsArray($theme);
            $this->assertArrayHasKey('name', $theme);
            $this->assertArrayHasKey('path', $theme);
        }
    }

    // ===== Statistics structure validation =====

    public function testIntensityStatisticsHasValidTotalsStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $stats = $this->facade->getIntensityStatistics();

        // Totals should contain expected keys
        if (!empty($stats['totals'])) {
            $totals = $stats['totals'];
            $this->assertIsArray($totals);
        }
    }

    public function testFrequencyStatisticsHasValidTotalsStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $stats = $this->facade->getFrequencyStatistics();

        // Totals should contain expected keys
        if (!empty($stats['totals'])) {
            $totals = $stats['totals'];
            $this->assertIsArray($totals);
        }
    }

    // ===== Server data structure validation =====

    public function testServerDataHasExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = $this->facade->getServerData();

        // Check for expected common keys (actual key names from GetServerData)
        $this->assertArrayHasKey('php', $data);
        $this->assertArrayHasKey('mysql', $data);
        $this->assertArrayHasKey('db_name', $data);
        $this->assertArrayHasKey('db_size', $data);
        $this->assertArrayHasKey('lwt_version', $data);
    }

    public function testServerDataPhpVersionIsValid(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $data = $this->facade->getServerData();

        // PHP version should match current PHP version
        $this->assertEquals(PHP_VERSION, $data['php']);
    }
}
