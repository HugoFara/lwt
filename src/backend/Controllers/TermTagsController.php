<?php declare(strict_types=1);
/**
 * \file
 * \brief Term Tags Controller - Manage vocabulary term tags
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Controllers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Controllers;

use Lwt\Core\Http\InputValidator;
use Lwt\Database\QueryBuilder;
use Lwt\Database\Maintenance;
use Lwt\Services\TagService;
use Lwt\View\Helper\IconHelper;

require_once __DIR__ . '/../Services/TagService.php';
require_once __DIR__ . '/../View/Helper/SelectOptionsBuilder.php';

/**
 * Controller for managing term tags (tags applied to vocabulary words).
 *
 * Extends AbstractCrudController to provide standard CRUD operations
 * for the tags table.
 *
 * @category Lwt
 * @package  Lwt\Controllers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated 3.0.0 Use \Lwt\Modules\Tags\Http\TermTagController instead.
 *             This class will be removed in a future version.
 *
 * @psalm-suppress UnusedClass - Used via string-based routing in routes.php
 */
class TermTagsController extends AbstractCrudController
{
    protected string $pageTitle = 'Term Tags';
    protected string $resourceName = 'tag';

    private TagService $service;

    /** @var array{clause: string, params: array} */
    private array $whereData;

    private int $currentSort;
    private int $currentPage;
    private string $currentQuery;

    /**
     * Initialize the controller with dependencies.
     *
     * @param TagService|null $service Tag service for term tags (optional for BC)
     */
    public function __construct(?TagService $service = null)
    {
        parent::__construct();
        $this->service = $service ?? new TagService('term');
    }

    /**
     * Main index action - handles term tags CRUD.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        // Load filter/sort/page settings
        $this->currentSort = InputValidator::getIntWithDb("sort", 'currenttagsort', 1);
        $this->currentPage = InputValidator::getIntWithSession("page", "currenttagpage", 1);
        $this->currentQuery = InputValidator::getStringWithSession("query", "currenttagquery");

        // Build WHERE clause
        $this->whereData = $this->service->buildWhereClause($this->currentQuery);

        // Use parent's standard CRUD flow
        parent::index($params);
    }

    /**
     * Get the ID parameter name for update operations.
     *
     * @return string
     */
    protected function getIdParameterName(): string
    {
        return 'TgID';
    }

    /**
     * Handle create operation.
     *
     * @return string Result message
     */
    protected function handleCreate(): string
    {
        $text = $this->param('TgText', '');
        $comment = $this->param('TgComment', '');

        QueryBuilder::table('tags')
            ->insertPrepared([
                'TgText' => $text,
                'TgComment' => $comment
            ]);

        return "Saved: 1";
    }

    /**
     * Handle update operation.
     *
     * @param int $id Tag ID to update
     *
     * @return string Result message
     */
    protected function handleUpdate(int $id): string
    {
        $text = $this->param('TgText', '');
        $comment = $this->param('TgComment', '');

        QueryBuilder::table('tags')
            ->where('TgID', '=', $id)
            ->updatePrepared([
                'TgText' => $text,
                'TgComment' => $comment
            ]);

        return "Updated: 1";
    }

    /**
     * Handle delete operation.
     *
     * @param int $id Tag ID to delete
     *
     * @return string Result message
     */
    protected function handleDelete(int $id): string
    {
        $result = $this->service->delete($id);
        return $result;
    }

    /**
     * Handle bulk action on marked items.
     *
     * @param string $action Action code
     * @param int[]  $ids    Array of tag IDs
     *
     * @return string Result message
     */
    protected function handleBulkAction(string $action, array $ids): string
    {
        if ($action === 'del') {
            QueryBuilder::table('tags')
                ->whereIn('TgID', $ids)
                ->deletePrepared();

            $count = count($ids);
            $this->cleanupOrphanedLinks();
            Maintenance::adjustAutoIncrement('tags', 'TgID');

            return "Deleted: $count";
        }

        return parent::handleBulkAction($action, $ids);
    }

    /**
     * Process action on all filtered items.
     *
     * @param string $action Action code
     *
     * @return string Result message
     */
    protected function processAllAction(string $action): string
    {
        if ($action === 'delall') {
            return $this->service->deleteAll($this->whereData);
        }

        return parent::processAllAction($action);
    }

    /**
     * Render the list view.
     *
     * @param string $message Optional message to display
     *
     * @return void
     */
    protected function renderList(string $message): void
    {
        // Format error message if needed
        $message = $this->service->formatDuplicateError($message);

        $this->message($message, false);

        TagService::getAllTermTags(true);   // refresh tags cache

        // Get counts and pagination
        $totalCount = $this->service->getCount($this->whereData);
        $pagination = $this->service->getPagination($totalCount, $this->currentPage);
        $currentpage = $pagination['currentPage'];

        // Get sort column
        $sortColumn = $this->service->getSortColumn($this->currentSort);

        // Get tags list
        $tags = $this->service->getList($this->whereData, $sortColumn, $currentpage, $pagination['perPage']);

        // Set view variables
        $currentQuery = $this->currentQuery;
        $currentSort = $this->currentSort;
        $isTextTag = false;
        $service = $this->service;

        // Include the view
        include __DIR__ . '/../Views/Tags/tag_list.php';
    }

    /**
     * Render the create form.
     *
     * @return void
     */
    protected function renderCreateForm(): void
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
     * Render the edit form.
     *
     * @param int $id Tag ID to edit
     *
     * @return void
     */
    protected function renderEditForm(int $id): void
    {
        $record = QueryBuilder::table('tags')
            ->where('TgID', '=', $id)
            ->getPrepared();

        if (count($record) === 0) {
            $this->message("Tag not found", false);
            return;
        }

        $record = $record[0];
        ?>
        <h2>Edit Tag</h2>
        <form name="edittag" class="validate lwt-form-check" action="/tags#rec<?php echo $id; ?>" method="post">
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
                    <button type="button" data-action="cancel" data-url="/tags#rec<?php echo $id; ?>">Cancel</button>
                    <input type="submit" name="op" value="Change" />
                </td>
            </tr>
        </table>
        </form>
        <?php
    }

    /**
     * Cleanup orphaned term tag links after deletion.
     *
     * @return void
     */
    private function cleanupOrphanedLinks(): void
    {
        $sql = "DELETE wordtags FROM wordtags LEFT JOIN tags ON WtTgID = TgID WHERE TgID IS NULL";
        $this->execute($sql);
    }
}
