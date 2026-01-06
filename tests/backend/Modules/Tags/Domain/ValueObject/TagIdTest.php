<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Tags\Domain\ValueObject;

use InvalidArgumentException;
use Lwt\Modules\Tags\Domain\ValueObject\TagId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TagId value object.
 */
class TagIdTest extends TestCase
{
    // ===== Factory Method Tests =====

    public function testFromIntCreatesValidId(): void
    {
        $id = TagId::fromInt(42);
        $this->assertInstanceOf(TagId::class, $id);
        $this->assertEquals(42, $id->toInt());
    }

    public function testFromIntAcceptsPositiveIntegers(): void
    {
        $id1 = TagId::fromInt(1);
        $this->assertEquals(1, $id1->toInt());

        $id2 = TagId::fromInt(999999);
        $this->assertEquals(999999, $id2->toInt());
    }

    public function testFromIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag ID must be positive');
        TagId::fromInt(0);
    }

    public function testFromIntRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tag ID must be positive');
        TagId::fromInt(-1);
    }

    public function testNewCreatesZeroId(): void
    {
        $id = TagId::new();
        $this->assertEquals(0, $id->toInt());
    }

    // ===== isNew Tests =====

    public function testIsNewReturnsTrueForNewId(): void
    {
        $id = TagId::new();
        $this->assertTrue($id->isNew());
    }

    public function testIsNewReturnsFalseForPersistedId(): void
    {
        $id = TagId::fromInt(1);
        $this->assertFalse($id->isNew());
    }

    // ===== equals Tests =====

    public function testEqualsSameValue(): void
    {
        $id1 = TagId::fromInt(42);
        $id2 = TagId::fromInt(42);
        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsDifferentValue(): void
    {
        $id1 = TagId::fromInt(42);
        $id2 = TagId::fromInt(43);
        $this->assertFalse($id1->equals($id2));
    }

    public function testEqualsNewIds(): void
    {
        $id1 = TagId::new();
        $id2 = TagId::new();
        $this->assertTrue($id1->equals($id2));
    }

    // ===== __toString Tests =====

    public function testToStringReturnsStringValue(): void
    {
        $id = TagId::fromInt(42);
        $this->assertEquals('42', (string) $id);
    }

    public function testToStringForNewId(): void
    {
        $id = TagId::new();
        $this->assertEquals('0', (string) $id);
    }
}
