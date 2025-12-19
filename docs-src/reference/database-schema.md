# Database Information

The following seventeen tables are one "LWT table set". The default table set has no table name prefix, so the tables are named just as written below: "settings", "languages", etc.  

Additional table sets have its "table set name" plus an underscore "\_" as a table name prefix: "setname\_settings", "setname\_languages", etc. The "table set name" is max. 20 characters long. Allowed characters are only: a-z, A-Z, 0-9, and the underscore "\_".

Only if the table set is not set in "connect.inc.php", the currently used table set is saved in a global table "\_lwtgeneral", in column LWTValue of row with LWTKey = "current\_table\_prefix". If such a row does not exist, the default table set will be used, or will be automatically created and used.

## Table "settings"

Settings as Key-Value Pairs

```sql
StKey varchar(40) NOT NULL,  
StValue varchar(40) DEFAULT NULL,  
PRIMARY KEY (StKey)
```  

## Table "languages"

Defined languages

```sql
LgID tinyint(3) unsigned NOT NULL AUTO\_INCREMENT,  
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
LgRightToLeft tinyint(1) UNSIGNED NOT NULL DEFAULT '0',  
PRIMARY KEY (LgID),  
UNIQUE KEY LgName (LgName)
```

## Table "texts"

(Active texts, parsed and cached in sentences and textitems)

```sql
TxID smallint(5) unsigned NOT NULL AUTO\_INCREMENT,  
TxLgID tinyint(3) unsigned NOT NULL, -- LANGUAGE FOREIGN KEY --  
TxTitle varchar(200) NOT NULL,  
TxText text NOT NULL,  
TxAnnotatedText longtext NOT NULL,  
TxAudioURI varchar(200) DEFAULT NULL,  
TxSourceURI varchar(1000) DEFAULT NULL,  
TxPosition smallint(5) NOT NULL DEFAULT '0',  
TxAudioPosition float NOT NULL DEFAULT '0',  
PRIMARY KEY

\1,
KEY TxLgID (TxLgID)
```

## Table "archivedtexts"

Text Archive, not parsed and cached

```sql
AtID smallint(5) unsigned NOT NULL AUTO\_INCREMENT,  
AtLgID tinyint(3) unsigned NOT NULL, -- LANGUAGE FOREIGN KEY --  
AtTitle varchar(200) NOT NULL,  
AtText text NOT NULL,  
AtAnnotatedText longtext NOT NULL,  
AtAudioURI varchar(200) DEFAULT NULL,  
AtSourceURI varchar(1000) DEFAULT NULL,  
PRIMARY KEY (AtID),  
KEY AtLgID (AtLgID)
```

## Table "tags2"

Text tags and comments

```sql
T2ID smallint(5) unsigned NOT NULL AUTO\_INCREMENT,  
T2Text varchar(20) CHARACTER SET utf8 COLLATE utf8\_bin NOT NULL,  
T2Comment varchar(200) NOT NULL DEFAULT '',  
PRIMARY KEY (T2ID),  
UNIQUE KEY T2Text (T2Text)
```

## Table "texttags"

Text tags relations

```sql
TtTxID smallint(5) unsigned NOT NULL, -- TEXT FOREIGN KEY --  
TtT2ID smallint(5) unsigned NOT NULL, -- TEXT TAG FOREIGN KEY --  
PRIMARY KEY (TtTxID,TtT2ID),  
KEY TtT2ID (TtT2ID)
```

## Table "archtexttags"

Archived text tags relations

```sql
AgAtID smallint(5) unsigned NOT NULL, -- ARCHIVED TEXT FOREIGN KEY --  
AgT2ID smallint(5) unsigned NOT NULL, -- TEXT TAG FOREIGN KEY --  
PRIMARY KEY (AgAtID,AgT2ID),  
KEY AgT2ID (AgT2ID)
```

## Table "words"

the words and expressions you have saved

```sql
WoID mediumint(8) unsigned NOT NULL AUTO\_INCREMENT,  
WoLgID tinyint(3) unsigned NOT NULL, -- LANGUAGE FOREIGN KEY --  
WoText varchar(250) NOT NULL,  
WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8\_bin NOT NULL,  
WoStatus tinyint(4) NOT NULL,  
WoTranslation varchar(500) NOT NULL DEFAULT '\*',  
WoRomanization varchar(100) DEFAULT NULL,  
WoSentence varchar(1000) DEFAULT NULL,  
WoWordCount tinyint(3) unsigned NOT NULL DEFAULT '0',  
WoCreated timestamp NOT NULL DEFAULT CURRENT\_TIMESTAMP,  
WoStatusChanged timestamp NOT NULL DEFAULT '1970-01-01 12:00:00',  
WoTodayScore double NOT NULL DEFAULT '0',  
WoTomorrowScore double NOT NULL DEFAULT '0',  
WoRandom double NOT NULL DEFAULT '0',  
PRIMARY KEY (WoID),  
UNIQUE KEY WoLgIDTextLC (WoLgID,WoTextLC),  
KEY WoLgID (WoLgID),  
KEY WoStatus (WoStatus),  
KEY WoTextLC (WoTextLC),  
KEY WoTranslation (WoTranslation),  
KEY WoCreated (WoCreated),  
KEY WoStatusChanged (WoStatusChanged),  
KEY WoWordCount (WoWordCount),  
KEY WoTodayScore (WoTodayScore),  
KEY WoTomorrowScore (WoTomorrowScore),  
KEY WoRandom (WoRandom)
```

