---
title: "Import an Anki Deck"
description: Seed your LWT vocabulary from a deck you already study in Anki, using Anki's own scheduling data to work out which words you already know.
---

# Import an Anki Deck

If you already study a language in Anki, you probably know thousands of words
that LWT does not know about yet. Importing your deck marks those words as known
in one step, instead of you reclassifying each one as you meet it while reading.

**Where:** *Terms → Import an Anki deck*, or `/vocabulary/anki-deck/import`.

::: tip This is not the same as Anki Export & Import
[Anki Export & Import](/reference/anki-export-import) is a **round trip** for
terms that started life in LWT: export them, study in Anki, import the file back
to update them. It matches notes by an `lwt-` identifier that only LWT-exported
files carry.

This page is the opposite direction: a deck **you built in Anki**, which has no
such identifier, imported to **create new terms**. Feeding an Anki-built deck to
the round-trip importer does nothing at all — it finds nothing to match.
:::

## Getting the file out of Anki

In Anki: **File → Export**, choose **Anki Deck Package (.apkg)**, pick the deck,
and keep **Include scheduling information** ticked. The scheduling data is what
lets LWT tell an word you have known for a year from one you saw yesterday.

## The import in two steps

**1. Upload.** LWT reads the file and lists the note types it contains, with
their field names and note counts.

**2. Map.** An `.apkg` records neither a language nor which field means what, so
you choose:

| Choice | Why LWT has to ask |
| --- | --- |
| **Note type** | A deck can contain several; you may only want one |
| **Term field** | Field names are arbitrary — `Front`, `Expression`, `Word`, `Vocab`… |
| **Translation field** | Optional. Pick `(none)` to import words only |
| **Language** | An `.apkg` does not record one |
| **Word status** | Derived from Anki, or one fixed status for everything |
| **Import tags** | Anki tags become LWT term tags |

## How word status is decided

With **Derive from Anki** (the default), each note's status comes from how well
Anki thinks you know it — specifically the card's interval:

| In Anki | Becomes in LWT |
| --- | --- |
| Suspended | **98** — Ignored |
| Never studied | **1** — Learning, level 1 |
| Interval 1–6 days | **2** |
| Interval 7–13 days | **3** |
| Interval 14–20 days | **4** |
| Interval 21+ days | **99** — Well known |

The 21-day line is Anki's own definition of a *mature* card, the same threshold
its statistics screen uses, so it should match your intuition about the deck.

A note with several cards (forward and reverse, say) is judged by its **strongest**
card: knowing the word in one direction is enough to count. A note only counts as
suspended if *every* one of its cards is suspended.

If that mapping does not suit your deck, choose **Give every word the same
status** instead — useful for a deck you have fully mastered (set everything to
*Well known*) or one you are only starting.

## What gets cleaned up

Anki fields are HTML, and real decks lean on it. Before storing a term LWT
removes formatting tags, converts `<br>` and block boundaries to spaces, decodes
entities like `&nbsp;` and `&eacute;`, drops `[sound:…]` media references, and
keeps the answer out of `{{c1::…}}` cloze markers. Without that, markup would end
up in the reading view and terms would never match the words in your texts.

## Importing twice is safe

The importer only ever **creates**. It never modifies or deletes a term you
already have, so re-importing the same deck — or a bigger version of it later —
adds only what is genuinely new. Words already in LWT are reported as
"already in LWT" and left alone.

Duplicates *within* one file are collapsed case-insensitively, so forward and
reverse notes for the same word produce one term.

## Reading the summary

The summary accounts for every note read:

- **Terms created** — new terms now in your vocabulary
- **Already in LWT** — the word existed, so it was left untouched
- **Skipped (empty term field)** — the note had nothing in the chosen field
- **Skipped (too long to store)** — the term exceeded 250 characters

If *everything* was skipped as empty, you almost certainly picked the wrong term
field. Go back and choose another; nothing was written.

## Limits

- **Only `.apkg` files.** Anki's newer `.colpkg` whole-collection format and
  compressed exports are not supported yet.
- **Requires `pdo_sqlite`**, since an `.apkg` is a zipped SQLite database. See
  [Anki Export & Import](/reference/anki-export-import#requirements).
- **No sentences or images.** Only the term, its translation and its tags are
  imported.
- **Scheduling is read, not carried over.** Anki's intervals decide the starting
  status, but LWT then schedules the term with its own system — the two do not
  stay in sync. Live sync is tracked separately and is deliberately deferred.

## See also

- [Anki Export & Import](/reference/anki-export-import) — the LWT→Anki→LWT round trip
- [Term Scores](/reference/term-scores) — how LWT schedules reviews afterwards
