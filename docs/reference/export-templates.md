# About LWT Export Templates for "Flexible Exports"

An export template consists of a string of characters. Some parts of this string are **placeholders** (beginning with **"%", "$" or "\\"**) that are **replaced** by the actual term data, **see the following table**. For each term (word or expression), that has been selected for export, the placeholders of the export template will be replaced by the term data and the string will be written to the export file.

**A template must end with** either **"\\n"** (UNIX, Mac) or **"\\r\\n"** (Windows). **If you omit this, the whole export will be one single line!**

If the export template is **empty, no terms of this language** will be exported.

| Placeholders |  Placeholders replaced by ...                                                                    |
|--------------|--------------------------------------------------------------------------------------------------|
| **%...**     | **Raw Text**                                                                                     |
| %w           | Term (Word/Expression) - as raw text.                                                            |
| %t           | Translation - as raw text.                                                                       |
| %s           | Sentence, curly braces removed - as raw text.                                                    |
| %c           | The sentence, but the "{xxx}" parts are replaced by "[...]" (close review question) - as raw text. |
| %d           | The sentence, but the "{xxx}" parts are replaced by "[xxx]" (close review solution) - as raw text. |
| %r           |  Romanization - as raw text. |
| %a           | Status (1..5, 98, 99) - as raw text. |
| %k           | Term in lowercase (key) - as raw text. |
| %z           | Tag List - as raw text. |
| %l           | Language - as raw text. |
| %n           | Word Number in LWT (key in table "words") - as raw text. |
| %%           | Just one percent sign "%". |
| **$...**     | **HTML Text.** HTML special characters are escaped:&lt; = &amp;lt; / &gt; = &amp;gt; / &amp; = &amp;amp; / &quot; = &amp;quot; |
| $w           | Term (Word/Expression) - as HTML text.|
| $t           | Translation - as HTML text.|
| $s           | Sentence, curly braces removed - as HTML text.|
| $c           | The sentence, but the "{xxx}" parts are replaced by "[...]" (close review question) - as HTML text.|
| $d           | The sentence, but the "{xxx}" parts are replaced by "[xxx]" (close review solution) - as HTML text.|
| $x           | The sentence in Anki2 close review notation: the "{xxx}" parts are replaced by "{{c1::xxx}}" - as HTML text. |
| $y           | The sentence in Anki2 close review notation, with translation: the "{xxx}" parts are replaced by "{{c1::xxx::translation}}" - as HTML text. |
| $r           | Romanization - as HTML text.|
| $k           | Term in lowercase (key) - as HTML text. |
| $z           | Tag List - as HTML text. |
| $l           | Language - as HTML text. |
| $$           | Just one dollar sign "$".|
| **\\...**     | **Special Characters** |
| \t           | TAB character (HEX 9).|
| \n           | NEWLINE character (HEX 10). |
| \r           | CARRIAGE RETURN character (HEX 13).|
| \\\          | Just one backslash "\".|
