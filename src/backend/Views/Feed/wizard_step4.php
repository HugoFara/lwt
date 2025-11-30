<?php

/**
 * Feed Wizard Step 4 - Edit Options
 *
 * Variables expected:
 * - $wizardData: array wizard session data
 * - $languages: array of language records
 * - $autoUpdI: string|null auto update interval value
 * - $autoUpdV: string|null auto update interval unit
 * - $service: FeedService instance
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
<div id="wizard-step4-config"
    data-edit-feed-id="<?php echo isset($wizardData['edit_feed']) ? (int)$wizardData['edit_feed'] : ''; ?>"
    style="display: none;"></div>
<script type="text/javascript">
(function() {
    function init() {
        var configEl = document.getElementById('wizard-step4-config');
        if (!configEl || typeof window.initWizardStep4 !== 'function') return;

        var editFeedId = configEl.dataset.editFeedId;
        window.initWizardStep4({
            editFeedId: editFeedId ? parseInt(editFeedId, 10) : null
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
<form class="validate" action="/feeds/edit" method="post">
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1">Language: </td>
            <td class="td1">
                <select name="NfLgID" class="notempty">
                    <option value="">[Select...]</option>
                <?php
                foreach ($languages as $lang) {
                    echo '<option value="' . $lang['LgID'] . '"';
                    if ($wizardData['lang'] === $lang['LgID']) {
                        echo ' selected="selected"';
                    }
                    echo '>' . $lang['LgName'] . '</option>';
                }
                ?>
                </select>
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1">Name: </td>
            <td class="td1">
                <input class="notempty" style="width:95%" type="text" name="NfName"
                value="<?php echo htmlspecialchars($wizardData['feed']['feed_title'], ENT_COMPAT); ?>" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1">Newsfeed url: </td>
            <td class="td1">
                <input class="notempty" style="width:95%" type="text" name="NfSourceURI"
                value="<?php echo htmlspecialchars($wizardData['rss_url']); ?>" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1">Article Section: </td>
            <td class="td1">
                <input class="notempty" style="width:95%" type="text" name="NfArticleSectionTags"
                value="<?php echo htmlspecialchars(preg_replace('/[ ]+/', ' ', trim($wizardData['redirect'] . ($wizardData['article_section'] ?? '')))); ?>" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
            <td class="td1">Filter Tags: </td>
            <td class="td1">
                <input type="text" style="width:95%" name="NfFilterTags"
                value="<?php echo htmlspecialchars(preg_replace('/[ ]+/', ' ', InputValidator::getString('html'))); ?>" />
            </td>
        </tr>
        <tr>
            <td class="td1">Options: </td>
            <td class="td1">
                <?php // Options table ?>
<table style="width:100%">
    <tr>
        <td style="width:35%">
            <input type="checkbox" name="edit_text"<?php
            if ($service->getNfOption($wizardData['options'], 'edit_text') !== null) {
                echo ' checked="checked"';
            } ?>
            /> Edit Text
        </td>
        <td>
            <input type="checkbox" name="c_autoupdate"<?php
            if ($autoUpdI !== null) {
                echo ' checked="checked"';
            } ?>
            /> Auto Update Interval:
            <input class="posintnumber<?php
            if ($service->getNfOption($wizardData['options'], 'autoupdate') !== null) {
                echo ' notempty';
            }
            ?>" data_info="Auto Update Interval" type="number"
            min="0" size="4" name="autoupdate" value="<?php echo $autoUpdI; ?>" <?php
            if ($autoUpdI == null) {
                echo ' disabled';
            } ?> />
            <select name="autoupdate" value="<?php echo $autoUpdV; ?>" <?php if ($autoUpdV == null) {
                echo ' disabled';
            } ?>>
                <option value="h"<?php if ($autoUpdV == 'h') {
                    echo ' selected="selected"';
                                 }?>>Hour(s)</option>
                <option value="d"<?php if ($autoUpdV == 'd') {
                    echo ' selected="selected"';
                                 }?>>Day(s)</option>
                <option value="w"<?php if ($autoUpdV == 'w') {
                    echo ' selected="selected"';
                                 }?>>Week(s)</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" name="c_max_links"<?php if ($service->getNfOption($wizardData['options'], 'max_links') !== null) {
                echo ' checked="checked"';
                                                     } ?> /> Max. Links:
            <input class="<?php if ($service->getNfOption($wizardData['options'], 'max_links') !== null) {
                echo 'notempty ';
                          } ?>posintnumber maxint_300" data_info="Max. Links"
            type="number" min="0" max="300" size="4" name="max_links"
            value="<?php echo $service->getNfOption($wizardData['options'], 'max_links'); ?>" <?php
            if ($service->getNfOption($wizardData['options'], 'max_links') == null) {
                echo ' disabled';
            } ?> />
        </td>
        <td>
            <input type="checkbox" name="c_charset"<?php if ($service->getNfOption($wizardData['options'], 'charset') !== null) {
                echo ' checked="checked"';
                                                   } ?> /> Charset: <input <?php if ($service->getNfOption($wizardData['options'], 'charset') !== null) {
    echo 'class="notempty" ';
                                                   } ?>type="text" data_info="Charset" size="20" name="charset" value="<?php
                echo $service->getNfOption($wizardData['options'], 'charset');
?>" <?php
if ($service->getNfOption($wizardData['options'], 'charset') == null) {
    echo ' disabled';
} ?> />
        </td>
    </tr>
    <tr>
        <td>
            <input type="checkbox" name="c_max_texts"<?php if ($service->getNfOption($wizardData['options'], 'max_texts') !== null) {
                echo ' checked="checked"';
                                                     } ?> />
            Max. Texts:
            <input class="<?php if ($service->getNfOption($wizardData['options'], 'max_texts') !== null) {
                echo 'notempty ';
                          } ?>posintnumber maxint_30" data_info="Max. Texts"
            type="number" min="0" max="30" size="4" name="max_texts"
            value="<?php echo $service->getNfOption($wizardData['options'], 'max_texts'); ?>" <?php
            if ($service->getNfOption($wizardData['options'], 'max_texts') == null) {
                echo ' disabled';
            } ?> />
        </td>
        <td>
            <input type="checkbox" name="c_tag"<?php if ($service->getNfOption($wizardData['options'], 'tag') !== null) {
                echo ' checked="checked"';
                                               } ?> />
            Tag:
            <input <?php if ($service->getNfOption($wizardData['options'], 'tag') !== null) {
                echo 'class="notempty" ';
                   } ?>type="text" data_info="Tag" size="20" name="tag"
            value="<?php echo $service->getNfOption($wizardData['options'], 'tag'); ?>"
            <?php if ($service->getNfOption($wizardData['options'], 'tag') == null) {
                echo ' disabled';
            } ?> />
        </td>
    </tr>
</table>
            </td>
        </tr>
    </table>
    <?php if (isset($wizardData['edit_feed'])) {
        echo '<input type="hidden" name="NfID" value="' . $wizardData['edit_feed'] . '" />';
    }?>
    <input type="button" value="Cancel" data-action="wizard-step4-cancel" />
    <input type="hidden" name="NfOptions" value="" />
    <input type="hidden" name="article_source"
    value="<?php echo htmlspecialchars($wizardData['feed']['feed_text']); ?>" />
    <input type="hidden" name="save_feed" value="1" />
    <input type="button" value="Back" data-action="wizard-step4-back" />
    <input type="submit" value="Save" data-action="wizard-step4-submit" />
</form>
