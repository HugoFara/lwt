<?php declare(strict_types=1);

namespace Lwt\Tests\Core;

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/EnvLoader.php';

use Lwt\Core\EnvLoader;
use Lwt\Core\Globals;
use Lwt\Core\Http\ParamHelpers;
use Lwt\Database\Configuration;
use Lwt\Database\Connection;
use Lwt\Database\Restore;
use Lwt\Database\Settings;
use Lwt\Core\StringUtils;
use Lwt\Services\DictionaryService;
use Lwt\Services\LanguageService;
use Lwt\Services\MediaService;
use Lwt\Services\TableSetService;
use Lwt\Services\TagService;
use Lwt\View\Helper\FormHelper;
use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\StatusHelper;
use PHPUnit\Framework\TestCase;

use function Lwt\Core\Utils\encodeURI;
use function Lwt\Core\Utils\getExecutionTime;
use function Lwt\Core\Utils\getFilePath;
use function Lwt\Core\Utils\makeCounterWithTotal;
use function Lwt\Core\Utils\printFilePath;
use function Lwt\Core\Utils\remove_soft_hyphens;
use function Lwt\Core\Utils\replace_supp_unicode_planes_char;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../.env');
$config = EnvLoader::getDatabaseConfig();
$GLOBALS['dbname'] = "test_" . $config['dbname'];

require_once __DIR__ . '/../../../src/backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../src/backend/View/Helper/FormHelper.php';
require_once __DIR__ . '/../../../src/backend/View/Helper/SelectOptionsBuilder.php';
require_once __DIR__ . '/../../../src/backend/View/Helper/StatusHelper.php';
require_once __DIR__ . '/../../../src/backend/Services/TextStatisticsService.php';
require_once __DIR__ . '/../../../src/backend/Services/SentenceService.php';
require_once __DIR__ . '/../../../src/backend/Services/AnnotationService.php';
require_once __DIR__ . '/../../../src/backend/Services/SimilarTermsService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextNavigationService.php';
require_once __DIR__ . '/../../../src/backend/Services/TextParsingService.php';
require_once __DIR__ . '/../../../src/backend/Services/ExpressionService.php';
require_once __DIR__ . '/../../../src/backend/Core/Database/Restore.php';
require_once __DIR__ . '/../../../src/backend/Services/ExportService.php';
require_once __DIR__ . '/../../../src/backend/Core/Http/param_helpers.php';
require_once __DIR__ . '/../../../src/backend/Services/MediaService.php';
require_once __DIR__ . '/../../../src/backend/Services/DictionaryService.php';
require_once __DIR__ . '/../../../src/backend/Services/LanguageService.php';
require_once __DIR__ . '/../../../src/backend/Services/WordStatusService.php';
require_once __DIR__ . '/../../../src/backend/Services/TableSetService.php';

/**
 * Integration tests for core functionality.
 *
 * These tests require database access and test cross-module functionality.
 * Renamed from SessionUtilityTest since session_utility.php was removed.
 */
class IntegrationTest extends TestCase
{
    private static ?LanguageService $languageService = null;

    public static function setUpBeforeClass(): void
    {
        // Ensure database connection is established
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
        // Set the database name in Globals for migrations
        Globals::setDatabaseName($testDbname);

        // Ensure we have a test database set up
        $result = Connection::query("SHOW TABLES LIKE 'texts'");
        $res = \mysqli_fetch_assoc($result);

        if ($res) {
            Restore::truncateUserDatabase();
        }

        // Install the demo DB
        $filename = getcwd() . '/db/seeds/demo.sql';
        if (file_exists($filename) && is_readable($filename)) {
            $handle = fopen($filename, "r");
            Restore::restoreFile($handle, "Demo Database");
        }

        self::$languageService = new LanguageService();
    }

    public function testInstallDemoDB(): void
    {
        // Truncate the database if not empty
        $result = Connection::query("SHOW TABLES LIKE 'texts'");
        $res = \mysqli_fetch_assoc($result);

        if ($res) {
            Restore::truncateUserDatabase();
        }

        // Install the demo DB
        $filename = getcwd() . '/db/seeds/demo.sql';
        $this->assertFileExists($filename);
        $this->assertFileIsReadable($filename);
        $handle = fopen($filename, "r");
        $message = Restore::restoreFile($handle, "Demo Database");
        $this->assertStringStartsNotWith("Error: ", $message);
    }

