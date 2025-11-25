# Optional Post-Installation Steps

LWT is a all-in-one product, but you can extend its capabilities in various ways.
Here are the additional features unavailable by default.

## Parse Japanese with MeCab

[MeCab](https://taku910.github.io/mecab/) is a Japanese parser. Installing it has
two main advantages:

* Much better parsing of Japanese texts than RegExp.
* Automatic romanization of words.
* Enables text-to-speech for any character group.

### Installation

### Standard Installtion

1. Follow the instructions to download MeCab at <https://taku910.github.io/mecab/#install>.
2. Add MeCab to your system PATH.
3. In LWT, go to "Edit Languages" → "Japanese" and change from the value for "RegExp Word Characters" to ``mecab``.

### Using Docker

Original instructions provided [here](https://nickramkissoon.medium.com/easily-set-up-and-use-mecab-with-docker-and-nodejs-5f01ae761a61).

1. Run your LWT container in interactive mode and install MeCab.

    ```bash
    docker exec -it lwt bash
    apt-get update && apt-get install -y mecab libmecab-dev mecab-ipadic-utf8
    ```

2. In LWT, go to "Edit Languages" → "Japanese" and change from the value for "RegExp Word Characters" to ``mecab``.

## Automatic Translation

### LibreTranslate

[LibreTranslate](https://libretranslate.com/) is a great open-source tool that allows you to translate text and provides an API.
With it you can achieve the following:

* Translation of sentences without using Google Translate.
* Automatic translation of words (so you don't need to fill by hand!).

![LibreTranslate Demo](../img/libretranslate_demo.png)

To use it, please read the following steps:

1. [Install it](https://github.com/LibreTranslate/LibreTranslate#install-and-run) on a local or remote server or using Docker.
2. In LWT, go to "Edit Langagues", either create a new or edit an existing language
3. In the field "Sentence Translator URI", replace it by the URL of you libre translate instance.
   * Do not forget to add the parameter ``lwt_translator=libretranslate`` for the automatic translation!
   * ``source=`` should be followed by two letters indicating the language translating from.
   * ``target=`` should be followed by two letters indicating the language to translate to.

## Enhanced Text-to-Speech

The Text-to-Speech may sound robotic with some languages or operating systems. A
workaround is to download a Text-To-Speech (TTS) plugin such as [Read Aloud](https://readaloud.app/).
