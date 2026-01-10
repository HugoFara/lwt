-- Migration: Merge archived_texts into texts table
-- Instead of separate tables, use a TxArchivedAt column to mark archived texts.
-- This is a standard soft-delete/archive pattern that simplifies the schema.

-- ============================================================================
-- PART 1: Add TxArchivedAt column to texts table
-- ============================================================================

ALTER TABLE texts ADD COLUMN IF NOT EXISTS TxArchivedAt DATETIME DEFAULT NULL;
ALTER TABLE texts ADD INDEX IF NOT EXISTS TxArchivedAt (TxArchivedAt);

-- ============================================================================
-- PART 2: Migrate data from archived_texts to texts
-- ============================================================================

-- Check if archived_texts table exists before migrating
SET @archived_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'archived_texts'
);

-- Only proceed if archived_texts exists and has data
SET @sql = IF(@archived_exists > 0,
    'INSERT INTO texts (TxUsID, TxLgID, TxTitle, TxText, TxAnnotatedText, TxAudioURI, TxSourceURI, TxPosition, TxAudioPosition, TxArchivedAt)
     SELECT AtUsID, AtLgID, AtTitle, AtText, AtAnnotatedText, AtAudioURI, AtSourceURI, 0, 0, NOW()
     FROM archived_texts',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Migrate archived_text_tag_map to text_tag_map
-- We need to map old AtID to new TxID based on matching titles within same language/user
-- ============================================================================

SET @tag_map_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'archived_text_tag_map'
);

-- Create a temporary mapping table for AtID -> TxID
SET @sql = IF(@archived_exists > 0 AND @tag_map_exists > 0,
    'INSERT IGNORE INTO text_tag_map (TtTxID, TtT2ID)
     SELECT t.TxID, atm.AgT2ID
     FROM archived_text_tag_map atm
     INNER JOIN archived_texts at ON at.AtID = atm.AgAtID
     INNER JOIN texts t ON t.TxArchivedAt IS NOT NULL
         AND t.TxTitle = at.AtTitle
         AND t.TxLgID = at.AtLgID
         AND COALESCE(t.TxUsID, 0) = COALESCE(at.AtUsID, 0)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 4: Drop foreign keys and old tables
-- ============================================================================

-- Drop FK constraints on archived_text_tag_map
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_archived_text;
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_text;
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_tag;
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_text_tag;

-- Drop FK constraints on archived_texts
ALTER TABLE archived_texts DROP FOREIGN KEY IF EXISTS fk_archived_texts_language;
ALTER TABLE archived_texts DROP FOREIGN KEY IF EXISTS fk_archived_texts_user;

-- Drop the tables
DROP TABLE IF EXISTS archived_text_tag_map;
DROP TABLE IF EXISTS archived_texts;
