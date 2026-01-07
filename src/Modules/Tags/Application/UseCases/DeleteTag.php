<?php declare(strict_types=1);
/**
 * Delete Tag Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Tags\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Tags\Application\UseCases;

use Lwt\Modules\Tags\Domain\TagAssociationInterface;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;

/**
 * Use case for deleting tags.
 *
 * @since 3.0.0
 */
class DeleteTag
{
    private TagRepositoryInterface $repository;
    private TagAssociationInterface $association;

    /**
     * Constructor.
     *
     * @param TagRepositoryInterface  $repository  Tag repository
     * @param TagAssociationInterface $association Tag association handler
     */
    public function __construct(
        TagRepositoryInterface $repository,
        TagAssociationInterface $association
    ) {
        $this->repository = $repository;
        $this->association = $association;
    }

    /**
     * Delete a single tag by ID.
     *
     * @param int $id Tag ID
     *
     * @return bool True if deleted
     */
    public function execute(int $id): bool
    {
        $deleted = $this->repository->delete($id);

        if ($deleted) {
            $this->association->cleanupOrphanedLinks();
        }

        return $deleted;
    }

    /**
     * Delete a single tag and return message.
     *
     * @param int $id Tag ID
     *
     * @return string Result message
     */
    public function executeWithMessage(int $id): string
    {
        $deleted = $this->execute($id);
        return $deleted ? "Deleted" : "Deleted (0 rows affected)";
    }

    /**
     * Delete multiple tags by IDs.
     *
     * @param int[] $ids Tag IDs
     *
     * @return int Number of deleted tags
     */
    public function executeMultiple(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }

        $deleted = $this->repository->deleteMultiple($ids);

        if ($deleted > 0) {
            $this->association->cleanupOrphanedLinks();
        }

        return $deleted;
    }

    /**
     * Delete multiple tags and return message.
     *
     * @param int[] $ids Tag IDs
     *
     * @return string Result message
     */
    public function executeMultipleWithMessage(array $ids): string
    {
        $deleted = $this->executeMultiple($ids);
        return $deleted > 0 ? "Deleted" : "Deleted (0 rows affected)";
    }

    /**
     * Delete all tags matching a filter.
     *
     * @param string $query Filter query (supports * wildcard)
     *
     * @return int Number of deleted tags
     */
    public function executeAll(string $query = ''): int
    {
        $deleted = $this->repository->deleteAll($query);

        if ($deleted > 0) {
            $this->association->cleanupOrphanedLinks();
        }

        return $deleted;
    }

    /**
     * Delete all tags matching filter and return message.
     *
     * @param string $query Filter query
     *
     * @return string Result message
     */
    public function executeAllWithMessage(string $query = ''): string
    {
        $deleted = $this->executeAll($query);
        return $deleted > 0 ? "Deleted" : "Deleted (0 rows affected)";
    }
}