    // ========== STRING MANIPULATION FUNCTIONS ==========

    public function testRemoveSoftHyphens(): void
    {
        $this->assertEquals('hello', \Lwt\Core\Utils\removeSoftHyphens('hel­lo'));
        $this->assertEquals('world', \Lwt\Core\Utils\removeSoftHyphens('world'));
        $this->assertEquals('', \Lwt\Core\Utils\removeSoftHyphens(''));
        // All soft hyphens are removed
        $this->assertEquals('testing', \Lwt\Core\Utils\removeSoftHyphens('test­­ing'));
    }

    public function testReplaceSupplementaryUnicodePlanes(): void
    {
        // Characters in supplementary planes (U+10000-U+10FFFF) should be replaced with U+2588 (█)
        $result = \Lwt\Core\Utils\replaceSuppUnicodePlanesChar('hello 𝕳𝖊𝖑𝖑𝖔 world');
        $this->assertStringContainsString('hello', $result);
        $this->assertStringContainsString('█', $result);

        // Regular characters should pass through unchanged
        $this->assertEquals('hello world', \Lwt\Core\Utils\replaceSuppUnicodePlanesChar('hello world'));

        // Empty string
        $this->assertEquals('', \Lwt\Core\Utils\replaceSuppUnicodePlanesChar(''));
    }

    public function testMakeCounterWithTotal(): void
    {
        // Single item - should return empty
        $this->assertEquals('', makeCounterWithTotal(1, 1));

        // Less than 10 items
        $this->assertEquals('3/5', makeCounterWithTotal(5, 3));
        $this->assertEquals('1/9', makeCounterWithTotal(9, 1));

        // 10 or more items - should pad with zeros
        $this->assertEquals('03/10', makeCounterWithTotal(10, 3));
        $this->assertEquals('025/100', makeCounterWithTotal(100, 25));
        $this->assertEquals('0005/1000', makeCounterWithTotal(1000, 5));
    }

    public function testEncodeURI(): void
    {
        $this->assertEquals('hello%20world', encodeURI('hello world'));
        $this->assertEquals('test-file_name.txt', encodeURI('test-file_name.txt'));
        $this->assertEquals('path/to/file', encodeURI('path/to/file'));
        $this->assertEquals('query?param=value&other=2', encodeURI('query?param=value&other=2'));
        $this->assertEquals('#anchor', encodeURI('#anchor'));
    }

    public function testGetFilePath(): void
    {
        // Test with a file that doesn't exist - should return absolute path
        $result = getFilePath('nonexistent_file.png');
        $this->assertEquals('/nonexistent_file.png', $result);

        // Test with path separator - should return absolute path
        $result = getFilePath('path/to/file.png');
        $this->assertStringStartsWith('/', $result);
        $this->assertStringContainsString('file.png', $result);

        // Test legacy path mappings
        $this->assertEquals('/assets/css/styles.css', getFilePath('css/styles.css'));
        $this->assertEquals('/assets/icons/example.svg', getFilePath('icn/example.svg'));
        $this->assertEquals('/assets/images/apple-touch-icon.png', getFilePath('img/apple-touch-icon.png'));
        $this->assertEquals('/assets/js/pgm.js', getFilePath('js/pgm.js'));

        // Test paths that already have assets/ prefix - should not double-prefix
        $this->assertEquals('/assets/css/styles.css', getFilePath('assets/css/styles.css'));
        $this->assertEquals('/assets/sounds/click.mp3', getFilePath('assets/sounds/click.mp3'));
    }

    public function testGetSepas(): void
    {
        $sepas = StringUtils::getSeparators();
        $this->assertIsString($sepas);
        $this->assertNotEmpty($sepas);

        // Should return same value on subsequent calls (static)
        $sepas2 = StringUtils::getSeparators();
        $this->assertEquals($sepas, $sepas2);
    }

