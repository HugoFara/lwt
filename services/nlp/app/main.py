from fastapi import FastAPI
from app.routers import tts, parse

app = FastAPI(title="LWT NLP Service", version="1.0.0")

app.include_router(tts.router, prefix="/tts", tags=["TTS"])
app.include_router(parse.router, prefix="/parse", tags=["Parsing"])


@app.get("/health")
async def health():
    return {"status": "ok", "version": "1.0.0"}
