<?php

/**
 * \file
 * \brief Edit/New Multi-word term (expression)
 *
 * Call: edit_mword.php?....
 *              ... op=Save ... do insert new
 *              ... op=Change ... do update
 *              ... tid=[textid]&ord=[textpos]&wid=[wordid] ... edit
 *              ... tid=[textid]&ord=[textpos]&txt=[word] ... new or edit
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/edit-mword.html
 * @since    1.0.3-fork
 */

require_once 'Core/session_utility.php';
require_once 'Core/simterms.php';
require_once 'Core/classes/Term.php';

use Lwt\Classes\Term;

/**
 * Export term data as a JSON dictionnary.
 *
 * @return string JSON dictionnary.
 */
function export_term_js_dict($term): string
{
    $translation = $term->translation . getWordTagList($term->id, ' ', 1, 0);
    $raw_answer = json_encode(
        array(
            "woid" => $term->id,
            "text" => $term->text,
            "romanization" => $term->roman,
            "translation" => $translation,
            "status" => $term->status
        )
    );
    if ($raw_answer === false) {
        $raw_answer = json_encode(array("error" => "Unable to return data."));
        if ($raw_answer === false) {
            my_die("Unable to return data");
        }
    }
    return $raw_answer;
}

/**
 * Use the superglobals to load a new Term object.
 *
 * Check if the lowercase version is a good one.
 *
 * @return Term The loaded data.
 *
 * @since 2.5.2-fork The created term has the attribute "wordcount" assigned
 * (instead of the wrong "word_count").
 */
function edit_mword_prepare_term()
{
    $textlc = trim(getreq("WoTextLC"));
    if (mb_strtolower(trim(getreq("WoText")), 'UTF-8') != $textlc) {
        $titletext = "New/Edit Term: " . tohtml($textlc);
        pagestart_nobody($titletext);
        echo '<h1>' . $titletext . '</h1>';
        $message = 'Error: Term in lowercase must be exactly = "' . $textlc .
        '", please go back and correct this!';
        echo error_message_with_hide($message, false);
        pageend();
        exit();
    }
    $term = new Term();
    $translation_raw = repl_tab_nl(getreq("WoTranslation"));
    $translation = ($translation_raw == '') ? '*' : $translation_raw;
    $term->translation = $translation;
    $term->text = prepare_textdata($_REQUEST["WoText"]);
    $term->textlc = prepare_textdata($textlc);
    $term->roman = $_REQUEST["WoRomanization"];
    // Words count is not necessary when updating multi-word
    if (!empty($_REQUEST["len"])) {
        $term->wordcount = (int) $_REQUEST["len"];
    }
    $term->sentence = $_REQUEST["WoSentence"];
    return $term;
}


/**
 * Do a server operation for multiwords.
 *
 * @return void
 */
function edit_mword_do_operation($term)
{
    if ($_REQUEST['op'] == 'Save') {
        // INSERT
        $term->status = (int) $_REQUEST["WoStatus"];
        $term->lgid = (int) $_REQUEST["WoLgID"];
        edit_mword_do_insert($term);
    } else {
        // UPDATE
        $term->id = (int) $_REQUEST["WoID"];
        $term->status = (int) $_REQUEST["WoOldStatus"];
        edit_mword_do_update($term, (int) $_REQUEST["WoStatus"]);
    }
    ?>
    <script type="text/javascript">
        cleanupRightFrames();
    </script>

    <?php
    /*
     * Unreachable code, at least since 2.3.0-fork.
     *
     * if (isset($sqltext)) {
        flush();
        do_mysqli_query($sqltext);
        echo '<p>OK: ',tohtml($message),'</p>';
    }*/
}

/**
 * Insert a multi-word to the database.
 *
 * @param Term $term Multi-word to be inserted.
 *
 * @return string "Terms saved: n"
 *
 * @global string $tbpref Database table prefix.
 *
 * @since 2.5.2-fork Use the "wordcount" attribute of $term instead of the
 * wrong word_count.
 */
function edit_mword_do_insert($term)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $titletext = "New Term: " . tohtml($term->textlc);
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';

    $message = runsql(
        "INSERT INTO {$tbpref}words (
            WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
            WoRomanization, WoWordCount, WoStatusChanged,"
            .  make_score_random_insert_update('iv') . '
        ) VALUES( ' .
            $term->lgid . ', ' .
            convert_string_to_sqlsyntax($term->textlc) . ', ' .
            convert_string_to_sqlsyntax($term->text) . ', ' .
            $term->status . ', ' .
            convert_string_to_sqlsyntax($term->translation) . ', ' .
            convert_string_to_sqlsyntax(repl_tab_nl($term->sentence)) . ', ' .
            convert_string_to_sqlsyntax($term->roman) . ', ' .
            $term->wordcount . ',
            NOW(), ' .
            make_score_random_insert_update('id') .
        ')',
        "Term saved"
    );
    init_word_count();
    // strToClassName($textlc);
    $term->id = get_last_key();
    saveWordTags($term->id);
    insertExpressions($term->textlc, $term->lgid, $term->id, $term->wordcount, 0);
    return $message;
}


