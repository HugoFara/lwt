-- Migration: Add user_id columns to data tables for multi-user support
-- These columns link data to specific users
-- Made idempotent for MariaDB compatibility (using ADD INDEX IF NOT EXISTS)

-- Get the default admin user ID for existing data
SET @default_user_id = (SELECT UsID FROM users WHERE UsUsername = 'admin' LIMIT 1);

-- Languages table
ALTER TABLE languages
    ADD COLUMN IF NOT EXISTS LgUsID int(10) unsigned DEFAULT NULL AFTER LgID,
    ADD INDEX IF NOT EXISTS LgUsID (LgUsID);
UPDATE languages SET LgUsID = @default_user_id WHERE LgUsID IS NULL AND @default_user_id IS NOT NULL;

-- Texts table
ALTER TABLE texts
    ADD COLUMN IF NOT EXISTS TxUsID int(10) unsigned DEFAULT NULL AFTER TxID,
    ADD INDEX IF NOT EXISTS TxUsID (TxUsID);
UPDATE texts SET TxUsID = @default_user_id WHERE TxUsID IS NULL AND @default_user_id IS NOT NULL;

-- Archived texts table
ALTER TABLE archivedtexts
    ADD COLUMN IF NOT EXISTS AtUsID int(10) unsigned DEFAULT NULL AFTER AtID,
    ADD INDEX IF NOT EXISTS AtUsID (AtUsID);
UPDATE archivedtexts SET AtUsID = @default_user_id WHERE AtUsID IS NULL AND @default_user_id IS NOT NULL;

-- Words table
ALTER TABLE words
    ADD COLUMN IF NOT EXISTS WoUsID int(10) unsigned DEFAULT NULL AFTER WoID,
    ADD INDEX IF NOT EXISTS WoUsID (WoUsID);
UPDATE words SET WoUsID = @default_user_id WHERE WoUsID IS NULL AND @default_user_id IS NOT NULL;

-- Tags table (word tags)
ALTER TABLE tags
    ADD COLUMN IF NOT EXISTS TgUsID int(10) unsigned DEFAULT NULL AFTER TgID,
    ADD INDEX IF NOT EXISTS TgUsID (TgUsID);
UPDATE tags SET TgUsID = @default_user_id WHERE TgUsID IS NULL AND @default_user_id IS NOT NULL;

-- Tags2 table (text tags)
ALTER TABLE tags2
    ADD COLUMN IF NOT EXISTS T2UsID int(10) unsigned DEFAULT NULL AFTER T2ID,
    ADD INDEX IF NOT EXISTS T2UsID (T2UsID);
UPDATE tags2 SET T2UsID = @default_user_id WHERE T2UsID IS NULL AND @default_user_id IS NOT NULL;

-- Newsfeeds table
ALTER TABLE newsfeeds
    ADD COLUMN IF NOT EXISTS NfUsID int(10) unsigned DEFAULT NULL AFTER NfID,
    ADD INDEX IF NOT EXISTS NfUsID (NfUsID);
UPDATE newsfeeds SET NfUsID = @default_user_id WHERE NfUsID IS NULL AND @default_user_id IS NOT NULL;

-- Settings table - add user_id column but keep simple PK for now
-- The composite PK will be added when the auth system is complete
ALTER TABLE settings
    ADD COLUMN IF NOT EXISTS StUsID int(10) unsigned DEFAULT NULL,
    ADD INDEX IF NOT EXISTS StUsID (StUsID);
UPDATE settings SET StUsID = @default_user_id WHERE StUsID IS NULL AND @default_user_id IS NOT NULL;
