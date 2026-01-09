#!/usr/bin/env python3
"""
Jieba tokenizer bridge for LWT (Learning With Texts).

This script reads Chinese text from stdin and outputs tokens one per line,
compatible with LWT's ExternalParser 'line' output format.

Usage:
    echo "这是一个测试" | python3 jieba_tokenize.py

Output format:
    - One token per line
    - Empty lines indicate sentence/paragraph boundaries
    - All Chinese characters and punctuation are preserved

Dependencies:
    pip install jieba
"""

import sys
import re

try:
    import jieba
except ImportError:
    print("Error: jieba is not installed. Install with: pip install jieba", file=sys.stderr)
    sys.exit(1)


# Chinese sentence-ending punctuation
SENTENCE_ENDINGS = re.compile(r'[。！？…\n]')

# Chinese punctuation that should be treated as non-words
PUNCTUATION = re.compile(r'^[\s\u3000-\u303F\uFF00-\uFFEF\u2000-\u206F]+$')


def is_word(token: str) -> bool:
    """Check if a token is a word (not just punctuation/whitespace)."""
    if not token or not token.strip():
        return False
    # Contains at least one CJK character or letter
    return bool(re.search(r'[\u4e00-\u9fff\u3400-\u4dbf\p{L}]', token, re.UNICODE))


def tokenize(text: str) -> None:
    """
    Tokenize Chinese text using jieba and output tokens.

    Args:
        text: Input text to tokenize
    """
    # Normalize whitespace but preserve newlines
    text = re.sub(r'[^\S\n]+', ' ', text)

    # Split into paragraphs first
    paragraphs = text.split('\n')

    for para_idx, paragraph in enumerate(paragraphs):
        paragraph = paragraph.strip()

        if not paragraph:
            # Empty line = paragraph boundary
            print()
            continue

        # Use jieba's precise mode for better accuracy
        tokens = jieba.cut(paragraph, cut_all=False)

        for token in tokens:
            if token and token.strip():
                print(token)

        # Paragraph boundary
        print()


def main():
    """Main entry point."""
    # Disable jieba's verbose output
    jieba.setLogLevel(jieba.logging.WARNING)

    # Read all input from stdin
    try:
        text = sys.stdin.read()
    except KeyboardInterrupt:
        sys.exit(0)

    if not text.strip():
        sys.exit(0)

    tokenize(text)


if __name__ == '__main__':
    main()