/**
 * Update a multi-word.
 *
 * @param Term $term      Multi-word to be inserted.
 * @param int  $newstatus New multi-word status
 *
 * @return string "Terms updated: n"
 *
 * @global string $tbpref Database table prefix.
 */
function edit_mword_do_update($term, $newstatus)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $titletext = "Edit Term: " . tohtml($term->textlc);
    pagestart_nobody($titletext);
    echo '<h1>' . $titletext . '</h1>';

    $oldstatus = $term->status;
    $status_change = '';
    if ($oldstatus != $newstatus) {
        $status_change = ', WoStatus = ' . $newstatus . ', WoStatusChanged = NOW()';
    }

    $message = runsql(
        'UPDATE ' . $tbpref . 'words set
        WoText = ' . convert_string_to_sqlsyntax($term->text) . ',
        WoTranslation = ' . convert_string_to_sqlsyntax($term->translation) . ',
        WoSentence = ' . convert_string_to_sqlsyntax(
            repl_tab_nl($term->sentence)
        ) . ',
        WoRomanization = ' . convert_string_to_sqlsyntax($term->roman) .
        $status_change . ',' .
        make_score_random_insert_update('u') . '
        where WoID = ' . $term->id,
        "Updated"
    );

    saveWordTags($term->id);

    $term->status = (int) $_REQUEST["WoStatus"];
    ?>
    <script type="text/javascript">
        function update_mword(mword, oldstatus) {
            const context = window.parent.document;
            let title = '';
            if (window.parent.LWT_DATA.settings.jQuery_tooltip)
                title = make_tooltip(
                    mword.text, mword.translation, mword.romanization, mword.status
                );
            $('.word' + mword.woid, context)
            .attr('data_trans', mword.translation)
            .attr('data_rom', mword.romanization)
            .attr('title', title)
            .removeClass('status' + oldstatus)
            .addClass('status' + mword.status)
            .attr('data_status', mword.status);
        }

        update_mword(
            <?php echo export_term_js_dict($term); ?>,
            <?php echo (int) $_REQUEST['WoOldStatus']; ?>
        );
    </script>
    <?php
    return $message;
}

/**
 * Make the main display for editing new multi-word.
 *
 * @param string $text Original group of words.
 * @param int    $tid  Text ID
 * @param int    $ord  Text order
 * @param int    $len  Number of words in the multi-word.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 */
function edit_mword_new($text, $tid, $ord, $len)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    $term = new Term();
    $term->lgid = get_first_value(
        "SELECT TxLgID AS value FROM {$tbpref}texts WHERE TxID = $tid"
    );
    $term->text = prepare_textdata($text);
    $term->textlc = mb_strtolower($term->text, 'UTF-8');

    $term->id = get_first_value(
        "SELECT WoID AS value FROM {$tbpref}words
        WHERE WoLgID = $term->lgid AND WoTextLC = " .
        convert_string_to_sqlsyntax($term->textlc)
    );
    if (isset($term->id)) {
        $term->text = get_first_value(
            "SELECT WoText AS value FROM {$tbpref}words WHERE WoID = $term->id"
        );
    }
    edit_mword_display_new($term, $tid, $ord, $len);
}

/**
 * Make the main display for editing existing multi-word.
 *
 * @param int $wid Term ID
 * @param int $tid Text ID
 * @param int $ord Text order
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 */
function edit_mword_update($wid, $tid, $ord)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    $term = new Term();

    $term->id = $wid;
    $sql = "SELECT WoText, WoLgID FROM {$tbpref}words WHERE WoID = $term->id";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    if (!$record) {
        my_die("Cannot access Term and Language in edit_mword.php");
    }
    $term->text = (string) $record['WoText'];
    $term->lgid = (int) $record['WoLgID'];
    mysqli_free_result($res);
    $term->textlc = mb_strtolower($term->text, 'UTF-8');
    edit_mword_display_change($term, $tid, $ord);
}

/**
 * Display a form for the insertion of a new multi-word.
 *
 * @param Term $term Multi-word to insert.
 * @param int  $tid  Text ID
 * @param int  $ord  Text order
 * @param int  $len  Number of words in the multi-word.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 */
