-- Migrations already present in the original LWT
-- Made idempotent with IF NOT EXISTS clauses for MariaDB 10.5+ compatibility

ALTER TABLE words
    ADD COLUMN IF NOT EXISTS WoTodayScore DOUBLE NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS WoTomorrowScore DOUBLE NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS WoRandom DOUBLE NOT NULL DEFAULT 0;

ALTER TABLE words
    ADD COLUMN IF NOT EXISTS WoWordCount tinyint(3) unsigned NOT NULL DEFAULT 0 AFTER WoSentence;

-- Add indexes if they don't exist (MariaDB 10.5+ supports ADD INDEX IF NOT EXISTS)
ALTER TABLE words
    ADD INDEX IF NOT EXISTS WoTodayScore (WoTodayScore),
    ADD INDEX IF NOT EXISTS WoTomorrowScore (WoTomorrowScore),
    ADD INDEX IF NOT EXISTS WoRandom (WoRandom);

ALTER TABLE languages
    ADD COLUMN IF NOT EXISTS LgRightToLeft tinyint(1) UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE texts
    ADD COLUMN IF NOT EXISTS TxAnnotatedText LONGTEXT NOT NULL AFTER TxText;

ALTER TABLE archivedtexts
    ADD COLUMN IF NOT EXISTS AtAnnotatedText LONGTEXT NOT NULL AFTER AtText;

ALTER TABLE tags
    CHANGE TgComment TgComment VARCHAR(200)
    CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE tags2
    CHANGE T2Comment T2Comment VARCHAR(200)
    CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

ALTER TABLE languages
    CHANGE COLUMN IF EXISTS LgGoogleTTSURI LgExportTemplate VARCHAR(1000)
    CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE texts
    ADD COLUMN IF NOT EXISTS TxSourceURI VARCHAR(1000)
    CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE archivedtexts
    ADD COLUMN IF NOT EXISTS AtSourceURI VARCHAR(1000)
    CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE texts
    ADD COLUMN IF NOT EXISTS TxPosition smallint(5) NOT NULL DEFAULT 0;

ALTER TABLE texts
    ADD COLUMN IF NOT EXISTS TxAudioPosition float NOT NULL DEFAULT 0;

-- Drop unused indexes (these are safe to drop - not used by FKs)
ALTER TABLE wordtags DROP INDEX IF EXISTS WtWoID;
ALTER TABLE texttags DROP INDEX IF EXISTS TtTxID;
ALTER TABLE archtexttags DROP INDEX IF EXISTS AgAtID;
