from fastapi import FastAPI
from app.routers import tts, parse, lemmatize

app = FastAPI(title="LWT NLP Service", version="1.0.0")

app.include_router(tts.router, prefix="/tts", tags=["TTS"])
app.include_router(parse.router, prefix="/parse", tags=["Parsing"])
app.include_router(lemmatize.router, prefix="/lemmatize", tags=["Lemmatization"])


@app.get("/health")
async def health():
    return {"status": "ok", "version": "1.0.0"}
