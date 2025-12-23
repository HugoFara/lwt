-- Migration: Dictionary system cleanup
-- Adds popup columns and explicit language fields to replace URL-based settings

-- Add popup columns to languages table
-- These replace the asterisk prefix (*url) and lwt_popup query parameter
ALTER TABLE `languages`
    ADD COLUMN `LgDict1PopUp` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Dictionary 1 opens in popup window'
        AFTER `LgGoogleTranslateURI`,
    ADD COLUMN `LgDict2PopUp` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Dictionary 2 opens in popup window'
        AFTER `LgDict1PopUp`,
    ADD COLUMN `LgGoogleTranslatePopUp` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
        COMMENT 'Translator opens in popup window'
        AFTER `LgDict2PopUp`;

-- Add explicit source/target language columns
-- These replace parsing translator URLs for language codes (sl/source params)
ALTER TABLE `languages`
    ADD COLUMN `LgSourceLang` VARCHAR(10) DEFAULT NULL
        COMMENT 'Source language code (BCP 47, e.g., ja, de)'
        AFTER `LgGoogleTranslatePopUp`,
    ADD COLUMN `LgTargetLang` VARCHAR(10) DEFAULT NULL
        COMMENT 'Target language code (BCP 47, e.g., en)'
        AFTER `LgSourceLang`;
