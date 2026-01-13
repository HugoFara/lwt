<?php

declare(strict_types=1);

namespace Tests\Modules\Feed\Infrastructure;

use PHPUnit\Framework\TestCase;
use Lwt\Modules\Feed\Infrastructure\MySqlArticleRepository;
use Lwt\Modules\Feed\Domain\Article;

/**
 * Tests for MySqlArticleRepository.
 *
 * @covers \Lwt\Modules\Feed\Infrastructure\MySqlArticleRepository
 */
class MySqlArticleRepositoryTest extends TestCase
{
    private MySqlArticleRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new MySqlArticleRepository();
    }

    // =========================================================================
    // Configuration Property Tests
    // =========================================================================

    public function testTableNameProperty(): void
    {
        $reflection = new \ReflectionProperty(MySqlArticleRepository::class, 'tableName');
        $reflection->setAccessible(true);

        $this->assertSame('feed_links', $reflection->getValue($this->repository));
    }

    public function testPrimaryKeyProperty(): void
    {
        $reflection = new \ReflectionProperty(MySqlArticleRepository::class, 'primaryKey');
        $reflection->setAccessible(true);

        $this->assertSame('FlID', $reflection->getValue($this->repository));
    }

    public function testColumnMapProperty(): void
    {
        $reflection = new \ReflectionProperty(MySqlArticleRepository::class, 'columnMap');
        $reflection->setAccessible(true);

        $columnMap = $reflection->getValue($this->repository);

        $this->assertIsArray($columnMap);
        $this->assertSame('FlID', $columnMap['id']);
        $this->assertSame('FlNfID', $columnMap['feedId']);
        $this->assertSame('FlTitle', $columnMap['title']);
        $this->assertSame('FlLink', $columnMap['link']);
        $this->assertSame('FlDescription', $columnMap['description']);
        $this->assertSame('FlDate', $columnMap['date']);
        $this->assertSame('FlAudio', $columnMap['audio']);
        $this->assertSame('FlText', $columnMap['text']);
    }

    // =========================================================================
    // mapToEntity() Tests via Reflection
    // =========================================================================

    public function testMapToEntityCreatesArticle(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $row = [
            'FlID' => '123',
            'FlNfID' => '456',
            'FlTitle' => 'Test Article',
            'FlLink' => 'https://example.com/article',
            'FlDescription' => 'Test description',
            'FlDate' => '2024-01-15 10:30:00',
            'FlAudio' => 'https://example.com/audio.mp3',
            'FlText' => 'Full article text',
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertSame(123, $article->id());
        $this->assertSame(456, $article->feedId());
        $this->assertSame('Test Article', $article->title());
        $this->assertSame('https://example.com/article', $article->link());
        $this->assertSame('Test description', $article->description());
        $this->assertSame('2024-01-15 10:30:00', $article->date());
        $this->assertSame('https://example.com/audio.mp3', $article->audio());
        $this->assertSame('Full article text', $article->text());
    }

    public function testMapToEntityWithNullOptionalFields(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $row = [
            'FlID' => '1',
            'FlNfID' => '2',
            'FlTitle' => 'Title',
            'FlLink' => 'https://example.com',
            'FlDescription' => null,
            'FlDate' => null,
            'FlAudio' => null,
            'FlText' => null,
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertSame('', $article->description());
        $this->assertSame('', $article->date());
        $this->assertSame('', $article->audio());
        $this->assertSame('', $article->text());
    }

    public function testMapToEntityWithMissingOptionalFields(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $row = [
            'FlID' => '1',
            'FlNfID' => '2',
            'FlTitle' => 'Title',
            'FlLink' => 'https://example.com',
            // Missing: FlDescription, FlDate, FlAudio, FlText
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertSame('', $article->description());
        $this->assertSame('', $article->date());
        $this->assertSame('', $article->audio());
        $this->assertSame('', $article->text());
    }

    public function testMapToEntityConvertsStringIdsToInt(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $row = [
            'FlID' => '999',
            'FlNfID' => '888',
            'FlTitle' => 'Title',
            'FlLink' => 'https://example.com',
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertSame(999, $article->id());
        $this->assertSame(888, $article->feedId());
    }

    public function testMapToEntityWithUnicodeContent(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $row = [
            'FlID' => '1',
            'FlNfID' => '2',
            'FlTitle' => '日本語タイトル',
            'FlLink' => 'https://example.com/日本語',
            'FlDescription' => 'Descripción en español',
            'FlDate' => '2024-01-15',
            'FlAudio' => '',
            'FlText' => 'Текст на русском',
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertSame('日本語タイトル', $article->title());
        $this->assertSame('Descripción en español', $article->description());
        $this->assertSame('Текст на русском', $article->text());
    }

    // =========================================================================
    // mapToRow() Tests via Reflection
    // =========================================================================

    public function testMapToRowConvertsArticleToArray(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToRow');
        $method->setAccessible(true);

        $article = Article::reconstitute(
            123,
            456,
            'Test Title',
            'https://example.com',
            'Description',
            '2024-01-15',
            'https://example.com/audio.mp3',
            'Full text'
        );

        $row = $method->invoke($this->repository, $article);

        $this->assertIsArray($row);
        $this->assertSame(456, $row['FlNfID']);
        $this->assertSame('Test Title', $row['FlTitle']);
        $this->assertSame('https://example.com', $row['FlLink']);
        $this->assertSame('Description', $row['FlDescription']);
        $this->assertSame('2024-01-15', $row['FlDate']);
        $this->assertSame('https://example.com/audio.mp3', $row['FlAudio']);
        $this->assertSame('Full text', $row['FlText']);
    }

    public function testMapToRowDoesNotIncludeId(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToRow');
        $method->setAccessible(true);

        $article = Article::reconstitute(123, 1, 'Title', 'link', '', '', '', '');

        $row = $method->invoke($this->repository, $article);

        $this->assertArrayNotHasKey('FlID', $row);
        $this->assertArrayNotHasKey('id', $row);
    }

    public function testMapToRowWithEmptyOptionalFields(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToRow');
        $method->setAccessible(true);

        $article = Article::reconstitute(1, 2, 'Title', 'link', '', '', '', '');

        $row = $method->invoke($this->repository, $article);

        $this->assertSame('', $row['FlDescription']);
        $this->assertSame('', $row['FlDate']);
        $this->assertSame('', $row['FlAudio']);
        $this->assertSame('', $row['FlText']);
    }

    // =========================================================================
    // getEntityId() Tests via Reflection
    // =========================================================================

    public function testGetEntityIdReturnsArticleId(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'getEntityId');
        $method->setAccessible(true);

        $article = Article::reconstitute(999, 1, 'Title', 'link', '', '', '', '');

        $id = $method->invoke($this->repository, $article);

        $this->assertSame(999, $id);
    }

    public function testGetEntityIdReturnsZeroForNewArticle(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'getEntityId');
        $method->setAccessible(true);

        $article = Article::create(1, 'Title', 'link');

        $id = $method->invoke($this->repository, $article);

        $this->assertSame(0, $id);
    }

    // =========================================================================
    // setEntityId() Tests via Reflection
    // =========================================================================

    public function testSetEntityIdSetsArticleId(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'setEntityId');
        $method->setAccessible(true);

        $article = Article::create(1, 'Title', 'link');
        $this->assertNull($article->id());

        $method->invoke($this->repository, $article, 123);

        $this->assertSame(123, $article->id());
    }

    // =========================================================================
    // findByIds() with Empty Array
    // =========================================================================

    public function testFindByIdsWithEmptyArrayReturnsEmpty(): void
    {
        $result = $this->repository->findByIds([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // =========================================================================
    // deleteByFeeds() with Empty Array
    // =========================================================================

    public function testDeleteByFeedsWithEmptyArrayReturnsZero(): void
    {
        $result = $this->repository->deleteByFeeds([]);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // deleteByIds() with Empty Array
    // =========================================================================

    public function testDeleteByIdsWithEmptyArrayReturnsZero(): void
    {
        $result = $this->repository->deleteByIds([]);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // resetErrorsByFeeds() with Empty Array
    // =========================================================================

    public function testResetErrorsByFeedsWithEmptyArrayReturnsZero(): void
    {
        $result = $this->repository->resetErrorsByFeeds([]);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // countByFeeds() with Empty Array
    // =========================================================================

    public function testCountByFeedsWithEmptyArrayReturnsZero(): void
    {
        $result = $this->repository->countByFeeds([]);

        $this->assertSame(0, $result);
    }

    // =========================================================================
    // findByFeedsWithStatus() with Empty Array
    // =========================================================================

    public function testFindByFeedsWithStatusWithEmptyArrayReturnsEmpty(): void
    {
        $result = $this->repository->findByFeedsWithStatus([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // =========================================================================
    // Roundtrip Tests (mapToEntity -> mapToRow)
    // =========================================================================

    public function testMapRoundtrip(): void
    {
        $mapToEntity = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $mapToEntity->setAccessible(true);
        $mapToRow = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToRow');
        $mapToRow->setAccessible(true);

        $originalRow = [
            'FlID' => '123',
            'FlNfID' => '456',
            'FlTitle' => 'Test Article',
            'FlLink' => 'https://example.com/article',
            'FlDescription' => 'Test description',
            'FlDate' => '2024-01-15 10:30:00',
            'FlAudio' => 'https://example.com/audio.mp3',
            'FlText' => 'Full article text',
        ];

        $article = $mapToEntity->invoke($this->repository, $originalRow);
        $resultRow = $mapToRow->invoke($this->repository, $article);

        // Verify all fields except ID are preserved
        $this->assertSame(456, $resultRow['FlNfID']);
        $this->assertSame('Test Article', $resultRow['FlTitle']);
        $this->assertSame('https://example.com/article', $resultRow['FlLink']);
        $this->assertSame('Test description', $resultRow['FlDescription']);
        $this->assertSame('2024-01-15 10:30:00', $resultRow['FlDate']);
        $this->assertSame('https://example.com/audio.mp3', $resultRow['FlAudio']);
        $this->assertSame('Full article text', $resultRow['FlText']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testMapToEntityWithErrorMarkedLink(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $row = [
            'FlID' => '1',
            'FlNfID' => '2',
            'FlTitle' => 'Error Article',
            'FlLink' => ' https://example.com', // Space prefix indicates error
            'FlDescription' => '',
            'FlDate' => '',
            'FlAudio' => '',
            'FlText' => '',
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertTrue($article->hasError());
        $this->assertStringStartsWith(' ', $article->link());
    }

    public function testMapToEntityWithLongValues(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $longText = str_repeat('a', 10000);
        $row = [
            'FlID' => '1',
            'FlNfID' => '2',
            'FlTitle' => 'Title',
            'FlLink' => 'https://example.com',
            'FlDescription' => $longText,
            'FlDate' => '',
            'FlAudio' => '',
            'FlText' => $longText,
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertSame($longText, $article->description());
        $this->assertSame($longText, $article->text());
    }

    public function testMapToEntityWithSpecialCharacters(): void
    {
        $method = new \ReflectionMethod(MySqlArticleRepository::class, 'mapToEntity');
        $method->setAccessible(true);

        $row = [
            'FlID' => '1',
            'FlNfID' => '2',
            'FlTitle' => "Title with 'quotes' and \"double quotes\"",
            'FlLink' => 'https://example.com?a=1&b=2',
            'FlDescription' => '<script>alert("xss")</script>',
            'FlDate' => '',
            'FlAudio' => '',
            'FlText' => "Line1\nLine2\tTabbed",
        ];

        $article = $method->invoke($this->repository, $row);

        $this->assertSame("Title with 'quotes' and \"double quotes\"", $article->title());
        $this->assertSame('https://example.com?a=1&b=2', $article->link());
        $this->assertStringContainsString('<script>', $article->description());
        $this->assertStringContainsString("\n", $article->text());
    }
}
