-- Add notes field to words table
-- This allows users to add personal notes separate from translations
-- Issue: https://github.com/HugoFara/lwt/issues/128
-- Made idempotent for MariaDB compatibility

ALTER TABLE words
    ADD COLUMN IF NOT EXISTS WoNotes VARCHAR(1000) DEFAULT NULL AFTER WoSentence;
