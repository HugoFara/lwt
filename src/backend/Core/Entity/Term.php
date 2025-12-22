<?php declare(strict_types=1);
/**
 * Term Entity
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Entity
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-classes-term.html
 * @since    2.7.0
 */

namespace Lwt\Core\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Lwt\Core\Entity\ValueObject\LanguageId;
use Lwt\Core\Entity\ValueObject\TermId;
use Lwt\Core\Entity\ValueObject\TermStatus;

/**
 * A term (word or multi-word) represented as a rich domain object.
 *
 * Terms are vocabulary items that users learn. They have a status indicating
 * learning progress (1-5), or special statuses (98=ignored, 99=well-known).
 *
 * This class enforces domain invariants and encapsulates business logic.
 *
 * @category Lwt
 * @package  Lwt\Entity
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class Term
{
    private TermId $id;
    private LanguageId $languageId;
    private string $text;
    private string $textLowercase;
    private TermStatus $status;
    private string $translation;
    private string $sentence;
    private string $notes;
    private string $romanization;
    private int $wordCount;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $statusChangedAt;
    private float $todayScore;
    private float $tomorrowScore;
    private float $random;

    /**
     * Private constructor - use factory methods instead.
     */
    private function __construct(
        TermId $id,
        LanguageId $languageId,
        string $text,
        string $textLowercase,
        TermStatus $status,
        string $translation,
        string $sentence,
        string $notes,
        string $romanization,
        int $wordCount,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $statusChangedAt,
        float $todayScore,
        float $tomorrowScore,
        float $random
    ) {
        $this->id = $id;
        $this->languageId = $languageId;
        $this->text = $text;
        $this->textLowercase = $textLowercase;
        $this->status = $status;
        $this->translation = $translation;
        $this->sentence = $sentence;
        $this->notes = $notes;
        $this->romanization = $romanization;
        $this->wordCount = $wordCount;
        $this->createdAt = $createdAt;
        $this->statusChangedAt = $statusChangedAt;
        $this->todayScore = $todayScore;
        $this->tomorrowScore = $tomorrowScore;
        $this->random = $random;
    }

    /**
     * Create a new term.
     *
     * @param LanguageId $languageId  The language this term belongs to
     * @param string     $text        The term text
     * @param string     $translation Optional initial translation
     *
     * @return self
     *
     * @throws InvalidArgumentException If text is empty
     */
    public static function create(
        LanguageId $languageId,
        string $text,
        string $translation = ''
    ): self {
        $trimmedText = trim($text);
        if ($trimmedText === '') {
            throw new InvalidArgumentException('Term text cannot be empty');
        }

        $wordCount = self::calculateWordCount($trimmedText);

        return new self(
            TermId::new(),
            $languageId,
            $trimmedText,
            mb_strtolower($trimmedText, 'UTF-8'),
            TermStatus::new(),
            $translation,
            '',
            '',
            '',
            $wordCount,
            new DateTimeImmutable(),
            new DateTimeImmutable(),
            0.0,
            0.0,
            (float) mt_rand() / (float) mt_getrandmax()
        );
    }

    /**
     * Reconstitute a term from persistence.
     *
     * This is used by repositories to recreate entities from database records.
     * It bypasses validation since the data is assumed to be valid.
     *
     * @param int               $id              The term ID
     * @param int               $languageId      The language ID
     * @param string            $text            The term text
     * @param string            $textLowercase   The lowercase text
     * @param int               $status          The status value
     * @param string            $translation     The translation
     * @param string            $sentence        The example sentence
     * @param string            $notes           Personal notes
     * @param string            $romanization    The romanization
     * @param int               $wordCount       The word count
     * @param DateTimeImmutable $createdAt       When the term was created
     * @param DateTimeImmutable $statusChangedAt When status last changed
     * @param float             $todayScore      Today's review score
     * @param float             $tomorrowScore   Tomorrow's review score
     * @param float             $random          Random value for review ordering
     *
     * @return self
     *
     * @internal This method is for repository use only
     */
    public static function reconstitute(
        int $id,
        int $languageId,
        string $text,
        string $textLowercase,
        int $status,
        string $translation,
        string $sentence,
        string $notes,
        string $romanization,
        int $wordCount,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $statusChangedAt,
        float $todayScore,
        float $tomorrowScore,
        float $random
    ): self {
        return new self(
            TermId::fromInt($id),
            LanguageId::fromInt($languageId),
            $text,
            $textLowercase,
            TermStatus::fromInt($status),
            $translation,
            $sentence,
            $notes,
            $romanization,
            $wordCount,
            $createdAt,
            $statusChangedAt,
            $todayScore,
            $tomorrowScore,
            $random
        );
    }

    /**
     * Advance the term's status to the next learning stage.
     *
     * @return void
     */
    public function advanceStatus(): void
    {
        $newStatus = $this->status->advance();
        if (!$newStatus->equals($this->status)) {
            $this->status = $newStatus;
            $this->statusChangedAt = new DateTimeImmutable();
        }
    }

    /**
     * Decrease the term's status to the previous learning stage.
     *
     * @return void
     */
    public function decreaseStatus(): void
    {
        $newStatus = $this->status->decrease();
        if (!$newStatus->equals($this->status)) {
            $this->status = $newStatus;
            $this->statusChangedAt = new DateTimeImmutable();
        }
    }

    /**
     * Set the term's status to a specific value.
     *
     * @param TermStatus $status The new status
     *
     * @return void
     */
    public function setStatus(TermStatus $status): void
    {
        if (!$status->equals($this->status)) {
            $this->status = $status;
            $this->statusChangedAt = new DateTimeImmutable();
        }
    }

    /**
     * Mark the term as fully learned.
     *
     * @return void
     */
    public function markAsLearned(): void
    {
        $this->setStatus(TermStatus::learned());
    }

    /**
     * Mark the term as ignored.
     *
     * @return void
     */
    public function ignore(): void
    {
        $this->setStatus(TermStatus::ignored());
    }

    /**
     * Mark the term as well-known.
     *
     * @return void
     */
    public function markAsWellKnown(): void
    {
        $this->setStatus(TermStatus::wellKnown());
    }

    /**
     * Update the translation.
     *
     * @param string $translation The new translation
     *
     * @return void
     */
    public function updateTranslation(string $translation): void
    {
        $this->translation = trim($translation);
    }

    /**
     * Update the example sentence.
     *
     * @param string $sentence The new sentence
     *
     * @return void
     */
    public function updateSentence(string $sentence): void
    {
        $this->sentence = trim($sentence);
    }

    /**
     * Update the romanization.
     *
     * @param string $romanization The new romanization
     *
     * @return void
     */
    public function updateRomanization(string $romanization): void
    {
        $this->romanization = trim($romanization);
    }

    /**
     * Update the notes.
     *
     * @param string $notes The new notes
     *
     * @return void
     */
    public function updateNotes(string $notes): void
    {
        $this->notes = trim($notes);
    }

    /**
     * Update review scores.
     *
     * @param float $todayScore    Today's score
     * @param float $tomorrowScore Tomorrow's score
     *
     * @return void
     */
    public function updateScores(float $todayScore, float $tomorrowScore): void
    {
        $this->todayScore = $todayScore;
        $this->tomorrowScore = $tomorrowScore;
    }

    /**
     * Check if the term is known (learned or well-known).
     *
     * @return bool
     */
    public function isKnown(): bool
    {
        return $this->status->isKnown();
    }

    /**
     * Check if the term is in a learning stage.
     *
     * @return bool
     */
    public function isLearning(): bool
    {
        return $this->status->isLearning();
    }

    /**
     * Check if the term is ignored.
     *
     * @return bool
     */
    public function isIgnored(): bool
    {
        return $this->status->isIgnored();
    }

    /**
     * Check if the term needs review.
     *
     * @return bool
     */
    public function needsReview(): bool
    {
        return $this->status->needsReview();
    }

    /**
     * Check if this is a multi-word term.
     *
     * @return bool
     */
    public function isMultiWord(): bool
    {
        return $this->wordCount > 1;
    }

    /**
     * Check if the term has a translation.
     *
     * @return bool
     */
    public function hasTranslation(): bool
    {
        return $this->translation !== '' && $this->translation !== '*';
    }

    // Getters

    public function id(): TermId
    {
        return $this->id;
    }

    public function languageId(): LanguageId
    {
        return $this->languageId;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function textLowercase(): string
    {
        return $this->textLowercase;
    }

    public function status(): TermStatus
    {
        return $this->status;
    }

    public function translation(): string
    {
        return $this->translation;
    }

    public function sentence(): string
    {
        return $this->sentence;
    }

    public function romanization(): string
    {
        return $this->romanization;
    }

    public function notes(): string
    {
        return $this->notes;
    }

    public function wordCount(): int
    {
        return $this->wordCount;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function statusChangedAt(): DateTimeImmutable
    {
        return $this->statusChangedAt;
    }

    public function todayScore(): float
    {
        return $this->todayScore;
    }

    public function tomorrowScore(): float
    {
        return $this->tomorrowScore;
    }

    public function random(): float
    {
        return $this->random;
    }

    /**
     * Calculate word count for a text.
     *
     * @param string $text The text to count words in
     *
     * @return int
     */
    private static function calculateWordCount(string $text): int
    {
        // Simple word count based on spaces
        // This matches the legacy behavior
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Internal method to set the ID after persistence.
     *
     * @param TermId $id The new ID
     *
     * @return void
     *
     * @internal This method is for repository use only
     */
    public function setId(TermId $id): void
    {
        if (!$this->id->isNew()) {
            throw new \LogicException('Cannot change ID of a persisted term');
        }
        $this->id = $id;
    }
}
