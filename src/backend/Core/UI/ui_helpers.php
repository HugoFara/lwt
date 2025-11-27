<?php

/**
 * \file
 * \brief UI helper functions for generating HTML elements.
 *
 * This file contains functions for generating HTML select options,
 * form elements, page headers, and other UI components.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-session-utility.html
 * @since   3.0.0 Split from session_utility.php
 */

require_once __DIR__ . '/vite_helper.php';
require_once __DIR__ . '/../Word/word_status.php';

/**
 * Display the main menu of navigation as a dropdown
 */
function quickMenu(): void
{
    ?>

<select id="quickmenu" onchange="quickMenuRedirection(value)">
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
    <?php
}

/**
 * Start a page without connecting to the database with a complete header and a non-closed body.
 *
 * @param string $title  Title of the page
 * @param string $addcss Some CSS to be embed in a style tag
 */
function pagestart_kernel_nobody($title, $addcss = ''): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $debug = \Lwt\Core\Globals::isDebug();
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    ?><!DOCTYPE html>
    <?php
    echo '<html lang="en">';
    ?>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <!--
        <?php echo file_get_contents("UNLICENSE.md");?>
    -->
    <meta name="viewport" content="width=900" />
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
    <link rel="apple-touch-icon" href="/assets/images/apple-touch-icon-57x57.png" />
    <link rel="apple-touch-icon" sizes="72x72" href="/assets/images/apple-touch-icon-72x72.png" />
    <link rel="apple-touch-icon" sizes="114x114" href="/assets/images/apple-touch-icon-114x114.png" />
    <link rel="apple-touch-startup-image" href="/assets/images/apple-touch-startup.png" />
    <meta name="apple-mobile-web-app-capable" content="yes" />

    <link rel="stylesheet" type="text/css" href="/assets/css/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/jquery.tagit.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css" />
    <link rel="stylesheet" type="text/css" href="/assets/css/feed_wizard.css" />
    <style type="text/css">
        <?php echo $addcss . "\n"; ?>
    </style>

    <script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery.scrollTo.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery-ui.min.js"  charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery.jeditable.mini.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/tag-it.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/overlib/overlib_mini.js" charset="utf-8"></script>
    <!-- URLBASE : "<?php echo tohtml(url_base()); ?>" -->
    <!-- TBPREF  : "<?php echo tohtml($tbpref);  ?>" -->
    <script type="text/javascript">
        //<![CDATA[
        var STATUSES = <?php echo json_encode(get_statuses()); ?>;
        //]]>
    </script>

    <title>LWT :: <?php echo tohtml($title); ?></title>
</head>
    <?php
    echo '<body>';
    ?>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
    <?php
    flush();
    if ($debug) {
        showRequest();
    }
}

/**
 * Add a closing body tag.
 *
 * @global bool $debug Show the requests if true
 * @global float $dspltime Total execution time since the PHP session started
 */
function pageend(): void
{
    if (\Lwt\Core\Globals::isDebug()) {
        showRequest();
    }
    if (\Lwt\Core\Globals::shouldDisplayTime()) {
        echo "\n<p class=\"smallgray2\">" .
        round(get_execution_time(), 5) . " secs</p>\n";
    }
    echo '</body></html>';
}

/**
 * Return an HTML formatted logo of the application.
 *
 * @since 2.7.0 Do no longer indicate database prefix in logo
 */
function echo_lwt_logo(): void
{
    echo '<img class="lwtlogo" src="' . get_file_path('assets/images/lwt_icon.png') . '" title="LWT" alt="LWT logo" />';
}

// -------------------------------------------------------------

function get_seconds_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = 5;
    }
    $r = '';
    for ($i = 1; $i <= 10; $i++) {
        $r .= "<option value=\"" . $i . "\"" . get_selected($v, $i);
        $r .= ">" . $i . " sec</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_playbackrate_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = '10';
    }
    $r = '';
    for ($i = 5; $i <= 15; $i++) {
        $text = ($i < 10 ? (' 0.' . $i . ' x ') : (' 1.' . ($i - 10) . ' x ') );
        $r .= "<option value=\"" . $i . "\"" . get_selected($v, $i);
        $r .= ">&nbsp;" . $text . "&nbsp;</option>";
    }
    return $r;
}

