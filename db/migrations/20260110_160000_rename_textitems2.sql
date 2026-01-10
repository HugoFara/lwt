-- Migration: Rename textitems2 to word_occurrences
-- The name "textitems2" is a legacy artifact. "word_occurrences" better describes
-- what this table stores: the locations where words appear in texts.
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys that reference textitems2
-- ============================================================================

-- Drop FKs on textitems2 that reference other tables
ALTER TABLE textitems2 DROP FOREIGN KEY IF EXISTS fk_textitems2_text;
ALTER TABLE textitems2 DROP FOREIGN KEY IF EXISTS fk_textitems2_sentence;
ALTER TABLE textitems2 DROP FOREIGN KEY IF EXISTS fk_textitems2_word;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

-- Only rename if old name exists and new name doesn't
-- MySQL/MariaDB will error if source doesn't exist, so we check first
SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'textitems2'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'word_occurrences'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE textitems2 TO word_occurrences',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with new constraint names
-- ============================================================================

-- FK to texts table (ON DELETE CASCADE)
ALTER TABLE word_occurrences DROP FOREIGN KEY IF EXISTS fk_word_occurrences_text;
ALTER TABLE word_occurrences
    ADD CONSTRAINT fk_word_occurrences_text
    FOREIGN KEY (Ti2TxID) REFERENCES texts(TxID) ON DELETE CASCADE;

-- FK to sentences table (ON DELETE CASCADE)
ALTER TABLE word_occurrences DROP FOREIGN KEY IF EXISTS fk_word_occurrences_sentence;
ALTER TABLE word_occurrences
    ADD CONSTRAINT fk_word_occurrences_sentence
    FOREIGN KEY (Ti2SeID) REFERENCES sentences(SeID) ON DELETE CASCADE;

-- FK to words table (ON DELETE SET NULL - unknown words have NULL)
ALTER TABLE word_occurrences DROP FOREIGN KEY IF EXISTS fk_word_occurrences_word;
ALTER TABLE word_occurrences
    ADD CONSTRAINT fk_word_occurrences_word
    FOREIGN KEY (Ti2WoID) REFERENCES words(WoID) ON DELETE SET NULL;
