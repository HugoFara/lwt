<?php declare(strict_types=1);
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

use Lwt\Core\Http\InputValidator;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Settings;
use Lwt\Database\Maintenance;
use Lwt\Services\TagService;
use Lwt\View\Helper\PageLayoutHelper;
use Lwt\View\Helper\SelectOptionsBuilder;
use Lwt\View\Helper\FormHelper;
use Lwt\View\Helper\IconHelper;

require_once __DIR__ . '/../Services/TagService.php';
require_once __DIR__ . '/../View/Helper/SelectOptionsBuilder.php';

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
        $currentsort = InputValidator::getIntWithDb("sort", 'currenttagsort', 1);
        $currentpage = InputValidator::getIntWithSession("page", "currenttagpage", 1);
        $currentquery = InputValidator::getStringWithSession("query", "currenttagquery");

        // Build WHERE clause using TagService
        $service = new TagService('term');
        $whereData = $service->buildWhereClause($currentquery);

        $this->render('Term Tags', true);


        // Process actions
        $message = $this->processTermTagActions($whereData);

        // Display appropriate view
        if ($this->param('new')) {
            $this->showNewTermTagForm();
        } elseif ($this->param('chg')) {
            $this->showEditTermTagForm((int)$this->param('chg'));
        } else {
            $this->showTermTagsList($message, $currentquery, $whereData, $currentsort, $currentpage);
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
        $currentsort = InputValidator::getIntWithDb("sort", 'currenttexttagsort', 1);
        $currentpage = InputValidator::getIntWithSession("page", "currenttexttagpage", 1);
        $currentquery = InputValidator::getStringWithSession("query", "currenttexttagquery");

        // Build WHERE clause using TagService
        $service = new TagService('text');
        $whereData = $service->buildWhereClause($currentquery);

        $this->render('Text Tags', true);


        // Process actions
        $message = $this->processTextTagActions($whereData);

        // Display appropriate view
        if ($this->param('new')) {
            $this->showNewTextTagForm();
        } elseif ($this->param('chg')) {
            $this->showEditTextTagForm((int)$this->param('chg'));
        } else {
            $this->showTextTagsList($message, $currentquery, $whereData, $currentsort, $currentpage);
        }

        $this->endRender();
    }

    // ==================== TERM TAGS METHODS ====================

    /**
     * Process term tag actions (delete, mark, save, update)
     *
     * @param array{clause: string, params: array} $whereData WHERE clause data from buildWhereClause()
     *
     * @return string Result message
     */
    private function processTermTagActions(array $whereData): string
    {
        $message = '';
        $service = new TagService('term');

        // Mark actions
        if ($this->param('markaction')) {
            $message = $this->handleTermTagMarkAction($this->param('markaction'));
        } elseif ($this->param('allaction')) {
            // All actions
            if ($this->param('allaction') == 'delall') {
                $message = $service->deleteAll($whereData);
            }
        } elseif ($this->param('del')) {
            // Single delete
            $message = $service->delete((int)$this->param('del'));
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
                QueryBuilder::table('tags')
                    ->whereIn('TgID', $ids)
                    ->deletePrepared();
                $count = count($ids);
                $message = "Deleted: " . $count;
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
        $text = $this->param('TgText', '');
        $comment = $this->param('TgComment', '');

        if ($op == 'Save') {
            QueryBuilder::table('tags')
                ->insertPrepared([
                    'TgText' => $text,
                    'TgComment' => $comment
                ]);
            return "Saved: 1";
        } elseif ($op == 'Change') {
            QueryBuilder::table('tags')
                ->where('TgID', '=', (int)$this->param('TgID'))
                ->updatePrepared([
                    'TgText' => $text,
                    'TgComment' => $comment
                ]);
            return "Updated: 1";
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
        // Delete orphaned wordtags that reference non-existent tags
        // wordtags inherits user context via WtWoID -> words FK
        $sql = "DELETE wordtags FROM wordtags LEFT JOIN tags ON WtTgID = TgID WHERE TgID IS NULL";
        $this->execute($sql, '');
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
        <form name="newtag" class="validate lwt-form-check" action="/tags" method="post">
        <table class="tab1" cellspacing="0" cellpadding="5">
            <tr>
                <td class="td1 right">Tag:</td>
                <td class="td1">
                    <input class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                    type="text" name="TgText" data_info="Tag" value="" maxlength="20" size="20" />
                    <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
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
                    <button type="button" data-action="cancel" data-url="/tags">Cancel</button>
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
        $record = QueryBuilder::table('tags')
            ->where('TgID', '=', $tagId)
            ->getPrepared();
        if ($record !== false && count($record) > 0) {
            $record = $record[0];
            ?>
            <h2>Edit Tag</h2>
            <form name="edittag" class="validate lwt-form-check" action="/tags#rec<?php echo $tagId; ?>" method="post">
            <input type="hidden" name="TgID" value="<?php echo $record['TgID']; ?>" />
            <table class="tab1" cellspacing="0" cellpadding="5">
                <tr>
                    <td class="td1 right">Tag:</td>
                    <td class="td1">
                        <input data_info="Tag" class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                        type="text" name="TgText" value="<?php echo htmlspecialchars($record['TgText'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="20" size="20" />
                        <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
                    </td>
                </tr>
                <tr>
                    <td class="td1 right">Comment:</td>
                    <td class="td1">
                        <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
                        data_maxlength="200" data_info="Comment" name="TgComment" rows="3"><?php echo htmlspecialchars($record['TgComment'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="td1 right" colspan="2">
                        <button type="button" data-action="cancel" data-url="/tags#rec<?php echo $tagId; ?>">Cancel</button>
                        <input type="submit" name="op" value="Change" />
                    </td>
                </tr>
            </table>
            </form>
            <?php
        }
    }

    /**
     * Show term tags list
     *
     * @param string $message      Result message to display
     * @param string $currentquery Current search query
     * @param array  $whereData    WHERE clause data
     * @param int    $currentsort  Current sort order
     * @param int    $currentpage  Current page number
     *
     * @return void
     */
    private function showTermTagsList(
        string $message,
        string $currentquery,
        array $whereData,
        int $currentsort,
        int $currentpage
    ): void {
        // Create service instance for term tags
        $service = new TagService('term');

        // Format error message if needed
        $message = $service->formatDuplicateError($message);

        TagService::getAllTermTags(true);   // refresh tags cache

        // Get counts and pagination
        $totalCount = $service->getCount($whereData);
        $pagination = $service->getPagination($totalCount, $currentpage);
        $currentpage = $pagination['currentPage'];

        // Get sort column
        $sortColumn = $service->getSortColumn($currentsort);

        // Get tags list
        $tags = $service->getList($whereData, $sortColumn, $currentpage, $pagination['perPage']);

        // Set view variables
        $currentQuery = $currentquery;
        $currentSort = $currentsort;
        $isTextTag = false;

        // Include the view
        include __DIR__ . '/../Views/Tags/tag_list.php';
    }

    // ==================== TEXT TAGS METHODS ====================

    /**
     * Process text tag actions (delete, mark, save, update)
     *
     * @param array{clause: string, params: array} $whereData WHERE clause data from buildWhereClause()
     *
     * @return string Result message
     */
    private function processTextTagActions(array $whereData): string
    {
        $message = '';
        $service = new TagService('text');

        // Mark actions
        if ($this->param('markaction')) {
            $message = $this->handleTextTagMarkAction($this->param('markaction'));
        } elseif ($this->param('allaction')) {
            // All actions
            if ($this->param('allaction') == 'delall') {
                $message = $service->deleteAll($whereData);
            }
        } elseif ($this->param('del')) {
            // Single delete
            $message = $service->delete((int)$this->param('del'));
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
                QueryBuilder::table('tags2')
                    ->whereIn('T2ID', $ids)
                    ->deletePrepared();
                $count = count($ids);
                $message = "Deleted: " . $count;
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
        $text = $this->param('T2Text', '');
        $comment = $this->param('T2Comment', '');

        if ($op == 'Save') {
            QueryBuilder::table('tags2')
                ->insertPrepared([
                    'T2Text' => $text,
                    'T2Comment' => $comment
                ]);
            return "Saved: 1";
        } elseif ($op == 'Change') {
            QueryBuilder::table('tags2')
                ->where('T2ID', '=', (int)$this->param('T2ID'))
                ->updatePrepared([
                    'T2Text' => $text,
                    'T2Comment' => $comment
                ]);
            return "Updated: 1";
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
        // Delete orphaned texttags that reference non-existent tags2
        // texttags inherits user context via TtTxID -> texts FK
        $sql = "DELETE texttags FROM texttags LEFT JOIN tags2 ON TtT2ID = T2ID WHERE T2ID IS NULL";
        $this->execute($sql, '');

        // Delete orphaned archtexttags that reference non-existent tags2
        // archtexttags inherits user context via AgAtID -> archivedtexts FK
        $sql = "DELETE archtexttags FROM archtexttags LEFT JOIN tags2 ON AgT2ID = T2ID WHERE T2ID IS NULL";
        $this->execute($sql, '');
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
        <form name="newtag" class="validate lwt-form-check" action="/tags/text" method="post">
        <table class="tab1" cellspacing="0" cellpadding="5">
        <tr>
            <td class="td1 right">Tag:</td>
            <td class="td1">
                <input class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                type="text" name="T2Text" data_info="Tag" value="" maxlength="20" />
                <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?>
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
            <button type="button" data-action="cancel" data-url="/tags/text">Cancel</button>
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
        $record = QueryBuilder::table('tags2')
            ->where('T2ID', '=', $tagId)
            ->getPrepared();
        if ($record !== false && count($record) > 0) {
            $record = $record[0];
            ?>
            <h2>Edit Tag</h2>
            <form name="edittag" class="validate lwt-form-check" action="/tags/text#rec<?php echo $tagId; ?>" method="post">
                <input type="hidden" name="T2ID" value="<?php echo $record['T2ID']; ?>" />
                <table class="tab1" cellspacing="0" cellpadding="5">
                <tr>
                <td class="td1 right">Tag:</td>
                <td class="td1">
                    <input data_info="Tag" class="notempty setfocus noblanksnocomma checkoutsidebmp respinput"
                    type="text" name="T2Text" value="<?php echo htmlspecialchars($record['T2Text'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="20" />
                    <?php echo IconHelper::render('circle-x', ['title' => 'Field must not be empty', 'alt' => 'Field must not be empty']); ?></td>
                </tr>
                <tr>
                    <td class="td1 right">Comment:</td>
                    <td class="td1">
                        <textarea class="textarea-noreturn checklength checkoutsidebmp respinput"
                        data_maxlength="200" data_info="Comment" name="T2Comment" rows="3"><?php echo htmlspecialchars($record['T2Comment'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </td>
                </tr>
                <tr>
                <td class="td1 right" colspan="2">
                <button type="button" data-action="cancel" data-url="/tags/text#rec<?php echo $tagId; ?>">Cancel</button>
                <input type="submit" name="op" value="Change" /></td>
                </tr>
                </table>
            </form>
            <?php
        }
    }

    /**
     * Show text tags list
     *
     * @param string $message      Result message to display
     * @param string $currentquery Current search query
     * @param array  $whereData    WHERE clause data
     * @param int    $currentsort  Current sort order
     * @param int    $currentpage  Current page number
     *
     * @return void
     */
    private function showTextTagsList(
        string $message,
        string $currentquery,
        array $whereData,
        int $currentsort,
        int $currentpage
    ): void {
        // Create service instance for text tags
        $service = new TagService('text');

        // Format error message if needed
        $message = $service->formatDuplicateError($message);

        TagService::getAllTextTags(true);   // refresh tags cache

        // Get counts and pagination
        $totalCount = $service->getCount($whereData);
        $pagination = $service->getPagination($totalCount, $currentpage);
        $currentpage = $pagination['currentPage'];

        // Get sort column
        $sortColumn = $service->getSortColumn($currentsort);

        // Get tags list
        $tags = $service->getList($whereData, $sortColumn, $currentpage, $pagination['perPage']);

        // Set view variables
        $currentQuery = $currentquery;
        $currentSort = $currentsort;
        $isTextTag = true;

        // Include the view
        include __DIR__ . '/../Views/Tags/tag_list.php';
    }
}