/**
 * Prepare options for mobile.
 *
 * @param "0"|"1"|"2" $v Current mobile type
 */
function get_mobile_display_mode_selectoptions($v): string
{
    if (!isset($v)) {
        $v = "0";
    }
    $r  = "<option value=\"0\"" . get_selected($v, "0");
    $r .= ">Auto</option>";
    $r .= "<option value=\"1\"" . get_selected($v, "1");
    $r .= ">Force Non-Mobile</option>";
    $r .= "<option value=\"2\"" . get_selected($v, "2");
    $r .= ">Force Mobile</option>";
    return $r;
}

// -------------------------------------------------------------

function get_sentence_count_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = 1;
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Just ONE</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">TWO (+previous)</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">THREE (+previous,+next)</option>";
    return $r;
}

// -------------------------------------------------------------

function get_words_to_do_buttons_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = "1";
    }
    $r  = "<option value=\"0\"" . get_selected($v, "0");
    $r .= ">I Know All &amp; Ignore All</option>";
    $r .= "<option value=\"1\"" . get_selected($v, "1");
    $r .= ">I Know All</option>";
    $r .= "<option value=\"2\"" . get_selected($v, "2");
    $r .= ">Ignore All</option>";
    return $r;
}

// -------------------------------------------------------------

function get_regex_selectoptions(string|null $v): string
{
    if (!isset($v)) {
        $v = "";
    }
    $r  = "<option value=\"\"" . get_selected($v, "");
    $r .= ">Default</option>";
    $r .= "<option value=\"r\"" . get_selected($v, "r");
    $r .= ">RegEx</option>";
    $r .= "<option value=\"COLLATE 'utf8_bin' r\"" . get_selected($v, "COLLATE 'utf8_bin' r");
    $r .= ">RegEx CaseSensitive</option>";
    return $r;
}

// -------------------------------------------------------------

function get_tooltip_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = 1;
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Native</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">JqueryUI</option>";
    return $r;
}

// -------------------------------------------------------------

function get_themes_selectoptions(string|null $v): string
{
    $themes = glob('assets/themes/*', GLOB_ONLYDIR);
    $r = '<option value="assets/themes/Default/">Default</option>';
    foreach ($themes as $theme) {
        if ($theme != 'assets/themes/Default') {
            $r .= '<option value="' . $theme . '/" ' . get_selected($v, $theme . '/');
            $r .= ">" . str_replace(array('assets/themes/','_'), array('',' '), $theme) . "</option>";
        }
    }
    return $r;
}

/**
 * If $value is true, return an HTML-style checked attribute.
 *
 * @param mixed $value Some value that can be evaluated as a boolean
 *
 * @return string ' checked="checked" ' if value is true, '' otherwise
 *
 * @psalm-return ' checked="checked" '|''
 */
function get_checked($value): string
{
    if (!isset($value)) {
        return '';
    }
    if ($value) {
        return ' checked="checked" ';
    }
    return '';
}

/**
 * Return an HTML attribute if $value is equal to $selval.
 *
 * @return string ''|' selected="selected" ' Depending if inputs are equal
 *
 * @psalm-return ' selected="selected" '|''
 */
function get_selected(int|string|null $value, int|string $selval): string
{
    if (!isset($value)) {
        return '';
    }
    if ($value == $selval) {
        return ' selected="selected" ';
    }
    return '';
}

/**
 * Return options as HTML code to insert in a language select.
 *
 * @param string|int|null $v  Selected language ID
 * @param string          $dt Default value to display
 */
