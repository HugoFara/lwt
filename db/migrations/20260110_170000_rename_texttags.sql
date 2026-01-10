-- Migration: Rename texttags to text_tag_map
-- The name "texttags" is unclear. "text_tag_map" better describes
-- what this table stores: the mapping between texts and their tags.
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys on texttags
-- ============================================================================

ALTER TABLE texttags DROP FOREIGN KEY IF EXISTS fk_texttags_text;
ALTER TABLE texttags DROP FOREIGN KEY IF EXISTS fk_texttags_tag;
ALTER TABLE texttags DROP FOREIGN KEY IF EXISTS fk_texttags_text_tag;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'texttags'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'text_tag_map'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE texttags TO text_tag_map',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with new constraint names
-- ============================================================================

-- FK to texts table (ON DELETE CASCADE)
ALTER TABLE text_tag_map DROP FOREIGN KEY IF EXISTS fk_text_tag_map_text;
ALTER TABLE text_tag_map
    ADD CONSTRAINT fk_text_tag_map_text
    FOREIGN KEY (TtTxID) REFERENCES texts(TxID) ON DELETE CASCADE;

-- FK to text_tags table (ON DELETE CASCADE)
ALTER TABLE text_tag_map DROP FOREIGN KEY IF EXISTS fk_text_tag_map_tag;
ALTER TABLE text_tag_map
    ADD CONSTRAINT fk_text_tag_map_tag
    FOREIGN KEY (TtT2ID) REFERENCES text_tags(T2ID) ON DELETE CASCADE;
