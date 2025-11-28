<?php

/**
 * Bulk Translate Form View - Form for bulk translating unknown words
 *
 * Variables expected:
 * - $tid: int - Text ID
 * - $sl: string|null - Source language code
 * - $tl: string|null - Target language code
 * - $pos: int - Current offset position
 * - $dictionaries: array - Dictionary URIs with keys: dict1, dict2, translate
 * - $terms: array - Array of terms to translate with keys: word, Ti2LgID
 * - $nextOffset: int|null - Next offset if more terms exist, null if last page
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Word;

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
    LWT_DATA.language.dict_link1 = '<?php echo $dictionaries['dict1']; ?>';
    LWT_DATA.language.dict_link2 = '<?php echo $dictionaries['dict2']; ?>';
    LWT_DATA.language.translator_link = '<?php echo $dictionaries['translate']; ?>';
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
    <form name="form1" action="/word/bulk-translate" method="post">
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
    $cnt = 0;
    foreach ($terms as $record) {
        $cnt++;
        $value = \tohtml($record['word']);
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
            <input type="hidden" name="term[<?php echo $cnt ?>][lg]" value="<?php echo \tohtml($record['Ti2LgID']) ?>" />
        </td>
        </tr>
        <?php
    }
    ?>
    </table>
    <input type="hidden" name="tid" value="<?php echo $tid ?>" />
    <?php if ($nextOffset !== null) : ?>
    <input type="hidden" name="offset" value="<?php echo $nextOffset ?>" />
    <input type="hidden" name="sl" value="<?php echo $sl ?>" />
    <input type="hidden" name="tl" value="<?php echo $tl ?>" />
    <?php endif; ?>
    </form>
