<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Vocabulary\Infrastructure\Anki;

use Lwt\Modules\Vocabulary\Infrastructure\Anki\AnkiFieldText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Anki fields are HTML. Real shared decks are full of markup, and any of it
 * surviving into `words.WoText` would both look wrong in the reading view and
 * stop the term ever matching a word in a text.
 */
final class AnkiFieldTextTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function fieldProvider(): array
    {
        return [
            'plain text is untouched' => ['der Hund', 'der Hund'],
            'strips bold' => ['<b>der Hund</b>', 'der Hund'],
            'strips styled spans' => ['<span style="color:red">Katze</span>', 'Katze'],
            'strips div wrappers' => ['<div>das Pferd</div>', 'das Pferd'],
            'br becomes a space' => ['eins<br>zwei', 'eins zwei'],
            'self-closing br becomes a space' => ['eins<br />zwei', 'eins zwei'],
            'div boundaries separate words' => ['<div>eins</div><div>zwei</div>', 'eins zwei'],
            'removes sound references' => ['neko [sound:neko.mp3]', 'neko'],
            'removes sound-only content' => ['[sound:a.mp3]', ''],
            'keeps cloze answers' => ['{{c1::Hund}}', 'Hund'],
            'keeps cloze answers with hints' => ['{{c1::Hund::animal}}', 'Hund'],
            'decodes entities' => ['caf&eacute;', 'café'],
            'decodes ampersands' => ['this &amp; that', 'this & that'],
            'non-breaking space becomes a space' => ["a&nbsp;b", 'a b'],
            'collapses whitespace' => ["a   \n\t b", 'a b'],
            'trims' => ['   Hund   ', 'Hund'],
            'empty stays empty' => ['', ''],
            'markup-only becomes empty' => ['<div><br></div>', ''],
            'combined real-world field' => [
                '<div><b>der&nbsp;Hund</b><br>[sound:hund.mp3]</div>',
                'der Hund',
            ],
        ];
    }

    #[DataProvider('fieldProvider')]
    public function testToPlainText(string $input, string $expected): void
    {
        $this->assertSame($expected, AnkiFieldText::toPlainText($input));
    }

    public function testNonLatinScriptsSurvive(): void
    {
        $this->assertSame('日本語', AnkiFieldText::toPlainText('<div>日本語</div>'));
        $this->assertSame('привет', AnkiFieldText::toPlainText('<b>привет</b>'));
        $this->assertSame('مرحبا', AnkiFieldText::toPlainText('مرحبا'));
    }
}
