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
<div id="lwt_header"
     data-hide-images="<?php echo $wizardData['hide_images'] == 'yes' ? 'true' : 'false'; ?>"
     data-is-minimized="<?php echo $wizardData['maxim'] == 0 ? 'true' : 'false'; ?>">
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
        <div id="adv">
        <button data-action="wizard-cancel">Cancel</button>
        <button id="adv_get_button">Get</button>
    </div>
    <div id="settings">
        <p><b>Feed Wizard | Settings</b></p>
        <div class="settings-content">
            Selection Mode:
            <select name="select_mode" data-action="wizard-select-mode">
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
                Hide Images: <select name="hide_images" data-action="wizard-hide-images">
                <option value="yes"<?php if ($wizardData['hide_images'] == 'yes') {
                    echo ' selected';
                                   }?>>Yes</option>
                <option value="no"<?php if ($wizardData['hide_images'] == 'no') {
                    echo ' selected';
                                  }?>>No</option>
            </select>
        </div>
        <button class="settings-ok" data-action="wizard-settings-close">
            OK
        </button>
    </div>
    <div id="lwt_container">
        <?php echo \Lwt\View\Helper\PageLayoutHelper::buildLogo(); ?>
        <h1>Feed Wizard | Step 2 - Select Article Text
        <a href="docs/info.html#feed_wizard" target="_blank">
            <img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img>
        </a>
        </h1>
        <ol id="lwt_sel">
            <?php
            if (InputValidator::has('html')) {
                echo InputValidator::getString('html', '', false);
            }
            if (InputValidator::has('article_tags') || InputValidator::has('edit_feed')) {
                echo $wizardData['article_tags'];
            } ?>
        </ol>
        <table class="tab2" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1 left">Name: </td>
                <td class="td1">
                    <input class="notempty" size="50" type="text" name="NfName"
                    value="<?php echo htmlspecialchars($wizardData['feed']['feed_title'], ENT_COMPAT); ?>" />
                    <img src="/assets/icons/status-busy.png" title="Field must not be empty"
                    alt="Field must not be empty" />
                </td>
            </tr>
            <tr>
                <td class="td1 left">Newsfeed url: </td>
                <td class="td1 left">
                    <?php echo tohtml($wizardData['rss_url']); ?>
                </td>
            </tr>
            <tr>
                <td class="td1 left">Article Source: </td>
                <td class="td1 left">
                    <select name="NfArticleSection"
                    data-action="wizard-article-section">
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
    <table class="wizard-controls">
        <tr>
            <td>
                <input type="hidden" name="rss_url"
                value="<?php echo tohtml($wizardData['rss_url']); ?>" />
                <input type="button" value="Cancel"
                data-action="wizard-delete-cancel" />
            </td>
            <td>
                <span>
                    <select name="selected_feed"
                    class="feed-selector"
                    data-action="wizard-selected-feed">
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
        <td class="actions-cell">
            <select name="mark_action" id="mark_action">
                <option value="">[Click On Text]</option>
            </select>
            <button id="get_button" name="button" disabled>Get</button>
            <img src="/assets/icons/wrench-screwdriver.png" title="Settings" alt="-"
            data-action="wizard-settings-open" />
        </td>
        <td>
            <span>
                <input type="button" value="Back"
                data-action="wizard-back" />
                <button id="next">Next</button>
            </span>
        </td>
        <td class="spacer-cell"></td>
    </tr>
</table>
<button class="wizard-minmax"
data-action="wizard-minmax">
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
