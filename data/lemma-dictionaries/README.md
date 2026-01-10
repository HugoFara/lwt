# Lemma Dictionaries

This directory contains lemma dictionary files for automatic lemmatization.

## File Format

Dictionary files use TSV (tab-separated values) format:
- Filename: `{language_code}_lemmas.tsv` (e.g., `en_lemmas.tsv`, `de_lemmas.tsv`)
- Columns: `word_form\tlemma`
- Lines starting with `#` are comments
- Empty lines are ignored

Example:
```
# English verbs
running	run
runs	run
ran	run
```

## Supported Languages

Add a TSV file for any language using its ISO 639-1 code:
- `en` - English
- `de` - German
- `fr` - French
- `es` - Spanish
- etc.

## Sources for Lemma Data

You can download or create lemma dictionaries from these sources:

1. **UniMorph** (https://unimorph.github.io/)
   - Morphological dictionaries for 150+ languages
   - Free, academic resource

2. **Wiktionary Dumps** (https://dumps.wikimedia.org/)
   - Extract word forms and lemmas from Wiktionary

3. **FrequencyWords** (https://github.com/hermitdave/FrequencyWords)
   - Word frequency lists for 40+ languages

4. **Lexique** (http://www.lexique.org/)
   - French lexical database with 140k lemmas

## Adding a New Dictionary

1. Create a TSV file: `{lang_code}_lemmas.tsv`
2. Add word forms and their base forms (lemmas)
3. The lemmatizer will automatically detect and load it

## Note

The sample `en_lemmas.tsv` included here is a minimal dictionary for testing.
For production use, download a complete dictionary from the sources above.
