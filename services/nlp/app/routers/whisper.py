"""Whisper transcription router with async job handling."""

import os
import tempfile
from fastapi import APIRouter, HTTPException, UploadFile, File, Form
from typing import Optional

from app.services.transcription_service import transcription_service, JobStatus

router = APIRouter()

# Allowed audio/video extensions
ALLOWED_EXTENSIONS = {
    "mp3",
    "mp4",
    "wav",
    "webm",
    "ogg",
    "m4a",
    "mkv",
    "flac",
    "avi",
    "mov",
    "wma",
    "aac",
}

# Max file size: 500MB
MAX_FILE_SIZE = 500 * 1024 * 1024


@router.get("/available")
async def check_available():
    """Check if Whisper is installed and available."""
    return {"available": transcription_service.is_available()}


@router.get("/languages")
async def list_languages():
    """List supported languages for transcription."""
    return {"languages": transcription_service.get_languages()}


@router.get("/models")
async def list_models():
    """List available Whisper models."""
    return {"models": transcription_service.get_models()}


@router.post("/transcribe")
async def start_transcription(
    file: UploadFile = File(...),
    language: Optional[str] = Form(None),
    model: str = Form("small"),
):
    """
    Start an async transcription job.

    Args:
        file: Audio or video file to transcribe
        language: Optional language code (None for auto-detect)
        model: Whisper model name (tiny, base, small, medium, large)

    Returns:
        job_id for tracking the transcription
    """
    if not transcription_service.is_available():
        raise HTTPException(
            status_code=503,
            detail="Whisper is not available. Please install openai-whisper.",
        )

    # Validate file extension
    filename = file.filename or "unknown"
    ext = filename.rsplit(".", 1)[-1].lower() if "." in filename else ""
    if ext not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=400,
            detail=f"Unsupported file type: {ext}. Allowed: {', '.join(sorted(ALLOWED_EXTENSIONS))}",
        )

    # Validate model
    valid_models = ["tiny", "base", "small", "medium", "large"]
    if model not in valid_models:
        raise HTTPException(
            status_code=400,
            detail=f"Invalid model: {model}. Allowed: {', '.join(valid_models)}",
        )

    # Save uploaded file to temp location
    try:
        content = await file.read()

        # Check file size
        if len(content) > MAX_FILE_SIZE:
            raise HTTPException(
                status_code=400,
                detail=f"File too large. Maximum size: {MAX_FILE_SIZE // (1024 * 1024)}MB",
            )

        # Create temp file with proper extension
        suffix = f".{ext}" if ext else ""
        with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
            tmp.write(content)
            temp_path = tmp.name

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Failed to process upload: {e}")

    # Start transcription job
    try:
        job_id = transcription_service.start_transcription(
            file_path=temp_path,
            language=language,
            model=model,
        )
        return {"job_id": job_id, "status": "pending", "message": "Transcription queued"}

    except Exception as e:
        # Clean up temp file on error
        if os.path.exists(temp_path):
            os.remove(temp_path)
        raise HTTPException(status_code=500, detail=f"Failed to start transcription: {e}")


@router.get("/status/{job_id}")
async def get_status(job_id: str):
    """Get the status of a transcription job."""
    job = transcription_service.get_job(job_id)
    if not job:
        raise HTTPException(status_code=404, detail="Job not found")

    return {
        "job_id": job.job_id,
        "status": job.status.value,
        "progress": job.progress,
        "message": job.message,
    }


@router.get("/result/{job_id}")
async def get_result(job_id: str):
    """Get the result of a completed transcription job."""
    job = transcription_service.get_job(job_id)
    if not job:
        raise HTTPException(status_code=404, detail="Job not found")

    if job.status == JobStatus.PENDING:
        raise HTTPException(status_code=202, detail="Job is still pending")

    if job.status == JobStatus.PROCESSING:
        raise HTTPException(status_code=202, detail="Job is still processing")

    if job.status == JobStatus.FAILED:
        raise HTTPException(status_code=500, detail=job.error or "Transcription failed")

    if job.status == JobStatus.CANCELLED:
        raise HTTPException(status_code=410, detail="Job was cancelled")

    return {
        "job_id": job.job_id,
        "text": job.text,
        "language": job.language,
        "duration_seconds": job.duration_seconds,
    }


@router.delete("/job/{job_id}")
async def cancel_job(job_id: str):
    """Cancel or delete a transcription job."""
    job = transcription_service.get_job(job_id)
    if not job:
        raise HTTPException(status_code=404, detail="Job not found")

    if job.status in (JobStatus.PENDING, JobStatus.PROCESSING):
        transcription_service.cancel_job(job_id)
        return {"cancelled": True, "message": "Job cancellation requested"}

    # If already completed/failed/cancelled, just delete it
    transcription_service.delete_job(job_id)
    return {"deleted": True, "message": "Job removed"}
