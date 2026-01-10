<?php declare(strict_types=1);
/**
 * \file
 * \brief Helper for page layout generation (headers, footers, navigation).
 *
 * PHP version 8.1
 *
 * @category View
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/src-backend-View-Helper-PageLayoutHelper.html
 * @since    3.0.0
 */

namespace Lwt\Shared\UI\Helpers;

require_once __DIR__ . '/../../Infrastructure/Http/UrlUtilities.php';
require_once __DIR__ . '/../Assets/ViteHelper.php';

use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Shared\UI\Assets\ViteHelper;
use Lwt\Core\StringUtils;

/**
 * Helper class for generating page layout elements.
 *
 * Provides methods for generating page headers, footers,
 * navigation menus, and other layout components.
 *
 * @since 3.0.0
 */
class PageLayoutHelper
{
    /**
     * Generate the main navigation bar HTML using Alpine.js and Bulma.
     *
     * @param string $currentPage Optional identifier for the current page to highlight
     *
     * @return string HTML navbar element
     */
    public static function buildNavbar(string $currentPage = ''): string
    {
        $homeIcon = IconHelper::render('home', ['alt' => 'Home']);
        $textsIcon = IconHelper::render('book-text', ['alt' => 'Texts']);
        $termsIcon = IconHelper::render('spell-check', ['alt' => 'Terms']);
        $languagesIcon = IconHelper::render('languages', ['alt' => 'Languages']);
        $statsIcon = IconHelper::render('bar-chart-2', ['alt' => 'Statistics']);
        $settingsIcon = IconHelper::render('settings', ['alt' => 'Settings']);

        $isTexts = in_array($currentPage, ['texts', 'archived', 'text-tags', 'text-check', 'long-import', 'feeds']);
        $isTerms = in_array($currentPage, ['terms', 'term-tags', 'term-import']);
        $isLanguages = in_array($currentPage, ['languages', 'language-new', 'language-edit']);
        $isAdmin = in_array($currentPage, ['backup', 'settings', 'tts']);

        $textsActive = $isTexts ? ' is-active' : '';
        $termsActive = $isTerms ? ' is-active' : '';
        $languagesActive = $isLanguages ? ' is-active' : '';
        $adminActive = $isAdmin ? ' is-active' : '';

        $base = UrlUtilities::getBasePath();
        $logoUrl = UrlUtilities::url('/assets/images/lwt_icon.png');

        return <<<HTML
<nav class="navbar is-light" role="navigation" aria-label="main navigation" x-data="navbar()">
    <div class="navbar-brand">
        <a class="navbar-item" href="{$base}/">
            <img src="{$logoUrl}" alt="LWT" width="28" height="28">
            <span class="ml-2 has-text-weight-semibold">LWT</span>
        </a>

        <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false"
           :class="{ 'is-active': isOpen }" @click="toggle()">
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </a>
    </div>

    <div class="navbar-menu" :class="{ 'is-active': isOpen }">
        <div class="navbar-start">
            <a class="navbar-item" href="{$base}/">
                {$homeIcon}
                <span class="ml-1">Home</span>
            </a>

            <div class="navbar-item has-dropdown{$textsActive}" :class="{ 'is-active': activeDropdown === 'texts' }">
                <a class="navbar-link" @click.prevent="toggleDropdown('texts')">
                    {$textsIcon}
                    <span class="ml-1">Texts</span>
                </a>
                <div class="navbar-dropdown">
                    <a class="navbar-item" href="{$base}/texts">Texts</a>
                    <a class="navbar-item" href="{$base}/text/archived">Archived Texts</a>
                    <hr class="navbar-divider">
                    <a class="navbar-item" href="{$base}/tags/text">Text Tags</a>
                    <a class="navbar-item" href="{$base}/text/check">Text Check</a>
                    <hr class="navbar-divider">
                    <a class="navbar-item" href="{$base}/feeds">Newsfeed Import</a>
                </div>
            </div>

            <div class="navbar-item has-dropdown{$termsActive}" :class="{ 'is-active': activeDropdown === 'terms' }">
                <a class="navbar-link" @click.prevent="toggleDropdown('terms')">
                    {$termsIcon}
                    <span class="ml-1">Terms</span>
                </a>
                <div class="navbar-dropdown">
                    <a class="navbar-item" href="{$base}/words/edit">Terms</a>
                    <a class="navbar-item" href="{$base}/tags">Term Tags</a>
                    <hr class="navbar-divider">
                    <a class="navbar-item" href="{$base}/word/upload">Import Terms</a>
                </div>
            </div>

            <div class="navbar-item has-dropdown{$languagesActive}" :class="{ 'is-active': activeDropdown === 'languages' }">
                <a class="navbar-link" @click.prevent="toggleDropdown('languages')">
                    {$languagesIcon}
                    <span class="ml-1">Languages</span>
                </a>
                <div class="navbar-dropdown">
                    <a class="navbar-item" href="{$base}/languages">Languages</a>
                    <hr class="navbar-divider">
                    <a class="navbar-item" href="{$base}/languages?new=1">Add New Language</a>
                </div>
            </div>

            <a class="navbar-item" href="{$base}/admin/statistics">
                {$statsIcon}
                <span class="ml-1">Statistics</span>
            </a>
        </div>

        <div class="navbar-end">
            <div class="navbar-item has-dropdown{$adminActive}" :class="{ 'is-active': activeDropdown === 'admin' }">
                <a class="navbar-link" @click.prevent="toggleDropdown('admin')">
                    {$settingsIcon}
                    <span class="ml-1">Admin</span>
                </a>
                <div class="navbar-dropdown is-right">
                    <a class="navbar-item" href="{$base}/admin/backup">Database Operations</a>
                    <a class="navbar-item" href="{$base}/admin/settings">Settings</a>
                    <hr class="navbar-divider">
                    <a class="navbar-item" href="{$base}/admin/server-data">Server Data</a>
                    <a class="navbar-item" href="{$base}/docs/info.html" target="_blank">Help</a>
                </div>
            </div>
        </div>
    </div>
</nav>
HTML;
    }

