-- Migrations from the original LWT to this fork
-- Made idempotent with IF NOT EXISTS / IF EXISTS clauses for MariaDB compatibility

ALTER TABLE sentences
    ADD COLUMN IF NOT EXISTS SeFirstPos smallint(5) NOT NULL;

ALTER TABLE archivedtexts
    MODIFY COLUMN AtLgID tinyint(3) unsigned NOT NULL,
    MODIFY COLUMN AtID smallint(5) unsigned NOT NULL;

-- Index on archivedtexts (idempotent)
ALTER TABLE archivedtexts DROP INDEX IF EXISTS AtLgIDSourceURI;
ALTER TABLE archivedtexts ADD INDEX AtLgIDSourceURI (AtSourceURI(20), AtLgID);

ALTER TABLE languages
    MODIFY COLUMN LgID tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
    MODIFY COLUMN LgRemoveSpaces tinyint(1) unsigned NOT NULL,
    MODIFY COLUMN LgSplitEachChar tinyint(1) unsigned NOT NULL,
    MODIFY COLUMN LgRightToLeft tinyint(1) unsigned NOT NULL;

ALTER TABLE sentences
    MODIFY COLUMN SeID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    MODIFY COLUMN SeLgID tinyint(3) unsigned NOT NULL,
    MODIFY COLUMN SeTxID smallint(5) unsigned NOT NULL,
    MODIFY COLUMN SeOrder smallint(5) unsigned NOT NULL;

ALTER TABLE texts
    MODIFY COLUMN TxID smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    MODIFY COLUMN TxLgID tinyint(3) unsigned NOT NULL;

-- Index on texts (idempotent)
ALTER TABLE texts DROP INDEX IF EXISTS TxLgIDSourceURI;
ALTER TABLE texts ADD INDEX TxLgIDSourceURI (TxSourceURI(20), TxLgID);

ALTER TABLE words
    MODIFY COLUMN WoID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    MODIFY COLUMN WoLgID tinyint(3) unsigned NOT NULL,
    MODIFY COLUMN WoStatus tinyint(4) NOT NULL;

-- Word indexes (idempotent)
ALTER TABLE words DROP INDEX IF EXISTS WoTextLC;
ALTER TABLE words DROP INDEX IF EXISTS WoLgIDTextLC;
ALTER TABLE words DROP INDEX IF EXISTS WoTextLCLgID;
ALTER TABLE words ADD UNIQUE INDEX WoTextLCLgID (WoTextLC, WoLgID);
ALTER TABLE words DROP INDEX IF EXISTS WoWordCount;
ALTER TABLE words ADD INDEX WoWordCount (WoWordCount);

ALTER TABLE archtexttags
    MODIFY COLUMN AgAtID smallint(5) unsigned NOT NULL,
    MODIFY COLUMN AgT2ID smallint(5) unsigned NOT NULL;

ALTER TABLE tags
    MODIFY COLUMN TgID smallint(5) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE tags2
    MODIFY COLUMN T2ID smallint(5) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE wordtags
    MODIFY COLUMN WtTgID smallint(5) unsigned NOT NULL AUTO_INCREMENT;

ALTER TABLE texttags
    MODIFY COLUMN TtTxID smallint(5) unsigned NOT NULL,
    MODIFY COLUMN TtT2ID smallint(5) unsigned NOT NULL;

-- temptextitems modifications (idempotent)
ALTER TABLE temptextitems
    ADD COLUMN IF NOT EXISTS TiCount smallint(5) unsigned NOT NULL,
    DROP COLUMN IF EXISTS TiLgID,
    DROP COLUMN IF EXISTS TiTxID;

-- Update sentences SeFirstPos (idempotent - only updates rows where condition matches)
UPDATE sentences
    JOIN textitems2
    ON Ti2SeID = SeID AND Ti2Order = SeFirstPos AND Ti2WordCount = 0
    SET SeFirstPos = SeFirstPos + 1;
