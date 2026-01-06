<?php declare(strict_types=1);
/**
 * Import Articles Use Case
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

use Lwt\Modules\Feed\Application\Services\ArticleExtractor;
use Lwt\Modules\Feed\Domain\ArticleRepositoryInterface;
use Lwt\Modules\Feed\Domain\FeedRepositoryInterface;
use Lwt\Modules\Feed\Domain\TextCreationInterface;

/**
 * Use case for importing articles as texts.
 *
 * Converts feed articles to LWT texts by:
 * 1. Extracting content from article URLs
 * 2. Creating text entries with parsing
 * 3. Applying tags and archiving old texts
 *
 * @since 3.0.0
 */
class ImportArticles
{
    /**
     * Constructor.
     *
     * @param ArticleRepositoryInterface $articleRepository Article repository
     * @param FeedRepositoryInterface    $feedRepository    Feed repository
     * @param TextCreationInterface      $textCreation      Text creation adapter
     * @param ArticleExtractor           $articleExtractor  Article extractor service
     */
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
        private FeedRepositoryInterface $feedRepository,
        private TextCreationInterface $textCreation,
        private ArticleExtractor $articleExtractor
    ) {
    }

    /**
     * Execute the use case.
     *
     * @param int[] $articleIds Article IDs to import
     *
     * @return array{
     *     imported: int,
     *     failed: int,
     *     archived: int,
     *     errors: array<string, string[]>
     * }
     */
    public function execute(array $articleIds): array
    {
        $result = [
            'imported' => 0,
            'failed' => 0,
            'archived' => 0,
            'errors' => [],
        ];

        if (empty($articleIds)) {
            return $result;
        }

        // Get articles with their feed data
        $articles = $this->articleRepository->findByIds($articleIds);

        // Group articles by feed for batch processing
        $byFeed = [];
        foreach ($articles as $article) {
            $feedId = $article->feedId();
            if (!isset($byFeed[$feedId])) {
                $byFeed[$feedId] = [];
            }
            $byFeed[$feedId][] = $article;
        }

        // Process each feed's articles
        foreach ($byFeed as $feedId => $feedArticles) {
            $feed = $this->feedRepository->find($feedId);
            if ($feed === null) {
                continue;
            }

            $feedResult = $this->importFeedArticles($feed, $feedArticles);
            $result['imported'] += $feedResult['imported'];
            $result['failed'] += $feedResult['failed'];
            $result['archived'] += $feedResult['archived'];

            if (!empty($feedResult['errors'])) {
                $result['errors'][(string) $feedId] = $feedResult['errors'];
            }
        }

        return $result;
    }

    /**
     * Import articles for a specific feed.
     *
     * @param \Lwt\Modules\Feed\Domain\Feed $feed     Feed entity
     * @param array                         $articles Article entities
     *
     * @return array Import result
     */
    private function importFeedArticles($feed, array $articles): array
    {
        $result = [
            'imported' => 0,
            'failed' => 0,
            'archived' => 0,
            'errors' => [],
        ];

        $options = $feed->options();
        $tagName = $options->get('tag') ?? ('feed_' . (int)$feed->id());
        $maxTexts = (int) ($options->get('max_texts') ?? 100);
        $charset = $options->get('charset');

        // Prepare article data for extraction
        $feedData = [];
        foreach ($articles as $article) {
            $feedData[] = [
                'title' => $article->title(),
                'link' => $article->link(),
                'desc' => $article->description(),
                'audio' => $article->audio(),
                'text' => $article->text(),
            ];
        }

        // Extract content from articles
        $extracted = $this->articleExtractor->extract(
            $feedData,
            $feed->articleSectionTags(),
            $feed->filterTags(),
            $charset
        );

        // Process extracted content
        foreach ($extracted as $key => $data) {
            if ($key === 'error') {
                $result['errors'] = $data['link'] ?? [];
                $result['failed'] += count($data['link'] ?? []);

                // Mark failed articles as error
                foreach ($data['link'] ?? [] as $link) {
                    $this->articleRepository->markAsError($link);
                }
                continue;
            }

            // Skip if already imported
            if ($this->textCreation->sourceUriExists($data['TxSourceURI'])) {
                continue;
            }

            // Create text
            try {
                $this->textCreation->createText(
                    $feed->languageId(),
                    $data['TxTitle'],
                    $data['TxText'],
                    $data['TxAudioURI'] ?? '',
                    $data['TxSourceURI'],
                    $tagName
                );
                $result['imported']++;
            } catch (\Exception $e) {
                $result['failed']++;
                $result['errors'][] = $data['TxSourceURI'];
            }
        }

        // Archive old texts if needed
        if ($maxTexts > 0 && $result['imported'] > 0) {
            $archiveResult = $this->textCreation->archiveOldTexts($tagName, $maxTexts);
            $result['archived'] = $archiveResult['archived'];
        }

        return $result;
    }
}
