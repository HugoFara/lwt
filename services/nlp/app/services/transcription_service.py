"""Whisper transcription service with async job handling."""

import os
import uuid
import time
import tempfile
import threading
from concurrent.futures import ThreadPoolExecutor
from dataclasses import dataclass, field
from enum import Enum
from typing import Optional


class JobStatus(str, Enum):
    PENDING = "pending"
    PROCESSING = "processing"
    COMPLETED = "completed"
    FAILED = "failed"
    CANCELLED = "cancelled"


@dataclass
class TranscriptionJob:
    """Represents a transcription job."""

    job_id: str
    status: JobStatus = JobStatus.PENDING
    progress: int = 0
    message: str = ""
    text: Optional[str] = None
    language: Optional[str] = None
    duration_seconds: float = 0.0
    created_at: float = field(default_factory=time.time)
    completed_at: Optional[float] = None
    error: Optional[str] = None
    cancelled: bool = False


# Whisper language codes
WHISPER_LANGUAGES = {
    "af": "Afrikaans",
    "ar": "Arabic",
    "hy": "Armenian",
    "az": "Azerbaijani",
    "be": "Belarusian",
    "bs": "Bosnian",
    "bg": "Bulgarian",
    "ca": "Catalan",
    "zh": "Chinese",
    "hr": "Croatian",
    "cs": "Czech",
    "da": "Danish",
    "nl": "Dutch",
    "en": "English",
    "et": "Estonian",
    "fi": "Finnish",
    "fr": "French",
    "gl": "Galician",
    "de": "German",
    "el": "Greek",
    "he": "Hebrew",
    "hi": "Hindi",
    "hu": "Hungarian",
    "is": "Icelandic",
    "id": "Indonesian",
    "it": "Italian",
    "ja": "Japanese",
    "kn": "Kannada",
    "kk": "Kazakh",
    "ko": "Korean",
    "lv": "Latvian",
    "lt": "Lithuanian",
    "mk": "Macedonian",
    "ms": "Malay",
    "mr": "Marathi",
    "mi": "Maori",
    "ne": "Nepali",
    "no": "Norwegian",
    "fa": "Persian",
    "pl": "Polish",
    "pt": "Portuguese",
    "ro": "Romanian",
    "ru": "Russian",
    "sr": "Serbian",
    "sk": "Slovak",
    "sl": "Slovenian",
    "es": "Spanish",
    "sw": "Swahili",
    "sv": "Swedish",
    "tl": "Tagalog",
    "ta": "Tamil",
    "th": "Thai",
    "tr": "Turkish",
    "uk": "Ukrainian",
    "ur": "Urdu",
    "vi": "Vietnamese",
    "cy": "Welsh",
}

WHISPER_MODELS = ["tiny", "base", "small", "medium", "large"]


