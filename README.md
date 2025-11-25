# Learning with Texts

[![Latest Stable Version](https://poser.pugx.org/hugofara/lwt/v)](https://packagist.org/packages/hugofara/lwt)
[![License](https://poser.pugx.org/hugofara/lwt/license)](https://packagist.org/packages/hugofara/lwt)
[![PHP Version Require](https://poser.pugx.org/hugofara/lwt/require/php)](https://packagist.org/packages/hugofara/lwt)
![Composer Continuous Integration](https://github.com/hugofara/lwt/actions/workflows/php.yml/badge.svg)
[![Docker Image](https://github.com/HugoFara/lwt/actions/workflows/docker-image.yml/badge.svg)](https://github.com/HugoFara/lwt/actions/workflows/docker-image.yml)

**Learning with Texts** (LWT) is a tool for language learning by reading. It is a self-hosted web application.

<div style="text-align: center;">
<img src="https://github.com/HugoFara/lwt/raw/master/img/lwt_icon_big.jpg" alt="LWT icon of a book"/>
</div>

You feel that you won't learn much by translating dumb sentences or using grammar books? Learning With Texts offers you the possibility to learn by reading texts in your target language. Concept: when you don't know a word in a text, just click it. We show you the translation, and you will have regular tests to remember it. Ready to go?

> [!IMPORTANT]  
> **THIS IS A THIRD PARTY VERSION**. This version is not the
official one, and brings many improvements and new features.
It is quicker, has smaller database size,
and is open for contributions. The official version is on
[source forge](https://sourceforge.net/projects/learning-with-texts)

> [!NOTE]  
> HugoFara: I don't plan to continue developping LWT on a regular basis. While I may keep maintaining for fun, I recommend [jzohrab/lute-v3](https://github.com/jzohrab/lute-v3) as the main target for development effort in the LWT software family.

## Installation

As LWT is self-hosted, you will need a server, which can be your computer.
You can either use Docker (recommended), or install it on your machine.

### Docker (any OS)

Install [Docker](https://docs.docker.com/get-docker/) (if not already done).

* For an light-weight installation, you may use [HugoFara/lwt-docker-installer](https://github.com/HugoFara/lwt-docker-installer).

* To build from source, download the latest release and run:

  ```bash
  cd lwt
  docker compose up  # Now open http://localhost:8010/lwt/ in a browser
  ```

### Linux

1. Get the [latest GitHub release](https://github.com/HugoFara/lwt/releases). You can also try to download the [latest stable version](https://github.com/HugoFara/lwt/archive/refs/heads/master.zip) if you want the cutting-edge updates (that may include some bugs).
2. Start a shell in the downloaded folder an run: ``./INSTALL.sh``. You may need to run ``chmod +x ./INSTALL.sh`` first.

### Other Systems

1. **Please follow**: [docs/install.md](docs/install.md) for setup instructions.
2. Copy `.env.example` to `.env` and configure your database credentials. Everything is explained at [docs/info.html](https://hugofara.github.io/lwt/docs/info.html#install).

And you are ready to go!

## Description

LWT is a language learning web application. To learn a language, you
need to practice, and we guide you in reading exercises.

First copy/paste any text you want to read. It can be raw text or an RSS feed.

![Adding French text](https://github.com/HugoFara/lwt/raw/master/img/05.jpg)

Then, we parse the text. Unknown words will be displayed with different colors,
just click them to see it in a dictionary.

![Learning French text](https://github.com/HugoFara/lwt/raw/master/img/06.jpg)

Read as much as you want!

To make sure you memorize new words, you can take review exercises.

![Reviewing French word](https://github.com/HugoFara/lwt/raw/master/img/07.jpg)

The difference with popular remembering software like
[Anki](https://apps.ankiweb.net/) is that we keep track of the
context to help you. By the way, we also ship
an Anki exporter.

## Features

> **Full features list**: [docs/features.md](docs/features.md)

Features included from the official LWT software:

* Support for almost 40 languages.
* Text parsing for roman languages, right-to-left,
and East-Asian ideogram systems
* Translate words on-the-fly
* Add an audio track and read it online
* Practice words you don't remember
* Statistics to record your progress

### Features not in the official LWT

> **Full new features list**: [docs/newfeatures.md](docs/newfeatures.md)

Features that were added by the community:

* Support for mobile
* Automatically import texts from RSS feeds
* Support for different themes
* Display translations of terms with status in the reading frame
* Multiwords selection (click and hold on a word
→ move to another word → release mouse button)
* Bulk translate new words in the reading frame
* Text to speech
* Optional "ignore all" button in read texts
* Key bindings in the reading frame
* Selecting terms according to a text tag
* Two database backup modes (new or old structure)

### Improvements compared to the official LWT

* Database improvements (db size is much smaller now)
* Longer (>9) expressions can now be saved (up to 250 characters)
* Save text/audio position in the reading frame
* Google api (use 'ggl.php' instead of '*<http://translate.google.com>' for Google Translate)
* Improved Search/Query for Words/Texts
* Term import with more options (i.e.: combine translations, multiple tag import)
* Support for MeCab for Japanese word-by-word automatic translation.
* You can include video files from popular video platforms.
* Code documentation.
* Code is well organised, making debugging and contribution easier.

## Contribute

> **Complete explanation**: [docs/contribute.md](docs/contribute.md)

To contribute, you need to clone or fork this repository, and [Composer](https://getcomposer.org/download/).
The composer package is at [hugofara/lwt](https://packagist.org/packages/hugofara/lwt).

Run ``git clone https://github.com/HugoFara/lwt``

Next, got to the lwt folder and use ``composer install --dev``.

In short:

```bash
git clone https://github.com/HugoFara/lwt
cd lwt
composer install --dev
```

## Branches

* The stable branch is *master*. Last commit on this branch is
considered to be bug-free.
* The *dev* branch is for unstable versions.
* The *official* branch is for the official LWT Releases.
Any other branch if considered under development.

## Useful links

* General documentation at [docs/info.html](https://hugofara.github.io/lwt/docs/info.html).
* Please find more help at [docs/index.html](https://hugofara.github.io/lwt/docs/index.html).

## Alternatives

> *See also*: [docs/links.md](docs/links.md)

* [jzohrab/LUTE](https://github.com/jzohrab/lute) is a rewrite of LWT with modern tools such as Symfony.
* [FLTR ◆ Foreign Language Text Reader](https://sourceforge.net/projects/foreign-language-text-reader/),
a Java clone, by [lang-learn-guy](https://sourceforge.net/u/lang-learn-guy/profile/)
(original author of LWT), it is a standalone installation.
* [simjanos-dev/LinguaCafe](https://github.com/simjanos-dev/LinguaCafe): a beautiful
equivalent in Vue.js and PHP.

## Unlicense

Under unlicense, view [UNLICENSE.md](UNLICENSE.md), please look at <http://unlicense.org/>.

**Let's learn new languages!**
