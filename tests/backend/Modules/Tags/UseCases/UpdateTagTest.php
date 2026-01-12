<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\UseCases;

use Lwt\Modules\Tags\Application\UseCases\UpdateTag;
use Lwt\Modules\Tags\Domain\Tag;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use Lwt\Modules\Tags\Domain\TagType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the UpdateTag use case.
 */
class UpdateTagTest extends TestCase
{
    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $repository;
    private UpdateTag $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TagRepositoryInterface::class);
        $this->useCase = new UpdateTag($this->repository);
    }

    private function createTag(int $id, string $text, string $comment = ''): Tag
    {
        return Tag::reconstitute($id, TagType::TERM, $text, $comment);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteUpdatesTagSuccessfully(): void
    {
        $existingTag = $this->createTag(1, 'oldname', 'Old comment');

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($existingTag);

        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('newname', 1)
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($existingTag);

        $tag = $this->useCase->execute(1, 'newname', 'New comment');

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals('newname', $tag->text());
        $this->assertEquals('New comment', $tag->comment());
    }

    public function testExecuteThrowsExceptionForNonExistentTag(): void
    {
        $this->repository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag not found: 999');

        $this->useCase->execute(999, 'newname', 'comment');
    }

    public function testExecuteThrowsExceptionForDuplicateText(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->with(1)
            ->willReturn($existingTag);

        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('duplicate', 1)
            ->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag "duplicate" already exists');

        $this->useCase->execute(1, 'duplicate', 'comment');
    }

    public function testExecuteThrowsExceptionForEmptyText(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->with(1)
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot be empty');

        $this->useCase->execute(1, '', 'comment');
    }

    public function testExecuteThrowsExceptionForTooLongText(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->with(1)
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot exceed 20 characters');

        $this->useCase->execute(1, str_repeat('x', 21), 'comment');
    }

    public function testExecuteThrowsExceptionForTooLongComment(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->with(1)
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag comment cannot exceed 200 characters');

        $this->useCase->execute(1, 'valid', str_repeat('c', 201));
    }

    public function testExecuteAllowsSameTextWhenUpdatingSameTag(): void
    {
        $existingTag = $this->createTag(1, 'samename', 'Old comment');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('samename', 1)
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute(1, 'samename', 'New comment');

        $this->assertEquals('samename', $tag->text());
        $this->assertEquals('New comment', $tag->comment());
    }

    public function testExecuteNormalizesText(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->willReturn($existingTag);

        // textExists is called with raw text before normalization
        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('  new name  ', 1)
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute(1, '  new name  ', 'comment');

        // But the resulting tag text is normalized
        $this->assertEquals('newname', $tag->text());
    }

    public function testExecuteTrimsComment(): void
    {
        $existingTag = $this->createTag(1, 'tag');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute(1, 'tag', '  trimmed comment  ');

        $this->assertEquals('trimmed comment', $tag->comment());
    }

    // =========================================================================
    // executeWithResult() Tests
    // =========================================================================

    public function testExecuteWithResultReturnsSuccessOnUpdate(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $result = $this->useCase->executeWithResult(1, 'newname', 'New comment');

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Tag::class, $result['tag']);
        $this->assertNull($result['error']);
        $this->assertEquals('newname', $result['tag']->text());
    }

    public function testExecuteWithResultReturnsFailureOnNotFound(): void
    {
        $this->repository->method('find')
            ->willReturn(null);

        $result = $this->useCase->executeWithResult(999, 'text', 'comment');

        $this->assertFalse($result['success']);
        $this->assertNull($result['tag']);
        $this->assertStringContainsString('Tag not found', $result['error']);
    }

    public function testExecuteWithResultReturnsFailureOnDuplicate(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(true);

        $result = $this->useCase->executeWithResult(1, 'duplicate', 'comment');

        $this->assertFalse($result['success']);
        $this->assertNull($result['tag']);
        $this->assertStringContainsString('already exists', $result['error']);
    }

    public function testExecuteWithResultReturnsFailureOnDatabaseException(): void
    {
        $existingTag = $this->createTag(1, 'oldname');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->useCase->executeWithResult(1, 'newname', 'comment');

        $this->assertFalse($result['success']);
        $this->assertNull($result['tag']);
        $this->assertStringContainsString('Database error', $result['error']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteHandlesUnicodeText(): void
    {
        $existingTag = $this->createTag(1, 'english');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute(1, '日本語', 'Japanese tag');

        $this->assertEquals('日本語', $tag->text());
    }

    public function testExecuteHandlesEmptyComment(): void
    {
        $existingTag = $this->createTag(1, 'tag', 'Old comment');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute(1, 'tag', '');

        $this->assertEquals('', $tag->comment());
    }

    public function testExecutePreservesTagId(): void
    {
        $existingTag = $this->createTag(42, 'oldname');

        $this->repository->method('find')
            ->willReturn($existingTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute(42, 'newname', 'comment');

        $this->assertEquals(42, $tag->id()->toInt());
    }

    public function testExecutePreservesTagType(): void
    {
        $textTag = Tag::reconstitute(1, TagType::TEXT, 'oldname', '');

        $this->repository->method('find')
            ->willReturn($textTag);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute(1, 'newname', 'comment');

        $this->assertTrue($tag->isTextTag());
    }
}
