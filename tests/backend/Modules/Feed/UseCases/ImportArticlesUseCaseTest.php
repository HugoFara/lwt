<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Feed\UseCases;

use Lwt\Modules\Feed\Application\Services\ArticleExtractor;
use Lwt\Modules\Feed\Application\UseCases\ImportArticles;
use Lwt\Modules\Feed\Domain\Article;
use Lwt\Modules\Feed\Domain\ArticleRepositoryInterface;
use Lwt\Modules\Feed\Domain\Feed;
use Lwt\Modules\Feed\Domain\FeedRepositoryInterface;
use Lwt\Modules\Feed\Domain\TextCreationInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the ImportArticles use case.
 *
 * Tests the feed import pipeline including article extraction,
 * text creation, deduplication, and archival.
 *
 * @covers \Lwt\Modules\Feed\Application\UseCases\ImportArticles
 */
class ImportArticlesUseCaseTest extends TestCase
{
    /** @var ArticleRepositoryInterface&MockObject */
    private ArticleRepositoryInterface $articleRepository;

    /** @var FeedRepositoryInterface&MockObject */
    private FeedRepositoryInterface $feedRepository;

    /** @var TextCreationInterface&MockObject */
    private TextCreationInterface $textCreation;

    /** @var ArticleExtractor&MockObject */
    private ArticleExtractor $articleExtractor;

    private ImportArticles $importArticles;

    protected function setUp(): void
    {
        $this->articleRepository = $this->createMock(ArticleRepositoryInterface::class);
        $this->feedRepository = $this->createMock(FeedRepositoryInterface::class);
        $this->textCreation = $this->createMock(TextCreationInterface::class);
        $this->articleExtractor = $this->createMock(ArticleExtractor::class);

        $this->importArticles = new ImportArticles(
            $this->articleRepository,
            $this->feedRepository,
            $this->textCreation,
            $this->articleExtractor
        );
    }

    // =====================
    // EMPTY INPUT TESTS
    // =====================

    public function testExecuteWithEmptyArrayReturnsZeroCounts(): void
    {
        $result = $this->importArticles->execute([]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['archived']);
        $this->assertEmpty($result['errors']);
    }

    // =====================
    // SUCCESSFUL IMPORT TESTS
    // =====================

