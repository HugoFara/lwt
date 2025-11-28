<?php

/**
 * New Feed Form View
 *
 * Variables expected:
 * - $languages: array of language data [{LgID, LgName}, ...]
 * - $currentLang: int current language ID (for pre-selection)
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
<h2>New Feed</h2>
<a href="/feeds?page=1">My Feeds</a>
<span class="nowrap"></span>
<a href="/feeds/wizard?step=1">
    <img src="/assets/icons/wizard.png" title="new_feed_wizard" alt="new_feed_wizard" style="height: 20px;"/>
    New Feed Wizard
</a>
<br></br>
<form class="validate" action="/feeds/edit" method="post">
<table class="tab2" cellspacing="0" cellpadding="5">
<tr><td class="td1">Language: </td><td class="td1"><select name="NfLgID">
    <?php foreach ($languages as $lang): ?>
    <option value="<?php echo $lang['LgID']; ?>"<?php if ($currentLang === (int)$lang['LgID']) echo ' selected="selected"'; ?>><?php echo $lang['LgName']; ?></option>
    <?php endforeach; ?>
</select></td></tr>
<tr><td class="td1">
Name: </td><td class="td1">
    <input class="notempty" style="width:95%" type="text" name="NfName" />
<img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td></tr>
<tr><td class="td1">Newsfeed url: </td>
<td class="td1"><input class="notempty" style="width:95%" type="text" name="NfSourceURI" />
<img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td></tr>
<tr><td class="td1">Article Section: </td>
<td class="td1"><input class="notempty" style="width:95%" type="text" name="NfArticleSectionTags" />
<img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
</td></tr>
<tr><td class="td1">Filter Tags: </td>
<td class="td1"><input type="text" style="width:95%" name="NfFilterTags" /></td></tr>
<tr><td class="td1">Options: </td>
<td class="td1"><table style="width:100%">
<tr><td style="width:35%"><input type="checkbox" name="edit_text" checked="checked" /> Edit Text </td>
<td>
    <input type="checkbox" name="c_autoupdate" /> Auto Update Interval:
    <input class="posintnumber" data_info="Auto Update Interval" type="number" min="0" size="4" name="autoupdate" disabled />
    <select name="autoupdate" disabled><option value="h">Hour(s)</option>
    <option value="d">Day(s)</option><option value="w">Week(s)</option></select>
</td></tr>
<tr><td>
    <input type="checkbox" name="c_max_links" /> Max. Links:
    <input class="posintnumber maxint_300" data_info="Max. Links" type="number" min="0" max="300" size="4" name="max_links" disabled /></td>
    <td><input type="checkbox" name="c_charset" /> Charset:
    <input type="text" data_info="Charset" size="20" name="charset" disabled /> </td></tr>
<tr><td>
    <input type="checkbox" name="c_max_texts" /> Max. Texts:
    <input class="posintnumber maxint_30" data_info="Max. Texts" type="number" min="0" max="30" size="4" name="max_texts" disabled /></td>
    <td>
        <input type="checkbox" name="c_tag" /> Tag:
        <input type="text" data_info="Tag" size="20" name="tag" disabled />
    </td>
</tr>
<tr><td colspan="2">
    <input type="checkbox" name="c_article_source" /> Article Source:
    <input data_info="Article Source" type="text" size="20" name="article_source" disabled /></td></tr>
</table></td></tr>
</table><input type="submit" value="Save" />
<input type="hidden" name="NfOptions" value="" />
<input type="hidden" name="save_feed" value="1" />
<input type="button" value="Cancel" onclick="location.href='/feeds/edit';" />
</form>
<script type="text/javascript">
$('[name^="c_"]').change(function(){
    if(this.checked){
        $(this).parent().children('input[type="text"]')
        .removeAttr('disabled').addClass("notempty");
        $(this).parent().find('select').removeAttr('disabled');
    } else {
        $(this).parent().children('input[type="text"]')
        .attr('disabled','disabled').removeClass("notempty");
        $(this).parent().find('select').attr('disabled','disabled');
    }
});
$('[type="submit"]').on('click', function(){
    var str;
    str=$('[name="edit_text"]:checked').length > 0?"edit_text=1,":"";
    $('[name^="c_"]').each(function(){
        str+=this.checked ? $(this).parent().children('input[type="text"]')
        .attr('name') + '='
        + $(this).parent().children('input[type="text"]').val()
        + ($(this).attr('name')=='c_autoupdate' ? $(this).parent().find('select').val() + ',' : ','): '';
    });
    $('input[name="NfOptions"]').val(str);
});
</script>
