<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Application\Services\Anki;

use Lwt\Modules\Language\Domain\LanguageRepositoryInterface;
use Lwt\Modules\Language\Infrastructure\MySqlLanguageRepository;
use Lwt\Modules\Tags\Application\Services\TermTagService;
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgDeck;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgNote;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgWriter;
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;
use RuntimeException;

/**
 * Orchestrates building an .apkg from one LWT language's terms.
 *
 * Pulls the terms via the term repository, looks up tags through
 * TermTagService, runs them through ApkgTermMapper, then hands the resulting
 * notes to ApkgWriter. The writer is the only piece that knows the Anki
 * binary layout; this service is just data assembly.
 */
final class ApkgExportService
{
    public function __construct(
        private readonly TermRepositoryInterface $terms,
        private readonly LanguageRepositoryInterface $languages,
        private readonly ApkgWriter $writer,
    ) {
    }

    public static function default(): self
    {
        return new self(
            new MySqlTermRepository(),
            new MySqlLanguageRepository(),
            new ApkgWriter(),
        );
    }

    public function exportLanguage(int $languageId, string $outputPath): ExportResult
    {
        $language = $this->languages->find($languageId);
        if ($language === null) {
            throw new RuntimeException("Language {$languageId} not found");
        }

        $terms = $this->terms->findByLanguage($languageId);
        if ($terms === []) {
            throw new RuntimeException(
                "No terms to export for language '{$language->name()}'"
            );
        }

        $deck = ApkgDeck::forLanguage($languageId, $language->name());

        $notes = [];
        $suspended = 0;
        foreach ($terms as $term) {
            $tagNames = array_values(TermTagService::getWordTagsArray($term->id()->toInt()));
            $note = ApkgTermMapper::termToNote($term, $tagNames);
            $notes[] = $note;
            if ($note->suspended) {
                $suspended++;
            }
        }

        // Writer requires a non-empty list; guarded above.
        /** @var non-empty-list<ApkgNote> $notes */
        $this->writer->write($outputPath, $deck, $notes);

        return new ExportResult(
            outputPath: $outputPath,
            languageId: $languageId,
            languageName: $language->name(),
            deckName: $deck->name,
            noteCount: count($notes),
            suspendedCount: $suspended,
        );
    }
}
