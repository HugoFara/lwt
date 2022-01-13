# Learning with Texts

> [Learning with Texts](https://sourceforge.net/projects/learning-with-texts) (LWT) is a tool for Language Learning.

This is @chaosarium's fork of [@PirtleShell's fork](https://github.com/pirtleshell/lwt) of [@andreask7's fork](https://github.com/andreask7/lwt). Its altered database structure makes it quicker, and it has many features not found in the original. It also looks more likely to develop communally, whereas the original is fairly stagnant and not open for contributions.

If the reading page displays a database error, try downgrading to PHP version 5.4.45.

[@gustavklopp's LingL](https://github.com/gustavklopp/LingL) is a wonderful alternative written in python.

**THIS IS A THIRD PARTY VERSION**
IT DIFFERS IN MANY RESPECTS FROM THE OFFICIAL LWT-VERSION

## What's in this version (chaosarium/lwt)

In brief, changes include:

- A new theme with a less distracting colour scheme
- Status distribution charts in log scale to improve readability
- Thinner frames for a more minimal look
- Bring back unknown word percentage
- Fix bulk new word lookup when translator is not set to google translate
- Disabled dictionary URI check to allow Mac users to use the built-in dictionary by setting the URI to `dict:///###`
- Disabled text to speech (I can't get it to work at all and it crashes the backend every time)

Note that this version uses MAMP configuration by default. To use other PHP environment, delete `connect.inc.php` and rename one of `connect_easyphp.inc.php` or `connect_wordpress.inc.php` or `connect_xampp.inc.php` to `connect.inc.php`

Unfortunately I haven't figured out a way to port LWT to a newer PHP version so PHP 5 is still required. Anyhow happy language learning.

---

Just an update of what may be added next:

- [ ] Reimplement text to speech using [say.js](https://github.com/Marak/say.js/)
- [ ] Add setting to toggle status chart scale
- [ ] Fix log scale to reflect the number 1 in status charts


## New in this Version (not available in the OFFICIAL LWT)

* Database improvements (db size is much smaller now)
* Automatically import texts from RSS feeds
* Support for different themes
* Longer (>9) expressions can now be saved (up to 250 characters)
* Display translations of terms with status(es) in the reading frame
* Save text/audio position in the reading frame
* Multiwords selection (click and hold on a word -> move to another word -> release mouse button)
* Key bindings work when you hover over a word
* Bulk translate new words in the reading frame
* Google api (use 'ggl.php' instead of '*http://translate.google.com' for Google Translate)
* Text to speech support (only words)
* Optional "ignore all" button in read texts
* New key bindings in the reading frame: T (translate sentence), P (pronounce term), G (edit term with Google Translate)
* Ability to change audio playback speed (doesn't work when using the flash plugin)
* Improved Search/Query for Words/Texts
* Selecting terms according to a text tag
* Term import with more options (i.e.: combine translations, multiple tag import)
* Two database backup modes (new or old structure)
* My custom theme

---
## Original README from LWT

PLEASE READ MORE ...
Either open ... info.htm (within the distribution)
or     open ... http://lwt.sf.net (official LWT)

"Learning with Texts" (LWT) is free and unencumbered software
released into the PUBLIC DOMAIN.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a
compiled binary, for any purpose, commercial or non-commercial,
and by any means.

In jurisdictions that recognize copyright laws, the author or
authors of this software dedicate any and all copyright
interest in the software to the public domain. We make this
dedication for the benefit of the public at large and to the
detriment of our heirs and successors. We intend this
dedication to be an overt act of relinquishment in perpetuity
of all present and future rights to this software under
copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

For more information, please refer to [http://unlicense.org/].
