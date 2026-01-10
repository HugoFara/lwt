-- Migration: Rename feedlinks to feed_links
-- Snake_case consistency with other table names.
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys on feedlinks
-- ============================================================================

ALTER TABLE feedlinks DROP FOREIGN KEY IF EXISTS fk_feedlinks_newsfeed;
ALTER TABLE feedlinks DROP FOREIGN KEY IF EXISTS fk_feedlinks_news_feed;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'feedlinks'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'feed_links'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE feedlinks TO feed_links',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with new constraint names
-- ============================================================================

-- FK to news_feeds table (ON DELETE CASCADE)
ALTER TABLE feed_links DROP FOREIGN KEY IF EXISTS fk_feed_links_news_feed;
ALTER TABLE feed_links
    ADD CONSTRAINT fk_feed_links_news_feed
    FOREIGN KEY (FlNfID) REFERENCES news_feeds(NfID) ON DELETE CASCADE;
