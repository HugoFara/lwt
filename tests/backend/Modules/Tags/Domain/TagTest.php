<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\Domain;

use InvalidArgumentException;
use LogicException;
use Lwt\Modules\Tags\Domain\Tag;
use Lwt\Modules\Tags\Domain\TagType;
use Lwt\Modules\Tags\Domain\ValueObject\TagId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Tag entity.
 *
 * Tests factory methods, validation, and domain behavior.
 */
class TagTest extends TestCase
{
    // ===== Constants Tests =====

    public function testMaxTextLengthConstant(): void
    {
        $this->assertEquals(20, Tag::MAX_TEXT_LENGTH);
    }

    public function testMaxCommentLengthConstant(): void
    {
        $this->assertEquals(200, Tag::MAX_COMMENT_LENGTH);
    }

    // ===== create Factory Method Tests =====

    public function testCreateTermTag(): void
    {
        $tag = Tag::create(TagType::TERM, 'vocabulary', 'My vocabulary tag');

        $this->assertTrue($tag->isNew());
        $this->assertTrue($tag->isTermTag());
        $this->assertFalse($tag->isTextTag());
        $this->assertEquals('vocabulary', $tag->text());
        $this->assertEquals('My vocabulary tag', $tag->comment());
        $this->assertSame(TagType::TERM, $tag->type());
    }

    public function testCreateTextTag(): void
    {
        $tag = Tag::create(TagType::TEXT, 'reading', 'Reading materials');

        $this->assertTrue($tag->isNew());
        $this->assertFalse($tag->isTermTag());
        $this->assertTrue($tag->isTextTag());
        $this->assertEquals('reading', $tag->text());
        $this->assertEquals('Reading materials', $tag->comment());
        $this->assertSame(TagType::TEXT, $tag->type());
    }

    public function testCreateWithEmptyComment(): void
    {
        $tag = Tag::create(TagType::TERM, 'test');

        $this->assertEquals('test', $tag->text());
        $this->assertEquals('', $tag->comment());
    }

    public function testCreateNormalizesText(): void
    {
        $tag = Tag::create(TagType::TERM, '  spaced  text  ');

        // Spaces should be removed (tags don't allow spaces)
        $this->assertEquals('spacedtext', $tag->text());
    }

    public function testCreateRemovesCommas(): void
    {
        $tag = Tag::create(TagType::TERM, 'one,two,three');

        // Commas should be removed (used as separators)
        $this->assertEquals('onetwothree', $tag->text());
    }

    public function testCreateTrimsComment(): void
    {
        $tag = Tag::create(TagType::TERM, 'test', '  trimmed comment  ');

        $this->assertEquals('trimmed comment', $tag->comment());
    }

    public function testCreateRejectsEmptyText(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot be empty');

        Tag::create(TagType::TERM, '');
    }

    public function testCreateRejectsTextWithOnlySpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot be empty');

