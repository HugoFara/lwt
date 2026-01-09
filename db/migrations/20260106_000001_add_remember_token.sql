-- Migration: Add remember token columns to users table
-- These columns support persistent "remember me" functionality
-- Made idempotent for MariaDB compatibility

ALTER TABLE users
ADD COLUMN IF NOT EXISTS UsRememberToken varchar(64) DEFAULT NULL AFTER UsApiTokenExpires,
ADD COLUMN IF NOT EXISTS UsRememberTokenExpires datetime DEFAULT NULL AFTER UsRememberToken;

ALTER TABLE users DROP INDEX IF EXISTS UsRememberToken;
ALTER TABLE users ADD UNIQUE KEY UsRememberToken (UsRememberToken);
