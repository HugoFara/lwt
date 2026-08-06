<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Modules\Vocabulary\Application\Services\Anki\AnkiDeckImportService;
use Lwt\Modules\Vocabulary\Application\Services\Anki\DeckImportResult;
use Lwt\Modules\Vocabulary\Application\Services\Anki\DeckImportSettings;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ForeignApkgReader;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ForeignNotetype;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use RuntimeException;
use Throwable;

/**
 * Import a deck built in Anki as new LWT terms (issue #228).
 *
 * Two steps, because LWT cannot guess any of the mapping: an .apkg carries no
 * language, and field names in a shared deck are arbitrary.
 *
 *   GET  /vocabulary/anki-deck/import   upload form
 *   POST /vocabulary/anki-deck/import   upload -> configure, or configure -> import
 *
 * The uploaded file is parked in a temp path between the two steps, keyed by a
 * token held in the session so the second request cannot be pointed at an
 * arbitrary path.
 */
class AnkiDeckImportController extends VocabularyBaseController
{
    private const SESSION_KEY = 'lwt_anki_deck_import';

    private LanguageFacade $languageFacade;
    private ForeignApkgReader $reader;
    private ?AnkiDeckImportService $importService;

    public function __construct(
        ?LanguageFacade $languageFacade = null,
        ?ForeignApkgReader $reader = null,
        ?AnkiDeckImportService $importService = null,
    ) {
        parent::__construct();
        $this->languageFacade = $languageFacade ?? new LanguageFacade();
        $this->reader = $reader ?? new ForeignApkgReader();
        $this->importService = $importService;
    }

