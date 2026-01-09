from abc import ABC, abstractmethod
from pydantic import BaseModel


class Token(BaseModel):
    text: str
    is_word: bool
    reading: str | None = None


class ParseResult(BaseModel):
    sentences: list[str]
    tokens: list[Token]


class BaseParser(ABC):
    @abstractmethod
    def parse(self, text: str) -> ParseResult:
        pass
