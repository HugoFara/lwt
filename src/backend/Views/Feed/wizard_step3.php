<?php

/**
 * Feed Wizard Step 3 - Filter Text
 *
 * Variables expected:
 * - $wizardData: array wizard session data
 * - $feedLen: int number of feed items
 * - $feedHtml: string HTML content of the selected feed item
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

namespace Lwt\Views\Feed;

?>
<script type="text/javascript">
    filter_Array = [];
    const lwt_wizard_filter = {
        updateFilterArray: function() {
            articleSection = <?php echo json_encode((string)($wizardData['article_selector'] ?? '')); ?>;
            articleSection.trim();
            if (articleSection == '') {
                alert("Article section is empty!")
            }
            $('#lwt_header')
                .nextAll()
                .find('*')
                .addBack()
                .not(xpathQuery(articleSection).find('*').addBack())
                .not($('#lwt_header').find('*').addBack())
                .each(function() {
                    $(this).addClass('lwt_filtered_text');
                    filter_Array.push(this);
                });
        },

        hideImages: function() {
            $("img").not($("#lwt_header").find("*")).css("display", "none");
        },

        clickCancel: function() {
            $('#adv').hide();
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            return false;
        },

        changeSelectMode: function() {
            $('*').removeClass('lwt_marked_text');
            $('*[class=\'\']').removeAttr('class');
            $('#get_button').prop('disabled', true);
            $('#mark_action').empty();
            $('<option/>').val('').text('[Click On Text]').appendTo('#mark_action');
            return false;
        },

        changeHideImages: function() {
            if ($(this).val() == 'no')
                $('img').not($('#lwt_header').find('*')).css('display', '');
            else
                $('img').not($('#lwt_header').find('*')).css('display', 'none');
            return false;
        },

        changeSelectedFeed: function() {
            const html = $('#lwt_sel').html();
            $('input[name=\'html\']').val(html);
            document.lwt_form1.submit();
        },

        clickBack: function() {
            location.href = '/feeds/wizard?step=2&amp;article_tags=1&amp;maxim=' +
                $('#maxim').val() + '&amp;filter_tags=' +
                encodeURIComponent($('#lwt_sel').html()) + '&amp;select_mode=' +
                encodeURIComponent($('select[name=\'select_mode\']').val()) +
                '&amp;hide_images=' +
                encodeURIComponent($('select[name=\'hide_images\']').val());
            return false;
        },

        clickMinMax: function() {
            $('#lwt_container').toggle();
            if ($('#lwt_container').css('display') == 'none') {
                $('input[name=\'maxim\']').val(0);
            } else {
                $('input[name=\'maxim\']').val(1);
            }
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            return false;
        },

        setMaxim: function() {
            $('#lwt_container').hide();
            $('#lwt_last').css('margin-top', $('#lwt_header').height());
            if ($('#lwt_container').css('display') == 'none') {
                $('input[name=\'maxim\']').val(0);
            } else {
                $('input[name=\'maxim\']').val(1);
            }
        }
    }

    // Extend jQuery
    $(function() {
        jQuery.fn.get_adv_xpath = extend_adv_xpath
    });

    // Prepare the page
    $(lwt_feed_wizard.prepareInteractions);

    if (<?php echo json_encode($wizardData['hide_images'] == 'yes'); ?>) {
        $(lwt_wizard_filter.hideImages);
    }

    $(lwt_wizard_filter.updateFilterArray);
</script>
<div id="lwt_header">
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
    <div id="adv" style="display: none;">
    <button onclick="lwt_wizard_filter.clickCancel()">Cancel</button>
    <button id="adv_get_button">Get</button>
</div>
<div id="settings" style="display: none;">
    <p><b>Feed Wizard | Settings</b></p>
    <div style="margin-left:150px;text-align:left">
        Selection Mode:
        <select name="select_mode" onchange="lwt_wizard_filter.changeSelectMode()">
            <option value="0"<?php if ($wizardData['select_mode'] == '0') {
                echo ' selected';
                             }?>>Smart Selection</option>
            <option value="all"<?php if ($wizardData['select_mode'] == 'all') {
                echo ' selected';
                               }?>>Get All Attributes</option>
            <option value="adv"<?php if ($wizardData['select_mode'] == 'adv') {
                echo ' selected';
                               }?>>Advanced Selection</option>
        </select><br />
        Hide Images:
        <select name="hide_images" onchange="lwt_wizard_filter.changeHideImage()">
            <option value="yes"<?php if ($wizardData['hide_images'] == 'yes') {
                echo ' selected';
                               }?>>Yes</option>
            <option value="no"<?php if ($wizardData['hide_images'] == 'no') {
                echo ' selected';
                              }?>>No</option>
        </select>
    </div>
    <button style="position:relative;left:150px;" onclick="$('#settings').hide();return false;">OK</button>
    </div>
    <div id="lwt_container">
        <?php echo_lwt_logo();?>
        <h1>Feed Wizard | Step 3 - Filter Text
        <a href="docs/info.html#feed_wizard" target="_blank">
            <img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img>
        </a>
        </h1>
        <ol id="lwt_sel" style="margin-left:77px">
            <?php echo $wizardData['filter_tags']; ?>
        </ol>
        <table class="tab2" style="margin-left:77px" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1" style="text-align:left">Name: </td>
                <td class="td1" style="text-align:left">
                    <?php echo htmlspecialchars($wizardData['feed']['feed_title'], ENT_COMPAT); ?></td></tr>
            <tr>
                <td class="td1" style="text-align:left">Newsfeed url: </td>
                <td class="td1" style="text-align:left">
                    <?php echo tohtml($wizardData['rss_url']); ?>
                </td>
            </tr>
            <tr>
                <td class="td1" style="text-align:left">Article Section: </td>
                <td class="td1" style="text-align:left">
                    <?php echo tohtml($wizardData['article_section'] ?? ''); ?>
                </td>
            </tr>
            <tr>
                <td class="td1" style="text-align:left">Article Source: </td>
                <td class="td1" style="text-align:left">
                    <?php
                    if (array_key_exists('feed_text', $wizardData['feed'])) {
                        echo $wizardData['feed']['feed_text'];
                    } else {
                        echo 'Webpage Link';
                        $wizardData['feed']['feed_text'] = '';
                    } ?>
                </td>
            </tr>
        </table>
    </div>
    <?php // Step 3 Controls ?>
    <table style="width:100%;">
        <tr>
            <td>
                <input type="button" value="Cancel" onclick="location.href='/feeds/edit?del_wiz=1';return false;" />
            </td>
            <td>
                <span>
                    <select name="selected_feed" style="width:250px;max-width:200px;"
                    onchange="lwt_wizard_filter.changeSelectedFeed()">
                        <?php
                        $current_host = '';
                        $current_status = '';

                        for ($i = 0; $i < $feedLen; $i++) {
                            $feed_host = parse_url($wizardData['feed'][$i]['link']);
                            $feed_host = $feed_host['host'];
                            if (!isset($wizardData['host2'][$feed_host])) {
                                $wizardData['host2'][$feed_host] = '-';
                            }
                            echo "<option value=" . $i . " title=" . tohtml($wizardData['feed'][$i]['title']);
                            if ($i == $wizardData['selected_feed']) {
                                echo ' selected="selected"';
                                $current_host = $feed_host;
                                $current_status = $wizardData['host2'][$feed_host];
                            }
                            echo '>' . (
                                (
                                    isset($wizardData['feed'][$i]['html']) ||
                                    $i == $wizardData['selected_feed']
                                ) ?
                                    ('&#9658; ') : ('- ')
                            ) .
                                ($i + 1)  . ' ' . $wizardData['host2'][$feed_host] .
                                '&nbsp;host: ' . $feed_host . '</option>';
                        }
                        ?>
                </select>
                <input type="hidden" name="host_name" value="<?php echo $current_host ?>" />
                    <?php if (count($wizardData['host']) > 1) { ?>
                <select id="host_status" name="host_status2">
                    <option value="&nbsp;-&nbsp;" <?php if ($current_status == '&nbsp;-&nbsp;') {
                        echo 'selected="selected"';
                                                  } ?>>
                            &nbsp;-&nbsp;
                        </option>
                        <option value="&#9734;" <?php if ($current_status == '&#9734;') {
                            echo 'selected="selected"';
                                                } ?>>&#9734;</option>
                        <option value="&#9733;" <?php if ($current_status == '&#9733;') {
                            echo 'selected="selected"';
                                                } ?>>&#9733;</option>
                    </select>
                        <?php
                    } ?>
                </span>
            </td>
            <td style="width:280px;text-align: right;">
                <select name="mark_action" id="mark_action" >
                    <option value="">[Click On Text]</option>
                </select>
                <button id="filter_button" name="button" disabled>Filter</button>
                <img src="/assets/icons/wrench-screwdriver.png" title="Settings" alt="-"
                onclick="$('#settings').show();return false;" />
            </td>
            <td>
                <span>
                    <input type="button" value="Back"
                    onclick="lwt_wizard_filter.clickBack()" />
                    <button id="next">Next</button>
                </span>
            </td>
            <td style="width:63px"></td>
        </tr>
    </table>
    <button style="position:absolute;right:10px;top:10px"
    onclick="lwt_wizard_filter.clickMinMax()">
        min/max
    </button>
    <input type="hidden" id="filter_tags" name="filter_tags" disabled />
    <input type="hidden" name="html" />
    <input type="hidden" name="step" value="3" />
    <input type="hidden" id="maxim" name="maxim" value="1" />
    </form>
</div>
<br /><p id="lwt_last"></p>
<?php echo $feedHtml; ?>
<script type="text/javascript">
    if (<?php echo json_encode($wizardData['maxim'] == 0); ?>) {
        $(lwt_wizard_filter.setMaxim);
    }
</script>
