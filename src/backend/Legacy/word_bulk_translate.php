<?php

/**
 * \file
 * \brief Translate groups of words
 *
 * Call: bulk_translate_words.php?....
 *      ... tid=[textid] ... Vocabulary from this text
 *      ... sl=[sourcelg] ... Source language (usually two letters)
 *      ... tl=[targetlg] ... Target language (usually two letters)
 *      ... term=[term]   ... Term to translate
 *      ... offset=[pos]  ... An optional offset position
 *
 * PHP version 8.1
 *
 * @category Helper_Frame
 * @package Lwt
 * @author  LWT Project <lwt-project@hotmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/bulk-translate-words.html
 * @since   1.6.1
 */

require_once 'Core/session_utility.php';


function bulk_save_terms($terms, $tid, $cleanUp): void
{
    global $tbpref;
    $sqlarr = array();
    $max = get_first_value("SELECT max(WoID) AS value FROM {$tbpref}words");
    foreach ($terms as $row) {
        $sqlarr[] =  '(' .
        convert_string_to_sqlsyntax($row['lg']) . ',' .
        convert_string_to_sqlsyntax(mb_strtolower($row['text'], 'UTF-8')) . ',' .
        convert_string_to_sqlsyntax($row['text']) . ',' .
        convert_string_to_sqlsyntax($row['status']) . ',' .
        (
            (!isset($row['trans']) || $row['trans'] == '') ?
            '"*"' :
            convert_string_to_sqlsyntax($row['trans'])
        ) .
        ',
        "",
        "",
        NOW(), ' .
        make_score_random_insert_update('id') .
        ')';
    }
    $sqltext = "INSERT INTO {$tbpref}words (
        WoLgID, WoTextLC, WoText, WoStatus, WoTranslation, WoSentence,
        WoRomanization, WoStatusChanged," .
        make_score_random_insert_update('iv') . "
    ) VALUES " . rtrim(implode(',', $sqlarr), ',');
    runsql($sqltext, '');
    $tooltip_mode = getSettingWithDefault('set-tooltip-mode');
    $res = do_mysqli_query(
        "SELECT WoID, WoTextLC, WoStatus, WoTranslation
        FROM {$tbpref}words
        where WoID > $max"
    );


    do_mysqli_query(
        "UPDATE {$tbpref}textitems2
        JOIN {$tbpref}words
        ON lower(Ti2Text)=WoTextLC AND Ti2WordCount=1 AND Ti2LgID=WoLgID AND WoID>$max
        SET Ti2WoID = WoID"
    );
    ?>
<p id="displ_message">
    <img src="/assets/icons/waiting2.gif" /> Updating Texts
</p>
<script type="text/javascript">
    const context = window.parent.document;
    const tooltip = <?php echo json_encode($tooltip_mode == 1); ?>;

    function change_term(term) {
        $(".TERM" + term.hex, context)
        .removeClass("status0")
        .addClass("status" + term.WoStatus)
        .addClass("word" + term.WoID)
        .attr("data_wid", term.WoID)
        .attr("data_status", term.WoStatus)
        .attr("data_trans", term.translation);
        if (tooltip) {
            $(".TERM" + term.hex, context).each(
                function() {
                    this.title = make_tooltip(
                        $(this).text(), $(this).attr('data_trans'),
                        $(this).attr('data_rom'), $(this).attr('data_status')
                    );
                }
            );
        } else {
            $(".TERM" + term.hex, context).attr('title', '');
        }

    }
    <?php
    while ($record = mysqli_fetch_assoc($res)) {
        $record["hex"] = strToClassName(prepare_textdata($record["WoTextLC"]));
        $record["translation"] = $record["WoTranslation"];
        echo "change_term(" . json_encode($record) . ");";
    }
    ?>

    $('#learnstatus', context)
    .html('<?php echo addslashes(todo_words_content((int) $tid)); ?>');
    $('#displ_message').remove();
    if (<?php echo json_encode($cleanUp); ?>) {
        cleanupRightFrames();
    }
