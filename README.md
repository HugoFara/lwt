# Learning with Texts

<p align="center">
  <img src="https://github.com/HugoFara/lwt/raw/master/img/lwt_icon_big.jpg" alt="LWT logo - an open book" width="200"/>
</p>

<p align="center">
  <strong>Learn languages by reading texts you love</strong>
</p>

<p align="center">
  <a href="https://packagist.org/packages/hugofara/lwt"><img src="https://poser.pugx.org/hugofara/lwt/v" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/hugofara/lwt"><img src="https://poser.pugx.org/hugofara/lwt/require/php" alt="PHP Version"></a>
  <a href="https://github.com/HugoFara/lwt/actions/workflows/php.yml"><img src="https://github.com/hugofara/lwt/actions/workflows/php.yml/badge.svg" alt="CI Status"></a>
  <a href="https://github.com/HugoFara/lwt/actions/workflows/docker-image.yml"><img src="https://github.com/HugoFara/lwt/actions/workflows/docker-image.yml/badge.svg" alt="Docker"></a>
  <a href="http://unlicense.org/"><img src="https://poser.pugx.org/hugofara/lwt/license" alt="License"></a>
</p>

---

**Learning with Texts** (LWT) is a self-hosted web application for language learning through reading. Import any text, click unknown words to see translations, and build vocabulary with spaced repetition.

> [!IMPORTANT]
> This is a **community-maintained fork** with significant improvements over the [official version](https://sourceforge.net/projects/learning-with-texts): modern PHP support (8.1-8.4), smaller database footprint, mobile support, and active development.

## Table of Contents

- [Quick Start](#quick-start)
- [How It Works](#how-it-works)
- [Features](#features)
- [Installation](#installation)
- [Requirements](#requirements)
- [Documentation](#documentation)
- [Contributing](#contributing)
- [Alternatives](#alternatives)
- [License](#license)

## Quick Start

The fastest way to get started is with Docker:

```bash
git clone https://github.com/HugoFara/lwt.git
cd lwt
docker compose up
```

Then open <http://localhost:8010/lwt/> in your browser.

## How It Works

**1. Import a text** — Paste any content you want to read, or import from RSS feeds.

![Adding French text](https://github.com/HugoFara/lwt/raw/master/img/05.jpg)

**2. Read and learn** — Unknown words are highlighted. Click any word to see its translation and save it to your vocabulary.

![Learning French text](https://github.com/HugoFara/lwt/raw/master/img/06.jpg)

**3. Review with context** — Practice vocabulary with spaced repetition, always seeing words in their original context.

![Reviewing French word](https://github.com/HugoFara/lwt/raw/master/img/07.jpg)

Unlike flashcard apps like [Anki](https://apps.ankiweb.net/), LWT keeps words connected to the texts where you found them. We also include an Anki exporter if you want both.

## Features

### Core Features

- **40+ languages supported** — Roman, right-to-left, and East-Asian writing systems
- **Click-to-translate** — Instant dictionary lookups while reading
- **Audio integration** — Sync audio tracks with your texts
- **Spaced repetition** — Review words at optimal intervals
- **Progress tracking** — Statistics to monitor your learning

### Community Additions

This fork adds features not in the official LWT:

| Feature | Description |
| --- | --- |
| Mobile support | Responsive design for phones and tablets |
| RSS feeds | Automatically import texts from feeds |
| Themes | Customizable appearance |
| Multi-word selection | Click and drag to select phrases |
| Bulk translation | Translate multiple new words at once |
| Text-to-speech | Hear pronunciation of words |
| Keyboard shortcuts | Navigate efficiently while reading |
| Video embedding | Include videos from YouTube and other platforms |
| MeCab integration | Japanese word-by-word translation |

### Technical Improvements

- **Smaller database** — Optimized schema reduces storage significantly
- **Long expressions** — Save phrases up to 250 characters (was limited to 9)
- **Better search** — Improved querying for words and texts
- **Position memory** — Resume reading where you left off
- **Modern PHP** — Supports PHP 8.1, 8.2, 8.3, and 8.4

## Installation

### Docker (Recommended)

Works on any OS with Docker installed.

#### Option A: Quick installer

```bash
# Use the lightweight installer
git clone https://github.com/HugoFara/lwt-docker-installer.git
cd lwt-docker-installer
docker compose up
```

#### Option B: Build from source

```bash
git clone https://github.com/HugoFara/lwt.git
cd lwt
docker compose up
```

Access at <http://localhost:8010/lwt/>

### Linux

```bash
# Download and extract the latest release
wget https://github.com/HugoFara/lwt/archive/refs/heads/master.zip
unzip master.zip && cd lwt-master

# Run the installer
chmod +x ./INSTALL.sh
./INSTALL.sh
```

### Manual Installation (Windows/macOS/Linux)

1. Install prerequisites: PHP 8.1+, MySQL/MariaDB, a web server (Apache/Nginx)
2. Clone or download the repository
3. Configure the database:

   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. Install dependencies:

   ```bash
   composer install
   npm install && npm run build:all
   ```

See the [Installation Guide](https://hugofara.github.io/lwt/docs/guide/installation) for detailed instructions.

## Requirements

| Component | Version |
| --- | --- |
| PHP | 8.1, 8.2, 8.3, or 8.4 |
| MySQL/MariaDB | 5.7+ / 10.3+ |
| PHP Extensions | mysqli, mbstring, dom |

For development, you'll also need [Composer](https://getcomposer.org/) and [Node.js](https://nodejs.org/) 18+.

## Documentation

- **[User Guide](https://hugofara.github.io/lwt/docs/guide/)** — Getting started and usage
- **[API Reference](https://hugofara.github.io/lwt/docs/reference/)** — REST API documentation
- **[Developer Docs](https://hugofara.github.io/lwt/docs/developer/)** — Architecture and contribution guide

## Contributing

Contributions are welcome! Here's how to set up a development environment:

```bash
git clone https://github.com/HugoFara/lwt.git
cd lwt
composer install --dev
npm install
```

### Development Commands

```bash
# Run tests
composer test              # PHP tests with coverage
npm test                   # Frontend tests

# Code quality
./vendor/bin/psalm         # Static analysis
npm run lint               # ESLint
npm run typecheck          # TypeScript checking

# Build assets
npm run dev                # Development server with HMR
npm run build:all          # Production build
```

### Branch Strategy

| Branch | Purpose |
| --- | --- |
| `master` | Stable releases |
| `dev` | Development and testing |
| `official` | Tracks official LWT releases |

## Alternatives

If LWT doesn't fit your needs, consider these projects:

- **[LUTE v3](https://github.com/jzohrab/lute-v3)** — Modern rewrite using Python/Flask, actively developed
- **[LinguaCafe](https://github.com/simjanos-dev/LinguaCafe)** — Beautiful Vue.js/PHP implementation
- **[FLTR](https://sourceforge.net/projects/foreign-language-text-reader/)** — Java desktop app by LWT's original author

## License

This project is released into the **public domain** under the [Unlicense](UNLICENSE.md). You're free to use, modify, and distribute it however you like.

---

<p align="center">
  <strong>Happy reading, happy learning!</strong>
</p>
