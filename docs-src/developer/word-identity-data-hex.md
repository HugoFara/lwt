---
title: "Proposal: data_hex Word Identity"
description: Replace the reading-view TERM<hex> CSS class-as-index with a data_hex attribute identity and a hashed token.
---

# Proposal: `data_hex` Word Identity

**Status:** Proposed — deferred until after the next release (a major feature lands
first; this is a broad cross-cutting refactor we don't want to collide with it).
Tracked in [issue #237](https://github.com/HugoFara/lwt/issues/237).

A design proposal, not shipped work. It records the agreed approach so it isn't lost
between releases.

## Problem

In the reading view, every occurrence of the same term shares an identity token so
that a status change (e.g. marking a word "known") can restyle **all** occurrences
client-side in a single pass. Today that identity is carried two ways at once:

- a CSS class `TERM<hex>` on each word span, and
- a `data_hex` attribute on JS-rendered spans (the same value, duplicated).

The `<hex>` token comes from `StringUtils::toClassName()`, an original-LWT (2011)
encoder that keeps `0-9 A-Z a-z` and Unicode ≥ 165 and escapes everything else to
`¤` + hex. Three issues:

1. **Dual identity.** The same value lives in both a class and an attribute; lookups
   are split across `.TERM<hex>` selectors and `data_hex` reads.
2. **Hacky, subtly-broken encoding.** `toClassName` iterates per character
   (`mb_substr`) but tests per byte (`ord`), so for any non-ASCII char `ord()` only
   ever saw the lead byte. The `¤`-sentinel / `165`-threshold scheme was designed to
   keep the encoding unambiguous, but the byte/codepoint confusion meant that
   invariant was never actually realized. PHP 8.5 surfaced the smell by deprecating
   `ord()` on a multi-byte string.
3. **Fragile extraction.** The JS extractors use `TERM([a-f0-9]+)`, but the encoded
   token can contain `g-z`, `G-Z`, and `¤`; for those words the regex fails and
   silently falls back to `data_hex` — proof the class-as-index is already being
   superseded.

## Proposal

Make `data_hex` the **single, future-facing identity**:

- Select occurrences via the `[data_hex="…"]` attribute selector.
- **Drop the `TERM` class entirely** (it has zero CSS dependencies — purely an index).
- Replace `toClassName`'s `¤`/hex encoding with a short hash:

  ```php
  public static function toClassName(string $string): string
  {
      return substr(hash('sha256', $string), 0, 16); // 64-bit, pure [0-9a-f]
  }
  ```

The token stays an **opaque, recomputable, contained value** — the API `hex` field
keeps its exact role, just a hash string. So there is **no wire-format ripple** and
**no `CSS.escape`** needed (a pure-hex token is selector-safe). As a bonus, the
`TERM([a-f0-9]+)` extractors become correct by construction, and the whole
`¤` / `165` / `mb_ord`-vs-`ord` question disappears.

## Why it's safe

- **The token is never reversed back to text.** Nothing decodes `¤`/hex to a string;
  the backend re-derives the token from `WoTextLC` (e.g.
  `TermEditController::textToClassName`). So abandoning a reversible encoding for a
  one-way hash loses nothing in use today.
- **`.TERM` has no CSS rules** — removing the class affects styling nowhere (status
  and word-id classes do the styling).
- **No persistence.** Tokens are computed per render, never stored, so changing the
  format can't desync stored data.

The only thing given up is human readability of the token in devtools
(`data_hex="3a7f9c2e1b0d4f88"` instead of a mostly-readable string) — accepted.

## Scope sketch

When picked up post-release:

- **PHP token:** `src/Shared/Infrastructure/Utilities/StringUtils.php::toClassName()`
  → hash. Keep `toHex()` (independent, tested utility).
- **PHP emit (5 spans, 2 files):** drop `'TERM' . toClassName(...)` from the `class`
  and add `'data_hex' => toClassName(...)` in
  `Modules/Text/Application/Services/TextReadingService.php` (×3) and
  `Modules/Vocabulary/Application/Services/ExpressionService.php` (×2).
- **JS emit:** remove the `TERM${word.hex}` push in
  `modules/text/pages/reading/text_renderer.ts` (`data_hex` is already emitted).
- **JS selectors (~9):** `.TERM${hex}` → `[data_hex="${hex}"]` in
  `modules/vocabulary/services/word_dom_updates.ts`,
  `modules/vocabulary/pages/word_result_init.ts`, and `text_renderer.ts`.
- **JS extractors (4):** read `data_hex` instead of parsing the class in
  `text_reader.ts`, `text_keyboard.ts`, `word_actions.ts`, `text_events.ts`.
- **Tests:** update `toClassName` assertions in
  `tests/backend/Core/IntegrationTest.php` and `tests/backend/Core/Text/TextProcessingTest.php`
  (assert hash shape, not the old `¤` output); migrate frontend fixtures from
  `class="… TERM<x>"` + `.TERM<x>` to `data_hex="<x>"` + `[data_hex="<x>"]`
  (`tests/frontend/reading/*`, `tests/frontend/words/*`, `tests/frontend/texts/text_reader.test.ts`).

### Out of scope

- `toHex()` and its tests (kept as an independent utility).
- `Modules/Review/Views/table_review_row.php`'s `id="TERM<woId>"` — a different
  mechanism (numeric word-id element id), not the hex class.

## Verification (at implementation time)

1. PHP gates: `phpcs --standard=PSR12`, `psalm --threads=1`, `composer test:no-coverage`.
2. Frontend: `npm run typecheck`, `npm run lint`, `npm test`, `npm run build:all`.
3. E2E smoke (`npm run e2e`): open a text, change a word's status, confirm **all**
   occurrences restyle at once — including in a multi-word expression and on a
   server-rendered reading page (the `TextReadingService` path). Exercise keyboard
   word-nav and the word-edit result refresh.