</script>
    <?php
    mysqli_free_result($res);
    flush();
}


function bulk_do_content($tid, $sl, $tl, $pos): void
{
    global $tbpref;
    $cnt = 0;
    $offset = '';
    $limit = (int)getSettingWithDefault('set-ggl-translation-per-page') + 1;
    $sql = "SELECT LgName, LgDict1URI, LgDict2URI, LgGoogleTranslateURI
    FROM {$tbpref}languages, {$tbpref}texts
    WHERE LgID = TxLgID AND TxID = $tid";
    $res = do_mysqli_query($sql);
    $record = mysqli_fetch_assoc($res);
    $wb1 = isset($record['LgDict1URI']) ? $record['LgDict1URI'] : "";
    $wb2 = isset($record['LgDict2URI']) ? $record['LgDict2URI'] : "";
    $wb3 = isset($record['LgGoogleTranslateURI']) ?
    $record['LgGoogleTranslateURI'] : "";
    ?>
<style>
    .dict {
        cursor: pointer;
    }

    .dict1:hover, .dict2:hover, .dict3:hover {
        opacity:1;
        color:red;
    }

    input[name="WoTranslation"] {
        border: 1px solid red;
    }

    .del_trans{
        cursor: pointer;
        float: right;
    }

    .del_trans:after{
        content: url(icn/broom.png);
        opacity: 0.2;
    }

    .del_trans:hover:after{
        opacity: 1;
    }
</style>
<script type="text/javascript">
    LWT_DATA.language.dict_link1 = '<?php echo $wb1; ?>';
    LWT_DATA.language.dict_link2 = '<?php echo $wb2; ?>';
    LWT_DATA.language.translator_link = '<?php echo $wb3; ?>';
    $('h3,h4,title').addClass('notranslate');

    function clickDictionary() {
        if ($(this).hasClass( "dict1" ))
            WBLINK = LWT_DATA.language.dict_link1;
        if ($(this).hasClass( "dict2" ))
            WBLINK = LWT_DATA.language.dict_link2;
        if ($(this).hasClass( "dict3" ))
            WBLINK = LWT_DATA.language.translator_link;
        let dict_link = WBLINK;
        let popup;
        if (dict_link.startsWith('*')) {
            popup = true;
            dict_link = dict_link.substring(1);
        }
        try {
            let final_url = new URL(dict_link);
            popup = popup || final_url.searchParams.has("lwt_popup");
        } catch (err) {
            if (!(err instanceof TypeError)) {
                throw err;
            }
        }
        if (popup) {
            owin(createTheDictUrl(
                dict_link, $(this).parent().prev().text()
                ));
        } else {
            window.parent.frames['ru'].location.href = createTheDictUrl(
                dict_link, $(this).parent().prev().text()
            );
        }
        $('[name="WoTranslation"]')
        .attr('name',$('[name="WoTranslation"]')
        .attr('data_name'));
        const el = $(this).parent().parent().next().children();
        el.attr('data_name', el.attr('name'));
        el.attr('name','WoTranslation');
    }

    const bulk_interactions = function() {
        $('[name="form1"]').submit(function() {
            $('[name="WoTranslation"]').attr('name',$('[name="WoTranslation"]')
            .attr('data_name'));
            window.parent.frames['ru'].location.href = 'empty.html';
            return true;
        });

        $('td').on(
            'click',
            'span.dict1, span.dict2, span.dict3',
            clickDictionary
        ).on(
            'click',
            '.del_trans',
            function() { $(this).prev().val('').focus(); }
        );

        const displayTranslations = setInterval(function() {
            if ($(".trans>font").length == $(".trans").length) {
                $('.trans').each(function() {
                    const txt = $(this).text();
                    const cnt = $(this).attr('id').replace('Trans_', '');
                    $(this).addClass('notranslate')
                    .html(
                        '<input type="text" name="term[' + cnt + '][trans]" value="'
                        + txt + '" maxlength="100" class="respinput"></input>' +
                        '<div class="del_trans"></div>'
                    );
                });
                $('.term').each(function() {
                    $(this).parent().css('position', 'relative');
                    $(this).after(
                        '<div class="dict">' +
                        (LWT_DATA.language.dict_link1 ? '<span class="dict1">D1</span>' : '') +
                        (LWT_DATA.language.dict_link2 ? '<span class="dict2">D2</span>' : '') +
                        (LWT_DATA.language.translator_link ? '<span class="dict3">Tr</span>' : '') +
                        '</div>'
                    );
                });
                $('iframe,#google_translate_element').remove();
                selectToggle(true, 'form1');
                $('[name^=term]').prop('disabled', false);
                clearInterval(displayTranslations);
            }
        }, 300);
    }

    const bulk_checkbox = function() {
        window.parent.frames['ru'].location.href = 'empty.html';
        $('input[type="checkbox"]').change(function(){
            let v = parseInt($(this).val());
            const e = '[name=term\\[' + v + '\\]\\[text\\]],[name=term\\[' + v +
            '\\]\\[lg\\]],[name=term\\[' + v + '\\]\\[status\\]]';
            $(e).prop('disabled', !this.checked);
            $('#Trans_'+v+' input').prop('disabled', !this.checked);
            if ($('input[type="checkbox"]:checked').length) {
                let operation_option;
                if (this.checked) {
                    operation_option = 'Save';
                } else if ($('input[name="offset"]').length) {
                    operation_option = 'Next';
                } else {
                    operation_option = 'End';
                }
                $('input[type="submit"]').val(operation_option);
            }
        });
    }

    $(window).load(bulk_interactions);

    $(document).ready(bulk_checkbox);

    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: '<?php echo $sl; ?>',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            includedLanguages: '<?php echo $tl; ?>',
            autoDisplay: false
            }, 'google_translate_element');
    }
