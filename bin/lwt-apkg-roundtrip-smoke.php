<?php

declare(strict_types=1);

/**
 * Smoke test for the Anki .apkg writer + reader.
 *
 * Builds a small .apkg using a few hard-coded LWT-shaped notes, prints the
 * output path, then re-reads the file and asserts every field round-tripped.
 *
 * Usage:
 *   php bin/lwt-apkg-roundtrip-smoke.php [output.apkg]
 *
 * The output file can then be fed to Anki desktop (File → Import) for human
 * verification, or to scripts/anki/validate-apkg.py for automated checking
 * via the genanki round-trip.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgDeck;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgNote;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgReader;
use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgWriter;

$outputPath = $argv[1] ?? sys_get_temp_dir() . '/lwt-smoke.apkg';

$deck = ApkgDeck::forLanguage(1, 'English');

$inputs = [
    new ApkgNote(
        lwtTermId: 42,
        term: 'hello',
        translation: 'a greeting',
        romanization: 'həˈloʊ',
        notes: 'informal',
        tags: ['greeting', 'common'],
        suspended: false,
    ),
    new ApkgNote(
        lwtTermId: 43,
        term: 'world',
        translation: 'la planète Terre',
        romanization: '',
        notes: '',
        tags: [],
        suspended: false,
    ),
    new ApkgNote(
        lwtTermId: 44,
        term: 'goodbye',
        translation: 'leave-taking',
        romanization: '',
        notes: 'we know this one well',
        tags: ['known'],
        suspended: true,
    ),
];

(new ApkgWriter())->write($outputPath, $deck, $inputs);

$size = filesize($outputPath);
fwrite(STDOUT, sprintf("[OK] wrote %s (%d bytes)\n", $outputPath, (int) $size));

$readBack = (new ApkgReader())->read($outputPath);
fwrite(STDOUT, sprintf("[OK] read back %d notes\n", count($readBack)));

$failures = 0;
$assert = static function (bool $cond, string $msg) use (&$failures): void {
    if (!$cond) {
        fwrite(STDERR, "  [FAIL] $msg\n");
        $failures++;
    }
};

if (count($readBack) !== count($inputs)) {
    $assert(false, sprintf('count mismatch: expected %d, got %d', count($inputs), count($readBack)));
}

$byId = [];
foreach ($readBack as $n) {
    $byId[$n->lwtTermId] = $n;
}

foreach ($inputs as $expected) {
    $actual = $byId[$expected->lwtTermId] ?? null;
    if ($actual === null) {
        $assert(false, "missing note for lwtTermId={$expected->lwtTermId}");
        continue;
    }
    $assert($actual->term === $expected->term, "term mismatch for #{$expected->lwtTermId}");
    $assert($actual->translation === $expected->translation, "translation mismatch for #{$expected->lwtTermId}");
    $assert($actual->romanization === $expected->romanization, "romanization mismatch for #{$expected->lwtTermId}");
    $assert($actual->notes === $expected->notes, "notes mismatch for #{$expected->lwtTermId}");
    $assert($actual->tags === $expected->tags, "tags mismatch for #{$expected->lwtTermId} (got " . json_encode($actual->tags) . ')');
    $assert($actual->suspended === $expected->suspended, "suspended mismatch for #{$expected->lwtTermId}");
}

if ($failures > 0) {
    fwrite(STDERR, "\n[FAIL] $failures assertion(s) failed\n");
    exit(1);
}

fwrite(STDOUT, "[OK] round-trip clean\n");
