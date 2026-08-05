<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

use InvalidArgumentException;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ForeignNote;

/**
 * What the user chose on the import-configuration screen.
 *
 * LWT cannot infer any of this: field names in a shared deck are arbitrary,
 * and an .apkg carries no language.
 */
final class DeckImportSettings
{
    /**
     * Anki interval (days) at which a card is treated as genuinely known.
     *
     * Anki's own "mature card" threshold is 21 days, and its statistics screen
     * uses the same line, so users already read it that way.
     */
    public const MATURE_INTERVAL_DAYS = 21;

    /**
     * @param int         $notetypeId       Which notetype to import
     * @param string      $termField        Field holding the term
     * @param string|null $translationField Field holding the translation, if any
     * @param int         $languageId       Target LWT language
     * @param bool        $deriveStatus     Map Anki maturity onto LWT status
     * @param int         $fixedStatus      Status for every term when $deriveStatus is false
     * @param bool        $importTags       Carry Anki tags across
     */
    public function __construct(
        public readonly int $notetypeId,
        public readonly string $termField,
        public readonly ?string $translationField,
        public readonly int $languageId,
        public readonly bool $deriveStatus = true,
        public readonly int $fixedStatus = 1,
        public readonly bool $importTags = true,
    ) {
        if ($termField === '') {
            throw new InvalidArgumentException('A term field must be chosen');
        }
        if ($languageId <= 0) {
            throw new InvalidArgumentException('A target language must be chosen');
        }
        if (!TermStatus::isValid($fixedStatus)) {
            throw new InvalidArgumentException("Invalid status: {$fixedStatus}");
        }
    }

    /**
     * The LWT status a note should land on.
     *
     * The whole value of importing a deck rather than reclassifying by hand is
     * that Anki already knows how well each word is learned — that lives in
     * `cards.ivl`. The mapping is deliberately coarse; the user can refine
     * individual terms afterwards.
     *
     *   suspended         -> 98 (ignored)      — the user parked it in Anki
     *   never studied     -> 1  (learning)     — no evidence of knowledge
     *   < 21 days         -> 2..4              — young, scaled by interval
     *   >= 21 days        -> 99 (well-known)   — Anki's own "mature" threshold
     */
    public function statusFor(ForeignNote $note): int
    {
        if (!$this->deriveStatus) {
            return $this->fixedStatus;
        }

        if ($note->suspended) {
            return 98;
        }

        if ($note->isNew || $note->interval <= 0) {
            return 1;
        }

        if ($note->interval >= self::MATURE_INTERVAL_DAYS) {
            return 99;
        }

        // 1..20 days spread across the middle learning statuses.
        if ($note->interval >= 14) {
            return 4;
        }
        if ($note->interval >= 7) {
            return 3;
        }

        return 2;
    }
}
