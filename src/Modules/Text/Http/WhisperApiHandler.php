<?php

/**
 * Whisper API Handler
 *
 * Provides endpoints for audio/video transcription using Whisper.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Text\Http;

use Lwt\Modules\Text\Infrastructure\WhisperClient;

/**
 * Handler for Whisper transcription API endpoints.
 *
 * Proxies requests to the NLP microservice for Whisper transcription.
 *
 * @since 3.0.0
 */
class WhisperApiHandler
{
    /**
     * Allowed audio/video file extensions.
     */
    private const ALLOWED_EXTENSIONS = [
        'mp3', 'mp4', 'wav', 'webm', 'ogg', 'm4a', 'mkv', 'flac', 'avi', 'mov', 'wma', 'aac'
    ];

    /**
     * Maximum file size in bytes (500MB).
     */
    private const MAX_FILE_SIZE = 500 * 1024 * 1024;

    private WhisperClient $client;

    public function __construct()
    {
        $this->client = new WhisperClient();
    }

    /**
     * Check if Whisper transcription is available.
     *
     * @return array{available: bool}
     */
    public function formatIsAvailable(): array
    {
        return ['available' => $this->client->isAvailable()];
    }

    /**
     * Get list of supported languages.
     *
     * @return array{languages: array}
     */
    public function formatGetLanguages(): array
    {
        return ['languages' => $this->client->getLanguages()];
    }

    /**
     * Get list of available Whisper models.
     *
     * @return array{models: array}
     */
    public function formatGetModels(): array
    {
        return ['models' => $this->client->getModels()];
    }

    /**
     * Start a transcription job.
     *
     * @param array{name?: string, tmp_name?: string, size?: int} $file Uploaded file from $_FILES
     * @param string|null $language Language code (null for auto-detect)
     * @param string      $model    Whisper model name
     *
     * @return array{job_id: string}
     *
     * @throws \InvalidArgumentException If file is invalid
     * @throws \RuntimeException If Whisper is not available
     */
    public function formatStartTranscription(
        array $file,
        ?string $language,
        string $model = 'small'
    ): array {
        // Validate file was uploaded
        $tmpName = $file['tmp_name'] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new \InvalidArgumentException('No file uploaded');
        }

        // Validate file extension
        $filename = $file['name'] ?? 'unknown';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(
                'Unsupported file type: ' . $ext . '. Allowed: ' . implode(', ', self::ALLOWED_EXTENSIONS)
            );
        }

        // Validate file size
        $fileSize = $file['size'] ?? 0;
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException(
                'File too large. Maximum size: ' . (self::MAX_FILE_SIZE / (1024 * 1024)) . 'MB'
            );
        }

        // Validate model
        $validModels = ['tiny', 'base', 'small', 'medium', 'large'];
        if (!in_array($model, $validModels, true)) {
            throw new \InvalidArgumentException(
                'Invalid model: ' . $model . '. Allowed: ' . implode(', ', $validModels)
            );
        }

        // Check if Whisper is available
        if (!$this->client->isAvailable()) {
            throw new \RuntimeException('Whisper transcription is not available. Please check NLP service.');
        }

        // Start transcription
        $jobId = $this->client->startTranscription(
            $tmpName,
            $filename,
            $language,
            $model
        );

        return ['job_id' => $jobId];
    }

    /**
     * Get the status of a transcription job.
     *
     * @param string $jobId Job ID
     *
     * @return array{job_id: string, status: string, progress: int, message: string}
     */
    public function formatGetStatus(string $jobId): array
    {
        if (empty($jobId)) {
            throw new \InvalidArgumentException('Job ID is required');
        }

        return $this->client->getStatus($jobId);
    }

    /**
     * Get the result of a completed transcription.
     *
     * @param string $jobId Job ID
     *
     * @return array{job_id: string, text: string, language: string, duration_seconds: float}
     */
    public function formatGetResult(string $jobId): array
    {
        if (empty($jobId)) {
            throw new \InvalidArgumentException('Job ID is required');
        }

        return $this->client->getResult($jobId);
    }

    /**
     * Cancel a transcription job.
     *
     * @param string $jobId Job ID
     *
     * @return array{cancelled: bool}
     */
    public function formatCancelJob(string $jobId): array
    {
        if (empty($jobId)) {
            throw new \InvalidArgumentException('Job ID is required');
        }

        return ['cancelled' => $this->client->cancelJob($jobId)];
    }
}
