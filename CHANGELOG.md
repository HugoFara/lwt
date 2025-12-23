# Changelog

This project's changelog. Versions marked with "-fork" come from the community,
other versions come from the canonical LWT ("official" branch on Git).
For git tags, official releases are marked like "v1.0.0", while unofficial
ones are marked like "v1.0.0-fork".

## [UNRELEASED]

### Added

* **Notes Field for Terms** ([#128](https://github.com/HugoFara/lwt/issues/128)):
  Added a dedicated "Notes" field to terms/words, allowing users to add personal
  notes separate from translations. Includes database migration, updated entity
  classes, service layer, UI forms, and API endpoints.
* **Inline Markdown for Translations and Notes** ([#126](https://github.com/HugoFara/lwt/issues/126)):
  Translations and notes now support inline Markdown formatting: `**bold**`,
  `*italic*`, `~~strikethrough~~`, and `[links](url)`. Markdown is rendered in
  the word modal, annotations (translations shown above/below words), word list,
  test screens, and word detail views. Implemented with custom lightweight parsers
  in both TypeScript and PHP with XSS protection (HTML escaped before parsing,
  URLs sanitized to block javascript: schemes).
* Official support for PHP 8.3 and 8.4.
* **Multi-user support** ([#221](https://github.com/HugoFara/lwt/issues/221)):
  Users are now stored in a dedicated `users` table with proper foreign key
  relationships instead of using table prefixes. Includes user authentication,
  registration, and role-based access (user/admin).
* **MVC Architecture** ([#212](https://github.com/HugoFara/lwt/issues/212),
  [#215](https://github.com/HugoFara/lwt/issues/215)): Complete restructure of
  the PHP codebase following the Model-View-Controller pattern:
  * Controllers in `src/backend/Controllers/` (17 controller classes)
  * Services in `src/backend/Services/` (35 service classes)
  * Views in `src/backend/Views/`
  * Router-based URL handling in `src/backend/Router/`
* **Front Controller Pattern** ([#213](https://github.com/HugoFara/lwt/issues/213)):
  All requests now route through `index.php`. The root folder no longer contains
  dozens of PHP files - only `index.php` and `router.php` remain.
* **Alpine.js Frontend Framework** ([#208](https://github.com/HugoFara/lwt/issues/208)):
  Adopted Alpine.js for reactive UI components, reducing reliance on PHP-rendered
  pages for user interactions.
* **TypeScript Migration** ([#209](https://github.com/HugoFara/lwt/issues/209)):
  Frontend JavaScript converted to TypeScript with 90+ `.ts` files organized in
  `src/frontend/js/` with proper module structure (api/, core/, reading/, testing/,
  feeds/, languages/, etc.).
* **jQuery Removal** ([#211](https://github.com/HugoFara/lwt/issues/211)):
  jQuery has been replaced with modern vanilla JavaScript and Alpine.js.
* **Overlib Replacement** ([#214](https://github.com/HugoFara/lwt/issues/214)):
  The 20-year-old overlib library has been removed in favor of modern alternatives.
* **HoverIntent Replacement** ([#210](https://github.com/HugoFara/lwt/issues/210)):
  The `briancherne/jquery-hoverintent` plugin has been replaced with a native
  TypeScript implementation.
* **Iframe Replacement** ([#166](https://github.com/HugoFara/lwt/issues/166)):
  Legacy iframe-based dynamic content loading replaced with Alpine.js components
  and REST API calls. Only dictionary iframes remain for external dictionary display.
* **Extensible Language Parsers** ([#222](https://github.com/HugoFara/lwt/issues/222)):
  Implemented a clean, extensible parser architecture using the Strategy Pattern.
  Each language can now specify its parser type via the `LgParserType` database column:
  * **Regex Parser** (default): Standard regex-based tokenization for most languages
  * **Character Parser**: Character-by-character splitting for CJK languages (Chinese)
  * **MeCab Parser**: Japanese morphological analyzer integration
  New parser infrastructure in `src/backend/Core/Parser/` includes `ParserInterface`,
  `ParserRegistry`, `ParsingCoordinator`, and value objects (`Token`, `ParserResult`,
  `ParserConfig`). The language edit form dynamically shows/hides relevant options
  based on the selected parser. Future parsers can be added by implementing
  `ParserInterface` and registering in `ParserRegistry`.

### Changed

* **Database Engine Migration** ([#220](https://github.com/HugoFara/lwt/issues/220)):
  All permanent tables converted from MyISAM to InnoDB engine. Benefits include:
  * ACID transaction support
  * Row-level locking for better concurrency
  * Foreign key constraints for data integrity
  * Improved crash recovery
* **Inter-Table Foreign Keys** ([#220](https://github.com/HugoFara/lwt/issues/220)):
  Added 16 foreign key constraints for referential integrity between tables:
  * Language references: texts, words, sentences, archivedtexts, newsfeeds → languages (CASCADE)
  * Text references: sentences, textitems2, texttags → texts (CASCADE)
  * Other references: textitems2 → sentences (CASCADE), textitems2 → words (SET NULL),
    wordtags → words/tags, texttags → tags2, archtexttags → archivedtexts/tags2,
    feedlinks → newsfeeds (all CASCADE)
  * Migration: `20251221_120000_add_inter_table_foreign_keys.sql`
  * **Breaking change**: `textitems2.Ti2WoID` now uses NULL instead of 0 for unknown words
* Database schema now includes foreign key constraints linking user-owned data
  (languages, texts, words, tags, feeds) to the `users` table with CASCADE delete.
* **TTS Settings Storage** ([#186](https://github.com/HugoFara/lwt/issues/186)):
  Text-to-Speech language settings (voice, rate, pitch) now use browser localStorage
  instead of cookies. Includes automatic migration from old cookie format.
* **Removed SYSTEM_VARIABLES_ADMIN Privilege Requirement**
  ([#167](https://github.com/HugoFara/lwt/issues/167)): LWT no longer requires
  elevated database privileges for word imports. The `SET GLOBAL max_heap_table_size`
  call has been removed and replaced with chunked batch inserts (500 rows per batch)
  that stream files line-by-line, reducing memory usage for large imports.

### Fixed

* **Tag Duplicate Key Error** ([#120](https://github.com/HugoFara/lwt/issues/120)):
  Fixed rare error when updating a word with tags. When the session cache was
  stale, saving a word with an existing tag would fail with "Duplicate entry
  for key 'TgText'" and remove the tag from the word. Changed tag insertion
  to use `INSERT IGNORE` to handle race conditions and stale cache gracefully.
* **Japanese Annotations** ([#101](https://github.com/HugoFara/lwt/issues/101)):
  Fixed annotations not displaying correctly in Japanese texts. The
  `annotationToJson()` function was using an off-by-one index that didn't
  match the `Ti2Order` values used by the frontend, causing approximately
  50% of annotations to fail to display.
* **Text Parsing** ([#114](https://github.com/HugoFara/lwt/issues/114)): Fixed
  last word of text not being recognized when text ends without punctuation.
  Words at the end of a text are now correctly identified regardless of trailing
  punctuation.
* **PHP 8.3 Compatibility**: Fixed deprecation warnings in `langFromDict()` and
  `targetLangFromDict()` - both functions now check for null query strings before
  calling `parse_str()`.
* **Database Maintenance**: Fixed `initWordCount()` generating invalid SQL for
  split-each-char languages (e.g., Chinese). When `preg_match_all` returned 0
  matches, the CASE statement was malformed. Word count now defaults to 1 minimum.
* **SQL Prefix Queries**: Fixed `prefixQuery()` in database migrations:
  * Now handles `DROP TABLE IF EXISTS` syntax (previously only `IF NOT EXISTS`).
  * Now case-insensitive for SQL keywords (CREATE, DROP, ALTER, INSERT).
* **Punctuation Formatting** ([#125](https://github.com/HugoFara/lwt/issues/125)):
  Fixed punctuation marks (periods, commas, quotation marks) breaking away from
  adjacent words onto separate lines. Punctuation now stays "stuck" to the word
  it belongs to by wrapping word+punctuation pairs in non-breaking groups.

### Security

* Adds session security cookie to protect against session hijacking.
* Fixed SQL injection vulnerabilities in `validateLang()`, `validateText()`,
  `validateTag()`, and `validateArchTextTag()` functions. All validation
  functions now properly sanitize non-numeric input before executing SQL
  queries.
* Fixed SQL injection vulnerabilities in `inc/tags.php`: `getTextTitle()`,
  `saveWordTags()`, `saveTextTags()`, and `saveArchivedTextTags()` now
  validate numeric input before using in SQL queries.
* Fixed SQL syntax errors in tag list functions when handling empty lists or
  failed tag creation. Functions now return gracefully instead of generating
  invalid SQL.

### Deprecated

* Removed testing for PHP 8.0.
* Legacy table prefix system (`$tbpref`) is deprecated in favor of the new
  user-based multi-tenancy. Existing prefixed tables will be migrated to the
  new system automatically.

### Removed

* **Orphaned Frame Settings** ([#116](https://github.com/HugoFara/lwt/issues/116)):
  Removed all legacy frame-related settings from the admin settings page:
  * `set-text-h-frameheight-no-audio` - Frame height without audio
  * `set-text-h-frameheight-with-audio` - Frame height with audio
  * `set-text-l-framewidth-percent` - Width of left frames (text)
  * `set-text-r-frameheight-percent` - Height of right frame (text)
  * `set-test-h-frameheight` - Frame height (test)
  * `set-test-l-framewidth-percent` - Width of left frames (test)
  * `set-test-r-frameheight-percent` - Height of right frame (test)

  These settings controlled frame dimensions in the text reading and test
  interfaces, but became non-functional after the iframe-based layout was
  replaced with Alpine.js components ([#166](https://github.com/HugoFara/lwt/issues/166)).
  Also removed the dead `initFrameResizable()` function from TypeScript.
  Closes #116.

## 2.10.0-fork (April 1 2024)

### Added in 2.10.0-fork

* Support for Japanese with MeCab on Mac! This was added thanks to
[quopquai](https://github.com/quopquai) on [#135](https://github.com/HugoFara/lwt/issues/135).
* New globals ([#163](https://github.com/HugoFara/lwt/issues/163)):
  * On `inc/kernel_utility.php`: `LWT_APP_VERSION` and `LWT_RELEASE_DATE`.
  * On `api.php`: `LWT_API_VERSION` and `LWT_API_RELEASE_DATE`.
  * `src/js/jq_pgm.js`: `LWT_DATA`.
  * `unloadformcheck.js` now declares a new object `lwtFormCheck` that contains
  all the functions needed.
* Important additions to Text-To-Speech (TTS):
  * Word can be read on hover or on click.
  Pull request [#147](https://github.com/HugoFara/lwt/pull/147) by
  [@ProgramComputer](https://github.com/ProgramComputer).
  * You can add a custom text reader with the new voice API feature!
  A courtesy of [@ProgramComputer](https://github.com/ProgramComputer) on pull request
  [#153](https://github.com/HugoFara/lwt/pull/153).
  A feature first requested on [#143](https://github.com/HugoFara/lwt/issues/143).
  Discussion in open on [#174](https://github.com/HugoFara/lwt/discussions/174).
  * New JS functions: `readTextWithExternal` to read a text with an external
  application and `speechDispatcher` that can choose a text reader
  (browser or third party).
* Starts a cleaner database management. Database schema is defined in
`db/schema/baseline.sql` and no longer in PHP code.
* You can choose to add romanization for languages that don't need it in the language
settings ([#119](https://github.com/HugoFara/lwt/issues/119)).
* Docker images are now built for multiple platforms, see PR
[#169](https://github.com/HugoFara/lwt/pull/169), closing discussion
[#141](https://github.com/HugoFara/lwt/discussions/141).
Many thanks to [@ProgramComputer](https://github.com/ProgramComputer)!
* On word review:
  * The "Read words aloud" setting is now saved, PR [#185](https://github.com/HugoFara/lwt/pull/185).
  * UX change: You can click on "Read words aloud" text to check the checkbox.

### Changed in 2.10.0-fork

* Adds missing documentation in the Docker image:
  * MarkDown files in the root folder are now included.
  See [#160](https://github.com/HugoFara/lwt/issues/160).
  It adds `README.md` and `UNLICENSE.md`.
  * Adds `docs/info.html` and `docs/index.html` from [#146](https://github.com/HugoFara/lwt/pull/146).
* Multi-word creation was reviewed to use a simpler JS code.
* Word review:
  * Status + 1 can be set only after word display. Before, you could
  press key up at any time to increase term status, but keydown was effective only
  after solution display ([#159](https://github.com/HugoFara/lwt/issues/159)).
  * When testing sentences, sentences are always displayed, even when less than 70% of words in the sentence are known.
  See [#194](https://github.com/HugoFara/lwt/issues/194).
* New files:
  * `inc/feeds.php` and `inc/tags.php`, stemmed from `inc/session_utility.php`.
  It should not lead to any code or behaviour change.
  * `src/js/overlib_interface.js`, stemmed from `src/js/pgm.js`. It contains all the
  overlib interactions.
  * `src/js/src/js/text_events.js`, stemmed from `src/js/jq_pgm.js`.
  It defines interactions with a text on reading.
* For audio URI, raises the characters length limit from 200 to 2048
([#144](https://github.com/HugoFara/lwt/issues/144)).
* Database:
  * For Linux users, the official database RDBMS recommended is now MariaDB as a drop-in replacement of MySQL.
  * Database creation and migration is now handled directly in SQL, for a safer
behavior.
  * You do no longer need to give to LWT SYSTEM_VARIABLES_ADMIN for text reparsing.
  (new function `checkExpressions`) ([#167](https://github.com/HugoFara/lwt/issues/167)).
  It is still necessary for words import.
* Docker changes:
  * By default, the images now runs at localhot/lwt and no longer in the
  root folder ([installer #4](https://github.com/HugoFara/lwt-docker-installer/pull/4)
  and [#169](https://github.com/HugoFara/lwt/pull/169)).
  * Image updated from `apache-buster` to `apache-bullseye`.
* Composer dependencies up to date 2024-03-24.

### Fixed in 2.10.0-fork

* The database wizard was broken since 2.9.0.
* Word status:
  * It was impossible to change a word status on hover since 2.9.1.
  * Having wrong dictionary link and error reporting enabled prevented word status from being changed.
* Avoids JavaScript error on editing a language when the dictionary field is empty.
* For some feeds, feed wizard session was not working due to ill-configured session.
Solves [#129](https://github.com/HugoFara/lwt/issues/129), thanks to the help of PR
[#168](https://github.com/HugoFara/lwt/pull/168).
* Text reading position was not working consistently when adding several known words.
* Multi-words:
  * Tooltip was not properly saved ([#170](https://github.com/HugoFara/lwt/pull/170)).
  * Translation may be escaped two times ([#170](https://github.com/HugoFara/lwt/pull/170)).
  * Fixes [#170](https://github.com/HugoFara/lwt/pull/170): on creation,
  multi-word was always displayed before the text it encompasses,
  without removing this text.
  * Tootltip title was not properly saved, normally without incidence on user display.
  * Fixes [#69](https://github.com/HugoFara/lwt/issues/69): having the same
  multi-word on multiple texts was displaying it at many positions on creation.
* Japanese:
  * Parsing Japanese texts with MeCab was creating warnings when the text was not
  finishing by a punctuation mark.
  * Multi-words were not saved with MeCab parsing on Japanese.
  * Japanese was always requiring MeCab for TTS, even if it was not used
  ([#155](https://github.com/HugoFara/lwt/pull/155)) and [#182](https://github.com/HugoFara/lwt/pull/182).
* On word review (test):
  * Space keyboard shortcut may have been inoperative.
  * On `api.php`, tests were always set to "multi-word" due to a missing variable
  type conversion. Pull request [#175](https://github.com/HugoFara/lwt/pull/175).
  * Text-To-Speech was not working (suggested by [#187](https://github.com/HugoFara/lwt/pull/187)).
  * Testing terms (`Terms > Multi Actions > Test ALL Terms`) was broken since 2.9.0.
  [#192](https://github.com/HugoFara/lwt/issues/192).
* An explicit dependency to [php-dom](https://www.php.net/manual/en/book.dom.php) was
missing in `INSTALL.sh` (and `composer.json`), as stated in
[#178](https://github.com/HugoFara/lwt/pull/178).
* API: the REST API was not accessible for users running LWT in a root folder.
* On audio, the replay button is fixed:
[issue #138](https://github.com/HugoFara/lwt/issues/138) and
[PR #191](https://github.com/HugoFara/lwt/pull/191).
* The field `SeFirstPos` was missing in table `sentences` of demo database,
resulting in failed installation the first time.
* Editing test annotation would not display since 2.9.1 [#193](https://github.com/HugoFara/lwt/issues/193).

### Deprecated in 2.10.0-fork

* Using any function from `unloadformcheck.js` without using `lwtFormCheck` is deprecated.
* `get_database_prefixes` is deprecated, superseded by `getDatabasePrefix` which is much cleaner.
* Globals defined in `jq_pgm.js` are going into a single global `LWT_DATA`.
* New text creation workflow simplified in `inc/database_connect.php`: `update_default_values` replaced by
`registerSentencesTextItems` and `check_text` by `displayTextStatistics`.

### Full Changelog for 2.10.0-fork

* [2.9.1...2.10.0](https://github.com/HugoFara/lwt/compare/2.9.1...2.10.0)

## 2.9.1-fork (December 29 2023)

### Added in 2.9.1-fork

* New tools for documentation:
  * New JavaScript documentation with [JSDoc](https://jsdoc.app/)! You will find it under `docs/js/`.
  * Better PHP documentation using [phpDocumentor](https://phpdoc.org/). The new documentation is at `docs/php/`.
* New constant `LWT_LANGUAGES_ARRAY`, that replaces `$langDefs` (in `inc/langdefs.php`).
Part of [#163](https://github.com/HugoFara/lwt/issues/163).

### Changed in 2.9.1-fork

* PHP files header docblocks are expanded, notably with a PHP version, package and category name.
* Japanese number are now parsed by MeCab. Big thanks to
[@ProgramComputer](https://github.com/ProgramComputer) for his pull request
[#149](https://github.com/HugoFara/lwt/pull/149)!
* Removes dead feeds from the demo database, as signaled in
[#148](https://github.com/HugoFara/lwt/issues/148).
* Patch update to the REST API to 0.1.1: requests on GET with empty payload were
resulting in error.

### Fixed in 2.9.1-fork

* Fixes [#113](https://github.com/HugoFara/lwt/issues/113): changing a language name
was making text-to-speech unusable and may prompt a warning.
* Since 2.9.0, tests may be unable to run due to a missing key in an AJAX request ("type").
* Since 2.9.0, audio position was not saved properly.
* On `upload_words.php`, "update only empty fields" was broken.
* Translation were not set by default properly when editing terms translations table.
* The field MeCab was set for Japanese even when not asked for.
Signaled on PR [#155](https://github.com/HugoFara/lwt/pull/155), thanks to
[@ProgramComputer](https://github.com/ProgramComputer).
* Since 2.8.1, Japanese word romanization was not displayed any more.
Thanks to 아르노 for signalling the bug on Discord!
* The go slower/faster feature on audio was broken
([#138](https://github.com/HugoFara/lwt/issues/138)), as functions `click_slower`
and `click_faster` were declared two times in `src/js/audio_controller.js`.
* When opening a text, text position was not saved and a `text_id is unknown` error was triggered.
* Do not display an error on step 2 of feed wizard at first initialization
([#129](https://github.com/HugoFara/lwt/issues/129)).
* It was impossible to install the demo database if they was more or less than one instruction a line.
This is fixed, and the SQL file was made more readable.
* Changes `WoStatusChange` default value to '1970-01-01 01:00:01', it was impossible
to install the demo DB out of LWT (related to [#78](https://github.com/HugoFara/lwt/issues/78)).
* Deleting a language deletes the language ([#151](https://github.com/HugoFara/lwt/issues/151)).
Before it was setting the language to empty values.
* On media select, the lwt folder name was prepended to the media folder,
resulting in broken path ([#161](https://github.com/HugoFara/lwt/issues/161)).
* With the REST API, requests with an empty query were resulting in an error.
* `no-minify` composer command was fixed, suggested as a part of
PR [#154](https://github.com/HugoFara/lwt/pull/154/files) by
[@ProgramComputer](https://github.com/ProgramComputer).

### Deprecated in 2.9.1-fork

* PHP documentation using Doxygen (under `docs/html`) is now deprecated and will be removed in 3.0.0.
* Using `$langDefs` is deprecated in favor of `LWT_LANGUAGES_ARRAY` ([#163](https://github.com/HugoFara/lwt/issues/163)).
* Calling the global function `new_pos` to set audio position is deprecated. Please use `lwt_audio_controller.setPosition`.

### Full Changelog for 2.9.1-fork

* [2.9.0...2.9.1](https://github.com/HugoFara/lwt/compare/2.9.0...2.9.1)

## 2.9.0-fork (December 24 2023)

### Added in 2.9.0-fork

* ``langFromDict`` and ``targetLangFromDict`` are now officially going to the
kernel utility functions.
* Text and title boxes change language according to the text's language for
short text creation/edition, long text creation and text check
([#111](https://github.com/HugoFara/lwt/issues/111)).
* Refactored texts with OOP:
  * New class: ``Text`` (in ``inc/classes/Text.php``).
  * New function: ``edit_texts_form`` (in ``edit_texts.php``) that takes a Text object as input.
* Tests (checking if you know words) now use AJAX ([#112](https://github.com/HugoFara/lwt/issues/112)), it has several advantages:
  * Musics play fully
  * Page do not need to reload.
  * The timer continues instead of resetting.
* REST API, the new ``api.php`` is intended to continue developing itself
as a REST API. It features the following interaction:
  * On GET, the endpoints are:
    * `/media-files`: the list of audio and video files in the media folder.
    * `/sentences-with-term`: sentences containing a new term.
    * `/sentences-with-term/{term-id}`: sentences containing a registered term.
    * `/similar-terms`: terms similar to a given one.
    * `/theme-path`: the path for a media using theme.
    * `/terms`
      * `/imported`: imported terms.
      * `/{term-id}/translations`: translations for a term.
    * `/review`
      * `/next-word`: next word to review.
      * `/tomorrow-count`: number of reviews for the next day.
    * `/phonetic-reading`: phonetic reading for a term or sentence.
    * `/texts/statistics`: word statistics on texts.
    * `/version`: REST API version.
  * On POST, the endpoints are:
    * `/settings`: save a setting.
    * `/terms` followed by:
      * `/{term-id}` for any term update, in particular:
        * `/status`, to change the status, followed by either:
          * `/down`: decrement status of a term by one unit.
          * `/up`: increment status of a term by one unit.
          * `/set/{new-status}`: set the status of a term.
        * `/translations`: add a translation for an existing word.
      * `/new`: add a translation for a new word.
    * `/texts/{text-id}` followed by:
      * `/annotation`: change the text annotation
      * `/audio-position`: change `audio` position.
      * `/reading-position`: change the reading position.
* Similar terms mark the word edit form as edited only if something was
actually changed.
* You can now specify a socket for your database through ``$socket`` in
``connect.inc.php``. This feature was brought to you by
[@hangug-eo](https://github.com/hangug-eo) in his PR
[#132](https://github.com/HugoFara/lwt/pull/132).
* You can now change the voice of the TTT feature through
``text_to_speech_settings.php``.
* ``server_data.php`` was reorganized and is more informative with details
about the REST API.

### Changed in 2.9.0-fork

* User Interface Improvements:
  * Prettier UI to navigate between text creation/edition/archive pages.
  * ``flex-spaced`` now sets the property of ``text-align: center;``. This property was inherited before.
  * The long text import page looks a bit nicer.
* User Experience:
  * When reading text, you can click on "Show All" and "Translations" to click the checkbox.
  * When changing a word status, the displayed messaged is now more informative: before database change, success message, now: waiting screen, tentative database change, result message (error or success). Under the hood, we are transitioning from pure PHP to AJAX.
  * It is faster to exit a text page: audio and reading position saves are now asynchronous.
* Annotations "order" are now identical to ``Ti2Order`` (in ``textitems2``). It makes management easier, but users may need to reload the annotations once.
* Updated composer dependencies (up-to-date 2023-11-23).

### Fixed in 2.9.0-fork

* When editing an existing text, surrounding spaces are no longer inserted ([#92](https://github.com/HugoFara/lwt/issues/92)).
* Language code is better detected from translator URL when editing an existing language.
* The field "Short Text Import" on long text import and was not redirecting to
the intended page. Same goes for "New Text" on "Archived Texts" page.
* It was impossible to archive more than one text ([#118](https://github.com/HugoFara/lwt/issues/118)).
* Testing a word was not changing it's color.
* A warning was sent 'Undefined array key "query"' when creating a new word ([#121](https://github.com/HugoFara/lwt/issues/121)).
* A warning when savings settings in ``settings.php`` was sometimes displayed ([#121](https://github.com/HugoFara/lwt/issues/121)).
* Test header was different when testing languages or terms since 2.1.0-fork.
* Wrong link to documentation (``info.php`` instead of ``docs/info.html``). Signaled by Mooncake on Discord.
* Text reading:
  * The reading button is usable at last! Clicking the button a second time stops the reading process ([#108](https://github.com/HugoFara/lwt/issues/118)).
  * Text language code was often not recognized in TTT settings (``text_to_speech_settings.php``).
  * Clicking the "Read" button in TTT Settings was refreshing the page.
* It was impossible to copy a similar term that had apostrophes ([#127](https://github.com/HugoFara/lwt/issues/127)).
* A deprecation warning for ``trim`` was sent in simterms when no similar term was found.
* During a text creation, the ``media_uri`` property of ``Text`` was used instead of ``source``. Thanks to [@hangug-eo](https://github.com/hangug-eo) for his PR [#133](https://github.com/HugoFara/lwt/pull/133)!
* When looking for an audio file on file edit/creation, the displayed path may have been wrong for characters between 0x80 and 0x9F.
* Multi-words were not properly highlighted for languages separating each
character as a word (e. g.: Chinese). Big thanks to [@hangug-eo](https://github.com/hangug-eo) for signaling the issue and solving it in his PR [#140](https://github.com/HugoFara/lwt/pull/140)!
* The database wizard was not correctly saving the password. This is fixed by
[@hangug-eo](https://github.com/hangug-eo) in
[#132](https://github.com/HugoFara/lwt/pull/132).
* The list of translations of a term was sometimes not found on
text annotation edition.

### Deprecated in 2.9.0-fork

* Legacy AJAX API. The following AJAX interactions are now deprecated in favor to the new REST API (at ``api.php``):
  * ``inc/ajax_show_similar_terms.php``, use ``/similar-terms`` on GET, `simterms_lgid` becomes `lg_id` and `simterms_word` `term`.
  * ``inc/ajax_add_term_transl.php``
    * For a new term, use `/terms/new`. `lang` becomes `lg_id`, `text` is `term_text`.
    * To update an existing term, use `/terms/{term-id}/translations`. Same arguments.
  * ``inc/ajax_check_regexp.php`` should no longer be used and will be removed.
  * ``inc/ajax_chg_term_status.php`` should be accessed through ``/terms/{term-id}/status``. Argument ``data`` is now part of the URL, on post only.
  * ``inc/ajax_get_phonetic.php`` should be accessed through ``/phonetic-reading``, same arguments.
  * ``inc/ajax_get_theme.php`` should be accessed through ``/settings/theme-path``. Argument ``filepath`` is now ``path``.
  * ``inc/ajax_save_setting.php`` should be accessed through ``/settings``, `k` becomes `key` and `v` `value`.
  * ``inc/ajax_save_text_position.php`` should be accessed through ``/texts/{text-id}/reading-position``, `tposition` is now `position`.
  * ``inc/ajax_word_counts.php`` should be accessed through ``/texts/statistics``, on GET. Argument `id` is now `texts_id`.
  * ``inc/ajax_update_media_select.php`` should be accessed through ``/media-files``.
  * ``inc/ajax_show_sentences.php`` should be accessed through ``/sentences-with-term``, on GET. Argument ``lang`` is now ``lg_id``, ``word`` is ``word_lc`` and ``woid`` is ``word_id``, ``ctl`` is no longer required.
  * ``inc/ajax_show_imported_terms.php`` should be accessed through ``/terms/imported``, same arguments.
  * ``inc/ajax_save_impr_text.php`` should be accessed through ``/texts/{text-id}/annotation``, ``id`` is now optional.
  * ``inc/ajax_edit_impr_text.php``:
    * On display, using ``word=""`` is now deprecated as the page loads in pure PHP.
    * On term edition, should be accessed through ``/terms/{term-id}/translations``, ``id`` is now ``text_id`` and ``word`` becomes ``text_lc``.
* During a word review (test), a "free" test where the user design there own test SQL is now deprecated and will be removed.

### Security in 2.9.0-fork

* During tests (word review), test data is no longer be send as raw SQL string, except for "free" test.

### Full Changelog for 2.9.0-fork

* [2.8.1...2.9.0](https://github.com/HugoFara/lwt/compare/2.8.1...2.9.0)

## 2.8.1-fork (April 14 2023)

### Changed in 2.8.1-fork

* Updated some documentation screenshots: home screen, language edition and terms upload.

### Fixed in 2.8.1-fork

* Since 2.8.0-fork, PHP installations with ext-dom absent or disabled could not display text.
* Using the import terms feature, it is better detected if local infile is enabled.
* Since 2.2.2-fork, many multi-words were not displayed, as explained in [#70](https://github.com/HugoFara/lwt/issues/70). Issues may remain.
* Auto-translation of all words (bulk translate) could not open dictionaries starting with '*'.
* Since 2.7.0-fork, using a dictionary starting with "ggl.php" was creating a fatal error on opening text.

### Full Changelog for 2.8.1-fork

* [2.8.0...2.8.1](https://github.com/HugoFara/lwt/compare/2.8.0...2.8.1)

## 2.8.0-fork (April 08 2023)

### Added in 2.8.0-fork

* Responsive design: LWT gets easier to visualize on phone!

### Changed in 2.8.0-fork

* The language settings wizard is now *open by default* on new language creation.
* Potential breaking change in the way words are displayed while reading. The target was to make the code more robust.
* ``item_parser`` and ``echo_term`` in ``do_text_text.php`` now both takes an optional ``$expr`` argument.
* Terms import form changed for a simpler presentation. It becomes easier to use to use on your phone.
* Many tables are now displayed larger (when your screen has the available space).
* Updated composer dependencies.

### Fixed in 2.8.0-fork

* Removed warnings: dictionaries url without query do no longer raise warnings.
* Feeds:
  * Click "New Text" on the first page of ``do_feeds.php`` had no effect. Changed to "New Feed".
  * Step 2 of feed wizard was sometimes failing because ``$_SESSION['wizard']['host']`` was a string an not an array.
  * Type error in ``get_links_from_new_feed`` was breaking step 2 of feed wizard.
  * Loading feeds could result in malformed SQL, see [#100](https://github.com/HugoFara/lwt/issues/100). Thanks [@maanh96](https://github.com/maanh96) for the hint!
* Docker: ``media/`` was neither accessible nor saved to a container ([#106](https://github.com/HugoFara/lwt/issues/106)). Thanks to [@parradam](https://github.com/parradam) for signaling and solving the issue!
* On creating a language, the Glosbe dictionary comes with a "?lwt_popup=1" to open in new window by default.
* Japanese pronunciation: works even if the language is not exactly called "Japanese", see [#103](https://github.com/HugoFara/lwt/issues/103).
* Expressions containing word feature repaired! [#90](https://github.com/HugoFara/lwt/issues/90).
* On text-to-speech settings, the region was often not displayed.

### Full Changelog for 2.8.0-fork

* [2.7.0...2.8.0](https://github.com/HugoFara/lwt/compare/2.7.0...2.8.0)

## 2.7.0-fork (March 14 2023)

### Added in 2.7.0-fork

* The translation and romanization of new words can now be automatic.
  * Supported automatic translation is achieved using [LibreTranslate](https://libretranslate.com/).
  * Romanization using [MeCab](https://taku910.github.io/mecab/) (Japanese only) toward katakana only.
* A lot of improvements for language creation/edition:
  * More intuitive fields, better interactions with the user, etc...
  * A "Pop-Up" checkbox helps you choose if the website should be displayed in a pop-up.
* LWT becomes easier to install and maintain for users:
  * An installer for Linux users at ``INSTALL.sh`` helps installing requirements and configuration.
  * Database creation wizard (``database_wizard.php``) to help setting the connection with the database.
  * A server data page at ``server_data.php`` showing all relevant information.
* In ``index.php``:
  * ``get_server_data_table`` replaces ``get_server_data`` as a better designed function.
  * ``index_do_main_page`` now renders the page to avoid global variables.
  * You get warnings if the PHP version is too low, or if a newer version of LWT is released.
* ``pagestart_kernel_nobody`` in ``inc/kernel.php`` that displays the minimal HTML formatting.
* ``inc/classes/`` folder for a better incorporation of OOP.
  * ``inc/classes/Term`` defines the ``Term`` class representing a word or multi-word.
  * ``inc/classes/Language`` defines the ``Language`` class representing a language.
* Post-Install Documentation added:
  * MeCab without and with Docker, Thanks to [@99MengXin](https://github.com/99MengXin) for the link ([#94](https://github.com/HugoFara/lwt/issues/94#issuecomment-1437030748)).
  * LibreTranslate integration.
  * TTS issues, as suggested by [@asdrubalivan](https://github.com/asdrubalivan) in [#85](https://github.com/HugoFara/lwt/issues/85#issuecomment-1369751473).

### Changed in 2.7.0-fork

* Graphic review:
  * Headers reviewed. Most h4 headers became div.bigger, and h3 became h1 level headers.
  * We now use responsive design instead of fixed-size.
  * All themes updated accordingly, so themes were modified.
  * ``do_text_page`` (``do_text.php``) and ``do_test_page`` (``do_test.php``) now enlarge the body.
* ``echo_lwt_logo`` in ``inc/session_utility.php`` echoes a logo, without information on the table set used
because it was useless. This information is now displayed on the welcome page.
* Access to the database prefix edition at ``start.php`` was reviewed and simplified. It is still considered a legacy feature.
* Language creation reviewed:
  * When creating a new language, the language wizard do no longer display in a pop-up but in the main window.
  * Select option boxes replaced by checkboxes (better accessibility).
* Updated [hoverIntent jQuery Plug-in](http://briancherne.github.io/jquery-hoverIntent/)
from 1.8.0 to 1.10.2. This brings some bug fixes.
* "TO DO" changed to a more explicit "Unknown words" in text header.
* Refactored ``bulk_translate_word.php``. It has a better visual aspect and works better.
* Updated composer dependencies.
  * vimeo/psalm updated from ^4.12 to ^5.6.
  * phpunit/phpunit updated from ^9.5 to ^10.0.
* Changed to the text from YouTube feature (``text_from_yt.php``):
  * Refactored.
  * Moved from root folder to ``inc/``.
  * This is still an experimental feature!
* Moved Google API files:
  * Moved ``googleTimeToken.php`` from root folder to ``inc/google_time_token.php``.
  * Moved ``googleTranslateClass.php`` from root folder to ``inc/classes/GoogleTranslate.php``.
* Updates in demo database:
  * Faster to install. ``install_demo_db_sql.gz`` uncompressed to ``install_demo_db.sql``. Functions adapted.
  * Dict links changed: ### replaced by lwt_term, * replaced by lwt_popup=1, some dict url protocol changed to https.

### Fixed in 2.7.0-fork

* Markdown: enforced consistency with official style recommendations. The documentation display got improved.
* The default date for new words was note accepted depending on the SQL configuration, causing issues with Docker installation. See [#78](https://github.com/HugoFara/lwt/issues/78).
* Bulk translate words:
  * Translating with bulk translate was not updating word rendering.
  * All broom icons icon were display in the screen top-right corner, and not
  at the right if text field.
* Main page (``index.php``) was not rendered properly on non-default theme.
* Changing language name was breaking this, this is fixed:
  * Text header was sometimes not available.
  * It was breaking full text-to-speech dispatcher. Issue signaled [with issue #80](https://github.com/HugoFara/lwt/issues/80#issuecomment-1368180304)
* Support for PHP 7.4 restored, wrappers for ``str_starts_with``, ``str_ends_with``, ``str_contains``.
* The encoding of ``docs/info.html`` is finally fixed!
* ``ggl.php`` feature fixed!
* Multi-words:
  * Sometimes multi-words indicator was cut, resulting in strange display ([#89](https://github.com/HugoFara/lwt/issues/89)).
  * For Japanese: multi-words indicator was before each character, this is fixed [#96](https://github.com/HugoFara/lwt/issues/96).
* Wrong link to documentation (``docs/info.php`` instead of ``docs/info.html``) on the help button (main page). Signaled by babaeali on Discord.
* Depending on browser, clicking on deletion button for active texts had no effect.

### Deprecated in 2.7.0-fork

* Dictionary and translator URIs changed:
  * They should **always** be proper URLs.
  * Replacing ``###`` by ``lwt_term`` is strongly recommended!
  * "*" At the beginning of an URI should be replaced by ``lwt_popup=1`` as an argument.
  * ``ggl.php`` should be replaced by the file full URL, for instance "<http://localhost/lwt/ggl.php>".

### Full Changelog for 2.7.0-fork

* [2.6.0...2.7.0](https://github.com/HugoFara/lwt/compare/2.6.0...2.7.0)

## 2.6.0-fork (January 01 2023)

### Added in 2.6.0-fork

* Frames resizing is back! The text reading and test interfaces updated in several ways. Based on several suggestions such as [#60](https://github.com/HugoFara/lwt/issues/60).
  * The desktop interface is now much similar to what it was before 2.2.1-fork.
  * The mobile interface for texts unchanged (2.2.1-fork to 2.5.3-fork).
  * You can resize frames on desktop.
* Many new functions officially introduced in PHP and JS. Some of these function were already present in the code but undocumented.

### Changed in 2.6.0-fork

* UX: Faster testing: you do no longer need to enter "Space" first for speed testing, except for status down and change. Related to [#71](https://github.com/HugoFara/lwt/pull/71).
* UI:
  * Do no longer show "[]" near words when they are no tags. ``getWordTagList`` behavior changed (``inc/session_utility.php``).
  * Tests have a better general aspect thanks to CSS cleanning.
* PHP:
  * ``do_test.php``, ``edit_texts.php``, ``edit_words.php`` and ``set_test_status.php`` now explicitly require a running session. They were silently failing before this release.
  * ``save_setting_redirect.php`` moved to ``inc/save_setting_redirect.php``.
  * Psalm static code analysis of all PHP files.
  * We use "EOP" for end-of-paragraph markers instead of misleading "EOS" (MeCab).
  * Slightly changed how a connection is established with SQL. It makes messages more relevant when SQL is not running.
  * Not Japanese texts now always use the PHP parser. The SQL parser is no longer used.
* JS: Some deprecated functions ``escape`` and ``unescape`` were replaced by modern equivalents ``encodeURIcomponent`` and ``decodeURIcomponent``. This may lead to changes in cookies, notably making them work better.
* DB: the NO_ZERO_DATE mode is no longer required, see [#78](https://github.com/HugoFara/lwt/issues/78).
  * In the ``words`` table, replaced the default timestamp ``0000-00-00 00:00:00`` by ``0000-00-00 00:00:01``.
  * The demo database underwent the same change.
* Updated ``composer.lock``.
* Docker: more default options, documentation updated.

### Deprecated in 2.6.0-fork

* ``do_test_test_css`` in ``do_test_test.php`` is deprecated since it was causing display issues. Its CSS rules were trimmed.

### Removed in 2.6.0-fork

* The ability to use a dictionary with a specific encoding, introduced in 1.0.2, is being removed. It was making things overwhelmingly complex and caused issues, as signaled in [#58](https://github.com/HugoFara/lwt/issues/58). Adapted from PR [#59](https://github.com/HugoFara/lwt/pull/59).

### Fixed in 2.6.0-fork

* Docker:
  * Docker integration repaired ([#37](https://github.com/HugoFara/lwt/issues/37))! Huge thanks to [@hakuro-jp](https://github.com/hakuro-jp) for the initial help and [@darkone23](https://github.com/darkone23) for the final solution. Without you two I would have long given up with Docker.
  * Docker continuous integration is back as well due to a rewrite of the workflow.
* Texts:
  * The *audio* player was no longer working since 2.1.0-fork since the play button was hidden.
  * Save text position (``inc/ajax_save_text_position.php``) was broken for all texts. This is fixed.
  * Right frames should hide automatically but they often don't ([#61](https://github.com/HugoFara/lwt/issues/61)). Merged PR [#62](https://github.com/HugoFara/lwt/pull/62).
  * Japanese parsing is now better, and uses PHP only (the local_infile SQL functionality is no longer used). Related to PR [#43](https://github.com/HugoFara/lwt/pull/43).
  * One-word not Japanese texts do no longer result in a crash ([#80](https://github.com/HugoFara/lwt/issues/80)), whoever uses them.
* Tests:
  * Header was hidden during tests on Chrome-based browsers.
  * Testing specific terms was broken ([#66](https://github.com/HugoFara/lwt/issues/66)) and tests were sometimes not counting score. Solution inspired from PR [#67](https://github.com/HugoFara/lwt/issues/67) from [@jzohrab](https://github.com/jzohrab).
  * Sometimes tests were loop-reloading clicking after setting new status, this is fixed.
* JS:
  * You should no longer see annoying console messages of "cClick" crashing on "obj is null".
* PHP:
  * Type fixes thanks to psalm:
    * ``get_first_value`` documentation updated since it was also returning ``float`` and ``int``.
    * ``get_similar_terms`` in ``simterms.php`` officially returns int.
  * Inconsistent option in ``inc/ajax_save_setting.php``.
    * Since 2.2.2-fork, you had to use a GET request to use it, resulting in authorization errors.
    * POST requests are now again the default way to use it.
    * PHP tries to set the allow_local_infile option during the connection with SQL ([#20](https://github.com/HugoFara/lwt/issues/20), [#40](https://github.com/HugoFara/lwt/issues/40)).
* UI
  * Audio in ``edit_texts.php`` was never shown.
  * When adding text, the user was ask to create a ``media`` folder in ``...``, corrected to ``..``.
* DB:
  * For some users it was impossible to install the default database due to the use of a ZERO date ([#78](https://github.com/HugoFara/lwt/issues/78)).
  * Deleted wrong database instructions ``ADD DROP INDEX TiTextLC`` altering ``temptextitems`` in ``update_database`` of ``database_connect.php``.

### Full Changelog for 2.6.0-fork

* [2.5.3...2.6.0](https://github.com/HugoFara/lwt/compare/2.5.3...2.6.0)

## 2.5.3-fork (November 06 2022)

### Added in 2.5.3-fork

* Links to the Discord community (``README.md`` and ``inc/kernel_utility.php``).

### Changed in 2.5.3-fork

* Renamed ``connect_mamp.php`` to ``connect_mamp.inc.php`` for consistency with documentation.
* "I KNOW ALL" button becomes "Set All to Known" and "IGNORE ALL" becomes "Ignore All".
* Changed the messages when clicking "Set All to Known" or "Ignore All".
* Uncomplete code linting in ``bulk_translate_words.php``. For phone users, it now properly focuses on the main screen after use.
* Only sentences containing more than 70% of known words are shown were testing sentences. Based on an idea from [#53](https://github.com/HugoFara/lwt/issues/53), with contribution of PR [#65](https://github.com/HugoFara/lwt/pull/65).

### Fixed in 2.5.3-fork

* Left-to-right languages where displayed as right-to-left when checking them, as signaled in [pull request #42](https://github.com/HugoFara/lwt/pull/42), thanks [@Heliozoa](https://github.com/Heliozoa)!
* People who didn't fill the URL for Google Translate were seeing deprecation warnings while using using the ``bulk_translate`` functionality (clicking in "TO DO" for editing multiple new words). Bulk translate itself is not fixed, but warnings are no longer displayed. Merged [pull request #44](https://github.com/HugoFara/lwt/pull/44), from [@Heliozoa](https://github.com/Heliozoa).
* Multiple fixes to the "I Known All Words" feature. Great thanks to [@jzohrab](https://github.com/jzohrab)!
  * Setting all words to well-known was resulting in a crash when no words were added as well-known (``all_words_wellknown.php``).
  * Setting all words to well-known was resulting in a crash when multiple words had the same lowercase value. See issue [#46](https://github.com/HugoFara/lwt/issues/46)!
  * Words were not updated in the view unless reparsing the text ([#48](https://github.com/HugoFara/lwt/pull/48)). Merged pull request [#49](https://github.com/HugoFara/lwt/pull/49).
* In the main dropdown menu, the option "Text-to-Speech Settings" was still leading to ``tts.php`` (now ``text_to_speech_settings.php``). Merged pull request [#51](https://github.com/HugoFara/lwt/pull/51), thanks [@jzohrab](https://github.com/jzohrab).
* Database backup/restoration:
  * Fixed database backup, signaled in [#55](https://github.com/HugoFara/lwt/pull/55).
  * Fixed the database restoration, apparently broken since 2.0.3-fork. Pull request [#56](https://github.com/HugoFara/lwt/pull/56) by [@jzohrab](https://github.com/jzohrab).
* Sentences with unknown words were showed in test when it was not supposed to be the case ([#52](https://github.com/HugoFara/lwt/issues/52) and [#64](https://github.com/HugoFara/lwt/issues/64)).

### Full Changelog for 2.5.3-fork

* [2.5.2...2.5.3](https://github.com/HugoFara/lwt/compare/2.5.2...2.5.3)

## 2.5.2-fork (September 27 2022)

### Changed in 2.5.2-fork

* Refactored ``upload_words.php``.

### Fixed in 2.5.2-fork

* Multi-words insertion for languages using no space is now repaired! Thanks to [@gaoxiangluke](https://github.com/gaoxiangluke) for signaling it ([#38](https://github.com/HugoFara/lwt/issues/38)).
* Terms import (``upload_words.php``) can now be used with ``@@GLOBAL.local_infile`` (MySQL) set to 0.
* Deleted a parasite ";" that was displayed after importing terms.

### Full Changelog for 2.5.2-fork

* [2.5.1...2.5.2](https://github.com/HugoFara/lwt/compare/2.5.1...2.5.2)

## 2.5.1-fork (September 16 2022)

### Fixed in 2.5.1-fork

* Having ``@@GLOBAL.local_infile`` (MySQL) set to 0, or any configuration disabling ``LOAD DATA LOCAL INFILE``, was causing a fatal error on adding a new text. This is fixing by a slower method.
  * If ``local_infile`` is enabled, no change should be noticed.
  * This was signaled in issues [#20](https://github.com/HugoFara/lwt/issues/20) and [#40](https://github.com/HugoFara/lwt/issues/40).
* The WordPress binding scripts had multiple issues as reported in [#41](https://github.com/HugoFara/lwt/issues/41), thanks [@Redmattski](https://github.com/Redmattski)!

### Full Changelog for 2.5.1-fork

* [2.5.0...2.5.1](https://github.com/HugoFara/lwt/compare/2.5.0...2.5.1)

## 2.5.0-fork (July 08 2022)

### Added in 2.5.0-fork

* Compatibility with PHP 8+!
* Updated with LWT 2.0.3, solving incompatibilities with PHP 8.1 throwing MySQLI errors.
* Introducing the new class ``Term`` in ``edit_mword.php``. This class is yet to be considered experimental, so expect important changes, but should gradually get used everywhere.
* ``insert_expression_from_mecab`` in ``session_utility.php``. Better name and behavior for ``insertExpressionFromMeCab`` (see deprecations).

### Changed in 2.5.0-fork

* Composer dependencies updated.
* The Docker container now uses PHP 8.1 (instead of 7.4).

### Fixed in 2.5.0-fork

* For some user, the --secure-priv-file option was still required to be on. This was due to a conflict between PHP and MySQLI authorizations.
* When deleting a word, it was previously necessary to reload the page to see a visual change.
* Multi-words insertion fixed.
* "Show Sentences" button during words edition was broken since 2.3.0-fork.

### Full Changelog for 2.5.0-fork

* [2.4.1...2.5.0](https://github.com/HugoFara/lwt/compare/2.4.1...2.5.0)

### Deprecated in 2.5.0-fork

* ``set_word_count`` in ``database_connect.php`` had a misleading name. It was changed to ``init_word_count``.
* ``insertExpressionFromMeCab`` deprecated for ``insert_expression_from_mecab`` in ``session_utility.php``.
* PHP <8 won't be tested anymore. You are highly encouraged to update to PHP 8+!

## 2.0.3 (February 15 2022)

### Fixed in 2.0.3

* An incompatibility with PHP 8.1+ (changed mysqli_report default setting in PHP 8.1+) has been fixed.

## 2.4.1-fork (June 09 2022)

### Changed in 2.4.1-fork

* Some function signature were not logical in ``edit_texts.php``. These signatures remain untouched for backward compatibility, but the internals were changed.
* Texts without tag do no longer display a "[]" string next to the title. Applies to both archived and active texts.

### Fixed in 2.4.1-fork

* A typo was breaking the feeds count in ``edit_languages.php``, creating annoying notices as illustrated at [#35](https://github.com/HugoFara/lwt/issues/35).
* The error "[1290] The MySQL server is running with the --secure-file-priv option" should no longer appear when trying to save Japanese texts. It was referenced [in this comment](https://github.com/HugoFara/lwt/issues/34#issuecomment-1141976723) in [issue #34](https://github.com/HugoFara/lwt/issues/34).
* The "Undefined index: trans in .../bulk_translate_word.php" notice fixed.
* The "Undefined index: WoText in .../delete_word.php" notice fixed.
* Repaired ``long_text_import.php`` for non-Japanese texts, it was broken since 2.4.0. Thanks to [@rc-ops](https://github.com/rc-ops) for this issue [#33](https://github.com/HugoFara/lwt/issues/33).
* Text statistics were not displayed when there was more than one text since 2.2.2-fork.

### Full Changelog for 2.4.1-fork

* [2.4.0...2.4.1](https://github.com/HugoFara/lwt/compare/2.4.0...2.4.1)

## 2.4.0-fork (May 23 2022)

### Added in 2.4.0-fork

* The external dependency for Japanese parsing, MeCab, is now more easily detected on Windows and Linux.
* Better integration of Docker.

### Changed in 2.4.0-fork

* You must not only include ``pgm.js`` to ask the user before exiting but also the new function ``ask_before_exiting``.
* ``splitCheckText`` was split into smaller functions.
* Refactored ``edit_languages.php`` with functional paradigm.

### Fixed in 2.4.0-fork

* Many pages were asking before exiting while it was unnecessary.
* Made bigger buttons and unified the presentation of ``edit_texts.php``,
``edit_archivedtexts.php``, ``long_text_import.php`` and ``do_feeds.php``. It solves
issue [#29](https://github.com/HugoFara/lwt/issues/29).
* Japanese texts were ill parsed on Windows. The issue [#23](https://github.com/HugoFara/lwt/issues/23) is now solved!
[@rc-ops](https://github.com/rc-ops), arigatou!
* The number of texts, expressions and so on was always 0 in ``edit_languages.php``.
* On Windows, annoying notices were often displayed in ``edit_languages.php``.

### Removed in 2.4.0-fork

* The HTML code documentation is no longer included in the dev branch.
* The unnecessary JS files were removed since they were all merged in ``js/``.

### Full Changelog for 2.4.0-fork

* [2.3.0...2.4.0](https://github.com/HugoFara/lwt/compare/2.3.0...2.4.0)

## 2.3.0-fork (April 25 2022)

### Added in 2.3.0-fork

* Commands included in ``Makefile`` where transcripted in ``composer.json``.
* [Docker](https://www.docker.com/) integration! It is done through ``Dockerfile``, ``docker-compose.yml``, ``.dockerignore`` and ``.env`` files.
* You can click on a read icon in order to read a word.
* Text-To-Speech (TTS) Settings!
  * Change the language rate, pitch, or local region for text-to-speech.
* Early support of local videos, discussed in [#9](https://github.com/HugoFara/lwt/issues/9) with [@chaosarium](https://github.com/chaosarium).

### Changed in 2.3.0-fork

* mbstring and mysqli extensions are now clearly asked for by Composer.
* The "database update" part of ``check_update_db`` in ``inc/database_connect.php`` was moved to ``update_database``.
* Almost all JS goes in one file when minified. This has several reasons:
  * Better browser caching: JS code is downloaded once for all.
  * Easier maintaining: it was difficult to manage which php file was needing which JS script.
  * Consistency: scripts were calling functions that should be imported from other files, making the debugging difficult.
* Feed wizard changes
  * The feed wizard got a little broken: the "next" button is always active, even if you did not select text and click "Get".
  * It's style was uniformized with your current theme.
* ``tts.php`` becomes ``text_to_speech_settings.php``. As this file was unused, it is not considered as a breaking change.
* Refactored ``all_words_wellknown.php``, ``edit_texts.php``. Those pages should load a bit faster.
* We do no longer load pages in [Almost Standards Mode](https://developer.mozilla.org/en-US/docs/Web/HTML/Quirks_Mode_and_Standards_Mode). Unless you use some Netscape browser, it should not impact you.

### Deprecated in 2.3.0-fork

* Use ``clean-doc`` install of ``clean`` in ``Makefile`` because it was ambiguous with composer commands.

### Fixed in 2.3.0-fork

* Creating the database was sometimes impossible.
* Database names containing special characters (hyphens, carets, etc...) was not possible.
* The "I KNOW ALL" button calling ``all_words_wellknown.php`` created errors. This file was fixed. Thanks [@nghiaphamtm](https://github.com/nghiaphamtm) (issue [#26](https://github.com/HugoFara/lwt/issues/26)).
* In ``edit_texts.php``, it was not displayed in the barchart when knowing 1 word. Thanks [@chaosarium](https://github.com/chaosarium) for signaling it (issue [#11](https://github.com/HugoFara/lwt/issues/11)).
* Tags were breaking text modification. Issue [#12](https://github.com/HugoFara/lwt/issues/12), thanks [@chaosarium](https://github.com/chaosarium).
* In edit_texts.php, unknown words (with status 0), had no abbreviation display between parentheses. It was showing "Unknown ()".

### Full Changelog for 2.3.0-fork

* [2.2.2...2.3.0](https://github.com/HugoFara/lwt/compare/2.2.2...2.3.0)

## 2.2.2-fork (February 13 2022)

### Added in 2.2.2-fork

* A contribution guide at ``docs/contribute.md``.

### Changed in 2.2.2-fork

* Updated the jQuery deprecated events. It should have no consequence.
* Slightly changed the behavior of the CSS/JS minifiers. The relative paths in the return string were different from the ones in the saved file.
* Regenerated documentation.

### Fixed in 2.2.2-fork

* Some AJAX files could not work properly since 2.0.3-fork.
  * Refreshing the list of audio files in ``edit_texts.php`` works again.
  * Theme in ``info.html`` works now.
  * Some other problems may have been fixed.
* With ``do_test?text=``, the language name displayed instead of "[L2]" was often wrong.
* ``$fixed_tbpref`` was never declared at global scope.
* Fixed an incompatibility in ``database_connect.php``, ``splitCheckText`` with PHP <7.4.
* Impossible to start bulk_translate_new_words from ``do_text.php``.
* The audio player does no longer show at the end of the page (DOM node was not closed).
* Relative paths in themes were often broken. For instance: no images in audio player.
  * An explanation was also added on how to add custom images in your theme.
* Very small errors corrected in some themes.

### Deprecated in 2.2.2-fork

* Deprecated a lot of camelCase functions in ``do_text_text.php`` to their snake_case counterpart. The behavior of the deprecated functions did not change.
  * camelCase to snake_case: ``getTextData`` to ``get_text_data``, ``sentenceParser`` to ``sentence_parser``, ``wordParser`` to ``word_parser`` and ``mainWordLoop`` to ``main_word_loop``
  * Typo fixing: ``getLanguagesSettings`` to ``get_language_settings`` (use singular)
  * Signature changed: ``echoTerm`` to ``echo_term`` (no return value, no ``$hideuntil`` parameter)
  * Name uniformisation: ``prepareStyle`` to ``do_text_text_style`` and  ``do_text_javascript`` to ``do_text_text_javascript``.

### Full Changelog for 2.2.2-fork

* [2.2.1...2.2.2](https://github.com/HugoFara/lwt/compare/2.2.1...2.2.2)

## 2.2.1-fork (February 07 2022)

### Changed in 2.2.1-fork

* Composer in no longer *required* for standard users (but is still required for contributing).
* Updated ``README.md``.
* [league/commonmark](https://packagist.org/packages/league/commonmark) becomes a dev requirement (no longer required for everyone).
* Re-minified ``jquery.tagit.css``, it may have visual consequences.
* Replaced some jQuery functions by their equivalents. It should not have consequences.

### Fixed in 2.2.1-fork

* Calling ``do_text.php?text=`` created a database error, it does no longer.
* Long text were laggy in ``do_text_text.php`` since 2.0.3-fork. Some other issues may have been fixed at the same time.
* Possible insecure PHP string to JS string conversion in ``do_text_header.php``, function ``browser_tts``.

### Deprecated in 2.2.1-fork

* The ``is_mobile`` function now always returns false.
* The ``wordProcessor`` (``do_text_text.php``) function was incorrect.
  * It now always return 0.
  * Please use ``wordParser``, ``sentenceParser`` and some more code instead.

### Removed in 2.2.1-fork

* Removed unnecessary dependencies:
  * [components/jquery](https://packagist.org/packages/components/jquery) version ^3.6 was required, but only 1.12.4 was in use.
  * [flesler/jquery.scrollto](https://packagist.org/packages/flesler/jquery.scrollto) already bundled by git.
  * [mobiledetect/mobiledetectlib](https://packagist.org/packages/mobiledetect/mobiledetectlib) was unused. It is now removed.
  * [happyworm/jplayer](https://packagist.org/packages/happyworm/jplayer) is no longer integrated by composer (no update since 2014), but it still in use.
* The ``components/`` folder was also deleted. It was bundling JS code from composer.
  * It should have been git ignored at least.
  * Its content was unused. The files it was trying to use are duplicated.

### Full Changelog for 2.2.1-fork

* [2.2.0...2.2.1](https://github.com/HugoFara/lwt/compare/2.2.0...2.2.1)

## 2.2.0-fork (February 04 2022)

### Added in 2.2.0-fork

* Sounds while testing terms!
* New minifier for themes: it is now very easy to create new themes.
* JS files added to documentation.
* ``info_export_template.md`` was imported and adapted from official documentation.

### Changed in 2.2.0-fork

* All the do_test*.php part do no longer use frames.
* All the display_impr_text*.php part do no longer use frames.
* It means LWT is now mobile-friendly!
* Regenerated themes.
* "do_text.php?text=..." is the official way to call texts. The "start" argument is still supported.
* New and enhanced design for the welcome page (``index.php``)
* Enhanced semantic for ``docs/info.html``, and for ``docs/*.md`` files.
* The Doxygen-generated content now uses LWT default style.

### Fixed in 2.2.0-fork

* Several database flaws fixed. Now the database should stop rotting with time.
* It is easier to close the right frames in do_text.php.
* When viewing the maximum number of results per page (edit_text.php for instance),
the maximum value was shown as a floating point value.

### Removed in 2.2.0-fork

* Floating Menu from JTricks.com was unused since 2.0.4-fork. It is now deleted.

### Full Changelog for 2.2.0-fork

* [2.1.0...2.2.0](https://github.com/HugoFara/lwt/compare/2.1.0...2.2.0)

## 2.1.0-fork (January 09 2022)

### Added in 2.1.0-fork

* Badges in the README providing up-to-date information on the state of the project.
* When reading, right frames are hidden and will slide into screen when needed.

### Changed in 2.1.0-fork

* PHP >=7.4 is now the official PHP version.
* Refactored the do_test* pages.
* Better CSS minification.
* Code base inconsistencies and security issues fixed
(level 5 and above psalm errors fixed).
* Code is much more strongly typed (~80% of the code base). Level 4 psalm errors partially fixed.
* GitHub continuous integration reviewed.
* Regenerated documentation.

### Fixed in 2.1.0-fork

* ``composer.json`` is now working! Thanks [chaosarium](https://github.com/chaosarium) for signaling
this [issue #4](https://github.com/HugoFara/lwt/issues/4)!
* Since 2.0.3-fork, it was difficult to get annotations. This is no longer the case.
* [tag-it](https://github.com/aehlke/tag-it), [jquery-hoverintent](https://github.com/briancherne/jquery-hoverIntent),
and [jquery-xpath](https://github.com/ilinsky/jquery-xpath) are now copied from raw code, and no longer integrated by composer.

### Removed in 2.1.0-fork

* Effectively dropped support for PHP <=7.

### Full Changelog for 2.1.0-fork

* [v2.0.4-fork...v2.1.0](https://github.com/HugoFara/lwt/compare/v2.0.4-fork...v2.1.0)

## 2.0.4-fork (December 03 2021)

This version brings a better composer compatibility, and starts revamping
mobile compatibility.

### Changed in 2.0.4-fork

* Starting to refactor for 2021 HTML!
* Much less iframes for reading texts.
* Now you can read texts on mobile without the experimental mobile LWT.
* Texts can be read using "focus mode" on most browsers.
* Updated documentation (expanded and refactored).
* Composer is now the recommended way to download lwt.
* PHP_codesniffer is now recommended, and no longer dev-required.
* Refactored many parts of the code, that gets easier to read.

### Removed in 2.0.4-fork

* ``composer.phar`` and ``composer.lock`` are now git ignored.

### Full Changelog for 2.0.4-fork

* [v2.0.3-fork...v2.0.4-fork](https://github.com/HugoFara/lwt/compare/v2.0.3-fork...v2.0.4-fork)

## 2.0.3-fork (November 26 2021)

Serious maintaining is back!

This version should be the stable merge between official v2.0.2 and community maintained 1.6.31-fork.

### Added in 2.0.3-fork

* Show Learning translation setting.
* README.md created
* Code documentation.
* Automatic text-to-speech.
* Integrating Composer to manage dependencies.
* Issue templates for GitHub.
* Markdown integration in PHP.
* Video player for texts.
* Makefile to simplify workflows.

### Changed in 2.0.3-fork

* MeCab is now the default way to learn Japanese.
* JS and CSS are now minified.
* Code linting.
* Important code refactors.

### Full Changelog for 2.0.3-fork

* [v1.6.31-fork...v2.0.3-fork](https://github.com/HugoFara/lwt/compare/v1.6.31-fork...v2.0.3-fork)

## 2.0.2 (September 07 2021)

### Fixed in 2.0.2

* An incompatibility with PHP 8+ (removed function "get\_magic\_quotes\_gpc()" in PHP 8+) has been fixed. Thanks to Lucas L. for the hint.  

## 2.0.1 (October 07 2020)

### Fixed in 2.0.1

* A bug when visiting terms/expressions with key strokes LEFT or RIGHT after a previous status change and with a set status filtering has been fixed.  

## 2.0.0 (October 04 2020)

### Fixed in 2.0.0

* No code changes. Sourceforge links corrected.  
* The old links \[lwt.sf.net\], \[lwt.sourceforge.net\] or \[sourceforge.net/projects/lwt\] are no longer valid!  
* The new links are now [learning-with-texts.sourceforge.io](https://learning-with-texts.sourceforge.io) (documentation and demo database) and [sourceforge.net/projects/learning-with-texts](https://sourceforge.net/projects/learning-with-texts) (project home and downloads).

## 1.6.3 (April 06 2020)

### Added in 1.6.3

* Some missing confirmation dialogues (when deleting a single text, text tag, term, term tag, or language) added.

## 1.6.2 (March 10 2018, this page "info.php" last updated August 12 2019)

### Added in 1.6.2

* Audio playback speed can now be set between 0.5x and 1.5x.  
* Waiting wheel (to indicate saving data to database in the background) added in "Edit Improved Annotated Text".  
* Checking for characters in the Unicode Supplementary Multilingual Planes (> U+FFFF) like emojis or very rare characters improved/added. Such characters are currently not supported.

### Changed in 1.6.2

* jQuery library updated to v1.12.4.  
    "Mobile\_Detect.php" updated to v2.8.30.  
* LWT demo database updated.  
* Documentation updated.

### Fixed in 1.6.2

* Some minor glitches fixed.

### Removed in 1.6.2

* Glosbe API calls via "glosbe\_api.php" in demo database and language settings wizard removed - they often did not work due to API restrictions. The file "glosbe\_api.php" is still supplied as an example of a close integration of a dictionary API into LWT.  

## 1.6.1 (February 01 2016, this page "info.php" last updated January 13 2018)

### Added in 1.6.1

* [Chinese text segmentation "Jieba" link](info.html#links) added in documentation (Important Links - Additional Resources - For learners of Chinese).

### Changed in 1.6.1

* The jQuery and jPlayer libraries have been updated to v1.12.0 and v2.9.2, respectively.
* The jQuery.ScrollTo package has been updated to v2.1.2.

## 1.6.31-fork (October 03 2016)

### Fixed in 1.6.31-fork

* Multibyte character parsing fixed (i.e. Chinese).  

## 1.6.30-fork (July 28 2016)

### Added in 1.6.30-fork

* MeCab support (in development).

### Fixed in 1.6.30-fork

* Google translate API updated.  

## 1.6.29-fork (April 21 2016)

### Changed in 1.6.29-fork

* In abbreviations like 'Mr.' the dot is now part of the term. A reparse of texts is needed to take effect.  
* Wizard Language: Arabic 'RegExp Word Characters' changed.  

### Fixed in 1.6.29-fork

* ggl.php API doesn't work (Token generation fixed).  
* Dictionary doesn't open.  
* Negative/uncorrect WordCount in 'edit\_texts\_php'.  

## 1.6.28-fork (April 07 2016)

### Added in 1.6.28-fork

* DB collation check added.

### Changed in 1.6.28-fork

* Wizard Language Defaults changed.

### Fixed in 1.6.28-fork

* Access denied, LOAD DATA INFILE error (text parsing on a server).  
* 'remove spaces' not working.  

## 1.6.27-fork (February 21 2016)

### Fixed in 1.6.27-fork

* Bugfix: DB backup/import errors.
* RegExp Word Characters are checked for correct syntax when creating/updating language.

### Removed in 1.6.27-fork

* thumbnail/image support removed.  

## 1.6.26-fork (February 11 2016)

### Changed in 1.6.26-fork

* Demo Database updated.  
* mysqli changes from orig. LWT.  
* php-mobile-detect updated.  

### Fixed in 1.6.26-fork

* Some bugfixes: errors when emptying database and installing Demo database, added 'follow redirect' to feed.

## 1.6.25-fork (January 31 2016)

### Added in 1.6.25-fork

* Added German Feed [NachDenkSeiten](http://www.nachdenkseiten.de/?feed=audiopodcast) to DemoDatabase.  
* Added saved expressions to CheckText.

### Changed in 1.6.25-fork

* SplitCheckText rewritten.
* Database changes (table 'temptextitems'): added: TiCount, dropped: TiLgID, TiTxID, TiTextLC and index TiTextLC  
* Database changes : dropped: index WtWoID, index TtTxID, index AgAtID

### Fixed in 1.6.25-fork

* Bugfix: No Word Counts displayed when text has no saved words.

## 1.6.0 (January 28 2016)

### Changed in 1.6.0

* As mysql\_\* database calls are deprecated and are no longer supported by PHP, they have been changed to the corresponding mysqli\_\* calls. If you run a server with PHP version 7.0.0 or higher, you MUST use LWT 1.6.0 or higher. Thanks to Laurens Vercaigne for his work!  
* Debugging updated.
* Status information on start page improved.
* Documentation updated.

## 1.5.21 (January 14 2016)

### Changed in 1.5.21

* [Soft hyphens](https://en.wikipedia.org/wiki/Soft_hyphen) (U+00AD, UTF-8: 0xC2 0xAD) are now automatically removed during text import.  
* "Mobile\_Detect.php" updated to v2.8.19.

## 1.6.24-fork (January 11 2016)

### Added in 1.6.24-fork

* Added possibility to switch between 'unique' and 'total' word count by clicking on 'u'/'t'-button.  

### Changed in 1.6.24-fork

* HTML lang attribute added in testing frame.  
* Speed improvements in 'My Texts' screen.  
* Replaced percentage of 'unknown words' by 'word charts' in 'My Texts' screen.  

### Removed in 1.6.24-fork

* Deleted Setting: Show Word Counts of Texts immediately

## 1.6.23-fork (December 13 2015)

### Added in 1.6.23-fork

* HTML lang attribute added in reading frame.

### Changed in 1.6.23-fork

* Jplayer, Jquery, JqueryUI updated.  
* Google Translate API changes: random google domain access added (domain can be changed in googleTranslateClass.php), updated generateToken.

## 1.6.22-fork (November 11 2015)

### Added in 1.6.22-fork

* Google Translate API(ggl.php): added headers and corrected token.

## 1.6.21-fork (October 16 2015)

### Added in 1.6.21-fork

* Google Translate API(ggl.php): added token to URL.

## 1.6.20-fork (September 26 2015)

### Changed in 1.6.20-fork

* Jquery Changes in the reading frame for hover\_over/highlight words.

### Fixed in 1.6.20-fork

* Google TextToSpeech callback fixed.

## 1.6.19-fork (August 29 2015)

### Added in 1.6.19-fork

* New default settings: 'Tooltips' (new Default: 'JqueryUI') and 'Position of translations' (new Default: 'below').

### Changed in 1.6.19-fork

* Mysql login process changed.  
* JPlayer CSS and Skin changes. Skin are now integrated into 'Themes'.  

## 1.6.18-fork (June 11 2015)

### Added in 1.6.18-fork

* New Setting: Position of translation  
* Translations can now be displayed 'behind', 'in front of', 'above' or 'below' the term in the reading frame.  
* Improved encoding detection in 'newsfeed import'.

### Fixed in 1.6.18-fork

* Words that are created by 'bulk import' are not updated in the reading frame (i.e. when new translations are added afterwards).  
* mysql error 'duplicate entry' in 'newsfeed import'.  

## 1.6.17-fork (May 09 2015)

### Fixed in 1.6.17-fork

* Newsfeed Import doesn't load new links if WordPress is used for multiple users.  

## 1.6.16-fork (May 01 2015)

### Changed in 1.6.16-fork

* Improved Sentence Bondary Detection when parsing texts.  
* User ID is saved in the PHP Session Variable (instead of a Cookie) if wordpress is used to log in.
* Switch to mysqli extension for database connection.  
* Minified CSS and Javascript files; the uncompressed files can be found in the directory 'src'.

## 1.6.15-fork (April 10 2015)

### Changed in 1.6.15-fork

* Some CSS changes.

### Fixed in 1.6.15-fork

* Ggl API retrieval error fixed.

## 1.6.14-fork (March 28 2015)

### Changed in 1.6.14-fork

* Glosbe API now uses javascript (browser based) instead of php(server based) to prevent possible retrieval errors when LWT is installed on a webhoster for multiple users.

## 1.6.13-fork (March 23 2015)

### Added in 1.6.13-fork

* New Setting: Tooltips (JQueryUI will show images in Tooltips in the Read Text Screen)  
* New Feature: You can now add thumbnail images to your terms. If you click on the icon at the left of the translation field in the new\_term/edit\_term frame/window you can select an thumbnail from 'google image search'. In order to display the images in the Read Text Screen you must set 'Tooltips' to 'JQueryUI'. The thumbnail images are not included in the backup at the moment.  
* New Feature: Key binding J for edit term with Google Image Search added

### Fixed in 1.6.13-fork

* 'error when making backup' fixed  

## 1.6.12-fork (March 01 2015)

### Changed in 1.6.12-fork

* Jquery, JqueryUI updated  

### Fixed in 1.6.12-fork

* CSS/jquery fixes when selecting multiple word expressions in text frame  
* Bugfix: new or imported multiple word expressions are not show in the text with 'remove Spaces' is 1 and 'split Each Char' is 0  

## 1.6.11-fork (February 09 2015)

### Fixed in 1.6.11-fork

* Bugfix: 'Show term sentences' and 'Create term sentences' fixed  

## 1.6.10-fork (January 25 2015)

### Changed in 1.6.10-fork

* CSS changes for firefox (version >= 35) in dark themes  

### Fixed in 1.6.10-fork

* Bugfixes in bulk import terms  

## 1.6.9-fork (December 21 2014)

### Fixed in 1.6.9-fork

* Getting article from feed even if there is no link  

## 1.6.8-fork (December 19 2014)

### Changed in 1.6.8-fork

* Google API can now do a requery  
* Dict Lookup from bulk import terms frame is now possible  

## 1.6.7-fork (December 18 2014)

### Fixed in 1.6.7-fork

* Database error in newsfeeds

## 1.6.6-fork (December 16 2014)

### Added in 1.6.6-fork

* Ability to change audio playback speed (doesn't work when using the flash plugin)  
* Combine translation field option when importing words ('Merge translation fields' or 'Update existing translations')  

## 1.6.5-fork (December 01 2014)

### Fixed in 1.6.5-fork

* Error in 'upload\_words.php'  

## 1.6.4-fork (November 29 2014)

### Fixed in 1.6.4-fork

* Wrong dict links for sentence translate  

## 1.6.3-fork (October 12 2014)

### Added in 1.6.3-fork

* Key binding G for edit term with Google Translate added

### Changed in 1.6.3-fork

* Google api rewritten (works on webhoster with cURL-plugin)  

## 1.6.2-fork (October 06 2014)

### Added in 1.6.2-fork

* Key binding T for translating sentence added  
* New Backup Option: official LWT backup added

### Changed in 1.6.2-fork

* Database Changes: indexes changed in table words  

## 1.6.1-fork (September 28 2014)

### Added in 1.6.1-fork

* Translations of terms can now be display in the reading frame  
* Key bindings when hovering over words in the reading frame  
* Bulk translate new words in the reading frame

Changes from official LWT version 1.5.20 imported:  

* Possibility to display similar terms while creating or editing a term. This will give you more language insight, and may ease inputting new terms that are similar. The number of displayed similar terms can be set from 0 (old behavior, default) to 9 on the "Settings" page. Clicking on the green icon in front of a similar term will copy the translation and romanization into the form fields for further editing. Important: If you want to use this new feature, you must change the setting "Similar terms to be displayed while adding/editing a term" to a value greater than 0. It will make more sense to do this if you have already many saved terms (e.g. more than 1,000). If you start with a language and have only a few terms, no or not very similar terms will be normally displayed and this feature will not make much sense.
* "https://" dictionary URIs are now allowed in the language settings. Checking of dictionary URIs in the language settings has been improved.

### Changed in 1.6.1-fork

* The Glosbe dictionary page has been improved with a simple form to change the term and do a requery if you are unhappy with the query results.
* The jQuery and jPlayer libraries have been updated to v1.11.1 and v2.7.0, respectively. The jQuery.ScrollTo package has been updated to v1.4.13. The Floating Menu package has been updated to v1.12.  

* Broken links corrected or deleted. From now on, only the installation with EasyPHP for Windows and MAMP for Mac OS X will be explained in detail and is recommended. Other local web server packages, like XAMPP, etc., are of course still possible, but it's beyond the scope of this document to explain all the details for every webserver package (it will also confuse most LWT users who are not familiar with web server packages and their setup).
* "Mobile\_Detect.php" updated to v2.8.3.
* Documentation updated.

## 1.5.20 (September 22 2014)

### Changed in 1.5.20

* "Mobile\_Detect.php" updated to v2.8.3.

### Fixed in 1.5.20

* Missing volume controls in audio player (only on mobile devices) fixed.  

## 1.5.19 (September 15 2014)

* Missing tag cache updating fixed (in "Add tag in all/marked texts or terms").  
* Tag caches now also work properly if several instances of LWT are installed in parallel directories on the same server.  
* Information about [which web browser to use for LWT](info.html#abstract) in this document updated.  

## 1.5.18 (September 14 2014)

### Added in 1.5.18

* Possibility to display similar terms while creating or editing a term. This will give you more language insight, and may ease inputting new terms that are similar. The number of displayed similar terms can be set from 0 (old behavior, default) to 9 on the "Settings" page. Clicking on the green icon in front of a similar term will copy the translation and romanization into the form fields for further editing. Important: If you want to use this new feature, you must change the setting "Similar terms to be displayed while adding/editing a term" to a value greater than 0. It will make more sense to do this if you have already many saved terms (e.g. more than 1,000). If you start with a language and have only a few terms, no or not very similar terms will be normally displayed and this feature will not make much sense.
* New sort option for texts, terms or tags: "Oldest first".  
* The Catalan language has been added to the Language Settings Wizard.

### Changed in 1.5.18

* "https://" dictionary URIs are now allowed in the language settings. Checking of dictionary URIs in the language settings has been improved. The Glosbe dictionary page has been improved with a simple form to change the term and do a requery if you are unhappy with the query results.  
* The jQuery and jPlayer libraries have been updated to v1.11.1 and v2.7.0, respectively. The jQuery.ScrollTo package has been updated to v1.4.13. The Floating Menu package has been updated to v1.12.  
* Some error messages (term/tag already exists) have been improved.  
* Documentation updated.

### Fixed in 1.5.18

* Some minor bugs fixed: media selection in archived texts, tag import errors, adding existing tag errors, etc.

### Removed in 1.5.18

* The audio player skin selection has been removed; the "Blue Monday Small" skin is the standard skin beginning with this release.  

## 1.6.0-fork (September 12 2014)

### Added in 1.6.0-fork

* Longer (>9) expressions can now be saved  
* TextToSpeech support for words added  
* Experimental google api (use 'ggl.php' instead of '\*<http://translate.google.com>' for google translate)  
* New word select mode in read texts (hold down mouse button)

### Changed in 1.6.0-fork

* Database Changes: table textitems replaced by textitems2, temporary tables added, global table tts added  
* statistics.php, upload\_words.php rewritten  

## 1.5.17-fork (June 08 2014)

### Added in 1.5.17-fork

* New Feature: Selecting terms according to a text tag  
* New Feature: Start a document where you left off (only "Read Text Screen")  
* New Feature: Improved Search/Query for Words/Texts  
* New Feature: Automatically import texts from RSS feeds (for more info see: [Newsfeed Import](info.html#Newsfeed%20Import))  
* New Setting: Button(s) for "words to do" "IGNORE ALL"/"I KNOW ALL"  
* New Setting: Theme  
* New Setting: term/word query with standard/regexp/regexp CS  
* New Sort option "Oldest First"  
* New option "Set Active Term(1-5) Sentences" in My Texts

### Changed in 1.5.17-fork

* JQuery, JQuery UI, JPlayer, jQuery.ScrollTo, Tag-it, Sorttable and Floating Menu updated  
* Database table optimization: first check, only optimize if (Overhead >10% of table and > 100KB) or (Overhead > 1,0MB)  
* Database table optimization: data types changed.  
* Documentation updated.

### Fixed in 1.5.17-fork

* Importing multiple words with the same tag causes an error  
* Can't select media in Archived Texts  
* Confirmation-popup when leaving via selectbutton in Settings/Preferences even if there are no changes (chrome-browser)  
* Bottom page select doesn't work in firefox (edit\_texts.php, edit\_words.php, edit\_archivedtexts.php, edit\_texttags.php, edit\_tags.php)  
* Setting a tag where tag already exists causes an error  
* New tag isn't saved in SESSION VAR (when adding a new text with a new tag / may cause an error when editing that text)  

## 1.5.17 (August 15 2014, this document updated Aug 17 2014 and Aug 24 2014)

### Changed in 1.5.17

* Documentation updated. Broken links corrected or deleted. From now on, only the installation with EasyPHP for Windows and MAMP for Mac OS X will be explained in detail and is recommended. Other local web server packages, like XAMPP, etc., are of course still possible, but it's beyond the scope of this document to explain all the details for every webserver package (it will also confuse most LWT users who are not familiar with web server packages and their setup).  
* Documentation updated on August 17 2014: Installation screencasts added.  
* Documentation updated on August 24 2014: Linux (Ubuntu, LinuxMint) installation hints and screencast added.

### Fixed in 1.5.17

* Minor bug in Utilities fixed.  

## 1.5.16 (February 19 2014)

### Changed in 1.5.16

* Documentation updated.

### Fixed in 1.5.16

* Paging (via page select, and only at the bottom of a page) did not work correctly in all cases, has been corrected.

## 1.5.15 (December 17 2013)

### Changed in 1.5.15

* Documentation updated.

### Fixed in 1.5.15

* Corrected wrong language code (French) within the language wizard definitions.

## 1.5.14 (August 05 2013, this document updated Oct 30 2013)

### Changed in 1.5.14

* Documentation updated on October 30 2013.

### Fixed in 1.5.14

* Wrong text display in Print Screen corrected. Special handling of word breaks (if "Remove spaces" = Yes) removed.  

## 1.5.13 (July 22 2013)

### Changed in 1.5.13

* License texts updated according to text on [unlicense.org](http://unlicense.org/).  
* Documentation updated.

### Fixed in 1.5.13

* Removed an erroneous extra space in "wp\_logincheck.inc.php". Thanks to a post in the help forum for pointing this out!  
* Minor bug fixes.  

## 1.5.12 (July 16 2013)

### Added in 1.5.12

* New Sort option for Terms/Expressions: "Word Count in Active Texts" (Descending). Only when you choose this sort option, the word count will be calculated, displayed, and used for sorting the table. This may slow down the term table display. If you prefer faster term table display, choose the other sort options.

### Changed in 1.5.12

* Much better Tablet/iPad® user experience in screens with several frames. There is a new setting "Frame Set Display Mode" where you can select how frame sets are displayed on different devices (default: "Auto"). If you prefer the old mode also on mobile devices, set this to "Force Non-Mobile".  
* Tagging and JQuery UI updated.  
* Better error message when Glosbe API call fails.  
* Unsaved changes alerts extended to tag changes.

### Fixed in 1.5.12

* Tags cache updating corrected: when LWT table set has been changed or after restore/emptying tables.  

## 1.5.11 (July 12 2013)

### Added in 1.5.11

* If an improved annotated text exists, highlight the selected term translation in red in the text window popup and the text display frame (when using the keyboard).  
* New language settings wizard.

### Changed in 1.5.11

* Better check on duplicate language names.  
* Text window popup title is now a link (text color: yellow) to make editing an existent term a little easier.  
* Documentation updated.

### Fixed in 1.5.11

* Some minor bug fixes.  

## 1.5.10 (July 07 2013)

### Added in 1.5.10

* New alerts in some forms when there are unsaved changes during unload event.

### Changed in 1.5.10

* Improved database error checking and reporting, better error messages when fatal errors occur, both with traceback information.  
* Some improvements in Glosbe-LWT integration.
* Documentation updated.

### Fixed in 1.5.10

* Default values corrected in new language form.
* SQL query optimizations in 1.5.8/1.5.9 caused problems, old versions restored.  

## 1.5.9 (July 03 2013)

### Added in 1.5.9

* Long Text Import: Importing a long text via file upload or from a text box, with splitting options.  
* Possibility to save the source URI with an active or archived text.  

### Changed in 1.5.9

* Documentation and some screenshots updated.

### Fixed in 1.5.9

* Missing code in tag management stylesheet restored.  

## 1.5.8 (June 27 2013)

### Changed in 1.5.8

* Optimization of SQL queries for text and print display (reducing query time by up to thirty percent).  
* Checking database status and database upgrade program code rewritten.  
* Some minor improvements and bugfixes. Documentation updated.  

## 1.5.7 (June 25 2013)

### Added in 1.5.7

* A new 3rd "Flexible" term export is introduced, controlled by an "Export Template" in the language settings. [Read more ...](info.html#extmpl)

### Changed in 1.5.7

* Texts are now automatically reparsed, however only after changing language settings that influence the sentence and textitems cache.
* Documentation updated.  

### Fixed in 1.5.7

* Some minor corrections in some SQL CREATE/INSERT statements. MySQL session string is now set to an empty string to avoid too strict SQL checking. Thanks to a poster in the help forum for pointing this out!

## 1.5.6 (June 22 2013)

### Fixed in 1.5.6

* Two SQL statements (Anki/TSV export of marked terms) corrected. Thanks to a poster in the help forum for pointing this out!  

## 1.5.5 (June 21 2013)

### Added in 1.5.5

* Integration of the Glosbe API into LWT via a "special" dictionary link. Read more [in the Glosbe documentation](info.html#glosbe).
* LWT-WordPress integration, read more [in the WordPress integration guide](info.html#wp) (only for users who want to use WordPress authentication together with the LWT multiple user/table set feature introduced in version 1.5.3).

### Changed in 1.5.5

* Some minor improvements: Window width of dictionary popups changed from 600 to 800 Pixel. No "\_lwtgeneral" operations if table prefix is fixed.
* Documentation updated.  

## 1.5.4 (June 19 2013)

### Added in 1.5.4

* If more than one table set exists, and $tbpref was NOT set to a fixed value in "connect.inc.php", you can now select a table set via "start.php", or by clicking on the LWT icon or title in the LWT menu screen "index.php".  
* By hovering over the LWT icon in the top left corner of every screen, you can now display the current table set in a yellow tooltip.  
* A new test/review type "Table" has been added in the testing area. Words, translations, romanizations, sentences and status are presented in a table. You may hide and/or sort columns. After testing yourself, you can reveal the hidden information by clicking into the table cell, and change your status.

### Changed in 1.5.4

* Player appearance improved. Some settings, that were not saved until now, are now automatically saved. Documentation and some screenshots updated.  

## 1.5.3 (June 14 2013)

### Added in 1.5.3

* New Feature: It is now possible to create and to use not only ONE set of LWT tables within one database. You are now able to create and use unlimited LWT table sets within one database (as space and MySQL limitations permit). This feature is especially useful for users who want to set up a multi user environment with a set of tables for each user. You can also create one table set for every language you study - this allows you to create different term/text tags for each language. If you don't need this feature, you just use LWT like in earlier versions with the "default table set". Read more [in the multi-user environment guide](info.html#mue) and [database documentation](info.html#database).  

### Changed in 1.5.3

* Complete code review. Some minor improvements and bugfixes. Documentation & Anki 1+2 template decks updated.  

## 1.5.2 (June 09 2013)

### Added in 1.5.2

* Easy navigation to the previous and the next text (according to current text filters and sort order) is now possible.

### Changed in 1.5.2

* "Backup" does not store anymore the tables 'sentences' and 'textitems'. These tables are now automatically recreated (by reparsing the texts) within "Restore". This makes backup faster and the backup file much smaller, while "Restore" will take a bit longer.
* Documentation and all screenshots updated.  
* Installation procedures (EasyPHP) updated.  

## 1.5.1 (June 07 2013)

### Added in 1.5.1

* Display screen of improved annotated texts (= [hyperliteral translations](http://learnanylanguage.wikia.com/wiki/Hyperliteral_translations) as [interlinear text](http://en.wikipedia.org/wiki/Interlinear_gloss)) improved: Clicking the "T" or "A" lightbulb icons hides/shows the complete text or all annotations. You may also click on a single term or a single annotation to show or to hide it. This enables you to test yourself or to concentrate on one text only. Romanizations, if available, appear now while hovering over a term.

### Changed in 1.5.1

* Documentation and screenshots updated.  

## 1.5.0 (April 22 2013)

### Added in 1.5.0

* New Feature: Create and edit an improved annotated text version (as [interlinear text](http://en.wikipedia.org/wiki/Interlinear_gloss)) for online or offline learning. Read more [in the interlinear text documentation](info.html#il).  
* In-Place-editing of translations and romanizations now possible within the terms table.
* You may now empty (= delete the contents of) the LWT database in the "Backup/Restore/Empty Database" screen.

### Changed in 1.5.0

* Some minor improvements. Documentation, screenshots and demo database updated.  

## 1.4.10 (February 22 2013)

### Added in 1.4.10

* New option in "Print" screen: annotation can now also be placed above the term (via [Ruby characters](http://en.wikipedia.org/wiki/Ruby_character)). This is especially helpful for Chinese and Japanese, when annotating the text with the romanization (Pinyin, Hiragana, Katakana). Your browser must support ruby markup. Firefox needs the [HTML Ruby Add-On](https://addons.mozilla.org/de/firefox/addon/html-ruby/) to display ruby markup properly.

### Changed in 1.4.10

* Help document updated.  

## 1.4.9 (August 29 2012)

### Fixed in 1.4.9

* Anki and TSV export bug fixed (in some cases the term tags were not exported).  

## 1.4.8 (May 11 2012, some external links updated June 19 2012)

### Added in 1.4.8

* Timing of transactions now possible as an additional debugging option in settings.inc.php ($dspltime = 1; normally switched off = 0).

### Changed in 1.4.8

* "Important Links" section within this document updated and expanded.  

### Fixed in 1.4.8

* Correction of a small (typo) bug in js/jq\_pgm.js that caused an SQL error when pressing the "E" key (Thank you, anthonylauder!). See also [this thread](http://sourceforge.net/projects/lwt/forums/forum/1813497/topic/5265425).  
* Correction in do\_text\_header.php: fix negative audio positions to zero. See also [this thread](http://sourceforge.net/projects/lwt/forums/forum/1813497/topic/5220016).

## 1.4.7 (April 6 2012)

### Fixed in 1.4.7

* Correction of some minor mistakes and glitches in the code.  

## 1.4.6 (March 14 2012)

### Fixed in 1.4.6

* Documentation and example database corrected. Google Translate links now open in a popup window, not in a frame of the frameset, as Google now disallows this. Please make sure to deactivate popup window blockers.  

## 1.4.5 (October 01 2011, documentation updated Oct 13 2011, external links updated Dec 8 2011)

### Added in 1.4.5

* Double-Click on a term and "A" key (while going through non-blue terms via keyboard) sets audio position approximately to text position. Hovering over sentence marker (green or red dot) in the terms table shows tooltip with sentence. Some updates and corrections in the documentation.  

## 1.4.4 (September 23 2011)

### Changed in 1.4.4

* Changed the handling of backslash removal in posted data on servers with magic\_quotes\_gpc = Off. Documentation updated.  

## 1.4.3 (September 21 2011)

### Changed in 1.4.3

* Changed some unintentionally written short PHP open tags "<?" to "<?php". Thanks to a poster in the help forum for pointing this out.  

## 1.4.2 (September 19 2011)

### Removed in 1.4.2

* Removed PGUP/PGDN key bindings (mark first/last non-blue term, use HOME/END keys). Some minor changes in the documentation.  

## 1.4.1 (September 15 2011)

### Changed in 1.4.1

* Anki Export changed. The full sentence doesn't have brackets anymore.  
* Data in Anki template updated.  

## 1.4.0 (September 09 2011)

### Added in 1.4.0

* Mobile Version (experimental, via mobile.php): Selection of Language, Text, and Sentence, Playing the audio (if exists), Reading the text either sentence-by-sentence or term-by-term (saved words shown with translation, romanization, and status (via color). This mobile interface does not yet allow data manipulations.  
* Texts, archived texts and terms can now also be filtered by "untagged".  
* Added a "Repeat Audio / Single Play" toggle button for media player.  

## 1.3.1 (September 05 2011)

### Added in 1.3.1

* New multi actions for marked/all terms: Set Terms to Lowercase, Capitalize Terms, Delete Sentences of Terms.

### Changed in 1.3.1

* Screenshots updated.

## 1.3.0 (September 03 2011)

### Added in 1.3.0

* Tagging of texts and archived texts introduced. With this feature, it will be easier to categorize and organize your texts. After having tagged your texts, you are able to filter texts according to one or two tags.  
* Rudimentary right-to-left (rtl) script support: new db field in languages to set a language to right-to-left script, all relevant parts with respect to rtl support changed. A simple Hebrew example added to demonstrate rtl support.

### Changed in 1.3.0

* Documentation, screenshots, Anki example file updated.  

## 1.2.2 (August 26 2011)

### Added in 1.2.2

* Added column "Percentage Unknown Words" in Texts table.
* During reading a text, you can now create terms that do not occur in the text. Click on the yellow icon in the top left frame, and type in the term, translation, etc. You may now also edit the text directly.
* iPod touch®/iPhone®/iPad® icons and splash screen added, and HTML header for touch devices modified. (Thanks, Derek!)

### Changed in 1.2.2

* Documentation and screenshots updated.  

### Fixed in 1.2.2

* Removed a bug that prevented Strg-C/Cmd-C (and other key strokes) in text/test frames.  
* Removed a bug in statistics calculation.  
* Removed a bug in text selection dropdown control.
* Removed language column in Texts/Terms tables if language filter is set.

## 1.2.1 (August 25 2011)

### Added in 1.2.1

* New Rewind and Fast Forward button for audio player. User can set the time to rewind or fast forward from 1 to 10 seconds.  
* Added a section in the help document about the setup of LWT for iPads®, etc.

### Changed in 1.2.1

* Documentation and screenshots updated.  

## 1.2.0 (August 24 2011)

* Tagging of terms introduced. Tags are little pieces of information (20 characters max., no spaces, no commas, case sensitive!) attached to terms to help you catagorize and organize your terms. You can import (CSV, TSV) and export (Anki, TSV) terms together with tag information. Two new tables save tag information, and they are created automatically during first usage after update.  
* Display of example sentences is now delayed.  
* Backup creates the database SQL file now as a gzipped file. Restore can process old unzipped or new gzipped files. Gzipped files are much smaller in size (10-20 % of original size).  
* Documentation and screenshots updated.  

## 1.1.1 (August 17 2011)

### Added in 1.1.1

* New option in "Print" screen to select whether annotation should be placed in front (new option) or after (default) the term.  
* New option in settings screen "Visit only saved terms with status(es) ..." to specify which terms are visited when using RIGHT/SPACE/LEFT/etc. keys in the text frame (default: ALL non-blue terms = statuses 1..5, Ign, WKn).

### Changed in 1.1.1

* The Status filter lists have been expanded.
* Documentation and screenshots updated.

### Fixed in 1.1.1

* Fixed problems with non-ASCII characters in media file names (hopefully).

## 1.1.0 (August 16 2011)

### Added in 1.1.0

* New status display during tests: "Elapsed Time / Total = Not yet tested + Wrong + Correct", plus a small bar graph.  
* Tests can now be done also via key strokes (but you must first click in the test frame): SPACE: show solution, UP/DOWN: Status +1/-1, ESC: don't change status, NUMBER KEYS 1-5: set status to 1-5, I: set "Ignored", W: set "Well known", E: edit term.  
* In the "Read Text" frame, the next unknown (blue) word in the text can now be shown for term creation just by pressing the RETURN key. The term will be marked by a red border. You type in the translation, etc., and press RETURN to save the word. Now you can press RETURN again to show the next unknown (blue) word, enter a translation, save it, and so on... There is sometimes the problem that some external dictionaries catch the focus although the cursor should be in the translation field in the edit frame. Especially Chrome behaves badly, and I cannot change this. Please try different dictionaries and/or browsers.  
* You can also review/manage saved (non-blue) terms with key strokes in the "Read Text" frame (RIGHT or SPACE: next term, LEFT: previous term, PAGE-UP or HOME: first term, PAGE-DOWN or END: last term, NUMBER KEYS 1-5: set current term status to 1-5, I: set current term status to "Ignored", W: set current term status to "Well known", E: edit current term, ESC: reset). The current term has a black border and the frame scrolls automatically. The term is displayed in the top right frame.  
* New refresh button refreshes media files combo box (without page reload) on the text input/edit screen.  
* Information added how to install LWT at a (free) webhoster.

### Changed in 1.1.0

* DB design altered: Table "words" changed: 3 new columns to make random word selection (in tests) and score calculation/query faster.  
* Translation, romanization and sentence are now optional. An empty translation or an asterisk in the translation field are equivalent. Terms without translation or in status "Well Known" or "Ignore" will never be tested. Import of terms without translation is now possible.  
* Textarea input boxes have now all a maximum text/bytes length check.  
* Testing algorithm revised, simplified and optimized.  
* Terms due today and tomorrow are marked in score column (red/yellow) in terms table.  
* Documentation and screenshots updated, new floating menu.

### Fixed in 1.1.0

* EasyPHP installation corrected.  

## 1.0.4 (August 11 2011)

### Added in 1.0.4

* Checking maximum text length in text input/edit/check screens.  

### Changed in 1.0.4

* Code cleanup and optimization, better code documentation.
* Media directory is not anymore included, user has to create it if needed. The demo media are now all online.  
* Demo database installation is now done within LWT (optional). If the database is empty, a hint on the main screen is displayed either to install the demo db or to start with the definition of a language. Backup/Restore now with more hints and an option to install the demo database.  
* Documentation and screenshots updated.  

## 1.0.3 (August 09 2011)

### Added in 1.0.3

* New button to open a new text immediately after saving.  
* Dictionaries can now be opened not only within the frame set (default) but also in a popup window (please deactivate popup window blocking in your browser!). To open a dictionary in a separate popup window, put an asterisk \* in front of the Uniform Resource Identifier (Example: \*<http://mywebdict.com?q=###).> Please use this method if a web dictionary does not open properly within the frame set.  
* The application now always checks if the current text or language still exist (could have sometimes resulted in empty query results after deleting).  
* New settings "Texts per Page", "Show Word Counts of Texts immediately", "Archived Texts per Page" and "Terms per Page". You can now define how many texts or terms are on one page. As the calculation of text word counts can slow down the loading of a long text table, you can switch off this calculation (and do the calculation later).  
    "New/Edit Term" now allow dictionary lookup (always in popup window). If you want to enter a term manually, you must first select the language on the "My Terms" page.

### Changed in 1.0.3

* Documentation and screenshots updated.  

## 1.0.2 (August 05 2011)

### Added in 1.0.2

* Language definition: If the searchword in the Uniform Resource Identifiers (URIs) needs to be converted into a different encoding (standard is UTF-8), you can now use *###encoding###* as a placeholder. Example: *<http://mywebdict.com?q=###ISO-8859-15###>*. A list of encodings can be found [in the PHP mbstring documentation](http://php.net/manual/en/mbstring.supported-encodings.php) (omit the asterisk if one is at the end).

### Changed in 1.0.2

* Documentation and screenshots updated. Thai example added in demo database.  

## 1.0.1 (August 04 2011)

### Added in 1.0.1

* Empty database will be created automatically if database does not exist. Tables will be automatically recreated if some or all tables are missing. System handles (future) database changes automatically.  

### Changed in 1.0.1

* Testing: Last term tested will not show up immediately. Automatically selected sentences in tests now must not contain unsaved (blue) words. This does not apply if a sentence saved with a term contains unknown words - such sentences may come up within a test.
* Documentation and screenshots updated.  

## 1.0.0 (August 01 2011)

* First stable release. For some time, there won't be any new releases. I hope you'll understand that. Please post all problems, questions, and (hopefully not too many) bugs [on the SourceForge forum](http://sourceforge.net/projects/lwt/forums/forum/1813497), and ideas and suggestions for new features [on UserVoice](http://lwt.uservoice.com). Thanks!  

## 0.9.8 (July 31 2011)

### Added in 0.9.8

* New Text Display Mode (see new checkbox on the "Read text" screen).  
  * \[Show All\] = ON (the only mode in previous releases): ALL terms are shown, and all multi-word terms are shown as superscripts before the first word. The superscript indicates the number of words in the multi-word term.  
  * \[Show All\] = OFF (new): Multi-word terms now hide single words and shorter or overlapping multi-word terms. This makes it easier to concentrate on multi-word terms while displaying them without superscripts, but creation and deletion of multi-word terms can be a bit slow in long texts.

### Changed in 0.9.8

* Documentation and screenshots updated.  

## 0.9.7 (July 28 2011)

### Changed in 0.9.7

* Internal improvements, status names, abbreviations, and tooltips (Thanks, Arthaey!). Printout of texts with optional inline annotation (translation and/or romanization) of terms that are of specified status(es). Documentation and screenshots corrected.  

## 0.9.6 (July 26 2011)

### Changed in 0.9.6

* More visual improvements (layout, tables, etc.), many improvements and bugfixes. Dictionary Uniform Resource Identifiers (URIs) use now ### as a placeholder for the searchword. If ### is missing, the searchword will be appended (like in older versions). More multi-actions for terms. Translation and sentence fields do NOT accept newlines anymore, now the form is submitted. Status entry changed from dropdown to radio buttons. Backup/Restore improved. CSS and XHTML corrected and validated. Korean example added. An Anki example deck is now provided: "LWT.anki" in directory "anki". Documentation and screenshots improved.  

## 0.9.5 (July 23 2011)

### Changed in 0.9.5

* Visual improvements (icons), a few bug fixes. Database Restore changed for bigger files. Two new tests to test L2 -> L1 or L1 -> L2 without sentence (just the term). The term filter operates not only on the term field but also on the romanization and translation field.  

## 0.9.4 (July 22 2011)

### Added in 0.9.4

* New buttons to switch easier between active texts and archived texts.  
* New settings "Testing: Number of sentences displayed from text, if available" and "Terms: Number of sentences generated from text, if available". Default is "Just ONE". The options "TWO (+ previous)" and "THREE (+ previous, + next)" now allow you to do MCD (Massive-Context Cloze Deletion) testing, as proposed by Khatzumoto @ AJATT.  

### Changed in 0.9.4

* "Import of terms" now only needs a translation if the status is set to 1, 2, 3, 4 or 5. Furthermore it not only imports terms as TAB (ASCII 9) separated values (TSV) or "#" separated values, but also as comma separated values (CSV, strings in quotes ("...", if needed), a quote within a string as double quotes (""); this is the format that LingQ uses for exporting terms).
* Some minor bug fixes. Documentation updated.  

## 0.9.3 (July 21 2011)

### Fixed in 0.9.3

* After a lot of multi-platform/multi-server testing: several bug fixes and improvements. One severe bug that crashed importing and checking of texts on XAMPP/Win removed (Thanks, Kendall!). Within text, archive, and term queries, selected sort order will be retained (saved in database). All other query settings are now retained per session. Documentation updated.  

## 0.9.2 (July 19 2011)

### Fixed in 0.9.2

* New "Settings" screen. Documentation updated and improved.  

## 0.9.1 (July 18 2011)

### Changed in 0.9.1

* Testing totally revamped (and extended). Many, many improvements and bugfixes. Documentation and screenshots updated. EasyPHP for Windows installation explained.

### Fixed in 0.9.1

* Many, many improvements and bugfixes.

## 0.9.0 (July 14 2011)

### Added in 0.9.0

* TSV export of terms added.
* New buttons to jump from test to text and vice versa.
* Questions and answers added

### Changed in 0.9.0

* Frame screen "Edit term" is now more compact to save space.
* Anki export improved.
* The multi actions drop-down-list is now disabled when no checkboxes are checked.
* Testing completely revised and improved.
* It is now possible to create expressions with up to 9 words (previous releases: up to 6 words).
  * Important: Re-parsing is necessary to use this feature in existing texts.
  * New texts have this feature will automatically.
* Documentation and screenshots updated.  

## 0.8.2 (July 13 2011)

### Changed in 0.8.2

* Minor improvements.
* Term score formula revised.
* Clicking on a blue (unknown) word opens "New Term" and first dictionary automatically.
* Documentation (topic: Term scores) added.

### Fixed in 0.8.2

* Minor bug fixes.

## 0.8.1 (July 12 2011)

### Changed in 0.8.1

* Mac installation and upgrade (see documentation) completely changed.

## 0.8.0 (July 11 2011)

### Added in 0.8.0

* Possibility to change term (but only uppercase/lowercase changes allowed).
* Import terms with overwrite now possible.
* Number of saved words and "To Do" words are now displayed in "My Texts".
* Backup and Restore of the LWT database.

### Changed in 0.8.0

* "Learn/Edit Word/Expression" opens first dictionary automatically.
* Documentation updated and improved.  

## 0.7.0 (July 09 2011)

### Changed in 0.7.0

* Minor improvements, more documentation.
* Japanese example added.  

## 0.6.0 (July 08 2011)

### Changed in 0.6.0

* Improvements, more documentation.
* Settings now stored in DB (not in cookies anymore).  

### Fixed in 0.6.0

* Bugfixes.

## 0.5.0 (June 30 2011)

### Added in 0.5.0

* Text archiving.

### Changed in 0.5.0

* Improvements.

### Fixed in 0.5.0

* Bugfixes.

## 0.4.0 (June 29 2011)

### Added in 0.4.0

* Basic documentation completed
* Two new tests.

### Changed in 0.4.0

* Improvements.

### Fixed in 0.4.0

* Bugfixes.

## 0.3.0 (June 27 2011)

### Added in 0.3.0

* More documentation.

### Changed in 0.3.0

* Improvements.

### Fixed in 0.3.0

* Bugfixes.

## 0.2.0 (June 25 2011)

### Added in 0.2.0

* More documentation.

### Changed in 0.2.0

* Improvements.

### Fixed in 0.2.0

* Bugfixes.

## 0.1.0 (June 24 2011)

* Initial release.