function get_languages_selectoptions($v, $dt): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $sql = "SELECT LgID, LgName FROM {$tbpref}languages
    WHERE LgName<>'' ORDER BY LgName";
    $res = do_mysqli_query($sql);
    $r = '<option value="" ';
    if (!isset($v) || trim((string) $v) == '') {
        $r .= 'selected="selected"';
    }
    $r .= ">$dt</option>";
    while ($record = mysqli_fetch_assoc($res)) {
        $d = (string) $record["LgName"];
        if (strlen($d) > 30) {
            $d = substr($d, 0, 30) . "...";
        }
        $r .= "<option value=\"" . $record["LgID"] . "\" " .
        get_selected($v, (int)$record["LgID"]) . ">" . tohtml($d) . "</option>";
    }
    mysqli_free_result($res);
    return $r;
}

// -------------------------------------------------------------

function get_languagessize_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = 100;
    }
    $r = "<option value=\"100\"" . get_selected($v, 100);
    $r .= ">100 %</option>";
    $r .= "<option value=\"150\"" . get_selected($v, 150);
    $r .= ">150 %</option>";
    $r .= "<option value=\"200\"" . get_selected($v, 200);
    $r .= ">200 %</option>";
    $r .= "<option value=\"250\"" . get_selected($v, 250);
    $r .= ">250 %</option>";
    return $r;
}

// -------------------------------------------------------------

function get_wordstatus_radiooptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = 1;
    }
    $r = "";
    $statuses = get_statuses();
    foreach ($statuses as $n => $status) {
        $r .= '<span class="status' . $n . '" title="' . tohtml($status["name"]) . '">';
        $r .= '&nbsp;<input type="radio" name="WoStatus" value="' . $n . '"';
        if ($v == $n) {
            $r .= ' checked="checked"';
        }
        $r .= ' />' . tohtml($status["abbr"]) . "&nbsp;</span> ";
    }
    return $r;
}

// -------------------------------------------------------------

