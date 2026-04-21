# Lemmatization

Lemmatization maps inflected word forms to their base form: `running` → `run`, `children` → `child`, `went` → `go`. LWT uses this to group word families so a status change on one form can propagate to related forms, and so vocabulary review can treat them as a single unit.

## How It Works

Each term has a `WoLemma` field (the base form) alongside its `WoText` (the surface form). Terms that share a lemma belong to the same word family. You can set a lemma three ways:

1. **Manually**, by typing it in the word edit form.
2. **Automatically**, via a lemmatizer configured per language.
3. **Via the API** (`POST /api/v1/terms` with `WoLemma`), for bulk imports.

## Lemmatization Strategies

LWT supports four strategies, configured per language:

| Strategy | Source | Coverage | Speed | When to use |
|----------|--------|----------|-------|-------------|
| `none` | — | Manual only | n/a | Languages with no useful inflection (Chinese, Japanese) or when you prefer to set lemmas by hand |
| `dictionary` | TSV file on disk | Whatever you ship | Fastest | Closed list of known forms, domain-specific vocabularies, or languages without a spaCy model |
| `spacy` | Pre-trained NLP models | 24 languages | Network round-trip | Languages with good spaCy support; best accuracy for novel forms |
| `hybrid` | Dictionary → spaCy fallback | Combined | Dictionary-fast, spaCy-accurate | **Recommended default** when both are available |

The `dictionary` strategy is the default for new languages (`LgLemmatizerType = 'dictionary'`).

## Languages Supported by spaCy

Out of the box, the NLP service can load spaCy models for:

`ca`, `da`, `de`, `el`, `en`, `es`, `fi`, `fr`, `hr`, `it`, `ja`, `ko`, `lt`, `mk`, `nb`, `nl`, `pl`, `pt`, `ro`, `ru`, `sl`, `sv`, `uk`, `zh`.

Models are loaded lazily and cached. The first request per language pays the load cost; subsequent requests are fast.

For any other language, use `dictionary` (ship a TSV) or leave at `none` and enter lemmas by hand.

## Configuring a Language

The active strategy is stored in the `languages.LgLemmatizerType` column (valid values: `none`, `dictionary`, `spacy`, `hybrid`).

At the moment there is **no dedicated UI for this setting**; you change it directly in the database:

```sql
UPDATE languages SET LgLemmatizerType = 'hybrid' WHERE LgID = 1;
```

This is tracked as a UI improvement. For most setups the default (`dictionary`) combined with the hybrid fallback chosen automatically at runtime is sufficient.

## Manual Lemma Override

Whatever the strategy, you can always override the lemma on an individual term:

1. Open the word edit form (pencil icon in the reader, or from the vocabulary list).
2. Fill the **Lemma** field.
3. Save.

Manual lemmas are never overwritten by automatic lemmatizers.

## Custom Dictionaries

To add a dictionary-based lemmatizer for a language, drop a TSV file into `data/lemma-dictionaries/`:

```
# data/lemma-dictionaries/en_lemmas.tsv
running	run
runs	run
ran	run
```

Filename convention: `{iso-639-1}_lemmas.tsv`. Lines starting with `#` are comments, empty lines are ignored. The file is detected automatically — no restart required.

See [`data/lemma-dictionaries/README.md`](https://github.com/HugoFara/lwt/blob/main/data/lemma-dictionaries/README.md) for sources (UniMorph, Wiktionary dumps, Lexique, FrequencyWords).

## Running the NLP Service

`spacy` and `hybrid` strategies require the NLP microservice (`services/nlp/`).

### With Docker (recommended)

The default `docker compose up` starts the `nlp` container alongside LWT. LWT talks to it at `http://nlp:8000` by default (overridable via the `NLP_SERVICE_URL` environment variable).

### Standalone

```bash
cd services/nlp
pip install -r requirements.txt
python -m spacy download en_core_web_sm   # repeat per language you need
uvicorn app.main:app --host 0.0.0.0 --port 8000
```

Then set `NLP_SERVICE_URL=http://localhost:8000` in LWT's `.env`.

### Checking availability

The NLP service exposes live Swagger docs at `GET /docs` and a language-availability endpoint at `GET /lemmatize/available`, which returns the list of installed spaCy models.

## API

LWT's REST API exposes lemma data on each term and provides word-family queries:

| Endpoint | Purpose |
|----------|---------|
| `GET /api/v1/terms/{id}` | Returns `WoLemma` and `WoLemmaLC` on the term |
| `GET /api/v1/word-families?language_id=X&lemma_lc=run` | All terms sharing a given lemma |
| `GET /api/v1/word-families?language_id=X` | Paginated list of word families for a language |
| `GET /api/v1/word-families/stats?language_id=X` | Lemma coverage statistics |
| `POST /api/v1/terms` / `PUT /api/v1/terms/{id}` | Accepts `WoLemma` in the payload |

See [API Reference](/developer/api) for full details.

## Troubleshooting

**Lemmas aren't being set automatically.** Check the language's `LgLemmatizerType` — the default is `dictionary`, which does nothing unless you've shipped a TSV for that language. Switch to `hybrid` (and make sure the NLP service is running) to get spaCy fallback.

**NLP service is unreachable.** From the LWT container, `curl http://nlp:8000/lemmatize/available` should succeed. If it doesn't, confirm the `nlp` service is running (`docker compose ps`) and that `NLP_SERVICE_URL` matches its hostname.

**A language isn't in the list above.** Either switch to `dictionary` and provide a TSV, or leave it at `none` and enter lemmas manually.

**A specific form is being lemmatized wrong.** Enter the correct lemma manually in the word edit form — it takes precedence over any lemmatizer.
