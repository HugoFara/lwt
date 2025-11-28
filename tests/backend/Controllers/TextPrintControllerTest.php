<?php

declare(strict_types=1);

namespace Lwt\Tests\Controllers;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Controllers\TextPrintController;
use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Services\TextPrintService;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/Core/UI/ui_helpers.php';
require_once __DIR__ . '/../../../src/backend/Core/Http/param_helpers.php';
require_once __DIR__ . '/../../../src/backend/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/backend/Controllers/TextPrintController.php';
require_once __DIR__ . '/../../../src/backend/Services/TextPrintService.php';

/**
 * Unit tests for the TextPrintController class.
 *
 * Tests controller initialization, service integration,
 * and verifies the MVC pattern implementation.
 */
class TextPrintControllerTest extends TestCase
{
    private static bool $dbConnected = false;
    private static string $tbpref = '';
    private static int $testLangId = 0;
    private static int $testTextId = 0;
    private array $originalRequest;
    private array $originalServer;
    private array $originalGet;
    private array $originalPost;

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

        if (self::$dbConnected) {
            // Create a test language if it doesn't exist
            $tbpref = self::$tbpref;
            $existingLang = Connection::fetchValue(
                "SELECT LgID AS value FROM {$tbpref}languages WHERE LgName = 'PrintControllerTestLang' LIMIT 1"
            );

            if ($existingLang) {
                self::$testLangId = (int)$existingLang;
            } else {
                Connection::query(
                    "INSERT INTO {$tbpref}languages (LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI, " .
                    "LgTextSize, LgCharacterSubstitutions, LgRegexpSplitSentences, LgExceptionsSplitSentences, " .
                    "LgRegexpWordCharacters, LgRemoveSpaces, LgSplitEachChar, LgRightToLeft, LgShowRomanization) " .
                    "VALUES ('PrintControllerTestLang', 'http://test.com/###', '', 'http://translate.google.com/?sl=en&tl=fr&###', " .
                    "100, '', '.!?', '', 'a-zA-Z', 0, 0, 0, 1)"
                );
                self::$testLangId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }

            // Create a test text
            $existingText = Connection::fetchValue(
                "SELECT TxID AS value FROM {$tbpref}texts WHERE TxTitle = 'PrintControllerTestText' LIMIT 1"
            );

            if ($existingText) {
                self::$testTextId = (int)$existingText;
            } else {
                Connection::query(
                    "INSERT INTO {$tbpref}texts (TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI) " .
                    "VALUES (" . self::$testLangId . ", 'PrintControllerTestText', 'This is test text.', " .
                    "'0\tThis\t\t\n1\tis\t\t\n2\ttest\t\t\n3\ttext\t\ttranslation', " .
                    "'http://audio.test/audio.mp3', 'http://source.test')"
                );
                self::$testTextId = (int)Connection::fetchValue(
                    "SELECT LAST_INSERT_ID() AS value"
                );
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        $tbpref = self::$tbpref;
        // Clean up test data
        Connection::query("DELETE FROM {$tbpref}textitems2 WHERE Ti2TxID = " . self::$testTextId);
        Connection::query("DELETE FROM {$tbpref}sentences WHERE SeTxID = " . self::$testTextId);
        Connection::query("DELETE FROM {$tbpref}texts WHERE TxTitle = 'PrintControllerTestText'");
        Connection::query("DELETE FROM {$tbpref}languages WHERE LgName = 'PrintControllerTestLang'");
    }

    protected function setUp(): void
    {
        // Save original superglobals
        $this->originalRequest = $_REQUEST;
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;

        // Reset superglobals
        $_REQUEST = [];
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_REQUEST = $this->originalRequest;
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
    }

    // ===== Constructor tests =====

    public function testControllerCanBeInstantiated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();

        $this->assertInstanceOf(TextPrintController::class, $controller);
    }

    public function testControllerHasPrintService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $service = $controller->getPrintService();