function get_wordstatus_selectoptions(
    int|string|null $v,
    bool $all,
    bool $not9899,
    bool $off = true
): string {
    if (!isset($v)) {
        if ($all) {
            $v = "";
        } else {
            $v = 1;
        }
    }
    $r = "";
    if ($all && $off) {
        $r .= "<option value=\"\"" . get_selected($v, '');
        $r .= ">[Filter off]</option>";
    }
    $statuses = get_statuses();
    foreach ($statuses as $n => $status) {
        if ($not9899 && ($n == 98 || $n == 99)) {
            continue;
        }
        $r .= "<option value =\"" . $n . "\"" . get_selected($v, $n != 0 ? $n : '0');
        $r .= ">" . tohtml($status['name']) . " [" .
        tohtml($status['abbr']) . "]</option>";
    }
    if ($all) {
        $r .= '<option disabled="disabled">--------</option>';
        $status_1_name = tohtml($statuses[1]["name"]);
        $status_1_abbr = tohtml($statuses[1]["abbr"]);
        $r .= "<option value=\"12\"" . get_selected($v, 12);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." .
        tohtml($statuses[2]["abbr"]) . "]</option>";
        $r .= "<option value=\"13\"" . get_selected($v, 13);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." .
        tohtml($statuses[3]["abbr"]) . "]</option>";
        $r .= "<option value=\"14\"" . get_selected($v, 14);
        $r .= ">" . $status_1_name . " [" . $status_1_abbr . ".." .
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"15\"" . get_selected($v, 15);
        $r .= ">Learning/-ed [" . $status_1_abbr . ".." .
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $status_2_name = tohtml($statuses[2]["name"]);
        $status_2_abbr = tohtml($statuses[2]["abbr"]);
        $r .= "<option value=\"23\"" . get_selected($v, 23);
        $r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." .
        tohtml($statuses[3]["abbr"]) . "]</option>";
        $r .= "<option value=\"24\"" . get_selected($v, 24);
        $r .= ">" . $status_2_name . " [" . $status_2_abbr . ".." .
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"25\"" . get_selected($v, 25);
        $r .= ">Learning/-ed [" . $status_2_abbr . ".." .
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $status_3_name = tohtml($statuses[3]["name"]);
        $status_3_abbr = tohtml($statuses[3]["abbr"]);
        $r .= "<option value=\"34\"" . get_selected($v, 34);
        $r .= ">" . $status_3_name . " [" . $status_3_abbr . ".." .
        tohtml($statuses[4]["abbr"]) . "]</option>";
        $r .= "<option value=\"35\"" . get_selected($v, 35);
        $r .= ">Learning/-ed [" . $status_3_abbr . ".." .
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $r .= "<option value=\"45\"" . get_selected($v, 45);
        $r .= ">Learning/-ed [" .  tohtml($statuses[4]["abbr"]) . ".." .
        tohtml($statuses[5]["abbr"]) . "]</option>";
        $r .= '<option disabled="disabled">--------</option>';
        $r .= "<option value=\"599\"" . get_selected($v, 599);
        $r .= ">All known [" . tohtml($statuses[5]["abbr"]) . "+" .
        tohtml($statuses[99]["abbr"]) . "]</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_annotation_position_selectoptions(int|string|null $v): string
{
    if (! isset($v)) {
        $v = 1;
    }
    $r = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Behind</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">In Front Of</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Below</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Above</option>";
    return $r;
}
// -------------------------------------------------------------

function get_hts_selectoptions(int|string|null $current_setting): string
{
    if (!isset($current_setting)) {
        $current_setting = 1;
    }
    $options = array(
        1 => "Never",
        2 => "On Click",
        3 => "On Hover"
    );
    $r = "";
    foreach ($options as $key => $value) {
        $r .= sprintf(
            '<option value="%d"%s>%s</option>',
            $key,
            get_selected($current_setting, $key),
            $value
        );
    }
    return $r;
}

// -------------------------------------------------------------

function get_paging_selectoptions(int $currentpage, int $pages): string
{
    $r = "";
    for ($i = 1; $i <= $pages; $i++) {
        $r .= "<option value=\"" . $i . "\"" . get_selected($i, $currentpage);
        $r .= ">$i</option>";
    }
    return $r;
}

// -------------------------------------------------------------

function get_wordssort_selectoptions(int|string|null $v): string
{
    if (! isset($v)) {
        $v = 1;
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Term A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Translation A-Z</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Newest first</option>";
    $r .= "<option value=\"7\"" . get_selected($v, 7);
    $r .= ">Oldest first</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Oldest first</option>";
    $r .= "<option value=\"5\"" . get_selected($v, 5);
    $r .= ">Status</option>";
    $r .= "<option value=\"6\"" . get_selected($v, 6);
    $r .= ">Score Value (%)</option>";
    $r .= "<option value=\"7\"" . get_selected($v, 7);
    $r .= ">Word Count Active Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_tagsort_selectoptions(int|string|null $v): string
{
    if (! isset($v)) {
        $v = 1;
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Tag Text A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Tag Comment A-Z</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Newest first</option>";
    $r .= "<option value=\"4\"" . get_selected($v, 4);
    $r .= ">Oldest first</option>";
    return $r;
}

// -------------------------------------------------------------

function get_textssort_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = 1;
    }
    $r  = "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">Title A-Z</option>";
    $r .= "<option value=\"2\"" . get_selected($v, 2);
    $r .= ">Newest first</option>";
    $r .= "<option value=\"3\"" . get_selected($v, 3);
    $r .= ">Oldest first</option>";
    return $r;
}


// -------------------------------------------------------------

function get_andor_selectoptions(int|string|null $v): string
{
    if (!isset($v)) {
        $v = 0;
    }
    $r  = "<option value=\"0\"" . get_selected($v, 0);
    $r .= ">... OR ...</option>";
    $r .= "<option value=\"1\"" . get_selected($v, 1);
    $r .= ">... AND ...</option>";
    return $r;
}

// -------------------------------------------------------------

function get_set_status_option(int $n, string $suffix = ""): string
{
    return "<option value=\"s" . $n . $suffix . "\">Set Status to " .
    tohtml(get_status_name($n)) . " [" . tohtml(get_status_abbr($n)) .
    "]</option>";
}

// -------------------------------------------------------------

function get_status_name(int $n): string
{
    $statuses = get_statuses();
    return $statuses[$n]["name"];
}

// -------------------------------------------------------------

function get_status_abbr(int $n): string
{
    $statuses = get_statuses();
    return $statuses[$n]["abbr"];
}

// -------------------------------------------------------------

function get_colored_status_msg(int $n): string
{
    return '<span class="status' . $n . '">&nbsp;' . tohtml(get_status_name($n)) .
    '&nbsp;[' . tohtml(get_status_abbr($n)) . ']&nbsp;</span>';
}

// -------------------------------------------------------------

function get_multiplewordsactions_selectoptions(): string
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"test\">Test Marked Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"spl1\">Increase Status by 1 [+1]</option>";
    $r .= "<option value=\"smi1\">Reduce Status by 1 [-1]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= get_set_status_option(1);
    $r .= get_set_status_option(5);
    $r .= get_set_status_option(99);
    $r .= get_set_status_option(98);
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"today\">Set Status Date to Today</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"lower\">Set Marked Terms to Lowercase</option>";
    $r .= "<option value=\"cap\">Capitalize Marked Terms</option>";
    $r .= "<option value=\"delsent\">Delete Sentences of Marked Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"exp\">Export Marked Terms (Anki)</option>";
    $r .= "<option value=\"exp2\">Export Marked Terms (TSV)</option>";
    $r .= "<option value=\"exp3\">Export Marked Terms (Flexible)</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Terms</option>";
    return $r;
}

// -------------------------------------------------------------

function get_multipletagsactions_selectoptions(): string
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option value=\"del\">Delete Marked Tags</option>";
    return $r;
}

// -------------------------------------------------------------

function get_allwordsactions_selectoptions(): string
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"testall\">Test ALL Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"spl1all\">Increase Status by 1 [+1]</option>";
    $r .= "<option value=\"smi1all\">Reduce Status by 1 [-1]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= get_set_status_option(1, "all");
    $r .= get_set_status_option(5, "all");
    $r .= get_set_status_option(99, "all");
    $r .= get_set_status_option(98, "all");
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"todayall\">Set Status Date to Today</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"lowerall\">Set ALL Terms to Lowercase</option>";
    $r .= "<option value=\"capall\">Capitalize ALL Terms</option>";
    $r .= "<option value=\"delsentall\">Delete Sentences of ALL Terms</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtagall\">Add Tag</option>";
    $r .= "<option value=\"deltagall\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"expall\">Export ALL Terms (Anki)</option>";
    $r .= "<option value=\"expall2\">Export ALL Terms (TSV)</option>";
    $r .= "<option value=\"expall3\">Export ALL Terms (Flexible)</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"delall\">Delete ALL Terms</option>";
    return $r;
}

