<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

use Lwt\Modules\Tags\Application\Services\TermTagService;
use Lwt\Modules\Vocabulary\Application\Services\WordCrudService;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\AnkiFieldText;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ForeignApkgReader;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ForeignNote;

/**
 * Creates LWT terms from a deck the user built in Anki (issue #228).
 *
 * This is the "seed my known words" path: rather than reclassifying thousands
 * of words by hand while reading, the user points LWT at a deck they already
 * study and LWT infers each word's status from Anki's own scheduling data.
 *
 * Distinct from {@see ApkgImportService}, which merges an LWT-exported file
 * back into the terms it came from. That one matches on `lwt-` guids and
 * updates; this one has no guids to match and only ever creates.
 */
final class AnkiDeckImportService
{
    /** `words.WoText` is varchar(250). */
    private const MAX_TERM_LENGTH = 250;

    /** How many created terms to show back in the summary. */
    private const SAMPLE_SIZE = 8;

    public function __construct(
        private readonly ForeignApkgReader $reader,
        private readonly WordCrudService $words,
    ) {
    }

    public static function default(): self
    {
        return new self(new ForeignApkgReader(), new WordCrudService());
    }

    public function import(string $apkgPath, DeckImportSettings $settings): DeckImportResult
    {
        $notes = $this->reader->notes($apkgPath, $settings->notetypeId);

        $created = 0;
        $skippedExisting = 0;
        $skippedEmpty = 0;
        $skippedTooLong = 0;
        $statusCounts = [];
        $samples = [];

        // Terms already created in this run, so a deck containing the same word
        // twice (common with forward/reverse notes split across notetypes)
        // does not report a spurious "already existed".
        $seen = [];

        foreach ($notes as $note) {
            $term = $this->cleanFieldValue($note->field($settings->termField));

            if ($term === '') {
                $skippedEmpty++;
                continue;
            }

            if (mb_strlen($term) > self::MAX_TERM_LENGTH) {
                $skippedTooLong++;
                continue;
            }

            $termLc = mb_strtolower($term, 'UTF-8');
            if (isset($seen[$termLc])) {
                $skippedExisting++;
                continue;
            }
            $seen[$termLc] = true;

            $status = $settings->statusFor($note);

            $result = $this->words->create([
                'WoLgID' => $settings->languageId,
                'WoText' => $term,
                'WoStatus' => $status,
                'WoTranslation' => $this->translationFor($note, $settings),
                'WoSentence' => '',
                'WoNotes' => '',
                'WoRomanization' => '',
            ]);

            if ($result['success'] !== true) {
                // WordCrudService reports a duplicate as a failed create; the
                // unique key on (WoTextLC, WoLgID) is what actually enforces it,
                // so this is the authoritative "already had it" signal.
                $skippedExisting++;
                continue;
            }

            $created++;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if (count($samples) < self::SAMPLE_SIZE) {
                $samples[] = $term;
            }

            if ($settings->importTags && $note->tags !== []) {
                TermTagService::saveWordTags($result['id'], $note->tags);
            }
        }

        ksort($statusCounts);

        return new DeckImportResult(
            totalNotes: count($notes),
            created: $created,
            skippedExisting: $skippedExisting,
            skippedEmpty: $skippedEmpty,
            skippedTooLong: $skippedTooLong,
            statusCounts: $statusCounts,
            samples: $samples,
        );
    }

    private function translationFor(ForeignNote $note, DeckImportSettings $settings): string
    {
        if ($settings->translationField === null) {
            return '';
        }

        return $this->cleanFieldValue($note->field($settings->translationField));
    }

    private function cleanFieldValue(string $value): string
    {
        return AnkiFieldText::toPlainText($value);
    }
}
