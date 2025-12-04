-- Basefile to install LWT

-- Migration files to update the database
DROP TABLE IF EXISTS _migrations;
-- Redundant but necessary "IF NOT EXISTS" because this file will be read line by line.
-- Another process my have recreated _migrations
CREATE TABLE IF NOT EXISTS _migrations (
	filename VARCHAR(255) NOT NULL,
	PRIMARY KEY (filename)
);
INSERT INTO _migrations VALUES
('19700101_000001_lwt_original.sql'),
('19700102_000001_lwt_fork.sql'),
('20231224_131202_missing_auto_increment.sql'),
('20240103_120316_2.10.0-fork.sql'),
('20251130_120000_myisam_to_innodb.sql'),
('20251201_174538_remove_mobile_setting.sql'),
('20251204_120000_fix_audio_uri_null.sql');

-- Database definition

CREATE TABLE IF NOT EXISTS archivedtexts (
    AtID smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    AtLgID tinyint(3) unsigned NOT NULL,
    AtTitle varchar(200) NOT NULL,
    AtText text NOT NULL,
    AtAnnotatedText longtext NOT NULL,
    AtAudioURI varchar(200) DEFAULT NULL,
    AtSourceURI varchar(1000) DEFAULT NULL,
    PRIMARY KEY (AtID),
    KEY AtLgID (AtLgID),
    KEY AtLgIDSourceURI (AtSourceURI(20),AtLgID)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS languages (
    LgID tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
    LgName varchar(40) NOT NULL,
    LgDict1URI varchar(200) NOT NULL,
    LgDict2URI varchar(200) DEFAULT NULL,
    LgGoogleTranslateURI varchar(200) DEFAULT NULL,
    LgExportTemplate varchar(1000) DEFAULT NULL,
    LgTextSize smallint(5) unsigned NOT NULL DEFAULT '100',
    LgCharacterSubstitutions varchar(500) NOT NULL,
    LgRegexpSplitSentences varchar(500) NOT NULL,
    LgExceptionsSplitSentences varchar(500) NOT NULL,
    LgRegexpWordCharacters varchar(500) NOT NULL,
    LgRemoveSpaces tinyint(1) unsigned NOT NULL DEFAULT '0',
    LgSplitEachChar tinyint(1) unsigned NOT NULL DEFAULT '0',
    LgRightToLeft tinyint(1) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (LgID),
    UNIQUE KEY LgName (LgName)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS sentences (
    SeID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    SeLgID tinyint(3) unsigned NOT NULL,
    SeTxID smallint(5) unsigned NOT NULL,
    SeOrder smallint(5) unsigned NOT NULL,
    SeText text,
    SeFirstPos smallint(5) unsigned NOT NULL,
    PRIMARY KEY (SeID),
    KEY SeLgID (SeLgID),
    KEY SeTxID (SeTxID),
    KEY SeOrder (SeOrder)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS settings (
    StKey varchar(40) NOT NULL,
    StValue varchar(40) DEFAULT NULL,
    PRIMARY KEY (StKey)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS textitems2 (
    Ti2WoID mediumint(8) unsigned NOT NULL,
    Ti2LgID tinyint(3) unsigned NOT NULL,
    Ti2TxID smallint(5) unsigned NOT NULL,
    Ti2SeID mediumint(8) unsigned NOT NULL,
    Ti2Order smallint(5) unsigned NOT NULL,
    Ti2WordCount tinyint(3) unsigned NOT NULL,
    Ti2Text varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    PRIMARY KEY (Ti2TxID,Ti2Order,Ti2WordCount), KEY Ti2WoID (Ti2WoID)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS temptextitems (
    TiCount smallint(5) unsigned NOT NULL,
    TiSeID mediumint(8) unsigned NOT NULL,
    TiOrder smallint(5) unsigned NOT NULL,
    TiWordCount tinyint(3) unsigned NOT NULL,
    TiText varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tempwords (
    WoText varchar(250) DEFAULT NULL,
    WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    WoTranslation varchar(500) NOT NULL DEFAULT '*',
    WoRomanization varchar(100) DEFAULT NULL,
    WoSentence varchar(1000) DEFAULT NULL,
    WoTaglist varchar(255) DEFAULT NULL,
    PRIMARY KEY(WoTextLC)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS texts (
    TxID smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    TxLgID tinyint(3) unsigned NOT NULL,
    TxTitle varchar(200) NOT NULL,
    TxText text NOT NULL,
    TxAnnotatedText longtext NOT NULL,
    TxAudioURI varchar(2048) DEFAULT NULL,
    TxSourceURI varchar(1000) DEFAULT NULL,
    TxPosition smallint(5) DEFAULT 0,
    TxAudioPosition float DEFAULT 0,
    PRIMARY KEY (TxID),
    KEY TxLgID (TxLgID),
    KEY TxLgIDSourceURI (TxSourceURI(20),TxLgID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS words (
    WoID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    WoLgID tinyint(3) unsigned NOT NULL,
    WoText varchar(250) NOT NULL,
    WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    WoStatus tinyint(4) NOT NULL,
    WoTranslation varchar(500) NOT NULL DEFAULT '*',
    WoRomanization varchar(100) DEFAULT NULL,
    WoSentence varchar(1000) DEFAULT NULL,
    WoWordCount tinyint(3) unsigned NOT NULL DEFAULT 0,
    WoCreated timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    WoStatusChanged timestamp NOT NULL DEFAULT '1970-01-01 12:00:00',
    WoTodayScore double NOT NULL DEFAULT '0',
    WoTomorrowScore double NOT NULL DEFAULT '0',
    WoRandom double NOT NULL DEFAULT '0',
    PRIMARY KEY (WoID),
    UNIQUE KEY WoTextLCLgID (WoTextLC,WoLgID),
    KEY WoLgID (WoLgID),
    KEY WoStatus (WoStatus),
    KEY WoTranslation (WoTranslation(20)),
    KEY WoCreated (WoCreated),
    KEY WoStatusChanged (WoStatusChanged),
    KEY WoWordCount(WoWordCount),
    KEY WoTodayScore (WoTodayScore),
    KEY WoTomorrowScore (WoTomorrowScore),
    KEY WoRandom (WoRandom)
)
ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tags (
    TgID smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    TgText varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    TgComment varchar(200) NOT NULL DEFAULT '',
    PRIMARY KEY (TgID),
    UNIQUE KEY TgText (TgText)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS wordtags (
    WtWoID mediumint(8) unsigned NOT NULL,
    WtTgID smallint(5) unsigned NOT NULL,
    PRIMARY KEY (WtWoID,WtTgID),
    KEY WtTgID (WtTgID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tags2 (
    T2ID smallint(5) unsigned NOT NULL AUTO_INCREMENT,
    T2Text varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
    T2Comment varchar(200) NOT NULL DEFAULT '',
    PRIMARY KEY (T2ID),
    UNIQUE KEY T2Text (T2Text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS texttags (
    TtTxID smallint(5) unsigned NOT NULL,
    TtT2ID smallint(5) unsigned NOT NULL,
    PRIMARY KEY (TtTxID,TtT2ID), KEY TtT2ID (TtT2ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS newsfeeds (
    NfID tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
    NfLgID tinyint(3) unsigned NOT NULL,
    NfName varchar(40) NOT NULL,
    NfSourceURI varchar(200) NOT NULL,
    NfArticleSectionTags text NOT NULL,
    NfFilterTags text NOT NULL,
    NfUpdate int(12) unsigned NOT NULL,
    NfOptions varchar(200) NOT NULL,
    PRIMARY KEY (NfID),
    KEY NfLgID (NfLgID),
    KEY NfUpdate (NfUpdate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS feedlinks (
    FlID mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
    FlTitle varchar(200) NOT NULL,
    FlLink varchar(400) NOT NULL,
    FlDescription text NOT NULL,
    FlDate datetime NOT NULL,
    FlAudio varchar(200) NOT NULL,
    FlText longtext NOT NULL,
    FlNfID tinyint(3) unsigned NOT NULL,
    PRIMARY KEY (FlID),
    KEY FlLink (FlLink),
    KEY FlDate (FlDate),
    UNIQUE KEY FlTitle (FlNfID,FlTitle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS archtexttags (
    AgAtID smallint(5) unsigned NOT NULL,
    AgT2ID smallint(5) unsigned NOT NULL,
    PRIMARY KEY (AgAtID,AgT2ID),
    KEY AgT2ID (AgT2ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
