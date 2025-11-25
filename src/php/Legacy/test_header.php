<?php

/**
 * \file
 * \brief Show test header frame
 *
 * Call: do_test_header.php?lang=[langid]
 * Call: do_test_header.php?text=[textid]
 * Call: do_test_header.php?selection=1
 *      (SQL via $_SESSION['testsql'])
 *
 * PHP version 8.1
 *
 * @category User_Interface
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/do-test-header.html
 * @since   1.0.3
 */

require_once 'inc/session_utility.php';

/**
 * Set useful data for the test using SQL query.
 *
 * @param string &$title Title to be overwritten
 * @param string &$p     Property URL to be overwritten
 *
 * @return string SQL query to use
 *
 * @global string $tbpref Database table prefix
 */
function get_sql_test_data(&$title, &$p)
{
    global $tbpref;
    $p = "selection=" . $_REQUEST['selection'];
    $testsql = do_test_test_from_selection(
        $_REQUEST['selection'],
        $_SESSION['testsql']
    );
    $totalcount = get_first_value(
        "SELECT count(distinct WoID) AS value FROM $testsql"
    );
    $title = 'Selected ' . $totalcount . ' Term' . ($totalcount < 2 ? '' : 's');
    $cntlang = get_first_value(
        'SELECT count(distinct WoLgID) AS value FROM ' . $testsql
    );
    if ($cntlang > 1) {
        $message = 'Error: The selected terms are in ' . $cntlang . ' languages, ' .
        'but tests are only possible in one language at a time.';
        echo error_message_with_hide($message, true);
        return '';
    }
    $title .= ' IN ' . get_first_value(
        "SELECT LgName AS value
        FROM {$tbpref}languages, {$testsql} AND LgID = WoLgID
        LIMIT 1"
    );
    return $testsql;
}

/**
 * Set useful data for the test using language.
 *
 * @param string $title Title to be overwritten
 * @param string $p     Property URL to be overwritten
 *
 * @return string SQL query to use
 *
 * @global string $tbpref Database table prefix
 */
function get_lang_test_data(&$title, &$p): string
{
    global $tbpref;
    $langid = getreq('lang');
    $p = "lang=" . $langid;
    $title = "All Terms in " . get_first_value(
        "SELECT LgName AS value FROM {$tbpref}languages WHERE LgID = $langid"
    );
    $testsql = ' ' . $tbpref . 'words WHERE WoLgID = ' . $langid . ' ';
    return $testsql;
}

/**
 * Set useful data for the test using text.
 *
 * @param string $title Title to be overwritten
 * @param string $p     Property URL to be overwritten
 *
 * @return string SQL query to use
 *
 * @global string $tbpref Database table prefix
 */
function get_text_test_data(&$title, &$p): string
{
    global $tbpref;
    $textid = getreq('text');
    $p = "text=" . $textid;
    $title = get_first_value(
        'SELECT TxTitle AS value FROM ' . $tbpref . 'texts WHERE TxID = ' . $textid
    );
    saveSetting('currenttext', $_REQUEST['text']);
    $testsql =
    ' ' . $tbpref . 'words, ' . $tbpref . 'textitems2
    WHERE Ti2LgID = WoLgID AND Ti2WoID = WoID AND Ti2TxID = ' . $textid . ' ';
    return $testsql;
}

/**
 * Return the words count for this test.
 *
 * @param string $testsql SQL query for this test.
 *
 * @return array{0: string, 1: string} Total words due and total words learning
 */
function get_test_counts($testsql)
{
    $totalcountdue = get_first_value(
        "SELECT count(distinct WoID) AS value
        FROM " . $testsql . " AND WoStatus BETWEEN 1 AND 5
        AND WoTranslation != '' AND WoTranslation != '*' AND WoTodayScore < 0"
    );
    $totalcount = get_first_value(
        "SELECT count(distinct WoID) AS value
        FROM " . $testsql . " AND WoStatus BETWEEN 1 AND 5 AND WoTranslation != ''
        AND WoTranslation != '*'"
    );
    return array($totalcountdue, $totalcount);
}


/**
 * Make the header row for tests.
 *
 * @param mixed $_p URL property to use (unnused), will be removed in LWT 3.0.0
 *
 * @return void
 */
function do_test_header_row($_p)
{
    ?>
<div class="flex-header">
    <div>
        <a href="edit_texts.php" target="_top">
            <?php echo_lwt_logo(); ?>
        </a>
    </div>
    <?php
    // This part only works if $textid is set
    if (is_numeric(getreq('text'))) {
        $textid = (int) getreq('text');
        echo '<div>' . getPreviousAndNextTextLinks(
            $textid,
            'do_test.php?text=',
            false,
            ''
        ) . '</div>';

        ?>
    <div>
        <a href="do_text.php?start=<?php echo $textid; ?>" target="_top">
            <img src="icn/book-open-bookmark.png" title="Read" alt="Read" />
        </a>
        <a href="print_text.php?text=<?php echo $textid; ?>" target="_top">
            <img src="icn/printer.png" title="Print" alt="Print" />
        </a>
        <?php echo get_annotation_link($textid); ?>
    </div>
        <?php
    }
    ?>
    <div>
        <?php quickMenu(); ?>
    </div>
</div>
    <?php
}

