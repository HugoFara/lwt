-- Migration: Add inter-table foreign key constraints
-- This establishes referential integrity for all inter-table relationships.
-- Critical change: textitems2.Ti2WoID uses NULL instead of 0 for unknown words.

-- ============================================================================
-- PART 0: Fix column type mismatches for FK compatibility
-- FK columns must match parent PK types exactly
-- All PK and FK columns are standardized to int(11) unsigned for consistency
-- ============================================================================

-- First, update parent PK columns to int(11) unsigned
ALTER TABLE languages MODIFY COLUMN LgID int(11) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE texts MODIFY COLUMN TxID int(11) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE sentences MODIFY COLUMN SeID int(11) unsigned NOT NULL AUTO_INCREMENT;

-- Fix all FK columns that reference languages.LgID
ALTER TABLE texts MODIFY COLUMN TxLgID int(11) unsigned NOT NULL;
ALTER TABLE words MODIFY COLUMN WoLgID int(11) unsigned NOT NULL;
ALTER TABLE archivedtexts MODIFY COLUMN AtLgID int(11) unsigned NOT NULL;
ALTER TABLE newsfeeds MODIFY COLUMN NfLgID int(11) unsigned NOT NULL;

-- Fix textitems2.Ti2TxID to match texts.TxID type
ALTER TABLE textitems2 MODIFY COLUMN Ti2TxID int(11) unsigned NOT NULL;

-- Fix textitems2.Ti2SeID to match sentences.SeID type
ALTER TABLE textitems2 MODIFY COLUMN Ti2SeID int(11) unsigned NOT NULL;

-- Fix textitems2.Ti2LgID to match languages.LgID type
ALTER TABLE textitems2 MODIFY COLUMN Ti2LgID int(11) unsigned NOT NULL;

-- Fix sentences.SeLgID to match languages.LgID type (if needed)
ALTER TABLE sentences MODIFY COLUMN SeLgID int(11) unsigned NOT NULL;

-- Fix sentences.SeTxID to match texts.TxID type (if needed)
ALTER TABLE sentences MODIFY COLUMN SeTxID int(11) unsigned NOT NULL;

-- Fix texttags.TtTxID to match texts.TxID type
ALTER TABLE texttags MODIFY COLUMN TtTxID int(11) unsigned NOT NULL;

-- Fix texttags.TtT2ID to match tags2.T2ID type
ALTER TABLE texttags MODIFY COLUMN TtT2ID int(11) unsigned NOT NULL;

-- Fix tags2.T2ID type for consistency
ALTER TABLE tags2 MODIFY COLUMN T2ID int(11) unsigned NOT NULL AUTO_INCREMENT;

-- Fix archtexttags columns
ALTER TABLE archtexttags MODIFY COLUMN AgAtID int(11) unsigned NOT NULL;
ALTER TABLE archtexttags MODIFY COLUMN AgT2ID int(11) unsigned NOT NULL;

-- Fix archivedtexts.AtID type
ALTER TABLE archivedtexts MODIFY COLUMN AtID int(11) unsigned NOT NULL AUTO_INCREMENT;

-- Fix wordtags columns to match parent types
ALTER TABLE wordtags MODIFY COLUMN WtWoID int(11) unsigned NOT NULL;
ALTER TABLE wordtags MODIFY COLUMN WtTgID int(11) unsigned NOT NULL;

-- Fix words.WoID type
ALTER TABLE words MODIFY COLUMN WoID int(11) unsigned NOT NULL AUTO_INCREMENT;

-- Fix tags.TgID type
ALTER TABLE tags MODIFY COLUMN TgID int(11) unsigned NOT NULL AUTO_INCREMENT;

-- Fix feedlinks.FlNfID to match newsfeeds.NfID type
ALTER TABLE newsfeeds MODIFY COLUMN NfID int(11) unsigned NOT NULL AUTO_INCREMENT;
ALTER TABLE feedlinks MODIFY COLUMN FlNfID int(11) unsigned NOT NULL;

