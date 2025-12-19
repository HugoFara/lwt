-- Migration: Convert prefixed table sets to user_id-based multi-user system
--
-- This migration:
-- 1. Finds all existing prefixed table sets (e.g., "john_", "student1_")
-- 2. Creates a user account for each prefix
-- 3. Copies data from prefixed tables to main (unprefixed) tables with user_id set
-- 4. Drops the old prefixed tables after successful migration
--
-- IMPORTANT: This migration uses stored procedures for complex logic.
-- It should be run manually or via a PHP migration script for full functionality.
--
-- For databases without prefixed tables, this migration does nothing.

-- Create a tracking table for migration status
CREATE TABLE IF NOT EXISTS _prefix_migration_log (
    prefix VARCHAR(40) NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    tables_migrated INT DEFAULT 0,
    migrated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (prefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Note: The actual prefix-to-user migration requires procedural logic.
-- See the PHP migration script: src/backend/Core/Database/PrefixMigration.php
--
-- The PHP script will:
-- 1. Call TableSetService::getAllPrefixes() to find prefixed table sets
-- 2. For each prefix:
--    a. Create a user with username derived from prefix
--    b. Copy all data from {prefix}_languages to languages with user_id
--    c. Copy all data from {prefix}_texts to texts with user_id
--    d. Copy all data from {prefix}_words to words with user_id
--    e. Copy all data from {prefix}_tags to tags with user_id
--    f. Copy all data from {prefix}_tags2 to tags2 with user_id
--    g. Copy all data from {prefix}_newsfeeds to newsfeeds with user_id
--    h. Copy all data from {prefix}_settings to settings with user_id
--    i. Copy related junction tables (sentences, textitems2, wordtags, etc.)
-- 3. Log the migration in _prefix_migration_log
-- 4. Optionally drop prefixed tables after verification

-- For simple cases with no prefixed tables, ensure the default (unprefixed) data
-- is assigned to the admin user if not already done
UPDATE languages SET LgUsID = 1 WHERE LgUsID IS NULL;
UPDATE texts SET TxUsID = 1 WHERE TxUsID IS NULL;
UPDATE archivedtexts SET AtUsID = 1 WHERE AtUsID IS NULL;
UPDATE words SET WoUsID = 1 WHERE WoUsID IS NULL;
UPDATE tags SET TgUsID = 1 WHERE TgUsID IS NULL;
UPDATE tags2 SET T2UsID = 1 WHERE T2UsID IS NULL;
UPDATE newsfeeds SET NfUsID = 1 WHERE NfUsID IS NULL;
UPDATE settings SET StUsID = 1 WHERE StUsID IS NULL;
