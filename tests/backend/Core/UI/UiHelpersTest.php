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
     * Test PageLayoutHelper::buildQuickMenu() function
     */
    public function testQuickMenu(): void
    {
        $output = PageLayoutHelper::buildQuickMenu();

        // Should output a select element
        $this->assertStringContainsString('<select', $output);
        $this->assertStringContainsString('id="quickmenu"', $output);
        $this->assertStringContainsString('onchange=', $output);

        // Should contain various menu options
        $this->assertStringContainsString('value="index"', $output);
        $this->assertStringContainsString('value="edit_languages"', $output);
        $this->assertStringContainsString('value="edit_texts"', $output);
        $this->assertStringContainsString('value="edit_words"', $output);
    }

    /**
     * Test PageLayoutHelper::renderPageStartKernelNobody() function
     */
    public function testPagestartKernelNobody(): void
    {
        // Capture output
        ob_start();
        PageLayoutHelper::renderPageStartKernelNobody('Test Page', 'body { color: red; }');
        $output = ob_get_clean();

        // Should output HTML document structure
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
        $this->assertStringContainsString('<html lang="en">', $output);
        $this->assertStringContainsString('<head>', $output);
        $this->assertStringContainsString('<title>LWT :: Test Page</title>', $output);

        // Should include custom CSS
        $this->assertStringContainsString('body { color: red; }', $output);

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
