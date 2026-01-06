<?php declare(strict_types=1);

namespace Lwt\Tests\Shared\Domain\ValueObjects;

use InvalidArgumentException;
use Lwt\Shared\Domain\ValueObjects\UserId;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UserId value object.
 */
class UserIdTest extends TestCase
{
    // ===== Factory Method Tests =====

    public function testFromIntCreatesValidId(): void
    {
        $id = UserId::fromInt(42);
        $this->assertInstanceOf(UserId::class, $id);
        $this->assertEquals(42, $id->toInt());
    }

    public function testFromIntAcceptsPositiveIntegers(): void
    {
        $id1 = UserId::fromInt(1);
        $this->assertEquals(1, $id1->toInt());

        $id2 = UserId::fromInt(999999);
        $this->assertEquals(999999, $id2->toInt());
    }

    public function testFromIntRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be positive');
        UserId::fromInt(0);
    }

    public function testFromIntRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be positive');
        UserId::fromInt(-1);
    }

    public function testNewCreatesZeroId(): void
    {
        $id = UserId::new();
        $this->assertEquals(0, $id->toInt());
    }

    // ===== isNew Tests =====

    public function testIsNewReturnsTrueForNewId(): void
    {
        $id = UserId::new();
        $this->assertTrue($id->isNew());
    }

    public function testIsNewReturnsFalseForPersistedId(): void
    {
        $id = UserId::fromInt(1);
        $this->assertFalse($id->isNew());
    }

    // ===== equals Tests =====

    public function testEqualsSameValue(): void
    {
        $id1 = UserId::fromInt(42);
        $id2 = UserId::fromInt(42);
        $this->assertTrue($id1->equals($id2));
    }

    public function testEqualsDifferentValue(): void
    {
        $id1 = UserId::fromInt(42);
        $id2 = UserId::fromInt(43);
        $this->assertFalse($id1->equals($id2));
    }

    public function testEqualsNewIds(): void
    {
        $id1 = UserId::new();
        $id2 = UserId::new();
        $this->assertTrue($id1->equals($id2));
    }

    // ===== __toString Tests =====

    public function testToStringReturnsStringValue(): void
    {
        $id = UserId::fromInt(42);
        $this->assertEquals('42', (string) $id);
    }

    public function testToStringForNewId(): void
    {
        $id = UserId::new();
        $this->assertEquals('0', (string) $id);
    }
}