function edit_mword_display_new($term, $tid, $ord, $len)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $scrdir = getScriptDirectionTag($term->lgid);
    $seid = get_first_value(
        "SELECT Ti2SeID AS value
        FROM {$tbpref}textitems2
        WHERE Ti2TxID = $tid AND Ti2Order = $ord"
    );
    $sent = getSentence(
        $seid,
        $term->textlc,
        (int) getSettingWithDefault('set-term-sentence-count')
    );
    $showRoman = (bool) get_first_value(
        "SELECT LgShowRomanization AS value
        FROM {$tbpref}languages JOIN {$tbpref}texts
        ON TxLgID = LgID
        WHERE TxID = $tid"
    );

    ?>

    <script type="text/javascript">
        $(document).ready(lwtFormCheck.askBeforeExit);
        $(window).on('beforeunload',function() {
            setTimeout(function() {
                window.parent.frames['ru'].location.href = 'empty.html';
            }, 0);
        });
    </script>
    <form name="newword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $term->lgid; ?>" />
    <input type="hidden" name="WoTextLC" value="<?php echo tohtml($term->textlc); ?>" />
    <input type="hidden" name="tid" value="<?php echo $tid; ?>" />
    <input type="hidden" name="ord" value="<?php echo $ord; ?>" />
    <input type="hidden" name="len" value="<?php echo $len; ?>" />
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr title="Only change uppercase/lowercase!">
            <td class="td1 right"><b>New Term:</b></td>
            <td class="td1">
                <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp" data_info="New Term" type="text" name="WoText" id="wordfield" value="<?php echo tohtml($term->text); ?>" maxlength="250" size="35" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <?php print_similar_terms_tabrow(); ?>
        <tr>
            <td class="td1 right">Translation:</td>
            <td class="td1">
                <textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp" data_maxlength="500" data_info="Translation" cols="35" rows="3"></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?php echo getWordTags(0); ?>
            </td>
        </tr>
        <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
            <td class="td1 right">Romaniz.:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Romanization" name="WoRomanization" value="" maxlength="100" size="35" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Sentence<br />Term in {...}:</td>
            <td class="td1">
                <textarea <?php echo $scrdir; ?> name="WoSentence" class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000" data_info="Sentence" cols="35" rows="3"><?php echo tohtml(repl_tab_nl($sent[1])); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Status:</td>
            <td class="td1">
                <?php echo get_wordstatus_radiooptions(1); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <?php echo createDictLinksInEditWin(
                    $term->lgid,
                    $term->text,
                    'document.forms[0].WoSentence',
                    !isset($_GET['nodict'])
                ); ?>
                &nbsp; &nbsp; &nbsp;
                <input type="submit" name="op" value="Save" />
            </td>
        </tr>
    </table>
    </form>
    <?php
    // Display example sentences button
    example_sentences_area($term->lgid, $term->textlc, 'document.forms.newword.WoSentence', -1);
}