</script>
<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script type="text/javascript">
    function markAll() {
        $('input[type^=submit]').val('Save');
        selectToggle(true, 'form1');
        $('[name^=term]').prop('disabled', false);
    }

    function markNone() {
        const v = (!$('input[name^=offset]').length) ? 'End' : 'Next';
        $('input[type^=submit]').val(v);
        selectToggle(false,'form1');
        $('[name^=term]').prop('disabled', true);
    }

    function changeTermToggles (elem) {
        const v = elem.val();
        if (v==6) {
            $('.markcheck:checked').each(function() {
                e=$('#Term_' + elem.val()).children('.term');
                e.text(e.text().toLowerCase());
                $('#Text_' + elem.val()).val(e.text().toLowerCase());
            });
            elem.prop('selectedIndex',0);
            return false;
        }
        if (v==7) {
            $('.markcheck:checked').each(function() {
                $('#Trans_' + elem.val() + ' input').val('*');
            });
            elem.prop('selectedIndex',0);
            return false;
        }
        $('.markcheck:checked').each(function() {
            $('#Stat_' + elem.val()).val(v);
        });
        elem.prop('selectedIndex', 0);
        return false;
    }
</script>
    <form name="form1" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
    <span class="notranslate">
        <div id="google_translate_element"></div>
        <table class="tab3" cellspacing="0">
            <tr class="notranslate">
                <th class="th1 center" colspan="3">
                    <input type="button" value="Mark All" onclick="markAll()" />
                    <input type="button" value="Mark None" onclick="markNone()" />
                    <br />
                </th>
            </tr>
            <tr class="notranslate">
                <td class="td1">Marked Terms: </td>
                <td class="td1">
                    <select onchange="changeTermToggles($(this));">
                        <option value="0" selected="selected">
                            [Choose...]
                        </option>
                        <optgroup label="Change Status">
                            <option value="1">Set Status To [1]</option>
                            <option value="2">Set Status To [2]</option>
                            <option value="3">Set Status To [3]</option>
                            <option value="4">Set Status To [4]</option>
                            <option value="5">Set Status To [5]</option>
                            <option value="99">Set Status To [WKn]</option>
                            <option value="98">Set Status To [Ign]</option>
                        </optgroup>
                        <option value="6">Set To Lowercase</option>
                        <option value="7">Delete Translation</option>
                    </select>
                </td>
                <td class="td1" style="min-width: 45px;">
                    <input  type="submit" value="Save" />
                </td>
            </tr>
        </table>
    </span>
    <table class="tab3" cellspacing="0">
        <tr class="notranslate">
            <th class="th1">Mark</th>
            <th class="th1" style="min-width:5em;">Term</th>
            <th class="th1">Translation</th>
            <th class="th1">Status</th>
        </tr>
    <?php
    $res = do_mysqli_query(
        "SELECT Ti2Text AS word, Ti2LgID, MIN(Ti2Order) AS pos
        FROM {$tbpref}textitems2
        WHERE Ti2WoID = 0 AND Ti2TxID = $tid AND Ti2WordCount = 1
        GROUP BY LOWER(Ti2Text)
        ORDER BY pos
        LIMIT $pos, $limit"
    );
    while ($record = mysqli_fetch_assoc($res)) {
        if (++$cnt < $limit) {
            $value = tohtml($record['word']);
            ?>
            <tr>
            <td class="td1 center notranslate">
                <input name="marked[<?php echo $cnt ?>]" type="checkbox" class="markcheck" checked="checked" value="<?php echo $cnt ?>" />
            </td>
            <td id="Term_<?php echo $cnt ?>" class="td1 left notranslate">
                <span class="term"><?php echo $value ?></span>
            </td>
            <td class="td1 trans" id="Trans_<?php echo $cnt ?>">
                <?php echo mb_strtolower($value, 'UTF-8') ?>
            </td>
            <td class="td1 center notranslate">
                <select id="Stat_<?php echo $cnt ?>" name="term[<?php echo $cnt ?>][status]">
                    <option value="1" selected="selected">[1]</option>
                    <option value="2">[2]</option>
                    <option value="3">[3]</option>
                    <option value="4">[4]</option>
                    <option value="5">[5]</option>
                    <option value="99">[WKn]</option>
                    <option value="98">[Ign]</option>
                </select>
                <input type="hidden" id="Text_<?php echo $cnt ?>" name="term[<?php echo $cnt ?>][text]" value="<?php echo $value ?>" />
                <input type="hidden" name="term[<?php echo $cnt ?>][lg]" value="<?php echo tohtml($record['Ti2LgID']) ?>" />
            </td>
            </tr>
            <?php
        } else {
            $offset = '<input type="hidden" name="offset" value="' .
            ($pos + $limit - 1) . '" />
            <input type="hidden" name="sl" value="' . $sl . '" />
            <input type="hidden" name="tl" value="' . $tl . '" />';
        }
    }
    mysqli_free_result($res);
    ?>
    </table>
    <input type="hidden" name="tid" value="<?php echo $tid ?>" />
    <?php echo $offset ?>
    </form>
    <?php
}


$tid = $_REQUEST['tid'];
if (isset($_REQUEST["offset"])) {
    $pos = $_REQUEST["offset"];
}
if (isset($_REQUEST['term'])) {
    $cnt = sizeof($_REQUEST['term']);
    if (isset($pos)) {
        $pos -= $cnt;
    }
    pagestart($cnt . ' New Word' . ($cnt == 1 ? '' : 's') . ' Saved', false);
    bulk_save_terms($_REQUEST['term'], $tid, !isset($pos));
} else {
    pagestart_nobody('Translate New Words');
}
if (isset($pos)) {
    $sl = null;
    $tl = null;
    if (isset($_REQUEST["sl"])) {
        $sl = $_REQUEST["sl"];
        $tl = $_REQUEST["tl"];
    }
    bulk_do_content($tid, $sl, $tl, $pos);
}
pageend();
?>
