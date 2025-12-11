<?php declare(strict_types=1);
namespace Lwt\Tests\Services;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Services\StatisticsService;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Services/StatisticsService.php';

/**
 * Unit tests for the StatisticsService class.
 *
 * Tests learning statistics retrieval through the service layer.
 */
class StatisticsServiceTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private StatisticsService $service;

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
        $this->service = new StatisticsService();
    }

    // ===== getIntensityStatistics() tests =====

    public function testGetIntensityStatisticsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getIntensityStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('languages', $result);
        $this->assertArrayHasKey('totals', $result);
    }

    public function testGetIntensityStatisticsTotalsHaveExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getIntensityStatistics();
        $totals = $result['totals'];

        $expectedKeys = ['s1', 's2', 's3', 's4', 's5', 's98', 's99', 's14', 's15', 's599', 'all'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $totals, "Missing total key: $key");
        }
    }

    public function testGetIntensityStatisticsTotalsAreIntegers(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getIntensityStatistics();
        $totals = $result['totals'];

        foreach ($totals as $key => $value) {
            $this->assertIsInt($value, "Total '$key' should be an integer");
        }
    }

    public function testGetIntensityStatisticsLanguagesHaveExpectedStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getIntensityStatistics();

        // If there are languages, check their structure
        if (!empty($result['languages'])) {
            $firstLang = $result['languages'][0];
            $this->assertArrayHasKey('id', $firstLang);
            $this->assertArrayHasKey('name', $firstLang);
            $this->assertArrayHasKey('s1', $firstLang);
            $this->assertArrayHasKey('s5', $firstLang);
            $this->assertArrayHasKey('all', $firstLang);
        }

        // Languages should be an array even if empty
        $this->assertIsArray($result['languages']);
    }

    public function testGetIntensityStatisticsTotalsAreNonNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getIntensityStatistics();

        foreach ($result['totals'] as $key => $value) {
            $this->assertGreaterThanOrEqual(0, $value, "Total '$key' should be non-negative");
        }
    }

    // ===== getFrequencyStatistics() tests =====

    public function testGetFrequencyStatisticsReturnsArray(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getFrequencyStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('languages', $result);
        $this->assertArrayHasKey('totals', $result);
    }

    public function testGetFrequencyStatisticsTotalsHaveExpectedKeys(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getFrequencyStatistics();
        $totals = $result['totals'];

        $expectedKeys = [
            'ct', 'at', 'kt',   // Today
            'cy', 'ay', 'ky',   // Yesterday
            'cw', 'aw', 'kw',   // Week
            'cm', 'am', 'km',   // Month
            'ca', 'aa', 'ka',   // Year
            'call', 'aall', 'kall'  // All time
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $totals, "Missing frequency total key: $key");
        }
    }

    public function testGetFrequencyStatisticsTotalsAreIntegers(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getFrequencyStatistics();
        $totals = $result['totals'];

        foreach ($totals as $key => $value) {
            $this->assertIsInt($value, "Frequency total '$key' should be an integer");
        }
    }

    public function testGetFrequencyStatisticsLanguagesHaveExpectedStructure(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getFrequencyStatistics();

        // If there are languages, check their structure
        if (!empty($result['languages'])) {
            $firstLang = $result['languages'][0];
            $this->assertArrayHasKey('id', $firstLang);
            $this->assertArrayHasKey('name', $firstLang);
            $this->assertArrayHasKey('ct', $firstLang);  // Created today
            $this->assertArrayHasKey('at', $firstLang);  // Active today
            $this->assertArrayHasKey('kt', $firstLang);  // Known today
        }

        $this->assertIsArray($result['languages']);
    }

    public function testGetFrequencyStatisticsTotalsAreNonNegative(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->service->getFrequencyStatistics();

        foreach ($result['totals'] as $key => $value) {
            $this->assertGreaterThanOrEqual(0, $value, "Frequency total '$key' should be non-negative");
        }
    }

    // ===== Consistency tests =====

    public function testIntensityAndFrequencyHaveSameLanguageCount(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $intensity = $this->service->getIntensityStatistics();
        $frequency = $this->service->getFrequencyStatistics();

        $this->assertCount(
            count($intensity['languages']),
            $frequency['languages'],
            'Intensity and frequency should have the same number of languages'
        );
    }
}
