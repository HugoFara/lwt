-- Add Microsoft OAuth support
-- Adds UsMicrosoftId column to users table for Microsoft account linking
-- Made idempotent for MariaDB compatibility

ALTER TABLE users
ADD COLUMN IF NOT EXISTS UsMicrosoftId varchar(255) DEFAULT NULL AFTER UsGoogleId;

ALTER TABLE users DROP INDEX IF EXISTS UsMicrosoftId;
ALTER TABLE users ADD UNIQUE KEY UsMicrosoftId (UsMicrosoftId);
