<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Utils;

require_once __DIR__ . '/../../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../../src/backend/Core/Utils/debug_utilities.php';

use Lwt\Core\Globals;
use PHPUnit\Framework\TestCase;
use function Lwt\Core\Utils\showRequest;
use function Lwt\Core\Utils\getExecutionTime;
use function Lwt\Core\Utils\echodebug;

Globals::initialize();

/**
 * Tests for debug_utilities.php functions
 */
final class DebugUtilitiesTest extends TestCase
{
    /**
     * Test getExecutionTime function
     */
    public function testGetExecutionTime(): void
    {
        // This function depends on REQUEST_TIME_FLOAT being set
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $time = getExecutionTime();
            $this->assertIsFloat($time);
            $this->assertGreaterThanOrEqual(0, $time);
        } else {
            // If REQUEST_TIME_FLOAT not set, should return 0
            $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
            usleep(1000); // Sleep 1ms to ensure time passes
            $time = getExecutionTime();
            $this->assertIsFloat($time);
            $this->assertGreaterThan(0, $time);
        }
    }

    /**
     * Test showRequest function
     */
    public function testShowRequest(): void
    {
        // Set up test request data
        $_REQUEST['test_key'] = 'test_value';
        $_REQUEST['another'] = 'data';

        // Capture output
        ob_start();
        showRequest();
        $output = ob_get_clean();

        // Should output request data
        $this->assertStringContainsString('_REQUEST', $output);

        // Clean up
        unset($_REQUEST['test_key']);
        unset($_REQUEST['another']);
    }

    /**
     * Test echodebug function
     */
    public function testEchodebug(): void
    {
        $originalDebug = Globals::getDebug();

        // Test with debug enabled
        Globals::setDebug(1);
        ob_start();
        echodebug('test value', 'Test Label');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Label', $output);
        $this->assertStringContainsString('test value', $output);

        // Test with debug disabled
        Globals::setDebug(0);
        ob_start();
        echodebug('test value', 'Test Label');
        $output = ob_get_clean();

        $this->assertEquals('', $output, 'Should not output anything when debug is disabled');

        // Restore original debug value
        Globals::setDebug($originalDebug);
    }
}
