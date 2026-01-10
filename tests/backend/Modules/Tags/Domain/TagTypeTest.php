<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\Domain;

use Lwt\Modules\Tags\Domain\TagType;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TagType enum.
 *
 * Tests all enum cases and their associated methods.
 */
class TagTypeTest extends TestCase
{
    // ===== Enum Cases Tests =====

    public function testTermCaseHasCorrectValue(): void
    {
        $this->assertEquals('term', TagType::TERM->value);
    }

    public function testTextCaseHasCorrectValue(): void
    {
        $this->assertEquals('text', TagType::TEXT->value);
    }

    // ===== tableName Tests =====

    public function testTableNameReturnsTagsForTerm(): void
    {
        $this->assertEquals('tags', TagType::TERM->tableName());
    }

    public function testTableNameReturnsTextTagsForText(): void
    {
        $this->assertEquals('text_tags', TagType::TEXT->tableName());
    }

    // ===== columnPrefix Tests =====

    public function testColumnPrefixReturnsTgForTerm(): void
    {
        $this->assertEquals('Tg', TagType::TERM->columnPrefix());
    }

    public function testColumnPrefixReturnsT2ForText(): void
    {
        $this->assertEquals('T2', TagType::TEXT->columnPrefix());
    }

    // ===== idColumn Tests =====

    public function testIdColumnReturnsTgIdForTerm(): void
    {
        $this->assertEquals('TgID', TagType::TERM->idColumn());
    }

    public function testIdColumnReturnsT2IdForText(): void
    {
        $this->assertEquals('T2ID', TagType::TEXT->idColumn());
    }

    // ===== textColumn Tests =====

    public function testTextColumnReturnsTgTextForTerm(): void
    {
        $this->assertEquals('TgText', TagType::TERM->textColumn());
    }

    public function testTextColumnReturnsT2TextForText(): void
    {
        $this->assertEquals('T2Text', TagType::TEXT->textColumn());
    }

    // ===== commentColumn Tests =====

    public function testCommentColumnReturnsTgCommentForTerm(): void
    {
        $this->assertEquals('TgComment', TagType::TERM->commentColumn());
    }

    public function testCommentColumnReturnsT2CommentForText(): void
    {
        $this->assertEquals('T2Comment', TagType::TEXT->commentColumn());
    }

    // ===== userIdColumn Tests =====

    public function testUserIdColumnReturnsTgUsIdForTerm(): void
    {
        $this->assertEquals('TgUsID', TagType::TERM->userIdColumn());
    }

    public function testUserIdColumnReturnsT2UsIdForText(): void
    {
        $this->assertEquals('T2UsID', TagType::TEXT->userIdColumn());
    }

    // ===== associationTable Tests =====

    public function testAssociationTableReturnsWordtagsForTerm(): void
    {
        $this->assertEquals('word_tag_map', TagType::TERM->associationTable());
    }

    public function testAssociationTableReturnsTexttagsForText(): void
    {
        $this->assertEquals('text_tag_map', TagType::TEXT->associationTable());
    }

    // ===== label Tests =====

    public function testLabelReturnsTermForTerm(): void
    {
        $this->assertEquals('Term', TagType::TERM->label());
    }

    public function testLabelReturnsTextForText(): void
    {
        $this->assertEquals('Text', TagType::TEXT->label());
    }

    // ===== baseUrl Tests =====

    public function testBaseUrlReturnsTagsForTerm(): void
    {
        $this->assertEquals('/tags', TagType::TERM->baseUrl());
    }

    public function testBaseUrlReturnsTagsTextForText(): void
    {
        $this->assertEquals('/tags/text', TagType::TEXT->baseUrl());
    }

    // ===== itemsUrlPattern Tests =====

    public function testItemsUrlPatternReturnsWordsUrlForTerm(): void
    {
        $this->assertEquals('/words?tag=%d', TagType::TERM->itemsUrlPattern());
    }

    public function testItemsUrlPatternReturnsTextsUrlForText(): void
    {
        $this->assertEquals('/texts?tag=%d', TagType::TEXT->itemsUrlPattern());
    }

    // ===== isTerm Tests =====

    public function testIsTermReturnsTrueForTerm(): void
    {
        $this->assertTrue(TagType::TERM->isTerm());
    }

    public function testIsTermReturnsFalseForText(): void
    {
        $this->assertFalse(TagType::TEXT->isTerm());
    }

    // ===== isText Tests =====

    public function testIsTextReturnsTrueForText(): void
    {
        $this->assertTrue(TagType::TEXT->isText());
    }

    public function testIsTextReturnsFalseForTerm(): void
    {
        $this->assertFalse(TagType::TERM->isText());
    }

    // ===== Column Methods Consistency Tests =====

    public function testAllColumnMethodsUseColumnPrefix(): void
    {
        // Verify all column methods derive from columnPrefix
        foreach (TagType::cases() as $type) {
            $prefix = $type->columnPrefix();

            $this->assertStringStartsWith($prefix, $type->idColumn());
            $this->assertStringStartsWith($prefix, $type->textColumn());
            $this->assertStringStartsWith($prefix, $type->commentColumn());
            $this->assertStringStartsWith($prefix, $type->userIdColumn());
        }
    }

    // ===== Enum from Value Tests =====

    public function testCanCreateFromTermString(): void
    {
        $type = TagType::from('term');
        $this->assertSame(TagType::TERM, $type);
    }

    public function testCanCreateFromTextString(): void
    {
        $type = TagType::from('text');
        $this->assertSame(TagType::TEXT, $type);
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $type = TagType::tryFrom('invalid');
        $this->assertNull($type);
    }

    // ===== Cases Enumeration Test =====

    public function testCasesReturnsAllTypes(): void
    {
        $cases = TagType::cases();
        $this->assertCount(2, $cases);
        $this->assertContains(TagType::TERM, $cases);
        $this->assertContains(TagType::TEXT, $cases);
    }
}
