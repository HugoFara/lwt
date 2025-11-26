<?php

/**
 * Save a Setting (k/v) and redirect to URI u
 *
 * Call: save_setting_redirect.php?k=[key]&v=[value]&u=[RedirURI]
 *
 * PHP version 8.1
 *
 * @package Lwt
 * @since   2.6.0-fork You can omit either u, or (k, v).
 */

namespace SaveSetting;

require_once __DIR__ . '/session_utility.php';

/**
 * Return the parameters from the URL.
 *
 * @return string[] Setting key, setting value and target URL
 *
 * @psalm-return list{string, string, string}
 */
function get_parameters(): array
{
    $k = getreq('k');
    $v = getreq('v');
    $url = getreq('u');
    return array($k, $v, $url);
}

/**
 * Unset all session settings, and set current text to default.
 *
 * @return void
 */
function unset_settings(): void
{
    unset($_SESSION['currenttextpage']);
    unset($_SESSION['currenttextquery']);
    unset($_SESSION['currenttextquerymode']);
    unset($_SESSION['currenttexttag1']);
    unset($_SESSION['currenttexttag2']);
    unset($_SESSION['currenttexttag12']);

    unset($_SESSION['currentwordpage']);
    unset($_SESSION['currentwordquery']);
    unset($_SESSION['currentwordquerymode']);
    unset($_SESSION['currentwordstatus']);
    unset($_SESSION['currentwordtext']);
    unset($_SESSION['currentwordtag1']);
    unset($_SESSION['currentwordtag2']);
    unset($_SESSION['currentwordtag12']);
    unset($_SESSION['currentwordtextmode']);
    unset($_SESSION['currentwordtexttag']);

    unset($_SESSION['currentarchivepage']);
    unset($_SESSION['currentarchivequery']);
    unset($_SESSION['currentarchivequerymode']);
    unset($_SESSION['currentarchivetexttag1']);
    unset($_SESSION['currentarchivetexttag2']);
    unset($_SESSION['currentarchivetexttag12']);

    unset($_SESSION['currentrsspage']);
    unset($_SESSION['currentrssfeed']);
    unset($_SESSION['currentrssquery']);
    unset($_SESSION['currentrssquerymode']);

    unset($_SESSION['currentfeedspage']);
    unset($_SESSION['currentmanagefeedsquery']);


    saveSetting('currenttext', '');

}

/**
 * Save settings and go to a page.
 *
 * @param string $k Setting key
 * @param string $v Setting value
 * @param string $u URL to go to
 *
 * @return void
 */
function save($k, $v): void
{
    if ($k == 'currentlanguage') {
        unset_settings();
    }

    saveSetting($k, $v);

}

list($k, $v, $url) = get_parameters();
if ($k != '') {
    save($k, $v);
}
if ($url != '') {
    if (isset(parse_url($url)['host'])) {
        // Absolute URL, go to header
        header("Location: " . $url);
    } else {
        // Relative, change current path
        header("Location: ../" . $url);
    }
    exit();
}
?>