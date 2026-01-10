-- Migration: Rename archivedtexts to archived_texts
-- Snake_case consistency with other table names.
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys referencing archivedtexts
-- ============================================================================

ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_text;
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_archivedtext;
ALTER TABLE archivedtexts DROP FOREIGN KEY IF EXISTS fk_archivedtexts_language;
ALTER TABLE archivedtexts DROP FOREIGN KEY IF EXISTS fk_archivedtexts_user;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'archivedtexts'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'archived_texts'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE archivedtexts TO archived_texts',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with new constraint names
-- ============================================================================

-- FK from archived_texts to languages
ALTER TABLE archived_texts DROP FOREIGN KEY IF EXISTS fk_archived_texts_language;
ALTER TABLE archived_texts
    ADD CONSTRAINT fk_archived_texts_language
    FOREIGN KEY (AtLgID) REFERENCES languages(LgID) ON DELETE CASCADE;

-- FK from archived_text_tag_map to archived_texts
ALTER TABLE archived_text_tag_map DROP FOREIGN KEY IF EXISTS fk_archived_text_tag_map_archived_text;
ALTER TABLE archived_text_tag_map
    ADD CONSTRAINT fk_archived_text_tag_map_archived_text
    FOREIGN KEY (AgAtID) REFERENCES archived_texts(AtID) ON DELETE CASCADE;
