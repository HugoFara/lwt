<?php declare(strict_types=1);
/**
 * Feed Wizard Step 1 - Insert Feed URI
 *
 * Variables expected:
 * - $errorMessage: string|null error message to display
 * - $rssUrl: string|null previously entered RSS URL
 *
 * JavaScript moved to src/frontend/js/feeds/feed_wizard_common.ts
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
<script type="application/json" id="wizard-step1-config"><?php echo json_encode(['step' => 1]); ?></script>

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
                    echo 'value="' . htmlspecialchars($rssUrl ?? '', ENT_QUOTES, 'UTF-8') . '" ';
                }?>
                />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
    </table>
    <input type="hidden" name="step" value="2" />
    <input type="hidden" name="selected_feed" value="0" />
    <input type="hidden" name="article_tags" value="1" />
    <input type="button" value="Cancel" data-action="wizard-cancel" data-url="/feeds/edit?del_wiz=1" />
    <button>Next</button>
</form>
