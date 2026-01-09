-- Migration: Add password reset token columns to users table
-- These columns support secure password reset functionality
-- Made idempotent for MariaDB compatibility
--
-- Token workflow:
-- 1. User requests password reset
-- 2. System generates random token, stores SHA-256 hash in UsPasswordResetToken
-- 3. System sets expiration time in UsPasswordResetTokenExpires (1 hour from creation)
-- 4. User receives plaintext token via email
-- 5. User submits token with new password
-- 6. System hashes submitted token, compares with stored hash
-- 7. If valid and not expired, password is updated and token cleared

ALTER TABLE users
ADD COLUMN IF NOT EXISTS UsPasswordResetToken varchar(64) DEFAULT NULL AFTER UsRememberTokenExpires,
ADD COLUMN IF NOT EXISTS UsPasswordResetTokenExpires datetime DEFAULT NULL AFTER UsPasswordResetToken;

ALTER TABLE users DROP INDEX IF EXISTS UsPasswordResetToken;
ALTER TABLE users ADD UNIQUE KEY UsPasswordResetToken (UsPasswordResetToken);