    /**
     * Generate an action card with buttons (non-collapsible).
     *
     * Creates a Bulma card with action buttons for page-level actions.
     *
     * @param array<array{url: string, label: string, icon?: string, class?: string, target?: string, attrs?: string}> $actions Array of actions
     *
     * @return string HTML for the action card
     */
    public static function buildActionCard(array $actions): string
    {
        $buttonsHtml = '';
        foreach ($actions as $action) {
            $url = htmlspecialchars(UrlUtilities::url($action['url']), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8');
            $icon = isset($action['icon']) ? IconHelper::render($action['icon'], ['alt' => $label]) : '';
            $class = isset($action['class']) ? ' ' . htmlspecialchars($action['class'], ENT_QUOTES, 'UTF-8') : '';
            $target = isset($action['target']) ? ' target="' . htmlspecialchars($action['target'], ENT_QUOTES, 'UTF-8') . '"' : '';
            // Allow custom attributes (e.g., Alpine.js directives) - not escaped to allow dynamic bindings
            $attrs = isset($action['attrs']) ? ' ' . $action['attrs'] : '';

            $buttonsHtml .= <<<HTML
                <a href="{$url}" class="button is-light{$class}"{$target}{$attrs}>
                    <span class="icon">{$icon}</span>
                    <span>{$label}</span>
                </a>
HTML;
        }

        return <<<HTML
<div class="card action-card mb-4">
    <div class="card-content">
        <div class="buttons is-centered">
            {$buttonsHtml}
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Generate the LWT logo HTML.
     *
     * @param string $imagePath Path to the logo image
     *
     * @return string HTML img element for the logo
     */
    public static function buildLogo(string $imagePath = 'assets/images/lwt_icon.png'): string
    {
        $path = StringUtils::getFilePath($imagePath);
        $url = UrlUtilities::url($path);
        return '<img class="lwtlogo" src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            . '" title="LWT" alt="LWT logo" />';
    }

    /**
     * Generate pagination controls HTML.
     *
     * @param int                  $currentPage    Current page number
     * @param int                  $totalPages     Total number of pages
     * @param string               $scriptUrl      Base URL for pagination links
     * @param string               $formName       Form name for JavaScript reference (unused, kept for BC)
     * @param array<string, mixed> $preserveParams Query parameters to preserve in pagination links
     *
     * @return string HTML pagination controls
     */
    public static function buildPager(
        int $currentPage,
        int $totalPages,
        string $scriptUrl,
        string $formName,
        array $preserveParams = []
    ): string {
        $result = '';
        $margerStyle = 'style="margin-left: 4px; margin-right: 4px;"';
        $escapedUrl = htmlspecialchars($scriptUrl, ENT_QUOTES, 'UTF-8');

        // Build query string from preserved params (excluding page)
        unset($preserveParams['page']);
        $baseQuery = '';
        if (!empty($preserveParams)) {
            // Filter out empty values
            $filtered = array_filter($preserveParams, fn($v) => $v !== '' && $v !== null);
            if (!empty($filtered)) {
                $baseQuery = http_build_query($filtered) . '&';
            }
        }

        // Helper to build page URL
        $pageUrl = fn(int $page): string =>
            $escapedUrl . '?' . $baseQuery . 'page=' . $page;

        // Previous page controls
        if ($currentPage > 1) {
            $result .= '<a href="' . $pageUrl(1) . '" ' . $margerStyle . '>';
            $result .= IconHelper::render('chevrons-left', ['title' => 'First Page', 'alt' => 'First Page']);
            $result .= '</a>';
            $result .= '<a href="' . $pageUrl($currentPage - 1) . '" ' . $margerStyle . '>';
            $result .= IconHelper::render('chevron-left', ['title' => 'Previous Page', 'alt' => 'Previous Page']);
            $result .= '</a>';
        }

        // Page indicator
        $result .= 'Page ';
        if ($totalPages == 1) {
            $result .= '1';
        } else {
            // Pass preserved params as data attribute for JS navigation
            $jsonParams = json_encode($preserveParams);
            $dataParams = !empty($preserveParams) && $jsonParams !== false
                ? ' data-preserve-params="' . htmlspecialchars($jsonParams, ENT_QUOTES, 'UTF-8') . '"'
                : '';
            $result .= '<select name="page" data-action="pager-navigate" data-base-url="' . $escapedUrl . '"' . $dataParams . '>';
            $result .= SelectOptionsBuilder::forPagination($currentPage, $totalPages);
            $result .= '</select>';
        }
        $result .= ' of ' . $totalPages . ' ';

        // Next page controls
        if ($currentPage < $totalPages) {
            $result .= '<a href="' . $pageUrl($currentPage + 1) . '" ' . $margerStyle . '>';
            $result .= IconHelper::render('chevron-right', ['title' => 'Next Page', 'alt' => 'Next Page']);
            $result .= '</a>';
            $result .= '<a href="' . $pageUrl($totalPages) . '" ' . $margerStyle . '>';
            $result .= IconHelper::render('chevrons-right', ['title' => 'Last Page', 'alt' => 'Last Page']);
            $result .= '</a>';
        }

        return $result;
    }

    /**
     * Generate HTTP cache prevention headers.
     *
     * @return void
     */
    public static function sendNoCacheHeaders(): void
    {
        @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
        @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        @header('Cache-Control: no-cache, must-revalidate, max-age=0');
        @header('Pragma: no-cache');
    }

    /**
     * Build the HTML head meta tags.
     *
     * @return string HTML meta tags
     */
    public static function buildMetaTags(): string
    {
        $basePath = UrlUtilities::getBasePath();
        $favicon = UrlUtilities::url('/favicon.ico');
        $icon57 = UrlUtilities::url('/assets/images/apple-touch-icon-57x57.png');
        $icon72 = UrlUtilities::url('/assets/images/apple-touch-icon-72x72.png');
        $icon114 = UrlUtilities::url('/assets/images/apple-touch-icon-114x114.png');
        $startup = UrlUtilities::url('/assets/images/apple-touch-startup.png');
        $manifest = UrlUtilities::url('/assets/manifest.json');

        return <<<HTML
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="lwt-base-path" content="{$basePath}" />
<meta name="theme-color" content="#3273dc" />
<link rel="shortcut icon" href="{$favicon}" type="image/x-icon"/>
<link rel="apple-touch-icon" href="{$icon57}" />
<link rel="apple-touch-icon" sizes="72x72" href="{$icon72}" />
<link rel="apple-touch-icon" sizes="114x114" href="{$icon114}" />
<link rel="apple-touch-startup-image" href="{$startup}" />
<link rel="manifest" href="{$manifest}" />
<meta name="apple-mobile-web-app-capable" content="yes" />
HTML;
    }

    /**
     * Build the page title HTML element.
     *
     * @param string $title Page title
     *
     * @return string HTML h1 element with title
     */
    public static function buildPageTitle(string $title): string
    {
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return '<h1>' . $escapedTitle . '</h1>';
    }

    /**
     * Build the document title tag content.
     *
     * @param string $title Page title
     *
     * @return string HTML title element
     */
    public static function buildDocumentTitle(string $title): string
    {
        return '<title>LWT :: ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
    }

    /**
     * Build the execution time display.
     *
     * @param float $executionTime Execution time in seconds
     *
     * @return string HTML paragraph with execution time
     */
    public static function buildExecutionTime(float $executionTime): string
    {
        return '<p class="has-text-grey is-size-7">' . round($executionTime, 5) . ' secs</p>';
    }

    // =========================================================================
    // Methods migrated from Core/UI/ui_helpers.php
    // =========================================================================

    /**
     * Render a minimal page header (kernel, no database).
     *
     * Outputs directly to browser. Sets cache control headers,
     * renders HTML5 doctype, head, and opening body.
     *
     * @param string $title Page title
     *
     * @return void
     */
    public static function renderPageStartKernelNobody(string $title): void
    {
        self::sendNoCacheHeaders();

        $favicon = UrlUtilities::url('/favicon.ico');
        $icon57 = UrlUtilities::url('/assets/images/apple-touch-icon-57x57.png');
        $icon72 = UrlUtilities::url('/assets/images/apple-touch-icon-72x72.png');
        $icon114 = UrlUtilities::url('/assets/images/apple-touch-icon-114x114.png');
        $startup = UrlUtilities::url('/assets/images/apple-touch-startup.png');

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
        echo '<!--' . "\n";
        echo file_get_contents("UNLICENSE.md");
        echo '-->';
        echo '<meta name="viewport" content="width=900" />';
        echo '<link rel="shortcut icon" href="' . $favicon . '" type="image/x-icon"/>';
        echo '<link rel="apple-touch-icon" href="' . $icon57 . '" />';
        echo '<link rel="apple-touch-icon" sizes="72x72" href="' . $icon72 . '" />';
        echo '<link rel="apple-touch-icon" sizes="114x114" href="' . $icon114 . '" />';
        echo '<link rel="apple-touch-startup-image" href="' . $startup . '" />';
        echo '<link rel="manifest" href="' . UrlUtilities::url('/assets/manifest.json') . '" />';
        echo '<meta name="theme-color" content="#3273dc" />';
        echo '<meta name="apple-mobile-web-app-capable" content="yes" />';

        if (ViteHelper::shouldUse()) {
            echo '<!-- Critical CSS for fast first paint -->';
            echo ViteHelper::criticalCss();
            echo '<!-- Vite assets (async CSS) -->';
            echo ViteHelper::assets('js/main.ts');
        } else {
            echo '<!-- Legacy assets -->';
            echo '<link rel="stylesheet" type="text/css" href="' . UrlUtilities::url('/assets/css/styles.css') . '" />';
        }

        echo '<!-- URLBASE : "' . htmlspecialchars(UrlUtilities::urlBase(), ENT_QUOTES, 'UTF-8') . '" -->';
        echo '<title>LWT :: ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '</head>';
        echo '<body>';

        flush();
    }

    /**
     * Render a full page header (no database).
     *
     * Outputs directly to browser. Sets cache control headers,
     * renders HTML5 doctype, full head with assets, and opening body.
     *
     * @param string $title     Page title
     * @param string $bodyClass Optional CSS class for body element
     *
     * @return void
     */
    public static function renderPageStartNobody(
        string $title,
        string $bodyClass = ''
    ): void {
        self::sendNoCacheHeaders();

        $favicon = UrlUtilities::url('/favicon.ico');
        $icon57 = UrlUtilities::url('/assets/images/apple-touch-icon-57x57.png');
        $icon72 = UrlUtilities::url('/assets/images/apple-touch-icon-72x72.png');
        $icon114 = UrlUtilities::url('/assets/images/apple-touch-icon-114x114.png');
        $startup = UrlUtilities::url('/assets/images/apple-touch-startup.png');

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
        echo '<!--' . "\n";
        echo file_get_contents("UNLICENSE.md");
        echo '-->';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<meta name="lwt-base-path" content="' . htmlspecialchars(UrlUtilities::getBasePath(), ENT_QUOTES, 'UTF-8') . '" />';
        echo '<link rel="shortcut icon" href="' . $favicon . '" type="image/x-icon"/>';
        echo '<link rel="apple-touch-icon" href="' . $icon57 . '" />';
        echo '<link rel="apple-touch-icon" sizes="72x72" href="' . $icon72 . '" />';
        echo '<link rel="apple-touch-icon" sizes="114x114" href="' . $icon114 . '" />';
        echo '<link rel="apple-touch-startup-image" href="' . $startup . '" />';
        echo '<link rel="manifest" href="' . UrlUtilities::url('/assets/manifest.json') . '" />';
        echo '<meta name="theme-color" content="#3273dc" />';
        echo '<meta name="apple-mobile-web-app-capable" content="yes" />';

        if (ViteHelper::shouldUse()) {
            echo '<!-- Critical CSS for fast first paint -->';
            echo ViteHelper::criticalCss();
            echo '<!-- Vite assets (async CSS) -->';
            echo ViteHelper::assets('js/main.ts');
        } else {
            echo '<!-- Legacy assets -->';
            echo '<link rel="stylesheet" type="text/css" href="' . UrlUtilities::url('/assets/css/styles.css') . '" />';
            echo '<script type="text/javascript" src="' . UrlUtilities::url('/assets/js/pgm.js') . '" charset="utf-8"></script>';
        }

        echo '<!-- URLBASE : "' . htmlspecialchars(UrlUtilities::urlBase(), ENT_QUOTES, 'UTF-8') . '" -->';
        echo '<title>LWT :: ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '</head>';
        echo '<body' . ($bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : '') . '>';

        flush();
    }

    /**
     * Render a standard page header with navigation.
     *
     * Calls renderPageStartNobody then adds navbar and page title.
     *
     * @param string $title       Page title
     * @param bool   $close       Whether to show full navigation (true) or minimal header (false)
     * @param string $currentPage Optional identifier for the current page to highlight in navbar
     *
     * @return void
     */
    public static function renderPageStart(string $title, bool $close, string $currentPage = ''): void
    {
        self::renderPageStartNobody($title);
        if ($close) {
            echo self::buildNavbar($currentPage);
        } else {
            echo '<div>';
            echo self::buildLogo();
            echo '</div>';
        }
        echo self::buildPageTitle($title);
    }

    /**
     * Render the page footer (closing body and html tags).
     *
     * @return void
     */
    public static function renderPageEnd(): void
    {
        echo '</body></html>';
    }

    /**
     * Render a frameset page header.
     *
     * Outputs directly to browser. For legacy frameset-based pages.
     *
     * @param string $title Page title
     *
     * @return void
     */
    public static function renderFramesetHeader(string $title): void
    {
        self::sendNoCacheHeaders();

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
        echo '<link rel="stylesheet" type="text/css" href="' . UrlUtilities::url('/assets/css/styles.css') . '" />';
        echo '<link rel="shortcut icon" href="' . UrlUtilities::url('/favicon.ico') . '" type="image/x-icon"/>';
        echo '<!--' . "\n";
        echo file_get_contents("UNLICENSE.md");
        echo '-->';
        echo '<title>LWT :: ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '</head>';
    }

    /**
     * Display a message (success/error) to the user.
     *
     * Renders a Bulma notification with appropriate styling. Error messages
     * (starting with "Error") are shown as danger notifications with a back button.
     * Success messages are shown as success notifications and auto-hide.
     *
     * @param string $message  The message to display
     * @param bool   $autoHide Whether to auto-hide the message (default: true)
     *
     * @return void
     */
    public static function renderMessage(string $message, bool $autoHide = true): void
    {
        if (trim($message) === '') {
            return;
        }

        $escapedMessage = \htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        $isError = str_starts_with($message, 'Error');

        if ($isError) {
            $backButton = $autoHide
                ? ''
                : '<button class="button is-small mt-2" data-action="go-back">' .
                  IconHelper::render('arrow-left', ['alt' => 'Go back']) .
                  '<span class="ml-1">Go back and correct</span></button>';

            echo '<div class="notification is-danger">' .
                '<button class="delete" aria-label="close"></button>' .
                '<strong>Error:</strong> ' . $escapedMessage .
                $backButton .
                '</div>';
        } else {
            $autoHideAttr = $autoHide ? ' data-auto-hide="true"' : '';
            echo '<div class="notification is-success"' . $autoHideAttr . '>' .
                '<button class="delete" aria-label="close"></button>' .
                $escapedMessage .
                '</div>';
        }
    }
}
