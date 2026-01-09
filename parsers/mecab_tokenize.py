#!/usr/bin/env python3
"""
MeCab tokenizer bridge for LWT (Learning With Texts).

This script reads Japanese text from stdin and outputs tokens one per line,
compatible with LWT's ExternalParser 'line' output format.

Usage:
    echo "これはテストです" | python3 mecab_tokenize.py

Output format:
    - One token per line
    - Empty lines indicate sentence/paragraph boundaries
    - All Japanese characters and punctuation are preserved

Dependencies:
    - System: mecab, mecab-ipadic-utf8 (or other dictionary)
    - Python: pip install mecab-python3

Installation on Debian/Ubuntu:
    apt-get install mecab mecab-ipadic-utf8
    pip install mecab-python3
"""

import sys
import re

try:
    import MeCab
except ImportError:
    print("Error: mecab-python3 is not installed.", file=sys.stderr)
    print("Install with: pip install mecab-python3", file=sys.stderr)
    print("Also ensure system MeCab is installed: apt-get install mecab mecab-ipadic-utf8", file=sys.stderr)
    sys.exit(1)


def tokenize(text: str) -> None:
    """
    Tokenize Japanese text using MeCab and output tokens.

    Args:
        text: Input text to tokenize
    """
    try:
        # Create MeCab tagger
        # Empty string uses default dictionary
        tagger = MeCab.Tagger("")
    except RuntimeError as e:
        print(f"Error initializing MeCab: {e}", file=sys.stderr)
        print("Ensure MeCab dictionary is installed: apt-get install mecab-ipadic-utf8", file=sys.stderr)
        sys.exit(1)

    # Normalize whitespace but preserve newlines
    text = re.sub(r'[^\S\n]+', ' ', text)

    # Split into paragraphs
    paragraphs = text.split('\n')

    for para_idx, paragraph in enumerate(paragraphs):
        paragraph = paragraph.strip()

        if not paragraph:
            # Empty line = paragraph boundary
            print()
            continue

        # Parse with MeCab
        # parseToNode returns a linked list of nodes
        node = tagger.parseToNode(paragraph)

        while node:
            # surface is the actual token text
            surface = node.surface

            if surface:
                # Output the token
                print(surface)

            # Move to next node
            node = node.next

        # Paragraph boundary
        print()


def main():
    """Main entry point."""
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
