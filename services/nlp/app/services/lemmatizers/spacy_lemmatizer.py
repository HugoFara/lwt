"""
SpaCy-based lemmatizer for high-accuracy lemmatization.

SpaCy provides excellent lemmatization for 25+ languages with context awareness.
Models are loaded lazily and cached for performance.
"""
import logging
from functools import lru_cache
from .base import BaseLemmatizer

logger = logging.getLogger(__name__)

# Map of language codes to spaCy model names
# Using small models by default for faster loading; can be upgraded to medium/large
SPACY_MODELS = {
    'en': 'en_core_web_sm',
    'de': 'de_core_news_sm',
    'fr': 'fr_core_news_sm',
    'es': 'es_core_news_sm',
    'pt': 'pt_core_news_sm',
    'it': 'it_core_news_sm',
    'nl': 'nl_core_news_sm',
    'el': 'el_core_news_sm',
    'nb': 'nb_core_news_sm',  # Norwegian Bokmål
    'lt': 'lt_core_news_sm',  # Lithuanian
    'pl': 'pl_core_news_sm',
    'ro': 'ro_core_news_sm',
    'ru': 'ru_core_news_sm',
    'ca': 'ca_core_news_sm',  # Catalan
    'da': 'da_core_news_sm',  # Danish
    'fi': 'fi_core_news_sm',  # Finnish
    'hr': 'hr_core_news_sm',  # Croatian
    'ko': 'ko_core_news_sm',  # Korean
    'mk': 'mk_core_news_sm',  # Macedonian
    'sl': 'sl_core_news_sm',  # Slovenian
    'sv': 'sv_core_news_sm',  # Swedish
    'uk': 'uk_core_news_sm',  # Ukrainian
    'zh': 'zh_core_web_sm',   # Chinese
    'ja': 'ja_core_news_sm',  # Japanese (spaCy also has Japanese support)
}


@lru_cache(maxsize=10)
def _load_model(model_name: str):
    """Load and cache a spaCy model."""
    try:
        import spacy
        return spacy.load(model_name)
    except OSError:
        logger.warning(f"SpaCy model '{model_name}' not installed. Run: python -m spacy download {model_name}")
        return None
    except ImportError:
        logger.error("SpaCy not installed. Run: pip install spacy")
        return None


def _get_available_models() -> dict[str, bool]:
    """Check which spaCy models are installed."""
    available = {}
    try:
        import spacy
        for lang, model in SPACY_MODELS.items():
            try:
                spacy.load(model)
                available[lang] = True
            except OSError:
                available[lang] = False
    except ImportError:
        pass
    return available


class SpacyLemmatizer(BaseLemmatizer):
    """
    Lemmatizer using spaCy NLP library.

    SpaCy provides context-aware lemmatization which handles:
    - Verb conjugations (running -> run, went -> go)
    - Noun plurals (children -> child, mice -> mouse)
    - Adjective comparatives (better -> good)
    - Language-specific morphology
    """

    def __init__(self):
        self._available_models: dict[str, bool] | None = None

    def _get_model(self, language: str):
        """Get spaCy model for language."""
        model_name = SPACY_MODELS.get(language)
        if not model_name:
            return None
        return _load_model(model_name)

    def lemmatize(self, word: str, language: str) -> str | None:
        """Lemmatize a single word using spaCy."""
        nlp = self._get_model(language)
        if nlp is None:
            return None

        # Process the word
        doc = nlp(word)
        if len(doc) == 0:
            return None

        # Get lemma of first token
        lemma = doc[0].lemma_

        # spaCy returns the word itself if it's already a lemma
        # Return None if lemma equals the word (lowercase comparison)
        if lemma.lower() == word.lower():
            return None

        return lemma

    def lemmatize_batch(self, words: list[str], language: str) -> dict[str, str | None]:
        """Lemmatize multiple words using spaCy."""
        nlp = self._get_model(language)
        if nlp is None:
            return {word: None for word in words}

        results = {}

        # Process words individually to avoid context interference
        # For single-word lemmatization, we don't want sentence context
        for word in words:
            doc = nlp(word)
            if len(doc) > 0:
                lemma = doc[0].lemma_
                # Return None if lemma equals the word
                if lemma.lower() == word.lower():
                    results[word] = None
                else:
                    results[word] = lemma
            else:
                results[word] = None

        return results

    def supports_language(self, language: str) -> bool:
        """Check if language is supported and model is available."""
        if language not in SPACY_MODELS:
            return False

        # Check if model is actually installed
        nlp = self._get_model(language)
        return nlp is not None

    def get_supported_languages(self) -> list[str]:
        """Get list of languages with installed models."""
        if self._available_models is None:
            self._available_models = _get_available_models()

        return [lang for lang, available in self._available_models.items() if available]

    def get_all_languages(self) -> list[str]:
        """Get list of all potentially supported languages (including non-installed)."""
        return list(SPACY_MODELS.keys())


# Singleton instance
_instance: SpacyLemmatizer | None = None


def get_spacy_lemmatizer() -> SpacyLemmatizer:
    """Get the singleton SpacyLemmatizer instance."""
    global _instance
    if _instance is None:
        _instance = SpacyLemmatizer()
    return _instance
