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
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Text\Http;

use Lwt\Api\V1\Response;
use Lwt\Modules\Text\Infrastructure\WhisperClient;
use Lwt\Modules\Text\Infrastructure\WhisperJobRepository;
use Lwt\Shared\Http\ApiRoutableInterface;
use Lwt\Shared\Http\ApiRoutableTrait;
use Lwt\Shared\Infrastructure\Http\JsonResponse;

/**
 * Handler for Whisper transcription API endpoints.
 *
 * Proxies requests to the NLP microservice for Whisper transcription.
 *
 * @since 3.0.0
 */
class WhisperApiHandler implements ApiRoutableInterface
{
    use ApiRoutableTrait;

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
    private WhisperJobRepository $jobs;

    public function __construct(?WhisperClient $client = null, ?WhisperJobRepository $jobs = null)
    {
        $this->client = $client ?? new WhisperClient();
        $this->jobs = $jobs ?? new WhisperJobRepository();
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

        // Bind the NLP-issued job_id to the caller so status/result/cancel
        // can reject foreign IDs even if the UUID leaks.
        $this->jobs->recordForCurrentUser($jobId);

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
        if (!$this->jobs->isOwnedByCurrentUser($jobId)) {
            throw new \RuntimeException('Job not found');
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
        if (!$this->jobs->isOwnedByCurrentUser($jobId)) {
            throw new \RuntimeException('Job not found');
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
        if (!$this->jobs->isOwnedByCurrentUser($jobId)) {
            throw new \RuntimeException('Job not found');
        }

        $cancelled = $this->client->cancelJob($jobId);
        if ($cancelled) {
            $this->jobs->forget($jobId);
        }
        return ['cancelled' => $cancelled];
    }

    public function routeGet(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        switch ($frag1) {
            case 'available':
                return Response::success($this->formatIsAvailable());
            case 'languages':
                return Response::success($this->formatGetLanguages());
            case 'models':
                return Response::success($this->formatGetModels());
            case 'status':
                if ($frag2 === '') {
                    return Response::error('job_id is required', 400);
                }
                try {
                    return Response::success($this->formatGetStatus($frag2));
                } catch (\RuntimeException $e) {
                    // The ownership check throws "Job not found" — surface
                    // it as 404 (same shape as an unknown job_id at NLP).
                    return Response::error($e->getMessage(), 404);
                }
            case 'result':
                if ($frag2 === '') {
                    return Response::error('job_id is required', 400);
                }
                try {
                    return Response::success($this->formatGetResult($frag2));
                } catch (\RuntimeException $e) {
                    $code = $e->getMessage() === 'Job not found' ? 404 : 500;
                    return Response::error($e->getMessage(), $code);
                }
            default:
                return Response::error(
                    'Expected "available", "languages", "models", "status/{id}", or "result/{id}"',
                    404
                );
        }
    }

    public function routePost(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);

        if ($frag1 === 'transcribe') {
            /** @var array{name?: string, tmp_name?: string, size?: int}|null $file */
            $file = $_FILES['file'] ?? null;
            if ($file === null) {
                return Response::error('No file uploaded', 400);
            }

            $language = isset($params['language']) && $params['language'] !== '' ? (string) $params['language'] : null;
            $model = (string) ($params['model'] ?? 'small');

            try {
                return Response::success($this->formatStartTranscription($file, $language, $model));
            } catch (\InvalidArgumentException $e) {
                return Response::error($e->getMessage(), 400);
            } catch (\RuntimeException $e) {
                return Response::error($e->getMessage(), 503);
            }
        }

        return Response::error('Expected "transcribe"', 404);
    }

    public function routeDelete(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'job' && $frag2 !== '') {
            try {
                return Response::success($this->formatCancelJob($frag2));
            } catch (\RuntimeException $e) {
                return Response::error($e->getMessage(), 404);
            }
        }

        return Response::error('Expected "job/{id}"', 404);
    }
}
