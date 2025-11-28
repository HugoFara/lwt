<?php

/**
 * \file
 * \brief UI helper functions - Backward compatibility shim.
 *
 * This file provides backward compatibility for code that still uses
 * the legacy function names. All functions delegate to the new View Helper classes.
 *
 * DEPRECATED: New code should use the View Helper classes directly:
 * - \Lwt\View\Helper\FormHelper
 * - \Lwt\View\Helper\SelectOptionsBuilder
 * - \Lwt\View\Helper\PageLayoutHelper
 * - \Lwt\View\Helper\StatusHelper
 * - \Lwt\View\Helper\ViteHelper
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   3.0.0 Split from session_utility.php
 * @deprecated 3.0.0 Use View Helper classes instead
 */

use Lwt\Database\Connection;
use Lwt\Services\LanguageService;
use Lwt\Services\TextService;
use Lwt\Services\ThemeService;
use Lwt\View\Helper\FormHelper;
use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\StatusHelper;
use Lwt\View\Helper\ViteHelper;

require_once __DIR__ . '/../Http/url_utilities.php';
require_once __DIR__ . '/../../Services/WordStatusService.php';
require_once __DIR__ . '/../../Services/LanguageService.php';
require_once __DIR__ . '/../../Services/TextService.php';
require_once __DIR__ . '/../../Services/ThemeService.php';
require_once __DIR__ . '/../../View/Helper/FormHelper.php';
require_once __DIR__ . '/../../View/Helper/SelectOptionsBuilder.php';
require_once __DIR__ . '/../../View/Helper/PageLayoutHelper.php';
require_once __DIR__ . '/../../View/Helper/StatusHelper.php';
require_once __DIR__ . '/../../View/Helper/ViteHelper.php';

// =========================================================================
// Vite Helper Functions (backward compatibility)
// =========================================================================

/**
 * @deprecated 3.0.0 Use ViteHelper::isDevServerRunning() instead
 */
function is_vite_dev_server_running(): bool
{
    return ViteHelper::isDevServerRunning();
}

/**
 * @deprecated 3.0.0 Use ViteHelper::getManifest() instead
 */
function get_vite_manifest(): ?array
{
    return ViteHelper::getManifest();
}

/**
 * @deprecated 3.0.0 Use ViteHelper::assets() instead
 */
function vite_assets(string $entry = 'js/main.ts'): string
{
    return ViteHelper::assets($entry);
}

/**
 * @deprecated 3.0.0 Use ViteHelper::shouldUse() instead
 */
function should_use_vite(): bool
{
    return ViteHelper::shouldUse();
}

// =========================================================================
// Page Layout Functions (backward compatibility)
// =========================================================================

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::buildQuickMenu() instead
 */
function quickMenu(): void
{
    echo PageLayoutHelper::buildQuickMenu();
}

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::renderPageStartKernelNobody() instead
 */
function pagestart_kernel_nobody($title, $addcss = ''): void
{
    PageLayoutHelper::renderPageStartKernelNobody($title, $addcss);
}

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::renderPageStartNobody() instead
 */
function pagestart_nobody($title, $addcss = ''): void
{
    PageLayoutHelper::renderPageStartNobody($title, $addcss);
}

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::renderPageStart() instead
 */
function pagestart($title, $close): void
{
    PageLayoutHelper::renderPageStart($title, $close);
}

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::renderPageEnd() instead
 */
function pageend(): void
{
    PageLayoutHelper::renderPageEnd();
}

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::buildLogo() instead
 */
function echo_lwt_logo(): void
{
    echo PageLayoutHelper::buildLogo();
}

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::renderFramesetHeader() instead
 */
function framesetheader($title): void
{
    PageLayoutHelper::renderFramesetHeader($title);
}

// =========================================================================
// Form Helper Functions (backward compatibility)
// =========================================================================

/**
 * @deprecated 3.0.0 Use FormHelper::getChecked() instead
 */