## Table "tags"

Term tags and comments

```sql
TgID smallint(5) unsigned NOT NULL AUTO\_INCREMENT,  
TgText varchar(20) CHARACTER SET utf8 COLLATE utf8\_bin NOT NULL,  
TgComment varchar(200) NOT NULL DEFAULT '',  
PRIMARY KEY (TgID),  
UNIQUE KEY TgText (TgText)
```

## Table "wordtags"

Term tags relations

```sql
WtWoID int(11) unsigned NOT NULL, -- TERM FOREIGN KEY --  
WtTgID smallint(5) unsigned NOT NULL, -- TERM TAG FOREIGN KEY --  
PRIMARY KEY (WtWoID,WtTgID),  
KEY WtTgID (WtTgID)
```

## Table "sentences"

Sentences cache, no backup needed

```sql
SeID mediumint(8) unsigned NOT NULL AUTO\_INCREMENT,  
SeLgID tinyint(3) unsigned NOT NULL, -- LANGUAGE FOREIGN KEY --  
SeTxID smallint(5) unsigned NOT NULL, -- TEXT FOREIGN KEY --  
SeOrder smallint(5) unsigned NOT NULL,  
SeText text,  
SeFirstPos smallint(5) unsigned NOT NULL,  
PRIMARY KEY (SeID),  
KEY SeLgID (SeLgID),  
KEY SeTxID (SeTxID),  
KEY SeOrder (SeOrder)
```

## Table "textitems2"

Text items cache, no backup needed

```sql
Ti2WoID mediumint(8) unsigned NOT NULL,  
Ti2LgID tinyint(3) unsigned NOT NULL, -- LANGUAGE FOREIGN KEY --  
Ti2TxID smallint(5) unsigned NOT NULL, -- TEXT FOREIGN KEY --  
Ti2SeID mediumint(8) unsigned NOT NULL, -- SENTENCE FOREIGN KEY --  
Ti2Order smallint(5) unsigned NOT NULL,  
Ti2WordCount tinyint(3) unsigned NOT NULL,  
Ti2Text varchar(250) CHARACTER SET utf8 COLLATE utf8\_bin NOT NULL,  
PRIMARY KEY (Ti2TxID,Ti2Order,Ti2WordCount),  
KEY Ti2WoID (Ti2WoID)
```

## Table "temptextitems"

memory table only used when creating texts, otherwise empty

```sql
TiCount smallint(5) unsigned NOT NULL,  
TiSeID mediumint(8) unsigned NOT NULL,  
TiOrder smallint(5) unsigned NOT NULL,  
TiWordCount tinyint(3) unsigned NOT NULL,  
TiText varchar(250) CHARACTER SET utf8 COLLATE utf8\_bin NOT NULL
```

## Table "tempwords"

memory table only used when importing words, otherwise empty

```sql
WoText varchar(250) DEFAULT NULL,  
WoTextLC varchar(250) CHARACTER SET utf8 COLLATE utf8\_bin NOT NULL,  
WoTranslation varchar(500) NOT NULL DEFAULT '\*',  
WoRomanization varchar(100) DEFAULT NULL,  
WoSentence varchar(1000) DEFAULT NULL,  
WoTaglist varchar(255) DEFAULT NULL,  
PRIMARY KEY (WoTextLC)
```

## Table "feedlinks"

Newsfeed articles

```sql
FlID smallint(5) unsigned NOT NULL AUTO\_INCREMENT,  
FlTitle varchar(200) NOT NULL,  
FlLink varchar(400) NOT NULL,  
FlDescription text NOT NULL,  
FlDate datetime NOT NULL,  
FlAudio varchar(200) NOT NULL,  
FlText longtext NOT NULL,  
FlNfID tinyint(3) unsigned NOT NULL,  
PRIMARY KEY (FlID),  
UNIQUE KEY FlTitle (FlTitle),  
KEY FlLink (FlLink),  
KEY FlDate (FlDate),  
KEY FlNfID (FlNfID)
```

## Table "newsfeeds"

Newsfeed settings

```sql
NfID tinyint(3) unsigned NOT NULL AUTO\_INCREMENT,  
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
```