    public function testExecuteImportsSingleArticleSuccessfully(): void
    {
        $articleId = 1;
        $feedId = 10;
        $langId = 1;

        // Create mock article
        $article = $this->createMockArticle($articleId, $feedId, 'Test Title', 'http://example.com/article');

        // Create mock feed
        $feed = $this->createMockFeed($feedId, $langId);

        $this->articleRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with([$articleId])
            ->willReturn([$article]);

        $this->feedRepository
            ->expects($this->once())
            ->method('find')
            ->with($feedId)
            ->willReturn($feed);

        // Article extractor returns extracted data
        $this->articleExtractor
            ->expects($this->once())
            ->method('extract')
            ->willReturn([
                0 => [
                    'TxTitle' => 'Test Title',
                    'TxText' => 'Article content here.',
                    'TxSourceURI' => 'http://example.com/article',
                    'TxAudioURI' => '',
                ]
            ]);

        // Source URI doesn't exist yet
        $this->textCreation
            ->expects($this->once())
            ->method('sourceUriExists')
            ->with('http://example.com/article')
            ->willReturn(false);

        // Text creation succeeds
        $this->textCreation
            ->expects($this->once())
            ->method('createText')
            ->with($langId, 'Test Title', 'Article content here.', '', 'http://example.com/article', $this->anything());

        // Archive called
        $this->textCreation
            ->expects($this->once())
            ->method('archiveOldTexts')
            ->willReturn(['archived' => 0]);

        $result = $this->importArticles->execute([$articleId]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testExecuteImportsMultipleArticlesFromSameFeed(): void
    {
        $feedId = 10;
        $langId = 1;

        $articles = [
            $this->createMockArticle(1, $feedId, 'Article 1', 'http://example.com/1'),
            $this->createMockArticle(2, $feedId, 'Article 2', 'http://example.com/2'),
            $this->createMockArticle(3, $feedId, 'Article 3', 'http://example.com/3'),
        ];

        $feed = $this->createMockFeed($feedId, $langId);

        $this->articleRepository
            ->method('findByIds')
            ->willReturn($articles);

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->articleExtractor
            ->method('extract')
            ->willReturn([
                0 => ['TxTitle' => 'Article 1', 'TxText' => 'Content 1', 'TxSourceURI' => 'http://example.com/1', 'TxAudioURI' => ''],
                1 => ['TxTitle' => 'Article 2', 'TxText' => 'Content 2', 'TxSourceURI' => 'http://example.com/2', 'TxAudioURI' => ''],
                2 => ['TxTitle' => 'Article 3', 'TxText' => 'Content 3', 'TxSourceURI' => 'http://example.com/3', 'TxAudioURI' => ''],
            ]);

        $this->textCreation
            ->method('sourceUriExists')
            ->willReturn(false);

        $this->textCreation
            ->expects($this->exactly(3))
            ->method('createText');

        $this->textCreation
            ->method('archiveOldTexts')
            ->willReturn(['archived' => 0]);

        $result = $this->importArticles->execute([1, 2, 3]);

        $this->assertEquals(3, $result['imported']);
        $this->assertEquals(0, $result['failed']);
    }

    // =====================
    // DEDUPLICATION TESTS
    // =====================

    public function testExecuteSkipsDuplicateArticles(): void
    {
        $feedId = 10;
        $langId = 1;

        $article = $this->createMockArticle(1, $feedId, 'Duplicate', 'http://example.com/duplicate');
        $feed = $this->createMockFeed($feedId, $langId);

        $this->articleRepository
            ->method('findByIds')
            ->willReturn([$article]);

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->articleExtractor
            ->method('extract')
            ->willReturn([
                0 => ['TxTitle' => 'Duplicate', 'TxText' => 'Content', 'TxSourceURI' => 'http://example.com/duplicate', 'TxAudioURI' => ''],
            ]);

        // Source URI already exists
        $this->textCreation
            ->expects($this->once())
            ->method('sourceUriExists')
            ->with('http://example.com/duplicate')
            ->willReturn(true);

        // createText should NOT be called
        $this->textCreation
            ->expects($this->never())
            ->method('createText');

        $result = $this->importArticles->execute([1]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['failed']);
    }

    // =====================
    // ERROR HANDLING TESTS
    // =====================

    public function testExecuteHandlesExtractionErrors(): void
    {
        $feedId = 10;
        $langId = 1;

        $article = $this->createMockArticle(1, $feedId, 'Bad Article', 'http://example.com/bad');
        $feed = $this->createMockFeed($feedId, $langId);

        $this->articleRepository
            ->method('findByIds')
            ->willReturn([$article]);

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        // Extractor returns error
        $this->articleExtractor
            ->method('extract')
            ->willReturn([
                'error' => [
                    'message' => 'Extraction failed',
                    'link' => ['http://example.com/bad'],
                ]
            ]);

        $this->articleRepository
            ->expects($this->once())
            ->method('markAsError')
            ->with('http://example.com/bad');

        $result = $this->importArticles->execute([1]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['failed']);
    }

    public function testExecuteHandlesTextCreationException(): void
    {
        $feedId = 10;
        $langId = 1;

        $article = $this->createMockArticle(1, $feedId, 'Article', 'http://example.com/article');
        $feed = $this->createMockFeed($feedId, $langId);

        $this->articleRepository
            ->method('findByIds')
            ->willReturn([$article]);

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->articleExtractor
            ->method('extract')
            ->willReturn([
                0 => ['TxTitle' => 'Article', 'TxText' => 'Content', 'TxSourceURI' => 'http://example.com/article', 'TxAudioURI' => ''],
            ]);

        $this->textCreation
            ->method('sourceUriExists')
            ->willReturn(false);

        // Text creation throws exception
        $this->textCreation
            ->method('createText')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->importArticles->execute([1]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['failed']);
        $this->assertContains('http://example.com/article', $result['errors'][(string) $feedId]);
    }

    public function testExecuteHandlesMissingFeed(): void
    {
        $feedId = 999;

        $article = $this->createMockArticle(1, $feedId, 'Article', 'http://example.com/article');

        $this->articleRepository
            ->method('findByIds')
            ->willReturn([$article]);

        $this->feedRepository
            ->expects($this->once())
            ->method('find')
            ->with($feedId)
            ->willReturn(null);

        // Should not call extractor when feed is missing
        $this->articleExtractor
            ->expects($this->never())
            ->method('extract');

        $result = $this->importArticles->execute([1]);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['failed']);
    }

    // =====================
    // ARCHIVAL TESTS
    // =====================

    public function testExecuteArchivesOldTextsWhenMaxTextIsSet(): void
    {
        $feedId = 10;
        $langId = 1;

        $article = $this->createMockArticle(1, $feedId, 'Article', 'http://example.com/article');
        $feed = $this->createMockFeed($feedId, $langId, 5); // max_texts = 5

        $this->articleRepository
            ->method('findByIds')
            ->willReturn([$article]);

        $this->feedRepository
            ->method('find')
            ->willReturn($feed);

        $this->articleExtractor
            ->method('extract')
            ->willReturn([
                0 => ['TxTitle' => 'Article', 'TxText' => 'Content', 'TxSourceURI' => 'http://example.com/article', 'TxAudioURI' => ''],
            ]);

        $this->textCreation
            ->method('sourceUriExists')
            ->willReturn(false);

        $this->textCreation
            ->method('createText');

        // Archive should be called with correct parameters
        $this->textCreation
            ->expects($this->once())
            ->method('archiveOldTexts')
            ->with($this->stringContains('feed_'), 5)
            ->willReturn(['archived' => 2]);

        $result = $this->importArticles->execute([1]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(2, $result['archived']);
    }

    // =====================
    // MULTIPLE FEEDS TESTS
    // =====================

    public function testExecuteGroupsArticlesByFeed(): void
    {
        $feed1 = $this->createMockFeed(1, 1);
        $feed2 = $this->createMockFeed(2, 2);

        $articles = [
            $this->createMockArticle(1, 1, 'Article 1-1', 'http://feed1.com/1'),
            $this->createMockArticle(2, 1, 'Article 1-2', 'http://feed1.com/2'),
            $this->createMockArticle(3, 2, 'Article 2-1', 'http://feed2.com/1'),
        ];

        $this->articleRepository
            ->method('findByIds')
            ->willReturn($articles);

        $this->feedRepository
            ->method('find')
            ->willReturnCallback(fn($id) => $id === 1 ? $feed1 : $feed2);

        $this->articleExtractor
            ->expects($this->exactly(2)) // Called once per feed
            ->method('extract')
            ->willReturn([]);

        $this->importArticles->execute([1, 2, 3]);
    }

    // =====================
    // HELPER METHODS
    // =====================

    /**
     * Create a mock Article object.
     */
    private function createMockArticle(int $id, int $feedId, string $title, string $link): Article
    {
        $article = $this->createMock(Article::class);
        $article->method('feedId')->willReturn($feedId);
        $article->method('title')->willReturn($title);
        $article->method('link')->willReturn($link);
        $article->method('description')->willReturn('');
        $article->method('audio')->willReturn('');
        $article->method('text')->willReturn('');

        return $article;
    }

    /**
     * Create a mock Feed object.
     */
    private function createMockFeed(int $id, int $langId, int $maxTexts = 100): Feed
    {
        // Use the real reconstitute method for a more realistic mock
        return Feed::reconstitute(
            $id,
            $langId,
            'Test Feed',
            'http://example.com/feed.rss',
            '//article',
            '',
            0,
            "max_texts={$maxTexts}"
        );
    }
}