function get_checked($value): string
{
    return FormHelper::getChecked($value);
}

/**
 * @deprecated 3.0.0 Use FormHelper::getSelected() instead
 */
function get_selected(int|string|null $value, int|string $selval): string
{
    return FormHelper::getSelected($value, $selval);
}

/**
 * @deprecated 3.0.0 Use FormHelper::checkInRequest() instead
 */
function checkTest($val, $name): string
{
    return FormHelper::checkInRequest($val, $name);
}

// =========================================================================
// Select Options Functions (backward compatibility)
// =========================================================================

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forSeconds() instead
 */
function get_seconds_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forSeconds($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forPlaybackRate() instead
 */
function get_playbackrate_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forPlaybackRate($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forMobileDisplayMode() instead
 */
function get_mobile_display_mode_selectoptions($v): string
{
    return SelectOptionsBuilder::forMobileDisplayMode($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forSentenceCount() instead
 */
function get_sentence_count_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forSentenceCount($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forWordsToDoButtons() instead
 */
function get_words_to_do_buttons_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forWordsToDoButtons($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forRegexMode() instead
 */
function get_regex_selectoptions(string|null $v): string
{
    return SelectOptionsBuilder::forRegexMode($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forTooltipType() instead
 */
function get_tooltip_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forTooltipType($v);
}

/**
 * @deprecated 3.0.0 Use ThemeService + SelectOptionsBuilder::forThemes() instead
 */
function get_themes_selectoptions(string|null $v): string
{
    static $themeService = null;
    if ($themeService === null) {
        $themeService = new ThemeService();
    }
    $themes = $themeService->getAvailableThemes();
    return SelectOptionsBuilder::forThemes($themes, $v);
}

/**
 * @deprecated 3.0.0 Use LanguageService + SelectOptionsBuilder::forLanguages() instead
 */
function get_languages_selectoptions($v, $dt): string
{
    static $languageService = null;
    if ($languageService === null) {
        $languageService = new LanguageService();
    }
    $languages = $languageService->getLanguagesForSelect();
    return SelectOptionsBuilder::forLanguages($languages, $v, $dt);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forLanguageSize() instead
 */
function get_languagessize_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forLanguageSize($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forWordStatusRadio() instead
 */
function get_wordstatus_radiooptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forWordStatusRadio($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forWordStatus() instead
 */
function get_wordstatus_selectoptions(
    int|string|null $v,
    bool $all,
    bool $not9899,
    bool $off = true
): string {
    return SelectOptionsBuilder::forWordStatus($v, $all, $not9899, $off);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forAnnotationPosition() instead
 */
function get_annotation_position_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forAnnotationPosition($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forHoverTranslation() instead
 */
function get_hts_selectoptions(int|string|null $current_setting): string
{
    return SelectOptionsBuilder::forHoverTranslation($current_setting);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forPagination() instead
 */
function get_paging_selectoptions(int $currentpage, int $pages): string
{
    return SelectOptionsBuilder::forPagination($currentpage, $pages);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forWordSort() instead
 */
function get_wordssort_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forWordSort($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forTagSort() instead
 */
function get_tagsort_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forTagSort($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forTextSort() instead
 */
function get_textssort_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forTextSort($v);
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forAndOr() instead
 */
function get_andor_selectoptions(int|string|null $v): string
{
    return SelectOptionsBuilder::forAndOr($v);
}

/**
 * @deprecated 3.0.0 Use TextService + SelectOptionsBuilder::forTexts() instead
 */
function get_texts_selectoptions(int|string|null $lang, int|string|null $v): string
{
    static $textService = null;
    if ($textService === null) {
        $textService = new TextService();
    }
    $langId = ($lang !== null && $lang !== '') ? (int)$lang : null;
    $texts = $textService->getTextsForSelect($langId);
    return SelectOptionsBuilder::forTexts($texts, $v, $langId === null);
}

// =========================================================================
// Status Helper Functions (backward compatibility)
// =========================================================================

/**
 * @deprecated 3.0.0 Use StatusHelper::getName() instead
 */
function get_status_name(int $n): string
{
    return StatusHelper::getName($n);
}

/**
 * @deprecated 3.0.0 Use StatusHelper::getAbbr() instead
 */
function get_status_abbr(int $n): string
{
    return StatusHelper::getAbbr($n);
}

/**
 * @deprecated 3.0.0 Use StatusHelper::buildSetStatusOption() instead
 */
function get_set_status_option(int $n, string $suffix = ""): string
{
    return StatusHelper::buildSetStatusOption($n, StatusHelper::getName($n), StatusHelper::getAbbr($n), $suffix);
}

/**
 * @deprecated 3.0.0 Use StatusHelper::buildColoredMessage() instead
 */
function get_colored_status_msg(int $n): string
{
    return StatusHelper::buildColoredMessage($n, StatusHelper::getName($n), StatusHelper::getAbbr($n));
}

/**
 * @deprecated 3.0.0 Use StatusHelper::makeCondition() instead
 */
function makeStatusCondition(string $fieldname, int $statusrange): string
{
    return StatusHelper::makeCondition($fieldname, $statusrange);
}

/**
 * @deprecated 3.0.0 Use StatusHelper::checkRange() instead
 */
function checkStatusRange(int $currstatus, int $statusrange): bool
{
    return StatusHelper::checkRange($currstatus, $statusrange);
}

/**
 * @deprecated 3.0.0 Use StatusHelper::makeClassFilter() instead
 */
function makeStatusClassFilter($status)
{
    return StatusHelper::makeClassFilter($status);
}

/**
 * @deprecated 3.0.0 This is an internal helper, no longer needed
 */
function makeStatusClassFilterHelper($status, &$array): void
{
    $pos = array_search($status, $array);
    if ($pos !== false) {
        $array[$pos] = -1;
    }
}

/**
 * @deprecated 3.0.0 Use StatusHelper::buildTestTableControls() instead
 */
function make_status_controls_test_table($score, $status, $wordid): string
{
    $placeholder = get_file_path('assets/icons/placeholder.png');
    return StatusHelper::buildTestTableControls(
        $score,
        $status,
        $wordid,
        StatusHelper::getAbbr($status),
        $placeholder
    );
}

// =========================================================================
// Action Menu Functions (backward compatibility)
// =========================================================================

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forMultipleWordsActions() instead
 */
function get_multiplewordsactions_selectoptions(): string
{
    return SelectOptionsBuilder::forMultipleWordsActions();
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forMultipleTagsActions() instead
 */
function get_multipletagsactions_selectoptions(): string
{
    return SelectOptionsBuilder::forMultipleTagsActions();
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forAllWordsActions() instead
 */
function get_allwordsactions_selectoptions(): string
{
    return SelectOptionsBuilder::forAllWordsActions();
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forAllTagsActions() instead
 */
function get_alltagsactions_selectoptions(): string
{
    return SelectOptionsBuilder::forAllTagsActions();
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forMultipleTextsActions() instead
 */
function get_multipletextactions_selectoptions(): string
{
    return SelectOptionsBuilder::forMultipleTextsActions();
}

/**
 * @deprecated 3.0.0 Use SelectOptionsBuilder::forMultipleArchivedTextsActions() instead
 */
function get_multiplearchivedtextactions_selectoptions(): string
{
    return SelectOptionsBuilder::forMultipleArchivedTextsActions();
}

// =========================================================================
// Pagination Functions (backward compatibility)
// =========================================================================

/**
 * @deprecated 3.0.0 Use PageLayoutHelper::buildPager() instead
 */
function makePager(int $currentpage, int $pages, string $script, string $formname): void
{
    echo PageLayoutHelper::buildPager($currentpage, $pages, $script, $formname);
}
