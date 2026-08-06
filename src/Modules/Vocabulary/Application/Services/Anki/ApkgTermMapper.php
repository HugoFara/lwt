<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgNote;

/**
 * Pure mapping between LWT Term entities and ApkgNote DTOs.
 *
 * Kept free of database access so it can be exercised by unit tests without
 * an integration fixture. The orchestrating services
 * (ApkgExportService / ApkgImportService) handle persistence.
 *
 * Status-to-suspended convention:
 *  - LWT 1..5  -> not suspended in Anki (active card)
 *  - LWT 98    -> suspended in Anki (ignored)
 *  - LWT 99    -> suspended in Anki (well-known; user already knows it)
 *
 * On import, suspending a card in Anki is read as "user no longer wants this
 * in their active rotation" -> status 98 (ignored). We deliberately do *not*
 * promote to status 99 (well-known) on import, because suspension and
 * "well-known" carry different semantics and we don't want to silently lose
 * the distinction.
 */
final class ApkgTermMapper
{
    /**
     * Build an ApkgNote from a Term + its current tag list.
     *
     * @param list<string> $tagNames
     */
    public static function termToNote(Term $term, array $tagNames): ApkgNote
    {
        return new ApkgNote(
            lwtTermId: $term->id()->toInt(),
            term: $term->text(),
            translation: $term->translation(),
            romanization: $term->romanization(),
            notes: $term->notes(),
            tags: $tagNames,
            suspended: self::shouldSuspend($term->status()),
        );
    }

    /**
     * Apply round-tripped note data back onto a Term entity in place.
     *
     * Returns a record of which fields changed so the caller can produce
     * useful import summary stats.
     */
    public static function applyNoteToTerm(Term $term, ApkgNote $note): TermChange
    {
        $changedTranslation = $term->translation() !== $note->translation;
        $changedRomanization = $term->romanization() !== $note->romanization;
        $changedNotes = $term->notes() !== $note->notes;

        if ($changedTranslation) {
            $term->updateTranslation($note->translation);
        }
        if ($changedRomanization) {
            $term->updateRomanization($note->romanization);
        }
        if ($changedNotes) {
            $term->updateNotes($note->notes);
        }

        $statusChangedToIgnored = false;
        if ($note->suspended && self::isLearningStatus($term->status())) {
            $term->setStatus(TermStatus::ignored());
            $statusChangedToIgnored = true;
        }

        return new TermChange(
            translationChanged: $changedTranslation,
            romanizationChanged: $changedRomanization,
            notesChanged: $changedNotes,
            statusChangedToIgnored: $statusChangedToIgnored,
        );
    }

    /**
     * Whether a status maps to a suspended Anki card.
     */
    public static function shouldSuspend(TermStatus $status): bool
    {
        return $status->isIgnored() || $status->isKnown();
    }

    private static function isLearningStatus(TermStatus $status): bool
    {
        return !$status->isIgnored() && !$status->isKnown();
    }
}
