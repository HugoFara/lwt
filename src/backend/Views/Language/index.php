<?php

/**
 * Languages Index View
 *
 * Variables expected:
 * - $languages: array of language data with stats
 * - $currentLanguageId: int current language ID
 * - $message: string optional message to display
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

namespace Lwt\Views\Language;

?>
<p>
    <a href="/languages?new=1">
        <img src="/assets/icons/plus-button.png" title="New" alt="New" /> New Language ...
    </a>
</p>

<?php if (empty($languages)): ?>
<p>No languages found.</p>
<?php else: ?>

<table class="sortable tab2" cellspacing="0" cellpadding="5">
    <tr>
        <th class="th1 sorttable_nosort">Curr. Lang.</th>
        <th class="th1 sorttable_nosort">Test</th>
        <th class="th1 sorttable_nosort">Actions</th>
        <th class="th1 clickable">Language</th>
        <th class="th1 sorttable_numeric clickable">Texts, Reparse</th>
        <th class="th1 sorttable_numeric clickable">Arch. Texts</th>
        <th class="th1 sorttable_numeric clickable">News feeds<wbr />(Articles)</th>
        <th class="th1 sorttable_numeric clickable">Terms</th>
        <th class="th1 sorttable_nosort">Export Template?</th>
    </tr>

    <?php foreach ($languages as $lang): ?>
    <?php
        $isCurrent = ($currentLanguageId == $lang['id']);
        $canDelete = ($lang['textCount'] == 0 && $lang['archivedTextCount'] == 0 &&
                      $lang['wordCount'] == 0 && $lang['feedCount'] == 0);
        $currentClass = $isCurrent ? ' language-current-row' : '';
    ?>
    <tr>
        <?php if ($isCurrent): ?>
        <td class="td1 center<?php echo $currentClass; ?>">
            <img src="/assets/icons/exclamation-red.png" title="Current Language" alt="Current Language" />
        </td>
        <?php else: ?>
        <td class="td1 center">
            <a href="/admin/save-setting?k=currentlanguage&amp;v=<?php echo $lang['id']; ?>&amp;u=/languages">
                <img src="/assets/icons/tick-button.png" title="Set as Current Language" alt="Set as Current Language" />
            </a>
        </td>
        <?php endif; ?>

        <td class="td1 center<?php echo $currentClass; ?>">
            <a href="do_test.php?lang=<?php echo $lang['id']; ?>">
                <img src="/assets/icons/question-balloon.png" title="Test" alt="Test" />
            </a>
        </td>

        <td class="td1 center<?php echo $currentClass; ?>">
            <a href="/languages?chg=<?php echo $lang['id']; ?>">
                <img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" />
            </a>
            <?php if ($canDelete): ?>
            &nbsp;
            <span class="click" data-action="confirm-delete" data-url="/languages?del=<?php echo $lang['id']; ?>">
                <img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" />
            </span>
            <?php else: ?>
            &nbsp;
            <img src="/assets/icons/placeholder.png" title="Delete not possible" alt="Delete not possible" />
            <?php endif; ?>
        </td>

        <td class="td1 center<?php echo $currentClass; ?>"><?php echo tohtml($lang['name']); ?></td>

        <td class="td1 center<?php echo $currentClass; ?>">
            <?php if ($lang['textCount'] > 0): ?>
            <a href="edit_texts.php?page=1&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>"><?php echo $lang['textCount']; ?></a>
            <a href="/languages?refresh=<?php echo $lang['id']; ?>">
                <img src="/assets/icons/lightning.png" title="Reparse Texts" alt="Reparse Texts" />
            </a>
            <?php else: ?>
            0 <img src="/assets/icons/placeholder.png" title="No texts to reparse" alt="No texts to reparse" />
            <?php endif; ?>
        </td>

        <td class="td1 center<?php echo $currentClass; ?>">
            <?php if ($lang['archivedTextCount'] > 0): ?>
            <a href="edit_archivedtexts.php?page=1&amp;query=&amp;filterlang=<?php echo $lang['id']; ?>"><?php echo $lang['archivedTextCount']; ?></a>
            <?php else: ?>
            0
            <?php endif; ?>
        </td>

        <td class="td1 center<?php echo $currentClass; ?>">
            <?php if ($lang['feedCount'] > 0): ?>
            <a href="do_feeds.php?query=&amp;selected_feed=&amp;check_autoupdate=1&amp;filterlang=<?php echo $lang['id']; ?>"><?php echo $lang['feedCount']; ?> (<?php echo $lang['articleCount']; ?>)</a>
            <?php else: ?>
            0
            <?php endif; ?>
        </td>

        <td class="td1 center<?php echo $currentClass; ?>">
            <?php if ($lang['wordCount'] > 0): ?>
            <a href="edit_words.php?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=<?php echo $lang['id']; ?>&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1="><?php echo $lang['wordCount']; ?></a>
            <?php else: ?>
            0
            <?php endif; ?>
        </td>

        <td class="td1 center language-last-col<?php echo $currentClass; ?>">
            <?php if ($lang['hasExportTemplate']): ?>
            <img src="/assets/icons/status.png" title="Yes" alt="Yes" />
            <?php else: ?>
            <img src="/assets/icons/status-busy.png" title="No" alt="No" />
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
