-- Migration: Add users table for multi-user support
-- This table stores user accounts for authentication

CREATE TABLE IF NOT EXISTS users (
    UsID int(10) unsigned NOT NULL AUTO_INCREMENT,
    UsUsername varchar(100) NOT NULL,
    UsEmail varchar(255) NOT NULL,
    UsPasswordHash varchar(255) DEFAULT NULL,
    UsApiToken varchar(64) DEFAULT NULL,
    UsApiTokenExpires datetime DEFAULT NULL,
    UsWordPressId int(10) unsigned DEFAULT NULL,
    UsCreated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UsLastLogin timestamp NULL DEFAULT NULL,
    UsIsActive tinyint(1) unsigned NOT NULL DEFAULT 1,
    UsRole enum('user','admin') NOT NULL DEFAULT 'user',
    PRIMARY KEY (UsID),
    UNIQUE KEY UsUsername (UsUsername),
    UNIQUE KEY UsEmail (UsEmail),
    UNIQUE KEY UsApiToken (UsApiToken),
    KEY UsWordPressId (UsWordPressId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert a migration-only admin user for existing installations upgrading to multi-user mode.
-- SECURITY NOTE: This account has NO PASSWORD (NULL hash) and CANNOT log in.
-- The Login use case rejects users with null password hashes.
-- Users must register a new account with proper credentials.
-- For fresh installations using baseline.sql, no default admin is created.
-- This INSERT exists only to provide a user_id=1 owner for migrated data.
INSERT IGNORE INTO users (UsUsername, UsEmail, UsRole)
VALUES ('admin', 'admin@localhost', 'admin');