class TranscriptionService:
    """Service for handling Whisper transcriptions with async job support."""

    def __init__(self, max_workers: int = 2, job_ttl_seconds: int = 3600):
        self._jobs: dict[str, TranscriptionJob] = {}
        self._lock = threading.Lock()
        self._executor = ThreadPoolExecutor(max_workers=max_workers)
        self._job_ttl = job_ttl_seconds
        self._whisper_model = None
        self._current_model_name: Optional[str] = None
        self._model_lock = threading.Lock()

    def is_available(self) -> bool:
        """Check if Whisper is installed and available."""
        try:
            import whisper

            return True
        except ImportError:
            return False

    def get_languages(self) -> list[dict]:
        """Get list of supported languages."""
        return [{"code": code, "name": name} for code, name in WHISPER_LANGUAGES.items()]

    def get_models(self) -> list[dict]:
        """Get list of available Whisper models."""
        return [
            {"name": "tiny", "description": "Fastest, lowest accuracy (~1GB VRAM)"},
            {"name": "base", "description": "Fast, good accuracy (~1GB VRAM)"},
            {"name": "small", "description": "Balanced speed/accuracy (~2GB VRAM)"},
            {"name": "medium", "description": "Higher accuracy, slower (~5GB VRAM)"},
            {"name": "large", "description": "Best accuracy, slowest (~10GB VRAM)"},
        ]

    def _load_model(self, model_name: str):
        """Load Whisper model (lazy loading, cached)."""
        with self._model_lock:
            if self._whisper_model is None or self._current_model_name != model_name:
                import whisper

                self._whisper_model = whisper.load_model(model_name)
                self._current_model_name = model_name
            return self._whisper_model

    def start_transcription(
        self,
        file_path: str,
        language: Optional[str] = None,
        model: str = "small",
    ) -> str:
        """
        Start an async transcription job.

        Args:
            file_path: Path to the audio/video file
            language: Optional language code (None for auto-detect)
            model: Whisper model name (tiny, base, small, medium, large)

        Returns:
            job_id for tracking the transcription
        """
        job_id = str(uuid.uuid4())

        job = TranscriptionJob(
            job_id=job_id,
            status=JobStatus.PENDING,
            message="Queued for transcription",
        )

        with self._lock:
            self._jobs[job_id] = job
            self._cleanup_old_jobs()

        # Submit to thread pool
        self._executor.submit(self._run_transcription, job_id, file_path, language, model)

        return job_id

    def _run_transcription(
        self,
        job_id: str,
        file_path: str,
        language: Optional[str],
        model: str,
    ):
        """Run transcription in background thread."""
        job = self._jobs.get(job_id)
        if not job:
            return

        try:
            # Update status
            with self._lock:
                job.status = JobStatus.PROCESSING
                job.message = "Loading model..."
                job.progress = 5

            # Check for cancellation
            if job.cancelled:
                with self._lock:
                    job.status = JobStatus.CANCELLED
                    job.message = "Cancelled by user"
                return

            # Load model
            whisper_model = self._load_model(model)

            with self._lock:
                job.message = "Transcribing audio..."
                job.progress = 10

            # Check for cancellation
            if job.cancelled:
                with self._lock:
                    job.status = JobStatus.CANCELLED
                    job.message = "Cancelled by user"
                return

            # Run transcription
            transcribe_options = {"fp16": False}  # Use FP32 for CPU compatibility
            if language:
                transcribe_options["language"] = language

            result = whisper_model.transcribe(file_path, **transcribe_options)

            # Check for cancellation
            if job.cancelled:
                with self._lock:
                    job.status = JobStatus.CANCELLED
                    job.message = "Cancelled by user"
                return

            # Update job with results
            with self._lock:
                job.status = JobStatus.COMPLETED
                job.progress = 100
                job.message = "Transcription complete"
                job.text = result.get("text", "").strip()
                job.language = result.get("language", language)
                # Calculate duration from segments if available
                segments = result.get("segments", [])
                if segments:
                    job.duration_seconds = segments[-1].get("end", 0)
                job.completed_at = time.time()

        except Exception as e:
            with self._lock:
                job.status = JobStatus.FAILED
                job.error = str(e)
                job.message = f"Transcription failed: {e}"
                job.completed_at = time.time()

        finally:
            # Clean up temp file if it exists
            if file_path.startswith(tempfile.gettempdir()) and os.path.exists(file_path):
                try:
                    os.remove(file_path)
                except OSError:
                    pass

    def get_job(self, job_id: str) -> Optional[TranscriptionJob]:
        """Get job by ID."""
        with self._lock:
            return self._jobs.get(job_id)

    def cancel_job(self, job_id: str) -> bool:
        """Cancel a job if it's still pending or processing."""
        with self._lock:
            job = self._jobs.get(job_id)
            if not job:
                return False

            if job.status in (JobStatus.PENDING, JobStatus.PROCESSING):
                job.cancelled = True
                return True

            return False

    def delete_job(self, job_id: str) -> bool:
        """Delete a job from memory."""
        with self._lock:
            if job_id in self._jobs:
                del self._jobs[job_id]
                return True
            return False

    def _cleanup_old_jobs(self):
        """Remove jobs older than TTL."""
        current_time = time.time()
        expired_jobs = [
            job_id
            for job_id, job in self._jobs.items()
            if job.completed_at and (current_time - job.completed_at) > self._job_ttl
        ]
        for job_id in expired_jobs:
            del self._jobs[job_id]


# Global service instance
transcription_service = TranscriptionService()
