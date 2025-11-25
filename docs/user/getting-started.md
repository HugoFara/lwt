# Getting Started with Learning with Texts

> **THIS IS A THIRD PARTY VERSION** - IT DIFFERS IN MANY RESPECTS FROM THE OFFICIAL LWT VERSION! See the [new features](../reference/new-features.md) for more information.

## What is LWT?

[_Learning with Texts_ (LWT)](https://sourceforge.net/projects/learning-with-texts/) is a tool for Language Learning by reading texts.
It is inspired by:

* [Stephen Krashen's](http://sdkrashen.com) principles in Second Language Acquisition,
* Steve Kaufmann's [LingQ](http://lingq.com) System and
* Ideas from Khatzumoto, published at ["AJATT - All Japanese All The Time"](http://www.alljapaneseallthetime.com).

You define languages you want to learn and import texts you want to use for learning. While listening to the optional audio, you read the text, save, review and test "terms" (words or multi word expressions, 2 to 9 words).

In new texts all your previously saved words and expressions are displayed according to their current learn statuses, tooltips show translations and romanizations (readings), editing, changing the status, dictionary lookup, etc. is just a click away.

Import of terms in TSV/CSV format, export in TSV format, and export to [Anki](http://ankisrs.net) (prepared for cloze tests), are also possible.

## Requirements

To run LWT, you'll need:

1. **A modern web browser**. Do not use Internet Explorer, any other browser (Chrome, Firefox, Safari or Edge) should be fine.
2. **A local web server**.
    An easy way to install a local web server are preconfigured packages like
    * [EasyPHP](http://www.easyphp.org/) or [XAMPP](https://www.apachefriends.org/download.html) (Windows), or
    * [MAMP](http://mamp.info/en/index.html) (macOS), or
    * a [LAMP (Linux-Apache-MariaDB-PHP) server](http://en.wikipedia.org/wiki/LAMP_%28software_bundle%29) (Linux).
3. **The LWT Application**. The latest version  _lwt\_v\_x\_y.zip_ can be downloaded at <https://github.com/HugoFara/lwt/archive/refs/heads/master.zip>. View the [installation guide](installation.md).

## History

### Original Author: [lang-learn-guy](https://sourceforge.net/u/lang-learn-guy/)

* I started this software application in 2010 as a hobby project for my personal learning (reading & listening to foreign texts, saving & reviewing new words and expressions).
* In June 2011, I decided to publish the software in the hope that it will be useful to other language learners around the world.
* The software is 100 % free, open source, and in the public domain. You may do with it what you like: use it, improve it, change it, publish an improved version, even use it within a commercial product.
* English is not my mother tongue - so please forgive me any mistakes.
* A piece of software will be never completely free of "bugs" - please inform me of any problem you will encounter. Your feedback and ideas are always welcome.
* My programming style is quite chaotic, and my software is mostly undocumented. This will annoy people with much better programming habits than mine, but please bear in mind that LWT is a one-man hobby project and completely free.
* Thank you for your attention. I hope you will enjoy this application as I do every day.

### Community Version: [HugoFara](https://github.com/HugoFara) (GitHub version maintainer)

I started using LWT in 2021, and continued its development almost instantly. I felt that the core idea was very good, but its implementation seemed unadapted, and the code was quite obfuscated. While I do not have any official responsibility to LWT (we don't have any kind of official agreement with lang-learn-guy), I am the the *de facto* maintainer of the community version. I dedicated myself to the following points (see the [GitHub post](https://github.com/HugoFara/lwt/discussions/6)):

* Make LWT Open Source: document and refactor code
* Meet the HTML5 standards: the interface was relying on deprecated systems like frames, making it difficult to use on small screens.
* Simplify users' lives: avoid complex installation or procedures whenever possible.

If you spot any problem, please post any [issue on GitHub](https://github.com/HugoFara/lwt/issues), and we will look at it.

While work is not yet finished, I also aim to expand LWT:

* Better UI: custom themes and better default appearance
* Better UX: as of today (2022), 60 % of the web is done through mobile devices. It means less content at once and more intuitive behaviors.
* Sounds: language learning is not just language reading.
  * Text-to-speech features.
  * Motivational sounds when testing terms to makes things more lively.

But there is much more! The community version of LWT is no longer the feat of one man, it belongs to everyone. As such, it gets well easier to implement new features, discuss and exchange code and ideas. I don't know if LWT contains *your* killer feature, but I can say that it *can be implemented* with this version. Enjoy!
