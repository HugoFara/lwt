<?php declare(strict_types=1);
/**
 * Edit Feed Form View
 *
 * Variables expected:
 * - $feed: array feed data from database
 * - $languages: array of language data [{LgID, LgName}, ...]
 * - $options: array parsed feed options
 * - $autoUpdateInterval: string|null auto-update interval value
 * - $autoUpdateUnit: string|null auto-update unit (h/d/w)
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
<h2>
    Edit Feed
    <a target="_blank" href="docs/info.html#new_feed">
        <img src="/assets/icons/question-frame.png" title="Help" alt="Help" />
    </a>
</h2>
<a href="/feeds?page=1"> My Feeds</a>
<span class="nowrap"></span>
<a href="/feeds/wizard?step=2&amp;edit_feed=<?php echo $feed['NfID']; ?>">
<img src="/assets/icons/wizard.png" title="feed_wizard" alt="feed_wizard" />Feed Wizard</a>
<form class="validate" action="/feeds/edit" method="post">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr>
    <td class="td1">Language: </td>
    <td class="td1">
        <select name="NfLgID">
    <?php foreach ($languages as $lang): ?>
        <option value="<?php echo $lang['LgID']; ?>"<?php if ($feed['NfLgID'] === $lang['LgID']) echo ' selected="selected"'; ?>><?php echo $lang['LgName']; ?></option>
    <?php endforeach; ?>
        </select>
    </td>
</tr>
<tr>
    <td class="td1">Name: </td>
    <td class="td1">
        <input class="notempty feed-form-input" type="text" name="NfName"
        value="<?php echo tohtml($feed['NfName']); ?>" />
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
</tr>
<tr>
    <td class="td1">Newsfeed url: </td>
    <td class="td1">
        <input class="notempty feed-form-input" type="text" name="NfSourceURI"
        value="<?php echo tohtml($feed['NfSourceURI']); ?>" />
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
</tr>
<tr>
    <td class="td1">Article Section: </td>
    <td class="td1">
        <input class="notempty feed-form-input" type="text"
        name="NfArticleSectionTags" value="<?php echo tohtml($feed['NfArticleSectionTags']); ?>" />
        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
    </td>
</tr>
<tr>
    <td class="td1">Filter Tags: </td>
    <td class="td1">
        <input type="text" class="feed-form-input" name="NfFilterTags"
        value="<?php echo tohtml($feed['NfFilterTags']); ?>" />
    </td>
</tr>
<tr>
    <td class="td1">Options: </td>
    <td class="td1">
        <table class="feed-options-table">
        <tr>
            <td class="option-label">
            <input type="checkbox" name="edit_text"<?php
            if (isset($options['edit_text'])) echo ' checked="checked"';
            ?> />
            Edit Text
        </td>
<td>
    <input type="checkbox" name="c_autoupdate"<?php if ($autoUpdateInterval !== null) echo ' checked="checked"'; ?> />
Auto Update Interval: <input class="posintnumber<?php if ($autoUpdateInterval !== null) echo ' notempty'; ?>" data_info="Auto Update Interval" type="number" min="0" size="4" name="autoupdate" value="<?php echo $autoUpdateInterval; ?>"<?php if ($autoUpdateInterval === null) echo ' disabled'; ?> />
<select name="autoupdate"<?php if ($autoUpdateUnit === null) echo ' disabled'; ?>>
<option value="h"<?php if ($autoUpdateUnit === 'h') echo ' selected="selected"'; ?>>Hour(s)</option>
<option value="d"<?php if ($autoUpdateUnit === 'd') echo ' selected="selected"'; ?>>Day(s)</option>
<option value="w"<?php if ($autoUpdateUnit === 'w') echo ' selected="selected"'; ?>>Week(s)</option>
</select>
</td>
</tr>
<tr>
    <td>
        <input type="checkbox" name="c_max_links"<?php if (isset($options['max_links'])) echo ' checked="checked"'; ?> />
Max. Links: <input class="<?php if (isset($options['max_links'])) echo 'notempty '; ?>posintnumber maxint_300" data_info="Max. Links" type="number" min="0" max="300" size="4" name="max_links" value="<?php echo $options['max_links'] ?? ''; ?>"<?php if (!isset($options['max_links'])) echo ' disabled'; ?> />
</td>
<td>
    <input type="checkbox" name="c_charset"<?php if (isset($options['charset'])) echo ' checked="checked"'; ?> />
Charset: <input <?php if (isset($options['charset'])) echo 'class="notempty" '; ?>type="text" data_info="Charset" size="20" name="charset" value="<?php echo $options['charset'] ?? ''; ?>"<?php if (!isset($options['charset'])) echo ' disabled'; ?> />
</td>
</tr>
<tr>
    <td>
        <input type="checkbox" name="c_max_texts"<?php if (isset($options['max_texts'])) echo ' checked="checked"'; ?> />
Max. Texts:
<input class="<?php if (isset($options['max_texts'])) echo 'notempty '; ?>posintnumber maxint_30" data_info="Max. Texts" type="number" min="0" max="30"
size="4" name="max_texts"
value="<?php echo $options['max_texts'] ?? ''; ?>"<?php if (!isset($options['max_texts'])) echo ' disabled'; ?> />
</td>
<td>
    <input type="checkbox" name="c_tag"<?php if (isset($options['tag'])) echo ' checked="checked"'; ?> />
       Tag: <input <?php if (isset($options['tag'])) echo 'class="notempty" '; ?>type="text" data_info="Tag" size="20" name="tag" value="<?php echo $options['tag'] ?? ''; ?>"<?php if (!isset($options['tag'])) echo ' disabled'; ?> />
</td>
</tr>
<tr>
    <td colspan="2">
    <input type="checkbox" name="c_article_source"<?php if (isset($options['article_source'])) echo ' checked="checked"'; ?> />
Article Source: <input class="<?php if (isset($options['article_source'])) echo 'notempty '; ?>" data_info="Article Source" type="text" size="20" name="article_source" value="<?php echo $options['article_source'] ?? ''; ?>"<?php if (!isset($options['article_source'])) echo ' disabled'; ?> />
</td>
</tr>
</table>
</td>
</tr>
</table>
<input type="submit" value="Update" />
<input type="hidden" name="NfID" value="<?php echo tohtml($feed['NfID']); ?>" />
<input type="button" value="Cancel" data-action="navigate" data-url="/feeds/edit" />
<input type="hidden" name="NfOptions" value="" />
<input type="hidden" name="update_feed" value="1" />
</form>
<!-- Feed form interactions handled by feeds/feed_form.ts -->
