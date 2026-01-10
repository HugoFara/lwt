-- Migration: Rename archtexttags to archived_text_tag_map
-- The name "archtexttags" is unclear. "archived_text_tag_map" better describes
-- what this table stores: the mapping between archived texts and their tags.
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys on archtexttags
-- ============================================================================

ALTER TABLE archtexttags DROP FOREIGN KEY IF EXISTS fk_archtexttags_archivedtext;
ALTER TABLE archtexttags DROP FOREIGN KEY IF EXISTS fk_archtexttags_tag;
ALTER TABLE archtexttags DROP FOREIGN KEY IF EXISTS fk_archtexttags_text_tag;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'archtexttags'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'archived_text_tag_map'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE archtexttags TO archived_text_tag_map',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with new constraint names
-- ============================================================================

-- FK to archivedtexts table (ON DELETE CASCADE)
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_text;
ALTER TABLE archived_text_tag_map
    ADD CONSTRAINT fk_archived_text_tag_map_text
    FOREIGN KEY (AgAtID) REFERENCES archivedtexts(AtID) ON DELETE CASCADE;

-- FK to text_tags table (ON DELETE CASCADE)
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_tag;
ALTER TABLE archived_text_tag_map
    ADD CONSTRAINT fk_archived_text_tag_map_tag
    FOREIGN KEY (AgT2ID) REFERENCES text_tags(T2ID) ON DELETE CASCADE;
