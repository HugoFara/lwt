<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Http;

require_once __DIR__ . '/../../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../../src/backend/Core/Http/param_helpers.php';
require_once __DIR__ . '/../../../../src/backend/Services/SettingsService.php';

use Lwt\Core\Globals;
use Lwt\Services\SettingsService;
use PHPUnit\Framework\TestCase;

Globals::initialize();

/**
 * Tests for param_helpers.php functions
 */
final class ParamHelpersTest extends TestCase
{
    /**
     * Test get request helper
     */
    public function testGetreq(): void
    {
        // Set up test request
        $_REQUEST['test_key'] = '  test_value  ';
        $_REQUEST['empty'] = '';

        // Should trim values
        $this->assertEquals('test_value', getreq('test_key'));

        // Should return empty string for empty values
        $this->assertEquals('', getreq('empty'));

        // Should return empty string for non-existent keys
        $this->assertEquals('', getreq('nonexistent'));

        // Clean up
        unset($_REQUEST['test_key']);
        unset($_REQUEST['empty']);
    }

    /**
     * Test getreq with special characters and trimming
     */
    public function testGetreqSpecialCharacters(): void
    {
        // HTML in request
        $_REQUEST['html_test'] = '<script>alert("XSS")</script>';
        $result = getreq('html_test');
        $this->assertEquals('<script>alert("XSS")</script>', $result);
        $this->assertStringNotContainsString('&lt;', $result);

        // Unicode with whitespace
        $_REQUEST['unicode_test'] = '  日本語  ';
        $this->assertEquals('日本語', getreq('unicode_test'));

        // Newlines and tabs in value
        $_REQUEST['whitespace_test'] = "  value\twith\nnewlines  ";
        $result = getreq('whitespace_test');
        // trim() only removes leading/trailing whitespace, not internal
        $this->assertEquals("value\twith\nnewlines", $result);

        // Clean up
        unset($_REQUEST['html_test']);
        unset($_REQUEST['unicode_test']);
        unset($_REQUEST['whitespace_test']);
    }

    /**
     * Test getsess function for session variable retrieval
     */
    public function testGetsess(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set up test session variables
        $_SESSION['test_key'] = '  test_value  ';
        $_SESSION['empty'] = '';
        $_SESSION['null_value'] = null;

        // Should trim values
        $this->assertEquals('test_value', getsess('test_key'));

        // Should return empty string for empty values
        $this->assertEquals('', getsess('empty'));

        // Should return empty string for null values
        $this->assertEquals('', getsess('null_value'));

        // Should return empty string for non-existent keys
        $this->assertEquals('', getsess('nonexistent'));

        // Clean up
        unset($_SESSION['test_key']);
        unset($_SESSION['empty']);
        unset($_SESSION['null_value']);
    }

    /**
     * Test getsess with various data types
     */
    public function testGetsessDataTypes(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Integer value
        $_SESSION['int_value'] = 42;
        $result = getsess('int_value');
        $this->assertEquals('42', $result); // Should be converted to string

        // Array value (should be converted to string)
        $_SESSION['array_value'] = ['test'];
        $result = getsess('array_value');
        $this->assertIsString($result);

        // Boolean values
        $_SESSION['bool_true'] = true;
        $_SESSION['bool_false'] = false;
        $this->assertEquals('1', getsess('bool_true'));
        $this->assertEquals('', getsess('bool_false'));

        // Clean up
        unset($_SESSION['int_value']);
        unset($_SESSION['array_value']);
        unset($_SESSION['bool_true']);
        unset($_SESSION['bool_false']);
    }

    /**
     * Test SettingsService::getDefinitions() function (formerly get_setting_data)
     */
    public function testGetSettingData(): void
    {
        $settings = SettingsService::getDefinitions();

        // Should return an array
        $this->assertIsArray($settings);
        $this->assertNotEmpty($settings);

        // Each setting should have 'dft' (default) and 'num' at minimum
        foreach ($settings as $key => $setting) {
            $this->assertIsArray($setting);
            $this->assertArrayHasKey('dft', $setting, "Setting '$key' should have 'dft' key");
            $this->assertArrayHasKey('num', $setting, "Setting '$key' should have 'num' key");

            // If numeric ('num' == 1), should have min and max
            if ($setting['num'] == 1) {
                $this->assertArrayHasKey('min', $setting, "Numeric setting '$key' should have 'min'");
                $this->assertArrayHasKey('max', $setting, "Numeric setting '$key' should have 'max'");
            }
        }

        // Test some known settings exist (using array keys)
        $settingKeys = array_keys($settings);
        $this->assertContains('set-texts-per-page', $settingKeys);
        $this->assertContains('set-terms-per-page', $settingKeys);
        $this->assertContains('set-theme_dir', $settingKeys);

        // Verify specific setting structure
        $this->assertEquals('10', $settings['set-texts-per-page']['dft']);
        $this->assertEquals(1, $settings['set-texts-per-page']['num']);
    }
}
