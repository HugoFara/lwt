-- Migration: Rename tags2 to text_tags
-- The name "tags2" is a legacy artifact. "text_tags" better describes
-- what this table stores: tag definitions for texts (as opposed to "tags" which
-- stores tag definitions for vocabulary/words).
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys that reference tags2
-- ============================================================================

-- Drop FKs from texttags that reference tags2
ALTER TABLE texttags DROP FOREIGN KEY IF EXISTS fk_texttags_tag;

-- Drop FKs from archtexttags that reference tags2
ALTER TABLE archtexttags DROP FOREIGN KEY IF EXISTS fk_archtexttags_tag;

-- Drop FK on tags2 itself (user reference)
ALTER TABLE tags2 DROP FOREIGN KEY IF EXISTS fk_tags2_user;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

-- Only rename if old name exists and new name doesn't
SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'tags2'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'text_tags'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE tags2 TO text_tags',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with updated table name
-- ============================================================================

-- FK from texttags to text_tags (ON DELETE CASCADE)
ALTER TABLE texttags DROP FOREIGN KEY IF EXISTS fk_texttags_text_tag;
ALTER TABLE texttags
    ADD CONSTRAINT fk_texttags_text_tag
    FOREIGN KEY (TtT2ID) REFERENCES text_tags(T2ID) ON DELETE CASCADE;

-- FK from archtexttags to text_tags (ON DELETE CASCADE)
ALTER TABLE archtexttags DROP FOREIGN KEY IF EXISTS fk_archtexttags_text_tag;
ALTER TABLE archtexttags
    ADD CONSTRAINT fk_archtexttags_text_tag
    FOREIGN KEY (AgT2ID) REFERENCES text_tags(T2ID) ON DELETE CASCADE;

-- FK from text_tags to users (ON DELETE CASCADE) - if users table exists
ALTER TABLE text_tags DROP FOREIGN KEY IF EXISTS fk_text_tags_user;
SET @users_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'users'
);

SET @sql = IF(@users_exists > 0,
    'ALTER TABLE text_tags ADD CONSTRAINT fk_text_tags_user FOREIGN KEY (T2UsID) REFERENCES users(UsID) ON DELETE CASCADE',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
