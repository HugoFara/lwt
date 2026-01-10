-- Migration: Rename temporary tables
-- - temptextitems -> temp_word_occurrences (matches word_occurrences)
-- - tempwords -> temp_words (snake_case consistency)
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Rename temptextitems to temp_word_occurrences
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'temptextitems'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'temp_word_occurrences'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE temptextitems TO temp_word_occurrences',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 2: Rename tempwords to temp_words
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'tempwords'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'temp_words'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE tempwords TO temp_words',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