// -------------------------------------------------------------

function get_alltagsactions_selectoptions(): string
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option value=\"delall\">Delete ALL Tags</option>";
    return $r;
}

/// Returns options for an HTML dropdown to choose a text along a criterion
function get_multipletextactions_selectoptions(): string
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"test\">Test Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"rebuild\">Reparse Texts</option>";
    $r .= "<option value=\"setsent\">Set Term Sentences</option>";
    $r .= "<option value=\"setactsent\">Set Active Term Sentences</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"arch\">Archive Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Texts</option>";
    return $r;
}

// -------------------------------------------------------------

function get_multiplearchivedtextactions_selectoptions(): string
{
    $r = "<option value=\"\" selected=\"selected\">[Choose...]</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"addtag\">Add Tag</option>";
    $r .= "<option value=\"deltag\">Remove Tag</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"unarch\">Unarchive Marked Texts</option>";
    $r .= "<option disabled=\"disabled\">------------</option>";
    $r .= "<option value=\"del\">Delete Marked Texts</option>";
    return $r;
}

// -------------------------------------------------------------

/**
 * @psalm-suppress UnusedParam Parameters are used after null coalescing
 */
function get_texts_selectoptions(int|string|null $lang, int|string|null $v): string
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    if (! isset($v)) {
        $v = '';
    }
    if (! isset($lang)) {
        $lang = '';
    }
    if ($lang == "") {
        $l = "";
    } else {
        $l = "and TxLgID=" . $lang;
    }
    $r = "<option value=\"\"" . get_selected($v, '');
    $r .= ">[Filter off]</option>";
    $sql = "select TxID, TxTitle, LgName
    from " . $tbpref . "languages, " . $tbpref . "texts
    where LgID = TxLgID " . $l . "
    order by LgName, TxTitle";
    $res = do_mysqli_query($sql);
    while ($record = mysqli_fetch_assoc($res)) {
        $d = (string) $record["TxTitle"];
        if (mb_strlen($d, 'UTF-8') > 30) {
            $d = mb_substr($d, 0, 30, 'UTF-8') . "...";
        }
        $r .= "<option value=\"" . $record["TxID"] . "\"" .
        get_selected($v, (int)$record["TxID"]) . ">" . tohtml(($lang != "" ? "" : ($record["LgName"] . ": ")) . $d) . "</option>";
    }
    mysqli_free_result($res);
    return $r;
}