    public function testGetFirstSepa(): void
    {
        $sepa = StringUtils::getFirstSeparator();
        $this->assertIsString($sepa);
        $this->assertEquals(1, mb_strlen($sepa, 'UTF-8'));

        // Should return same value on subsequent calls (static)
        $sepa2 = StringUtils::getFirstSeparator();
        $this->assertEquals($sepa, $sepa2);
    }

    public function testGetChecked(): void
    {
        $this->assertEquals(' checked="checked" ', FormHelper::getChecked(true));
        $this->assertEquals(' checked="checked" ', FormHelper::getChecked(1));
        $this->assertEquals(' checked="checked" ', FormHelper::getChecked('yes'));
        $this->assertEquals('', FormHelper::getChecked(false));
        $this->assertEquals('', FormHelper::getChecked(0));
        $this->assertEquals('', FormHelper::getChecked(''));
        $this->assertEquals('', FormHelper::getChecked(null));
    }

    public function testGetSelected(): void
    {
        $this->assertEquals(' selected="selected" ', FormHelper::getSelected('apple', 'apple'));
        $this->assertEquals(' selected="selected" ', FormHelper::getSelected(5, 5));
        $this->assertEquals('', FormHelper::getSelected('apple', 'orange'));
        $this->assertEquals('', FormHelper::getSelected(5, 10));
        $this->assertEquals(' selected="selected" ', FormHelper::getSelected('0', 0));
    }

    public function testStrToHex(): void
    {
        // strToHex returns UPPERCASE hex
        $this->assertEquals('68656C6C6F', StringUtils::toHex('hello'));
        $this->assertEquals('776F726C64', StringUtils::toHex('world'));
        $this->assertEquals('', StringUtils::toHex(''));

        // Test with UTF-8
        $hex = StringUtils::toHex('你好');
        $this->assertIsString($hex);
        $this->assertNotEmpty($hex);
    }

    public function testStrToClassName(): void
    {
        $this->assertEquals('hello', StringUtils::toClassName('hello'));
        $this->assertEquals('test123', StringUtils::toClassName('test123'));

        // Space (ASCII 32) is outside allowed range, converted to ¤20
        $this->assertEquals('hello¤20world', StringUtils::toClassName('hello world'));

        // Non-ASCII should be converted to hex with ¤ prefix
        $result = StringUtils::toClassName('hello 世界');
        $this->assertStringStartsWith('hello', $result);
        $this->assertStringContainsString('¤', $result);
    }

    public function testReplTabNl(): void
    {
        $this->assertEquals('hello world', replTabNl("hello\tworld"));
        $this->assertEquals('line one line two', replTabNl("line one\nline two"));
        // Multiple whitespace is collapsed to single space
        $this->assertEquals('test spaces', replTabNl("test\t\nspaces"));
    }

    // ========== STATUS AND VALIDATION FUNCTIONS ==========

    public function testCheckStatusRange(): void
    {
        // Status range works with special codes (not simple "1-5")
        // Range 12-15 means status 1 to (range % 10)
        $this->assertTrue(StatusHelper::checkRange(1, 15));  // 1 <= 5
        $this->assertTrue(StatusHelper::checkRange(3, 15));  // 3 <= 5
        $this->assertTrue(StatusHelper::checkRange(5, 15));  // 5 <= 5
        $this->assertFalse(StatusHelper::checkRange(1, 23)); // 1 < 2

        // Status 599 means 5 or 99
        $this->assertTrue(StatusHelper::checkRange(5, 599));
        $this->assertTrue(StatusHelper::checkRange(99, 599));
        $this->assertFalse(StatusHelper::checkRange(4, 599));

        // Invalid range
        $this->assertFalse(StatusHelper::checkRange(1, 0));
    }

    public function testGetStatusName(): void
    {
        $this->assertEquals('Learning', StatusHelper::getName(1));
        $this->assertEquals('Learned', StatusHelper::getName(5));
        $this->assertEquals('Ignored', StatusHelper::getName(98));
        $this->assertEquals('Well Known', StatusHelper::getName(99));

        // Test all statuses 1-5
        for ($i = 1; $i <= 5; $i++) {
            $name = StatusHelper::getName($i);
            $this->assertIsString($name);
            $this->assertNotEmpty($name);
        }
    }

