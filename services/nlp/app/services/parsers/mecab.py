import MeCab
from .base import BaseParser, ParseResult, Token


class MeCabParser(BaseParser):
    def __init__(self):
        self._tagger = MeCab.Tagger()

    def parse(self, text: str) -> ParseResult:
        sentences = []
        tokens = []

        # Split by newlines for paragraphs
        paragraphs = text.split('\n')

        for para in paragraphs:
            if not para.strip():
                continue

            node = self._tagger.parseToNode(para)
            sentence_tokens = []

            while node:
                surface = node.surface
                if surface:
                    features = node.feature.split(',')
                    pos = features[0] if features else ""

                    # Get reading (furigana) if available
                    reading = None
                    if len(features) > 7 and features[7] != '*':
                        reading = features[7]

                    # Determine if it's a "word" (learnable)
                    is_word = pos not in ['記号', '補助記号', '空白']

                    sentence_tokens.append(Token(
                        text=surface,
                        is_word=is_word,
                        reading=reading
                    ))

                node = node.next

            if sentence_tokens:
                sentences.append(para)
                tokens.extend(sentence_tokens)

        return ParseResult(sentences=sentences, tokens=tokens)


_parser = None


def get_parser() -> MeCabParser:
    global _parser
    if _parser is None:
        _parser = MeCabParser()
    return _parser


def parse(text: str) -> ParseResult:
    return get_parser().parse(text)