/**
 * Makes HTML content for a text of style "Page 1 of 3".
 *
 * @return void
 */
function makePager(int $currentpage, int $pages, string $script, string $formname): void
{
    $marger = 'style="margin-left: 4px; margin-right: 4px;"';
    if ($currentpage > 1) {
        ?>
<a href="<?php echo $script; ?>?page=1" <?php echo $marger; ?>>
    <img src="/assets/icons/control-stop-180.png" title="First Page" alt="First Page" />
</a>
<a href="<?php echo $script; ?>?page=<?php echo $currentpage - 1; ?>" <?php echo $marger; ?>>
    <img src="/assets/icons/control-180.png" title="Previous Page" alt="Previous Page" />
</a>
        <?php
    }
    ?>
Page
    <?php
    if ($pages == 1) {
        echo '1';
    } else {
        ?>
<select name="page" onchange="{val=document.<?php echo $formname; ?>.page.options[document.<?php echo $formname; ?>.page.selectedIndex].value; location.href='<?php echo $script; ?>?page=' + val;}">
        <?php echo get_paging_selectoptions($currentpage, $pages); ?>
</select>
        <?php
    }
    echo ' of ' . $pages . ' ';
    if ($currentpage < $pages) {
        ?>
<a href="<?php echo $script; ?>?page=<?php echo $currentpage + 1; ?>" <?php echo $marger; ?>>
    <img src="/assets/icons/control.png" title="Next Page" alt="Next Page" />
</a>
<a href="<?php echo $script; ?>?page=<?php echo $pages; ?>" <?php echo $marger; ?>>
    <img src="/assets/icons/control-stop.png" title="Last Page" alt="Last Page" />
</a>
        <?php
    }
}

// -------------------------------------------------------------

function makeStatusCondition(string $fieldname, int $statusrange): string
{
    if ($statusrange >= 12 && $statusrange <= 15) {
        return '(' . $fieldname . ' between 1 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange >= 23 && $statusrange <= 25) {
        return '(' . $fieldname . ' between 2 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange >= 34 && $statusrange <= 35) {
        return '(' . $fieldname . ' between 3 and ' . ($statusrange % 10) . ')';
    } elseif ($statusrange == 45) {
        return '(' . $fieldname . ' between 4 and 5)';
    } elseif ($statusrange == 599) {
        return $fieldname . ' in (5,99)';
    } else {
        return $fieldname . ' = ' . $statusrange;
    }
}

// -------------------------------------------------------------

