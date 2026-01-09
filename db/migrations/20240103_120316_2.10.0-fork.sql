-- LWT version 2.10.0 adds several features:
-- External voice api support, romanization hiding option, longer URI for text audio
-- Made idempotent for MariaDB compatibility

-- Add a third-party voice API
ALTER TABLE languages
    ADD COLUMN IF NOT EXISTS LgTTSVoiceAPI VARCHAR(2048) NOT NULL DEFAULT '';

-- Romanization disabling option
ALTER TABLE languages
    ADD COLUMN IF NOT EXISTS LgShowRomanization TINYINT(1) DEFAULT TRUE;

-- URI should be at least 2048 characters long:
-- https://stackoverflow.com/questions/417142/what-is-the-maximum-length-of-a-url-in-different-browsers
ALTER TABLE texts
    MODIFY COLUMN TxAudioURI VARCHAR(2048) DEFAULT NULL;
