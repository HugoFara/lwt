-- Migration: Add remember token columns to users table
-- These columns support persistent "remember me" functionality
-- Made idempotent for MariaDB 10.5+ compatibility

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS UsRememberToken varchar(64) DEFAULT NULL AFTER UsApiTokenExpires,
    ADD COLUMN IF NOT EXISTS UsRememberTokenExpires datetime DEFAULT NULL AFTER UsRememberToken,
    ADD UNIQUE INDEX IF NOT EXISTS UsRememberToken (UsRememberToken);
