<?php declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Domain\ValueObject;

use InvalidArgumentException;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TermId value object.
 */
class TermIdTest extends TestCase
{
    // ===== Factory Method Tests =====

    public function testFromIntCreatesValidId(): void
    {
        $id = TermId::fromInt(42);
        $this->assertInstanceOf(TermId::class, $id);
        $this->assertEquals(42, $id->toInt());
    }

    public function testFromIntAcceptsPositiveIntegers(): void
    {
        $id1 = TermId::fromInt(1);
        $this->assertEquals(1, $id1->toInt());

        $id2 = TermId::fromInt(999999);
        $this->assertEquals(999999, $id2->toInt());
    }

    public function testFromIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Term ID must be positive');
        TermId::fromInt(0);
    }

    public function testFromIntRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Term ID must be positive');
        TermId::fromInt(-1);
    }

    public function testNewCreatesZeroId(): void
    {
        $id = TermId::new();
        $this->assertEquals(0, $id->toInt());
    }

    // ===== isNew Tests =====

    public function testIsNewReturnsTrueForNewId(): void
    {
        $id = TermId::new();
        $this->assertTrue($id->isNew());
    }

    public function testIsNewReturnsFalseForPersistedId(): void
    {
        $id = TermId::fromInt(1);
        $this->assertFalse($id->isNew());
    }

    // ===== equals Tests =====

    public function testEqualsSameValue(): void
    {
        $id1 = TermId::fromInt(42);
        $id2 = TermId::fromInt(42);
        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsDifferentValue(): void
    {
        $id1 = TermId::fromInt(42);
        $id2 = TermId::fromInt(43);
        $this->assertFalse($id1->equals($id2));
    }

    public function testEqualsNewIds(): void
    {
        $id1 = TermId::new();
        $id2 = TermId::new();
        $this->assertTrue($id1->equals($id2));
    }

    // ===== __toString Tests =====

    public function testToStringReturnsStringValue(): void
    {
        $id = TermId::fromInt(42);
        $this->assertEquals('42', (string) $id);
    }

    public function testToStringForNewId(): void
    {
        $id = TermId::new();
        $this->assertEquals('0', (string) $id);
    }

    // ===== Immutability Tests =====

    public function testImmutability(): void
    {
        $id = TermId::fromInt(42);
        $this->assertEquals(42, $id->toInt());
        // Value objects are immutable - no setters exist
    }
}