/**
 * Prepare JavaScript content for the header.
 *
 * @return void
 */
function do_test_header_js()
{
    ?>
<script type="text/javascript">

    function setUtteranceSetting () {
        const utterancechecked = JSON.parse(
            localStorage.getItem('review-utterance-allowed')
        );
        const utterancecheckbox = document.getElementById('utterance-allowed');

        utterancecheckbox.checked = utterancechecked;
        utterancecheckbox.addEventListener('change', function () {
            localStorage.setItem(
                'review-utterance-allowed',
                utterancecheckbox.checked
            );
        });
    }


    /**
     * Reset frames location
     */
    function resetFrames() {
        parent.frames['ro'].location.href = 'empty.html';
        parent.frames['ru'].location.href = 'empty.html';
    }

    /**
     * Prepare frames for testing words
     */
    function startWordTest(type, property) {
        resetFrames();
        window.location.href = 'do_test.php?type=' + type + '&' + property;
    }

    /**
     * Prepare frames for test table.
     */
    function startTestTable(property) {
        resetFrames();
        window.location.href = 'do_test.php?type=table&' + property;
    }

    $(setUtteranceSetting)
    </script>
    <?php
}

/**
 * Make the header content for tests.
 *
 * @param string $title         Page title
 * @param string $p             URL property to use
 * @param string $totalcountdue Number of words due for today
 * @param string $totalcount    Total number of words.
 * @param string $language      L2 language name
 *
 * @return void
 */
function do_test_header_content($title, $p, $totalcountdue, $totalcount, $language)
{
    ?>
<h1>TEST ▶ <?php echo tohtml($title) ?></h1>
<div style="margin: 5px;">
    Word<?php echo intval($totalcount) > 1 ? 's' : ''; ?> due today:
    <?php echo htmlspecialchars($totalcount); ?>,
    <span class="todosty" id="not-tested-header"><?php
    echo htmlspecialchars($totalcountdue);
    ?></span> remaining.
</div>
<div class="flex-spaced">
    <div>
        <input type="button" value="..[<?php echo $language; ?>].."
        onclick="startWordTest(1, '<?php echo $p; ?>')" />
        <input type="button" value="..[L1].."
        onclick="startWordTest(2, '<?php echo $p; ?>')" />
        <input type="button" value="..[••].."
        onclick="startWordTest(3, '<?php echo $p; ?>')" />
    </div>
    <div>
        <input type="button" value="[<?php echo $language; ?>]"
        onclick="startWordTest(4, '<?php echo $p; ?>')" />
        <input type="button" value="[L1]"
        onclick="startWordTest(5, '<?php echo $p; ?>')" />
    </div>
    <div>
        <input type="button" value="Table"
        onclick="startTestTable('<?php echo $p; ?>')" />
    </div>
    <div>
        <input type="checkbox" id="utterance-allowed" />
        <label for="utterance-allowed">Read words aloud</label>
    </div>
</div>
    <?php
}

/**
 * Set useful data for the test.
 *
 * @param string $title Title to be overwritten
 * @param string $p     Property URL to be overwritten
 *
 * @return array{0: string, 1: string} Total words due and total words learning
 */
function get_test_data(&$title, &$p)
{
    if (isset($_REQUEST['selection']) && isset($_SESSION['testsql'])) {
        $testsql = get_sql_test_data($title, $p);
    } elseif (isset($_REQUEST['lang'])) {
        $testsql = get_lang_test_data($title, $p);
    } elseif (isset($_REQUEST['text'])) {
        $testsql = get_text_test_data($title, $p);
    } else {
        $testsql = '';
        $p = '';
        $title = 'Request Error!';
        pagestart($title, true);
        my_die("do_test_header.php called with wrong parameters");
    }
    return get_test_counts($testsql);
}

/**
 * Do the header for test page.
 *
 * @param string $title         Page title
 * @param string $p             URL property to use
 * @param string $totalcountdue Number of words due for today
 * @param string $totalcount    Total number of words.
 * @param string $language      L2 Language name
 *
 * @return void
 */
function do_test_header_page($title, $p, $totalcountdue, $totalcount, $language)
{
    do_test_header_js();

    $_SESSION['teststart'] = time() + 2;
    $_SESSION['testcorrect'] = 0;
    $_SESSION['testwrong'] = 0;
    $_SESSION['testtotal'] = $totalcountdue;


    do_test_header_row(null);
    do_test_header_content($title, $p, $totalcountdue, $totalcount, $language);
}


/**
 * Use requests passed to the page to start it.
 *
 * @param string $language L2 language name
 *
 * @return void
 */
function start_test_header_page($language = 'L2')
{
    $title = $p = '';
    list($totalcountdue, $totalcount) = get_test_data($title, $p);
    do_test_header_page($title, $p, $totalcountdue, $totalcount, $language);
}

?>
