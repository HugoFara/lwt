<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Text\Domain\ValueObject;

use InvalidArgumentException;
use Lwt\Modules\Text\Domain\ValueObject\TextId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TextId value object.
 */
class TextIdTest extends TestCase
{
    // ===== Factory Method Tests =====

    public function testFromIntCreatesValidId(): void
    {
        $id = TextId::fromInt(42);
        $this->assertInstanceOf(TextId::class, $id);
        $this->assertEquals(42, $id->toInt());
    }

    public function testFromIntAcceptsPositiveIntegers(): void
    {
        $id1 = TextId::fromInt(1);
        $this->assertEquals(1, $id1->toInt());

        $id2 = TextId::fromInt(999999);
        $this->assertEquals(999999, $id2->toInt());
    }

    public function testFromIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text ID must be positive');
        TextId::fromInt(0);
    }

    public function testFromIntRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Text ID must be positive');
        TextId::fromInt(-1);
    }

    public function testNewCreatesZeroId(): void
    {
        $id = TextId::new();
        $this->assertEquals(0, $id->toInt());
    }

    // ===== isNew Tests =====

    public function testIsNewReturnsTrueForNewId(): void
    {
        $id = TextId::new();
        $this->assertTrue($id->isNew());
    }

    public function testIsNewReturnsFalseForPersistedId(): void
    {
        $id = TextId::fromInt(1);
        $this->assertFalse($id->isNew());
    }

    // ===== equals Tests =====

    public function testEqualsSameValue(): void
    {
        $id1 = TextId::fromInt(42);
        $id2 = TextId::fromInt(42);
        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsDifferentValue(): void
    {
        $id1 = TextId::fromInt(42);
        $id2 = TextId::fromInt(43);
        $this->assertFalse($id1->equals($id2));
    }

    public function testEqualsNewIds(): void
    {
        $id1 = TextId::new();
        $id2 = TextId::new();
        $this->assertTrue($id1->equals($id2));
    }

    // ===== __toString Tests =====

    public function testToStringReturnsStringValue(): void
    {
        $id = TextId::fromInt(42);
        $this->assertEquals('42', (string) $id);
    }

    public function testToStringForNewId(): void
    {
        $id = TextId::new();
        $this->assertEquals('0', (string) $id);
    }
}