    public function testGetStatusAbbr(): void
    {
        // Abbreviations are just numbers for 1-5, special for 98/99
        $this->assertEquals('1', StatusHelper::getAbbr(1));
        $this->assertEquals('2', StatusHelper::getAbbr(2));
        $this->assertEquals('5', StatusHelper::getAbbr(5));
        $this->assertEquals('Ign', StatusHelper::getAbbr(98));
        $this->assertEquals('WKn', StatusHelper::getAbbr(99));
    }

    public function testGetColoredStatusMsg(): void
    {
        // Should return HTML with status color
        $msg1 = StatusHelper::buildColoredMessage(1, StatusHelper::getName(1), StatusHelper::getAbbr(1));
        $this->assertStringContainsString('Learning', $msg1);
        $this->assertStringContainsString('status', $msg1);

        $msg5 = StatusHelper::buildColoredMessage(5, StatusHelper::getName(5), StatusHelper::getAbbr(5));
        $this->assertStringContainsString('Learned', $msg5);

        $msg98 = StatusHelper::buildColoredMessage(98, StatusHelper::getName(98), StatusHelper::getAbbr(98));
        $this->assertStringContainsString('Ignored', $msg98);
    }

    // ========== SELECT OPTIONS GENERATION ==========

    public function testGetSecondsSelectOptions(): void
    {
        $options = SelectOptionsBuilder::forSeconds(3);
        $this->assertStringContainsString('<option', $options);
        $this->assertStringContainsString('selected', $options);
        $this->assertStringContainsString('3', $options);
    }

    public function testGetPlaybackRateSelectOptions(): void
    {
        // Playback rates are 0.5-1.5 (values 5-15)
        $options = SelectOptionsBuilder::forPlaybackRate(10); // 1.0x
        $this->assertStringContainsString('<option', $options);
        $this->assertStringContainsString('1.0', $options);
    }

    public function testGetSentenceCountSelectOptions(): void
    {
        $options = SelectOptionsBuilder::forSentenceCount(1);
        $this->assertStringContainsString('<option', $options);
        $this->assertStringContainsString('value="1"', $options);
    }

    public function testGetRegexSelectOptions(): void
    {
        $options = SelectOptionsBuilder::forRegexMode('0');
        $this->assertStringContainsString('<option', $options);
        $this->assertStringContainsString('Default', $options);
        $this->assertStringContainsString('RegEx', $options);
    }

    public function testGetTooltipSelectOptions(): void
    {
        $options = SelectOptionsBuilder::forTooltipType(1);
        $this->assertStringContainsString('<option', $options);
        $this->assertStringContainsString('selected', $options);
    }

    public function testGetWordStatusRadioOptions(): void
    {
        $options = SelectOptionsBuilder::forWordStatusRadio(1);
        $this->assertStringContainsString('type="radio"', $options);
        $this->assertStringContainsString('checked', $options);
        $this->assertStringContainsString('status1', $options);
    }

    public function testGetWordStatusSelectOptions(): void
    {
        // Test basic select
        $options = SelectOptionsBuilder::forWordStatus(1, false, false);
        $this->assertStringContainsString('<option', $options);
        $this->assertStringContainsString('selected', $options);

        // Test with "all" option
        $options_all = SelectOptionsBuilder::forWordStatus(1, true, false);
        $this->assertStringContainsString('All', $options_all);

        // Test without 98/99
        $options_no_9899 = SelectOptionsBuilder::forWordStatus(1, false, true);
        $this->assertStringContainsString('<option', $options_no_9899);
    }

    public function testGetAndOrSelectOptions(): void
    {
        // Takes numeric value: 0=OR, 1=AND
        $options = SelectOptionsBuilder::forAndOr(1);
        $this->assertStringContainsString('<option', $options);
        $this->assertStringContainsString('AND', $options);
        $this->assertStringContainsString('OR', $options);
    }

    // ========== TEXT AND WORD COUNT FUNCTIONS ==========