/**
 * Display an updating form for a multi-word.
 *
 * @param Term $term Multi-word to being modified.
 * @param int  $tid  Text ID
 * @param int  $ord  Text order
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 */
function edit_mword_display_change($term, $tid, $ord)
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();
    $scrdir = getScriptDirectionTag($term->lgid);
    $sql = 'SELECT WoTranslation, WoSentence, WoRomanization, WoStatus
    FROM ' . $tbpref . 'words WHERE WoID = ' . $term->id;
    $res = do_mysqli_query($sql);
    $showRoman = (bool) get_first_value(
        "SELECT LgShowRomanization AS value
        FROM {$tbpref}languages JOIN {$tbpref}texts
        ON TxLgID = LgID
        WHERE TxID = $tid"
    );

    if ($record = mysqli_fetch_assoc($res)) {
        $status = $record['WoStatus'];
        if ($status >= 98) {
            $status = 1;
        }
        $sentence = repl_tab_nl($record['WoSentence']);
        if ($sentence == '') {
            $seid = get_first_value(
                "SELECT Ti2SeID AS value
                FROM " . $tbpref . "textitems2
                WHERE Ti2TxID = $tid AND Ti2Order = $ord"
            );
            $sent = getSentence(
                $seid,
                $term->textlc,
                (int) getSettingWithDefault('set-term-sentence-count')
            );
            $sentence = repl_tab_nl($sent[1]);
        }
        $transl = repl_tab_nl($record['WoTranslation']);
        if ($transl == '*') {
            $transl = '';
        }
        ?>

    <script type="text/javascript">
        $(document).ready(lwtFormCheck.askBeforeExit);
        $(window).on('beforeunload',function() {
            setTimeout(function() {
                window.parent.frames['ru'].location.href = 'empty.html';
            }, 0);
        });
    </script>

    <form name="editword" class="validate" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
    <input type="hidden" name="WoLgID" id="langfield" value="<?php echo $term->lgid; ?>" />
    <input type="hidden" name="WoID" value="<?php echo $term->id; ?>" />
    <input type="hidden" name="WoOldStatus" value="<?php echo $record['WoStatus']; ?>" />
    <input type="hidden" name="WoStatus" value="<?php echo $status; ?>" />
    <input type="hidden" name="WoTextLC" value="<?php echo tohtml($term->textlc); ?>" />
    <input type="hidden" name="tid" value="<?php echo $tid; ?>" />
    <input type="hidden" name="ord" value="<?php echo $ord; ?>" />
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr title="Only change uppercase/lowercase!">
            <td class="td1 right"><b>Edit Term:</b></td>
            <td class="td1">
                <input <?php echo $scrdir; ?> class="notempty checkoutsidebmp"
                data_info="Term" type="text" name="WoText" id="wordfield"
                value="<?php echo tohtml($term->text); ?>" maxlength="250" size="35" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <?php print_similar_terms_tabrow(); ?>
        <tr>
            <td class="td1 right">Translation:</td>
            <td class="td1">
                <textarea name="WoTranslation" class="setfocus textarea-noreturn checklength checkoutsidebmp"
                data_maxlength="500" data_info="Translation" cols="35" rows="3"><?php echo tohtml($transl); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Tags:</td>
            <td class="td1">
                <?php echo getWordTags($term->id); ?>
            </td>
        </tr>
        <tr class="<?php echo ($showRoman ? '' : 'hide'); ?>">
            <td class="td1 right">Romaniz.:</td>
            <td class="td1">
                <input type="text" class="checkoutsidebmp" data_info="Romanization"
                name="WoRomanization" maxlength="100" size="35"
                value="<?php echo tohtml($record['WoRomanization']); ?>" />
            </td>
        </tr>
        <tr>
            <td class="td1 right">Sentence<br />Term in {...}:</td>
            <td class="td1">
                <textarea <?php echo $scrdir; ?> name="WoSentence"
                class="textarea-noreturn checklength checkoutsidebmp" data_maxlength="1000"
                data_info="Sentence" cols="35" rows="3"><?php echo tohtml($sentence); ?></textarea>
            </td>
        </tr>
        <tr>
            <td class="td1 right">Status:</td>
            <td class="td1">
                <?php echo get_wordstatus_radiooptions($record['WoStatus']); ?>
            </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
                <?php echo createDictLinksInEditWin(
                    $term->lgid,
                    $term->text,
                    'document.forms[0].WoSentence',
                    !isset($_GET['nodict'])
                ); ?>
                &nbsp; &nbsp; &nbsp;
                <input type="submit" name="op" value="Change" />
            </td>
        </tr>
    </table>
    </form>
        <?php
        // Display example sentences button
        example_sentences_area($term->lgid, $term->textlc, 'document.forms.editword.WoSentence', $term->id);
    }
    mysqli_free_result($res);
}

/**
 * Create the multi-word frame.
 *
 * @return void
 *
 * @global string $tbpref Database table prefix.
 */
function edit_mword_page()
{
    $tbpref = \Lwt\Core\Globals::getTablePrefix();

    if (isset($_REQUEST['op'])) {
        // INS/UPD
        $term = edit_mword_prepare_term();
        edit_mword_do_operation($term);
    } else {
        $str_id = getreq('wid');
        // No ID provided: check if text exists in database.
        if ($str_id == "" || !is_numeric($str_id)) {
            $lgid = get_first_value(
                "SELECT TxLgID AS value FROM {$tbpref}texts
                WHERE TxID = " . ((int) getreq('tid'))
            );
            $textlc = convert_string_to_sqlsyntax(
                mb_strtolower(prepare_textdata(getreq('txt')), 'UTF-8')
            );

            $str_id = get_first_value(
                "SELECT WoID AS value FROM {$tbpref}words
                WHERE WoLgID = $lgid AND WoTextLC = $textlc"
            );
        }
        if (!isset($str_id)) {
            // edit_mword.php?tid=..&ord=..&txt=.. for new multi-word
            pagestart_nobody("New Term: " . getreq('txt'));
            edit_mword_new(
                getreq('txt'),
                (int) getreq('tid'),
                (int) getreq('ord'),
                (int) getreq('len')
            );
        } else {
            // edit_mword.php?tid=..&ord=..&wid=.. for multi-word edit.
            $text = get_first_value(
                "SELECT WoText AS value FROM {$tbpref}words WHERE WoID = $str_id"
            );
            pagestart_nobody("Edit Term: " . $text);
            edit_mword_update(
                (int) $str_id,
                (int) getreq('tid'),
                (int) getreq('ord')
            );
        }
    }
    pageend();
}

edit_mword_page();

?>
