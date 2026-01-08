-- Migration: Add Google OAuth ID column to users table
-- This column stores the unique Google user ID for OAuth authentication.
-- Google user IDs are numeric strings up to 21 digits.

ALTER TABLE users
ADD COLUMN UsGoogleId varchar(255) DEFAULT NULL AFTER UsWordPressId,
ADD UNIQUE KEY UsGoogleId (UsGoogleId);
