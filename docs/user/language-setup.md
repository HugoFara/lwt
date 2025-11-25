# Language Setup

* This section shows some language setups ("RegExp Split Sentences", "RegExp Word Characters", "Make each character a word", "Remove spaces") for different languages. They are only recommendations, and you may change them according to your needs (and texts). See also [here](info.html#go1).  

* If you are unsure, try the "Language Settings Wizard" first. Later you can adjust the settings.  

* Please inform yourself about Unicode [here (general information)](http://en.wikipedia.org/wiki/Unicode) and [here (Table of Unicode characters)](http://unicode.coeurlumiere.com/) and about the characters that occur in the language you learn!  

| **Language**                                                                     | **RegExp Split Sentences** | **RegExp Word Characters** | **Make each character a word** | **Remove spaces** |
|----------------------------------------------------------------------------------|----------------------------|----------------------------|---------------------------------|---------------|
| Latin derived alphabet (English, French, German, etc.)                           | .!?:;                      | a-zA-ZÀ-ÖØ-öø-ȳ | No | No |
| Languages with a Cyrillic-derived alphabet (Russian, Bulgarian, Ukrainian, etc.) | .!?:;                      | a-zA-ZÀ-ÖØ-öø-ȳЀ-ӹ | No |  No |
| Greek                                                                            | .!?:;                      | \x{0370}-\x{03FF}\x{1F00}-\x{1FFF} | No | No |
| Hebrew (Right-To-Left = Yes)                                                     | .!?:;                      | \x{0590}-\x{05FF} | No | No |
| Thai                                                                             | .!?:;                      | ก-๛     | No | Yes |
| Chinese                                                                          | .!?:;。！？：；              | 一-龥 | Yes or No | Yes |
| Japanese (Without MeCab)                                                         | .!?:;。！？：；              | 一-龥ぁ-ヾ | Yes or No | Yes |
| Japanese (With MeCab)                                                            | .!?:;。！？：；              | mecab | Yes or No | Yes |
| Korean                                                                           | .!?:;。！？：；              | 가-힣ᄀ-ᇂ | No | No or Yes |

* "\\'" = Apostrophe, and/or "\\-" = Dash, may be added to "RegExp Word Characters", then words like "aujourd'hui" or "non-government-owned" are one word, instead of two or more single words. If you omit "\\'" and/or "\\-" here, you can still create a multi-word expression "aujourd'hui", etc., later.  

* ":" and ";" may be omitted in "RegExp Split Sentences", but longer example sentences may result from this.  

* "Make each character a word" = "Yes" should only be set in Chinese, Japanese, and similar languages. Normally words are split by any non-word character or whitespace. If you choose "Yes", then you do not need to insert spaces to specify word endings. If you choose "No", then you must prepare texts without whitespace by inserting whitespace to specify words. If you are a beginner, "Yes" may be better for you. If you are an advanced learner, and you have a possibility to prepare a text in the above described way, then "No" may be better for you.  

* "Remove spaces" = "Yes" should only be set in Chinese, Japanese, and similar languages to remove whitespace that has been automatically or manually inserted to specify words.