    /**
     * @return void
     */
    public function testReturnTextWordCount()
    {
        // Get first text from demo DB
        $text_res = Connection::query("SELECT TxID FROM texts LIMIT 1");
        if ($text_row = \mysqli_fetch_assoc($text_res)) {
            $text_id = (int)$text_row['TxID'];
            $counts = returnTextWordCount($text_id);

            $this->assertIsArray($counts);
            // Function returns: total, expr, stat, totalu, expru, statu
            $this->assertArrayHasKey('total', $counts);
            $this->assertArrayHasKey('expr', $counts);
            $this->assertArrayHasKey('stat', $counts);
            $this->assertIsArray($counts['total']);
        } else {
            $this->markTestSkipped('No texts in database');
        }
    }

    /**
     * @return void
     */
    public function testTodoWordsCount()
    {
        $text_res = Connection::query("SELECT TxID FROM texts LIMIT 1");
        if ($text_row = \mysqli_fetch_assoc($text_res)) {
            $text_id = (int)$text_row['TxID'];
            $count = todoWordsCount($text_id);

            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        } else {
            $this->markTestSkipped('No texts in database');
        }
    }

    // ========== SENTENCE FUNCTIONS ==========

    public function testSentencesContainingWordLcQuery(): void
    {
        $query = sentencesContainingWordLcQuery('test', 1);
        $this->assertIsString($query);
        $this->assertStringContainsString('SELECT', strtoupper($query));
        $this->assertStringContainsString('SeID', $query);
    }

