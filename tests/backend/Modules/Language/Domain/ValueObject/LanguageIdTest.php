<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Language\Domain\ValueObject;

use InvalidArgumentException;
use Lwt\Modules\Language\Domain\ValueObject\LanguageId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LanguageId value object.
 */
class LanguageIdTest extends TestCase
{
    // ===== Factory Method Tests =====

    public function testFromIntCreatesValidId(): void
    {
        $id = LanguageId::fromInt(42);
        $this->assertInstanceOf(LanguageId::class, $id);
        $this->assertEquals(42, $id->toInt());
    }

    public function testFromIntAcceptsPositiveIntegers(): void
    {
        $id1 = LanguageId::fromInt(1);
        $this->assertEquals(1, $id1->toInt());

        $id2 = LanguageId::fromInt(999999);
        $this->assertEquals(999999, $id2->toInt());
    }

    public function testFromIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Language ID must be positive');
        LanguageId::fromInt(0);
    }

    public function testFromIntRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Language ID must be positive');
        LanguageId::fromInt(-1);
    }

    public function testNewCreatesZeroId(): void
    {
        $id = LanguageId::new();
        $this->assertEquals(0, $id->toInt());
    }

    // ===== isNew Tests =====

    public function testIsNewReturnsTrueForNewId(): void
    {
        $id = LanguageId::new();
        $this->assertTrue($id->isNew());
    }

    public function testIsNewReturnsFalseForPersistedId(): void
    {
        $id = LanguageId::fromInt(1);
        $this->assertFalse($id->isNew());
    }

    // ===== equals Tests =====

    public function testEqualsSameValue(): void
    {
        $id1 = LanguageId::fromInt(42);
        $id2 = LanguageId::fromInt(42);
        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsDifferentValue(): void
    {
        $id1 = LanguageId::fromInt(42);
        $id2 = LanguageId::fromInt(43);
        $this->assertFalse($id1->equals($id2));
    }

    public function testEqualsNewIds(): void
    {
        $id1 = LanguageId::new();
        $id2 = LanguageId::new();
        $this->assertTrue($id1->equals($id2));
    }

    // ===== __toString Tests =====

    public function testToStringReturnsStringValue(): void
    {
        $id = LanguageId::fromInt(42);
        $this->assertEquals('42', (string) $id);
    }

    public function testToStringForNewId(): void
    {
        $id = LanguageId::new();
        $this->assertEquals('0', (string) $id);
    }
}
