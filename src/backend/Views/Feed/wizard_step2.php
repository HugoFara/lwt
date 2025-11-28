<?php

/**
 * Feed Wizard Step 2 - Select Article Text
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

use Lwt\Core\Http\InputValidator;

?>
<script type="text/javascript">
    // Extend jQuery
    $(function() {
        jQuery.fn.get_adv_xpath = extend_adv_xpath
    });

    filter_Array = [];
    // Prepare the page
    $(lwt_feed_wizard.prepareInteractions);

    if (<?php echo json_encode($wizardData['hide_images'] == 'yes'); ?>) {
        $(function () {
            $("img").not($("#lwt_header").find("*")).css("display","none");
        });
    }
    const lwt_wiz_select_test = {
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

        changeHideImage: function() {
            if ($(this).val() == 'no')
                $('img').not($('#lwt_header').find('*')).css('display', '');
            else
                $('img').not($('#lwt_header').find('*')).css('display', 'none');
            return false;
        },

        clickBack: function() {
            location.href = '/feeds/wizard?step=1&amp;select_mode=' +
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

        changeSelectedFeed: function() {
            var html = $('#lwt_sel').html();
            $('input[name=\'html\']').val(html);
            document.lwt_form1.submit();
        },

        changeArticleSection: function() {
            var html = $('#lwt_sel').html();
            $('input[name=\'html\']').val(html);
            document.lwt_form1.submit();
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
</script>
<div id="lwt_header">
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
        <div id="adv" style="display: none;">
        <button onclick="lwt_wiz_select_test.clickCancel()">Cancel</button>
        <button id="adv_get_button">Get</button>
    </div>
    <div id="settings" style="display: none;">
        <p><b>Feed Wizard | Settings</b></p>
        <div style="margin-left:150px;text-align:left">
            Selection Mode:
            <select name="select_mode" onchange="lwt_wiz_select_test.changeSelectMode()">
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
                Hide Images: <select name="hide_images" onchange="lwt_wiz_select_test.changeHideImage()">
                <option value="yes"<?php if ($wizardData['hide_images'] == 'yes') {
                    echo ' selected';
                                   }?>>Yes</option>
                <option value="no"<?php if ($wizardData['hide_images'] == 'no') {
                    echo ' selected';
                                  }?>>No</option>
            </select>
        </div>
        <button style="position:relative;left:150px;" onclick="$('#settings').hide();return false;">
            OK
        </button>
    </div>
    <div id="lwt_container">
        <?php echo_lwt_logo();?>
        <h1>Feed Wizard | Step 2 - Select Article Text
        <a href="docs/info.html#feed_wizard" target="_blank">
            <img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img>
        </a>
        </h1>
        <ol id="lwt_sel" style="margin-left:77px">
            <?php
            if (InputValidator::has('html')) {
                echo InputValidator::getString('html', '', false);
            }
            if (InputValidator::has('article_tags') || InputValidator::has('edit_feed')) {
                echo $wizardData['article_tags'];
            } ?>
        </ol>
        <table class="tab2" style="margin-left:77px" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1" style="text-align:left">Name: </td>
                <td class="td1">
                    <input class="notempty" size="50" type="text" name="NfName"
                    value="<?php echo htmlspecialchars($wizardData['feed']['feed_title'], ENT_COMPAT); ?>" />
                    <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                    alt="Field must not be empty" />
                </td>
            </tr>
            <tr>
                <td class="td1" style="text-align:left">Newsfeed url: </td>
                <td class="td1" style="text-align:left">
                    <?php echo tohtml($wizardData['rss_url']); ?>
                </td>
            </tr>
            <tr>
                <td class="td1" style="text-align:left">Article Source: </td>
                <td class="td1" style="text-align:left">
                    <select name="NfArticleSection"
                    onchange="lwt_wiz_select_test.changeArticleSection()">
                        <option value="" <?php
                        if (
                            !array_key_exists('feed_text', $wizardData['feed']) ||
                            $wizardData['feed']['feed_text'] == ''
                        ) {
                            echo ' selected="selected"';
                        }
                        ?>>
                            Webpage Link
                        </option>
                        <?php
                        $sources = array('description','encoded','content');
                        foreach ($sources as $source) {
                            if (isset($wizardData['feed'][0][$source])) {
                                echo '<option value="' . $source . '"';
                                if (
                                    array_key_exists('feed_text', $wizardData['feed']) &&
                                    $wizardData['feed']['feed_text'] == $source
                                ) {
                                    echo ' selected="selected"';
                                }
                                echo '>' . $source . '</option>';
                            }
                        }
                        ?>
                            </select>
                            <?php echo '(' . $wizardData['detected_feed'] . ')'; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php // Step 2 Controls ?>
    <table style="width:100%;">
        <tr>
            <td>
                <input type="hidden" name="rss_url"
                value="<?php echo tohtml($wizardData['rss_url']); ?>" />
                <input type="button" value="Cancel"
                onclick="location.href='/feeds/edit?del_wiz=1';return false;" />
            </td>
            <td>
                <span>
                    <select name="selected_feed"
                    style="width:250px;max-width:200px;"
                    onchange="lwt_wiz_select_test.changeSelectedFeed()">
                        <?php
                        $current_host = '';
                        $current_status = '';
                        for ($i = 0; $i < $feedLen; $i++) {
                            $feed_host = parse_url(
                                $wizardData['feed'][$i]['link'],
                                PHP_URL_HOST
                            );
                            if (gettype($feed_host) != 'string') {
                                my_die('$feed_host is of type ' . gettype($feed_host));
                            }
                            if (!isset($wizardData['host'][$feed_host])) {
                                $wizardData['host'][$feed_host] = '-';
                            }
                            echo '<option value="' . $i . '" title="' . tohtml($wizardData['feed'][$i]['title']) . '"';
                            if ($i == $wizardData['selected_feed']) {
                                echo ' selected="selected"';
                                $current_host = $feed_host;
                                $current_status = $wizardData['host'][$feed_host];
                            }
                            echo '>' .
                            (
                                (
                                    isset($wizardData['feed'][$i]['html']) ||
                                    $i == $wizardData['selected_feed']
                                ) ? '&#9658; ' : '- '
                            ) .
                            ($i + 1)  . ' ' . $wizardData['host'][$feed_host] . '&nbsp;host: ' .
                            $feed_host . '</option>';
                        }
                        ?>
                    </select>
                    <input type="hidden" name="host_name" value="<?php echo $current_host ?>" />
                    <?php if (count($wizardData['host']) > 1) { ?>
                    <select id="host_status" name="host_status">
                        <option value="&nbsp;-&nbsp;" <?php
                        if ($current_status == '&nbsp;-&nbsp;') {
                            echo 'selected="selected"';
                        }
                        ?>>
                        &nbsp;-&nbsp;
                    </option>
                    <option value="&#9734;" <?php
                    if ($current_status == '&#9734;') {
                        echo 'selected="selected"';
                    }
                    ?>>&#9734;</option>
                    <option value="&#9733;" <?php
                    if ($current_status == '&#9733;') {
                        echo 'selected="selected"';
                    }
                    ?>>&#9733;</option>
                </select>
                        <?php
                    }
                    ?>
            </span>
        </td>
        <td style="width:270px;text-align: right;">
            <select name="mark_action" id="mark_action">
                <option value="">[Click On Text]</option>
            </select>
            <button id="get_button" name="button" disabled>Get</button>
            <img src="/assets/icons/wrench-screwdriver.png" title="Settings" alt="-"
            onclick="$('#settings').show();return false;" />
        </td>
        <td>
            <span>
                <input type="button" value="Back"
                onclick="lwt_wiz_select_test.clickBack()" />
                <button id="next">Next</button>
            </span>
        </td>
        <td style="width:63px"></td>
    </tr>
</table>
<button style="position:absolute;right:10px;top:10px"
onclick="lwt_wiz_select_test.clickMinMax()">
    min/max
</button>
<input type="hidden" name="step" value="2" />
<input type="hidden" name="html" />
<input type="hidden" id="article_tags" name="article_tags" disabled />
<input type="hidden" name="maxim" value="1" />
    </form>
</div>
<br /><p id="lwt_last"></p>
<?php echo $feedHtml; ?>
<script type="text/javascript">
    if (<?php echo json_encode($wizardData['maxim'] == 0); ?>) {
        $(lwt_wiz_select_test.setMaxim);
    }
</script>
