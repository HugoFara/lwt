<?php declare(strict_types=1);

namespace Lwt\Tests\Core\UI;

require_once __DIR__ . '/../../../../src/backend/Core/Globals.php';
require_once __DIR__ . '/../../../../src/backend/Core/Utils/string_utilities.php';
require_once __DIR__ . '/../../../../src/backend/Core/version.php';
require_once __DIR__ . '/../../../../src/backend/View/Helper/PageLayoutHelper.php';

use Lwt\Core\Globals;
use Lwt\View\Helper\PageLayoutHelper;
use PHPUnit\Framework\TestCase;

Globals::initialize();

/**
 * Tests for PageLayoutHelper (migrated from ui_helpers.php)
 */
final class UiHelpersTest extends TestCase
{
    /**
     * Test PageLayoutHelper::buildNavbar() function
     */
    public function testQuickMenu(): void
    {
        $output = PageLayoutHelper::buildNavbar();

        // Should output a nav element
        $this->assertStringContainsString('<nav', $output);
        $this->assertStringContainsString('class="navbar', $output);
        $this->assertStringContainsString('role="navigation"', $output);

        // Should contain various menu sections
        $this->assertStringContainsString('href="/"', $output);
        $this->assertStringContainsString('href="/languages"', $output);
        $this->assertStringContainsString('href="/texts"', $output);
        $this->assertStringContainsString('href="/words/edit"', $output);
    }

    /**
     * Test PageLayoutHelper::renderPageStartKernelNobody() function
     */
    public function testPagestartKernelNobody(): void
    {
        // Capture output
        ob_start();
        PageLayoutHelper::renderPageStartKernelNobody('Test Page');
        $output = ob_get_clean();

        // Should output HTML document structure
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('<html lang="en">', $output);
        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<title>LWT :: Test Page</title>', $output);

        // Should have meta tags
        $this->assertStringContainsString('charset=utf-8', $output);
        $this->assertStringContainsString('viewport', $output);
    }

    /**
     * Test PageLayoutHelper::renderPageEnd() function
     */
    public function testPageend(): void
    {
        // Capture output
        ob_start();
        PageLayoutHelper::renderPageEnd();
        $output = ob_get_clean();

        // Should close body and html tags
        $this->assertStringContainsString('</body>', $output);
        $this->assertStringContainsString('</html>', $output);
    }
}
