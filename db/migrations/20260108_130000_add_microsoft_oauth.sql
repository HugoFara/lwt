-- Add Microsoft OAuth support
-- Adds UsMicrosoftId column to users table for Microsoft account linking

ALTER TABLE users
ADD COLUMN UsMicrosoftId varchar(255) DEFAULT NULL AFTER UsGoogleId,
ADD UNIQUE KEY UsMicrosoftId (UsMicrosoftId);
