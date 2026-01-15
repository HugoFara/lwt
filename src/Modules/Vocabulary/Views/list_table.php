<?php

/**
 * Word list table view
 *
 * Variables expected:
 * - $recno: Total record count
 * - $currentlang: Current language filter
 * - $currentpage: Current page number
 * - $currentsort: Current sort option
 * - $pages: Total pages
 * - $words: Array of word records from query result
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress TypeDoesNotContainType View included from different contexts
 */

declare(strict_types=1);

namespace Lwt\Views\Word;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\FormHelper;
use Lwt\Shared\UI\Helpers\SelectOptionsBuilder;
use Lwt\View\Helper\StatusHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\UI\Helpers\TagHelper;
use Lwt\Modules\Vocabulary\Application\Services\ExportService;

// Type assertions for variables passed from controller
assert(is_int($recno));
assert(is_int($currentlang) || is_string($currentlang));
assert(is_int($currentpage));
assert(is_int($currentsort));
assert(is_int($pages));
/** @var list<array{WoID: int, WoText: string, WoTextLC: string, WoStatus: int, Days: int|string, Score: int|float, Score2: int|float, WoTranslation?: string, WoRomanization?: string, WoSentence?: string, LgName?: string, LgGoogleTranslateURI?: string, LgRightToLeft?: bool, SentOK?: int, taglist?: string, textswordcount?: int}> $words */
assert(is_array($words));

?>
<?php if ($recno == 0) : ?>
<p class="has-text-grey">No terms found.</p>
<?php else : ?>
<form name="form2" action="/words/edit" method="post">
    <?php echo FormHelper::csrfField(); ?>
<input type="hidden" name="data" value="" />

<!-- Multi Actions Section -->
<div class="box mb-4">
    <div class="level is-mobile mb-3">
        <div class="level-left">
            <div class="level-item">
                <span class="icon-text">
                    <?php echo IconHelper::render('zap', ['title' => 'Multi Actions', 'alt' => 'Multi Actions']); ?>
                    <span class="has-text-weight-semibold ml-1">Multi Actions</span>
                </span>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-multiline">
        <div class="control">
            <div class="field has-addons">
                <div class="control">
                    <span class="button is-static is-small">
                        <strong>ALL</strong>&nbsp;<?php echo ($recno == 1 ? '1 Term' : $recno . ' Terms'); ?>
                    </span>
                </div>
                <div class="control">
                    <div class="select is-small">
                        <select name="allaction" data-action="all-action" data-recno="<?php echo $recno; ?>">
                            <?php echo SelectOptionsBuilder::forAllWordsActions(); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-multiline mt-3">
        <div class="control">
            <div class="buttons are-small">
                <button type="button" class="button is-light" data-action="mark-all">
                    <?php echo IconHelper::render('check-check', ['alt' => 'Mark All']); ?>
                    <span class="ml-1">Mark All</span>
                </button>
                <button type="button" class="button is-light" data-action="mark-none">
                    <?php echo IconHelper::render('x', ['alt' => 'Mark None']); ?>
                    <span class="ml-1">Mark None</span>
                </button>
            </div>
        </div>
        <div class="control">
            <div class="field has-addons">
                <div class="control">
                    <span class="button is-static is-small">Marked Terms</span>
                </div>
                <div class="control">
                    <div class="select is-small">
                        <select name="markaction" id="markaction" disabled="disabled" data-action="mark-action">
                            <?php echo SelectOptionsBuilder::forMultipleWordsActions(); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Desktop Table View -->
