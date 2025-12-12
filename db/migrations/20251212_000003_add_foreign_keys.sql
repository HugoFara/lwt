-- Migration: Add foreign key constraints for user ownership
-- This establishes user ownership constraints for multi-user data isolation.
-- NOTE: Inter-table FK constraints (texts→languages, etc.) are deferred to avoid
-- breaking existing data manipulation code that uses temporary tables and bulk operations.

-- First, fix column type mismatches that may exist in upgraded databases
-- These ensure FK columns match their parent PK types

-- Fix AtLgID type (should match LgID: tinyint(3) unsigned)
ALTER TABLE archivedtexts MODIFY COLUMN AtLgID tinyint(3) unsigned NOT NULL;

-- Fix WtWoID type (should match WoID: mediumint(8) unsigned)
ALTER TABLE wordtags MODIFY COLUMN WtWoID mediumint(8) unsigned NOT NULL;

-- Clean up orphaned data that would violate FK constraints
-- Delete archived texts referencing non-existent languages
DELETE at FROM archivedtexts at
    LEFT JOIN languages l ON at.AtLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Delete archived text tags for non-existent archived texts
DELETE att FROM archtexttags att
    LEFT JOIN archivedtexts at ON att.AgAtID = at.AtID
    WHERE at.AtID IS NULL;

-- Delete texts referencing non-existent languages
DELETE t FROM texts t
    LEFT JOIN languages l ON t.TxLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Delete sentences for non-existent texts
DELETE s FROM sentences s
    LEFT JOIN texts t ON s.SeTxID = t.TxID
    WHERE t.TxID IS NULL;

-- Delete text items for non-existent texts
DELETE ti FROM textitems2 ti
    LEFT JOIN texts t ON ti.Ti2TxID = t.TxID
    WHERE t.TxID IS NULL;

-- Delete words referencing non-existent languages
DELETE w FROM words w
    LEFT JOIN languages l ON w.WoLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Delete word tags for non-existent words
DELETE wt FROM wordtags wt
    LEFT JOIN words w ON wt.WtWoID = w.WoID
    WHERE w.WoID IS NULL;

-- Delete word tags for non-existent tags
DELETE wt FROM wordtags wt
    LEFT JOIN tags t ON wt.WtTgID = t.TgID
    WHERE t.TgID IS NULL;

-- Delete text tags for non-existent texts
DELETE tt FROM texttags tt
    LEFT JOIN texts t ON tt.TtTxID = t.TxID
    WHERE t.TxID IS NULL;

-- Delete text tags for non-existent tags
DELETE tt FROM texttags tt
    LEFT JOIN tags2 t ON tt.TtT2ID = t.T2ID
    WHERE t.T2ID IS NULL;

-- Delete newsfeeds referencing non-existent languages
DELETE nf FROM newsfeeds nf
    LEFT JOIN languages l ON nf.NfLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Delete feed links for non-existent newsfeeds
DELETE fl FROM feedlinks fl
    LEFT JOIN newsfeeds nf ON fl.FlNfID = nf.NfID
    WHERE nf.NfID IS NULL;

-- Foreign keys to users table (user ownership)
-- These ensure data belongs to valid users and cascades deletion
ALTER TABLE languages
    ADD CONSTRAINT fk_languages_user
    FOREIGN KEY (LgUsID) REFERENCES users(UsID) ON DELETE CASCADE;

ALTER TABLE texts
    ADD CONSTRAINT fk_texts_user
    FOREIGN KEY (TxUsID) REFERENCES users(UsID) ON DELETE CASCADE;

ALTER TABLE archivedtexts
    ADD CONSTRAINT fk_archivedtexts_user
    FOREIGN KEY (AtUsID) REFERENCES users(UsID) ON DELETE CASCADE;

ALTER TABLE words
    ADD CONSTRAINT fk_words_user
    FOREIGN KEY (WoUsID) REFERENCES users(UsID) ON DELETE CASCADE;

ALTER TABLE tags
    ADD CONSTRAINT fk_tags_user
    FOREIGN KEY (TgUsID) REFERENCES users(UsID) ON DELETE CASCADE;

ALTER TABLE tags2
    ADD CONSTRAINT fk_tags2_user
    FOREIGN KEY (T2UsID) REFERENCES users(UsID) ON DELETE CASCADE;

ALTER TABLE newsfeeds
    ADD CONSTRAINT fk_newsfeeds_user
    FOREIGN KEY (NfUsID) REFERENCES users(UsID) ON DELETE CASCADE;

-- NOTE: Settings FK constraint is deferred - settings need to work without user context
-- during the transition period. The FK will be added when the auth system is complete.
-- ALTER TABLE settings
--     ADD CONSTRAINT fk_settings_user
--     FOREIGN KEY (StUsID) REFERENCES users(UsID) ON DELETE CASCADE;

-- NOTE: Inter-table foreign keys (texts→languages, words→languages, etc.) are NOT added here
-- to preserve compatibility with existing bulk operations and temporary table workflows.
-- These can be added in a future migration after refactoring the text parsing system.
