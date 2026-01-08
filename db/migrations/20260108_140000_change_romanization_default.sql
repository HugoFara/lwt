-- Migration: Change default for LgShowRomanization to FALSE
-- Most languages don't need romanization (only Asian scripts like Chinese, Japanese, etc.)
-- Users can enable it per-language in the language settings

-- Change the column default to 0 (FALSE) for new languages
ALTER TABLE `languages`
    MODIFY COLUMN `LgShowRomanization` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

-- Note: This does NOT change existing values.
-- Users who want romanization disabled for existing languages should
-- edit each language in the language settings and uncheck "Show Romanization".