-- ============================================================================
-- PART 1: Ti2WoID Column Change (0 -> NULL for unknown words)
-- ============================================================================

-- Step 1a: Make Ti2WoID nullable (was NOT NULL with implicit 0 default)
ALTER TABLE textitems2
    MODIFY COLUMN Ti2WoID int(11) unsigned DEFAULT NULL;

-- Step 1b: Convert existing 0 values to NULL
UPDATE textitems2 SET Ti2WoID = NULL WHERE Ti2WoID = 0;

-- ============================================================================
-- PART 2: Orphan Data Cleanup
-- Clean up any orphaned records that would violate FK constraints
-- ============================================================================

-- Clean up orphaned texts (referencing non-existent languages)
DELETE t FROM texts t
    LEFT JOIN languages l ON t.TxLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Clean up orphaned words (referencing non-existent languages)
DELETE w FROM words w
    LEFT JOIN languages l ON w.WoLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Clean up orphaned archivedtexts (referencing non-existent languages)
DELETE a FROM archivedtexts a
    LEFT JOIN languages l ON a.AtLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Clean up orphaned sentences (referencing non-existent languages)
DELETE s FROM sentences s
    LEFT JOIN languages l ON s.SeLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Clean up orphaned sentences (referencing non-existent texts)
DELETE s FROM sentences s
    LEFT JOIN texts t ON s.SeTxID = t.TxID
    WHERE t.TxID IS NULL;

-- Clean up orphaned textitems2 (referencing non-existent texts)
DELETE ti FROM textitems2 ti
    LEFT JOIN texts t ON ti.Ti2TxID = t.TxID
    WHERE t.TxID IS NULL;

-- Clean up orphaned textitems2 (referencing non-existent sentences)
DELETE ti FROM textitems2 ti
    LEFT JOIN sentences s ON ti.Ti2SeID = s.SeID
    WHERE s.SeID IS NULL;

-- Clean up textitems2 with invalid word references (set to NULL)
UPDATE textitems2 ti
    LEFT JOIN words w ON ti.Ti2WoID = w.WoID
    SET ti.Ti2WoID = NULL
    WHERE ti.Ti2WoID IS NOT NULL AND w.WoID IS NULL;

-- Clean up orphaned texttags (referencing non-existent texts)
DELETE tt FROM texttags tt
    LEFT JOIN texts t ON tt.TtTxID = t.TxID
    WHERE t.TxID IS NULL;

-- Clean up orphaned feedlinks (referencing non-existent newsfeeds)
DELETE fl FROM feedlinks fl
    LEFT JOIN newsfeeds nf ON fl.FlNfID = nf.NfID
    WHERE nf.NfID IS NULL;

-- Clean up orphaned newsfeeds (referencing non-existent languages)
DELETE nf FROM newsfeeds nf
    LEFT JOIN languages l ON nf.NfLgID = l.LgID
    WHERE l.LgID IS NULL;

-- Clean up orphaned archtexttags (referencing non-existent archived texts)
DELETE att FROM archtexttags att
    LEFT JOIN archivedtexts at ON att.AgAtID = at.AtID
    WHERE at.AtID IS NULL;

-- Clean up orphaned archtexttags (referencing non-existent tags2)
DELETE att FROM archtexttags att
    LEFT JOIN tags2 t2 ON att.AgT2ID = t2.T2ID
    WHERE t2.T2ID IS NULL;

-- Clean up orphaned texttags (referencing non-existent tags2)
DELETE tt FROM texttags tt
    LEFT JOIN tags2 t2 ON tt.TtT2ID = t2.T2ID
    WHERE t2.T2ID IS NULL;

-- Clean up orphaned wordtags (referencing non-existent words)
DELETE wt FROM wordtags wt
    LEFT JOIN words w ON wt.WtWoID = w.WoID
    WHERE w.WoID IS NULL;

-- Clean up orphaned wordtags (referencing non-existent tags)
DELETE wt FROM wordtags wt
    LEFT JOIN tags t ON wt.WtTgID = t.TgID
    WHERE t.TgID IS NULL;

