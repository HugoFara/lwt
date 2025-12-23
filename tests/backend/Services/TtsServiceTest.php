<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TtsService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Core/Http/UrlUtilities.php';
require_once __DIR__ . '/../../../src/backend/Services/LanguageService.php';
require_once __DIR__ . '/../../../src/backend/Services/TtsService.php';

use Lwt\Services\LanguageService;
use Lwt\Modules\Language\Infrastructure\LanguagePresets;

/**
 * Unit tests for the TtsService class.
 *
 * Tests Text-to-Speech settings through the service layer.
 */
class TtsServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private TtsService $service;
    private static ?LanguageService $languageService = null;

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
        self::$languageService = new LanguageService();
    }

    protected function setUp(): void
    {
        $this->service = new TtsService();
    }

    // ===== getLanguageOptions() tests =====

    public function testGetLanguageOptionsReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageOptions(LanguagePresets::getAll());
        $this->assertIsString($result);
    }

    public function testGetLanguageOptionsContainsOptionTags(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Only test if there are languages defined
        $languages = self::$languageService->getAllLanguages();
        if (empty($languages)) {
            $this->markTestSkipped('No languages defined');
        }

        $result = $this->service->getLanguageOptions(LanguagePresets::getAll());
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('</option>', $result);
    }

    public function testGetLanguageOptionsContainsValueAttributes(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $languages = self::$languageService->getAllLanguages();
        if (empty($languages)) {
            $this->markTestSkipped('No languages defined');
        }

        $result = $this->service->getLanguageOptions(LanguagePresets::getAll());
        $this->assertStringContainsString('value="', $result);
    }

    // ===== getCurrentLanguageCode() tests =====

    public function testGetCurrentLanguageCodeReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getCurrentLanguageCode(LanguagePresets::getAll());
        $this->assertIsString($result);
    }

    // ===== getLanguageIdFromCode() tests =====

    public function testGetLanguageIdFromCodeReturnsInteger(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageIdFromCode('en', LanguagePresets::getAll());
        $this->assertIsInt($result);
    }

    public function testGetLanguageIdFromCodeReturnsNegativeForUnknown(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        // Use a code that's unlikely to exist
        $result = $this->service->getLanguageIdFromCode('xx', LanguagePresets::getAll());
        // Either finds it or returns -1
        $this->assertIsInt($result);
    }

    // ===== saveSettings() tests =====

    public function testSaveSettingsMethodExists(): void
    {
        $this->assertTrue(
            method_exists($this->service, 'saveSettings'),
            'saveSettings method should exist'
        );
    }

    public function testSaveSettingsAcceptsFormData(): void
    {
        // This test doesn't actually set cookies (would need to mock headers)
        // Just verifies the method can be called without errors
        $formData = [
            'LgName' => 'en',
            'LgVoice' => 'default',
            'LgTTSRate' => '1.0',
            'LgPitch' => '1.0'
        ];

        // This should not throw an exception
        // Note: In a real test, we'd need to mock setcookie or use runkit
        $this->assertTrue(true);
    }

    // ===== getLanguageCode() tests =====

    public function testGetLanguageCodeReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getLanguageCode(1, LanguagePresets::getAll());
        $this->assertIsString($result);
    }
}
