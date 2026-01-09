# Text Parsers

LWT uses text parsers to split texts into words and sentences. While most languages work with the built-in regex-based parser, CJK languages (Chinese, Japanese, Korean) benefit from specialized NLP parsers that understand word boundaries without spaces.

## Built-in Parsers

### Regex Parser (Default)

The default parser uses regular expressions to split text. It works well for languages that use spaces between words (English, French, German, etc.) and languages with clear character boundaries.

Configuration is done per-language in the language settings:
- **RegExp Split Sentences**: Characters that end sentences (e.g., `.!?:;`)
- **RegExp Word Characters**: Characters that form words (e.g., `a-zA-Z`)
- **Make each character a word**: For logographic languages
- **Remove spaces**: For languages that don't use word-spacing

See [Language Setup](/reference/language-setup) for recommended settings per language.

### Space Parser

A simple parser that splits text on whitespace. Useful for pre-segmented text where words are already separated by spaces.

## External NLP Parsers

For CJK languages, external NLP parsers provide accurate word segmentation. LWT includes Python-based parsers for Chinese and Japanese.

### Available Parsers

| Parser | Language | Description |
|--------|----------|-------------|
| Jieba | Chinese | Popular Chinese text segmentation library |
| MeCab | Japanese | Morphological analyzer for Japanese |

### Docker Installation (Recommended)

If you're using Docker, the parsers are pre-installed and ready to use. Simply use `docker compose up` and select the appropriate parser when configuring your language.

### Manual Installation

For non-Docker installations, you can install the Python parsers manually:

#### Prerequisites

- Python 3.8 or higher
- pip (Python package manager)

#### Linux/macOS

Run the installer script with the parser option:

```bash
./INSTALL.sh
```

When prompted, choose to install Python parsers. This will:
1. Create a Python virtual environment at `/opt/lwt-parsers`
2. Install jieba and mecab-python3
3. Deploy the parser bridge scripts

Alternatively, install manually:

```bash
# Create virtual environment
python3 -m venv /opt/lwt-parsers

# Install packages
/opt/lwt-parsers/bin/pip install jieba mecab-python3

# For Japanese (MeCab), also install system dependencies:
# Debian/Ubuntu:
sudo apt-get install mecab mecab-ipadic-utf8 libmecab-dev

# macOS:
brew install mecab mecab-ipadic
```

#### Windows

1. Install Python from [python.org](https://www.python.org/downloads/)
2. Install MeCab from [MeCab releases](https://github.com/ikegami-yukino/mecab/releases)
3. Open Command Prompt and run:

```batch
python -m venv C:\lwt-parsers
C:\lwt-parsers\Scripts\pip install jieba mecab-python3
```

## Configuring Parsers

External parsers are configured in `config/parsers.php`. This file defines:
- Parser binary path
- Command-line arguments
- Input/output modes

### Example Configuration

```php
return [
    'jieba' => [
        'name' => 'Jieba (Chinese)',
        'binary' => '/opt/lwt-parsers/bin/python3',
        'args' => ['/opt/lwt/parsers/jieba_tokenize.py'],
        'input_mode' => 'stdin',
        'output_format' => 'line',
    ],
    'mecab-python' => [
        'name' => 'MeCab Python (Japanese)',
        'binary' => '/opt/lwt-parsers/bin/python3',
        'args' => ['/opt/lwt/parsers/mecab_tokenize.py'],
        'input_mode' => 'stdin',
        'output_format' => 'line',
    ],
];
```

### Configuration Options

| Option | Description |
|--------|-------------|
| `name` | Display name shown in language settings |
| `binary` | Path to the executable (e.g., Python interpreter) |
| `args` | Array of command-line arguments |
| `input_mode` | How text is passed: `stdin` (pipe) or `file` (temp file path) |
| `output_format` | Output format: `line` (one token per line) or `wakati` (space-separated) |

## Using Parsers in Language Settings

1. Go to **Languages** in the main menu
2. Create or edit a language
3. In **RegExp Word Characters**, select the parser name (e.g., `jieba` or `mecab-python`)
4. The parser will be used to segment text for that language

## Creating Custom Parsers

You can add custom parsers by:

1. Creating an executable script that:
   - Reads text from stdin (or a file path argument)
   - Outputs tokens, one per line (or space-separated)
   - Preserves paragraph breaks as empty lines

2. Adding the configuration to `config/parsers.php`

### Example Custom Parser Script

```python
#!/usr/bin/env python3
import sys

def tokenize(text: str) -> None:
    """Your tokenization logic here."""
    for paragraph in text.split('\n'):
        if not paragraph.strip():
            print()  # Preserve paragraph breaks
            continue
        # Your word segmentation logic
        for word in your_segmenter(paragraph):
            print(word)
        print()  # End of paragraph

if __name__ == '__main__':
    text = sys.stdin.read()
    if text.strip():
        tokenize(text)
```

## Security Notes

Parser configuration is restricted to server administrators only. Binary paths come from the server-side configuration file (`config/parsers.php`), not from user input. This prevents arbitrary code execution vulnerabilities.

Never allow untrusted users to modify `config/parsers.php`.

## Troubleshooting

### Parser not appearing in language settings

1. Check that the binary path in `config/parsers.php` is correct
2. Verify the binary is executable: `ls -la /path/to/binary`
3. Test the parser manually:
   ```bash
   echo "Test text" | /path/to/python /path/to/parser_script.py
   ```

### MeCab: "no such file or directory: mecabrc"

MeCab needs its configuration file. Create a symlink:
```bash
sudo mkdir -p /usr/local/etc
sudo ln -s /etc/mecabrc /usr/local/etc/mecabrc
```

### Jieba: Slow first run

Jieba builds a dictionary cache on first use. Subsequent runs will be faster.

### Parser returns empty results

1. Check the script works standalone
2. Verify input encoding is UTF-8
3. Check for error messages in PHP logs
