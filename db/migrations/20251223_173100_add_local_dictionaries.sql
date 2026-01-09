-- Migration: Add local dictionary support
-- Adds tables for storing offline/local dictionaries and their entries
-- Made idempotent for MariaDB compatibility

-- Table: local_dictionaries (metadata about imported dictionaries)
CREATE TABLE IF NOT EXISTS `local_dictionaries` (
    `LdID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `LdLgID` TINYINT(3) UNSIGNED NOT NULL COMMENT 'Language ID this dictionary belongs to',
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

-- Table: local_dictionary_entries (individual dictionary entries)
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

-- Add local dict mode to languages table
-- Values: 0=online only, 1=local first (fallback online), 2=local only, 3=combined
ALTER TABLE languages
    ADD COLUMN IF NOT EXISTS LgLocalDictMode TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'Local dictionary mode: 0=online only, 1=local first, 2=local only, 3=combined'
    AFTER LgShowRomanization;
