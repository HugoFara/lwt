-- lwt-backup--- /ensures that this can be imported via Restore/
--
-- --------------------------------------------------------------
-- "Learning with Texts" (LWT) is free and unencumbered software
-- released into the PUBLIC DOMAIN.
--
-- Anyone is free to copy, modify, publish, use, compile, sell, or
-- distribute this software, either in source code form or as a
-- compiled binary, for any purpose, commercial or non-commercial,
-- and by any means.
--
-- In jurisdictions that recognize copyright laws, the author or
-- authors of this software dedicate any and all copyright
-- interest in the software to the public domain. We make this
-- dedication for the benefit of the public at large and to the
-- detriment of our heirs and successors. We intend this
-- dedication to be an overt act of relinquishment in perpetuity
-- of all present and future rights to this software under
-- copyright law.
--
-- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
-- EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
-- WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
-- AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE
-- FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
-- OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
-- CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
-- THE SOFTWARE.
--
-- For more information, please refer to [http://unlicense.org/].
-- --------------------------------------------------------------
--
-- --------------------------------------------------------------
-- Installing an LWT demo database
-- --------------------------------------------------------------
DROP
  TABLE IF EXISTS archivedtexts;

CREATE TABLE `archivedtexts` (
  `AtID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `AtLgID` int(11) unsigned NOT NULL,
  `AtTitle` varchar(200) NOT NULL,
  `AtText` text NOT NULL,
  `AtAnnotatedText` longtext NOT NULL,
  `AtAudioURI` varchar(200) DEFAULT NULL,
  `AtSourceURI` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`AtID`),
  KEY `AtLgID` (`AtLgID`)
) ENGINE = MyISAM AUTO_INCREMENT = 2 DEFAULT CHARSET = utf8;

INSERT INTO archivedtexts
VALUES
  (
    '1', '1', 'Bonjour!', 'Bonjour Manon.\nBonjour.\nAlors, je crois qu’il y a pas longtemps, là, vous avez fait une bonne action ?\nOui.',
    '', NULL, 'http://francebienvenue1.wordpress.com/2011/06/18/generosite/'
  );

DROP
  TABLE IF EXISTS archtexttags;
CREATE TABLE `archtexttags` (
  `AgAtID` int(11) unsigned NOT NULL,
  `AgT2ID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`AgAtID`, `AgT2ID`),
  KEY `AgAtID` (`AgAtID`),
  KEY `AgT2ID` (`AgT2ID`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;

INSERT INTO archtexttags
VALUES
  ('1', '1');
INSERT INTO archtexttags
VALUES
  ('1', '4');
INSERT INTO archtexttags
VALUES
  ('1', '8');

DROP
  TABLE IF EXISTS newsfeeds;

CREATE TABLE `newsfeeds` (
  `NfID` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `NfLgID` tinyint(3) unsigned NOT NULL,
  `NfName` varchar(40) NOT NULL,
  `NfSourceURI` varchar(200) NOT NULL,
  `NfArticleSectionTags` text NOT NULL,
  `NfFilterTags` text NOT NULL,
  `NfUpdate` int(12) unsigned NOT NULL,
  `NfOptions` varchar(200) NOT NULL,
  PRIMARY KEY (`NfID`),
  KEY `NfLgID` (`NfLgID`),
  KEY `NfUpdate` (`NfUpdate`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
INSERT INTO newsfeeds
VALUES
  (
    1, 9, 'The Guardian', 'http://www.theguardian.com/theguardian/mainsection/rss',
    '//div[@id="article-wrapper"]',
    '//div[@id="main-content-picture"] | //div[@id="article-wrapper"]/span[@class="trackable-component component-wrapper six-col"]',
    0, 'edit_text=1'
  );
INSERT INTO newsfeeds
VALUES
  (
    2, 1, 'Le Monde', 'http://www.lemonde.fr/rss/une.xml',
    '//*[@id="articleBody"] | //div[@class="entry-content"]/p',
    '', 0, 'edit_text=1'
  );
INSERT INTO newsfeeds
VALUES
  (
    3, 11, 'Il Corriere', 'http://xml.corriereobjects.it/rss/homepage.xml',
    '//div[@class="contenuto_articolo"]/p | //div[@id="content-to-read"]/p | //blockquote/p | //p[@class="chapter-paragraph"]',
    '', 0, 'edit_text=1,max_links=200'
  );
INSERT INTO newsfeeds
VALUES
  (
    4, 3, 'wissen.de', 'http://feeds.feedburner.com/wissen/wissen_de',
    '//div[@class="article-content"]',
    '//div[@class="file file-image file-image-jpeg"] | //em[last()] | //div[@class="imagegallery-wrapper hide"] | //ul[@class="links inline"] | //div[@class="smart-paging-pager"] | //div[@class="field-item even"]/div',
    0, 'edit_text=1,max_links=500'
  );
INSERT INTO newsfeeds
VALUES
  (
    5, 3, 'Der Spiegel', 'http://www.spiegel.de/schlagzeilen/index.rss',
    '//p[@class="article-intro"] | //div[@class="article-section clearfix"]',
    '//*[@class[contains(concat(" ",normalize-space(.)," ")," js-module-box-image ")]] |  //*[@class[contains(concat(" ",normalize-space(.)," ")," asset-box ")]] |  //*[@class[contains(concat(" ",normalize-space(.)," ")," htmlartikellistbox ")]] |  //p/i',
    0, 'edit_text=1,charset=meta'
  );
INSERT INTO newsfeeds
VALUES
  (
    6, 3, 'deutsche Welle Nachrichten',
    'http://rss.dw-world.de/xml/DKpodcast_lgn_de',
    '//description', '', 0, 'article_source=description'
  );
INSERT INTO newsfeeds
VALUES
  (
    7, 10, 'El Pais', 'http://ep00.epimg.net/rss/elpais/portada.xml',
    '//div[@id="cuerpo_noticia"]/p',
    '', 0, ''
  );
INSERT INTO newsfeeds
VALUES
  (
    8, 12, 'RIA Novosti', 'http://ria.ru/export/rss2/index.xml',
    '//div[@class="article_lead"] | //*[@*[contains(.,"articleBody")]]/p',
    '//p[@class="marker-quote3"]',
    0, 'edit_text=1'
  );
INSERT INTO newsfeeds
VALUES
  (
    9, 7, 'ข่าวไทยรัฐออนไลน์',
    'http://www.thairath.co.th/rss/news.xml',
    '//div[@class="entry"]/p', '//div[@id="content"]/p[@class="time"]',
    1455049278, 'edit_text=1'
  );
INSERT INTO newsfeeds
VALUES
  (
    10, 14, 'Euronews Arabic', 'http://feeds.feedburner.com/euronews/ar/news/',
    '//div[@id="article-text"]/p |  //div[@id="articleTranscript"]/p',
    '//div[@id="article-text"]/p[@class="en-cpy"]',
    0, ''
  );
INSERT INTO newsfeeds
VALUES
  (
    11, 10, 'Spanish Podcast', 'http://www.spanishpodcast.org/podcasts/index.xml',
    'redirect://div[@class="figure-content caption"]//a | //div[@class="figure-content caption"]/p | //div/p[@class="MsoNormal"]',
    '', 0, 'edit_text=1'
  );
INSERT INTO newsfeeds
VALUES
  (
    12, 3, 'NachDenkSeiten', 'http://www.nachdenkseiten.de/?feed=audiopodcast',
    '//encoded/p', '', 0, 'edit_text=1,article_source=encoded'
  );
DROP
  TABLE IF EXISTS languages;
CREATE TABLE `languages` (
  `LgID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `LgName` varchar(40) NOT NULL,
  `LgDict1URI` varchar(200) NOT NULL,
  `LgDict2URI` varchar(200) DEFAULT NULL,
  `LgGoogleTranslateURI` varchar(200) DEFAULT NULL,
  `LgExportTemplate` varchar(1000) DEFAULT NULL,
  `LgTextSize` int(5) unsigned NOT NULL DEFAULT '100',
  `LgCharacterSubstitutions` varchar(500) NOT NULL,
  `LgRegexpSplitSentences` varchar(500) NOT NULL,
  `LgExceptionsSplitSentences` varchar(500) NOT NULL,
  `LgRegexpWordCharacters` varchar(500) NOT NULL,
  `LgRemoveSpaces` int(1) unsigned NOT NULL DEFAULT '0',
  `LgSplitEachChar` int(1) unsigned NOT NULL DEFAULT '0',
  `LgRightToLeft` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`LgID`),
  UNIQUE KEY `LgName` (`LgName`)
) ENGINE = MyISAM AUTO_INCREMENT = 9 DEFAULT CHARSET = utf8;
INSERT INTO languages
VALUES
  (
    '1', 'French', 'http://www.wordreference.com/fren/lwt_term?lwt_popup=1',
    NULL, 'https://translate.google.com/?ie=UTF-8&sl=fr&tl=en&text=lwt_term&lwt_popup=1',
    '$y\\t$t\\n', '100', '´=\'|`=\'|’=\'|‘=\'|...=…|..=‥',
    '.!?:;', '[A-Z].|Dr.', 'a-zA-ZÀ-ÖØ-öø-ȳ',
    '0', '0', '0'
  );
INSERT INTO languages
VALUES
  (
    '2', 'Chinese', 'https://ce.linedict.com/dict.html#/cnen/search?query=lwt_term',
    'http://chinesedictionary.mobi/?handler=QueryWorddict&mwdqb=lwt_term',
    'https://translate.google.com/?ie=UTF-8&sl=zh&tl=en&text=lwt_term&lwt_popup=1',
    '$y\\t$t\\n', '200', '', '.!?:;。！？：；',
    '', '一-龥', '1', '1', '0'
  );
INSERT INTO languages
VALUES
  (
    '3', 'German', 'http://de-en.syn.dict.cc/?s=lwt_term',
    NULL, 'https://translate.google.com/?ie=UTF-8&sl=de&tl=en&text=lwt_term&lwt_popup=1',
    '$y\\t$t\\n', '150', '´=\'|`=\'|’=\'|‘=\'|...=…|..=‥',
    '.!?:;', '[A-Z].|Dr.', 'a-zA-ZäöüÄÖÜß',
    '0', '0', '0'
  );
INSERT INTO languages
VALUES
  (
    '4', 'Chinese2', 'https://ce.linedict.com/dict.html#/cnen/search?query=lwt_term',
    'http://chinesedictionary.mobi/?handler=QueryWorddict&mwdqb=lwt_term',
    'https://translate.google.com/?ie=UTF-8&sl=zh&tl=en&text=lwt_term&lwt_popup=1',
    '$y\\t$t\\n', '200', '', '.!?:;。！？：；',
    '', '一-龥', '1', '0', '0'
  );
INSERT INTO languages
VALUES
  (
    '5', 'Japanese', 'https://jisho.org/words?eng=&dict=edict&jap=lwt_term',
    'http://jisho.org/kanji/details/lwt_term',
    'https://translate.google.com/?ie=UTF-8&sl=ja&tl=en&text=lwt_term&lwt_popup=1',
    '$y\\t$t\\n', '200', '', '.!?:;。！？：；',
    '', '一-龥ぁ-ヾ', '1', '1', '0'
  );
INSERT INTO languages
VALUES
  (
    '6', 'Korean', 'http://endic.naver.com/search.nhn?sLn=kr&isOnlyViewEE=N&query=lwt_term&lwt_popup=1',
    NULL, 'https://translate.google.com/?text=lwt_term&ie=UTF-8&sl=ko&tl=en&lwt_popup=1',
    '$y\\t$t\\n', '150', '', '.!?:;。！？：；',
    '', '가-힣ᄀ-ᇂ', '0', '0', '0'
  );
INSERT INTO languages
VALUES
  (
    '7', 'Thai', 'http://dict.longdo.com/search/lwt_term',
    NULL, 'https://translate.google.com/?ie=UTF-8&sl=th&tl=en&text=lwt_term&lwt_popup=1',
    '$y\\t$t\\n', '250', '', '.!?:;',
    '', 'ก-๛', '1', '0', '0'
  );
INSERT INTO languages
VALUES
  (
    '8', 'Hebrew', 'http://dictionary.reverso.net/hebrew-english/lwt_term&lwt_popup=1',
    NULL, 'https://translate.google.com/?ie=UTF-8&sl=iw&tl=en&text=lwt_term&lwt_popup=1',
    '$y\\t$t\\n', '150', '', '.!?:;',
    '', '\\x{0590}-\\x{05FF}', '0', '0',
    '1'
  );
DROP
  TABLE IF EXISTS sentences;
CREATE TABLE `sentences` (
  `SeID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `SeLgID` int(11) unsigned NOT NULL,
  `SeTxID` int(11) unsigned NOT NULL,
  `SeOrder` int(11) unsigned NOT NULL,
  `SeText` text,
  `SeFirstPos` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`SeID`),
  KEY `SeLgID` (`SeLgID`),
  KEY `SeTxID` (`SeTxID`),
  KEY `SeOrder` (`SeOrder`)
) ENGINE = MyISAM AUTO_INCREMENT = 357 DEFAULT CHARSET = utf8;
DROP
  TABLE IF EXISTS settings;
CREATE TABLE `settings` (
  `StKey` varchar(40) NOT NULL,
  `StValue` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`StKey`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
INSERT INTO settings
VALUES
  ('dbversion', 'v002000000');
INSERT INTO settings
VALUES
  ('showallwords', '0');
INSERT INTO settings
VALUES
  ('currentlanguage', '1');
INSERT INTO settings
VALUES
  ('lastscorecalc', '2020-10-03');
INSERT INTO settings
VALUES
  (
    'set-text-h-frameheight-no-audio',
    '140'
  );
INSERT INTO settings
VALUES
  (
    'set-text-h-frameheight-with-audio',
    '200'
  );
INSERT INTO settings
VALUES
  (
    'set-text-l-framewidth-percent',
    '50'
  );
INSERT INTO settings
VALUES
  (
    'set-text-r-frameheight-percent',
    '50'
  );
INSERT INTO settings
VALUES
  ('set-test-h-frameheight', '140');
INSERT INTO settings
VALUES
  (
    'set-test-l-framewidth-percent',
    '50'
  );
INSERT INTO settings
VALUES
  (
    'set-test-r-frameheight-percent',
    '50'
  );
INSERT INTO settings
VALUES
  (
    'set-test-main-frame-waiting-time',
    '0'
  );
INSERT INTO settings
VALUES
  (
    'set-test-edit-frame-waiting-time',
    '500'
  );
INSERT INTO settings
VALUES
  ('set-test-sentence-count', '1');
INSERT INTO settings
VALUES
  ('set-term-sentence-count', '1');
INSERT INTO settings
VALUES
  (
    'set-archivedtexts-per-page', '100'
  );
INSERT INTO settings
VALUES
  ('set-texts-per-page', '10');
INSERT INTO settings
VALUES
  ('set-terms-per-page', '100');
INSERT INTO settings
VALUES
  ('set-tags-per-page', '100');
INSERT INTO settings
VALUES
  (
    'set-show-text-word-counts', '1'
  );
INSERT INTO settings
VALUES
  (
    'set-term-translation-delimiters',
    '/;|'
  );
INSERT INTO settings
VALUES
  ('set-mobile-display-mode', '0');
INSERT INTO settings
VALUES
  ('set-similar-terms-count', '0');
INSERT INTO settings
VALUES
  ('currenttext', '1');
DROP
  TABLE IF EXISTS tags;
CREATE TABLE `tags` (
  `TgID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `TgText` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `TgComment` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`TgID`),
  UNIQUE KEY `TgText` (`TgText`)
) ENGINE = MyISAM AUTO_INCREMENT = 29 DEFAULT CHARSET = utf8;
INSERT INTO tags
VALUES
  ('1', 'masc', '');
INSERT INTO tags
VALUES
  ('2', 'fem', '');
INSERT INTO tags
VALUES
  ('8', '3p-sg', '');
INSERT INTO tags
VALUES
  ('5', '1p-sg', '');
INSERT INTO tags
VALUES
  ('6', '2p-sg', '');
INSERT INTO tags
VALUES
  ('7', 'verb', '');
INSERT INTO tags
VALUES
  ('9', '1p-pl', '');
INSERT INTO tags
VALUES
  ('10', '2p-pl', '');
INSERT INTO tags
VALUES
  ('11', '3p-pl', '');
INSERT INTO tags
VALUES
  ('12', 'adj', '');
INSERT INTO tags
VALUES
  ('13', 'adv', '');
INSERT INTO tags
VALUES
  ('14', 'interj', '');
INSERT INTO tags
VALUES
  ('15', 'conj', '');
INSERT INTO tags
VALUES
  ('16', 'num', '');
INSERT INTO tags
VALUES
  ('17', 'infinitive', '');
INSERT INTO tags
VALUES
  ('18', 'noun', '');
INSERT INTO tags
VALUES
  ('19', 'pronoun', '');
INSERT INTO tags
VALUES
  ('20', 'informal', '');
INSERT INTO tags
VALUES
  ('21', 'colloc', '');
INSERT INTO tags
VALUES
  ('22', 'pres', '');
INSERT INTO tags
VALUES
  ('23', 'impf', '');
INSERT INTO tags
VALUES
  ('24', 'subj', '');
INSERT INTO tags
VALUES
  ('25', 'pastpart', '');
INSERT INTO tags
VALUES
  ('26', 'prespart', '');
INSERT INTO tags
VALUES
  ('27', 'name', '');
INSERT INTO tags
VALUES
  ('28', 'greeting', '');
DROP
  TABLE IF EXISTS tags2;
CREATE TABLE `tags2` (
  `T2ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `T2Text` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `T2Comment` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`T2ID`),
  UNIQUE KEY `T2Text` (`T2Text`)
) ENGINE = MyISAM AUTO_INCREMENT = 10 DEFAULT CHARSET = utf8;
INSERT INTO tags2
VALUES
  ('1', 'demo', '');
INSERT INTO tags2
VALUES
  ('2', 'basic', '');
INSERT INTO tags2
VALUES
  ('3', 'goethe', '');
INSERT INTO tags2
VALUES
  ('4', 'conversation', '');
INSERT INTO tags2
VALUES
  ('5', 'joke', '');
INSERT INTO tags2
VALUES
  ('6', 'chinesepod', '');
INSERT INTO tags2
VALUES
  ('7', 'literature', '');
INSERT INTO tags2
VALUES
  ('8', 'fragment', '');
INSERT INTO tags2
VALUES
  ('9', 'annotation', '');
DROP
  TABLE IF EXISTS textitems;
CREATE TABLE `textitems` (
  `TiID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `TiLgID` int(11) unsigned NOT NULL,
  `TiTxID` int(11) unsigned NOT NULL,
  `TiSeID` int(11) unsigned NOT NULL,
  `TiOrder` int(11) unsigned NOT NULL,
  `TiWordCount` int(1) unsigned NOT NULL,
  `TiText` varchar(250) NOT NULL,
  `TiTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `TiIsNotWord` tinyint(1) NOT NULL,
  PRIMARY KEY (`TiID`),
  KEY `TiLgID` (`TiLgID`),
  KEY `TiTxID` (`TiTxID`),
  KEY `TiSeID` (`TiSeID`),
  KEY `TiOrder` (`TiOrder`),
  KEY `TiTextLC` (`TiTextLC`),
  KEY `TiIsNotWord` (`TiIsNotWord`)
) ENGINE = MyISAM AUTO_INCREMENT = 12761 DEFAULT CHARSET = utf8;
DROP
  TABLE IF EXISTS textitems2;
CREATE TABLE `textitems2` (
  `Ti2WoID` mediumint(8) unsigned NOT NULL,
  `Ti2LgID` tinyint(3) unsigned NOT NULL,
  `Ti2TxID` smallint(5) unsigned NOT NULL,
  `Ti2SeID` mediumint(8) unsigned NOT NULL,
  `Ti2Order` smallint(5) unsigned NOT NULL,
  `Ti2WordCount` tinyint(3) unsigned NOT NULL,
  `Ti2Text` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `Ti2Translation` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (
    `Ti2TxID`, `Ti2Order`, `Ti2WordCount`
  ),
  KEY `Ti2WoID` (`Ti2WoID`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
DROP
  TABLE IF EXISTS temptextitems;
CREATE TABLE `temptextitems` (
  `TiCount` smallint(5) unsigned NOT NULL,
  `TiSeID` mediumint(8) unsigned NOT NULL,
  `TiOrder` smallint(5) unsigned NOT NULL,
  `TiWordCount` tinyint(3) unsigned NOT NULL,
  `TiText` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE = MEMORY DEFAULT CHARSET = utf8;
DROP
  TABLE IF EXISTS tempwords;
CREATE TABLE `tempwords` (
  `WoText` varchar(250) DEFAULT NULL,
  `WoTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `WoTranslation` varchar(500) NOT NULL DEFAULT '*',
  `WoRomanization` varchar(100) DEFAULT NULL,
  `WoSentence` varchar(1000) DEFAULT NULL,
  `WoTaglist` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`WoTextLC`)
) ENGINE = MEMORY DEFAULT CHARSET = utf8;
DROP
  TABLE IF EXISTS texts;
CREATE TABLE `texts` (
  `TxID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `TxLgID` int(11) unsigned NOT NULL,
  `TxTitle` varchar(200) NOT NULL,
  `TxText` text NOT NULL,
  `TxAnnotatedText` longtext NOT NULL,
  `TxAudioURI` varchar(200) DEFAULT NULL,
  `TxSourceURI` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`TxID`),
  KEY `TxLgID` (`TxLgID`)
) ENGINE = MyISAM AUTO_INCREMENT = 10 DEFAULT CHARSET = utf8;
INSERT INTO texts
VALUES
  (
    '1', '1', 'Mon premier don du sang',
    'Bonjour Manon.\nBonjour.\nAlors, je crois qu’il y a pas longtemps, là, vous avez fait une bonne action ?\nOui. \nOn peut dire ça comme ça. Qu’est-ce que vous avez fait, alors ?\nAlors, j’ai fait mon premier don du sang. Donc c’est à dire que on va dans une... Un organisme spécialisé vient dans l’IUT, dans notre université pour... pour prendre notre sang pour les malades de l’hôpital qui en ont besoin...\nOui, voilà, en cas d’accident par exemple, etc...\nEn cas d’accident ou en cas d’anémie ...\nOui, oui. D’accord. Et alors, donc, c’était la première fois que vous le faisiez ?\nC’est la première fois et ça m’a marquée parce que j’ai... j’ai très peur des piqures en temps habituel.\nAh bon !\nVoilà. J’en ai... j’en fais très rarement, le plus rarement possible...\nOui ?\n... pour... pour éviter ça au maximum. Et puis...\nOui, et là, c’est pas une petite piqure ! Ça dure un moment, en fait !\nAh non, ça dure quinze – vingt minutes.\nAh, d’accord.\nIls prennent... je sais plus combien de litres de sang. Ah, c’est beaucoup.\nOui, oui. D’accord.\nOuais, ouais, ouais.\nEt donc vous avez franchi le pas.\nVoilà.\nMais pourquoi, alors ?\nParce que je pense que c’est important d’aider les autres, surtout que j’ai appris que j’ai un sang assez rare.\nAh oui ? C’est vrai ?\nOuais.\nPourquoi ? C’est quoi ?\nA négatif.\nAh, d’accord. Oui, oui. Moi, c’est pareil.\nC’est un sang... Ah, c’est vrai ?\nOui, oui.\nAssez rare, donc voilà, les gens, si ils en ont besoin. Et puis si un jour, moi j’en ai besoin, je serai contente que d’autres en donnent. Donc voilà.\nOui, oui. D’accord.\nEn attendant, je fais ça.\nOui, oui, bien sûr. Et alors, comment ça se passe concrètement? Donc vous êtes allée... Donc ils sont venus à l’IUT, là. Ils installent tout bien comme il faut, et alors vous y allez et puis...\nOn y va, ils nous de ... Ils nous posent quelques questions par rapport à notre hygiène de vie évidemment, pour... pour les maladies, tout ça, si on n’a pas eu... été malades, pour les médicaments dans le sang, tout ça. Et puis après, ils vous installent sur une... sur une table, allongé, et ils vous... ils vous piquent et...\nEt on attend.\nEt on attend, 15 ou 20 minutes.\nOui, d’accord. Et le temps n’est pas trop long ? On se sent pas un peu bizarre ou...?\nOn se sent bizarre, mais ils sont vraiment à côté de nous pour... pour justement qu’on... qu’on reste éveillé en quelque sorte, et qu’on reste actif pour pas justement qu’on parte... un peu à...\nOui, à se poser des questions, tout ça, et puis se sentir affaibli ou quelque chose.\nVoilà. Donc ils sont vraiment à côté de nous, à nous parler, à nous faire rigoler pour voir si on est toujours conscient, finalement.\nAh oui, d’accord.\nOui, oui. Voilà.\nOui, oui, oui. Et après, alors, à la fin, qu’est-ce qui se passe ?\nA la fin, ils vous enlèvent la piqure. Et d’ailleurs, c’est là que je me suis évanouie !\nCarrément ?\nOuais.\nAh bon, d’accord !\nOui, oui. Ils ont dû... Ils ont dû me mettre les pieds en l’air. Ils ont... Ils ont bien rigolé parce que justement, ils... ils ont vu que quand je suis partie, justement, c’est quand ils ont enlevé la piqure, je souriais et quand je suis... quand je me suis... quand j’ai repris conscience, ils m’ont dit que c’était la... la première fois qu’ils avaient vu quelqu’un partir...\nEn souriant ?\n...en souriant.\nAh bon, d’accord. Alors vous êtes très spéciale !\nVoilà.\nAh oui ? Et alors, donc... mais vous vous êtes évanouie carrément ?\nAh oui, carrément ! J’ai perdu conscience pendant... bon pas longtemps, hein, peut-être une ou deux minutes, le temps que... que ça revienne. Je pense que c’était un...\nUn étourdissement ?\n... un trop-plein... trop-plein d’émotions en fait.\nAh d’accord !\nD’être contente et à la fois d’avoir eu peur.\nAh bon !\nTout ça, ouais.\nVous êtes à ce point sensible...\nAh oui, oui, vraiment.\n... émotive.\nOuais.\nD’accord. Et alors, qu’est-ce qu’ils ont fait, eux ? Ils vous ont... quoi ? Je sais pas... tapé sur les joues ?\nNon, non, ils ont été très calmes, apparemment, d’après ce que j’ai entendu.\nIl faut placer dans une bonne position, quoi. C’est ça ?\nVoilà, ils ont juste relevé mes pieds. Et ils m’ont... Ils m’ont mis un... un coton imbibé de... de quelque chose. Je sais pas ce que c’était. Ça... ça sentait l’eucalyptus.\nAh oui, oui. Pour un peu vous...\nPour un peu...\n... stimuler.\nOuais, voilà.\nD’accord. Bon bah c’est sympa ! J’espère que tout le monde se... s’évanouit pas après les... les prises de sang comme ça ! Et il y a pas à manger aussi un peu, non ? C’est ça ?\nVoilà, et après ils nous... ils nous donnent ce qu’on veut: un... un gâteau ou un verre de... de soda..\nOuais, ouais, pour reconstituer...\n... un truc bien sucré.\nOuais d’accord, pour se reconstituer les forces.\nPour repartir, voilà.\nD’accord. Bon bah c’est bien, alors, d’avoir fait ça. Est-ce que vous recommencerez, alors ?\nOui, oui. Il faut attendre trois mois. Donc je l’ai fait en février et je compte bien le faire en juin, là, ouais.\nAh bon d’accord. Et alors, vous comptez vous évanouir à nouveau ?\nNon, je vais essayer... je vais essayer de me retenir.\nD’accord.\nJe vais leur expliquer que je suis un peu émotive. Et...\nUn peu, oui. Hm, hm. Mais peut-être que la deuxième fois, comme vous saurez déjà comment ça se passe, ce sera moins...\nOui, je pense.\n... moins stressant.\nJe... je pars avec moins d’appréhension, en tout cas, pour la deuxième fois.\nOui, d’accord. Bon, bah, c’est bien, alors, d’avoir fait ça.\nOui.\nD’accord. Bah très bien pour les... pour les gens à qui ça va servir. Merci Manon.',
    '', 'https://learning-with-texts.sourceforge.io/media/dondusang.mp3',
    'http://francebienvenue1.wordpress.com/2011/06/18/generosite/'
  );
INSERT INTO texts
VALUES
  (
    '2', '2', 'The Man and the Dog (annotated version)',
    '一天，一个男人走在街上。突然，他看见前面有一只黑色的大狗，看起来很凶。男人非常害怕，不敢往前走。狗的旁边站着一个女人，男人问她：你的狗咬人吗？女人说：我的狗不咬人。这时，那只狗咬了男人。他气坏了，大叫：你说你的狗不咬人！女人回答：这不是我的狗。',
    '4  一天  11  one day\n-1  ，\n6  一  9  a\n8  个  12  (MW)\n12  男人  15  man\n14  走  16  walk\n16  在  17  at\n20  街上  20  on the street\n-1  。\n24  突然  21  suddenly\n-1  ，\n26  他  184  he\n30  看见  185  catch sight\n34  前面  186  ahead\n36  有  187  have\n38  一  9  a\n40  只  188  (MW)\n44  黑色  189  black color\n46  的  190  \'s\n48  大  191  big\n50  狗  192  dog\n-1  ，\n56  看起来  193  appears\n58  很  194  very\n60  凶  195  ferocious\n-1  。\n64  男人  15  man\n68  非常  196  exceptional\n72  害怕  197  be afraid\n-1  ，\n74  不  198  not\n76  敢  199  dare\n80  往前  200  move ahead\n82  走  16  walk\n-1  。\n84  狗  192  dog\n86  的  190  \'s\n90  旁边  201  side\n92  站  202  stand\n94  着  203  (there)\n96  一  9  a\n98  个  12  (MW)\n102  女人  204  woman\n-1  ，\n106  男人  15  man\n108  问  205  ask\n110  她  206  her\n-1  ：\n114  你的  220  your\n116  狗  192  dog\n118  咬  208  bite\n120  人  14  person\n122  吗  209  (QW)\n-1  ？\n126  女人  204  woman\n128  说  210  say\n-1  ：\n132  我的  211  my\n134  狗  192  dog\n136  不  198  not\n138  咬  208  bite\n140  人  14  person\n-1  。\n144  这时  212  at this time\n-1  ，\n146  那  213  that\n148  只  188  (MW)\n150  狗  192  dog\n152  咬  208  bite\n154  了  214  finish\n158  男人  15  man\n-1  。\n160  他  184  he\n164  气坏  215  furious\n166  了  214  (change)\n-1  ，\n168  大  191  strong\n170  叫  216  shout\n-1  ：\n172  你  207  you\n174  说  210  say\n178  你的  220  your\n180  狗  192  dog\n182  不  198  not\n184  咬  208  bite\n186  人  14  person\n-1  ！\n190  女人  204  woman\n194  回答  217  answer\n-1  ：\n196  这  218  this\n198  不  198  not\n200  是  219  be\n204  我的  211  my\n206  狗  192  dog\n-1  。',
    'https://learning-with-texts.sourceforge.io/media/manandthedog.mp3',
    'http://chinesepod.com/lessons/the-man-and-the-dog'
  );
INSERT INTO texts
VALUES
  (
    '3', '3', 'Die Leiden des jungen Werther',
    'Wie froh bin ich, daß ich weg bin! Bester Freund, was ist das Herz des Menschen! Dich zu verlassen, den ich so liebe, von dem ich unzertrennlich war, und froh zu sein! Ich weiß, du verzeihst mir\'s. Waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal, um ein Herz wie das meine zu ängstigen? Die arme Leonore! Und doch war ich unschuldig. Konnt\' ich dafür, daß, während die eigensinnigen Reize ihrer Schwester mir eine angenehme Unterhaltung verschafften, daß eine Leidenschaft in dem armen Herzen sich bildete? Und doch – bin ich ganz unschuldig? Hab\' ich nicht ihre Empfindungen genährt? Hab\' ich mich nicht an den ganz wahren Ausdrücken der Natur, die uns so oft zu lachen machten, so wenig lächerlich sie waren, selbst ergetzt? Hab\' ich nicht – o was ist der Mensch, daß er über sich klagen darf! Ich will, lieber Freund, ich verspreche dir\'s, ich will mich bessern, will nicht mehr ein bißchen Übel, das uns das Schicksal vorlegt, wiederkäuen, wie ich\'s immer getan habe; ich will das Gegenwärtige genießen, und das Vergangene soll mir vergangen sein. Gewiß, du hast recht, Bester, der Schmerzen wären minder unter den Menschen, wenn sie nicht – Gott weiß, warum sie so gemacht sind! – mit so viel Emsigkeit der Einbildungskraft sich beschäftigten, die Erinnerungen des vergangenen Übels zurückzurufen, eher als eine gleichgültige Gegenwart zu ertragen.\nDu bist so gut, meiner Mutter zu sagen, daß ich ihr Geschäft bestens betreiben und ihr ehstens Nachricht davon geben werde. Ich habe meine Tante gesprochen und bei weitem das böse Weib nicht gefunden, das man bei uns aus ihr macht. Sie ist eine muntere, heftige Frau von dem besten Herzen. Ich erklärte ihr meiner Mutter Beschwerden über den zurückgehaltenen Erbschaftsanteil; sie sagte mir ihre Gründe, Ursachen und die Bedingungen, unter welchen sie bereit wäre, alles herauszugeben, und mehr als wir verlangten – kurz, ich mag jetzt nichts davon schreiben, sage meiner Mutter, es werde alles gut gehen. Und ich habe, mein Lieber, wieder bei diesem kleinen Geschäft gefunden, daß Mißverständnisse und Trägheit vielleicht mehr Irrungen in der Welt machen als List und Bosheit. Wenigstens sind die beiden letzteren gewiß seltener.\nÜbrigens befinde ich mich hier gar wohl. Die Einsamkeit ist meinem Herzen köstlicher Balsam in dieser paradiesischen Gegend, und diese Jahreszeit der Jugend wärmt mit aller Fülle mein oft schauderndes Herz. Jeder Baum, jede Hecke ist ein Strauß von Blüten, und man möchte zum Maienkäfer werden, um in dem Meer von Wohlgerüchen herumschweben und alle seine Nahrung darin finden zu können.\nDie Stadt selbst ist unangenehm, dagegen rings umher eine unaussprechliche Schönheit der Natur. Das bewog den verstorbenen Grafen von M., einen Garten auf einem der Hügel anzulegen, die mit der schönsten Mannigfaltigkeit sich kreuzen und die lieblichsten Täler bilden. Der Garten ist einfach, und man fühlt gleich bei dem Eintritte, daß nicht ein wissenschaftlicher Gärtner, sondern ein fühlendes Herz den Plan gezeichnet, das seiner selbst hier genießen wollte. Schon manche Träne hab\' ich dem Abgeschiedenen in dem verfallenen Kabinettchen geweint, das sein Lieblingsplätzchen war und auch meines ist. Bald werde ich Herr vom Garten sein; der Gärtner ist mir zugetan, nur seit den paar Tagen, und er wird sich nicht übel dabei befinden.',
    '', 'https://learning-with-texts.sourceforge.io/media/werther.mp3',
    'http://www.gutenberg.org/ebooks/2407'
  );
INSERT INTO texts
VALUES
  (
    '4', '4', 'The Man and the Dog', '一天，一 个 男人 走 在 街上。 突然，他 看见 前面 有 一 只 黑色 的 大 狗，看起来 很 凶。 男人 非常 害怕，不敢 往前 走。 狗 的 旁边 站着 一 个 女人，男人 问 她： 你 的 狗 咬 人 吗？ 女人 说： 我 的 狗 不 咬 人。 这时，那 只 狗 咬 了 男人。 他 气坏 了，大 叫： 你 说 你 的 狗 不 咬 人！ 女人 回答： 这 不是 我 的 狗。',
    '', 'https://learning-with-texts.sourceforge.io/media/manandthedog.mp3',
    'http://chinesepod.com/lessons/the-man-and-the-dog'
  );
INSERT INTO texts
VALUES
  (
    '5', '5', 'Some expressions', 'はい。いいえ。\nすみません。\nどうも。\nありがとうございます。\n日本語を話しますか。はい、少し。\nイギリスから来ました。',
    '', 'https://learning-with-texts.sourceforge.io/media/jap.mp3',
    NULL
  );
INSERT INTO texts
VALUES
  (
    '6', '6', 'Test in Korean', '좋은 아침.\n안녕하세요.\n잘자요.\n잘가요.\n안녕하세요, 잘지냈어요?\n네, 잘지냈어요?\n네 그럼요.\n이름이 뭐에요?\n제 이름은 존이에요, 이름이 뭐에요?\n제 이름은 메리에요.',
    '', 'https://learning-with-texts.sourceforge.io/media/korean.mp3',
    NULL
  );
INSERT INTO texts
VALUES
  (
    '7', '7', 'Hello in Thai', 'ส วัส ดี ครับ\nส วัส ดี ค่ะ',
    '', 'https://learning-with-texts.sourceforge.io/media/thai.mp3',
    NULL
  );
INSERT INTO texts
VALUES
  (
    '8', '8', 'Greetings', 'בוקר טוב\nאחר צהריים טובים\nערב טוב\nלילה טוב\nלהתראות',
    '', 'https://learning-with-texts.sourceforge.io/media/hebrew.mp3',
    NULL
  );
INSERT INTO texts
VALUES
  (
    '9', '1', 'Mon premier don du sang (Short & annotated version)',
    'Bonjour Manon.\nBonjour.\nAlors, je crois qu’il y a pas longtemps, là, vous avez fait une bonne action ?\nOui. \nOn peut dire ça comme ça. Qu’est-ce que vous avez fait, alors ?\nAlors, j’ai fait mon premier don du sang.',
    '2  Bonjour  2  hello\n-1   \n4  Manon  1  *\n-1  . \n-1  ¶ \n7  Bonjour  2  hello\n-1  . \n-1  ¶ \n10  Alors  3  well\n-1  , \n12  je  7  I\n-1   \n14  crois  8  think\n-1   \n16  qu  6  that\n-1  \'\n22  il y a  4  there is\n-1   \n24  pas  170  (not)\n-1   \n26  longtemps  171  long time\n-1  , \n28  là  172  there\n-1  , \n30  vous  146  you\n-1   \n32  avez  150  have\n-1   \n34  fait  147  done\n-1   \n36  une  173  a\n-1   \n40  bonne action  46  good deed\n-1   ? \n-1  ¶ \n43  Oui  165  yes\n-1  . \n-1  ¶ \n46  On  166  one\n-1   \n48  peut  167  can\n-1   \n50  dire  26  say\n-1   \n52  ça  168  that\n-1   \n54  comme  169  as\n-1   \n56  ça  168  that\n-1  . \n64  Qu\'est-ce que  22  what\n-1   \n66  vous  146  you\n-1   \n68  avez  150  have\n-1   \n70  fait  147  done\n-1  , \n72  alors  3  then\n-1   ? \n-1  ¶ \n75  Alors  3  well\n-1  , \n77  j  174  I\n-1  \'\n79  ai  149  have\n-1   \n81  fait  147  made\n-1   \n83  mon  151  my\n-1   \n85  premier  175  first\n-1   \n91  don du sang  33  blood donation\n-1  .',
    'https://learning-with-texts.sourceforge.io/media/dondusang_short.mp3',
    'http://francebienvenue1.wordpress.com/2011/06/18/generosite/'
  );
DROP
  TABLE IF EXISTS texttags;
CREATE TABLE `texttags` (
  `TtTxID` int(11) unsigned NOT NULL,
  `TtT2ID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`TtTxID`, `TtT2ID`),
  KEY `TtTxID` (`TtTxID`),
  KEY `TtT2ID` (`TtT2ID`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
INSERT INTO texttags
VALUES
  ('1', '1');
INSERT INTO texttags
VALUES
  ('1', '4');
INSERT INTO texttags
VALUES
  ('2', '1');
INSERT INTO texttags
VALUES
  ('2', '5');
INSERT INTO texttags
VALUES
  ('2', '6');
INSERT INTO texttags
VALUES
  ('2', '9');
INSERT INTO texttags
VALUES
  ('3', '1');
INSERT INTO texttags
VALUES
  ('3', '3');
INSERT INTO texttags
VALUES
  ('3', '7');
INSERT INTO texttags
VALUES
  ('4', '1');
INSERT INTO texttags
VALUES
  ('4', '5');
INSERT INTO texttags
VALUES
  ('4', '6');
INSERT INTO texttags
VALUES
  ('5', '1');
INSERT INTO texttags
VALUES
  ('5', '2');
INSERT INTO texttags
VALUES
  ('6', '1');
INSERT INTO texttags
VALUES
  ('6', '2');
INSERT INTO texttags
VALUES
  ('7', '1');
INSERT INTO texttags
VALUES
  ('7', '2');
INSERT INTO texttags
VALUES
  ('8', '1');
INSERT INTO texttags
VALUES
  ('8', '2');
INSERT INTO texttags
VALUES
  ('9', '1');
INSERT INTO texttags
VALUES
  ('9', '4');
INSERT INTO texttags
VALUES
  ('9', '9');
DROP
  TABLE IF EXISTS words;
CREATE TABLE `words` (
  `WoID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `WoLgID` int(11) unsigned NOT NULL,
  `WoText` varchar(250) NOT NULL,
  `WoTextLC` varchar(250) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `WoStatus` tinyint(4) NOT NULL,
  `WoTranslation` varchar(500) NOT NULL DEFAULT '*',
  `WoRomanization` varchar(100) DEFAULT NULL,
  `WoSentence` varchar(1000) DEFAULT NULL,
  `WoCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `WoStatusChanged` timestamp NOT NULL DEFAULT '1970-01-01 01:00:01',
  `WoTodayScore` double NOT NULL DEFAULT '0',
  `WoTomorrowScore` double NOT NULL DEFAULT '0',
  `WoRandom` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`WoID`),
  UNIQUE KEY `WoLgIDTextLC` (`WoLgID`, `WoTextLC`),
  KEY `WoLgID` (`WoLgID`),
  KEY `WoStatus` (`WoStatus`),
  KEY `WoTextLC` (`WoTextLC`),
  KEY `WoTranslation` (
    `WoTranslation`(333)
  ),
  KEY `WoCreated` (`WoCreated`),
  KEY `WoStatusChanged` (`WoStatusChanged`),
  KEY `WoTodayScore` (`WoTodayScore`),
  KEY `WoTomorrowScore` (`WoTomorrowScore`),
  KEY `WoRandom` (`WoRandom`)
) ENGINE = MyISAM AUTO_INCREMENT = 221 DEFAULT CHARSET = utf8;
INSERT INTO words
VALUES
  (
    '1', '1', 'Manon', 'manon', '98', '(name)',
    NULL, 'Bonjour {Manon}.', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '100', '100',
    '0.77861288634931'
  );
INSERT INTO words
VALUES
  (
    '2', '1', 'bonjour', 'bonjour', '5',
    'hello / good morning / good afternoon',
    NULL, '{Bonjour} Manon.', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.15276275961917152'
  );
INSERT INTO words
VALUES
  (
    '3', '1', 'alors', 'alors', '5', 'then / in that case / at the time / else / if not / my goodness / well',
    NULL, '{Alors}, je crois qu\'il y a pas longtemps, là, vous avez fait une bonne action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.42797516978157235'
  );
INSERT INTO words
VALUES
  (
    '4', '1', 'il y a', 'il y a', '2', 'there is / there are',
    NULL, 'Alors, je crois qu\'{il y a} pas longtemps, là, vous avez fait une bonne action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.6815876007839922'
  );
INSERT INTO words
VALUES
  (
    '170', '1', 'pas', 'pas', '2', '(not) / footstep / step / walk',
    NULL, 'Alors, je crois qu\'il y a {pas} longtemps, là, vous avez fait une bonne action ?',
    '2013-03-27 12:21:30', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.5153064034090437'
  );
INSERT INTO words
VALUES
  (
    '6', '1', 'qu', 'qu', '5', 'that / how / so that',
    NULL, 'Alors, je crois {qu}\'il y a pas longtemps, là, vous avez fait une bonne action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.1240125253088889'
  );
INSERT INTO words
VALUES
  (
    '7', '1', 'je', 'je', '5', 'I', NULL,
    'Alors, {je} crois qu\'il y a pas longtemps, là, vous avez fait une bonne action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.5752998549261129'
  );
INSERT INTO words
VALUES
  (
    '8', '1', 'crois', 'crois', '4', 'believe / think',
    NULL, 'Alors, je {crois} qu\'il y a pas longtemps, là, vous avez fait une bonne action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.5044617294375429'
  );
INSERT INTO words
VALUES
  (
    '9', '2', '一', '一', '2', 'one / a',
    NULL, '{一}天，{一}个男人走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.7964091131430204'
  );
INSERT INTO words
VALUES
  (
    '10', '2', '天', '天', '2', 'sky, day',
    NULL, '一{天}，一个男人走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.4686604081361186'
  );
INSERT INTO words
VALUES
  (
    '11', '2', '一天', '一天', '1',
    'one day', NULL, '{一天}，一个男人走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9540747319851767'
  );
INSERT INTO words
VALUES
  (
    '12', '2', '个', '个', '2', '(MW)',
    NULL, '一天，一{个}男人走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.36439246625117255'
  );
INSERT INTO words
VALUES
  (
    '13', '2', '男', '男', '2', 'male',
    NULL, '一天，一个{男}人走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.9597381660339778'
  );
INSERT INTO words
VALUES
  (
    '14', '2', '人', '人', '2', 'human being / person',
    NULL, '一天，一个男{人}走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.7055134621500163'
  );
INSERT INTO words
VALUES
  (
    '15', '2', '男人', '男人', '2',
    'man', NULL, '一天，一个{男人}走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.6483528433817931'
  );
INSERT INTO words
VALUES
  (
    '16', '2', '走', '走', '2', 'walk',
    NULL, '一天，一个男人{走}在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.12522386119256157'
  );
INSERT INTO words
VALUES
  (
    '17', '2', '在', '在', '2', 'be / live/ at',
    NULL, '一天，一个男人走{在}街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.6810608065510735'
  );
INSERT INTO words
VALUES
  (
    '18', '2', '街', '街', '1', 'street',
    NULL, '一天，一个男人走在{街}上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.029632479911327808'
  );
INSERT INTO words
VALUES
  (
    '19', '2', '上', '上', '2', 'on / in / go up / upper part / last',
    NULL, '一天，一个男人走在街{上}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.10498001063706354'
  );
INSERT INTO words
VALUES
  (
    '20', '2', '街上', '街上', '1',
    'on the street', NULL, '一天，一个男人走在{街上}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.4360026441849793'
  );
INSERT INTO words
VALUES
  (
    '21', '2', '突然', '突然', '2',
    'suddenly', NULL, '{突然}，他看见前面有一只黑色的大狗，看起来很凶。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.8650732197473507'
  );
INSERT INTO words
VALUES
  (
    '22', '1', 'qu\'est-ce que', 'qu\'est-ce que',
    '4', 'what', NULL, '{Qu\'est-ce que} vous avez fait, alors ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.017358196915460936'
  );
INSERT INTO words
VALUES
  (
    '23', '1', 'est-ce que', 'est-ce que',
    '4', 'is it that', NULL, 'Qu\'{est-ce que} vous avez fait, alors ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.4915713560688974'
  );
INSERT INTO words
VALUES
  (
    '24', '1', 'est-ce', 'est-ce', '3',
    'is that / is this', NULL, 'Qu\'{est-ce} que vous avez fait, alors ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '20.067133683596026', '17.74023970358721',
    '0.40578222033174915'
  );
INSERT INTO words
VALUES
  (
    '25', '1', 'est', 'est', '1', 'is / east',
    NULL, 'Qu\'{est}-ce que vous avez fait, alors ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5541970641856986'
  );
INSERT INTO words
VALUES
  (
    '26', '1', 'dire', 'dire', '3', 'say / tell',
    NULL, 'On peut {dire} ça comme ça.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '20.067133683596026', '17.74023970358721',
    '0.5536386906668904'
  );
INSERT INTO words
VALUES
  (
    '27', '3', 'wie', 'wie', '2', 'how',
    NULL, '{Wie} froh bin ich, daß ich weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.10560229151100134'
  );
INSERT INTO words
VALUES
  (
    '28', '3', 'froh', 'froh', '2', 'happy / glad',
    NULL, 'Wie {froh} bin ich, daß ich weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.8670954162879805'
  );
INSERT INTO words
VALUES
  (
    '29', '3', 'bin', 'bin', '2', 'am', NULL,
    'Wie froh {bin} ich, daß ich weg {bin}!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.01867023764054313'
  );
INSERT INTO words
VALUES
  (
    '30', '3', 'ich', 'ich', '2', 'I', NULL,
    'Wie froh bin {ich}, daß {ich} weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.49206497007241934'
  );
INSERT INTO words
VALUES
  (
    '31', '3', 'daß', 'daß', '2', 'that / so that',
    NULL, 'Wie froh bin ich, {daß} ich weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.40431416817411236'
  );
INSERT INTO words
VALUES
  (
    '32', '3', 'weg', 'weg', '2', 'away / gone / path / way',
    NULL, 'Wie froh bin ich, daß ich {weg} bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.5453759613869488'
  );
INSERT INTO words
VALUES
  (
    '33', '1', 'don du sang', 'don du sang',
    '2', 'blood donation', NULL, 'Alors, j\'ai fait mon premier {don du sang}.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.5139373331460518'
  );
INSERT INTO words
VALUES
  (
    '34', '4', '一天', '一天', '1',
    'one day', 'yìtiān', '{一天}，一个男人走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9335588123030577'
  );
INSERT INTO words
VALUES
  (
    '35', '4', '男人', '男人', '1',
    'man', 'nánrén', '一天，一个{男人}走在街上。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.12598209281077805'
  );
INSERT INTO words
VALUES
  (
    '36', '4', '突然', '突然', '1',
    'suddenly', 'tūrán', '{突然}，他看见前面有一只黑色的大狗，看起来很凶。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.829234057878362'
  );
INSERT INTO words
VALUES
  (
    '37', '4', '他', '他', '1', 'he', 'tā',
    '{他}气坏了，大叫：', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '0', '-6.980681940026449',
    '0.7682240416931212'
  );
INSERT INTO words
VALUES
  (
    '38', '4', '这时', '这时', '1',
    'at this moment', 'zhèshí', '{这时}，那只狗咬了男人。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.35341806556416494'
  );
INSERT INTO words
VALUES
  (
    '39', '4', '狗', '狗', '1', 'dog',
    'gǒu', '{狗}的旁边站着一个女人，男人问她：',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.4624182334751061'
  );
INSERT INTO words
VALUES
  (
    '40', '4', '女人', '女人', '1',
    'woman', 'nǚrén', '{女人}说：',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.2518370014166804'
  );
INSERT INTO words
VALUES
  (
    '41', '4', '看起来', '看起来',
    '1', 'it seems / it appears / it looks as if',
    'kànqǐlái', '突然，他看见前面有一只黑色的大狗，{看起来}很凶。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8719303373917289'
  );
INSERT INTO words
VALUES
  (
    '42', '4', '凶', '凶', '1', 'ferocious, fierce',
    'xiōng', '突然，他看见前面有一只黑色的大狗，看起来很{凶}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6041407134422481'
  );
INSERT INTO words
VALUES
  (
    '43', '1', 'c\'est', 'c\'est', '1',
    'it\'s / that\'s', NULL, '{C\'est} quoi ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.4049125857696986'
  );
INSERT INTO words
VALUES
  (
    '44', '1', 'c', 'c', '5', 'that / it',
    NULL, '{C}\'est quoi ?', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.21214081925539377'
  );
INSERT INTO words
VALUES
  (
    '45', '1', 'quoi', 'quoi', '3', 'what',
    NULL, 'C\'est {quoi} ?', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '20.067133683596026',
    '17.74023970358721', '0.8459663697015181'
  );
INSERT INTO words
VALUES
  (
    '46', '1', 'bonne action', 'bonne action',
    '2', 'good deed', NULL, 'Alors, je crois qu\'il y a pas longtemps, là, vous avez fait une {bonne action} ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.5934094214750542'
  );
INSERT INTO words
VALUES
  (
    '47', '4', '你', '你', '1', 'you',
    'nǐ', '{你}的狗咬人吗？',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.4291480290043615'
  );
INSERT INTO words
VALUES
  (
    '48', '4', '你的', '你的', '1',
    'your', 'nǐde', '{你的}狗咬人吗？',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.36551191133029004'
  );
INSERT INTO words
VALUES
  (
    '49', '4', '的', '的', '5', 'of / \'s / (Part.)',
    'de', '你{的}狗咬人吗？',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.5401155003720107'
  );
INSERT INTO words
VALUES
  (
    '50', '1', 'il', 'il', '1', 'he', NULL,
    '{Il} faut attendre trois mois.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6040417986028286'
  );
INSERT INTO words
VALUES
  (
    '51', '1', 'ils', 'ils', '1', 'they',
    NULL, 'A la fin, {ils} vous enlèvent la piqure.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.3998625226317556'
  );
INSERT INTO words
VALUES
  (
    '52', '5', 'フランス', 'フランス',
    '1', 'France', 'ふらんす', '私は{フランス}から来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.18718724808393722'
  );
INSERT INTO words
VALUES
  (
    '53', '5', 'はい', 'はい', '1',
    'yes / OK / okay', 'hai', '{はい}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.7363487032580643'
  );
INSERT INTO words
VALUES
  (
    '103', '5', '日', '日', '3', 'day / Japan / sun',
    'kun: -か、 ひ、 -び / on: ジツ、 ニチ / names: あ、 あき、 いる、 く、 くさ、 こう、 す、 たち、 に、 にっ、 につ、 へ',
    '{日}本語を話しますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '20.067133683596026', '17.74023970358721',
    '0.5647589448511218'
  );
INSERT INTO words
VALUES
  (
    '54', '5', 'は', 'は', '5', '(Hiragana: ha) / (topic m.)',
    'ha // わ / wa', '私{は}イギリスから来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.12018180277215484'
  );
INSERT INTO words
VALUES
  (
    '55', '5', 'い', 'い', '5', '(Hiragana: i)',
    'i', 'は{い}。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.3918629348202263'
  );
INSERT INTO words
VALUES
  (
    '56', '5', 'え', 'え', '5', '(Hiragana: e)',
    'e', 'いい{え}。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.5987692965183121'
  );
INSERT INTO words
VALUES
  (
    '57', '5', 'す', 'す', '5', '(Hiragana: su)',
    'su', '{す}みません。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.8182577088645265'
  );
INSERT INTO words
VALUES
  (
    '58', '5', 'み', 'み', '5', '(Hiragana: mi)',
    'mi', 'す{み}ません。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.29498068550134143'
  );
INSERT INTO words
VALUES
  (
    '59', '5', 'ま', 'ま', '5', '(Hiragana: ma)',
    'ma', 'すみ{ま}せん。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.02013033164677241'
  );
INSERT INTO words
VALUES
  (
    '60', '5', 'せ', 'せ', '5', '(Hiragana: se)',
    'se', 'すみま{せ}ん。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.2157096324634828'
  );
INSERT INTO words
VALUES
  (
    '61', '5', 'ん', 'ん', '5', '(Hiragana: n)',
    'n', 'すみませ{ん}。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.01815719811074175'
  );
INSERT INTO words
VALUES
  (
    '62', '5', 'いいえ', 'いいえ',
    '1', 'no / nay', 'iie', '{いいえ}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.4436571238969053'
  );
INSERT INTO words
VALUES
  (
    '63', '5', 'すみません', 'すみません',
    '3', 'sorry / excuse me', 'sumimasen',
    '{すみません}。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '20.067133683596026',
    '17.74023970358721', '0.16381405588594644'
  );
INSERT INTO words
VALUES
  (
    '64', '5', 'ど', 'ど', '5', '(Hiragana: do)',
    'do', '{ど}うも。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.4880989384726611'
  );
INSERT INTO words
VALUES
  (
    '65', '5', 'う', 'う', '5', '(Hiragana: u)',
    'u', 'ど{う}も。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.9490525554391114'
  );
INSERT INTO words
VALUES
  (
    '66', '5', 'も', 'も', '5', '(Hiragana: mo)',
    'mo', 'どう{も}。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.2809659925112184'
  );
INSERT INTO words
VALUES
  (
    '104', '5', '本', '本', '3', 'book / main / origin / present / real / true',
    'kun: もと / on: ホン / names: まと',
    '日{本}語を話しますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '20.067133683596026', '17.74023970358721',
    '0.12560323916897478'
  );
INSERT INTO words
VALUES
  (
    '68', '5', 'あ', 'あ', '5', '(Hiragana: a)',
    'a', '{あ}りがとうございます。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.557672326972403'
  );
INSERT INTO words
VALUES
  (
    '69', '5', 'り', 'り', '5', '(Hiragana: ri)',
    'ri', 'あ{り}がとうございます。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.9454636880620044'
  );
INSERT INTO words
VALUES
  (
    '70', '5', 'が', 'が', '5', '(Hiragana: ga)',
    'ga', 'あり{が}とうございます。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.054301490126458456'
  );
INSERT INTO words
VALUES
  (
    '71', '5', 'と', 'と', '5', '(Hiragana: to)',
    'to', 'ありが{と}うございます。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.4351164171799239'
  );
INSERT INTO words
VALUES
  (
    '72', '5', 'ありがとう', 'ありがとう',
    '1', 'thank you', 'arigatou', '{ありがとう}ございます。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.012677646253889098'
  );
INSERT INTO words
VALUES
  (
    '73', '5', 'ご', 'ご', '5', '(Hiragana: go)',
    'go', 'ありがとう{ご}ざいます。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.7580390104633188'
  );
INSERT INTO words
VALUES
  (
    '74', '5', 'ざ', 'ざ', '5', '(Hiragana: za)',
    'za', 'ありがとうご{ざ}います。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.7521621442885716'
  );
INSERT INTO words
VALUES
  (
    '75', '5', 'ございます', 'ございます',
    '1', '(polite) be / exist', 'gozaimasu',
    'ありがとう{ございます}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.48669372078654705'
  );
INSERT INTO words
VALUES
  (
    '108', '5', 'どうも', 'どうも',
    '1', 'thanks', 'doumo', '{どうも}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.4154293177793075'
  );
INSERT INTO words
VALUES
  (
    '77', '5', 'た', 'た', '5', '(Hiragana: ta)',
    'ta', 'どうい{た}しまして。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.17698220180066507'
  );
INSERT INTO words
VALUES
  (
    '78', '5', 'し', 'し', '5', '(Hiragana: shi)',
    'shi', 'どういた{し}ま{し}て。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.4248298773773293'
  );
INSERT INTO words
VALUES
  (
    '79', '5', 'て', 'て', '5', '(Hiragana: te)',
    'te', 'どういたしまし{て}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.5932028122182962'
  );
INSERT INTO words
VALUES
  (
    '109', '5', '来ました', '来ました',
    '1', 'came', 'きました / kimashita',
    'イギリスから{来ました}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.10249195536830645'
  );
INSERT INTO words
VALUES
  (
    '83', '5', '日本', '日本', '1',
    'Japan', 'にほん / nihon', '{日本}語を話しますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.691524459693138'
  );
INSERT INTO words
VALUES
  (
    '85', '5', '日本語', '日本語',
    '1', 'Japanese lang.', 'にほんご / nihongo',
    '{日本語}を話しますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6780138925444464'
  );
INSERT INTO words
VALUES
  (
    '86', '5', 'を', 'を', '5', '(Hiragana: wo), (direct object)',
    'wo', '日本語{を}話しますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.3154961143764631'
  );
INSERT INTO words
VALUES
  (
    '87', '5', 'か', 'か', '5', '(Hiragana: ka) / (question)',
    'ka', '日本語を話します{か}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.5434389249826213'
  );
INSERT INTO words
VALUES
  (
    '88', '5', 'ます', 'ます', '1',
    '(respect)', 'masu', '日本語を話し{ます}か。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.770706312517362'
  );
INSERT INTO words
VALUES
  (
    '89', '5', '話し', '話し', '1',
    'talk / speech', 'はなし / hanashi',
    '日本語を{話し}ますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.22321481837259122'
  );
INSERT INTO words
VALUES
  (
    '105', '5', '語', '語', '4', 'language / speech / word',
    'kun: かた.らう、 かた.る / on: ゴ',
    '日本{語}を話しますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.9337393920251535'
  );
INSERT INTO words
VALUES
  (
    '106', '5', '話', '話', '3', 'tale / talk',
    'kun: はなし、 はな.す / on: ワ',
    '日本語を{話}しますか。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '20.067133683596026', '17.74023970358721',
    '0.2918872733524882'
  );
INSERT INTO words
VALUES
  (
    '92', '5', '少し', '少し', '1',
    'small quantity / little / few',
    'すこし / sukoshi', 'はい、{少し}。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8039551850445151'
  );
INSERT INTO words
VALUES
  (
    '93', '5', '少', '少', '1', 'few / little',
    'kun: すく.ない、 すこ.し / on: ショウ',
    'はい、{少}し。', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '0', '-6.980681940026449',
    '0.3501315008384469'
  );
INSERT INTO words
VALUES
  (
    '94', '5', '私', '私', '1', 'I / me',
    'あたし / watashi', '{私}はイギリスから来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.3387919797923341'
  );
INSERT INTO words
VALUES
  (
    '96', '5', 'ギ', 'ギ', '5', '(Katakana: gi)',
    'ぎ / gi', '私はイ{ギ}リスから来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.6435654271799749'
  );
INSERT INTO words
VALUES
  (
    '97', '5', 'イギリス', 'イギリス',
    '1', 'Great Britain / United Kingdom',
    'igirisu', '私は{イギリス}から来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.20145122725651715'
  );
INSERT INTO words
VALUES
  (
    '98', '5', 'イ', 'イ', '5', '(Katakana: i)',
    'い / i', '私は{イ}ギリスから来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.07655988547630597'
  );
INSERT INTO words
VALUES
  (
    '99', '5', 'リ', 'リ', '5', '(Katakana: ri)',
    'ri', '私はイギ{リ}スから来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.7784457763456234'
  );
INSERT INTO words
VALUES
  (
    '100', '5', 'ス', 'ス', '5', '(Katakana: su)',
    'su', '私はイギリ{ス}から来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.6625492560328443'
  );
INSERT INTO words
VALUES
  (
    '101', '5', 'ら', 'ら', '5', '(Hiragana: ra)',
    'ra', '私はイギリスか{ら}来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.9774089818609962'
  );
INSERT INTO words
VALUES
  (
    '102', '5', 'から', 'から', '1',
    'from', 'kara', '私はイギリス{から}来ています。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.899397171940093'
  );
INSERT INTO words
VALUES
  (
    '107', '5', '来', '来', '1', 'become / cause / come / due / next',
    'kun: き、 きた.す、 き.たす、 きた.る、 き.たる、 く.る、 こ / on: タイ、 ライ / names: くり、 くる、 ごろ、 さ',
    'イギリスから{来}ました。',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6582182214206255'
  );
INSERT INTO words
VALUES
  (
    '110', '3', 'wie froh', 'wie froh',
    '1', 'how happy', NULL, '{Wie froh} bin ich, daß ich weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.2661718542372546'
  );
INSERT INTO words
VALUES
  (
    '111', '3', 'wie froh bin', 'wie froh bin',
    '1', 'how happy am', NULL, '{Wie froh bin} ich, daß ich weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.023383435814998518'
  );
INSERT INTO words
VALUES
  (
    '112', '3', 'wie froh bin ich', 'wie froh bin ich',
    '1', 'how happy I am', NULL, '{Wie froh bin ich}, daß ich weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.31840164709687385'
  );
INSERT INTO words
VALUES
  (
    '113', '3', 'wie froh bin ich, daß',
    'wie froh bin ich, daß', '1', 'how happy I am that',
    NULL, '{Wie froh bin ich, daß} ich weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5218579587730188'
  );
INSERT INTO words
VALUES
  (
    '114', '3', 'wie froh bin ich, daß ich',
    'wie froh bin ich, daß ich', '1',
    'how happy I am that I', NULL, '{Wie froh bin ich, daß ich} weg bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6540848833081172'
  );
INSERT INTO words
VALUES
  (
    '115', '3', 'wie froh bin ich, daß ich weg',
    'wie froh bin ich, daß ich weg',
    '1', 'how happy I am that I (am) gone',
    NULL, '{Wie froh bin ich, daß ich weg} bin!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.7048505709551746'
  );
INSERT INTO words
VALUES
  (
    '116', '3', 'wie froh bin ich, daß ich weg bin',
    'wie froh bin ich, daß ich weg bin',
    '1', 'how happy I am that I am gone',
    NULL, '{Wie froh bin ich, daß ich weg bin}!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5619982355851664'
  );
INSERT INTO words
VALUES
  (
    '117', '3', 'waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal',
    'waren nicht meine übrigen verbindungen recht ausgesucht vom schicksal',
    '1', 'have not other attachments been specially appointed by fate',
    NULL, '{Waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal}, um ein Herz wie das meine zu ängstigen?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.695439495793953'
  );
INSERT INTO words
VALUES
  (
    '118', '3', 'um ein Herz wie das meine zu ängstigen',
    'um ein herz wie das meine zu ängstigen',
    '1', 'to torment a head like mine',
    NULL, 'Waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal, {um ein Herz wie das meine zu ängstigen}?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.7912028029479112'
  );
INSERT INTO words
VALUES
  (
    '119', '3', 'Waren', 'waren', '99',
    'were / goods', NULL, '{Waren} nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal, um ein Herz wie das meine zu ängstigen?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.869695558091342'
  );
INSERT INTO words
VALUES
  (
    '120', '3', 'nicht', 'nicht', '99',
    'not', NULL, 'Waren {nicht} meine übrigen Verbindungen recht ausgesucht vom Schicksal, um ein Herz wie das meine zu ängstigen?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.9748694123466214'
  );
INSERT INTO words
VALUES
  (
    '121', '3', 'meine', 'meine', '99',
    'my', NULL, 'Waren nicht {meine} übrigen Verbindungen recht ausgesucht vom Schicksal, um ein Herz wie das {meine} zu ängstigen?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.265260418192726'
  );
INSERT INTO words
VALUES
  (
    '122', '3', 'übrigen', 'übrigen',
    '99', 'others', NULL, 'Waren nicht meine {übrigen} Verbindungen recht ausgesucht vom Schicksal, um ein Herz wie das meine zu ängstigen?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.4016938846574108'
  );
INSERT INTO words
VALUES
  (
    '123', '3', 'um', 'um', '99', 'to', NULL,
    'Waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal, {um} ein Herz wie das meine zu ängstigen?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.21268819944252093'
  );
INSERT INTO words
VALUES
  (
    '124', '3', 'ein', 'ein', '99', 'a / one',
    NULL, 'Waren nicht meine übrigen Verbindungen recht ausgesucht vom Schicksal, um {ein} Herz wie das meine zu ängstigen?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.8583593739740172'
  );
INSERT INTO words
VALUES
  (
    '125', '3', 'Herz', 'herz', '99', 'heart',
    NULL, 'Bester Freund, was ist das {Herz} des Menschen!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.6537323022761683'
  );
INSERT INTO words
VALUES
  (
    '126', '3', 'Leonore', 'leonore', '98',
    '*', NULL, 'Die arme {Leonore}!',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '100', '100', '0.6935834201924348'
  );
INSERT INTO words
VALUES
  (
    '127', '6', '좋은', '좋은', '1',
    'good', 'joh-eun', '{좋은} 아침.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5067202248673143'
  );
INSERT INTO words
VALUES
  (
    '128', '6', '아침', '아침', '1',
    'morning', 'achim', '좋은 {아침}.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.452850894492912'
  );
INSERT INTO words
VALUES
  (
    '129', '6', '좋은 아침', '좋은 아침',
    '1', 'good morning', 'joh-eun achim',
    '{좋은 아침}.', '2011-08-30 12:00:00',
    '2020-10-03 18:08:22', '0', '-6.980681940026449',
    '0.744093828596262'
  );
INSERT INTO words
VALUES
  (
    '130', '6', '안녕하세요', '안녕하세요',
    '1', 'how are you / hello / good day',
    'annyeonghaseyo', '{안녕하세요}.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.36191649023621947'
  );
INSERT INTO words
VALUES
  (
    '131', '6', '잘지냈어요', '잘지냈어요',
    '1', 'how are you', 'jaljinaess-eoyo',
    '안녕하세요, {잘지냈어요}?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5773009961259561'
  );
INSERT INTO words
VALUES
  (
    '132', '6', '잘자요', '잘자요',
    '1', 'good night', 'jaljayo', '{잘자요}.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8007555406547668'
  );
INSERT INTO words
VALUES
  (
    '133', '6', '잘가요', '잘가요',
    '1', 'good bye', 'jalgayo', '{잘가요}.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.27187474562961117'
  );
INSERT INTO words
VALUES
  (
    '134', '6', '네', '네', '1', 'four / yes / ok / you(r)',
    'ne', '{네}, 잘지냈어요?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9571071369174003'
  );
INSERT INTO words
VALUES
  (
    '135', '6', '그럼요', '그럼요',
    '1', 'by all means / without fail / certainly / definitely',
    'geuleom-yo', '네 {그럼요}.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9699114784318129'
  );
INSERT INTO words
VALUES
  (
    '136', '7', 'ดี', 'ดี', '1',
    'good / is good', 'diː', 'สวัส{ดี}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9782360121405087'
  );
INSERT INTO words
VALUES
  (
    '137', '7', 'สวัส', 'สวัส',
    '1', 'blessing / good fortune',
    'sà wàt', '{สวัส}ดี',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.98144565614075'
  );
INSERT INTO words
VALUES
  (
    '139', '7', 'ครับ', 'ครับ',
    '1', '(polite M)', 'kʰráp', 'สวัสดี{ครับ}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9725202750158685'
  );
INSERT INTO words
VALUES
  (
    '140', '7', 'ค่ะ', 'ค่ะ',
    '1', '(polite, F)', 'kʰáʔ', 'สวัสดี{ค่ะ}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9182644373907376'
  );
INSERT INTO words
VALUES
  (
    '141', '7', 'สวัสดีครับ',
    'สวัสดีครับ',
    '1', 'hello (M) / goodbye (M)',
    'sà wàt diː kʰráp', '{สวัสดีครับ}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6737613926397277'
  );
INSERT INTO words
VALUES
  (
    '142', '7', 'สวัสดีค่ะ',
    'สวัสดีค่ะ',
    '1', 'hello (F) / goodbye (F)',
    'sà wàt diː kʰáʔ', '{สวัสดีค่ะ}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6140136817600705'
  );
INSERT INTO words
VALUES
  (
    '145', '7', 'วัสดี', 'วัสดี',
    '1', 'hi / hey / (inf. abbrev.)',
    'wàt diː', 'ส{วัสดี}ครับ',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.048784261614814646'
  );
INSERT INTO words
VALUES
  (
    '146', '1', 'vous', 'vous', '5', 'you / you all',
    NULL, 'Alors, je crois qu\'il y a pas longtemps, là, {vous} avez fait une bonne action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.40188029352750654'
  );
INSERT INTO words
VALUES
  (
    '147', '1', 'fait', 'fait', '4', 'made / done / make / fact / occurrence',
    NULL, 'Alors, je crois qu\'il y a pas longtemps, là, vous avez {fait} une bonne action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.8630487135267338'
  );
INSERT INTO words
VALUES
  (
    '148', '1', 'bonne', 'bonne', '2', 'good',
    NULL, 'Alors, je crois qu\'il y a pas longtemps, là, vous avez fait une {bonne} action ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.10960271778479472'
  );
INSERT INTO words
VALUES
  (
    '149', '1', 'ai', 'ai', '3', 'have',
    NULL, 'Alors, j\'{ai} fait mon premier don du sang.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '20.067133683596026', '17.74023970358721',
    '0.9588674790774169'
  );
INSERT INTO words
VALUES
  (
    '150', '1', 'avez', 'avez', '4', 'have',
    NULL, 'Qu\'est-ce que vous {avez} fait, alors ?',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.4655292727663454'
  );
INSERT INTO words
VALUES
  (
    '151', '1', 'mon', 'mon', '4', 'my',
    NULL, 'Alors, j\'ai fait {mon} premier don du sang.',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.4510439573331214'
  );
INSERT INTO words
VALUES
  (
    '152', '8', 'טוב', 'טוב', '1',
    'good', 'tov', 'בוקר {טוב}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8586319991002157'
  );
INSERT INTO words
VALUES
  (
    '153', '8', 'בוקר', 'בוקר',
    '1', 'morning', 'boker', '{בוקר} טוב',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9400281542353595'
  );
INSERT INTO words
VALUES
  (
    '154', '8', 'ערב', 'ערב', '1',
    'evening', 'erev', '{ערב} טוב',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.12424480460979492'
  );
INSERT INTO words
VALUES
  (
    '155', '8', 'לילה', 'לילה',
    '1', 'night', 'laila', '{לילה} טוב',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8011395910765413'
  );
INSERT INTO words
VALUES
  (
    '156', '8', 'להתראות', 'להתראות',
    '1', 'good bye', 'lehitraot', '{להתראות}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6329635722869668'
  );
INSERT INTO words
VALUES
  (
    '157', '8', 'טובים', 'טובים',
    '1', 'good', 'tovim', 'אחר צהריים {טובים}',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.761399118938855'
  );
INSERT INTO words
VALUES
  (
    '158', '8', 'צהריים', 'צהריים',
    '1', 'noon', 'tzahara\'im', 'אחר {צהריים} טובים',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9081049085670196'
  );
INSERT INTO words
VALUES
  (
    '159', '8', 'אחר', 'אחר', '1',
    'after / other', 'achar', '{אחר} צהריים טובים',
    '2011-08-30 12:00:00', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.25632721675217823'
  );
INSERT INTO words
VALUES
  (
    '160', '8', 'בוקר טוב', 'בוקר טוב',
    '1', 'good morning', 'boker tov',
    '{בוקר טוב}', '2011-09-02 19:00:09',
    '2020-10-03 18:08:22', '0', '-6.980681940026449',
    '0.5573213887934773'
  );
INSERT INTO words
VALUES
  (
    '161', '8', 'אחר צהריים טובים',
    'אחר צהריים טובים',
    '1', 'good afternoon', 'achar tzahara\'im tovim',
    '{אחר צהריים טובים}',
    '2011-09-02 19:00:50', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.017625324444496375'
  );
INSERT INTO words
VALUES
  (
    '162', '8', 'ערב טוב', 'ערב טוב',
    '1', 'good evening', 'erev tov',
    '{ערב טוב}', '2011-09-02 19:01:21',
    '2020-10-03 18:08:22', '0', '-6.980681940026449',
    '0.4161624865756952'
  );
INSERT INTO words
VALUES
  (
    '163', '8', 'לילה טוב', 'לילה טוב',
    '1', 'good night', 'laila tov', '{לילה טוב}',
    '2011-09-02 19:01:50', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.027936490278631907'
  );
INSERT INTO words
VALUES
  (
    '164', '8', 'אחר צהריים',
    'אחר צהריים', '1', 'afternoon',
    'achar tzahara\'im', '{אחר צהריים} טובים',
    '2011-09-02 19:21:20', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8911950223997189'
  );
INSERT INTO words
VALUES
  (
    '165', '1', 'Oui', 'oui', '5', 'yes',
    NULL, '{Oui}.', '2013-03-27 12:13:44',
    '2020-10-03 18:08:22', '99.99999999999999',
    '98.6038636119947', '0.37216567189634375'
  );
INSERT INTO words
VALUES
  (
    '166', '1', 'On', 'on', '4', 'someone / somebody / you / one / we',
    NULL, '{On} peut dire ça comme ça.',
    '2013-03-27 12:14:40', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.18724332301620703'
  );
INSERT INTO words
VALUES
  (
    '167', '1', 'peut', 'peut', '5', 'can / may',
    NULL, 'On {peut} dire ça comme ça.',
    '2013-03-27 12:15:10', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.819719630125649'
  );
INSERT INTO words
VALUES
  (
    '168', '1', 'ça', 'ça', '4', 'that / this / it',
    NULL, 'On peut dire {ça} comme {ça}.',
    '2013-03-27 12:16:08', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.5368682123132685'
  );
INSERT INTO words
VALUES
  (
    '169', '1', 'comme', 'comme', '3', 'as / just as / like / how',
    NULL, 'On peut dire ça {comme} ça.',
    '2013-03-27 12:16:47', '2020-10-03 18:08:22',
    '20.067133683596026', '17.74023970358721',
    '0.22518220192304086'
  );
INSERT INTO words
VALUES
  (
    '171', '1', 'longtemps', 'longtemps',
    '5', 'long time / long while / long',
    NULL, 'Alors, je crois qu\'il y a pas {longtemps}, là, vous avez fait une bonne action ?',
    '2013-03-27 12:22:02', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.900985442009741'
  );
INSERT INTO words
VALUES
  (
    '172', '1', 'là', 'là', '1', 'there',
    NULL, 'Alors, je crois qu\'il y a pas longtemps, {là}, vous avez fait une bonne action ?',
    '2013-03-27 12:22:35', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9590080305552184'
  );
INSERT INTO words
VALUES
  (
    '173', '1', 'une', 'une', '5', 'a / an / one / front page',
    NULL, 'Alors, je crois qu\'il y a pas longtemps, là, vous avez fait {une} bonne action ?',
    '2013-03-27 12:24:25', '2020-10-03 18:08:22',
    '99.99999999999999', '98.6038636119947',
    '0.09208385748051466'
  );
INSERT INTO words
VALUES
  (
    '174', '1', 'j', 'j', '99', 'I', NULL,
    'Alors, {j}\'ai fait mon premier don du sang …',
    '2013-03-27 12:26:34', '2020-10-03 18:08:22',
    '100', '100', '0.5833952264705629'
  );
INSERT INTO words
VALUES
  (
    '175', '1', 'premier', 'premier', '4',
    'first / primary / prime / initial',
    NULL, 'Alors, j\'ai fait mon {premier} don du sang …',
    '2013-03-27 12:27:33', '2020-10-03 18:08:22',
    '46.38244308231173', '44.63727259730512',
    '0.6407245906449152'
  );
INSERT INTO words
VALUES
  (
    '176', '1', 'y', 'y', '2', 'there / here / in',
    NULL, 'Alors, je crois qu\'il {y} a pas longtemps, là, vous avez fait une bonne action ?',
    '2013-03-27 12:32:17', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.4534373045465325'
  );
INSERT INTO words
VALUES
  (
    '177', '1', 'a', 'a', '2', 'have / has',
    NULL, 'Alors, je crois qu\'il y {a} pas longtemps, là, vous avez fait une bonne action ?',
    '2013-03-27 12:34:33', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.3450127815315619'
  );
INSERT INTO words
VALUES
  (
    '178', '1', 'action', 'action', '2',
    'action / deed', NULL, 'Alors, je crois qu\'il y a pas longtemps, là, vous avez fait une bonne {action} ?',
    '2013-03-27 12:35:08', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.364752024751857'
  );
INSERT INTO words
VALUES
  (
    '179', '1', 'don', 'don', '1', 'donation',
    NULL, 'Alors, j\'ai fait mon premier {don} du sang …',
    '2013-03-27 12:35:50', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.788721809898244'
  );
INSERT INTO words
VALUES
  (
    '180', '1', 'du', 'du', '1', 'of / from / some',
    NULL, 'Alors, j\'ai fait mon premier don {du} sang …',
    '2013-03-27 12:36:09', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8493530059692943'
  );
INSERT INTO words
VALUES
  (
    '181', '1', 'sang', 'sang', '1', 'blood',
    NULL, 'Alors, j\'ai fait mon premier don du {sang} …',
    '2013-03-27 12:36:17', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8805996308853846'
  );
INSERT INTO words
VALUES
  (
    '182', '1', 'ce', 'ce', '1', 'this / that / it',
    NULL, 'Qu\'est-{ce} que vous avez fait, alors ?',
    '2013-03-27 12:36:32', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8549391672526852'
  );
INSERT INTO words
VALUES
  (
    '183', '1', 'que', 'que', '1', 'that / how / so that / so',
    NULL, 'Qu\'est-ce {que} vous avez fait, alors ?',
    '2013-03-27 12:36:48', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6328969743409166'
  );
INSERT INTO words
VALUES
  (
    '184', '2', '他', '他', '1', 'he',
    NULL, '突然，{他}看见前面有一只黑色的大狗，看起来很凶。',
    '2013-04-18 09:03:32', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5996674006801727'
  );
INSERT INTO words
VALUES
  (
    '185', '2', '看见', '看见', '1',
    'see / catch sight', NULL, '突然，他{看见}前面有一只黑色的大狗，看起来很凶。',
    '2013-04-18 09:03:53', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.09964611111175838'
  );
INSERT INTO words
VALUES
  (
    '186', '2', '前面', '前面', '1',
    'ahead / front', NULL, '突然，他看见{前面}有一只黑色的大狗，看起来很凶。',
    '2013-04-18 09:04:12', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.699228384251919'
  );
INSERT INTO words
VALUES
  (
    '187', '2', '有', '有', '1', 'have / occur',
    NULL, '突然，他看见前面{有}一只黑色的大狗，看起来很凶。',
    '2013-04-18 09:04:29', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.19720361865796485'
  );
INSERT INTO words
VALUES
  (
    '188', '2', '只', '只', '1', '(MW) / only',
    NULL, '突然，他看见前面有一{只}黑色的大狗，看起来很凶。',
    '2013-04-18 09:04:58', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8883329712677123'
  );
INSERT INTO words
VALUES
  (
    '189', '2', '黑色', '黑色', '1',
    'black color', NULL, '突然，他看见前面有一只{黑色}的大狗，看起来很凶。',
    '2013-04-18 09:05:13', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8500540310983118'
  );
INSERT INTO words
VALUES
  (
    '190', '2', '的', '的', '1', '(Part.) / \'s',
    NULL, '突然，他看见前面有一只黑色{的}大狗，看起来很凶。',
    '2013-04-18 09:05:54', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5852712724220671'
  );
INSERT INTO words
VALUES
  (
    '191', '2', '大', '大', '1', 'big / strong',
    NULL, '突然，他看见前面有一只黑色的{大}狗，看起来很凶。',
    '2013-04-18 09:06:02', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.3761942995490453'
  );
INSERT INTO words
VALUES
  (
    '192', '2', '狗', '狗', '1', 'dog',
    NULL, '突然，他看见前面有一只黑色的大{狗}，看起来很凶。',
    '2013-04-18 09:06:08', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.12515771121267016'
  );
INSERT INTO words
VALUES
  (
    '193', '2', '看起来', '看起来',
    '1', 'seems / appears / looks as',
    NULL, '突然，他看见前面有一只黑色的大狗，{看起来}很凶。',
    '2013-04-18 09:06:36', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.4972056881498598'
  );
INSERT INTO words
VALUES
  (
    '194', '2', '很', '很', '1', 'very',
    NULL, '突然，他看见前面有一只黑色的大狗，看起来{很}凶。',
    '2013-04-18 09:06:42', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.1105553378449337'
  );
INSERT INTO words
VALUES
  (
    '195', '2', '凶', '凶', '1', 'ferocious / terrible',
    NULL, '突然，他看见前面有一只黑色的大狗，看起来很{凶}。',
    '2013-04-18 09:07:12', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.061159655508733965'
  );
INSERT INTO words
VALUES
  (
    '196', '2', '非常', '非常', '1',
    'exceptional / very', NULL, '男人{非常}害怕，不敢往前走。',
    '2013-04-18 09:07:24', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9741322947425137'
  );
INSERT INTO words
VALUES
  (
    '197', '2', '害怕', '害怕', '1',
    'be afraid', NULL, '男人非常{害怕}，不敢往前走。',
    '2013-04-18 09:07:52', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6871825379200117'
  );
INSERT INTO words
VALUES
  (
    '198', '2', '不', '不', '1', 'not',
    NULL, '男人非常害怕，{不}敢往前走。',
    '2013-04-18 09:08:01', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5135158361061624'
  );
INSERT INTO words
VALUES
  (
    '199', '2', '敢', '敢', '1', 'dare',
    NULL, '男人非常害怕，不{敢}往前走。',
    '2013-04-18 09:08:10', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5060315975044217'
  );
INSERT INTO words
VALUES
  (
    '200', '2', '往前', '往前', '1',
    'go forward / move ahead', NULL,
    '男人非常害怕，不敢{往前}走。',
    '2013-04-18 09:08:45', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9896105099372664'
  );
INSERT INTO words
VALUES
  (
    '201', '2', '旁边', '旁边', '1',
    'side / near by position/ right by',
    NULL, '狗的{旁边}站着一个女人，男人问她：',
    '2013-04-18 09:09:15', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.42995778790671174'
  );
INSERT INTO words
VALUES
  (
    '202', '2', '站', '站', '1', 'stand',
    NULL, '狗的旁边{站}着一个女人，男人问她：',
    '2013-04-18 09:09:29', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.18095744045540452'
  );
INSERT INTO words
VALUES
  (
    '203', '2', '着', '着', '1', '(there) / (cont.)',
    NULL, '狗的旁边站{着}一个女人，男人问她：',
    '2013-04-18 09:10:04', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.6149138692905324'
  );
INSERT INTO words
VALUES
  (
    '204', '2', '女人', '女人', '2',
    'woman', NULL, '狗的旁边站着一个{女人}，男人问她：',
    '2013-04-18 09:10:13', '2020-10-03 18:08:22',
    '6.84106830122592', '3.3507273312126955',
    '0.5316970558200935'
  );
INSERT INTO words
VALUES
  (
    '205', '2', '问', '问', '1', 'ask',
    NULL, '狗的旁边站着一个女人，男人{问}她：',
    '2013-04-18 09:10:20', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8137437019625154'
  );
INSERT INTO words
VALUES
  (
    '206', '2', '她', '她', '1', 'she / her',
    NULL, '狗的旁边站着一个女人，男人问{她}：',
    '2013-04-18 09:10:29', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.47362737308594155'
  );
INSERT INTO words
VALUES
  (
    '207', '2', '你', '你', '1', 'you',
    NULL, '{你}的狗咬人吗？',
    '2013-04-18 09:10:35', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.9269057902758063'
  );
INSERT INTO words
VALUES
  (
    '208', '2', '咬', '咬', '1', 'bite',
    NULL, '你的狗{咬}人吗？',
    '2013-04-18 09:10:44', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.2136468628548522'
  );
INSERT INTO words
VALUES
  (
    '209', '2', '吗', '吗', '1', '(QW)',
    NULL, '你的狗咬人{吗}？',
    '2013-04-18 09:10:59', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.28751697418048694'
  );
INSERT INTO words
VALUES
  (
    '210', '2', '说', '说', '1', 'say',
    NULL, '女人{说}：', '2013-04-18 09:11:06',
    '2020-10-03 18:08:22', '0', '-6.980681940026449',
    '0.7966443130715232'
  );
INSERT INTO words
VALUES
  (
    '211', '2', '我的', '我的', '1',
    'my', NULL, '{我的}狗不咬人。',
    '2013-04-18 09:11:16', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.12067067354979988'
  );
INSERT INTO words
VALUES
  (
    '212', '2', '这时', '这时', '1',
    'at this time', NULL, '{这时}，那只狗咬了男人。',
    '2013-04-18 09:12:08', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.2134204592680749'
  );
INSERT INTO words
VALUES
  (
    '213', '2', '那', '那', '1', 'that',
    NULL, '这时，{那}只狗咬了男人。',
    '2013-04-18 09:12:19', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.7050903064246199'
  );
INSERT INTO words
VALUES
  (
    '214', '2', '了', '了', '1', '(compl.) / finish / (change)',
    NULL, '这时，那只狗咬{了}男人。',
    '2013-04-18 09:12:48', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8851901850525198'
  );
INSERT INTO words
VALUES
  (
    '215', '2', '气坏', '气坏', '1',
    'furious', NULL, '他{气坏}了，大叫：',
    '2013-04-18 09:13:54', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.3106800367223844'
  );
INSERT INTO words
VALUES
  (
    '216', '2', '叫', '叫', '1', 'shout',
    NULL, '他气坏了，大{叫}：',
    '2013-04-18 09:14:07', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.8978296591880076'
  );
INSERT INTO words
VALUES
  (
    '217', '2', '回答', '回答', '1',
    'answer', NULL, '女人{回答}：',
    '2013-04-18 09:14:18', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.5571082165065298'
  );
INSERT INTO words
VALUES
  (
    '218', '2', '这', '这', '1', 'this',
    NULL, '{这}不是我的狗。',
    '2013-04-18 09:14:24', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.09205213570227114'
  );
INSERT INTO words
VALUES
  (
    '219', '2', '是', '是', '1', 'be',
    NULL, '这不{是}我的狗。',
    '2013-04-18 09:14:30', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.7889360597254113'
  );
INSERT INTO words
VALUES
  (
    '220', '2', '你的', '你的', '1',
    'your', NULL, '{你的}狗咬人吗？',
    '2013-04-18 09:22:32', '2020-10-03 18:08:22',
    '0', '-6.980681940026449', '0.668523922253888'
  );
DROP
  TABLE IF EXISTS wordtags;
CREATE TABLE `wordtags` (
  `WtWoID` int(11) unsigned NOT NULL,
  `WtTgID` int(11) unsigned NOT NULL,
  PRIMARY KEY (`WtWoID`, `WtTgID`),
  KEY `WtTgID` (`WtTgID`),
  KEY `WtWoID` (`WtWoID`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
INSERT INTO wordtags
VALUES
  ('1', '27');
INSERT INTO wordtags
VALUES
  ('2', '1');
INSERT INTO wordtags
VALUES
  ('2', '14');
INSERT INTO wordtags
VALUES
  ('2', '18');
INSERT INTO wordtags
VALUES
  ('2', '28');
INSERT INTO wordtags
VALUES
  ('3', '13');
INSERT INTO wordtags
VALUES
  ('6', '15');
INSERT INTO wordtags
VALUES
  ('7', '5');
INSERT INTO wordtags
VALUES
  ('7', '19');
INSERT INTO wordtags
VALUES
  ('8', '5');
INSERT INTO wordtags
VALUES
  ('8', '6');
INSERT INTO wordtags
VALUES
  ('8', '7');
INSERT INTO wordtags
VALUES
  ('8', '22');
INSERT INTO wordtags
VALUES
  ('22', '19');
INSERT INTO wordtags
VALUES
  ('25', '1');
INSERT INTO wordtags
VALUES
  ('25', '7');
INSERT INTO wordtags
VALUES
  ('25', '8');
INSERT INTO wordtags
VALUES
  ('25', '18');
INSERT INTO wordtags
VALUES
  ('25', '22');
INSERT INTO wordtags
VALUES
  ('26', '7');
INSERT INTO wordtags
VALUES
  ('26', '17');
INSERT INTO wordtags
VALUES
  ('33', '1');
INSERT INTO wordtags
VALUES
  ('33', '18');
INSERT INTO wordtags
VALUES
  ('45', '19');
INSERT INTO wordtags
VALUES
  ('46', '2');
INSERT INTO wordtags
VALUES
  ('46', '18');
INSERT INTO wordtags
VALUES
  ('50', '1');
INSERT INTO wordtags
VALUES
  ('50', '8');
INSERT INTO wordtags
VALUES
  ('50', '19');
INSERT INTO wordtags
VALUES
  ('51', '11');
INSERT INTO wordtags
VALUES
  ('51', '19');
INSERT INTO wordtags
VALUES
  ('146', '10');
INSERT INTO wordtags
VALUES
  ('146', '19');
INSERT INTO wordtags
VALUES
  ('147', '1');
INSERT INTO wordtags
VALUES
  ('147', '7');
INSERT INTO wordtags
VALUES
  ('147', '8');
INSERT INTO wordtags
VALUES
  ('147', '22');
INSERT INTO wordtags
VALUES
  ('147', '25');
INSERT INTO wordtags
VALUES
  ('148', '2');
INSERT INTO wordtags
VALUES
  ('148', '12');
INSERT INTO wordtags
VALUES
  ('149', '5');
INSERT INTO wordtags
VALUES
  ('149', '7');
INSERT INTO wordtags
VALUES
  ('149', '22');
INSERT INTO wordtags
VALUES
  ('150', '7');
INSERT INTO wordtags
VALUES
  ('150', '10');
INSERT INTO wordtags
VALUES
  ('150', '22');
INSERT INTO wordtags
VALUES
  ('151', '1');
INSERT INTO wordtags
VALUES
  ('151', '19');
INSERT INTO wordtags
VALUES
  ('171', '13');
INSERT INTO wordtags
VALUES
  ('175', '12');
INSERT INTO wordtags
VALUES
  ('175', '16');
DROP
  TABLE IF EXISTS feedlinks;
CREATE TABLE `feedlinks` (
  `FlID` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `FlTitle` varchar(200) NOT NULL,
  `FlLink` varchar(400) NOT NULL,
  `FlDescription` text NOT NULL,
  `FlDate` datetime NOT NULL,
  `FlAudio` varchar(200) NOT NULL,
  `FlText` longtext NOT NULL,
  `FlNfID` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`FlID`),
  KEY `FlLink` (`FlLink`),
  KEY `FlDate` (`FlDate`),
  UNIQUE KEY `FlTitle` (`FlNfID`, `FlTitle`)
) ENGINE = MyISAM DEFAULT CHARSET = utf8;
