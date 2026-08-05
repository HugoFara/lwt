<?php

declare(strict_types=1);

namespace Tests\Backend\Modules\Vocabulary\Application\Services\Anki;

use DateTimeImmutable;
use Lwt\Modules\Vocabulary\Application\Services\Anki\ApkgTermMapper;
use Lwt\Modules\Vocabulary\Domain\Term;
use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgNote;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApkgTermMapper. No DB; uses Term::reconstitute fixtures.
 */
final class ApkgTermMapperTest extends TestCase
{
    public function testTermToNoteCarriesAllFieldsAndTags(): void
    {
        $term = $this->makeTerm(
            id: 42,
            text: 'hello',
            translation: 'a greeting',
            romanization: 'həˈloʊ',
            notes: 'informal',
            status: TermStatus::LEARNING_3,
        );

        $note = ApkgTermMapper::termToNote($term, ['greeting', 'common']);

        self::assertSame(42, $note->lwtTermId);
        self::assertSame('hello', $note->term);
        self::assertSame('a greeting', $note->translation);
        self::assertSame('həˈloʊ', $note->romanization);
        self::assertSame('informal', $note->notes);
        self::assertSame(['greeting', 'common'], $note->tags);
        self::assertFalse($note->suspended, 'learning-status terms must not be suspended');
        self::assertSame('lwt-42', $note->guid());
    }

    public function testIgnoredAndWellKnownTermsAreSuspendedOnExport(): void
    {
        $ignored = $this->makeTerm(id: 1, text: 't', status: TermStatus::IGNORED);
        $known = $this->makeTerm(id: 2, text: 't', status: TermStatus::WELL_KNOWN);

        self::assertTrue(ApkgTermMapper::termToNote($ignored, [])->suspended);
        self::assertTrue(ApkgTermMapper::termToNote($known, [])->suspended);
    }

    public function testApplyNoteUpdatesChangedFieldsAndReportsThem(): void
    {
        $term = $this->makeTerm(
            id: 7,
            text: 'word',
            translation: 'old translation',
            romanization: 'old roman',
            notes: 'old notes',
        );

        $note = $this->makeNote(
            lwtTermId: 7,
            translation: 'new translation',
            romanization: 'new roman',
            notes: 'new notes',
        );

        $change = ApkgTermMapper::applyNoteToTerm($term, $note);

        self::assertTrue($change->translationChanged);
        self::assertTrue($change->romanizationChanged);
        self::assertTrue($change->notesChanged);
        self::assertFalse($change->statusChangedToIgnored);
        self::assertSame('new translation', $term->translation());
        self::assertSame('new roman', $term->romanization());
        self::assertSame('new notes', $term->notes());
    }

    public function testApplyNoteWithIdenticalDataReportsNoChange(): void
    {
        $term = $this->makeTerm(
            id: 7,
            text: 'word',
            translation: 'same',
            romanization: 'same',
            notes: 'same',
        );

        $note = $this->makeNote(
            lwtTermId: 7,
            translation: 'same',
            romanization: 'same',
            notes: 'same',
        );

        $change = ApkgTermMapper::applyNoteToTerm($term, $note);

        self::assertFalse($change->anyFieldChanged());
    }

    public function testSuspendedNoteDemotesLearningTermToIgnored(): void
    {
        $term = $this->makeTerm(id: 1, text: 't', status: TermStatus::LEARNING_3);

        $note = $this->makeNote(lwtTermId: 1, suspended: true);
        $change = ApkgTermMapper::applyNoteToTerm($term, $note);

        self::assertTrue($change->statusChangedToIgnored);
        self::assertSame(TermStatus::IGNORED, $term->status()->toInt());
    }

    public function testSuspendedNoteDoesNotTouchAlreadyIgnoredOrKnown(): void
    {
        $ignored = $this->makeTerm(id: 1, text: 't', status: TermStatus::IGNORED);
        $known = $this->makeTerm(id: 2, text: 't', status: TermStatus::WELL_KNOWN);

        $note = $this->makeNote(lwtTermId: 1, suspended: true);
        ApkgTermMapper::applyNoteToTerm($ignored, $note);
        ApkgTermMapper::applyNoteToTerm($known, $note);

        self::assertSame(TermStatus::IGNORED, $ignored->status()->toInt());
        self::assertSame(TermStatus::WELL_KNOWN, $known->status()->toInt());
    }

    public function testUnsuspendedNoteLeavesStatusAlone(): void
    {
        $term = $this->makeTerm(id: 1, text: 't', status: TermStatus::LEARNING_3);

        $note = $this->makeNote(lwtTermId: 1, suspended: false);
        $change = ApkgTermMapper::applyNoteToTerm($term, $note);

        self::assertFalse($change->statusChangedToIgnored);
        self::assertSame(TermStatus::LEARNING_3, $term->status()->toInt());
    }

    private function makeTerm(
        int $id,
        string $text,
        int $status = TermStatus::NEW,
        string $translation = '',
        string $romanization = '',
        string $notes = '',
    ): Term {
        $now = new DateTimeImmutable();
        return Term::reconstitute(
            id: $id,
            languageId: 1,
            text: $text,
            textLowercase: mb_strtolower($text),
            lemma: null,
            lemmaLc: null,
            status: $status,
            translation: $translation,
            sentence: '',
            notes: $notes,
            romanization: $romanization,
            wordCount: 1,
            createdAt: $now,
            statusChangedAt: $now,
            todayScore: 0.0,
            tomorrowScore: 0.0,
            random: 0.5,
        );
    }

    private function makeNote(
        int $lwtTermId,
        string $term = 'word',
        string $translation = '',
        string $romanization = '',
        string $notes = '',
        bool $suspended = false,
    ): ApkgNote {
        return new ApkgNote(
            lwtTermId: $lwtTermId,
            term: $term,
            translation: $translation,
            romanization: $romanization,
            notes: $notes,
            tags: [],
            suspended: $suspended,
        );
    }
}
