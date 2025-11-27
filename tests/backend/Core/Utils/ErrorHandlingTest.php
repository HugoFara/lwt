<?php declare(strict_types=1);

namespace Lwt\Tests\Core\Utils;

require_once __DIR__ . '/../../../../src/backend/Core/settings.php';
require_once __DIR__ . '/../../../../src/backend/Core/Utils/string_utilities.php';
require_once __DIR__ . '/../../../../src/backend/Core/Utils/error_handling.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests for error_handling.php functions
 */
final class ErrorHandlingTest extends TestCase
{
    /**
     * Test error_message_with_hide function
     */
    public function testErrorMessageWithHide(): void
    {
        // Test with non-error message (noback=true - no back button)
        $result = error_message_with_hide('Test message', true);
        $this->assertStringContainsString('Test message', $result);
        $this->assertStringContainsString('msgblue', $result);
        $this->assertStringNotContainsString('onclick="history.back();"', $result);

        // Test with Error prefix (should show red error with back button when noback=false)
        $result = error_message_with_hide('Error: Something went wrong', false);
        $this->assertStringContainsString('Error: Something went wrong', $result);
        $this->assertStringContainsString('class="red"', $result);
        $this->assertStringContainsString('onclick="history.back();"', $result);

        // Test with Error prefix and noback=true (no back button)
        $result = error_message_with_hide('Error: Another problem', true);
        $this->assertStringContainsString('Error: Another problem', $result);
        $this->assertStringContainsString('class="red"', $result);
        $this->assertStringNotContainsString('onclick="history.back()"', $result);

        // Test empty message (should return empty string)
        $result = error_message_with_hide('', true);
        $this->assertEquals('', $result);

        // Test whitespace-only message (should return empty string)
        $result = error_message_with_hide('   ', false);
        $this->assertEquals('', $result);
    }
}
