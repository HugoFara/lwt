<?php

declare(strict_types=1);

namespace Lwt\Modules\Vocabulary\Infrastructure\Anki;

/**
 * Turns an Anki field into the plain text LWT stores.
 *
 * Anki fields are HTML, and real shared decks lean on that heavily: `<div>`
 * wrappers, `<b>`/`<span style=...>` emphasis, `<br>` line breaks, `&nbsp;`,
 * `[sound:...]` media references and `{{c1::...}}` cloze markers. Storing any
 * of it raw would put markup into the reading view and stop terms matching the
 * words in a text, so it is all reduced to text here.
 */
final class AnkiFieldText
{
    public static function toPlainText(string $value): string
    {
        // Anki media syntax — meaningless outside Anki, and never part of the word.
        $value = preg_replace('/\[sound:[^\]]*\]/u', '', $value) ?? $value;

        // Cloze deletions: {{c1::answer}} and {{c1::answer::hint}} keep the answer.
        $value = preg_replace('/\{\{c\d+::(.*?)(?:::[^}]*)?\}\}/u', '$1', $value) ?? $value;

        // Structural breaks carry a word boundary; drop the rest of the markup.
        $value = preg_replace('#<\s*(br|/div|/p|/li|/tr|/td)\s*/?\s*>#iu', ' ', $value) ?? $value;
        $value = strip_tags($value);

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Anki emits non-breaking spaces liberally; treat them as whitespace.
        $value = str_replace("\u{00A0}", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
