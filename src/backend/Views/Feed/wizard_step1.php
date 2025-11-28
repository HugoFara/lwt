<?php

/**
 * Feed Wizard Step 1 - Insert Feed URI
 *
 * Variables expected:
 * - $errorMessage: string|null error message to display
 * - $rssUrl: string|null previously entered RSS URL
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
<?php if (!empty($errorMessage)): ?>
<div class="red">
    <p>+++ ERROR: PLEASE CHECK YOUR NEWSFEED URI!!! +++</p>
</div>
<?php endif; ?>
<form class="validate" action="/feeds/wizard" method="post">
    <table class="tab2" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1">Feed URI: </td>
            <td class="td1">
                <input class="notempty" style="width:90%" type="text" name="rss_url" <?php
                if (!empty($rssUrl)) {
                    echo 'value="' . tohtml($rssUrl) . '" ';
                }?>
                />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
    </table>
    <input type="hidden" name="step" value="2" />
    <input type="hidden" name="selected_feed" value="0" />
    <input type="hidden" name="article_tags" value="1" />
    <input type="button" value="Cancel" onclick="location.href='/feeds/edit?del_wiz=1';return false;" />
    <button>Next</button>
</form>
<script type="text/javascript">
    $('h1')
    .eq(-1)
    .html(
        'Feed Wizard | Step 1 - Insert Newsfeed URI ' +
        '<a href="docs/info.html#feed_wizard" target="_blank">' +
        '<img alt="Help" title="Help" src="/assets/icons/question-frame.png"></img></a>'
    )
    .css('text-align','center');
</script>