function checkStatusRange(int $currstatus, int $statusrange): bool
{
    if ($statusrange >= 12 && $statusrange <= 15) {
        return ($currstatus >= 1 && $currstatus <= ($statusrange % 10));
    }
    if ($statusrange >= 23 && $statusrange <= 25) {
        return ($currstatus >= 2 && $currstatus <= ($statusrange % 10));
    }
    if ($statusrange >= 34 && $statusrange <= 35) {
        return ($currstatus >= 3 && $currstatus <= ($statusrange % 10));
    }
    if ($statusrange == 45) {
        return ($currstatus == 4 || $currstatus == 5);
    } if ($statusrange == 599) {
        return ($currstatus == 5 || $currstatus == 99);
    }
    return ($currstatus == $statusrange);
}

/**
 * Adds HTML attributes to create a filter over words learning status.
 *
 * @param  int<0, 5>|98|99|599 $status Word learning status
 *                                     599 is a special status
 *                                     combining 5 and 99 statuses.
 *                                     0 return an empty string
 * @return string CSS class filter to exclude $status
 */
function makeStatusClassFilter($status)
{
    if ($status == 0) {
        return '';
    }
    $liste = array(1,2,3,4,5,98,99);
    if ($status == 599) {
        makeStatusClassFilterHelper(5, $liste);
        makeStatusClassFilterHelper(99, $liste);
    } elseif ($status < 6 || $status > 97) {
        makeStatusClassFilterHelper($status, $liste);
    } else {
        $from = (int) ($status / 10);
        $to = $status - ($from * 10);
        for ($i = $from; $i <= $to; $i++) {
            makeStatusClassFilterHelper($i, $liste);
        }
    }
    // Set all statuses that are not -1
    $r = '';
    foreach ($liste as $v) {
        if ($v != -1) {
            $r .= ':not(.status' . $v . ')';
        }
    }
    return $r;
}

/**
 * Replace $status in $array by -1
 *
 * @param int   $status A value in $array
 * @param int[] $array  Any array of values
 */
function makeStatusClassFilterHelper($status, &$array): void
{
    $pos = array_search($status, $array);
    if ($pos !== false) {
        $array[$pos] = -1;
    }
}

/**
 * Return checked attribute if $val is in array $_REQUEST[$name]
 *
 * @param mixed  $val  Value to look for, needle
 * @param string $name Key of request haystack.
 *
 * @return string ' ' of ' checked="checked" ' if the qttribute should be checked.
 *
 * @psalm-return ' '|' checked="checked" '
 */
function checkTest($val, $name): string
{
    if (!isset($_REQUEST[$name])) {
        return ' ';
    }
    if (!is_array($_REQUEST[$name])) {
        return ' ';
    }
    if (in_array($val, $_REQUEST[$name])) {
        return ' checked="checked" ';
    }
    return ' ';
}

/**
 * Make the plus and minus controls in a test table for a word.
 *
 * @param int $score  Score associated to this word
 * @param int $status Status for this word
 * @param int $wordid Word ID
 *
 * @return string the HTML-formatted string to use
 */
function make_status_controls_test_table($score, $status, $wordid): string
{
    if ($score < 0) {
        $scoret = '<span class="red2">' . get_status_abbr($status) . '</span>';
    } else {
        $scoret = get_status_abbr($status);
    }

    if ($status <= 5 || $status == 98) {
        $plus = '<img src="/assets/icons/plus.png" class="click" title="+" alt="+" onclick="changeTableTestStatus(' . $wordid . ',true);" />';
    } else {
        $plus = '<img src="' . get_file_path('assets/icons/placeholder.png') . '" title="" alt="" />';
    }
    if ($status >= 1) {
        $minus = '<img src="/assets/icons/minus.png" class="click" title="-" alt="-" onclick="changeTableTestStatus(' . $wordid . ',false);" />';
    } else {
        $minus = '<img src="' . get_file_path('assets/icons/placeholder.png') . '" title="" alt="" />';
    }
    return ($status == 98 ? '' : $minus . ' ') . $scoret . ($status == 99 ? '' : ' ' . $plus);
}

/**
 * Echo a HEAD tag for using with frames
 *
 * @param string $title Title to use
 *
 * @return void
 */