-- ============================================================================
-- PART 3: Language Reference FKs (ON DELETE CASCADE)
-- When a language is deleted, all related content is deleted
-- ============================================================================

ALTER TABLE texts
    ADD CONSTRAINT fk_texts_language
    FOREIGN KEY (TxLgID) REFERENCES languages(LgID) ON DELETE CASCADE;

ALTER TABLE archivedtexts
    ADD CONSTRAINT fk_archivedtexts_language
    FOREIGN KEY (AtLgID) REFERENCES languages(LgID) ON DELETE CASCADE;

ALTER TABLE words
    ADD CONSTRAINT fk_words_language
    FOREIGN KEY (WoLgID) REFERENCES languages(LgID) ON DELETE CASCADE;

ALTER TABLE sentences
    ADD CONSTRAINT fk_sentences_language
    FOREIGN KEY (SeLgID) REFERENCES languages(LgID) ON DELETE CASCADE;

ALTER TABLE newsfeeds
    ADD CONSTRAINT fk_newsfeeds_language
    FOREIGN KEY (NfLgID) REFERENCES languages(LgID) ON DELETE CASCADE;

-- ============================================================================
-- PART 4: Text Reference FKs (ON DELETE CASCADE)
-- When a text is deleted, sentences/textitems/texttags are deleted
-- ============================================================================

ALTER TABLE sentences
    ADD CONSTRAINT fk_sentences_text
    FOREIGN KEY (SeTxID) REFERENCES texts(TxID) ON DELETE CASCADE;

ALTER TABLE textitems2
    ADD CONSTRAINT fk_textitems2_text
    FOREIGN KEY (Ti2TxID) REFERENCES texts(TxID) ON DELETE CASCADE;

ALTER TABLE texttags
    ADD CONSTRAINT fk_texttags_text
    FOREIGN KEY (TtTxID) REFERENCES texts(TxID) ON DELETE CASCADE;

-- ============================================================================
-- PART 5: Other Content FKs
-- ============================================================================

-- Sentence reference (ON DELETE CASCADE)
ALTER TABLE textitems2
    ADD CONSTRAINT fk_textitems2_sentence
    FOREIGN KEY (Ti2SeID) REFERENCES sentences(SeID) ON DELETE CASCADE;

-- Word reference (ON DELETE SET NULL)
-- When a word is deleted, text items become "unknown" again (Ti2WoID = NULL)
ALTER TABLE textitems2
    ADD CONSTRAINT fk_textitems2_word
    FOREIGN KEY (Ti2WoID) REFERENCES words(WoID) ON DELETE SET NULL;

-- Word tags (ON DELETE CASCADE)
ALTER TABLE wordtags
    ADD CONSTRAINT fk_wordtags_word
    FOREIGN KEY (WtWoID) REFERENCES words(WoID) ON DELETE CASCADE;

ALTER TABLE wordtags
    ADD CONSTRAINT fk_wordtags_tag
    FOREIGN KEY (WtTgID) REFERENCES tags(TgID) ON DELETE CASCADE;

-- Text tags (ON DELETE CASCADE)
ALTER TABLE texttags
    ADD CONSTRAINT fk_texttags_tag
    FOREIGN KEY (TtT2ID) REFERENCES tags2(T2ID) ON DELETE CASCADE;

-- Archived text tags (ON DELETE CASCADE)
ALTER TABLE archtexttags
    ADD CONSTRAINT fk_archtexttags_archivedtext
    FOREIGN KEY (AgAtID) REFERENCES archivedtexts(AtID) ON DELETE CASCADE;

ALTER TABLE archtexttags
    ADD CONSTRAINT fk_archtexttags_tag
    FOREIGN KEY (AgT2ID) REFERENCES tags2(T2ID) ON DELETE CASCADE;

-- Feed links (ON DELETE CASCADE)
ALTER TABLE feedlinks
    ADD CONSTRAINT fk_feedlinks_newsfeed
    FOREIGN KEY (FlNfID) REFERENCES newsfeeds(NfID) ON DELETE CASCADE;
