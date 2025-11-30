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
<div id="wizard-step3-config"
    data-article-selector="<?php echo htmlspecialchars((string)($wizardData['article_selector'] ?? ''), ENT_QUOTES); ?>"
    data-hide-images="<?php echo $wizardData['hide_images'] == 'yes' ? 'true' : 'false'; ?>"
    data-is-minimized="<?php echo $wizardData['maxim'] == 0 ? 'true' : 'false'; ?>"
    style="display: none;"></div>
<div id="lwt_header">
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
    <div id="adv" style="display: none;">
    <button data-action="wizard-step3-cancel">Cancel</button>
    <button id="adv_get_button">Get</button>
</div>
<div id="settings" style="display: none;">
    <p><b>Feed Wizard | Settings</b></p>
    <div style="margin-left:150px;text-align:left">
        Selection Mode:
        <select name="select_mode" data-action="wizard-step3-select-mode">
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
        <select name="hide_images" data-action="wizard-step3-hide-images">
            <option value="yes"<?php if ($wizardData['hide_images'] == 'yes') {
                echo ' selected';
                               }?>>Yes</option>
            <option value="no"<?php if ($wizardData['hide_images'] == 'no') {
                echo ' selected';
                              }?>>No</option>
        </select>
    </div>
    <button style="position:relative;left:150px;" data-action="wizard-settings-close">OK</button>
    </div>
    <div id="lwt_container">
        <?php echo \Lwt\View\Helper\PageLayoutHelper::buildLogo(); ?>
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
                <input type="button" value="Cancel" data-action="wizard-cancel" data-url="/feeds/edit?del_wiz=1" />
            </td>
            <td>
                <span>
                    <select name="selected_feed" style="width:250px;max-width:200px;"
                    data-action="wizard-step3-selected-feed">
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
                data-action="wizard-settings-open" style="cursor:pointer;" />
            </td>
            <td>
                <span>
                    <input type="button" value="Back"
                    data-action="wizard-step3-back" />
                    <button id="next">Next</button>
                </span>
            </td>
            <td style="width:63px"></td>
        </tr>
    </table>
    <button style="position:absolute;right:10px;top:10px"
    data-action="wizard-step3-minmax">
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
