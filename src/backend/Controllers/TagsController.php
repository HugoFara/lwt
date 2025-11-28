<?php

/**
 * \file
 * \brief Tags Controller - Manage term and text tags
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license Unlicense <http://unlicense.org/>
 * @link    https://hugofara.github.io/lwt/docs/php/files/src-php-controllers-tagscontroller.html
 * @since   3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Services\TagService;

require_once __DIR__ . '/../Services/TagService.php';

/**
 * Controller for managing tags (both term tags and text tags).
 *
 * Handles CRUD operations for:
 * - Term tags (tags table) - tags applied to vocabulary words
 * - Text tags (tags2 table) - tags applied to texts
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class TagsController extends BaseController
{
    /**
     * Term tags index page (replaces tags_edit.php)
     *
     * Handles: List, Create, Edit, Delete term tags
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        $currentsort = (int) $this->dbParam("sort", 'currenttagsort', '1', true);
        $currentpage = (int) $this->sessionParam("page", "currenttagpage", '1', true);
        $currentquery = (string) $this->sessionParam("query", "currenttagquery", '', false);

        $wh_query = $this->escape(str_replace("*", "%", $currentquery));
        $wh_query = ($currentquery != '')
            ? (' and (TgText like ' . $wh_query . ' or TgComment like ' . $wh_query . ')')
            : '';

        $this->render('My Term Tags', true);


        // Process actions
        $message = $this->processTermTagActions($wh_query);

        // Display appropriate view
        if ($this->param('new')) {
            $this->showNewTermTagForm();
        } elseif ($this->param('chg')) {
            $this->showEditTermTagForm((int)$this->param('chg'));
        } else {
            $this->showTermTagsList($message, $currentquery, $wh_query, $currentsort, $currentpage, \Lwt\Core\Globals::isDebug());
        }

        $this->endRender();
    }

    /**
     * Text tags index page (replaces tags_text_edit.php)
     *
     * Handles: List, Create, Edit, Delete text tags
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function textTags(array $params): void
    {
        $currentsort = (int) $this->dbParam("sort", 'currenttexttagsort', '1', true);
        $currentpage = (int) $this->sessionParam("page", "currenttexttagpage", '1', true);
        $currentquery = (string) $this->sessionParam("query", "currenttexttagquery", '', false);

        $wh_query = $this->escape(str_replace("*", "%", $currentquery));
        $wh_query = ($currentquery != '')
            ? (' and (T2Text like ' . $wh_query . ' or T2Comment like ' . $wh_query . ')')
            : '';

        $this->render('My Text Tags', true);


        // Process actions
        $message = $this->processTextTagActions($wh_query);

        // Display appropriate view
        if ($this->param('new')) {
            $this->showNewTextTagForm();
        } elseif ($this->param('chg')) {
            $this->showEditTextTagForm((int)$this->param('chg'));
        } else {
            $this->showTextTagsList($message, $currentquery, $wh_query, $currentsort, $currentpage, \Lwt\Core\Globals::isDebug());
        }

        $this->endRender();
    }

    // ==================== TERM TAGS METHODS ====================

    /**
     * Process term tag actions (delete, mark, save, update)
     *
     * @param string $wh_query WHERE clause for filtering
     *
     * @return string Result message
     */
    private function processTermTagActions(string $wh_query): string
    {
        $message = '';

        // Mark actions
        if ($this->param('markaction')) {
            $message = $this->handleTermTagMarkAction($this->param('markaction'));
        } elseif ($this->param('allaction')) {
            // All actions
            if ($this->param('allaction') == 'delall') {
                $message = $this->execute(
                    'delete from ' . $this->table('tags') . ' where (1=1) ' . $wh_query,
                    "Deleted"
                );
                $this->cleanupOrphanedTermTagLinks();
                Maintenance::adjustAutoIncrement('tags', 'TgID');
            }
        } elseif ($this->param('del')) {
            // Single delete
            $message = $this->execute(
                'delete from ' . $this->table('tags') . ' where TgID = ' . (int)$this->param('del'),
                "Deleted"
            );
            $this->cleanupOrphanedTermTagLinks();
            Maintenance::adjustAutoIncrement('tags', 'TgID');
        } elseif ($this->param('op')) {
            // Insert/Update
            $message = $this->saveTermTag();
        }

        return $message;
    }

    /**
     * Handle mark action for term tags
     *
     * @param string $action Action code
     *
     * @return string Result message
     */
    private function handleTermTagMarkAction(string $action): string
    {
        $message = "Multiple Actions: 0";
        $marked = $this->param('marked');

        if (is_array($marked) && count($marked) > 0) {
            $ids = $this->getMarkedIds($marked);
            $list = "(" . implode(",", $ids) . ")";

            if ($action == 'del') {
                $message = $this->execute(
                    'delete from ' . $this->table('tags') . ' where TgID in ' . $list,
                    "Deleted"
                );
                $this->cleanupOrphanedTermTagLinks();
                Maintenance::adjustAutoIncrement('tags', 'TgID');
            }
        }

        return $message;
    }

    /**
     * Save or update term tag
     *
     * @return string Result message
     */
    private function saveTermTag(): string
    {
        $op = $this->param('op');
        $text = $this->escape($this->param('TgText', ''));
        $comment = $this->escapeNonNull($this->param('TgComment', ''));

        if ($op == 'Save') {
            return $this->execute(
                'insert into ' . $this->table('tags') . ' (TgText, TgComment) values(' . $text . ', ' . $comment . ')',
                "Saved",
                false
            );
        } elseif ($op == 'Change') {
            return $this->execute(
                'update ' . $this->table('tags') . ' set TgText = ' . $text . ', TgComment = ' . $comment .
                ' where TgID = ' . (int)$this->param('TgID'),
                "Updated",
                false
            );
        }

        return '';
    }

    /**
     * Cleanup orphaned term tag links after deletion
     *
     * @return void
     */
    private function cleanupOrphanedTermTagLinks(): void
    {
        $this->execute(
            "DELETE " . $this->table('wordtags') . " FROM (" .
            $this->table('wordtags') . " LEFT JOIN " . $this->table('tags') .
            " on WtTgID = TgID) WHERE TgID IS NULL",
            ''
        );
    }

    /**
     * Show new term tag form
     *
     * @return void
     */
    private function showNewTermTagForm(): void
    {
        ?>
        <h2>New Tag</h2>
        <script type="text/javascript" charset="utf-8">
            $(document).ready(lwtFormCheck.askBeforeExit);
        </script>
        <form name="newtag" class="validate" action="/tags" method="post">
        <table class="tab1" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1 right">Tag:</td>
                <td class="td1">
                    <input class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                    type="text" name="TgText" data_info="Tag" value="" maxlength="20" size="20" />
                    <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
                </td>
            </tr>
            <tr>
                <td class="td1 right">Comment:</td>
                <td class="td1">
                    <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
                    data_maxlength="200" data_info="Comment" name="TgComment" cols="40" rows="3"></textarea>
                </td>
            </tr>
            <tr>
                <td class="td1 right" colspan="2">
                    <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/tags';}" />
                    <input type="submit" name="op" value="Save" />
                </td>
            </tr>
        </table>
        </form>
        <?php
    }

    /**
     * Show edit term tag form
     *
     * @param int $tagId Tag ID to edit
     *
     * @return void
     */
    private function showEditTermTagForm(int $tagId): void
    {
        $sql = 'select * from ' . $this->table('tags') . ' where TgID = ' . $tagId;
        $res = $this->query($sql);
        if (($record = mysqli_fetch_assoc($res)) !== false) {
            ?>
            <h2>Edit Tag</h2>
            <script type="text/javascript" charset="utf-8">
                $(document).ready(lwtFormCheck.askBeforeExit);
            </script>
            <form name="edittag" class="validate" action="/tags#rec<?php echo $tagId; ?>" method="post">
            <input type="hidden" name="TgID" value="<?php echo $record['TgID']; ?>" />
            <table class="tab1" cellspacing="0" cellpadding="5">
                <tr>
                    <td class="td1 right">Tag:</td>
                    <td class="td1">
                        <input data_info="Tag" class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                        type="text" name="TgText" value="<?php echo \tohtml($record['TgText']); ?>" maxlength="20" size="20" />
                        <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
                    </td>
                </tr>
                <tr>
                    <td class="td1 right">Comment:</td>
                    <td class="td1">
                        <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
                        data_maxlength="200" data_info="Comment" name="TgComment" rows="3"><?php echo \tohtml($record['TgComment']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="td1 right" colspan="2">
                        <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/tags#rec<?php echo $tagId; ?>';}" />
                        <input type="submit" name="op" value="Change" />
                    </td>
                </tr>
            </table>
            </form>
            <?php
        }
        mysqli_free_result($res);
    }

    /**
     * Show term tags list
     *
     * @param string $message      Result message to display
     * @param string $currentquery Current search query
     * @param string $wh_query     WHERE clause
     * @param int    $currentsort  Current sort order
     * @param int    $currentpage  Current page number
     * @param bool   $debug        Debug mode
     *
     * @return void
     */
    private function showTermTagsList(
        string $message,
        string $currentquery,
        string $wh_query,
        int $currentsort,
        int $currentpage,
        bool $debug = false
    ): void {
        // Handle duplicate entry error message
        if (
            substr($message, 0, 24) == "Error: Duplicate entry '"
            && substr($message, -18) == "' for key 'TgText'"
        ) {
            $message = substr($message, 24);
            $message = substr($message, 0, strlen($message) - 18);
            $message = "Error: Term Tag '" . $message . "' already exists. Please go back and correct this!";
        }
        $this->message($message, false);

        TagService::getAllTermTags(true);   // refresh tags cache

        $sql = 'select count(TgID) as value from ' . $this->table('tags') . ' where (1=1) ' . $wh_query;
        $recno = (int) $this->getValue($sql);
        if ($debug) {
            echo $sql . ' ===&gt; ' . $recno;
        }

        $maxperpage = (int) Settings::getWithDefault('set-tags-per-page');
        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if ($currentpage > $pages) {
            $currentpage = $pages;
        }
        $limit = 'LIMIT ' . (($currentpage - 1) * $maxperpage) . ',' . $maxperpage;

        $sorts = array('TgText', 'TgComment', 'TgID desc', 'TgID asc');
        $lsorts = count($sorts);
        if ($currentsort < 1) {
            $currentsort = 1;
        }
        if ($currentsort > $lsorts) {
            $currentsort = $lsorts;
        }

        ?>
        <p><a href="/tags?new=1"><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New Term Tag ...</a></p>

        <form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
        <table class="tab2" cellspacing="0" cellpadding="5">
        <tr>
        <th class="th1" colspan="4">Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
        <input type="button" value="Reset All" onclick="{location.href='/tags?page=1&amp;query=';}" /></th>
        </tr>
        <tr>
        <td class="td1 center" colspan="4">
        Tag Text or Comment:
        <input type="text" name="query" value="<?php echo \tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
        <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value; location.href='/tags?page=1&amp;query=' + val;}" />&nbsp;
        <input type="button" value="Clear" onclick="{location.href='/tags?page=1&amp;query=';}" />
        </td>
        </tr>
        <?php if ($recno > 0) { ?>
        <tr>
        <th class="th1" colspan="1" nowrap="nowrap">
            <?php echo $recno; ?> Tag<?php echo ($recno == 1 ? '' : 's'); ?>
        </th><th class="th1" colspan="2" nowrap="nowrap">
            <?php \makePager($currentpage, $pages, '/tags', 'form1'); ?>
        </th><th class="th1" nowrap="nowrap">
        Sort Order:
        <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/tags?page=1&amp;sort=' + val;}"><?php echo \get_tagsort_selectoptions($currentsort); ?></select>
        </th></tr>
        <?php } ?>
        </table>
        </form>

        <?php
        if ($recno == 0) {
            ?>
            <p>No tags found.</p>
            <?php
        } else {
            ?>
            <form name="form2" action="/tags" method="post">
            <input type="hidden" name="data" value="" />
            <table class="tab2" cellspacing="0" cellpadding="5">
            <tr><th class="th1 center" colspan="2">
            Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
            </th></tr>
            <tr><td class="td1 center" colspan="2">
            <b>ALL</b> <?php echo ($recno == 1 ? '1 Tag' : $recno . ' Tags'); ?>:&nbsp;
            <select name="allaction" onchange="allActionGo(document.form2, document.form2.allaction,<?php echo $recno; ?>);"><?php echo \get_alltagsactions_selectoptions(); ?></select>
            </td></tr>
            <tr><td class="td1 center">
            <input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
            <input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
            </td>
            <td class="td1 center">Marked Tags:&nbsp;
            <select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo \get_multipletagsactions_selectoptions(); ?></select>
            </td></tr></table>

            <table class="sortable tab2" cellspacing="0" cellpadding="5">
            <tr>
            <th class="th1 sorttable_nosort">Mark</th>
            <th class="th1 sorttable_nosort">Actions</th>
            <th class="th1 clickable">Tag Text</th>
            <th class="th1 clickable">Tag Comment</th>
            <th class="th1 clickable">Terms With Tag</th>
            </tr>

            <?php
            $sql = 'select TgID, TgText, TgComment from ' . $this->table('tags') .
                   ' where (1=1) ' . $wh_query . ' order by ' . $sorts[$currentsort - 1] . ' ' . $limit;
            if ($debug) {
                echo $sql;
            }
            $res = $this->query($sql);
            while ($record = mysqli_fetch_assoc($res)) {
                $c = $this->getValue('select count(*) as value from ' . $this->table('wordtags') . ' where WtTgID=' . $record['TgID']);
                echo '<tr>
                    <td class="td1 center">
                        <a name="rec' . $record['TgID'] . '">
                        <input name="marked[]" type="checkbox" class="markcheck" value="' . $record['TgID'] . '" ' . \checkTest($record['TgID'], 'marked') . ' />
                        </a></td>
                    <td class="td1 center" nowrap="nowrap">&nbsp;<a href="/tags?chg=' . $record['TgID'] . '"><img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <a class="confirmdelete" href="/tags?del=' . $record['TgID'] . '"><img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></a>&nbsp;</td>
                    <td class="td1 center">' . \tohtml($record['TgText']) . '</td>
                    <td class="td1 center">' . \tohtml($record['TgComment']) . '</td>
                    <td class="td1 center">' . ($c > 0 ? '<a href="/words/edit?page=1&amp;query=&amp;text=&amp;status=&amp;filterlang=&amp;status=&amp;tag12=0&amp;tag2=&amp;tag1=' . $record['TgID'] . '">' . $c . '</a>' : '0') . '</td>
                </tr>';
            }
            mysqli_free_result($res);
            ?>
            </table>

            <?php if ($pages > 1) { ?>
            <table class="tab2" cellspacing="0" cellpadding="5">
                <tr>
                    <th class="th1" nowrap="nowrap">
                        <?php echo $recno; ?> Tag<?php echo ($recno == 1 ? '' : 's'); ?>
                    </th>
                    <th class="th1" nowrap="nowrap">
                        <?php \makePager($currentpage, $pages, '/tags', 'form2'); ?>
                    </th>
                </tr>
            </table>
            </form>
            <?php }
        }
    }

    // ==================== TEXT TAGS METHODS ====================

    /**
     * Process text tag actions (delete, mark, save, update)
     *
     * @param string $wh_query WHERE clause for filtering
     *
     * @return string Result message
     */
    private function processTextTagActions(string $wh_query): string
    {
        $message = '';

        // Mark actions
        if ($this->param('markaction')) {
            $message = $this->handleTextTagMarkAction($this->param('markaction'));
        } elseif ($this->param('allaction')) {
            // All actions
            if ($this->param('allaction') == 'delall') {
                $message = $this->execute(
                    'delete from ' . $this->table('tags2') . ' where (1=1) ' . $wh_query,
                    "Deleted"
                );
                $this->cleanupOrphanedTextTagLinks();
                Maintenance::adjustAutoIncrement('tags2', 'T2ID');
            }
        } elseif ($this->param('del')) {
            // Single delete
            $message = $this->execute(
                'delete from ' . $this->table('tags2') . ' where T2ID = ' . (int)$this->param('del'),
                "Deleted"
            );
            $this->cleanupOrphanedTextTagLinks();
            Maintenance::adjustAutoIncrement('tags2', 'T2ID');
        } elseif ($this->param('op')) {
            // Insert/Update
            $message = $this->saveTextTag();
        }

        return $message;
    }

    /**
     * Handle mark action for text tags
     *
     * @param string $action Action code
     *
     * @return string Result message
     */
    private function handleTextTagMarkAction(string $action): string
    {
        $message = "Multiple Actions: 0";
        $marked = $this->param('marked');

        if (is_array($marked) && count($marked) > 0) {
            $ids = $this->getMarkedIds($marked);
            $list = "(" . implode(",", $ids) . ")";

            if ($action == 'del') {
                $message = $this->execute(
                    'delete from ' . $this->table('tags2') . ' where T2ID in ' . $list,
                    "Deleted"
                );
                $this->cleanupOrphanedTextTagLinks();
                Maintenance::adjustAutoIncrement('tags2', 'T2ID');
            }
        }

        return $message;
    }

    /**
     * Save or update text tag
     *
     * @return string Result message
     */
    private function saveTextTag(): string
    {
        $op = $this->param('op');
        $text = $this->escape($this->param('T2Text', ''));
        $comment = $this->escapeNonNull($this->param('T2Comment', ''));

        if ($op == 'Save') {
            return $this->execute(
                'insert into ' . $this->table('tags2') . ' (T2Text, T2Comment) values(' . $text . ', ' . $comment . ')',
                "Saved",
                false
            );
        } elseif ($op == 'Change') {
            return $this->execute(
                'update ' . $this->table('tags2') . ' set T2Text = ' . $text . ', T2Comment = ' . $comment .
                ' where T2ID = ' . (int)$this->param('T2ID'),
                "Updated",
                false
            );
        }

        return '';
    }

    /**
     * Cleanup orphaned text tag links after deletion
     *
     * @return void
     */
    private function cleanupOrphanedTextTagLinks(): void
    {
        $this->execute(
            "DELETE " . $this->table('texttags') . " FROM (" .
            $this->table('texttags') . " LEFT JOIN " . $this->table('tags2') .
            " on TtT2ID = T2ID) WHERE T2ID IS NULL",
            ''
        );
        $this->execute(
            "DELETE " . $this->table('archtexttags') . " FROM (" .
            $this->table('archtexttags') . " LEFT JOIN " . $this->table('tags2') .
            " on AgT2ID = T2ID) WHERE T2ID IS NULL",
            ''
        );
    }

    /**
     * Show new text tag form
     *
     * @return void
     */
    private function showNewTextTagForm(): void
    {
        ?>
        <h2>New Tag</h2>
        <script type="text/javascript" charset="utf-8">
            $(document).ready(lwtFormCheck.askBeforeExit);
        </script>
        <form name="newtag" class="validate" action="/tags/text" method="post">
        <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Tag:</td>
            <td class="td1">
                <input class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                type="text" name="T2Text" data_info="Tag" value="" maxlength="20" />
                <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" />
            </td>
        </tr>
        <tr>
        <td class="td1 right">Comment:</td>
        <td class="td1">
            <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
            data_maxlength="200" data_info="Comment" name="T2Comment" rows="3"></textarea>
        </td>
        </tr>
        <tr>
            <td class="td1 right" colspan="2">
            <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/tags/text';}" />
            <input type="submit" name="op" value="Save" /></td>
        </tr>
        </table>
        </form>
        <?php
    }

    /**
     * Show edit text tag form
     *
     * @param int $tagId Tag ID to edit
     *
     * @return void
     */
    private function showEditTextTagForm(int $tagId): void
    {
        $sql = 'select * from ' . $this->table('tags2') . ' where T2ID = ' . $tagId;
        $res = $this->query($sql);
        if (($record = mysqli_fetch_assoc($res)) !== false) {
            ?>
            <h2>Edit Tag</h2>
            <script type="text/javascript" charset="utf-8">
                $(document).ready(lwtFormCheck.askBeforeExit);
            </script>
            <form name="edittag" class="validate" action="/tags/text#rec<?php echo $tagId; ?>" method="post">
                <input type="hidden" name="T2ID" value="<?php echo $record['T2ID']; ?>" />
                <table class="tab1" cellspacing="0" cellpadding="5">
                <tr>
                <td class="td1 right">Tag:</td>
                <td class="td1">
                    <input data_info="Tag" class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                    type="text" name="T2Text" value="<?php echo \tohtml($record['T2Text']); ?>" maxlength="20" />
                    <img src="/assets/icons/status-busy.png" title="Field must not be empty" alt="Field must not be empty" /></td>
                </tr>
                <tr>
                    <td class="td1 right">Comment:</td>
                    <td class="td1">
                        <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
                        data_maxlength="200" data_info="Comment" name="T2Comment" rows="3"><?php echo \tohtml($record['T2Comment']); ?></textarea>
                    </td>
                </tr>
                <tr>
                <td class="td1 right" colspan="2">
                <input type="button" value="Cancel" onclick="{lwtFormCheck.resetDirty(); location.href='/tags/text#rec<?php echo $tagId; ?>';};" />
                <input type="submit" name="op" value="Change" /></td>
                </tr>
                </table>
            </form>
            <?php
        }
        mysqli_free_result($res);
    }

    /**
     * Show text tags list
     *
     * @param string $message      Result message to display
     * @param string $currentquery Current search query
     * @param string $wh_query     WHERE clause
     * @param int    $currentsort  Current sort order
     * @param int    $currentpage  Current page number
     * @param bool   $debug        Debug mode
     *
     * @return void
     */
    private function showTextTagsList(
        string $message,
        string $currentquery,
        string $wh_query,
        int $currentsort,
        int $currentpage,
        bool $debug = false
    ): void {
        // Handle duplicate entry error message
        if (
            substr($message, 0, 24) == "Error: Duplicate entry '"
            && substr($message, -18) == "' for key 'T2Text'"
        ) {
            $message = substr($message, 24);
            $message = substr($message, 0, strlen($message) - 18);
            $message = "Error: Text Tag '" . $message . "' already exists. Please go back and correct this!";
        }
        $this->message($message, false);

        TagService::getAllTextTags(true);   // refresh tags cache

        $sql = 'select count(T2ID) as value from ' . $this->table('tags2') . ' where (1=1) ' . $wh_query;
        $recno = (int) $this->getValue($sql);
        if ($debug) {
            echo $sql . ' ===&gt; ' . $recno;
        }

        $maxperpage = (int) Settings::getWithDefault('set-tags-per-page');
        $pages = $recno == 0 ? 0 : (intval(($recno - 1) / $maxperpage) + 1);

        if ($currentpage < 1) {
            $currentpage = 1;
        }
        if ($currentpage > $pages) {
            $currentpage = $pages;
        }
        $limit = 'LIMIT ' . (($currentpage - 1) * $maxperpage) . ',' . $maxperpage;

        $sorts = array('T2Text', 'T2Comment', 'T2ID desc', 'T2ID asc');
        $lsorts = count($sorts);
        if ($currentsort < 1) {
            $currentsort = 1;
        }
        if ($currentsort > $lsorts) {
            $currentsort = $lsorts;
        }

        ?>
        <p><a href="/tags/text?new=1"><img src="/assets/icons/plus-button.png" title="New" alt="New" /> New Text Tag ...</a></p>

        <form name="form1" action="#" onsubmit="document.form1.querybutton.click(); return false;">
        <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <th class="th1" colspan="4">
                Filter <img src="/assets/icons/funnel.png" title="Filter" alt="Filter" />&nbsp;
            <input type="button" value="Reset All" onclick="{location.href='/tags/text?page=1&amp;query=';}" /></th>
        </tr>
        <tr>
        <td class="td1 center" colspan="4">
            Tag Text or Comment:
            <input type="text" name="query" value="<?php echo \tohtml($currentquery); ?>" maxlength="50" size="15" />&nbsp;
            <input type="button" name="querybutton" value="Filter" onclick="{val=document.form1.query.value; location.href='/tags/text?page=1&amp;query=' + val;}" />&nbsp;
            <input type="button" value="Clear" onclick="{location.href='/tags/text?page=1&amp;query=';}" />
        </td>
        </tr>
        <?php if ($recno > 0) { ?>
        <tr>
        <th class="th1" colspan="1" nowrap="nowrap">
            <?php echo $recno; ?> Tag<?php echo ($recno == 1 ? '' : 's'); ?>
        </th><th class="th1" colspan="2" nowrap="nowrap">
            <?php \makePager($currentpage, $pages, '/tags/text', 'form1'); ?>
        </th><th class="th1" nowrap="nowrap">
        Sort Order:
        <select name="sort" onchange="{val=document.form1.sort.options[document.form1.sort.selectedIndex].value; location.href='/tags/text?page=1&amp;sort=' + val;}"><?php echo \get_tagsort_selectoptions($currentsort); ?></select>
        </th></tr>
        <?php } ?>
        </table>
        </form>

        <?php
        if ($recno == 0) {
            ?>
            <p>No tags found.</p>
            <?php
        } else {
            ?>
            <form name="form2" action="/tags/text" method="post">
            <input type="hidden" name="data" value="" />
            <table class="tab2" cellspacing="0" cellpadding="5">
            <tr><th class="th1 center" colspan="2">
            Multi Actions <img src="/assets/icons/lightning.png" title="Multi Actions" alt="Multi Actions" />
            </th></tr>
            <tr><td class="td1 center" colspan="2">
            <b>ALL</b> <?php echo ($recno == 1 ? '1 Tag' : $recno . ' Tags'); ?>:&nbsp;
            <select name="allaction" onchange="allActionGo(document.form2, document.form2.allaction,<?php echo $recno; ?>);"><?php echo \get_alltagsactions_selectoptions(); ?></select>
            </td></tr>
            <tr><td class="td1 center">
            <input type="button" value="Mark All" onclick="selectToggle(true,'form2');" />
            <input type="button" value="Mark None" onclick="selectToggle(false,'form2');" />
            </td>
            <td class="td1 center">Marked Tags:&nbsp;
            <select name="markaction" id="markaction" disabled="disabled" onchange="multiActionGo(document.form2, document.form2.markaction);"><?php echo \get_multipletagsactions_selectoptions(); ?></select>
            </td></tr></table>

            <table class="sortable tab2" cellspacing="0" cellpadding="5">
            <tr>
            <th class="th1 sorttable_nosort">Mark</th>
            <th class="th1 sorttable_nosort">Actions</th>
            <th class="th1 clickable">Tag Text</th>
            <th class="th1 clickable">Tag Comment</th>
            <th class="th1 clickable">Texts<br />With Tag</th>
            <th class="th1 clickable">Arch.Texts<br />With Tag</th>
            </tr>

            <?php
            $sql = 'select T2ID, T2Text, T2Comment from ' . $this->table('tags2') .
                   ' where (1=1) ' . $wh_query . ' order by ' . $sorts[$currentsort - 1] . ' ' . $limit;
            if ($debug) {
                echo $sql;
            }
            $res = $this->query($sql);
            while ($record = mysqli_fetch_assoc($res)) {
                $c = $this->getValue('select count(*) as value from ' . $this->table('texttags') . ' where TtT2ID=' . $record['T2ID']);
                $ca = $this->getValue('select count(*) as value from ' . $this->table('archtexttags') . ' where AgT2ID=' . $record['T2ID']);
                echo '<tr>';
                echo '<td class="td1 center"><a name="rec' . $record['T2ID'] . '"><input name="marked[]" type="checkbox" class="markcheck" value="' . $record['T2ID'] . '" ' . \checkTest($record['T2ID'], 'marked') . ' /></a></td>';
                echo '<td class="td1 center" nowrap="nowrap">&nbsp;<a href="/tags/text?chg=' . $record['T2ID'] . '"><img src="/assets/icons/document--pencil.png" title="Edit" alt="Edit" /></a>&nbsp; <a class="confirmdelete" href="/tags/text?del=' . $record['T2ID'] . '"><img src="/assets/icons/minus-button.png" title="Delete" alt="Delete" /></a>&nbsp;</td>';
                echo '<td class="td1 center">' . \tohtml($record['T2Text']) . '</td>';
                echo '<td class="td1 center">' . \tohtml($record['T2Comment']) . '</td>';
                echo '<td class="td1 center">' . ($c > 0 ? '<a href="/text/edit?page=1&amp;query=&amp;tag12=0&amp;tag2=&amp;tag1=' . $record['T2ID'] . '">' . $c . '</a>' : '0') . '</td>';
                echo '<td class="td1 center">' . ($ca > 0 ? '<a href="/text/archived?page=1&amp;query=&amp;tag12=0&amp;tag2=&amp;tag1=' . $record['T2ID'] . '">' . $ca . '</a>' : '0') . '</td>';
                echo '</tr>';
            }
            mysqli_free_result($res);
            ?>
            </table>

            <?php if ($pages > 1) { ?>
            <table class="tab2" cellspacing="0" cellpadding="5">
            <tr>
            <th class="th1" nowrap="nowrap">
                <?php echo $recno; ?> Tag<?php echo ($recno == 1 ? '' : 's'); ?>
            </th><th class="th1" nowrap="nowrap">
                <?php \makePager($currentpage, $pages, '/tags/text', 'form2'); ?>
            </th></tr></table></form>
            <?php }
        }
    }
}
