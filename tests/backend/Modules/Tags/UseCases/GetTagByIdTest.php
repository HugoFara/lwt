<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\UseCases;

use Lwt\Modules\Tags\Application\UseCases\GetTagById;
use Lwt\Modules\Tags\Domain\Tag;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use Lwt\Modules\Tags\Domain\TagType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the GetTagById use case.
 */
class GetTagByIdTest extends TestCase
{
    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $repository;
    private GetTagById $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TagRepositoryInterface::class);
        $this->useCase = new GetTagById($this->repository);
    }

    private function createTag(int $id, string $text, string $comment = ''): Tag
    {
        return Tag::reconstitute($id, TagType::TERM, $text, $comment);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteReturnsTagWhenFound(): void
    {
        $expectedTag = $this->createTag(1, 'vocabulary', 'Learning words');

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($expectedTag);

        $tag = $this->useCase->execute(1);

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals(1, $tag->id()->toInt());
        $this->assertEquals('vocabulary', $tag->text());
        $this->assertEquals('Learning words', $tag->comment());
    }

    public function testExecuteReturnsNullWhenNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $tag = $this->useCase->execute(999);

        $this->assertNull($tag);
    }

    public function testExecuteReturnsTextTag(): void
    {
        $textTag = Tag::reconstitute(1, TagType::TEXT, 'articles', 'News articles');

        $this->repository->method('find')
            ->willReturn($textTag);

        $tag = $this->useCase->execute(1);

        $this->assertTrue($tag->isTextTag());
    }

    public function testExecuteReturnsTermTag(): void
    {
        $termTag = Tag::reconstitute(1, TagType::TERM, 'verbs', 'Action words');

        $this->repository->method('find')
            ->willReturn($termTag);

        $tag = $this->useCase->execute(1);

        $this->assertTrue($tag->isTermTag());
    }

    // =========================================================================
    // exists() Tests
    // =========================================================================

    public function testExistsReturnsTrueWhenTagExists(): void
    {
        $this->repository->expects($this->once())
            ->method('exists')
            ->with(1)
            ->willReturn(true);

        $exists = $this->useCase->exists(1);

        $this->assertTrue($exists);
    }

    public function testExistsReturnsFalseWhenTagNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('exists')
            ->with(999)
            ->willReturn(false);

        $exists = $this->useCase->exists(999);

        $this->assertFalse($exists);
    }

    public function testExistsWithZeroId(): void
    {
        $this->repository->method('exists')
            ->with(0)
            ->willReturn(false);

        $exists = $this->useCase->exists(0);

        $this->assertFalse($exists);
    }

    // =========================================================================
    // executeAsArray() Tests
    // =========================================================================

    public function testExecuteAsArrayReturnsArrayWhenFound(): void
    {
        $tag = $this->createTag(42, 'grammar', 'Grammar rules');

        $this->repository->method('find')
            ->with(42)
            ->willReturn($tag);

        $result = $this->useCase->executeAsArray(42);

        $this->assertIsArray($result);
        $this->assertEquals(42, $result['id']);
        $this->assertEquals('grammar', $result['text']);
        $this->assertEquals('Grammar rules', $result['comment']);
    }

    public function testExecuteAsArrayReturnsNullWhenNotFound(): void
    {
        $this->repository->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->useCase->executeAsArray(999);

        $this->assertNull($result);
    }

    public function testExecuteAsArrayContainsCorrectKeys(): void
    {
        $tag = $this->createTag(1, 'test', 'comment');

        $this->repository->method('find')
            ->willReturn($tag);

        $result = $this->useCase->executeAsArray(1);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('text', $result);
        $this->assertArrayHasKey('comment', $result);
    }

    public function testExecuteAsArrayWithEmptyComment(): void
    {
        $tag = $this->createTag(1, 'notag', '');

        $this->repository->method('find')
            ->willReturn($tag);

        $result = $this->useCase->executeAsArray(1);

        $this->assertEquals('', $result['comment']);
    }

    public function testExecuteAsArrayWithUnicodeText(): void
    {
        $tag = $this->createTag(1, '日本語', 'Japanese tag');

        $this->repository->method('find')
            ->willReturn($tag);

        $result = $this->useCase->executeAsArray(1);

        $this->assertEquals('日本語', $result['text']);
        $this->assertEquals('Japanese tag', $result['comment']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteWithLargeId(): void
    {
        $largeId = PHP_INT_MAX;

        $this->repository->expects($this->once())
            ->method('find')
            ->with($largeId)
            ->willReturn(null);

        $tag = $this->useCase->execute($largeId);

        $this->assertNull($tag);
    }

    public function testExecuteCallsRepositoryOnce(): void
    {
        $tag = $this->createTag(1, 'test');

        $this->repository->expects($this->once())
            ->method('find')
            ->willReturn($tag);

        $this->useCase->execute(1);
    }

    public function testExecuteAsArrayWithSpecialCharactersInComment(): void
    {
        $tag = $this->createTag(1, 'special', 'Comment with <html> & "quotes"');

        $this->repository->method('find')
            ->willReturn($tag);

        $result = $this->useCase->executeAsArray(1);

        $this->assertEquals('Comment with <html> & "quotes"', $result['comment']);
    }
}
