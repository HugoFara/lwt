<?php declare(strict_types=1);
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
use Lwt\Core\Utils\ErrorHandler;
use Lwt\View\Helper\IconHelper;

?>
<div id="lwt_header"
     data-hide-images="<?php echo $wizardData['hide_images'] == 'yes' ? 'true' : 'false'; ?>"
     data-is-minimized="<?php echo $wizardData['maxim'] == 0 ? 'true' : 'false'; ?>"
     x-data="{
         settingsOpen: false,
         isMinimized: <?php echo $wizardData['maxim'] == 0 ? 'true' : 'false'; ?>
     }">
    <form name="lwt_form1" class="validate" action="/feeds/wizard" method="post">
        <!-- Advanced Mode Buttons (shown when minimized) -->
        <div id="adv" class="buttons mb-2" x-show="isMinimized" x-cloak>
            <button type="button" class="button is-small is-danger is-outlined" data-action="wizard-cancel">
                Cancel
            </button>
            <button id="adv_get_button" class="button is-small is-info">
                Get
            </button>
        </div>

        <!-- Settings Modal -->
        <div class="modal" :class="settingsOpen ? 'is-active' : ''">
            <div class="modal-background" @click="settingsOpen = false"></div>
            <div class="modal-card">
                <header class="modal-card-head">
                    <p class="modal-card-title">
                        <span class="icon mr-2">
                            <?php echo IconHelper::render('settings', ['alt' => 'Settings']); ?>
                        </span>
                        Feed Wizard Settings
                    </p>
                    <button class="delete" aria-label="close" type="button" @click="settingsOpen = false"></button>
                </header>
                <section class="modal-card-body">
                    <div class="field">
                        <label class="label">Selection Mode</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="select_mode" data-action="wizard-select-mode">
                                    <option value="0"<?php if ($wizardData['select_mode'] == '0') echo ' selected'; ?>>Smart Selection</option>
                                    <option value="all"<?php if ($wizardData['select_mode'] == 'all') echo ' selected'; ?>>Get All Attributes</option>
                                    <option value="adv"<?php if ($wizardData['select_mode'] == 'adv') echo ' selected'; ?>>Advanced Selection</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label">Hide Images</label>
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select name="hide_images" data-action="wizard-hide-images">
                                    <option value="yes"<?php if ($wizardData['hide_images'] == 'yes') echo ' selected'; ?>>Yes</option>
                                    <option value="no"<?php if ($wizardData['hide_images'] == 'no') echo ' selected'; ?>>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
                <footer class="modal-card-foot">
                    <button type="button" class="button is-success" @click="settingsOpen = false">OK</button>
                </footer>
            </div>
        </div>

        <!-- Keep old settings div for JS compatibility -->
        <div id="settings" style="display: none;">
            <button class="settings-ok" data-action="wizard-settings-close">OK</button>
        </div>

        <div id="lwt_container" x-show="!isMinimized">
            <?php echo \Lwt\View\Helper\PageLayoutHelper::buildLogo(); ?>

            <h1 class="title is-4 is-flex is-align-items-center">
                <span class="icon mr-2">
                    <?php echo IconHelper::render('wand-2', ['alt' => 'Wizard']); ?>
                </span>
                Feed Wizard - Step 2: Select Article Text
                <a href="docs/info.html#feed_wizard" target="_blank" class="ml-2">
                    <?php echo IconHelper::render('help-circle', ['title' => 'Help', 'alt' => 'Help']); ?>
                </a>
            </h1>

            <!-- Steps indicator -->
            <div class="steps is-small mb-4">
                <div class="step-item is-completed is-success">
                    <div class="step-marker">1</div>
                    <div class="step-details"><p class="step-title">Feed URL</p></div>
                </div>
                <div class="step-item is-active is-primary">
                    <div class="step-marker">2</div>
                    <div class="step-details"><p class="step-title">Select Article</p></div>
                </div>
                <div class="step-item">
                    <div class="step-marker">3</div>
                    <div class="step-details"><p class="step-title">Filter Text</p></div>
                </div>
                <div class="step-item">
                    <div class="step-marker">4</div>
                    <div class="step-details"><p class="step-title">Save</p></div>
                </div>
            </div>

            <!-- Selected elements list -->
            <div class="box has-background-light mb-4">
                <p class="is-size-7 has-text-grey mb-2">Selected elements:</p>
                <ol id="lwt_sel" class="ml-4">
                    <?php
                    if (InputValidator::has('html')) {
                        echo InputValidator::getString('html', '', false);
                    }
                    if (InputValidator::has('article_tags') || InputValidator::has('edit_feed')) {
                        echo $wizardData['article_tags'];
                    }
                    ?>
                </ol>
            </div>

            <!-- Feed Info -->
            <div class="box">
                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label">Name</label>
                    </div>
                    <div class="field-body">
                        <div class="field has-addons">
                            <div class="control is-expanded">
                                <input class="input notempty"
                                       type="text"
                                       name="NfName"
                                       value="<?php echo htmlspecialchars($wizardData['feed']['feed_title'], ENT_COMPAT); ?>"
                                       required />
                            </div>
                            <div class="control">
                                <span class="icon has-text-danger" title="Field must not be empty">
                                    <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label">Newsfeed URL</label>
                    </div>
                    <div class="field-body">
                        <div class="field">
                            <p class="control">
                                <span class="has-text-grey-dark is-size-7">
                                    <?php echo htmlspecialchars($wizardData['rss_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label">Article Source</label>
                    </div>
                    <div class="field-body">
                        <div class="field has-addons">
                            <div class="control">
                                <div class="select">
                                    <select name="NfArticleSection" data-action="wizard-article-section">
                                        <option value=""<?php
                                        if (!array_key_exists('feed_text', $wizardData['feed']) || $wizardData['feed']['feed_text'] == '') {
                                            echo ' selected';
                                        }
                                        ?>>Webpage Link</option>
                                        <?php
                                        $sources = array('description', 'encoded', 'content');
                                        foreach ($sources as $source) {
                                            if (isset($wizardData['feed'][0][$source])) {
                                                echo '<option value="' . $source . '"';
                                                if (array_key_exists('feed_text', $wizardData['feed']) && $wizardData['feed']['feed_text'] == $source) {
                                                    echo ' selected';
                                                }
                                                echo '>' . $source . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="control">
                                <span class="tag is-info is-light"><?php echo '(' . $wizardData['detected_feed'] . ')'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wizard Controls -->
        <nav class="level wizard-controls mt-4">
            <div class="level-left">
                <div class="level-item">
                    <input type="hidden" name="rss_url" value="<?php echo htmlspecialchars($wizardData['rss_url'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" />
                    <button type="button" class="button is-danger is-outlined" data-action="wizard-delete-cancel">
                        Cancel
                    </button>
                </div>
            </div>

            <div class="level-item">
                <div class="field has-addons">
                    <div class="control">
                        <div class="select">
                            <select name="selected_feed" class="feed-selector" data-action="wizard-selected-feed">
                                <?php
                                $current_host = '';
                                $current_status = '';
                                for ($i = 0; $i < $feedLen; $i++) {
                                    $feed_host = parse_url($wizardData['feed'][$i]['link'], PHP_URL_HOST);
                                    if (gettype($feed_host) != 'string') {
                                        ErrorHandler::die('$feed_host is of type ' . gettype($feed_host));
                                    }
                                    if (!isset($wizardData['host'][$feed_host])) {
                                        $wizardData['host'][$feed_host] = '-';
                                    }
                                    echo '<option value="' . $i . '" title="' . htmlspecialchars($wizardData['feed'][$i]['title'] ?? '', ENT_QUOTES, 'UTF-8') . '"';
                                    if ($i == $wizardData['selected_feed']) {
                                        echo ' selected';
                                        $current_host = $feed_host;
                                        $current_status = $wizardData['host'][$feed_host];
                                    }
                                    echo '>' .
                                    ((isset($wizardData['feed'][$i]['html']) || $i == $wizardData['selected_feed']) ? '&#9658; ' : '- ') .
                                    ($i + 1) . ' ' . $wizardData['host'][$feed_host] . '&nbsp;host: ' . $feed_host . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <input type="hidden" name="host_name" value="<?php echo $current_host ?>" />
                    <?php if (count($wizardData['host']) > 1): ?>
                    <div class="control">
                        <div class="select">
                            <select id="host_status" name="host_status">
                                <option value="&nbsp;-&nbsp;"<?php if ($current_status == '&nbsp;-&nbsp;') echo ' selected'; ?>>&nbsp;-&nbsp;</option>
                                <option value="&#9734;"<?php if ($current_status == '&#9734;') echo ' selected'; ?>>&#9734;</option>
                                <option value="&#9733;"<?php if ($current_status == '&#9733;') echo ' selected'; ?>>&#9733;</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="level-item actions-cell">
                <div class="field has-addons">
                    <div class="control">
                        <div class="select">
                            <select name="mark_action" id="mark_action">
                                <option value="">[Click On Text]</option>
                            </select>
                        </div>
                    </div>
                    <div class="control">
                        <button id="get_button" name="button" class="button is-info" disabled>Get</button>
                    </div>
                    <div class="control">
                        <button type="button" class="button" @click="settingsOpen = true">
                            <?php echo IconHelper::render('settings', ['title' => 'Settings', 'alt' => 'Settings']); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="level-right">
                <div class="level-item">
                    <div class="buttons">
                        <button type="button" class="button" data-action="wizard-back">
                            <span class="icon is-small">
                                <?php echo IconHelper::render('arrow-left', ['alt' => 'Back']); ?>
                            </span>
                            <span>Back</span>
                        </button>
                        <button id="next" class="button is-primary">
                            <span>Next</span>
                            <span class="icon is-small">
                                <?php echo IconHelper::render('arrow-right', ['alt' => 'Next']); ?>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <button type="button" class="button is-small wizard-minmax mt-2"
                data-action="wizard-minmax"
                @click="isMinimized = !isMinimized">
            <span class="icon is-small">
                <?php echo IconHelper::render('minimize-2', ['alt' => 'Toggle']); ?>
            </span>
            <span>min/max</span>
        </button>

        <input type="hidden" name="step" value="2" />
        <input type="hidden" name="html" />
        <input type="hidden" id="article_tags" name="article_tags" disabled />
        <input type="hidden" name="maxim" value="1" />
    </form>
</div>

<br /><p id="lwt_last"></p>
<?php echo $feedHtml; ?>
