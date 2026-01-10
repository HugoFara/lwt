-- Add Piper TTS voice ID column to languages table
-- This column stores the selected Piper voice for text-to-speech

ALTER TABLE languages
ADD COLUMN IF NOT EXISTS LgPiperVoiceId VARCHAR(100) DEFAULT NULL
COMMENT 'Piper TTS voice ID for this language (e.g., en_US-lessac-medium)';