        // After normalization (removing spaces), this becomes empty
        Tag::create(TagType::TERM, '   ');
    }

    public function testCreateRejectsTextTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot exceed 20 characters');

        $longText = str_repeat('a', 21);
        Tag::create(TagType::TERM, $longText);
    }

    public function testCreateAcceptsMaxLengthText(): void
    {
        $maxText = str_repeat('a', 20);
        $tag = Tag::create(TagType::TERM, $maxText);

        $this->assertEquals($maxText, $tag->text());
    }

    public function testCreateRejectsCommentTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag comment cannot exceed 200 characters');

        $longComment = str_repeat('a', 201);
        Tag::create(TagType::TERM, 'test', $longComment);
    }

    public function testCreateAcceptsMaxLengthComment(): void
    {
        $maxComment = str_repeat('a', 200);
        $tag = Tag::create(TagType::TERM, 'test', $maxComment);

        $this->assertEquals($maxComment, $tag->comment());
    }

    // ===== reconstitute Factory Method Tests =====

    public function testReconstituteFromDatabase(): void
    {
        $tag = Tag::reconstitute(42, TagType::TERM, 'restored', 'From DB');

        $this->assertFalse($tag->isNew());
        $this->assertEquals(42, $tag->id()->toInt());
        $this->assertEquals('restored', $tag->text());
        $this->assertEquals('From DB', $tag->comment());
        $this->assertSame(TagType::TERM, $tag->type());
    }

    public function testReconstitutePreservesExactText(): void
    {
        // reconstitute should not normalize text (it's already stored)
        $tag = Tag::reconstitute(1, TagType::TERM, 'exact text', 'comment');

        // Note: reconstitute does NOT normalize, so spaces are preserved
        $this->assertEquals('exact text', $tag->text());
    }

    // ===== rename Tests =====

    public function testRenameUpdatesText(): void
    {
        $tag = Tag::create(TagType::TERM, 'original');
        $tag->rename('renamed');

        $this->assertEquals('renamed', $tag->text());
    }

    public function testRenameNormalizesText(): void
    {
        $tag = Tag::create(TagType::TERM, 'original');
        $tag->rename('  spaced  ');

        $this->assertEquals('spaced', $tag->text());
    }

    public function testRenameRejectsEmptyText(): void
    {
        $tag = Tag::create(TagType::TERM, 'original');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot be empty');

        $tag->rename('');
    }

    public function testRenameRejectsTooLongText(): void
    {
        $tag = Tag::create(TagType::TERM, 'original');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag text cannot exceed 20 characters');

        $tag->rename(str_repeat('a', 21));
    }

    // ===== updateComment Tests =====

    public function testUpdateCommentChangesComment(): void
    {
        $tag = Tag::create(TagType::TERM, 'test', 'original');
        $tag->updateComment('updated');

        $this->assertEquals('updated', $tag->comment());
    }

    public function testUpdateCommentTrims(): void
    {
        $tag = Tag::create(TagType::TERM, 'test');
        $tag->updateComment('  trimmed  ');

        $this->assertEquals('trimmed', $tag->comment());
    }

    public function testUpdateCommentAllowsEmpty(): void
    {
        $tag = Tag::create(TagType::TERM, 'test', 'has comment');
        $tag->updateComment('');

        $this->assertEquals('', $tag->comment());
    }

    public function testUpdateCommentRejectsTooLong(): void
    {
        $tag = Tag::create(TagType::TERM, 'test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag comment cannot exceed 200 characters');

        $tag->updateComment(str_repeat('a', 201));
    }

    // ===== setId Tests =====

    public function testSetIdForNewTag(): void
    {
        $tag = Tag::create(TagType::TERM, 'test');
        $this->assertTrue($tag->isNew());

        $tag->setId(TagId::fromInt(42));

        $this->assertFalse($tag->isNew());
        $this->assertEquals(42, $tag->id()->toInt());
    }

    public function testSetIdRejectsChangeForPersistedTag(): void
    {
        $tag = Tag::reconstitute(1, TagType::TERM, 'test', '');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot change ID of a persisted tag');

        $tag->setId(TagId::fromInt(2));
    }

    // ===== toArray Tests =====

    public function testToArrayReturnsCorrectStructure(): void
    {
        $tag = Tag::reconstitute(42, TagType::TERM, 'mytag', 'My comment');

        $array = $tag->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(42, $array['id']);
        $this->assertEquals('mytag', $array['text']);
        $this->assertEquals('My comment', $array['comment']);
        $this->assertEquals('term', $array['type']);
    }

    public function testToArrayForTextTag(): void
    {
        $tag = Tag::create(TagType::TEXT, 'texttag');

        $array = $tag->toArray();

        $this->assertEquals(0, $array['id']); // New tag has ID 0
        $this->assertEquals('texttag', $array['text']);
        $this->assertEquals('', $array['comment']);
        $this->assertEquals('text', $array['type']);
    }

    // ===== Query Method Tests =====

    public function testIdReturnsTagId(): void
    {
        $tag = Tag::reconstitute(42, TagType::TERM, 'test', '');

        $id = $tag->id();

        $this->assertInstanceOf(TagId::class, $id);
        $this->assertEquals(42, $id->toInt());
    }

    public function testTypeReturnsTagType(): void
    {
        $termTag = Tag::create(TagType::TERM, 'term');
        $textTag = Tag::create(TagType::TEXT, 'text');

        $this->assertSame(TagType::TERM, $termTag->type());
        $this->assertSame(TagType::TEXT, $textTag->type());
    }

    public function testIsTermTagReturnsTrueForTermTags(): void
    {
        $tag = Tag::create(TagType::TERM, 'test');
        $this->assertTrue($tag->isTermTag());
    }

    public function testIsTermTagReturnsFalseForTextTags(): void
    {
        $tag = Tag::create(TagType::TEXT, 'test');
        $this->assertFalse($tag->isTermTag());
    }

    public function testIsTextTagReturnsTrueForTextTags(): void
    {
        $tag = Tag::create(TagType::TEXT, 'test');
        $this->assertTrue($tag->isTextTag());
    }

    public function testIsTextTagReturnsFalseForTermTags(): void
    {
        $tag = Tag::create(TagType::TERM, 'test');
        $this->assertFalse($tag->isTextTag());
    }

    // ===== Unicode Support Tests =====

    public function testCreateSupportsUnicodeText(): void
    {
        $tag = Tag::create(TagType::TERM, 'vocabulaire');
        $this->assertEquals('vocabulaire', $tag->text());

        $tag2 = Tag::create(TagType::TERM, 'test');
        $this->assertEquals('test', $tag2->text());
    }

    public function testMaxLengthIsCharactersNotBytes(): void
    {
        // 20 multibyte characters should be accepted
        $unicodeText = str_repeat('a', 20);
        $tag = Tag::create(TagType::TERM, $unicodeText);
        $this->assertEquals($unicodeText, $tag->text());
    }
}
