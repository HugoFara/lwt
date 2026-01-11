<?php

declare(strict_types=1);

/**
 * Term Status Value Object
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Domain\ValueObject
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value object representing a Term's learning status.
 *
 * Encapsulates the business rules around term status transitions.
 *
 * Status values:
 * - 1-5: Learning stages (1=new, 5=learned)
 * - 98: Ignored words
 * - 99: Well-known words
 *
 * @since 3.0.0
 */
final readonly class TermStatus
{
    /** @var int New/unknown term */
    public const NEW = 1;

    /** @var int Learning stage 2 */
    public const LEARNING_2 = 2;

    /** @var int Learning stage 3 */
    public const LEARNING_3 = 3;

    /** @var int Learning stage 4 */
    public const LEARNING_4 = 4;

    /** @var int Fully learned */
    public const LEARNED = 5;

    /** @var int Ignored term */
    public const IGNORED = 98;

    /** @var int Well-known term (no need to learn) */
    public const WELL_KNOWN = 99;

    /** @var int[] Valid status values */
    private const VALID_STATUSES = [
        self::NEW,
        self::LEARNING_2,
        self::LEARNING_3,
        self::LEARNING_4,
        self::LEARNED,
        self::IGNORED,
        self::WELL_KNOWN,
    ];

    /**
     * @param int $value The status value
     */
    private function __construct(private int $value)
    {
    }

    /**
     * Create from a database value.
     *
     * @param int $status The status value from database
     *
     * @return self
     *
     * @throws InvalidArgumentException If status is invalid
     */
    public static function fromInt(int $status): self
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                'Invalid term status: ' . $status . '. Valid values: ' . implode(', ', self::VALID_STATUSES)
            );
        }
        return new self($status);
    }

    /**
     * Create a new (unknown) status.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self(self::NEW);
    }

    /**
     * Create a learned status.
     *
     * @return self
     */
    public static function learned(): self
    {
        return new self(self::LEARNED);
    }

    /**
     * Create an ignored status.
     *
     * @return self
     */
    public static function ignored(): self
    {
        return new self(self::IGNORED);
    }

    /**
     * Create a well-known status.
     *
     * @return self
     */
    public static function wellKnown(): self
    {
        return new self(self::WELL_KNOWN);
    }

    /**
     * Advance to the next learning stage.
     *
     * Returns a new TermStatus with the next stage, or the same status
     * if already at maximum learning level or special status.
     *
     * @return self
     */
    public function advance(): self
    {
        if ($this->value >= self::LEARNED || $this->isSpecial()) {
            return $this;
        }
        return new self($this->value + 1);
    }

    /**
     * Decrease to the previous learning stage.
     *
     * Returns a new TermStatus with the previous stage, or the same status
     * if already at minimum level or special status.
     *
     * @return self
     */
    public function decrease(): self
    {
        if ($this->value <= self::NEW || $this->isSpecial()) {
            return $this;
        }
        return new self($this->value - 1);
    }

    /**
     * Check if the term is known (learned or well-known).
     *
     * @return bool
     */
    public function isKnown(): bool
    {
        return $this->value === self::LEARNED || $this->value === self::WELL_KNOWN;
    }

    /**
     * Check if the term is in a learning stage (1-4).
     *
     * @return bool
     */
    public function isLearning(): bool
    {
        return $this->value >= self::NEW && $this->value <= self::LEARNING_4;
    }

    /**
     * Check if this is a special status (ignored or well-known).
     *
     * @return bool
     */
    public function isSpecial(): bool
    {
        return $this->value === self::IGNORED || $this->value === self::WELL_KNOWN;
    }

    /**
     * Check if the term is ignored.
     *
     * @return bool
     */
    public function isIgnored(): bool
    {
        return $this->value === self::IGNORED;
    }

    /**
     * Check if the term needs review (learning stages 1-4).
     *
     * @return bool
     */
    public function needsReview(): bool
    {
        return $this->isLearning();
    }

    /**
     * Get the integer value.
     *
     * @return int
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Check equality with another TermStatus.
     *
     * @param TermStatus $other The other status to compare
     *
     * @return bool
     */
    public function equals(TermStatus $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get a human-readable label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this->value) {
            self::NEW => 'New',
            self::LEARNING_2 => 'Learning (2)',
            self::LEARNING_3 => 'Learning (3)',
            self::LEARNING_4 => 'Learning (4)',
            self::LEARNED => 'Learned',
            self::IGNORED => 'Ignored',
            self::WELL_KNOWN => 'Well Known',
            default => 'Unknown',
        };
    }

    /**
     * String representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
