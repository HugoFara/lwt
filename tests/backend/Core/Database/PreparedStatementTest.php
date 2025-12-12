<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';

use Lwt\Database\Connection;
use Lwt\Database\PreparedStatement;
use Lwt\Database\QueryBuilder;

/**
 * Tests for PreparedStatement class and prepared statement functionality.
 */
class PreparedStatementTest extends TestCase
{
    private static bool $dbConnected = false;

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
        parent::setUp();

        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    public function testPrepareCreatesStatement(): void
    {
        $table = Globals::table('languages');
        $stmt = Connection::prepare(
            "SELECT LgID FROM {$table} WHERE LgID = ?"
        );

        $this->assertInstanceOf(PreparedStatement::class, $stmt);
    }

    public function testBindAndFetchOne(): void
    {
        $table = Globals::table('languages');

        // First, get an existing language ID
        $row = Connection::fetchOne(
            "SELECT LgID FROM {$table} LIMIT 1"
        );

        if ($row === null) {
            $this->markTestSkipped('No languages in database');
        }

        $langId = (int) $row['LgID'];

        // Test prepared statement fetch
        $result = Connection::preparedFetchOne(
            "SELECT LgID, LgName FROM {$table} WHERE LgID = ?",
            [$langId]
        );

        $this->assertNotNull($result);
        $this->assertEquals($langId, (int) $result['LgID']);
    }

    public function testPreparedFetchAll(): void
    {
        $table = Globals::table('languages');
        $results = Connection::preparedFetchAll(
            "SELECT LgID FROM {$table} WHERE LgID > ?",
            [0]
        );

        $this->assertIsArray($results);
    }

    public function testPreparedFetchValue(): void
    {
        $table = Globals::table('languages');
        $count = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS value FROM {$table} WHERE LgID > ?",
            [0]
        );

        $this->assertIsNumeric($count);
    }

    public function testBindWithMultipleTypes(): void
    {
        $table = Globals::table('languages');
        // Test binding string and int types
        $stmt = Connection::prepare(
            "SELECT LgID FROM {$table} WHERE LgID = ? AND LgName != ?"
        );

        $stmt->bind('is', 1, 'nonexistent');
        $result = $stmt->fetchAll();

        $this->assertIsArray($result);
    }

    public function testBindValues(): void
    {
        $table = Globals::table('languages');
        $stmt = Connection::prepare(
            "SELECT LgID FROM {$table} WHERE LgID = ? AND LgName != ?"
        );

        // bindValues auto-detects types
        $stmt->bindValues([1, 'nonexistent']);
        $result = $stmt->fetchAll();

        $this->assertIsArray($result);
    }

    public function testQueryBuilderPreparedSelect(): void
    {
        $results = QueryBuilder::table('languages')
            ->select(['LgID', 'LgName'])
            ->where('LgID', '>', 0)
            ->limit(5)
            ->getPrepared();

        $this->assertIsArray($results);
    }

    public function testQueryBuilderPreparedFirst(): void
    {
        $result = QueryBuilder::table('languages')
            ->select(['LgID', 'LgName'])
            ->where('LgID', '>', 0)
            ->firstPrepared();

        // Could be null if no languages exist, or an array with expected keys
        if ($result !== null) {
            $this->assertArrayHasKey('LgID', $result);
        } else {
            $this->assertNull($result);
        }
    }

    public function testQueryBuilderPreparedCount(): void
    {
        $count = QueryBuilder::table('languages')
            ->where('LgID', '>', 0)
            ->countPrepared();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testQueryBuilderPreparedExists(): void
    {
        $exists = QueryBuilder::table('languages')
            ->where('LgID', '>', 0)
            ->existsPrepared();

        $this->assertIsBool($exists);
    }

    public function testQueryBuilderWhereInPrepared(): void
    {
        $results = QueryBuilder::table('languages')
            ->select(['LgID'])
            ->whereIn('LgID', [1, 2, 3])
            ->getPrepared();

        $this->assertIsArray($results);
    }

    public function testPreparedStatementWithNullValue(): void
    {
        $table = Globals::table('languages');
        // Test that null values are handled correctly
        $stmt = Connection::prepare(
            "SELECT LgID FROM {$table} WHERE LgID = ? OR ? IS NULL"
        );

        $stmt->bindValues([1, null]);
        $result = $stmt->fetchAll();

        $this->assertIsArray($result);
    }

    public function testPreparedStatementTypeInference(): void
    {
        $table = Globals::table('languages');
        $stmt = Connection::prepare(
            "SELECT LgID FROM {$table} WHERE LgID > ?"
        );

        // Test that float is properly handled
        $stmt->bindValues([0.5]);
        $result = $stmt->fetchAll();

        $this->assertIsArray($result);
    }
}
