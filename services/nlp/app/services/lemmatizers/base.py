from abc import ABC, abstractmethod
from pydantic import BaseModel


class LemmaResult(BaseModel):
    """Result of lemmatizing a single word."""
    word: str
    lemma: str | None


class BatchLemmaResult(BaseModel):
    """Result of lemmatizing multiple words."""
    results: dict[str, str | None]  # word -> lemma mapping


class LemmatizerInfo(BaseModel):
    """Information about a lemmatizer."""
    id: str
    name: str
    languages: list[str]
    available: bool


class BaseLemmatizer(ABC):
    """Base class for lemmatizers."""

    @abstractmethod
    def lemmatize(self, word: str, language: str) -> str | None:
        """Lemmatize a single word."""
        pass

    @abstractmethod
    def lemmatize_batch(self, words: list[str], language: str) -> dict[str, str | None]:
        """Lemmatize multiple words."""
        pass

    @abstractmethod
    def supports_language(self, language: str) -> bool:
        """Check if language is supported."""
        pass

    @abstractmethod
    def get_supported_languages(self) -> list[str]:
        """Get list of supported languages."""
        pass