function framesetheader($title): void
{
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    ?><!DOCTYPE html>
    <?php echo '<html lang="en">'; ?>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/styles.css');?>" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
    <!--
        <?php echo file_get_contents("UNLICENSE.md");?>
    -->
    <title>LWT :: <?php echo tohtml($title); ?></title>
</head>
    <?php
}

/**
 * Write a page header and start writing its body.
 *
 * @param string $title Title of the page
 * @param bool   $close Set to true if you are closing the header
 *
 * @since 2.7.0 Show no text near the logo, page title enclosed in H1
 *
 * @global bool $debug Show a DEBUG span if true
 */
function pagestart($title, $close): void
{
    pagestart_nobody($title);
    echo '<div>';
    if ($close) {
        echo '<a href="index.php" target="_top">';
    }
    echo_lwt_logo();
    if ($close) {
        echo '</a>';
        quickMenu();
    }
    echo '</div>
    <h1>' . tohtml($title) . (\Lwt\Core\Globals::isDebug() ? ' <span class="red">DEBUG</span>' : '') . '</h1>';
}

/**
 * Start a standard page with a complete header and a non-closed body.
 *
 * @param string $title  Title of the page
 * @param string $addcss Some CSS to be embed in a style tag
 */
function pagestart_nobody($title, $addcss = ''): void
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $debug = \Lwt\Core\Globals::isDebug();
    @header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    @header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    @header('Cache-Control: no-cache, must-revalidate, max-age=0');
    @header('Pragma: no-cache');
    ?><!DOCTYPE html>
    <?php
    echo '<html lang="en">';
    ?>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <!--
        <?php echo file_get_contents("UNLICENSE.md");?>
    -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon"/>
    <link rel="apple-touch-icon" href="<?php print_file_path('img/apple-touch-icon-57x57.png');?>" />
    <link rel="apple-touch-icon" sizes="72x72" href="<?php print_file_path('img/apple-touch-icon-72x72.png');?>" />
    <link rel="apple-touch-icon" sizes="114x114" href="<?php print_file_path('img/apple-touch-icon-114x114.png');?>" />
    <link rel="apple-touch-startup-image" href="/assets/images/apple-touch-startup.png" />
    <meta name="apple-mobile-web-app-capable" content="yes" />

    <?php if (should_use_vite()) : ?>
    <!-- Vite assets -->
        <?php echo vite_assets('js/main.ts'); ?>
    <?php else : ?>
    <!-- Legacy assets -->
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/jquery-ui.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/jquery.tagit.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/styles.css');?>" />
    <link rel="stylesheet" type="text/css" href="<?php print_file_path('css/feed_wizard.css');?>" />
    <script type="text/javascript" src="/assets/js/jquery.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery.scrollTo.min.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery-ui.min.js"  charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/jquery.jeditable.mini.js" charset="utf-8"></script>
    <script type="text/javascript" src="/assets/js/tag-it.js" charset="utf-8"></script>
    <?php endif; ?>
    <script type="text/javascript" src="/assets/js/overlib/overlib_mini.js" charset="utf-8"></script>
    <style type="text/css">
        <?php echo $addcss . "\n"; ?>
    </style>
    <!-- URLBASE : "<?php echo tohtml(url_base()); ?>" -->
    <!-- TBPREF  : "<?php echo tohtml($tbpref);  ?>" -->
    <script type="text/javascript">
        //<![CDATA[
        var STATUSES = <?php echo json_encode(get_statuses()); ?>;
        var TAGS = <?php echo json_encode(get_tags()); ?>;
        var TEXTTAGS = <?php echo json_encode(get_texttags()); ?>;
        //]]>
    </script>
    <?php if (!should_use_vite()) : ?>
    <script type="text/javascript" src="/assets/js/pgm.js" charset="utf-8"></script>
    <?php endif; ?>

    <title>LWT :: <?php echo tohtml($title); ?></title>
</head>
    <?php
    echo '<body>';
    ?>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
    <?php
    flush();
    if ($debug) {
        showRequest();
    }
}

?>
