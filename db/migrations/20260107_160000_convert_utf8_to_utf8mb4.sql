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
-- Using column-by-column conversion to avoid unique key violations from collation changes
-- when hiragana/katakana or voiced/unvoiced pairs would become duplicates under unicode_ci
ALTER TABLE words DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE words MODIFY WoTextLC varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;
ALTER TABLE words MODIFY WoText varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE words MODIFY WoTranslation varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '*';
ALTER TABLE words MODIFY WoRomanization varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE words MODIFY WoSentence varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE words MODIFY WoNotes varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;

-- textitems2 table - preserve binary collation on Ti2Text
-- Note: Only modifying text columns. INT columns don't need charset conversion
-- and their types must not be changed (they have FK constraints with specific types)
ALTER TABLE textitems2 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE textitems2 MODIFY Ti2Text varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;

-- tags table - preserve binary collation on TgText for case-sensitive tag matching
ALTER TABLE tags DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tags MODIFY TgText varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;
ALTER TABLE tags MODIFY TgComment varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';

-- tags2 table - preserve binary collation on T2Text
ALTER TABLE tags2 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE tags2 MODIFY T2Text varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL;
ALTER TABLE tags2 MODIFY T2Comment varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';

ALTER TABLE wordtags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE texttags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE archtexttags CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE newsfeeds CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE feedlinks CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Note: temptextitems and tempwords are MEMORY tables that are recreated on each session
-- They will use the new default charset from baseline.sql for new installations
-- For existing installations, they are temporary and don't persist data
