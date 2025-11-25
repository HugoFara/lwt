<?php
/**
 * \file
 * \brief Load a RSS feed.
 *
 * Call: inc/ajax_load_feed.php
 *
 * @author  andreask7 <andreask7@users.noreply.github.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/inc-ajax-load-feed.html
 * @since   1.6.0-fork
 */

require_once __DIR__ . '/session_utility.php';

/**
 * Get the list of feeds.
 *
 * @param string[][] $feed A feed
 *
 * @return array{0: int, 1: int} Number of imported feeds and number of duplicated feeds.
 *
 * @global string $tbpref Database table prefix
 */
function get_feeds_list($feed, $nfid): array
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    $valuesArr = array();
    foreach ($feed as $data) {
        $d_title=convert_string_to_sqlsyntax($data['title']);
        $d_link=convert_string_to_sqlsyntax($data['link']);
        $d_text=convert_string_to_sqlsyntax(isset($data['text']) ?  $data['text'] : null);
        $d_desc=convert_string_to_sqlsyntax($data['desc']);
        $d_date=convert_string_to_sqlsyntax($data['date']);
        $d_audio=convert_string_to_sqlsyntax($data['audio']);
        $d_feed=convert_string_to_sqlsyntax($nfid);
        $valuesArr[] = "($d_title,$d_link,$d_text,$d_desc,$d_date,$d_audio,$d_feed)";
    }
    $sql = 'INSERT IGNORE INTO ' . $tbpref . 'feedlinks (FlTitle,FlLink,FlText,FlDescription,FlDate,FlAudio,FlNfID)
    VALUES ' . implode(',', $valuesArr);
    do_mysqli_query($sql);
    $imported_feed = mysqli_affected_rows($GLOBALS["DBCONNECTION"]);
    $nif = count($valuesArr) - $imported_feed;
    unset($valuesArr);
    return array($imported_feed, $nif);
}

/**
 * Update the feeds database and echo a result message.
 *
 * @param int    $imported_feed Number of imported feeds
 * @param int    $nif           Number of duplicated feeds
 * @param string $nfname        News feed name
 * @param int    $nfid          News feed ID
 * @param string $nfoptions     News feed options
 *
 * @return void
 *
 * @global string $tbpref Database table prefix
 */
function print_feed_result($imported_feed, $nif, $nfname, $nfid, $nfoptions)
{
    $tbpref = \Lwt\Core\LWT_Globals::getTablePrefix();
    do_mysqli_query(
        'UPDATE ' . $tbpref . 'newsfeeds
        SET NfUpdate="' . time() . '"
        WHERE NfID=' . $nfid
    );
    $nf_max_links = get_nf_option($nfoptions, 'max_links');
    if (!$nf_max_links) {
        if (get_nf_option($nfoptions, 'article_source')) {
            $nf_max_links=getSettingWithDefault('set-max-articles-with-text');
        } else {
            $nf_max_links=getSettingWithDefault('set-max-articles-without-text');
        }
    }
    $msg = $nfname . ": ";
    if (!$imported_feed) {
        $msg .= "no";
    } else {
        $msg .= $imported_feed;
    }
    $msg .= " new article";
    if ($imported_feed > 1) {
        $msg .=  "s";
    }
    $msg .= " imported";
    if ($nif > 1) {
        $msg .= ", $nif articles are dublicates";
    } else if ($nif==1) {
        $msg.= ", $nif dublicated article";
    }
    $result=do_mysqli_query(
        "SELECT COUNT(*) AS total
        FROM " . $tbpref . "feedlinks
        WHERE FlNfID IN (".$nfid.")"
    );
    $row = mysqli_fetch_assoc($result);
    $to = ($row['total'] - $nf_max_links);
    if ($to>0) {
        do_mysqli_query(
            "DELETE FROM " . $tbpref . "feedlinks
            WHERE FlNfID in (".$nfid.")
            ORDER BY FlDate
            LIMIT $to"
        );
        $msg.= ", $to old article(s) deleted";
    }
    echo "<div class=\"msgblue\"><p> $msg </p></div>";
}

/**
 * Main function to execute an AJAX query echoing feeds.
 *
 * @param string $nfname      Newsfeed name
 * @param int    $nfid        News feed ID
 * @param string $nfsourceuri News feed source
 * @param string $nfoptions   News feed options
 *
 * @return string Message if an error occured, '' otherwise
 */
function do_ajax_load_feed($nfname, $nfid, $nfsourceuri, $nfoptions)
{
    chdir('..');
    $msg = '';
    $feed = get_links_from_rss($nfsourceuri, get_nf_option($nfoptions, 'article_source'));
    if (empty($feed)) {
        $msg = 'Error: Could not load "' . $nfname . '"! ';
        echo "<div class=\"red\"><p> $msg </p></div>";
    } else {
        list($imported_feed, $nif) = get_feeds_list($feed, $nfid);
        print_feed_result($imported_feed, $nif, $nfname, $nfid, $nfoptions);
    }
    return $msg;
}

if (isset($_POST['NfName']) && isset($_POST['NfID']) && isset($_POST['NfSourceURI']) && isset($_POST['NfOptions'])) {
    session_write_close();
    $msg = do_ajax_load_feed(
        $_POST['NfName'], (int)$_POST['NfID'],
        $_POST['NfSourceURI'], $_POST['NfOptions']
    );
    session_start();
    $_SESSION['feed_loaded'][$_POST['cnt']] = $msg;
    session_write_close();
}

?>
