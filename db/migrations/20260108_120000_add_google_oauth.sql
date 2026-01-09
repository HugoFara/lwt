-- Migration: Add Google OAuth ID column to users table
-- This column stores the unique Google user ID for OAuth authentication.
-- Google user IDs are numeric strings up to 21 digits.
-- Made idempotent for MariaDB 10.5+ compatibility

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS UsGoogleId varchar(255) DEFAULT NULL AFTER UsWordPressId,
    ADD UNIQUE INDEX IF NOT EXISTS UsGoogleId (UsGoogleId);
