-- Migration: Add user_id columns to data tables for multi-user support
-- These columns link data to specific users

-- Get the default admin user ID for existing data
SET @default_user_id = (SELECT UsID FROM users WHERE UsUsername = 'admin' LIMIT 1);

-- Languages table
ALTER TABLE languages
    ADD COLUMN LgUsID int(10) unsigned DEFAULT NULL AFTER LgID,
    ADD KEY LgUsID (LgUsID);
UPDATE languages SET LgUsID = @default_user_id WHERE LgUsID IS NULL;

-- Texts table
ALTER TABLE texts
    ADD COLUMN TxUsID int(10) unsigned DEFAULT NULL AFTER TxID,
    ADD KEY TxUsID (TxUsID);
UPDATE texts SET TxUsID = @default_user_id WHERE TxUsID IS NULL;

-- Archived texts table
ALTER TABLE archivedtexts
    ADD COLUMN AtUsID int(10) unsigned DEFAULT NULL AFTER AtID,
    ADD KEY AtUsID (AtUsID);
UPDATE archivedtexts SET AtUsID = @default_user_id WHERE AtUsID IS NULL;

-- Words table
ALTER TABLE words
    ADD COLUMN WoUsID int(10) unsigned DEFAULT NULL AFTER WoID,
    ADD KEY WoUsID (WoUsID);
UPDATE words SET WoUsID = @default_user_id WHERE WoUsID IS NULL;

-- Tags table (word tags)
ALTER TABLE tags
    ADD COLUMN TgUsID int(10) unsigned DEFAULT NULL AFTER TgID,
    ADD KEY TgUsID (TgUsID);
UPDATE tags SET TgUsID = @default_user_id WHERE TgUsID IS NULL;

-- Tags2 table (text tags)
ALTER TABLE tags2
    ADD COLUMN T2UsID int(10) unsigned DEFAULT NULL AFTER T2ID,
    ADD KEY T2UsID (T2UsID);
UPDATE tags2 SET T2UsID = @default_user_id WHERE T2UsID IS NULL;

-- Newsfeeds table
ALTER TABLE newsfeeds
    ADD COLUMN NfUsID int(10) unsigned DEFAULT NULL AFTER NfID,
    ADD KEY NfUsID (NfUsID);
UPDATE newsfeeds SET NfUsID = @default_user_id WHERE NfUsID IS NULL;

-- Settings table - add user_id column but keep simple PK for now
-- The composite PK will be added when the auth system is complete
ALTER TABLE settings
    ADD COLUMN StUsID int(10) unsigned DEFAULT NULL,
    ADD KEY StUsID (StUsID);
UPDATE settings SET StUsID = @default_user_id WHERE StUsID IS NULL;
