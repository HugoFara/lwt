-- Migration: Recreate the tables and columns earlier migrations failed to create.
--
-- books, local_dictionaries and local_dictionary_entries are only ever created
-- by migrations, never by db/schema/baseline.sql. books and local_dictionaries
-- declare a foreign key on languages(LgID) typed INT(11) UNSIGNED. On installs
-- where 20251221_120000_add_inter_table_foreign_keys.sql never widened
-- languages.LgID from tinyint(3), those CREATE TABLE statements failed with
-- errno 150 "Foreign key constraint is incorrectly formed" — and the runner
-- recorded the migration as applied anyway, so it was never retried. The tables
-- stayed missing and the features using them crashed at runtime with
-- "Table 'books' doesn't exist" (issue #247).
--
-- Migrations::alignReferenceColumnTypes() now realigns reference columns with
-- the keys they point at before migrations run, so the statements below succeed
-- on those installs. Everything here is conditional, so healthy installs are
-- untouched.

CREATE TABLE IF NOT EXISTS `books` (
    `BkID` SMALLINT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `BkUsID` INT(10) UNSIGNED NULL,
    `BkLgID` INT(11) UNSIGNED NOT NULL,
    `BkTitle` VARCHAR(200) NOT NULL,
    `BkAuthor` VARCHAR(200) NULL,
    `BkDescription` TEXT NULL,
    `BkCoverPath` VARCHAR(500) NULL COMMENT 'Path to cover image file',
    `BkSourceType` ENUM('text', 'epub', 'pdf') NOT NULL DEFAULT 'text',
    `BkSourceHash` VARCHAR(64) NULL COMMENT 'SHA-256 hash for duplicate detection',
    `BkTotalChapters` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,
    `BkCurrentChapter` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
    `BkCreated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `BkUpdated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_books_language` (`BkLgID`),
    INDEX `idx_books_user` (`BkUsID`),
    INDEX `idx_books_source_hash` (`BkSourceHash`),

    CONSTRAINT `fk_books_language` FOREIGN KEY (`BkLgID`)
        REFERENCES `languages` (`LgID`) ON DELETE RESTRICT,
    CONSTRAINT `fk_books_user` FOREIGN KEY (`BkUsID`)
        REFERENCES `users` (`UsID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `local_dictionaries` (
    `LdID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `LdLgID` INT(11) UNSIGNED NOT NULL COMMENT 'Language ID this dictionary belongs to',
    `LdName` VARCHAR(100) NOT NULL COMMENT 'Dictionary name',
    `LdDescription` VARCHAR(500) DEFAULT NULL COMMENT 'Optional description',
    `LdSourceFormat` VARCHAR(20) NOT NULL DEFAULT 'csv' COMMENT 'Original import format: csv, json, stardict',
    `LdEntryCount` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Number of entries',
    `LdPriority` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Priority for lookup order (1=highest)',
    `LdEnabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Whether dictionary is active',
    `LdCreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `LdUsID` INT(10) UNSIGNED DEFAULT NULL COMMENT 'User ID for multi-user mode',
    PRIMARY KEY (`LdID`),
    KEY `LdLgID` (`LdLgID`),
    KEY `LdUsID` (`LdUsID`),
    KEY `LdEnabled_LdPriority` (`LdEnabled`, `LdPriority`),
    CONSTRAINT `fk_local_dict_language` FOREIGN KEY (`LdLgID`)
        REFERENCES `languages` (`LgID`) ON DELETE CASCADE,
    CONSTRAINT `fk_local_dict_user` FOREIGN KEY (`LdUsID`)
        REFERENCES `users` (`UsID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `local_dictionary_entries` (
    `LeID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `LeLdID` INT(10) UNSIGNED NOT NULL COMMENT 'Dictionary ID',
    `LeTerm` VARCHAR(250) NOT NULL COMMENT 'Headword/term',
    `LeTermLc` VARCHAR(250) NOT NULL COMMENT 'Lowercase normalized term for searching',
    `LeDefinition` TEXT NOT NULL COMMENT 'Definition/translation',
    `LeReading` VARCHAR(250) DEFAULT NULL COMMENT 'Pronunciation/reading (e.g., furigana)',
    `LePartOfSpeech` VARCHAR(50) DEFAULT NULL COMMENT 'Part of speech',
    PRIMARY KEY (`LeID`),
    KEY `LeLdID` (`LeLdID`),
    KEY `LeTermLc` (`LeTermLc`),
    CONSTRAINT `fk_entry_dictionary` FOREIGN KEY (`LeLdID`)
        REFERENCES `local_dictionaries` (`LdID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Columns added by the same migrations. Guarded with INFORMATION_SCHEMA rather
-- than ADD COLUMN IF NOT EXISTS, which is MariaDB-only syntax.

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'texts' AND COLUMN_NAME = 'TxBkID') = 0,
    'ALTER TABLE texts ADD COLUMN TxBkID SMALLINT(5) UNSIGNED NULL AFTER TxUsID',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'texts' AND COLUMN_NAME = 'TxChapterNum') = 0,
    'ALTER TABLE texts ADD COLUMN TxChapterNum SMALLINT(5) UNSIGNED NULL AFTER TxBkID',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'texts' AND COLUMN_NAME = 'TxChapterTitle') = 0,
    'ALTER TABLE texts ADD COLUMN TxChapterTitle VARCHAR(200) NULL AFTER TxChapterNum',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'texts' AND INDEX_NAME = 'idx_texts_book') = 0,
    'ALTER TABLE texts ADD INDEX idx_texts_book (TxBkID, TxChapterNum)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'languages' AND COLUMN_NAME = 'LgLocalDictMode') = 0,
    'ALTER TABLE languages ADD COLUMN LgLocalDictMode TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''Local dictionary mode: 0=online only, 1=local first, 2=local only, 3=combined''',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