        $this->assertInstanceOf(TextPrintService::class, $service);
    }

    // ===== Method existence tests =====

    public function testControllerHasRequiredMethods(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();

        $this->assertTrue(method_exists($controller, 'printPlain'));
        $this->assertTrue(method_exists($controller, 'printAnnotated'));
        $this->assertTrue(method_exists($controller, 'getPrintService'));
        $this->assertTrue(method_exists($controller, 'formatTermOutput'));
    }

    // ===== formatTermOutput tests =====

    public function testFormatTermOutputBehind(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            'rom',
            'trans',
            '',
            true,  // showRom
            true,  // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('annterm', $output);
        $this->assertStringContainsString('rom', $output);
        $this->assertStringContainsString('trans', $output);
    }

    public function testFormatTermOutputInFront(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            'rom',
            'trans',
            '',
            true,  // showRom
            true,  // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_INFRONT
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('annterm', $output);
        $this->assertStringContainsString('anntrans', $output);
    }

    public function testFormatTermOutputRuby(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            'rom',
            'trans',
            '',
            true,  // showRom
            true,  // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_RUBY
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('<ruby>', $output);
        $this->assertStringContainsString('anntermruby', $output);
        $this->assertStringContainsString('anntransruby', $output);
    }

    public function testFormatTermOutputWithTags(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            '',
            '',
            'tag1',
            false, // showRom
            true,  // showTrans
            true,  // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('tag1', $output);
    }

    public function testFormatTermOutputWithNoAnnotation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            '',
            '',
            '',
            false, // showRom
            false, // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringNotContainsString('annterm', $output);
    }

    public function testFormatTermOutputWithEmptyRomanization(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        // When showRom is true but rom is empty, showRom should become false
        $output = $controller->formatTermOutput(
            'word',
            '',     // empty romanization
            'trans',
            '',
            true,  // showRom - but should become false due to empty rom
            true,  // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('trans', $output);
        // Should not contain annrom class since rom is empty
        $this->assertStringNotContainsString('annrom', $output);
    }

    public function testFormatTermOutputWithEmptyTranslation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        // When showTrans is true but trans is empty, showTrans should become false
        $output = $controller->formatTermOutput(
            'word',
            'rom',
            '',     // empty translation
            '',
            true,  // showRom
            true,  // showTrans - but should become false due to empty trans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('rom', $output);
        // Should not contain anntrans class since trans is empty
        $this->assertStringNotContainsString('anntrans', $output);
    }

    // ===== Service integration tests =====

    public function testGetPrintServiceReturnsService(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $service = $controller->getPrintService();

        $this->assertInstanceOf(TextPrintService::class, $service);

        // Verify service works
        $data = $service->getTextData(self::$testTextId);
        $this->assertIsArray($data);
        $this->assertEquals('PrintControllerTestText', $data['TxTitle']);
    }

    // ===== Annotation combination tests =====

    public function testFormatTermOutputTranslationOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            'rom',
            'trans',
            '',
            false, // showRom
            true,  // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('anntrans', $output);
        $this->assertStringNotContainsString('annrom', $output);
    }

    public function testFormatTermOutputRomanizationOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            'rom',
            'trans',
            '',
            true,  // showRom
            false, // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('word', $output);
        $this->assertStringContainsString('annrom', $output);
        $this->assertStringNotContainsString('anntrans', $output);
    }

    public function testFormatTermOutputTagsAppendedToEmptyTranslation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        // When trans is empty but tags exist and showTags is true,
        // trans should become "* tags"
        $output = $controller->formatTermOutput(
            'word',
            '',
            '',        // empty translation
            'mytag',
            false, // showRom
            true,  // showTrans
            true,  // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('mytag', $output);
    }

    public function testFormatTermOutputTagsAppendedToTranslation(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        // When trans and tags both exist, they should be concatenated
        $output = $controller->formatTermOutput(
            'word',
            '',
            'mytrans',
            'mytag',
            false, // showRom
            true,  // showTrans
            true,  // showTags
            TextPrintService::ANN_PLACEMENT_BEHIND
        );

        $this->assertStringContainsString('mytrans', $output);
        $this->assertStringContainsString('mytag', $output);
    }

    // ===== Ruby format specific tests =====

    public function testFormatTermOutputRubyRomanizationOnly(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            'rom',
            '',     // no translation
            '',
            true,  // showRom
            false, // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_RUBY
        );

        $this->assertStringContainsString('<ruby>', $output);
        $this->assertStringContainsString('annromrubysolo', $output);
    }

    public function testFormatTermOutputRubyNoAnnotations(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $controller = new TextPrintController();
        $output = $controller->formatTermOutput(
            'word',
            '',
            '',
            '',
            false, // showRom
            false, // showTrans
            false, // showTags
            TextPrintService::ANN_PLACEMENT_RUBY
        );

        // Should just output the word without ruby tags
        $this->assertStringContainsString('word', $output);
        $this->assertStringNotContainsString('<ruby>', $output);
    }
}
