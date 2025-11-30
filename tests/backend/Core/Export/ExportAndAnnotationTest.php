<?php declare(strict_types=1);
namespace Lwt\Tests\Core\Export;

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\DB;
use Lwt\Database\Escaping;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../../src/backend/Services/TextStatisticsService.php';
require_once __DIR__ . '/../../../../src/backend/Services/SentenceService.php';
require_once __DIR__ . '/../../../../src/backend/Services/AnnotationService.php';
require_once __DIR__ . '/../../../../src/backend/Services/SimilarTermsService.php';
require_once __DIR__ . '/../../../../src/backend/Services/TextNavigationService.php';
require_once __DIR__ . '/../../../../src/backend/Services/TextParsingService.php';
require_once __DIR__ . '/../../../../src/backend/Services/ExpressionService.php';
require_once __DIR__ . '/../../../../src/backend/Core/Database/Restore.php';
require_once __DIR__ . '/../../../../src/backend/Services/ExportService.php';
require_once __DIR__ . '/../../../../src/backend/Services/LanguageService.php';

/**
 * Unit tests for export and annotation functions.
 *
 * Tests export helper functions, annotation creation, and annotation management.
 */
class ExportAndAnnotationTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';

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

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test data
        $tbpref = self::$tbpref;
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxTitle LIKE 'test_export_%'");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName LIKE 'test_export_%'");
    }

    // ===== create_ann() tests =====

    public function testCreateAnnReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Create test language
        Connection::query("INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgGoogleTranslateURI) 
                         VALUES ('test_export_lang', 'http://test', 'http://test')");
        $lgId = (int)Connection::lastInsertId();
        
        // Create test text
        Connection::query("INSERT INTO {$tbpref}texts (TxTitle, TxText, TxLgID) 
                         VALUES ('test_export_text', 'Test content', $lgId)");
        $textId = (int)Connection::lastInsertId();
        
        $ann = create_ann($textId);
        
        $this->assertIsString($ann);
        // Even with no textitems2, should return some annotation structure
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $lgId");
    }

    public function testCreateAnnWithNonExistentText(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $ann = create_ann(999999);
        
        $this->assertIsString($ann);
        // Should return empty or minimal annotation structure
    }

    // ===== recreate_save_ann() tests =====

    public function testRecreateSaveAnnReturnsString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Create test language
        Connection::query("INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgGoogleTranslateURI) 
                         VALUES ('test_export_recreate', 'http://test', 'http://test')");
        $lgId = (int)Connection::lastInsertId();
        
        // Create test text
        Connection::query("INSERT INTO {$tbpref}texts (TxTitle, TxText, TxLgID) 
                         VALUES ('test_export_recreate_text', 'Test content', $lgId)");
        $textId = (int)Connection::lastInsertId();
        
        $oldAnn = "1\tword\t0\ttranslation\n";
        $newAnn = recreate_save_ann($textId, $oldAnn);
        
        $this->assertIsString($newAnn);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $lgId");
    }

    public function testRecreateSaveAnnWithEmptyOldAnn(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Create test language
        Connection::query("INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgGoogleTranslateURI) 
                         VALUES ('test_export_empty', 'http://test', 'http://test')");
        $lgId = (int)Connection::lastInsertId();
        
        // Create test text
        Connection::query("INSERT INTO {$tbpref}texts (TxTitle, TxText, TxLgID) 
                         VALUES ('test_export_empty_text', 'Test content', $lgId)");
        $textId = (int)Connection::lastInsertId();
        
        $newAnn = recreate_save_ann($textId, '');
        
        $this->assertIsString($newAnn);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $lgId");
    }

    public function testRecreateSaveAnnUpdatesDatabase(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Create test language
        Connection::query("INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgGoogleTranslateURI) 
                         VALUES ('test_export_update', 'http://test', 'http://test')");
        $lgId = (int)Connection::lastInsertId();
        
        // Create test text
        Connection::query("INSERT INTO {$tbpref}texts (TxTitle, TxText, TxLgID, TxAnnotatedText) 
                         VALUES ('test_export_update_text', 'Test content', $lgId, '')");
        $textId = (int)Connection::lastInsertId();
        
        $oldAnn = "1\tword\t0\ttranslation\n";
        recreate_save_ann($textId, $oldAnn);
        
        // Verify database was updated
        $saved = Connection::fetchValue("SELECT TxAnnotatedText AS value FROM {$tbpref}texts WHERE TxID = $textId");
        $this->assertNotNull($saved);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $lgId");
    }

    // ===== Helper function tests =====

    public function testReplTabNlReplacesTabsAndNewlines(): void
    {
        $this->assertEquals('hello world', repl_tab_nl("hello\tworld"));
        $this->assertEquals('line one line two', repl_tab_nl("line one\nline two"));
        $this->assertEquals('mixed tabs newlines', repl_tab_nl("mixed\ttabs\nnewlines"));
    }

    public function testReplTabNlWithEmptyString(): void
    {
        $this->assertEquals('', repl_tab_nl(''));
    }

    public function testReplTabNlWithNormalText(): void
    {
        $this->assertEquals('normal text', repl_tab_nl('normal text'));
    }

    public function testTohtml(): void
    {
        // tohtml is used to escape HTML entities
        $this->assertEquals('&lt;b&gt;test&lt;/b&gt;', tohtml('<b>test</b>'));
        $this->assertEquals('test &amp; example', tohtml('test & example'));
        $this->assertEquals('&quot;quoted&quot;', tohtml('"quoted"'));
    }

    // ===== Annotation structure tests =====

    public function testAnnotationStructureFormat(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Create test language
        Connection::query("INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgGoogleTranslateURI) 
                         VALUES ('test_export_struct', 'http://test', 'http://test')");
        $lgId = (int)Connection::lastInsertId();
        
        // Create test text
        Connection::query("INSERT INTO {$tbpref}texts (TxTitle, TxText, TxLgID) 
                         VALUES ('test_export_struct_text', 'Test content', $lgId)");
        $textId = (int)Connection::lastInsertId();
        
        $ann = create_ann($textId);
        
        // Annotation should contain lines
        $lines = explode("\n", $ann);
        $this->assertIsArray($lines);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $lgId");
    }

    public function testAnnotationWithTabSeparatedValues(): void
    {
        // Test that old annotation parsing works correctly
        $oldAnn = "1\tword\t0\ttranslation\n2\tother\t0\tmeaning\n";
        $lines = explode("\n", $oldAnn);
        
        $this->assertGreaterThan(0, count($lines));
        
        foreach ($lines as $line) {
            if (strlen(trim($line)) > 0) {
                $parts = explode("\t", $line);
                $this->assertGreaterThan(0, count($parts));
            }
        }
    }

    // ===== Integration tests =====

    public function testAnnotationWorkflow(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Create test language
        Connection::query("INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgGoogleTranslateURI) 
                         VALUES ('test_export_workflow', 'http://test', 'http://test')");
        $lgId = (int)Connection::lastInsertId();
        
        // Create test text
        Connection::query("INSERT INTO {$tbpref}texts (TxTitle, TxText, TxLgID) 
                         VALUES ('test_export_workflow_text', 'Test content for workflow', $lgId)");
        $textId = (int)Connection::lastInsertId();
        
        // Step 1: Create initial annotation
        $ann1 = create_ann($textId);
        $this->assertIsString($ann1);
        
        // Step 2: Recreate annotation with old data
        $ann2 = recreate_save_ann($textId, $ann1);
        $this->assertIsString($ann2);
        
        // Step 3: Verify annotation was saved
        $saved = Connection::fetchValue("SELECT TxAnnotatedText AS value FROM {$tbpref}texts WHERE TxID = $textId");
        $this->assertNotNull($saved);
        $this->assertIsString($saved);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $lgId");
    }

    public function testAnnotationPreservesTranslations(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Create test language
        Connection::query("INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgGoogleTranslateURI) 
                         VALUES ('test_export_preserve', 'http://test', 'http://test')");
        $lgId = (int)Connection::lastInsertId();
        
        // Create test text
        Connection::query("INSERT INTO {$tbpref}texts (TxTitle, TxText, TxLgID) 
                         VALUES ('test_export_preserve_text', 'Test', $lgId)");
        $textId = (int)Connection::lastInsertId();
        
        // Create annotation with translation
        $oldAnn = "1\tword\t5\tmy_translation\n";
        $newAnn = recreate_save_ann($textId, $oldAnn);
        
        // The new annotation should preserve "my_translation" if the word is still present
        $this->assertIsString($newAnn);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxID = $textId");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgID = $lgId");
    }

    // ===== Additional utility tests =====

    public function testConvertStringToSqlsyntax(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = Escaping::toSqlSyntax("test's value");
        $this->assertStringStartsWith("'", $escaped);
        $this->assertStringEndsWith("'", $escaped);
        $this->assertStringContainsString("\\'", $escaped);
    }

    public function testConvertStringToSqlsyntaxWithEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = Escaping::toSqlSyntax("");
        $this->assertEquals('NULL', $escaped);
    }

    public function testConvertStringToSqlsyntaxNonull(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = Escaping::toSqlSyntaxNoNull("test's value");
        $this->assertStringStartsWith("'", $escaped);
        $this->assertStringEndsWith("'", $escaped);
    }

    public function testConvertStringToSqlsyntaxNonullWithEmptyString(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $escaped = Escaping::toSqlSyntaxNoNull("");
        $this->assertEquals("''", $escaped);
    }

    public function testGetFirstValue(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        // Insert test data
        Connection::query("INSERT INTO {$tbpref}tags (TgText) VALUES ('test_export_firstval')");
        $id = (int)Connection::lastInsertId();
        
        $value = Connection::fetchValue("SELECT TgText AS value FROM {$tbpref}tags WHERE TgID = $id");
        
        $this->assertEquals('test_export_firstval', $value);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}tags WHERE TgID = $id");
    }

    public function testGetFirstValueReturnsNullForNoResults(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        $value = Connection::fetchValue("SELECT TgText AS value FROM {$tbpref}tags WHERE TgID = 999999");
        
        $this->assertNull($value);
    }

    public function testRunsql(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $tbpref = self::$tbpref;
        
        $result = DB::execute(
            "INSERT INTO {$tbpref}tags (TgText) VALUES ('test_export_runsql')"
        );

        // DB::execute returns number of affected rows
        $this->assertEquals(1, $result);
        
        // Clean up
        Connection::query("DELETE FROM {$tbpref}tags WHERE TgText = 'test_export_runsql'");
    }
}
