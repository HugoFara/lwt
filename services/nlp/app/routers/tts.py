from fastapi import APIRouter, HTTPException
from fastapi.responses import Response
from pydantic import BaseModel
from app.services.piper_tts import PiperTTSService
from app.services.voice_manager import VoiceManager
from app.config import settings

router = APIRouter()
tts_service = PiperTTSService(settings.piper_voices_dir)
voice_manager = VoiceManager(settings.piper_voices_dir)


class SpeakRequest(BaseModel):
    text: str
    voice_id: str


class DownloadRequest(BaseModel):
    voice_id: str


@router.get("/voices")
async def list_voices():
    """List all voices (installed and available for download)."""
    return {"voices": voice_manager.get_available_voices()}


@router.get("/voices/installed")
async def list_installed_voices():
    """List only installed voices."""
    return {"voices": voice_manager.get_installed_voices()}


@router.post("/voices/download")
async def download_voice(request: DownloadRequest):
    """Download a voice from the catalog."""
    try:
        result = await voice_manager.download_voice(request.voice_id)
        return {"success": True, "voice": result}
    except ValueError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Download failed: {e}")


@router.delete("/voices/{voice_id}")
async def delete_voice(voice_id: str):
    """Remove an installed voice."""
    if voice_manager.delete_voice(voice_id):
        return {"success": True}
    raise HTTPException(status_code=404, detail="Voice not found")


@router.post("/speak")
async def speak(request: SpeakRequest):
    """Synthesize speech and return WAV audio."""
    try:
        audio = tts_service.synthesize(request.text, request.voice_id)
        return Response(content=audio, media_type="audio/wav")
    except FileNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Synthesis failed: {e}")
