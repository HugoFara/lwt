from fastapi import FastAPI
from app.routers import tts, parse, lemmatize, whisper

app = FastAPI(title="LWT NLP Service", version="1.0.0")

app.include_router(tts.router, prefix="/tts", tags=["TTS"])
app.include_router(parse.router, prefix="/parse", tags=["Parsing"])
app.include_router(lemmatize.router, prefix="/lemmatize", tags=["Lemmatization"])
app.include_router(whisper.router, prefix="/whisper", tags=["Whisper"])


@app.get("/health")
async def health():
    return {"status": "ok", "version": "1.0.0"}
