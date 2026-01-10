import re
import jieba as jieba_lib
from .base import BaseParser, ParseResult, Token

# Suppress jieba's verbose logging
jieba_lib.setLogLevel(20)


class JiebaParser(BaseParser):
    def parse(self, text: str) -> ParseResult:
        sentences = []
        tokens = []

        # Split by newlines for paragraphs
        paragraphs = text.split('\n')

        for para in paragraphs:
            if not para.strip():
                continue

            words = jieba_lib.cut(para, cut_all=False)
            sentence_tokens = []

            for word in words:
                # Check if word is punctuation/whitespace
                is_word = bool(re.search(r'[\u4e00-\u9fff\w]', word))

                sentence_tokens.append(Token(
                    text=word,
                    is_word=is_word,
                    reading=None
                ))

            if sentence_tokens:
                sentences.append(para)
                tokens.extend(sentence_tokens)

        return ParseResult(sentences=sentences, tokens=tokens)


_parser = None


def get_parser() -> JiebaParser:
    global _parser
    if _parser is None:
        _parser = JiebaParser()
    return _parser


def parse(text: str) -> ParseResult:
    return get_parser().parse(text)