<div class="table-container is-hidden-mobile">
<table class="table is-striped is-hoverable is-fullwidth sortable">
<thead>
<tr>
    <th class="has-text-centered sorttable_nosort" style="width: 3em;">Mark</th>
    <th class="has-text-centered sorttable_nosort" style="width: 5em;">Act.</th>
    <?php if ($currentlang == '') : ?>
    <th class="clickable">Lang.</th>
    <?php endif; ?>
    <th class="clickable">Term / Romanization</th>
    <th class="clickable">
        Translation [Tags]
        <span id="waitinfo" class="has-text-grey is-size-7">
            Please <?php
            echo IconHelper::render('loader-2', ['class' => 'icon-spin', 'alt' => 'Loading...']);
            ?> wait...
        </span>
    </th>
    <th class="has-text-centered sorttable_nosort" style="width: 3em;" title="Has valid sentence?">Se.?</th>
    <th class="has-text-centered sorttable_numeric clickable" style="width: 5em;">Stat./Days</th>
    <th class="has-text-centered sorttable_numeric clickable" style="width: 5em;">Score %</th>
    <?php if ($currentsort == 7) : ?>
    <th
        class="has-text-centered sorttable_numeric clickable"
        style="width: 5em;"
        title="Word Count in Active Texts"
    >WCnt Txts</th>
    <?php endif; ?>
</tr>
</thead>
<tbody>
    <?php
    foreach ($words as $record) :
        /**
         * @var array{WoID: int, WoText: string, WoTextLC: string, WoStatus: int,
         *            Days: int|string, Score: int|float, Score2: int|float, WoTranslation?: string,
         *            WoRomanization?: string, WoSentence?: string, LgName?: string,
         *            LgGoogleTranslateURI?: string, LgRightToLeft?: bool, SentOK?: int,
         *            taglist?: string, textswordcount?: int} $record
         */
        $days = $record['Days'];
        if ($record['WoStatus'] > 5) {
            $days = "-";
        }
        $score = $record['Score'];
        if ($score < 0) {
            $scoreHtml = '<span class="tag is-danger is-light">0 '
                . IconHelper::render('circle-x', ['title' => 'Review today!', 'alt' => 'Review today!'])
                . '</span>';
        } else {
            $reviewIcon = $record['Score2'] < 0
                ? IconHelper::render('circle-dot', ['title' => 'Review tomorrow!', 'alt' => 'Review tomorrow!'])
                : IconHelper::render('circle-check', ['title' => '-', 'alt' => '-']);
            $scoreHtml = '<span class="tag is-success is-light">'
                . floor((int)$score) . ' ' . $reviewIcon . '</span>';
        }
        $statusName = StatusHelper::getName($record['WoStatus']);
        $statusAbbr = StatusHelper::getAbbr($record['WoStatus']);
        ?>
