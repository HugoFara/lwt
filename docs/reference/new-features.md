# New feature not available in the official LWT

## User Interface

* Mobile support, responsive design
* Support for different themes
* Display translations of terms with status in the reading frame
* Much better interface for the main menu
* Save text/audio position in the reading frame
* Multiwords selection (click and hold on a word → move to another word → release mouse button)
* Key bindings work when you hover over a word
* Bulk translate new words in the reading frame
* New key bindings in the reading frame:
  * T (translate sentence),
  * P (pronounce term),
  * G (edit term with Google Translate)
* Ability to change audio playback speed (doesn't work when using the flash plugin)
* Improved HTML organization. It has several effect:
  * You can read texts in "reader mode" in most browsers
  * Simple mobile compatibility

## More than LWT

* Automatically translate terms with LibreTranslate
* Automatic text to speech
* Automatic spelling for Japanese with MeCab
* Automatically import texts from RSS feeds (for more info see: [Newsfeed Import](info.html#Newsfeed%20Import))
* Longer (> 9 characters) expressions can now be saved (up to 250 characters)
* Improved Search/Query for Words/Texts
* Selecting terms according to a text tag
* Term import with more options (i.e.: combine translations, multiple tag import,...)
* Two database backup modes (new or old structure)

## Back-end  

* Native support for Docker
* Database improvements: the database takes much less space on the server
* Better caching due to an improved file management
* Enhanced security and robustness due do sanitazed SQL inputs
