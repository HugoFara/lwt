---
title: "Proposal: data_hex Word Identity"
description: Replace the reading-view TERM<hex> CSS class-as-index with a data_hex attribute identity and a hashed token.
---

# Proposal: `data_hex` Word Identity

**Status:** Implemented (#237), on the post-3.2.1 development line.
Tracked in [issue #237](https://github.com/HugoFara/lwt/issues/237).

This document records the design and rationale of the change as it was carried out.

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

## What changed

- **PHP token:** `src/Shared/Infrastructure/Utilities/StringUtils.php::toClassName()`
  → `substr(hash('sha256', $s), 0, 16)`. `toHex()` kept (independent, tested utility).
- **PHP emit (5 spans, 2 files):** dropped `'TERM' . toClassName(...)` from the `class`
  and added `'data_hex' => toClassName(...)` in
  `Modules/Text/Application/Services/TextReadingService.php` (×3) and
  `Modules/Vocabulary/Application/Services/ExpressionService.php` (×2). The
  server-rendered spans previously carried the token *only* as the class, so this
  is an add-`data_hex` change, not just a drop.
- **JS emit:** removed the `TERM${word.hex}` push in
  `modules/text/pages/reading/text_renderer.ts` (`data_hex` was already emitted).
- **JS selectors (9):** `.TERM${hex}` → `[data_hex="${hex}"]` in
  `modules/vocabulary/services/word_dom_updates.ts` (×5),
  `modules/vocabulary/pages/word_result_init.ts` (×2), and `text_renderer.ts` (×2).
- **JS extractors (2):** dropped the `TERM([0-9A-Fa-f]+)` class-regex fallback and now
  read `data_hex` directly in `text_reader.ts` and `text_keyboard.ts`. (The
  `word_actions.ts` / `text_events.ts` extractors named in the original proposal had
  already been refactored away by the time this landed.)
- **Tests:** rewrote the `toClassName` assertions in
  `tests/backend/Core/IntegrationTest.php` to assert the hash shape (16-char lowercase
  hex) instead of the old `¤` output; migrated the frontend fixtures from
  `class="… TERM<x>"` + `.TERM<x>` to `data_hex="<x>"` + `[data_hex="<x>"]`
  (`tests/frontend/reading/*`, `tests/frontend/words/*`, `tests/frontend/texts/text_reader.test.ts`)
  and removed the now-obsolete class-name-fallback extractor test.

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
