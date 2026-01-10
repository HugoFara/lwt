-- Migration: Rename wordtags to word_tag_map
-- Consistency with text_tag_map and archived_text_tag_map naming convention.
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys on wordtags
-- ============================================================================

ALTER TABLE wordtags DROP FOREIGN KEY IF EXISTS fk_wordtags_word;
ALTER TABLE wordtags DROP FOREIGN KEY IF EXISTS fk_wordtags_tag;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'wordtags'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'word_tag_map'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE wordtags TO word_tag_map',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with new constraint names
-- ============================================================================

-- FK to words table (ON DELETE CASCADE)
ALTER TABLE word_tag_map DROP FOREIGN KEY IF EXISTS fk_word_tag_map_word;
ALTER TABLE word_tag_map
    ADD CONSTRAINT fk_word_tag_map_word
    FOREIGN KEY (WtWoID) REFERENCES words(WoID) ON DELETE CASCADE;

-- FK to tags table (ON DELETE CASCADE)
ALTER TABLE word_tag_map DROP FOREIGN KEY IF EXISTS fk_word_tag_map_tag;
ALTER TABLE word_tag_map
    ADD CONSTRAINT fk_word_tag_map_tag
    FOREIGN KEY (WtTgID) REFERENCES tags(TgID) ON DELETE CASCADE;
