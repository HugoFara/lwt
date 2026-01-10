# Lemmatization Support Proposal

## Overview

Add lemmatization support to group inflected word forms under their base form (lemma), so that learning "runs", "running", "ran" automatically associates them with the lemma "run".

## Problem Statement

Current LWT behavior:

- Words are matched by **exact lowercase text**: `LOWER(Ti2Text) = WoTextLC`
- Each inflected form is a separate vocabulary entry
- User must manually create entries for "run", "runs", "running", "ran"
- No automatic association between related word forms
- Statistics don't reflect true vocabulary size (inflections inflate word count)

This is particularly painful for:

- **Morphologically rich languages**: Finnish (15+ cases), Russian (6 cases × 3 genders), German (4 cases)
- **Verb conjugations**: Spanish/French/Italian have 50+ verb forms per verb
- **Agglutinative languages**: Turkish, Hungarian, Japanese

## Proposed Solution

### Design Philosophy

Two complementary features:

1. **Lemma field**: Store canonical form for each word
2. **Word families**: Group related words visually and statistically

### Database Schema Changes

```sql
-- Option A: Lemma column on words table (simpler)
ALTER TABLE words
    ADD COLUMN WoLemma VARCHAR(250) NULL AFTER WoTextLC,
    ADD COLUMN WoLemmaLC VARCHAR(250) NULL AFTER WoLemma,
    ADD INDEX idx_words_lemma (WoLemmaLC, WoLgID);

-- Option B: Separate lemmas table (more normalized, supports lemma metadata)
CREATE TABLE lemmas (
    LeID MEDIUMINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    LeUsID INT UNSIGNED NULL,
    LeLgID TINYINT UNSIGNED NOT NULL,
    LeText VARCHAR(250) NOT NULL,              -- Display form
    LeTextLC VARCHAR(250) NOT NULL,            -- Lowercase for matching
    LePartOfSpeech ENUM('noun', 'verb', 'adj', 'adv', 'other') NULL,
    LeFrequencyRank INT UNSIGNED NULL,         -- From frequency lists
    LeCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY lemma_lang (LeTextLC, LeLgID),
    FOREIGN KEY (LeLgID) REFERENCES languages(LgID) ON DELETE RESTRICT,
    INDEX idx_lemmas_user (LeUsID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE words
    ADD COLUMN WoLeID MEDIUMINT UNSIGNED NULL AFTER WoLgID,
    ADD FOREIGN KEY (WoLeID) REFERENCES lemmas(LeID) ON DELETE SET NULL;
```

**Recommendation**: Start with Option A (simpler), migrate to Option B if lemma metadata becomes valuable.

### Lemmatization Approaches

#### Approach 1: Dictionary-Based Lookup

Pre-built lemma dictionaries for each language.

```
Source: Wiktionary dumps, FrequencyWords, UniMorph
Format: TSV file per language

running    run     verb
runs       run     verb
ran        ran     verb
runner     runner  noun
```

**Pros**: Fast, predictable, no external dependencies
**Cons**: Incomplete coverage, needs maintenance per language

#### Approach 2: Rule-Based Stemming

Apply language-specific suffix rules.

```php
// Porter Stemmer example (English)
"running" → remove "ning" → "run"
"flies"   → remove "ies" + add "y" → "fly"
```

Libraries:

- **wamania/php-stemmer**: PHP, supports 15 languages
- **Snowball stemmers**: Industry standard algorithms

**Pros**: No dictionary needed, handles unknown words
**Cons**: Overstemming (different words → same stem), not true lemmas

#### Approach 3: NLP Model-Based

Use trained NLP models for morphological analysis.

Tools:

- **spaCy** (Python): Best accuracy, 25+ languages
- **Stanza** (Python): Stanford NLP, 66 languages
- **TreeTagger** (C): Lightweight, 25 languages
- **MeCab** (C++): Already integrated for Japanese

**Pros**: Highest accuracy, handles context
**Cons**: External process, slower, larger memory footprint

#### Approach 4: Hybrid

1. Check dictionary first (fast path)
2. Fall back to rule-based stemming
3. Optionally call NLP model for ambiguous cases

**Recommendation**: Start with dictionary + rules, add NLP integration later.

### Implementation Phases

#### Phase 1: Schema and Basic Support

1. Add `WoLemma` / `WoLemmaLC` columns to `words` table
2. Update `Term` entity with lemma property
3. Modify word creation to accept optional lemma
4. UI: Add lemma field to word edit form
5. API: Include lemma in term endpoints

