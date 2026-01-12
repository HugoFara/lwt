<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\UseCases;

use Lwt\Modules\Tags\Application\UseCases\CreateTag;
use Lwt\Modules\Tags\Domain\Tag;
use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use Lwt\Modules\Tags\Domain\TagType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the CreateTag use case.
 */
class CreateTagTest extends TestCase
{
    /** @var TagRepositoryInterface&MockObject */
    private TagRepositoryInterface $repository;
    private CreateTag $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(TagRepositoryInterface::class);
        $this->useCase = new CreateTag($this->repository);
    }

    // =========================================================================
    // execute() Tests
    // =========================================================================

    public function testExecuteCreatesTagSuccessfully(): void
    {
        $this->repository->expects($this->once())
            ->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('vocabulary')
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Tag::class));

        $tag = $this->useCase->execute('vocabulary', 'Words to learn');

        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertEquals('vocabulary', $tag->text());
        $this->assertEquals('Words to learn', $tag->comment());
        $this->assertTrue($tag->isTermTag());
    }

    public function testExecuteCreatesTextTagSuccessfully(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TEXT);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('save');

        $tag = $this->useCase->execute('news', 'News articles');

        $this->assertTrue($tag->isTextTag());
        $this->assertEquals('news', $tag->text());
    }

    public function testExecuteThrowsExceptionForDuplicateTag(): void
    {
        $this->repository->method('textExists')
            ->with('existing')
            ->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag "existing" already exists');

        $this->useCase->execute('existing');
    }

    public function testExecuteThrowsExceptionForEmptyText(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot be empty');

        $this->useCase->execute('');
    }

    public function testExecuteThrowsExceptionForWhitespaceOnlyText(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        // After normalization, spaces are removed, resulting in empty string
        $this->repository->method('textExists')
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot be empty');

        $this->useCase->execute('   ');
    }

    public function testExecuteThrowsExceptionForTooLongText(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot exceed 20 characters');

        $this->useCase->execute('ThisIsAVeryLongTagNameThatExceedsTwentyCharacters');
    }

    public function testExecuteThrowsExceptionForTooLongComment(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag comment cannot exceed 200 characters');

        $longComment = str_repeat('a', 201);
        $this->useCase->execute('valid', $longComment);
    }

    public function testExecuteNormalizesTextByRemovingSpaces(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        // textExists is called with raw text before normalization
        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('new words')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute('new words');

        // But the resulting tag text is normalized (spaces removed)
        $this->assertEquals('newwords', $tag->text());
    }

    public function testExecuteNormalizesTextByRemovingCommas(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        // textExists is called with raw text before normalization
        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('tag,one')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute('tag,one');

        // But the resulting tag text is normalized (commas removed)
        $this->assertEquals('tagone', $tag->text());
    }

    public function testExecuteTrimsWhitespace(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        // textExists is called with raw text before normalization
        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('  mytag  ')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute('  mytag  ');

        // But the resulting tag text is trimmed
        $this->assertEquals('mytag', $tag->text());
    }

    public function testExecuteTrimsCommentWhitespace(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute('tag', '  My comment  ');

        $this->assertEquals('My comment', $tag->comment());
    }

    public function testExecuteWithEmptyComment(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute('tag');

        $this->assertEquals('', $tag->comment());
    }

    public function testExecuteHandlesUnicodeText(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->expects($this->once())
            ->method('textExists')
            ->with('日本語')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute('日本語', 'Japanese words');

        $this->assertEquals('日本語', $tag->text());
    }

    public function testExecuteHandlesMaxLengthText(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $maxText = str_repeat('a', 20);
        $this->repository->method('textExists')
            ->with($maxText)
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute($maxText);

        $this->assertEquals($maxText, $tag->text());
        $this->assertEquals(20, mb_strlen($tag->text()));
    }

    public function testExecuteHandlesMaxLengthComment(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $maxComment = str_repeat('b', 200);
        $tag = $this->useCase->execute('tag', $maxComment);

        $this->assertEquals($maxComment, $tag->comment());
        $this->assertEquals(200, mb_strlen($tag->comment()));
    }

    // =========================================================================
    // executeWithResult() Tests
    // =========================================================================

    public function testExecuteWithResultReturnsSuccessOnCreate(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $result = $this->useCase->executeWithResult('newtag', 'A comment');

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Tag::class, $result['tag']);
        $this->assertNull($result['error']);
        $this->assertEquals('newtag', $result['tag']->text());
    }

    public function testExecuteWithResultReturnsFailureOnDuplicate(): void
    {
        $this->repository->method('textExists')
            ->willReturn(true);

        $result = $this->useCase->executeWithResult('existing');

        $this->assertFalse($result['success']);
        $this->assertNull($result['tag']);
        $this->assertStringContainsString('already exists', $result['error']);
    }

    public function testExecuteWithResultReturnsFailureOnEmptyText(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $result = $this->useCase->executeWithResult('');

        $this->assertFalse($result['success']);
        $this->assertNull($result['tag']);
        $this->assertStringContainsString('cannot be empty', $result['error']);
    }

    public function testExecuteWithResultReturnsFailureOnDatabaseException(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save')
            ->willThrowException(new \Exception('Database connection lost'));

        $result = $this->useCase->executeWithResult('tag');

        $this->assertFalse($result['success']);
        $this->assertNull($result['tag']);
        $this->assertStringContainsString('Database connection lost', $result['error']);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function testExecuteWithSpecialCharactersInComment(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $specialComment = 'Comment with "quotes" & <tags> and émojis 🎉';
        $tag = $this->useCase->execute('tag', $specialComment);

        $this->assertEquals($specialComment, $tag->comment());
    }

    public function testExecuteCreatesNewTagEntity(): void
    {
        $this->repository->method('getTagType')
            ->willReturn(TagType::TERM);

        $this->repository->method('textExists')
            ->willReturn(false);

        $this->repository->method('save');

        $tag = $this->useCase->execute('newtag');

        $this->assertTrue($tag->isNew());
    }
}
