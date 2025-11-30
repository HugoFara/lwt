<?php

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

namespace Lwt\View\Helper;

require_once __DIR__ . '/../../Core/Http/url_utilities.php';

use Lwt\Core\Http\UrlUtilities;
use function Lwt\Core\Utils\showRequest;
use function Lwt\Core\Utils\get_execution_time;

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
     * Generate the quick menu dropdown HTML.
     *
     * @return string HTML select element for quick navigation
     */
    public static function buildQuickMenu(): string
    {
        return <<<'HTML'
<select id="quickmenu" data-action="quick-menu-redirect">
    <option value="" selected="selected">[Menu]</option>
    <option value="index">Home</option>
    <optgroup label="Texts">
        <option value="edit_texts">Texts</option>
        <option value="edit_archivedtexts">Text Archive</option>
        <option value="edit_texttags">Text Tags</option>
        <option value="check_text">Text Check</option>
        <option value="long_text_import">Long Text Import</option>
    </optgroup>
    <option value="edit_languages">Languages</option>
    <optgroup label="Terms">
        <option value="edit_words">Terms</option>
        <option value="edit_tags">Term Tags</option>
        <option value="upload_words">Term Import</option>
    </optgroup>
    <option value="statistics">Statistics</option>
    <option value="rss_import">Newsfeed Import</option>
    <optgroup label="Other">
        <option value="backup_restore">Backup/Restore</option>
        <option value="settings">Settings</option>
        <option value="text_to_speech_settings">Text-to-Speech Settings</option>
        <option value="INFO">Help</option>
    </optgroup>
</select>
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
        $path = function_exists('get_file_path') ? get_file_path($imagePath) : '/' . $imagePath;
        return '<img class="lwtlogo" src="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8')
            . '" title="LWT" alt="LWT logo" />';
    }

    /**
     * Generate pagination controls HTML.
     *
     * @param int    $currentPage Current page number
     * @param int    $totalPages  Total number of pages
     * @param string $scriptUrl   Base URL for pagination links
     * @param string $formName    Form name for JavaScript reference (unused, kept for BC)
     *
     * @return string HTML pagination controls
     */
    public static function buildPager(
        int $currentPage,
        int $totalPages,
        string $scriptUrl,
        string $formName
    ): string {
        $result = '';
        $margerStyle = 'style="margin-left: 4px; margin-right: 4px;"';
        $scriptUrl = htmlspecialchars($scriptUrl, ENT_QUOTES, 'UTF-8');

        // Previous page controls
        if ($currentPage > 1) {
            $result .= '<a href="' . $scriptUrl . '?page=1" ' . $margerStyle . '>';
            $result .= '<img src="/assets/icons/control-stop-180.png" title="First Page" alt="First Page" />';
            $result .= '</a>';
            $result .= '<a href="' . $scriptUrl . '?page=' . ($currentPage - 1) . '" ' . $margerStyle . '>';
            $result .= '<img src="/assets/icons/control-180.png" title="Previous Page" alt="Previous Page" />';
            $result .= '</a>';
        }

        // Page indicator
        $result .= 'Page ';
        if ($totalPages == 1) {
            $result .= '1';
        } else {
            $result .= '<select name="page" data-action="pager-navigate" data-base-url="' . $scriptUrl . '">';
            $result .= SelectOptionsBuilder::forPagination($currentPage, $totalPages);
            $result .= '</select>';
        }
        $result .= ' of ' . $totalPages . ' ';

        // Next page controls
        if ($currentPage < $totalPages) {
            $result .= '<a href="' . $scriptUrl . '?page=' . ($currentPage + 1) . '" ' . $margerStyle . '>';
            $result .= '<img src="/assets/icons/control.png" title="Next Page" alt="Next Page" />';
            $result .= '</a>';
            $result .= '<a href="' . $scriptUrl . '?page=' . $totalPages . '" ' . $margerStyle . '>';
            $result .= '<img src="/assets/icons/control-stop.png" title="Last Page" alt="Last Page" />';
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
        return <<<'HTML'
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
<link rel="apple-touch-icon" href="/assets/images/apple-touch-icon-57x57.png" />
<link rel="apple-touch-icon" sizes="72x72" href="/assets/images/apple-touch-icon-72x72.png" />
<link rel="apple-touch-icon" sizes="114x114" href="/assets/images/apple-touch-icon-114x114.png" />
<link rel="apple-touch-startup-image" href="/assets/images/apple-touch-startup.png" />
<meta name="apple-mobile-web-app-capable" content="yes" />
HTML;
    }

    /**
     * Build the page title HTML element.
     *
     * @param string $title   Page title
     * @param bool   $isDebug Whether to show debug indicator
     *
     * @return string HTML h1 element with title
     */
    public static function buildPageTitle(string $title, bool $isDebug = false): string
    {
        $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $debugSpan = $isDebug ? ' <span class="red">DEBUG</span>' : '';
        return '<h1>' . $escapedTitle . $debugSpan . '</h1>';
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
        return '<p class="smallgray2">' . round($executionTime, 5) . ' secs</p>';
    }

    /**
     * Build the overlib div used for tooltips.
     *
     * Only needed for legacy mode (non-Vite). Vite mode uses jQuery UI dialogs.
     *
     * @return string HTML div element, or empty string for Vite mode
     */
    public static function buildOverlibDiv(): string
    {
        if (ViteHelper::shouldUse()) {
            return '';
        }
        return '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>';
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
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $debug = \Lwt\Core\Globals::isDebug();

        self::sendNoCacheHeaders();

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
        echo '<!--' . "\n";
        echo file_get_contents("UNLICENSE.md");
        echo '-->';
        echo '<meta name="viewport" content="width=900" />';
        echo '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>';
        echo '<link rel="apple-touch-icon" href="/assets/images/apple-touch-icon-57x57.png" />';
        echo '<link rel="apple-touch-icon" sizes="72x72" href="/assets/images/apple-touch-icon-72x72.png" />';
        echo '<link rel="apple-touch-icon" sizes="114x114" href="/assets/images/apple-touch-icon-114x114.png" />';
        echo '<link rel="apple-touch-startup-image" href="/assets/images/apple-touch-startup.png" />';
        echo '<meta name="apple-mobile-web-app-capable" content="yes" />';

        if (ViteHelper::shouldUse()) {
            echo '<!-- Vite assets -->';
            echo '<script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>';
            echo '<script type="text/javascript" src="/assets/js/jquery-ui.min.js" charset="utf-8"></script>';
            echo ViteHelper::assets('js/main.ts');
        } else {
            echo '<!-- Legacy assets -->';
            echo '<link rel="stylesheet" type="text/css" href="/assets/css/jquery-ui.css" />';
            echo '<link rel="stylesheet" type="text/css" href="/assets/css/styles.css" />';
            echo '<script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>';
            echo '<script type="text/javascript" src="/assets/js/jquery-ui.min.js" charset="utf-8"></script>';
            echo '<!-- Legacy only: overlib (Vite uses jQuery UI dialogs) -->';
            echo '<script type="text/javascript" src="/assets/js/overlib/overlib_mini.js" charset="utf-8"></script>';
        }

        echo '<!-- URLBASE : "' . tohtml(UrlUtilities::urlBase()) . '" -->';
        echo '<!-- TBPREF  : "' . tohtml($tbpref) . '" -->';
        echo '<title>LWT :: ' . tohtml($title) . '</title>';
        echo '</head>';
        echo '<body>';

        if (!ViteHelper::shouldUse()) {
            echo '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>';
        }

        flush();
        if ($debug) {
            showRequest();
        }
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
        $tbpref = \Lwt\Core\Globals::getTablePrefix();
        $debug = \Lwt\Core\Globals::isDebug();

        self::sendNoCacheHeaders();

        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta http-equiv="content-type" content="text/html; charset=utf-8" />';
        echo '<!--' . "\n";
        echo file_get_contents("UNLICENSE.md");
        echo '-->';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>';
        echo '<link rel="apple-touch-icon" href="' . get_file_path('img/apple-touch-icon-57x57.png') . '" />';
        echo '<link rel="apple-touch-icon" sizes="72x72" href="' . get_file_path('img/apple-touch-icon-72x72.png') . '" />';
        echo '<link rel="apple-touch-icon" sizes="114x114" href="' . get_file_path('img/apple-touch-icon-114x114.png') . '" />';
        echo '<link rel="apple-touch-startup-image" href="/assets/images/apple-touch-startup.png" />';
        echo '<meta name="apple-mobile-web-app-capable" content="yes" />';

        if (ViteHelper::shouldUse()) {
            echo '<!-- Vite assets -->';
            echo '<!-- Load jQuery synchronously for inline scripts compatibility -->';
            echo '<script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>';
            echo '<script type="text/javascript" src="/assets/js/jquery-ui.min.js" charset="utf-8"></script>';
            echo ViteHelper::assets('js/main.ts');
        } else {
            echo '<!-- Legacy assets -->';
            echo '<link rel="stylesheet" type="text/css" href="' . get_file_path('css/jquery-ui.css') . '" />';
            echo '<link rel="stylesheet" type="text/css" href="' . get_file_path('css/styles.css') . '" />';
            echo '<script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>';
            echo '<script type="text/javascript" src="/assets/js/jquery-ui.min.js" charset="utf-8"></script>';
            echo '<!-- Legacy only: overlib (Vite uses jQuery UI dialogs) -->';
            echo '<script type="text/javascript" src="/assets/js/overlib/overlib_mini.js" charset="utf-8"></script>';
            echo '<script type="text/javascript" src="/assets/js/pgm.js" charset="utf-8"></script>';
        }

        echo '<!-- URLBASE : "' . tohtml(UrlUtilities::urlBase()) . '" -->';
        echo '<!-- TBPREF  : "' . tohtml($tbpref) . '" -->';
        echo '<title>LWT :: ' . tohtml($title) . '</title>';
        echo '</head>';
        echo '<body' . ($bodyClass !== '' ? ' class="' . tohtml($bodyClass) . '"' : '') . '>';

        if (!ViteHelper::shouldUse()) {
            echo '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>';
        }

        flush();
        if ($debug) {
            showRequest();
        }
    }

    /**
     * Render a standard page header with navigation.
     *
     * Calls renderPageStartNobody then adds logo and navigation menu.
     *
     * @param string $title Page title
     * @param bool   $close Whether to wrap logo in link to index
     *
     * @return void
     */
    public static function renderPageStart(string $title, bool $close): void
    {
        self::renderPageStartNobody($title);
        echo '<div>';
        if ($close) {
            echo '<a href="index.php" target="_top">';
        }
        echo self::buildLogo();
        if ($close) {
            echo '</a>';
            echo self::buildQuickMenu();
        }
        echo '</div>';
        echo self::buildPageTitle($title, \Lwt\Core\Globals::isDebug());
    }

    /**
     * Render the page footer (closing body and html tags).
     *
     * Outputs directly to browser. Shows debug info and execution time if configured.
     *
     * @return void
     */
    public static function renderPageEnd(): void
    {
        if (\Lwt\Core\Globals::isDebug()) {
            showRequest();
        }
        if (\Lwt\Core\Globals::shouldDisplayTime()) {
            echo "\n" . self::buildExecutionTime(get_execution_time()) . "\n";
        }
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
        echo '<link rel="stylesheet" type="text/css" href="' . get_file_path('css/styles.css') . '" />';
        echo '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>';
        echo '<!--' . "\n";
        echo file_get_contents("UNLICENSE.md");
        echo '-->';
        echo '<title>LWT :: ' . tohtml($title) . '</title>';
        echo '</head>';
    }

    /**
     * Display a message (success/error) to the user.
     *
     * Renders a message with appropriate styling. Error messages
     * (starting with "Error") are shown in red with a back button.
     * Success messages are shown in blue and auto-hide.
     *
     * @param string $message  The message to display
     * @param bool   $autoHide Whether to auto-hide the message (default: true)
     *
     * @return void
     */
    public static function renderMessage(string $message, bool $autoHide = true): void
    {
        if (trim($message) == '') {
            return;
        }
        if (substr($message, 0, 5) == "Error") {
            echo '<p class="red">*** ' . \tohtml($message) . ' ***' .
                ($autoHide ?
                '' :
                '<br /><input type="button" value="&lt;&lt; Go back and correct &lt;&lt;" data-action="go-back" />' ) .
                '</p>';
        } else {
            echo '<p id="hide3" class="msgblue">+++ ' . \tohtml($message) . ' +++</p>';
        }
    }
}
