import httpx
from pathlib import Path
from app.config import settings

# Curated list of recommended voices per language
VOICE_CATALOG = {
    "en_US": [
        {"id": "en_US-lessac-medium", "name": "Lessac (US)", "quality": "medium"},
        {"id": "en_US-libritts-high", "name": "LibriTTS (US)", "quality": "high"},
    ],
    "en_GB": [
        {"id": "en_GB-alba-medium", "name": "Alba (UK)", "quality": "medium"},
    ],
    "de_DE": [
        {"id": "de_DE-thorsten-high", "name": "Thorsten", "quality": "high"},
    ],
    "fr_FR": [
        {"id": "fr_FR-siwis-medium", "name": "Siwis", "quality": "medium"},
    ],
    "es_ES": [
        {"id": "es_ES-sharvard-medium", "name": "Sharvard", "quality": "medium"},
    ],
    "ja_JP": [
        {"id": "ja_JP-kokoro-medium", "name": "Kokoro", "quality": "medium"},
    ],
    "zh_CN": [
        {"id": "zh_CN-huayan-medium", "name": "Huayan", "quality": "medium"},
    ],
}


class VoiceManager:
    def __init__(self, voices_dir: str):
        self.voices_dir = Path(voices_dir)
        self.voices_dir.mkdir(parents=True, exist_ok=True)

    def get_installed_voices(self) -> list[dict]:
        """List locally installed voice files."""
        voices = []
        for onnx_file in self.voices_dir.glob("*.onnx"):
            voices.append({
                "id": onnx_file.stem,
                "name": onnx_file.stem.replace("-", " ").replace("_", " ").title(),
                "installed": True,
                "path": str(onnx_file)
            })
        return voices

    def get_available_voices(self) -> list[dict]:
        """List all voices (installed + available for download)."""
        installed = {v["id"] for v in self.get_installed_voices()}
        all_voices = []

        for lang, voices in VOICE_CATALOG.items():
            for voice in voices:
                all_voices.append({
                    **voice,
                    "lang": lang,
                    "installed": voice["id"] in installed
                })

        return all_voices

    async def download_voice(self, voice_id: str) -> dict:
        """Download a voice from Hugging Face."""
        # Find voice in catalog
        voice_info = None
        for lang, voices in VOICE_CATALOG.items():
            for v in voices:
                if v["id"] == voice_id:
                    voice_info = {"lang": lang, **v}
                    break

        if not voice_info:
            raise ValueError(f"Unknown voice: {voice_id}")

        # Construct download URLs
        lang_path = voice_info["lang"].replace("_", "/")
        base_url = f"{settings.piper_voices_url}/{lang_path}/{voice_id}"

        onnx_url = f"{base_url}.onnx"
        json_url = f"{base_url}.onnx.json"

        # Download files with increased timeout for large files
        async with httpx.AsyncClient(timeout=300.0) as client:
            # Download ONNX model
            onnx_resp = await client.get(onnx_url, follow_redirects=True)
            onnx_resp.raise_for_status()
            onnx_path = self.voices_dir / f"{voice_id}.onnx"
            onnx_path.write_bytes(onnx_resp.content)

            # Download config JSON
            json_resp = await client.get(json_url, follow_redirects=True)
            json_resp.raise_for_status()
            json_path = self.voices_dir / f"{voice_id}.onnx.json"
            json_path.write_bytes(json_resp.content)

        return {"id": voice_id, "installed": True, "path": str(onnx_path)}

    def delete_voice(self, voice_id: str) -> bool:
        """Remove a downloaded voice."""
        onnx_path = self.voices_dir / f"{voice_id}.onnx"
        json_path = self.voices_dir / f"{voice_id}.onnx.json"

        deleted = False
        if onnx_path.exists():
            onnx_path.unlink()
            deleted = True
        if json_path.exists():
            json_path.unlink()

        return deleted
