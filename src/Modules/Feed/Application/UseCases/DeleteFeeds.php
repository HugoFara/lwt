<?php

declare(strict_types=1);

/**
 * Delete Feeds Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Feed\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Feed\Application\UseCases;

use Lwt\Modules\Feed\Domain\ArticleRepositoryInterface;
use Lwt\Modules\Feed\Domain\FeedRepositoryInterface;

/**
 * Use case for deleting feeds and their articles.
 *
 * @since 3.0.0
 */
class DeleteFeeds
{
    /**
     * Constructor.
     *
     * @param FeedRepositoryInterface    $feedRepository    Feed repository
     * @param ArticleRepositoryInterface $articleRepository Article repository
     */
    public function __construct(
        private FeedRepositoryInterface $feedRepository,
        private ArticleRepositoryInterface $articleRepository
    ) {
    }

    /**
     * Execute the use case.
     *
     * @param int[] $feedIds Feed IDs to delete
     *
     * @return array{feeds: int, articles: int} Counts of deleted items
     */
    public function execute(array $feedIds): array
    {
        if (empty($feedIds)) {
            return ['feeds' => 0, 'articles' => 0];
        }

        // Delete articles first (foreign key constraint)
        $articlesDeleted = $this->articleRepository->deleteByFeeds($feedIds);

        // Delete feeds
        $feedsDeleted = $this->feedRepository->deleteMultiple($feedIds);

        return [
            'feeds' => $feedsDeleted,
            'articles' => $articlesDeleted,
        ];
    }

    /**
     * Delete a single feed.
     *
     * @param int $feedId Feed ID
     *
     * @return bool True if deleted, false if not found
     */
    public function executeSingle(int $feedId): bool
    {
        $result = $this->execute([$feedId]);
        return $result['feeds'] > 0;
    }
}
