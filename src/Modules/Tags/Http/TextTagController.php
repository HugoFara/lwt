<?php declare(strict_types=1);
/**
 * Text Tag Controller
 *
 * Controller for managing text tags in the Tags module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Tags\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Tags\Http;

use Lwt\Controllers\AbstractCrudController;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Modules\Tags\Domain\TagType;

/**
 * Controller for managing text tags (tags applied to texts/documents).
 *
 * @since 3.0.0
 */
class TextTagController extends AbstractCrudController
{
    protected string $pageTitle = 'Text Tags';
    protected string $resourceName = 'tag';

    private TagsFacade $facade;

    /** @var string */
    private string $currentQuery;

    /** @var int */
    private int $currentSort;

    /** @var int */
    private int $currentPage;

    /**
     * Constructor.
     *
     * @param TagsFacade|null $facade Tags facade
     */
    public function __construct(?TagsFacade $facade = null)
    {
        parent::__construct();
        $this->facade = $facade ?? TagsFacade::forTextTags();
    }

    /**
     * Main index action.
     *
     * @param array $params Route parameters
     *
     * @return void
     */
    public function index(array $params): void
    {
        // Load filter/sort/page settings
        $this->currentSort = InputValidator::getIntWithDb("sort", 'currenttextagsort', 1);
        $this->currentPage = InputValidator::getIntWithSession("page", "currenttextagpage", 1);
        $this->currentQuery = InputValidator::getStringWithSession("query", "currenttextagquery");

        parent::index($params);
    }

    /**
     * Get the ID parameter name.
     *
     * @return string
     */
    protected function getIdParameterName(): string
    {
        return 'T2ID';
    }

    /**
     * Handle create operation.
     *
     * @return string Result message
     */
    protected function handleCreate(): string
    {
        $text = $this->param('T2Text', '');
        $comment = $this->param('T2Comment', '');

        return $this->facade->create($text, $comment);
    }

    /**
     * Handle update operation.
     *
     * @param int $id Tag ID
     *
     * @return string Result message
     */
    protected function handleUpdate(int $id): string
    {
        $text = $this->param('T2Text', '');
        $comment = $this->param('T2Comment', '');

        return $this->facade->update($id, $text, $comment);
    }

    /**
     * Handle delete operation.
     *
     * @param int $id Tag ID
     *
     * @return string Result message
     */
    protected function handleDelete(int $id): string
    {
        return $this->facade->delete($id);
    }

    /**
     * Handle bulk action.
     *
     * @param string $action Action code
     * @param int[]  $ids    Tag IDs
     *
     * @return string Result message
     */
    protected function handleBulkAction(string $action, array $ids): string
    {
        if ($action === 'del') {
            $result = $this->facade->deleteMultiple($ids);
            $this->facade->cleanupOrphanedLinks();
            return $result;
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
            return $this->facade->deleteAll($this->currentQuery);
        }

        return parent::processAllAction($action);
    }

    /**
     * Render the list view.
     *
     * @param string $message Message to display
     *
     * @psalm-suppress UnusedVariable Variables are used by included view
     *
     * @return void
     */
    protected function renderList(string $message): void
    {
        $this->message($message, false);

        TagsFacade::getAllTextTags(true); // Refresh cache

        // Get counts and pagination
        $totalCount = $this->facade->getCount($this->currentQuery);
        $pagination = $this->facade->getPagination($totalCount, $this->currentPage);

        // Get sort column
        $sortColumn = $this->facade->getSortColumn($this->currentSort);

        // Get tags list
        $tags = $this->facade->getList(
            $this->currentQuery,
            $sortColumn,
            $pagination['currentPage'],
            $pagination['perPage']
        );

        // Set view variables
        $currentQuery = $this->currentQuery;
        $currentSort = $this->currentSort;
        $isTextTag = true;
        $service = $this->facade; // Backward compatible variable name

        include __DIR__ . '/../Views/tag_list.php';
    }

    /**
     * Render the create form.
     *
     * @psalm-suppress UnusedVariable Variables are used by included view
     *
     * @return void
     */
    protected function renderCreateForm(): void
    {
        $mode = 'new';
        $tag = null;
        $service = $this->facade;
        $formFieldPrefix = 'T2';

        include __DIR__ . '/../Views/tag_form.php';
    }

    /**
     * Render the edit form.
     *
     * @param int $id Tag ID
     *
     * @psalm-suppress UnusedVariable Variables are used by included view
     *
     * @return void
     */
    protected function renderEditForm(int $id): void
    {
        $tag = $this->facade->getById($id);

        if ($tag === null) {
            $this->message("Tag not found", false);
            return;
        }

        $mode = 'edit';
        $service = $this->facade;
        $formFieldPrefix = 'T2';

        include __DIR__ . '/../Views/tag_form.php';
    }
}
