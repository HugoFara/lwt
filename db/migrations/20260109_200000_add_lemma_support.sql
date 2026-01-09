-- Add lemma columns to words table for grouping inflected word forms
-- This allows associating "runs", "running", "ran" with the lemma "run"
-- Part of lemmatization support (Phase 1)
-- Made idempotent for MariaDB compatibility

ALTER TABLE words
    ADD COLUMN IF NOT EXISTS WoLemma VARCHAR(250) DEFAULT NULL AFTER WoTextLC,
    ADD COLUMN IF NOT EXISTS WoLemmaLC VARCHAR(250) DEFAULT NULL AFTER WoLemma;

-- Add index for efficient lemma lookups (word family queries)
CREATE INDEX IF NOT EXISTS idx_words_lemma ON words (WoLemmaLC, WoLgID);
