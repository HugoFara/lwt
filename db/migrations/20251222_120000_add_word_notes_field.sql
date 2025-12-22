-- Add notes field to words table
-- This allows users to add personal notes separate from translations
-- Issue: https://github.com/HugoFara/lwt/issues/128

ALTER TABLE `words`
    ADD COLUMN `WoNotes` VARCHAR(1000) DEFAULT NULL AFTER `WoSentence`;