```php
// Term entity changes
class Term {
    private ?string $lemma = null;
    private ?string $lemmaLc = null;

    public function lemma(): ?string {
        return $this->lemma;
    }

    public function withLemma(string $lemma): self {
        $clone = clone $this;
        $clone->lemma = $lemma;
        $clone->lemmaLc = mb_strtolower($lemma);
        return $clone;
    }
}
```

#### Phase 2: Dictionary Integration

1. Create `LemmaService` with dictionary loading
2. Import lemma dictionaries for major languages
3. Auto-suggest lemma when creating new words
4. Batch tool: "Apply lemmas to existing vocabulary"

```php
class LemmaService {
    private array $dictionaries = [];

    public function loadDictionary(int $languageId, string $path): void {
        // Load TSV: word_form → lemma
    }

    public function findLemma(string $wordForm, int $languageId): ?string {
        return $this->dictionaries[$languageId][$wordForm] ?? null;
    }

    public function suggestLemma(string $wordForm, int $languageId): ?string {
        // 1. Dictionary lookup
        // 2. Rule-based fallback
        // 3. Return null if unknown
    }
}
```

Dictionary sources:

- [Lexique](http://www.lexique.org/) - French (140k lemmas)
- [CELEX](https://catalog.ldc.upenn.edu/LDC96L14) - English, German, Dutch
- [FrequencyWords](https://github.com/hermitdave/FrequencyWords) - 40+ languages
- [UniMorph](https://unimorph.github.io/) - 150+ languages, morphological data

#### Phase 3: Word Family Grouping

1. Add "Word Family" view showing all forms of a lemma
2. Aggregate statistics by lemma
3. Learning status inheritance options:
   - Independent: each form has own status
   - Linked: marking one form affects all
   - Suggested: prompt user to update related forms

```sql
-- Query: All words sharing a lemma
SELECT WoID, WoText, WoStatus, WoTranslation
FROM words
WHERE WoLemmaLC = 'run' AND WoLgID = 1
ORDER BY WoWordCount, WoText;
```

UI mockup:

```
┌─────────────────────────────────────────────────┐
│ Word Family: run (verb)                         │
├─────────────────────────────────────────────────┤
│ Form        │ Status │ Last Seen │ Translation  │
│─────────────┼────────┼───────────┼──────────────│
│ run         │ ★★★★☆  │ Today     │ courir       │
│ runs        │ ★★★☆☆  │ Yesterday │ (inherited)  │
│ running     │ ★★★★★  │ 3 days    │ (inherited)  │
│ ran         │ ★★☆☆☆  │ 1 week    │ (inherited)  │
├─────────────────────────────────────────────────┤
│ [Mark all as Known] [Edit Family]               │
└─────────────────────────────────────────────────┘
```

#### Phase 4: Smart Matching

Change text-to-word linking to optionally match by lemma:

```php
// Current: exact match only
$sql = "UPDATE textitems2 ti
        JOIN words w ON LOWER(ti.Ti2Text) = w.WoTextLC
                    AND ti.Ti2LgID = w.WoLgID
        SET ti.Ti2WoID = w.WoID";

// New: lemma-aware matching (when word form not found)
// 1. Try exact match first
// 2. Lemmatize text item
// 3. Find word with matching lemma
// 4. Create new word entry for this form, linked to lemma
```

This enables: Read "runs" → System knows lemma "run" → Shows status/translation from "run" entry.

#### Phase 5: NLP Integration (Optional)

Add external lemmatizer support for better accuracy:

```php
interface LemmatizerInterface {
    public function lemmatize(string $text, string $languageCode): string;
    public function lemmatizeBatch(array $texts, string $languageCode): array;
}

class SpacyLemmatizer implements LemmatizerInterface {
    public function lemmatize(string $text, string $languageCode): string {
        // Call Python spaCy via shell or HTTP microservice
        $result = shell_exec("python3 lemmatize.py --lang={$languageCode} --text=" . escapeshellarg($text));
        return trim($result);
    }
}

class TreeTaggerLemmatizer implements LemmatizerInterface {
    // TreeTagger integration
}
```

Configuration per language:

```php
// languages table or config
$lemmatizers = [
    'en' => 'dictionary',      // English: dictionary sufficient
    'de' => 'treetagger',      // German: needs compound handling
    'ja' => 'mecab',           // Japanese: already integrated
    'fi' => 'spacy',           // Finnish: complex morphology
];
```

### API Changes

```
GET  /api/v1/terms?lemma=run              Filter by lemma
GET  /api/v1/terms/{id}/family            Get all words in family
POST /api/v1/terms                        Add lemma field
PUT  /api/v1/terms/{id}                   Update lemma field
POST /api/v1/terms/bulk-lemmatize         Apply lemmas to selection
GET  /api/v1/languages/{id}/lemma-stats   Lemma coverage statistics
```

### Statistics Changes

Current stats count every word form separately. With lemmatization:

| Metric | Current | With Lemmatization |
|--------|---------|-------------------|
| "Known words" | 500 | 320 lemmas (500 forms) |
| "Words in text" | 1000 | 650 unique lemmas |
| "Coverage" | 85% | 92% (lemma-based) |

Add toggle: "Show statistics by: [Word Forms] [Lemmas]"

### Language Configuration

Add to `languages` table:

```sql
ALTER TABLE languages
    ADD COLUMN LgLemmatizerType ENUM('none', 'dictionary', 'rules', 'spacy', 'treetagger', 'mecab')
        DEFAULT 'none' AFTER LgParserType,
    ADD COLUMN LgLemmaDictPath VARCHAR(500) NULL AFTER LgLemmatizerType;
```

### File Structure

```
src/Modules/Vocabulary/
├── Application/
│   └── Services/
│       ├── LemmaService.php           # Lemma lookup and suggestions
│       └── LemmatizerFactory.php      # Creates language-specific lemmatizer
├── Domain/
│   ├── LemmatizerInterface.php        # Contract for lemmatizers
│   └── Term.php                       # Add lemma properties
└── Infrastructure/
    ├── Lemmatizers/
    │   ├── DictionaryLemmatizer.php
    │   ├── RuleBasedLemmatizer.php
    │   ├── SpacyLemmatizer.php
    │   └── TreeTaggerLemmatizer.php
    └── Dictionaries/
        └── .gitkeep                   # User downloads dictionaries here

data/lemma-dictionaries/               # Or separate download
├── en_lemmas.tsv
├── de_lemmas.tsv
├── fr_lemmas.tsv
└── ...
```

### Migration Path

```sql
-- db/migrations/YYYYMMDD_HHMMSS_add_lemma_support.sql

ALTER TABLE words
    ADD COLUMN WoLemma VARCHAR(250) NULL,
    ADD COLUMN WoLemmaLC VARCHAR(250) NULL,
    ADD INDEX idx_words_lemma (WoLemmaLC, WoLgID);

ALTER TABLE languages
    ADD COLUMN LgLemmatizerType ENUM('none', 'dictionary', 'rules', 'spacy', 'treetagger', 'mecab')
        DEFAULT 'none',
    ADD COLUMN LgLemmaDictPath VARCHAR(500) NULL;

-- Backfill: Set lemma = text for single words (conservative default)
UPDATE words SET WoLemma = WoText, WoLemmaLC = WoTextLC WHERE WoWordCount = 1;
```

### Dependencies

```json
// composer.json
{
    "require": {
        "wamania/php-stemmer": "^3.0"
    }
}
```

Optional system dependencies:

- **TreeTagger**: Download from [IMS Stuttgart](https://www.cis.uni-muenchen.de/~schmid/tools/TreeTagger/)
- **spaCy**: `pip install spacy` + language models
- **MeCab**: Already supported for Japanese

### Performance Considerations

1. **Dictionary loading**: Cache in memory (APCu) or load on demand
2. **Batch lemmatization**: Process texts in bulk during import
3. **Indexing**: `idx_words_lemma (WoLemmaLC, WoLgID)` for fast family queries
4. **NLP calls**: Queue and batch external lemmatizer calls

### Open Questions

1. **Homographs**: "run" (verb) vs "run" (noun) - same lemma or different?
2. **Compound words**: German "Hausfrau" - lemmatize to "Haus" + "Frau"?
3. **Multi-word expressions**: Lemmatize "look up" → "look up" or component parts?
4. **User corrections**: Allow users to override auto-lemmatization?
5. **Status inheritance**: When marking "runs" as known, auto-update "run"?

### Success Metrics

- Vocabulary list size reduced by 30-50% (forms → lemmas)
- Text coverage percentage increases (recognize more word forms)
- User reports fewer "I already know this word in another form" moments
- Review sessions more efficient (test lemma, not every form)

## References

- [UniMorph Project](https://unimorph.github.io/) - Morphological dictionaries
- [spaCy Lemmatizer](https://spacy.io/api/lemmatizer) - NLP-based lemmatization
- [Snowball Stemmers](https://snowballstem.org/) - Rule-based stemming algorithms
- [TreeTagger](https://www.cis.uni-muenchen.de/~schmid/tools/TreeTagger/) - Part-of-speech tagger with lemmatization
- Current word handling: `src/Modules/Vocabulary/Application/Services/WordService.php`