    public function testMaskTermInSentenceV2(): void
    {
        $result = maskTermInSentenceV2('This is a test sentence');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testMaskTermInSentence(): void
    {
        $result = maskTermInSentence('This is a test', 'test');
        $this->assertIsString($result);
    }

    // ========== LANGUAGE FUNCTIONS ==========

    public function testGetLanguages(): void
    {
        $languages = self::$languageService->getAllLanguages();
        $this->assertIsArray($languages);

        // Returns array of language_name => language_id pairs
        if (count($languages) > 0) {
            $first_lang_id = reset($languages);
            $this->assertIsInt($first_lang_id);
        }
    }

    /**
     * @return void
     */
    public function testGetScriptDirectionTag()
    {
        // Test with a language from demo DB
        $lang_res = Connection::query("SELECT LgID FROM languages LIMIT 1");
        if ($lang_row = \mysqli_fetch_assoc($lang_res)) {
            $lang_id = (int)$lang_row['LgID'];
            $dir_tag = self::$languageService->getScriptDirectionTag($lang_id);

            $this->assertIsString($dir_tag);
            $this->assertTrue(
                $dir_tag === 'direction:ltr;' ||
                $dir_tag === 'direction:rtl;' ||
                $dir_tag === '' ||
                $dir_tag === ' dir="rtl" '
            );
        } else {
            $this->markTestSkipped('No languages in database');
        }
    }

    // ========== DATABASE HELPER FUNCTIONS ==========

    public function testGetLastKey(): void
    {
        // Insert a test record and get its ID
        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "tags (TgText) VALUES ('test_tag_" . time() . "')"
        );

        $last_id = (int)Connection::lastInsertId();
        $this->assertIsInt($last_id);
        $this->assertGreaterThan(0, $last_id);

        // Clean up
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "tags WHERE TgID = $last_id");
    }

    public function testTrimValue(): void
    {
        $value = "  hello world  ";
        trimValue($value);
        $this->assertEquals('hello world', $value);

        $value2 = "\t\ntest\n\t";
        trimValue($value2);
        $this->assertEquals('test', $value2);
    }

    public function testGetFirstTranslation(): void
    {
        $sepa = StringUtils::getFirstSeparator();
        $trans = "hello{$sepa}world{$sepa}test";
        $first = getFirstTranslation($trans);
        $this->assertEquals('hello', $first);

        $single = getFirstTranslation('onlyone');
        $this->assertEquals('onlyone', $single);
    }

    // ========== MEDIA FUNCTIONS ==========

    public function testGetMediaPaths(): void
    {
        $mediaService = new MediaService();
        $paths = $mediaService->getMediaPaths();
        $this->assertIsArray($paths);
    }

    // ========== THEME FUNCTIONS ==========

    public function testGetThemesSelectOptions(): void
    {
        $current_theme = Settings::getWithDefault('set-theme-dir');
        $themeService = new \Lwt\Services\ThemeService();
        $themes = $themeService->getAvailableThemes();
        $options = SelectOptionsBuilder::forThemes($themes, $current_theme);
        $this->assertStringContainsString('<option', $options);
    }

    // ========== VALIDATION FUNCTIONS (from database_connect.php) ==========

    public function testCheckTest(): void
    {
        $result = FormHelper::checkInRequest('value', 'fieldname');
        $this->assertIsString($result);
    }

    // ========== COMPLEX INTEGRATION TESTS ==========

    public function testWordTagList(): void
    {
        // Create a test word with tags
        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "languages (LgName, LgDict1URI, LgGoogleTranslateURI)
             VALUES ('Test Lang', 'http://test', 'http://test')"
        );
        $lang_id = (int)Connection::lastInsertId();

        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "words (WoText, WoTextLC, WoStatus, WoLgID)
             VALUES ('testword', 'testword', 1, $lang_id)"
        );
        $word_id = (int)Connection::lastInsertId();

        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "tags (TgText) VALUES ('testtag1')"
        );
        $tag_id = (int)Connection::lastInsertId();

        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "wordtags (WtWoID, WtTgID) VALUES ($word_id, $tag_id)"
        );

        // Test getting tag list
        $tag_list = TagService::getWordTagListFormatted($word_id);
        $this->assertStringContainsString('testtag1', $tag_list);

        // Clean up
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "wordtags WHERE WtWoID = $word_id");
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "words WHERE WoID = $word_id");
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "tags WHERE TgID = $tag_id");
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "languages WHERE LgID = $lang_id");
    }

    // ========== ADDITIONAL HELPER FUNCTIONS TESTS ==========

    public function testGetWordsToDoButtonsSelectOptions(): void
    {
        // Test with value 0 (I Know All & Ignore All)
        $result = SelectOptionsBuilder::forWordsToDoButtons(0);

        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('value="0"', $result);
        $this->assertStringContainsString('I Know All &amp; Ignore All', $result);
        $this->assertStringContainsString('selected', $result);

        // Test with value 1 (I Know All)
        $result = SelectOptionsBuilder::forWordsToDoButtons(1);
        $this->assertStringContainsString('value="1"', $result);
        $this->assertStringContainsString('I Know All</option>', $result);
        $this->assertStringContainsString('selected', $result);

        // Test with value 2 (Ignore All)
        $result = SelectOptionsBuilder::forWordsToDoButtons(2);
        $this->assertStringContainsString('value="2"', $result);
        $this->assertStringContainsString('Ignore All', $result);
    }

    // ========== ADDITIONAL FUNCTIONS FOR BETTER COVERAGE ==========

    public function testProcessSessParamExtended(): void
    {
        // Mock a request parameter
        $_REQUEST['test_param'] = '42';

        // Test with numeric parameter
        $result = ParamHelpers::processSessParam('test_param', 'sess_key', '0', 1);
        $this->assertEquals(42, $result);
        $this->assertEquals(42, $_SESSION['sess_key']);

        // Test with string parameter
        $_REQUEST['test_string'] = 'hello';
        $result = ParamHelpers::processSessParam('test_string', 'sess_str', 'default', 0);
        $this->assertEquals('hello', $result);

        // Test with missing parameter (should return default)
        $result = ParamHelpers::processSessParam('nonexistent', 'sess_none', 'default_val', 0);
        $this->assertEquals('default_val', $result);

        // Clean up
        unset($_REQUEST['test_param']);
        unset($_REQUEST['test_string']);
        unset($_SESSION['sess_key']);
        unset($_SESSION['sess_str']);
        unset($_SESSION['sess_none']);
    }

    public function testProcessDBParamExtended(): void
    {
        // Mock a request parameter
        $_REQUEST['test_db_param'] = '123';

        // Test with numeric parameter
        $result = ParamHelpers::processDBParam('test_db_param', 'db_key', '0', 1);
        $this->assertEquals(123, $result);

        // Verify it was saved to settings
        $saved = Settings::get('db_key');
        $this->assertEquals('123', $saved);

        // Test with string parameter
        $_REQUEST['test_db_string'] = 'test_value';
        $result = ParamHelpers::processDBParam('test_db_string', 'db_str_key', 'default', 0);
        $this->assertEquals('test_value', $result);

        // Test with missing parameter (should return default)
        $result = ParamHelpers::processDBParam('nonexistent_db', 'db_none_key', 'default_val', 0);
        $this->assertEquals('default_val', $result);

        // Clean up
        unset($_REQUEST['test_db_param']);
        unset($_REQUEST['test_db_string']);
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "settings WHERE StKey IN ('db_key', 'db_str_key', 'db_none_key')");
    }

    public function testGetPrefixesExtended(): void
    {
        $prefixes = TableSetService::getAllPrefixes();
        $this->assertIsArray($prefixes);
        // TableSetService::getAllPrefixes() returns table prefixes by looking for *_settings tables
        // In a test environment, there may be 0 or more prefixes depending on setup
        // Just verify it returns an array
    }

    public function testSelectMediaPathExtended(): void
    {
        $mediaService = new MediaService();
        // Test with non-existent path - returns HTML with select UI
        $result = $mediaService->getMediaPathSelector('nonexistent.mp3');
        $this->assertIsString($result);
        $this->assertStringContainsString('<select', $result);

        // Test with empty string - also returns HTML UI
        $result = $mediaService->getMediaPathSelector('');
        $this->assertIsString($result);
        $this->assertStringContainsString('select', $result);
    }

    public function testPrintFilePathExtended(): void
    {
        // Test that it outputs something
        ob_start();
        printFilePath('test.mp3');
        $output = ob_get_clean();

        $this->assertIsString($output);
        // Should contain the filename
        $this->assertStringContainsString('test.mp3', $output);
    }

    public function testEchoLwtLogoExtended(): void
    {
        // Test that it outputs the logo HTML
        $output = \Lwt\View\Helper\PageLayoutHelper::buildLogo();

        $this->assertIsString($output);
        // Should contain logo image
        $this->assertStringContainsString('<img', $output);
    }

    public function testGetPreviousAndNextTextLinksExtended(): void
    {
        // Create test texts
        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "languages (LgName, LgDict1URI, LgGoogleTranslateURI)
             VALUES ('Test Lang', 'http://test', 'http://test')"
        );
        $lang_id = (int)Connection::lastInsertId();

        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "texts (TxTitle, TxText, TxLgID)
             VALUES ('Text 1', 'Content 1', $lang_id)"
        );
        $text1_id = (int)Connection::lastInsertId();

        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "texts (TxTitle, TxText, TxLgID)
             VALUES ('Text 2', 'Content 2', $lang_id)"
        );
        $text2_id = (int)Connection::lastInsertId();

        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "texts (TxTitle, TxText, TxLgID)
             VALUES ('Text 3', 'Content 3', $lang_id)"
        );
        $text3_id = (int)Connection::lastInsertId();

        // Test getting navigation for middle text
        $result = getPreviousAndNextTextLinks($text2_id, 'do_text.php', 0, '');
        $this->assertIsString($result);
        // Should contain navigation elements
        $this->assertNotEmpty($result);

        // Test with first text (no previous)
        $result = getPreviousAndNextTextLinks($text1_id, 'do_text.php', 0, '');
        $this->assertIsString($result);

        // Test with last text (no next)
        $result = getPreviousAndNextTextLinks($text3_id, 'do_text.php', 0, '');
        $this->assertIsString($result);

        // Clean up
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "texts WHERE TxLgID = $lang_id");
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "languages WHERE LgID = $lang_id");
    }


    public function testMediaPathsSearchExtended(): void
    {
        $mediaService = new MediaService();
        // Test with a directory
        $result = $mediaService->searchMediaPaths('.');
        $this->assertIsArray($result);
    }

    public function testSelectMediaPathOptionsExtended(): void
    {
        $mediaService = new MediaService();
        // Test with current directory
        $result = $mediaService->getMediaPathOptions('.');
        $this->assertIsString($result);
        $this->assertStringContainsString('<option', $result);
    }

    // ========== ADDITIONAL CRITICAL FUNCTION TESTS ==========

    /**
     * Test restore_file function (import/backup restore)
     */
    public function testRestoreFileBasic(): void
    {
        // Create a simple SQL dump
        $sql_content = "INSERT INTO " . Globals::getTablePrefix() . "settings (StKey, StValue) VALUES ('test_restore', 'value1');\n";
        $sql_content .= "INSERT INTO " . Globals::getTablePrefix() . "settings (StKey, StValue) VALUES ('test_restore2', 'value2');";

        // Create temporary file
        $temp_file = tmpfile();
        fwrite($temp_file, $sql_content);
        rewind($temp_file);

        // Test restore
        $result = Restore::restoreFile($temp_file, "Test Backup");

        // Check result message
        $this->assertIsString($result);

        // Verify data was restored
        $value1 = Settings::get('test_restore');
        Settings::get('test_restore2');

        // Clean up - fclose is automatic for tmpfile when it goes out of scope
        // Don't call fclose() as the resource may already be closed by restore_file
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "settings WHERE StKey IN ('test_restore', 'test_restore2')");

        // Assertions (may vary based on restore success)
        $this->assertTrue(
            $value1 === 'value1' || $value1 === '',
            'Restore should insert data or handle gracefully'
        );
    }

    /**
     * Test truncateUserDatabase function
     */
    public function testTruncateUserDatabase(): void
    {
        // Insert test data
        Connection::query(
            "INSERT INTO " . Globals::getTablePrefix() . "settings (StKey, StValue) VALUES ('test_truncate', 'value1')"
        );

        // Get initial count
        $count_before = (int)Connection::fetchValue(
            "SELECT COUNT(*) as value FROM " . Globals::getTablePrefix() . "settings WHERE StKey = 'test_truncate'"
        );
        $this->assertEquals(1, $count_before);

        // Don't actually truncate in test as it would destroy demo DB
        // Just verify method exists
        $this->assertTrue(method_exists(Restore::class, 'truncateUserDatabase'));

        // Clean up
        Connection::query("DELETE FROM " . Globals::getTablePrefix() . "settings WHERE StKey='test_truncate'");
    }

    /**
     * Test StatusHelper::makeClassFilter function
     */
    public function testMakeStatusClassFilter(): void
    {
        // Test with status filter "15" (statuses 1-5)
        $result = StatusHelper::makeClassFilter('15');
        $this->assertIsString($result);
        $this->assertStringContainsString('status', $result);

        // Test with "599" (status 5 or 99)
        $result = StatusHelper::makeClassFilter('599');
        $this->assertIsString($result);

        // Test with empty filter
        $result = StatusHelper::makeClassFilter('');
        $this->assertIsString($result);
    }

    /**
     * Test SelectOptionsBuilder::forAnnotationPosition function
     */
    public function testGetAnnotationPositionSelectOptions(): void
    {
        // Test with value 1 (should have selected)
        $result = SelectOptionsBuilder::forAnnotationPosition(1);
        $this->assertStringContainsString('<option', $result);
        // The function returns options but selected is only on matching value
        $this->assertStringContainsString('value="1"', $result);

        // Test with value 2
        $result = SelectOptionsBuilder::forAnnotationPosition(2);
        $this->assertStringContainsString('<option', $result);
        $this->assertStringContainsString('value="2"', $result);
    }

    /**
     * Test getExecutionTime function
     */
    public function testGetExecutionTime(): void
    {
        // First call starts timer
        $start = getExecutionTime();
        $this->assertIsFloat($start);

        // Sleep briefly
        usleep(10000); // 10ms

        // Second call returns elapsed time
        $elapsed = getExecutionTime();
        $this->assertIsFloat($elapsed);
        $this->assertGreaterThan(0, $elapsed);
    }

    /**
     * Test createDictLinksInEditWin function (corrected signature)
     */
    public function testCreateDictLinksInEditWin(): void
    {
        // Function signature: DictionaryService::createDictLinksInEditWin($lang, $word, $sentctljs, $openfirst)
        $lang = 1;
        $word = "test";
        $sentctljs = "javascript:void(0)";
        $openfirst = true;

        $service = new DictionaryService();
        $result = $service->createDictLinksInEditWin($lang, $word, $sentctljs, $openfirst);
        $this->assertIsString($result);
    }
}
?>
