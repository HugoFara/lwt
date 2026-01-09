-- Add parser type column to languages table
-- This enables explicit selection of text parsers per language
-- Made idempotent for MariaDB compatibility

ALTER TABLE languages
ADD COLUMN IF NOT EXISTS LgParserType VARCHAR(50) DEFAULT NULL
AFTER LgRegexpWordCharacters;

-- Migrate existing MeCab languages (magic word detection)
-- Only update if not already set (idempotent)
UPDATE languages
SET LgParserType = 'mecab'
WHERE UPPER(TRIM(LgRegexpWordCharacters)) = 'MECAB' AND LgParserType IS NULL;

-- Migrate character-by-character languages
UPDATE languages
SET LgParserType = 'character'
WHERE LgSplitEachChar = 1 AND LgParserType IS NULL;

-- All others default to regex (NULL means regex in code)