<tr>
    <td class="has-text-centered">
        <a name="rec<?php echo $record['WoID']; ?>">
            <input
                name="marked[]"
                type="checkbox"
                class="markcheck"
                value="<?php echo $record['WoID']; ?>"
                <?php echo FormHelper::checkInRequest($record['WoID'], 'marked'); ?>
            />
        </a>
    </td>
    <td class="has-text-centered" style="white-space: nowrap;">
        <div class="buttons are-small is-centered">
            <a href="/words/<?php echo $record['WoID']; ?>/edit" class="button is-small is-ghost" title="Edit">
                <?php echo IconHelper::render('file-pen-line', ['title' => 'Edit', 'alt' => 'Edit']); ?>
            </a>
            <a
                class="button is-small is-ghost confirmdelete"
                href="/words/edit?del=<?php echo $record['WoID']; ?>"
                title="Delete"
            >
                <?php echo IconHelper::render('circle-minus', ['title' => 'Delete', 'alt' => 'Delete']); ?>
            </a>
        </div>
    </td>
        <?php if ($currentlang == '') : ?>
    <td class="has-text-centered"><?php echo htmlspecialchars($record['LgName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <?php endif; ?>
    <td>
        <span<?php
        if (
            isset($record['LgGoogleTranslateURI']) && $record['LgGoogleTranslateURI'] !== ''
            && strpos($record['LgGoogleTranslateURI'], '&sl=') !== false
        ) {
            $langCode = preg_replace('/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/', '$1', $record['LgGoogleTranslateURI']);
            echo ' class="tts_' . (is_string($langCode) ? $langCode : '') . '"';
        }
        echo (isset($record['LgRightToLeft']) && $record['LgRightToLeft'] ? ' dir="rtl"' : '');
        ?>><strong><?php echo htmlspecialchars($record['WoText'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <?php
        $romanization = isset($record['WoRomanization']) && $record['WoRomanization'] !== ''
            ? htmlspecialchars(ExportService::replaceTabNewline($record['WoRomanization']), ENT_QUOTES, 'UTF-8')
            : '*';
        ?>
        <span class="has-text-grey"> / </span>
        <span
            id="roman<?php echo $record['WoID']; ?>"
            class="edit_area clickedit has-text-grey-dark"
        ><?php echo $romanization; ?></span>
    </td>
    <td>
        <span id="trans<?php echo $record['WoID']; ?>" class="edit_area clickedit"><?php
            $trans = ExportService::replaceTabNewline($record['WoTranslation'] ?? '');
            echo htmlspecialchars($trans, ENT_QUOTES, 'UTF-8');
        ?></span>
        <?php echo TagHelper::renderInline($record['taglist'] ?? ''); ?>
    </td>
    <td class="has-text-centered">
        <?php
        $sentTitle = htmlspecialchars($record['WoSentence'] ?? '', ENT_QUOTES, 'UTF-8');
        if (isset($record['SentOK']) && $record['SentOK'] != 0) {
            $sentIcon = IconHelper::render(
                'circle-check',
                ['title' => $sentTitle, 'alt' => 'Yes', 'class' => 'has-text-success']
            );
        } else {
            $sentIcon = IconHelper::render(
                'circle-x',
                ['title' => '(No valid sentence)', 'alt' => 'No', 'class' => 'has-text-danger']
            );
        }
        echo $sentIcon;
        ?>
    </td>
    <td class="has-text-centered" title="<?php echo htmlspecialchars($statusName, ENT_QUOTES, 'UTF-8'); ?>">
        <span class="tag is-light"><?php
            echo htmlspecialchars($statusAbbr, ENT_QUOTES, 'UTF-8');
            echo ($record['WoStatus'] < 98 ? '/' . $days : '');
        ?></span>
    </td>
    <td class="has-text-centered" style="white-space: nowrap;"><?php echo $scoreHtml; ?></td>
        <?php if ($currentsort == 7) : ?>
    <td class="has-text-centered" style="white-space: nowrap;"><?php echo $record['textswordcount'] ?? 0; ?></td>
        <?php endif; ?>
</tr>
    <?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Mobile Card View -->
<div class="is-hidden-tablet">
    <?php
    foreach ($words as $record) :
        /**
         * @var array{WoID: int, WoText: string, WoTextLC: string, WoStatus: int,
         *            Days: int|string, Score: int|float, Score2: int|float, WoTranslation?: string,
         *            WoRomanization?: string, WoSentence?: string, LgName?: string,
         *            LgGoogleTranslateURI?: string, LgRightToLeft?: bool, SentOK?: int,
         *            taglist?: string, textswordcount?: int} $record
         */
        $days = $record['Days'];
        if ($record['WoStatus'] > 5) {
            $days = "-";
        }
        $score = $record['Score'];
        $scoreClass = $score < 0 ? 'is-danger' : 'is-success';
        $scoreValue = $score < 0 ? '0' : floor((int)$score);
        $statusName = StatusHelper::getName($record['WoStatus']);
        $statusAbbr = StatusHelper::getAbbr($record['WoStatus']);
        ?>
<div class="card mb-3">
    <div class="card-content">
        <div class="level is-mobile mb-2">
            <div class="level-left">
                <div class="level-item">
                    <label class="checkbox">
                        <input
                name="marked[]"
                type="checkbox"
                class="markcheck"
                value="<?php echo $record['WoID']; ?>"
                <?php echo FormHelper::checkInRequest($record['WoID'], 'marked'); ?>
            />
                    </label>
                </div>
                <div class="level-item">
                    <span<?php
                    if (
                        isset($record['LgGoogleTranslateURI']) && $record['LgGoogleTranslateURI'] !== ''
                        && strpos($record['LgGoogleTranslateURI'], '&sl=') !== false
                    ) {
                        $langCode = preg_replace(
                            '/.*[?&]sl=([a-zA-Z\-]*)(&.*)*$/',
                            '$1',
                            $record['LgGoogleTranslateURI']
                        );
                        echo ' class="tts_' . (is_string($langCode) ? $langCode : '') . '"';
                    }
                    $rtlAttr = isset($record['LgRightToLeft']) && $record['LgRightToLeft'];
                    echo ($rtlAttr ? ' dir="rtl"' : '');
                    $woText = htmlspecialchars($record['WoText'], ENT_QUOTES, 'UTF-8');
                    ?>><strong class="is-size-5"><?php echo $woText; ?></strong></span>
                </div>
            </div>
            <div class="level-right">
                <div class="level-item">
                    <div class="tags has-addons mb-0">
                        <span class="tag is-light"><?php
                            echo htmlspecialchars($statusAbbr, ENT_QUOTES, 'UTF-8');
                        ?></span>
                        <span class="tag <?php echo $scoreClass; ?> is-light"><?php
                            echo $scoreValue;
                        ?>%</span>
                    </div>
                </div>
            </div>
        </div>

            <?php if (isset($record['WoRomanization']) && $record['WoRomanization'] !== '') : ?>
        <p class="has-text-grey is-size-7 mb-1">
            <span id="roman<?php echo $record['WoID']; ?>" class="edit_area clickedit"><?php
                $roman = ExportService::replaceTabNewline($record['WoRomanization']);
                echo htmlspecialchars($roman, ENT_QUOTES, 'UTF-8');
            ?></span>
        </p>
            <?php endif; ?>

        <p class="mb-2">
            <span id="trans<?php echo $record['WoID']; ?>" class="edit_area clickedit"><?php
                $trans = ExportService::replaceTabNewline($record['WoTranslation'] ?? '');
                echo htmlspecialchars($trans, ENT_QUOTES, 'UTF-8');
            ?></span>
        </p>

        <div class="is-flex is-justify-content-space-between is-align-items-center">
            <div class="tags">
                    <?php if ($currentlang == '' && isset($record['LgName']) && $record['LgName'] !== '') : ?>
                <span class="tag is-info is-light"><?php
                    echo htmlspecialchars($record['LgName'], ENT_QUOTES, 'UTF-8');
                ?></span>
                    <?php endif; ?>
                    <?php echo TagHelper::renderInline($record['taglist'] ?? ''); ?>
                    <?php if (isset($record['SentOK']) && $record['SentOK'] != 0) :
                        $sentTitle = htmlspecialchars($record['WoSentence'] ?? '', ENT_QUOTES, 'UTF-8');
                        ?>
                <span class="tag is-success is-light" title="<?php echo $sentTitle; ?>">
                        <?php echo IconHelper::render('message-square', ['alt' => 'Has sentence']); ?>
                </span>
                    <?php endif; ?>
            </div>
            <div class="buttons are-small">
                <a
                    href="/words/<?php echo $record['WoID']; ?>/edit"
                    class="button is-small is-info is-light"
                >
                    <?php echo IconHelper::render('file-pen-line', ['alt' => 'Edit']); ?>
                </a>
                <a
                    class="button is-small is-danger is-light confirmdelete"
                    href="/words/edit?del=<?php echo $record['WoID']; ?>"
                >
                    <?php echo IconHelper::render('circle-minus', ['alt' => 'Delete']); ?>
                </a>
            </div>
        </div>
    </div>
</div>
    <?php endforeach; ?>
</div>

    <?php if ($pages > 1) : ?>
<!-- Pagination -->
<nav class="level mt-4">
    <div class="level-left">
        <div class="level-item">
            <span class="tag is-info is-medium">
                    <?php echo $recno; ?> Term<?php echo ($recno == 1 ? '' : 's'); ?>
            </span>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
                <?php echo PageLayoutHelper::buildPager($currentpage, $pages, '/words/edit', 'form2'); ?>
        </div>
    </div>
</nav>
    <?php endif; ?>
</form>
<?php endif; ?>
