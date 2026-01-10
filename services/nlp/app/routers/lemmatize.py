"""
Lemmatization API endpoints.

Provides lemmatization services using various backends (spaCy, etc.).
"""
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from app.services.lemmatizers.base import LemmaResult, BatchLemmaResult, LemmatizerInfo
from app.services.lemmatizers.spacy_lemmatizer import get_spacy_lemmatizer, SPACY_MODELS

router = APIRouter()


class LemmatizeRequest(BaseModel):
    """Request to lemmatize a single word."""
    word: str
    language: str
    lemmatizer: str = "spacy"  # 'spacy' | 'stanza' (future)


class BatchLemmatizeRequest(BaseModel):
    """Request to lemmatize multiple words."""
    words: list[str]
    language: str
    lemmatizer: str = "spacy"


@router.post("/", response_model=LemmaResult)
async def lemmatize(request: LemmatizeRequest):
    """
    Lemmatize a single word.

    Returns the base form (lemma) of the word, or null if the word
    is already in base form or not recognized.
    """
    if request.lemmatizer == "spacy":
        lemmatizer = get_spacy_lemmatizer()
        if not lemmatizer.supports_language(request.language):
            raise HTTPException(
                400,
                f"Language '{request.language}' not supported by spaCy. "
                f"Available: {lemmatizer.get_supported_languages()}"
            )
        lemma = lemmatizer.lemmatize(request.word, request.language)
        return LemmaResult(word=request.word, lemma=lemma)
    else:
        raise HTTPException(400, f"Unknown lemmatizer: {request.lemmatizer}")


@router.post("/batch", response_model=BatchLemmaResult)
async def lemmatize_batch(request: BatchLemmatizeRequest):
    """
    Lemmatize multiple words.

    Returns a mapping of words to their lemmas. Words that are already
    in base form or not recognized will have null as their lemma.
    """
    if not request.words:
        return BatchLemmaResult(results={})

    if request.lemmatizer == "spacy":
        lemmatizer = get_spacy_lemmatizer()
        if not lemmatizer.supports_language(request.language):
            raise HTTPException(
                400,
                f"Language '{request.language}' not supported by spaCy. "
                f"Available: {lemmatizer.get_supported_languages()}"
            )
        results = lemmatizer.lemmatize_batch(request.words, request.language)
        return BatchLemmaResult(results=results)
    else:
        raise HTTPException(400, f"Unknown lemmatizer: {request.lemmatizer}")


@router.get("/available")
async def available_lemmatizers():
    """
    List available lemmatizers and their supported languages.

    Shows both installed and potentially available lemmatizers.
    """
    spacy = get_spacy_lemmatizer()
    installed_langs = spacy.get_supported_languages()
    all_langs = spacy.get_all_languages()

    lemmatizers = [
        LemmatizerInfo(
            id="spacy",
            name="spaCy NLP",
            languages=installed_langs,
            available=len(installed_langs) > 0
        ),
    ]

    return {
        "lemmatizers": lemmatizers,
        "spacy_models": {
            "installed": installed_langs,
            "available": all_langs,
            "model_map": SPACY_MODELS
        }
    }


@router.get("/languages/{language}")
async def check_language(language: str):
    """
    Check if a language is supported for lemmatization.

    Returns details about available lemmatizers for the language.
    """
    spacy = get_spacy_lemmatizer()

    return {
        "language": language,
        "spacy": {
            "supported": language in SPACY_MODELS,
            "installed": spacy.supports_language(language),
            "model": SPACY_MODELS.get(language)
        }
    }
