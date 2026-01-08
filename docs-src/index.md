---
layout: home

hero:
  name: Learning with Texts
  text: Language learning by reading
  tagline: A self-hosted web application for learning languages through reading texts
  image:
    src: /assets/images/lwt_icon_big.png
    alt: LWT Logo
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: View on GitHub
      link: https://github.com/HugoFara/lwt

features:
  - icon: 📖
    title: Read & Learn
    details: Import texts in any language, click unknown words to look them up and save translations. Build vocabulary naturally through context.
  - icon: 📊
    title: Track Progress
    details: Words are tracked with status levels (1-5). Review your vocabulary with spaced repetition principles.
  - icon: 🏠
    title: Self-Hosted
    details: Run on your own server. Your data stays private. No subscription fees. 100% free and open source.
  - icon: 🌍
    title: Any Language
    details: Works with any language. Special support for Japanese (MeCab), Chinese, and other languages with custom parsing rules.
  - icon: 📱
    title: Mobile Ready
    details: Responsive design works on tablets and phones. Read and study anywhere with mobile-friendly interface.
  - icon: 🔄
    title: Export & Sync
    details: Export vocabulary to Anki for flashcard practice. Import/export terms in TSV/CSV format for flexibility.
---

## What is Learning with Texts?

**Learning with Texts (LWT)** is a tool for language learning inspired by [Stephen Krashen's](http://sdkrashen.com) principles of Second Language Acquisition, [LingQ](http://lingq.com), and ideas from [AJATT](http://www.alljapaneseallthetime.com) (All Japanese All The Time).

This is the **community-maintained fork** that improves upon the original with:
- Modern PHP 8.1+ support
- Smaller database size
- Better mobile experience
- Active development

## Quick Start

1. **Install a local web server** - Use [XAMPP](https://www.apachefriends.org/), [MAMP](http://mamp.info/en/index.html), or a LAMP stack
2. **Download LWT** - Get the [latest release](https://github.com/HugoFara/lwt/releases)
3. **Configure database** - Copy `.env.example` to `.env` and set your credentials
4. **Start learning** - Create a language, import a text, and begin reading!

See the [Installation Guide](/guide/installation) for detailed instructions.

## Community

- **GitHub**: [HugoFara/lwt](https://github.com/HugoFara/lwt)
- **Issues**: [Report bugs or request features](https://github.com/HugoFara/lwt/issues)
- **Discussions**: [Join the conversation](https://github.com/HugoFara/lwt/discussions)
