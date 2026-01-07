-- Add default values to columns for strict SQL mode compatibility
-- These columns were NOT NULL without defaults, which fails in STRICT_ALL_TABLES mode

ALTER TABLE languages
    MODIFY COLUMN LgCharacterSubstitutions varchar(500) NOT NULL DEFAULT '',
    MODIFY COLUMN LgRegexpSplitSentences varchar(500) NOT NULL DEFAULT '.!?',
    MODIFY COLUMN LgExceptionsSplitSentences varchar(500) NOT NULL DEFAULT '',
    MODIFY COLUMN LgRegexpWordCharacters varchar(500) NOT NULL DEFAULT 'a-zA-ZÀ-ÖØ-öø-ȳ';

ALTER TABLE texts
    MODIFY COLUMN TxAnnotatedText longtext NOT NULL DEFAULT '';

ALTER TABLE archivedtexts
    MODIFY COLUMN AtAnnotatedText longtext NOT NULL DEFAULT '';
