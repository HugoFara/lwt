-- Add lemmatizer configuration to languages table
-- Allows configuring which lemmatizer to use per language:
-- - 'none': No lemmatization
-- - 'dictionary': Use dictionary-based lookup (default)
-- - 'spacy': Use spaCy NLP service
-- - 'hybrid': Dictionary first, spaCy fallback
-- Part of lemmatization support (Phase 5 - NLP Integration)

ALTER TABLE languages
    ADD COLUMN IF NOT EXISTS LgLemmatizerType VARCHAR(20) DEFAULT 'dictionary' AFTER LgParserType;

-- Note: Valid values are 'none', 'dictionary', 'spacy', 'hybrid'
-- The application code handles validation, no ENUM needed for flexibility
