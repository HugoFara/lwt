-- Migration: Convert database from utf8 to utf8mb4
-- This enables support for 4-byte Unicode characters (emoji, some Asian scripts)
-- See SECURITY_AUDIT.md issue #28

-- Note: This migration may take a while on large databases as it converts all text data

-- Convert database default character set
-- (Uses dynamic SQL since database name varies per installation)
-- The application will use utf8mb4 for the connection after this migration

-- Convert all tables to utf8mb4 with unicode collation
-- Tables with _bin collation columns will keep binary collation for case-sensitive lookups

ALTER TABLE _migrations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE _prefix_migration_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE languages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE texts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE archivedtexts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE sentences CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- words table - preserve binary collation on WoTextLC for case-sensitive lookups
ALTER TABLE words CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE words MODIFY WoTextLC varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;

-- textitems2 table - preserve binary collation on Ti2Text
ALTER TABLE textitems2 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE textitems2 MODIFY Ti2Text varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;

-- tags table - preserve binary collation on TgText for case-sensitive tag matching
ALTER TABLE tags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tags MODIFY TgText varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;

-- tags2 table - preserve binary collation on T2Text
ALTER TABLE tags2 CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tags2 MODIFY T2Text varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;

ALTER TABLE wordtags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE texttags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE archtexttags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE newsfeeds CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE feedlinks CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Note: temptextitems and tempwords are MEMORY tables that are recreated on each session
-- They will use the new default charset from baseline.sql for new installations
-- For existing installations, they are temporary and don't persist data