    /**
     * @param array<string, string> $params Route params (unused).
     */
    public function index(array $params): void
    {
        PageLayoutHelper::renderPageStart('Import an Anki deck', true);

        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                $this->renderUploadForm(null);
            } elseif (InputValidator::getString('step') === 'import') {
                $this->handleImport();
            } else {
                $this->handleUpload();
            }
        } catch (Throwable $e) {
            $this->renderUploadForm($e->getMessage());
        }

        PageLayoutHelper::renderPageEnd();
    }

    /**
     * Step 1: accept the upload, park it, show the mapping form.
     */
    private function handleUpload(): void
    {
        $file = InputValidator::getUploadedFile('apkg');
        if ($file === null) {
            $this->renderUploadForm('No file was uploaded.');
            return;
        }

        if (!str_ends_with(strtolower($file['name']), '.apkg')) {
            $this->renderUploadForm('Only .apkg files are accepted.');
            return;
        }

        $parked = tempnam(sys_get_temp_dir(), 'lwt_deck_');
        if ($parked === false) {
            $this->renderUploadForm('Could not store the uploaded file.');
            return;
        }
        if (!move_uploaded_file($file['tmp_name'], $parked)) {
            // move_uploaded_file only accepts a genuine HTTP upload; fall back
            // to a copy so the flow stays exercisable outside a web request.
            if (!copy($file['tmp_name'], $parked)) {
                @unlink($parked);
                $this->renderUploadForm('Could not store the uploaded file.');
                return;
            }
        }

        $notetypes = $this->reader->notetypes($parked);
        if ($notetypes === []) {
            @unlink($parked);
            $this->renderUploadForm('No notetypes found in this file — it may not be an Anki deck.');
            return;
        }

        $this->rememberParkedFile($parked);
        $this->renderMappingForm($notetypes, null);
    }

    /**
     * Step 2: apply the chosen mapping.
     */
    private function handleImport(): void
    {
        $parked = $this->parkedFile();
        if ($parked === null) {
            $this->renderUploadForm('That upload has expired. Please choose the file again.');
            return;
        }

        $notetypes = $this->reader->notetypes($parked);

        try {
            $settings = new DeckImportSettings(
                notetypeId: InputValidator::getInt('notetype') ?? 0,
                termField: InputValidator::getString('term_field'),
                translationField: $this->optionalField('translation_field'),
                languageId: InputValidator::getInt('language') ?? 0,
                deriveStatus: InputValidator::getString('status_mode') !== 'fixed',
                fixedStatus: InputValidator::getInt('fixed_status') ?? 1,
                importTags: InputValidator::getString('import_tags') !== '',
            );
        } catch (\InvalidArgumentException $e) {
            $this->renderMappingForm($notetypes, $e->getMessage());
            return;
        }

        $result = $this->importSvc()->import($parked, $settings);

        $this->forgetParkedFile();
        $this->renderSummary($result, $settings);
    }

    private function optionalField(string $key): ?string
    {
        $value = InputValidator::getString($key);

        return $value === '' ? null : $value;
    }

    private function renderUploadForm(?string $error): void
    {
        echo '<h1>Import an Anki deck</h1>';

        if ($error !== null) {
            echo '<div class="notification is-danger">' . $this->esc($error) . '</div>';
        }

        echo '<p class="mb-4">Already study this language in Anki? Import the deck and LWT will '
            . 'mark those words as known, so you do not have to reclassify them while reading. '
            . 'Export your deck from Anki with <strong>File → Export → Anki Deck Package '
            . '(.apkg)</strong>, including scheduling information.</p>';

        echo '<form method="post" enctype="multipart/form-data" action="/vocabulary/anki-deck/import">';
        echo $this->csrfField();
        echo '<div class="field"><label class="label" for="apkg-file">Anki deck (.apkg)</label>'
            . '<div class="control"><input class="input" type="file" name="apkg" id="apkg-file"'
            . ' accept=".apkg" required></div></div>';
        echo '<div class="field"><div class="control">'
            . '<button class="button is-primary" type="submit">Continue</button>'
            . '</div></div>';
        echo '</form>';

        echo '<p class="help mt-4">This creates new terms. It never changes terms you already '
            . 'have — importing the same deck twice is safe.</p>';
    }

    /**
     * @param list<ForeignNotetype> $notetypes
     */
    private function renderMappingForm(array $notetypes, ?string $error): void
    {
        echo '<h1>Import an Anki deck</h1>';

        if ($error !== null) {
            echo '<div class="notification is-danger">' . $this->esc($error) . '</div>';
        }

        echo '<p class="mb-4">LWT cannot tell which field holds the word, or what language the '
            . 'deck is in — an .apkg does not record either. Choose them below.</p>';

        echo '<form method="post" action="/vocabulary/anki-deck/import">';
        echo $this->csrfField();
        echo '<input type="hidden" name="step" value="import">';

        // Notetype, with its fields listed so the choice is informed.
        echo '<div class="field"><label class="label" for="notetype">Note type</label>'
            . '<div class="control"><div class="select"><select name="notetype" id="notetype">';
        foreach ($notetypes as $nt) {
            echo '<option value="' . $nt->id . '">'
                . $this->esc($nt->name) . ' — ' . $nt->noteCount . ' notes ('
                . $this->esc(implode(', ', $nt->fields)) . ')</option>';
        }
        echo '</select></div></div></div>';

        // Field pickers list every field across every notetype; the user picks
        // the notetype above and the matching names from the same vocabulary.
        $allFields = [];
        foreach ($notetypes as $nt) {
            foreach ($nt->fields as $field) {
                $allFields[$field] = true;
            }
        }
        $fieldNames = array_keys($allFields);

        echo $this->fieldSelect('term_field', 'Field holding the term', $fieldNames, false);
        echo $this->fieldSelect('translation_field', 'Field holding the translation', $fieldNames, true);

        // Language.
        echo '<div class="field"><label class="label" for="language">Language</label>'
            . '<div class="control"><div class="select"><select name="language" id="language" required>'
            . '<option value="">Choose a language…</option>';
        foreach ($this->languageFacade->getAllLanguages() as $name => $id) {
            echo '<option value="' . $id . '">' . $this->esc($name) . '</option>';
        }
        echo '</select></div></div></div>';

        // Status rule.
        echo '<div class="field"><label class="label">Word status</label>';
        echo '<div class="control"><label class="radio">'
            . '<input type="radio" name="status_mode" value="derive" checked> '
            . 'Derive from Anki (recommended)</label></div>';
        echo '<p class="help">Cards you have known for ' . DeckImportSettings::MATURE_INTERVAL_DAYS
            . ' days or more become <strong>well known</strong>; younger cards get a learning '
            . 'status based on their interval; unstudied cards start at level 1; suspended cards '
            . 'become <strong>ignored</strong>.</p>';
        echo '<div class="control mt-2"><label class="radio">'
            . '<input type="radio" name="status_mode" value="fixed"> '
            . 'Give every word the same status</label></div>';
        echo '<div class="control mt-2"><div class="select is-small">'
            . '<select name="fixed_status">'
            . '<option value="99">Well known</option>'
            . '<option value="1">1 (learning)</option>'
            . '<option value="2">2</option>'
            . '<option value="3">3</option>'
            . '<option value="4">4</option>'
            . '<option value="5">5</option>'
            . '<option value="98">Ignored</option>'
            . '</select></div></div>';
        echo '</div>';

        echo '<div class="field"><div class="control"><label class="checkbox">'
            . '<input type="checkbox" name="import_tags" value="1" checked> '
            . 'Import Anki tags</label></div></div>';

        echo '<div class="field"><div class="control">'
            . '<button class="button is-primary" type="submit">Import</button>'
            . '</div></div>';
        echo '</form>';
    }

    /**
     * @param list<string> $fieldNames
     */
    private function fieldSelect(string $name, string $label, array $fieldNames, bool $optional): string
    {
        $out = '<div class="field"><label class="label" for="' . $name . '">'
            . $this->esc($label) . '</label>'
            . '<div class="control"><div class="select"><select name="' . $name . '" id="' . $name . '"'
            . ($optional ? '' : ' required') . '>';

        if ($optional) {
            $out .= '<option value="">(none)</option>';
        }
        foreach ($fieldNames as $field) {
            $out .= '<option value="' . $this->esc($field) . '">' . $this->esc($field) . '</option>';
        }

        return $out . '</select></div></div></div>';
    }

    private function renderSummary(DeckImportResult $result, DeckImportSettings $settings): void
    {
        echo '<h1>Import an Anki deck</h1>';

        echo '<div class="notification is-success"><p><strong>Imported '
            . $result->created . ' new term' . ($result->created === 1 ? '' : 's') . '.</strong></p></div>';

        echo '<table class="table is-narrow"><tbody>';
        echo '<tr><th>Notes read</th><td>' . $result->totalNotes . '</td></tr>';
        echo '<tr><th>Terms created</th><td>' . $result->created . '</td></tr>';
        echo '<tr><th>Already in LWT</th><td>' . $result->skippedExisting . '</td></tr>';
        if ($result->skippedEmpty > 0) {
            echo '<tr><th>Skipped (empty term field)</th><td>' . $result->skippedEmpty . '</td></tr>';
        }
        if ($result->skippedTooLong > 0) {
            echo '<tr><th>Skipped (too long to store)</th><td>' . $result->skippedTooLong . '</td></tr>';
        }
        echo '</tbody></table>';

        if ($result->statusCounts !== []) {
            echo '<h2 class="title is-5 mt-5">Status breakdown</h2><ul>';
            foreach ($result->statusCounts as $status => $count) {
                echo '<li>' . $this->esc($this->statusLabel($status)) . ': ' . $count . '</li>';
            }
            echo '</ul>';
        }

        if ($result->samples !== []) {
            echo '<p class="mt-4"><strong>For example:</strong> '
                . $this->esc(implode(', ', $result->samples)) . '…</p>';
        }

        if ($result->created === 0 && $result->skippedEmpty === $result->totalNotes) {
            echo '<div class="notification is-warning mt-4">Every note had an empty term field. '
                . 'The chosen field is probably not the one holding the word — go back and pick '
                . 'a different one.</div>';
        }

        echo '<p class="mt-5"><a class="button" href="/words/edit?lang='
            . $settings->languageId . '">View the imported terms</a> '
            . '<a class="button is-light" href="/vocabulary/anki-deck/import">Import another deck</a></p>';
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            98 => 'Ignored',
            99 => 'Well known',
            default => 'Level ' . $status,
        };
    }

    private function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="'
            . $this->esc(\Lwt\Shared\UI\Helpers\FormHelper::csrfToken()) . '">';
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function rememberParkedFile(string $path): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = $path;
        }
    }

    /**
     * The parked upload for this session, if it is still there.
     *
     * Reading the path from the session rather than the request is what stops
     * step 2 being pointed at an arbitrary file on disk.
     */
    private function parkedFile(): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }

        $path = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($path) || !is_file($path)) {
            return null;
        }

        return $path;
    }

    private function forgetParkedFile(): void
    {
        $path = $this->parkedFile();
        if ($path !== null) {
            @unlink($path);
        }
        unset($_SESSION[self::SESSION_KEY]);
    }

    private function importSvc(): AnkiDeckImportService
    {
        return $this->importService ??= AnkiDeckImportService::default();
    }
}
