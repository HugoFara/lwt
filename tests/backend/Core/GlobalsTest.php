<?php declare(strict_types=1);
namespace Lwt\Tests\Core;

require_once __DIR__ . '/../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Core/Exception/AuthException.php';

use Lwt\Core\Exception\AuthException;
use Lwt\Core\Globals;
use Lwt\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Globals class.
 *
 * Tests global state management for database connection, table prefix,
 * and various application-wide settings.
 */
class GlobalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset Globals state before each test
        Globals::reset();
    }

    protected function tearDown(): void
    {
        // Reset Globals state after each test
        Globals::reset();
        
        parent::tearDown();
    }

    // ===== initialize() tests =====

    public function testInitialize(): void
    {
        Globals::initialize();

        // After initialization, display settings should be false
        $this->assertFalse(Globals::isErrorDisplayEnabled());
    }

    public function testInitializeOnlyOnce(): void
    {
        Globals::initialize();
        Globals::setErrorDisplay(true);

        // Second initialize should not reset values
        Globals::initialize();

        $this->assertTrue(Globals::isErrorDisplayEnabled());
    }

    // ===== dbConnection tests =====

    public function testSetAndGetDbConnection(): void
    {
        $mockConnection = $this->createMock(\mysqli::class);
        
        Globals::setDbConnection($mockConnection);
        
        $this->assertSame($mockConnection, Globals::getDbConnection());
    }

    public function testGetDbConnectionReturnsNullInitially(): void
    {
        $this->assertNull(Globals::getDbConnection());
    }

    // ===== databaseName tests =====

    public function testSetAndGetDatabaseName(): void
    {
        Globals::setDatabaseName('test_database');
        
        $this->assertEquals('test_database', Globals::getDatabaseName());
    }

    public function testDatabaseNameDefaultsToEmpty(): void
    {
        $this->assertEquals('', Globals::getDatabaseName());
    }

    // ===== errorDisplay tests =====

    public function testSetErrorDisplayOn(): void
    {
        Globals::setErrorDisplay(true);

        $this->assertTrue(Globals::isErrorDisplayEnabled());
    }

    public function testSetErrorDisplayOff(): void
    {
        Globals::setErrorDisplay(true);
        Globals::setErrorDisplay(false);

        $this->assertFalse(Globals::isErrorDisplayEnabled());
    }

    public function testIsErrorDisplayEnabledReturnsBool(): void
    {
        Globals::setErrorDisplay(false);
        $this->assertIsBool(Globals::isErrorDisplayEnabled());

        Globals::setErrorDisplay(true);
        $this->assertIsBool(Globals::isErrorDisplayEnabled());
    }

    // ===== table() tests =====

    public function testTableReturnsTableName(): void
    {
        $this->assertEquals('words', Globals::table('words'));
    }

    // ===== query() tests =====

    public function testQueryReturnsQueryBuilder(): void
    {
        $qb = Globals::query('words');
        
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testQueryUsesCorrectTableName(): void
    {
        $qb = Globals::query('words');
        $sql = $qb->toSql();

        $this->assertStringContainsString('words', $sql);
    }

    // ===== reset() tests =====

    public function testResetClearsAllValues(): void
    {
        // Set various values
        $mockConnection = $this->createMock(\mysqli::class);
        Globals::setDbConnection($mockConnection);
        Globals::setDatabaseName('testdb');
        Globals::setErrorDisplay(true);
        Globals::setCurrentUserId(42);
        Globals::setMultiUserEnabled(true);
        Globals::initialize();

        // Reset
        Globals::reset();

        // Verify all values are cleared
        $this->assertNull(Globals::getDbConnection());
        $this->assertEquals('', Globals::getDatabaseName());
        $this->assertFalse(Globals::isErrorDisplayEnabled());
        $this->assertNull(Globals::getCurrentUserId());
        $this->assertFalse(Globals::isMultiUserEnabled());
    }

    // ===== User context tests =====

    public function testSetAndGetCurrentUserId(): void
    {
        Globals::setCurrentUserId(42);

        $this->assertEquals(42, Globals::getCurrentUserId());
    }

    public function testCurrentUserIdDefaultsToNull(): void
    {
        $this->assertNull(Globals::getCurrentUserId());
    }

    public function testSetCurrentUserIdToNull(): void
    {
        Globals::setCurrentUserId(42);
        Globals::setCurrentUserId(null);

        $this->assertNull(Globals::getCurrentUserId());
    }

    public function testRequireUserIdReturnsIdWhenSet(): void
    {
        Globals::setCurrentUserId(42);

        $this->assertEquals(42, Globals::requireUserId());
    }

    public function testRequireUserIdThrowsWhenNotSet(): void
    {
        $this->expectException(AuthException::class);

        Globals::requireUserId();
    }

    public function testIsAuthenticatedReturnsTrueWhenUserIdSet(): void
    {
        Globals::setCurrentUserId(42);

        $this->assertTrue(Globals::isAuthenticated());
    }

    public function testIsAuthenticatedReturnsFalseWhenUserIdNull(): void
    {
        $this->assertFalse(Globals::isAuthenticated());
    }

    public function testSetAndGetMultiUserEnabled(): void
    {
        Globals::setMultiUserEnabled(true);

        $this->assertTrue(Globals::isMultiUserEnabled());
    }

    public function testMultiUserEnabledDefaultsToFalse(): void
    {
        $this->assertFalse(Globals::isMultiUserEnabled());
    }

    public function testSetMultiUserEnabledToFalse(): void
    {
        Globals::setMultiUserEnabled(true);
        Globals::setMultiUserEnabled(false);

        $this->assertFalse(Globals::isMultiUserEnabled());
    }
}
