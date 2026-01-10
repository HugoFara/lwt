-- Migration: Rename newsfeeds to news_feeds
-- Snake_case consistency with other table names.
-- Made idempotent for MariaDB compatibility.

-- ============================================================================
-- PART 1: Drop foreign keys referencing newsfeeds
-- ============================================================================

ALTER TABLE feedlinks DROP FOREIGN KEY IF EXISTS fk_feedlinks_newsfeed;
ALTER TABLE feedlinks DROP FOREIGN KEY IF EXISTS fk_feed_links_news_feed;
ALTER TABLE newsfeeds DROP FOREIGN KEY IF EXISTS fk_newsfeeds_language;
ALTER TABLE newsfeeds DROP FOREIGN KEY IF EXISTS fk_newsfeeds_user;

-- ============================================================================
-- PART 2: Rename the table
-- ============================================================================

SET @table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'newsfeeds'
);

SET @new_table_exists = (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'news_feeds'
);

SET @sql = IF(@table_exists > 0 AND @new_table_exists = 0,
    'RENAME TABLE newsfeeds TO news_feeds',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- PART 3: Recreate foreign keys with new constraint names
-- ============================================================================

-- FK from news_feeds to languages
ALTER TABLE news_feeds DROP FOREIGN KEY IF EXISTS fk_news_feeds_language;
ALTER TABLE news_feeds
    ADD CONSTRAINT fk_news_feeds_language
    FOREIGN KEY (NfLgID) REFERENCES languages(LgID) ON DELETE CASCADE;
