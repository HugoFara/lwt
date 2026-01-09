import io
import wave
from pathlib import Path
from piper import PiperVoice


class PiperTTSService:
    def __init__(self, voices_dir: str):
        self.voices_dir = Path(voices_dir)
        self._voices: dict[str, PiperVoice] = {}

    def _load_voice(self, voice_id: str) -> PiperVoice:
        """Load a voice, caching for reuse."""
        if voice_id not in self._voices:
            voice_path = self.voices_dir / f"{voice_id}.onnx"
            if not voice_path.exists():
                raise FileNotFoundError(f"Voice not installed: {voice_id}")
            self._voices[voice_id] = PiperVoice.load(str(voice_path))
        return self._voices[voice_id]

    def synthesize(self, text: str, voice_id: str) -> bytes:
        """Generate WAV audio from text."""
        voice = self._load_voice(voice_id)

        # Piper outputs raw audio, we need to wrap in WAV
        audio_buffer = io.BytesIO()
        with wave.open(audio_buffer, "wb") as wav_file:
            wav_file.setnchannels(1)
            wav_file.setsampwidth(2)  # 16-bit
            wav_file.setframerate(voice.config.sample_rate)

            for audio_bytes in voice.synthesize_stream_raw(text):
                wav_file.writeframes(audio_bytes)

        return audio_buffer.getvalue()

    def is_voice_available(self, voice_id: str) -> bool:
        """Check if a voice is installed."""
        return (self.voices_dir / f"{voice_id}.onnx").exists()
