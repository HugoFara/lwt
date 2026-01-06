-- Migration: Add remember token columns to users table
-- These columns support persistent "remember me" functionality

ALTER TABLE users
ADD COLUMN UsRememberToken varchar(64) DEFAULT NULL AFTER UsApiTokenExpires,
ADD COLUMN UsRememberTokenExpires datetime DEFAULT NULL AFTER UsRememberToken,
ADD UNIQUE KEY UsRememberToken (UsRememberToken);
