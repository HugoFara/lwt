-- Add Microsoft OAuth support
-- Adds UsMicrosoftId column to users table for Microsoft account linking
-- Made idempotent for MariaDB 10.5+ compatibility

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS UsMicrosoftId varchar(255) DEFAULT NULL AFTER UsGoogleId,
    ADD UNIQUE INDEX IF NOT EXISTS UsMicrosoftId (UsMicrosoftId);
