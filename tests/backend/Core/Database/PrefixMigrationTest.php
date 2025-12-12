<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Database;

use PHPUnit\Framework\TestCase;

// Manually require the class since it uses manual loading, not PSR-4
require_once __DIR__ . '/../../../../src/backend/Core/Database/PrefixMigration.php';

use Lwt\Database\PrefixMigration;

/**
 * Tests for PrefixMigration class.
 *
 * @coversDefaultClass \Lwt\Database\PrefixMigration
 */
class PrefixMigrationTest extends TestCase
{
    /**
     * Test that the class can be instantiated.
     *
     * @covers ::__construct
     */
    public function testCanInstantiate(): void
    {
        $migration = new PrefixMigration();
        $this->assertInstanceOf(PrefixMigration::class, $migration);
    }

    /**
     * Test that verbose mode can be enabled.
     *
     * @covers ::__construct
     */
    public function testVerboseModeCanBeEnabled(): void
    {
        $migration = new PrefixMigration(true);
        $this->assertInstanceOf(PrefixMigration::class, $migration);
    }

    /**
     * Test that log starts empty.
     *
     * @covers ::getLog
     */
    public function testLogStartsEmpty(): void
    {
        $migration = new PrefixMigration();
        $this->assertEquals([], $migration->getLog());
    }

    /**
     * Test that USER_TABLES constant is properly defined.
     *
     * Uses reflection to check the constant contains expected tables.
     */
    public function testUserTablesConstantContainsExpectedTables(): void
    {
        $reflection = new \ReflectionClass(PrefixMigration::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('USER_TABLES', $constants);

        $userTables = $constants['USER_TABLES'];

        // Check all expected tables are present
        $expectedTables = [
            'languages' => 'LgUsID',
            'texts' => 'TxUsID',
            'archivedtexts' => 'AtUsID',
            'words' => 'WoUsID',
            'tags' => 'TgUsID',
            'tags2' => 'T2UsID',
            'newsfeeds' => 'NfUsID',
            'settings' => 'StUsID',
        ];

        foreach ($expectedTables as $table => $column) {
            $this->assertArrayHasKey($table, $userTables);
            $this->assertEquals($column, $userTables[$table]);
        }
    }

    /**
     * Test that DEPENDENT_TABLES constant is properly defined.
     *
     * Uses reflection to check the constant contains expected tables.
     */
    public function testDependentTablesConstantContainsExpectedTables(): void
    {
        $reflection = new \ReflectionClass(PrefixMigration::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('DEPENDENT_TABLES', $constants);

        $dependentTables = $constants['DEPENDENT_TABLES'];

        // Check key dependent tables are present
        $expectedTables = ['sentences', 'textitems2', 'wordtags', 'texttags', 'archtexttags', 'feedlinks'];

        foreach ($expectedTables as $table) {
            $this->assertArrayHasKey($table, $dependentTables);
        }
    }

    /**
     * Test that sentences table references texts and languages.
     */
    public function testSentencesForeignKeysAreCorrect(): void
    {
        $reflection = new \ReflectionClass(PrefixMigration::class);
        $constants = $reflection->getConstants();

        $dependentTables = $constants['DEPENDENT_TABLES'];
        $sentencesFks = $dependentTables['sentences'];

        $this->assertArrayHasKey('SeTxID', $sentencesFks);
        $this->assertEquals('texts', $sentencesFks['SeTxID']);

        $this->assertArrayHasKey('SeLgID', $sentencesFks);
        $this->assertEquals('languages', $sentencesFks['SeLgID']);
    }
}
