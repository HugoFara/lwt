<?php

declare(strict_types=1);

/**
 * Read an .apkg with the PHP reader and print one line per note. Useful for
 * verifying that LWT can ingest a file produced by Anki itself.
 *
 * Usage:
 *   php bin/lwt-apkg-read.php path/to/file.apkg
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Lwt\Modules\Vocabulary\Infrastructure\Anki\ApkgReader;

if ($argc !== 2) {
    fwrite(STDERR, "usage: php {$argv[0]} file.apkg\n");
    exit(2);
}

$notes = (new ApkgReader())->read($argv[1]);
fwrite(STDOUT, "Read " . count($notes) . " notes from {$argv[1]}\n");

foreach ($notes as $n) {
    fwrite(STDOUT, sprintf(
        "  lwt#%d  term=%-12s translation=%-20s suspended=%s tags=%s\n",
        $n->lwtTermId,
        '"' . $n->term . '"',
        '"' . $n->translation . '"',
        $n->suspended ? 'yes' : 'no',
        json_encode($n->tags)
    ));
}
