<?php

namespace Lwt\Api\V1\Handlers;

use Lwt\Core\Bootstrap\EnvLoader;

/**
 * Handler for communicating with the Python NLP microservice.
 *
 * Provides text-to-speech (Piper TTS), text parsing (MeCab/Jieba),
 * and voice management functionality.
 */
class NlpServiceHandler
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = EnvLoader::get('NLP_SERVICE_URL', 'http://nlp:8000');
        $this->timeout = 30;
    }

    /**
     * Check if the NLP service is available.
     */
    public function isAvailable(): bool
    {
        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 5]
        ]);
        $response = @file_get_contents($this->baseUrl . '/health', false, $context);
        return $response !== false;
    }

    /**
     * Synthesize speech using Piper TTS.
     *
     * @param string $text The text to speak
     * @param string $voiceId The Piper voice ID
     * @return string|null Base64 data URL of WAV audio, or null on failure
     */
    public function speak(string $text, string $voiceId): ?string
    {
        $payload = json_encode(['text' => $text, 'voice_id' => $voiceId]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ]
        ]);

        $audio = @file_get_contents($this->baseUrl . '/tts/speak', false, $context);

        if ($audio === false) {
            return null;
        }

        // Return as base64 data URL
        return 'data:audio/wav;base64,' . base64_encode($audio);
    }

    /**
     * Get list of all voices (installed and available for download).
     *
     * @return array List of voice objects
     */
    public function getVoices(): array
    {
        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 10]
        ]);

        $response = @file_get_contents($this->baseUrl . '/tts/voices', false, $context);
        if ($response === false) {
            return [];
        }

        $data = json_decode($response, true);
        return $data['voices'] ?? [];
    }

    /**
     * Get list of installed voices only.
     *
     * @return array List of installed voice objects
     */
    public function getInstalledVoices(): array
    {
        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 10]
        ]);

        $response = @file_get_contents($this->baseUrl . '/tts/voices/installed', false, $context);
        if ($response === false) {
            return [];
        }

        $data = json_decode($response, true);
        return $data['voices'] ?? [];
    }

    /**
     * Download a voice from the catalog.
     *
     * @param string $voiceId The voice ID to download
     * @return bool True on success
     */
    public function downloadVoice(string $voiceId): bool
    {
        $payload = json_encode(['voice_id' => $voiceId]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => 300, // Downloads can take time
            ]
        ]);

        $response = @file_get_contents($this->baseUrl . '/tts/voices/download', false, $context);
        return $response !== false;
    }

    /**
     * Delete an installed voice.
     *
     * @param string $voiceId The voice ID to delete
     * @return bool True on success
     */
    public function deleteVoice(string $voiceId): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'timeout' => 10,
            ]
        ]);

        $response = @file_get_contents(
            $this->baseUrl . '/tts/voices/' . urlencode($voiceId),
            false,
            $context
        );
        return $response !== false;
    }

    /**
     * Parse text using MeCab or Jieba.
     *
     * @param string $text The text to parse
     * @param string $parser Parser type: 'mecab' or 'jieba'
     * @return array|null Parsed result with sentences and tokens, or null on failure
     */
    public function parse(string $text, string $parser): ?array
    {
        $payload = json_encode(['text' => $text, 'parser' => $parser]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => $this->timeout,
            ]
        ]);

        $response = @file_get_contents($this->baseUrl . '/parse/', false, $context);
        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get list of available parsers.
     *
     * @return array List of parser objects
     */
    public function getAvailableParsers(): array
    {
        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 10]
        ]);

        $response = @file_get_contents($this->baseUrl . '/parse/available', false, $context);
        if ($response === false) {
            return [];
        }

        $data = json_decode($response, true);
        return $data['parsers'] ?? [];
    }

    // =========================================================================
    // Lemmatization Methods
    // =========================================================================

    /**
     * Lemmatize a single word using the NLP service.
     *
     * @param string $word         The word to lemmatize
     * @param string $languageCode ISO language code (e.g., 'en', 'de')
     * @param string $lemmatizer   Lemmatizer type: 'spacy' (default)
     *
     * @return string|null The lemma, or null if word is already base form or error
     */
    public function lemmatize(string $word, string $languageCode, string $lemmatizer = 'spacy'): ?string
    {
        $payload = json_encode([
            'word' => $word,
            'language' => $languageCode,
            'lemmatizer' => $lemmatizer,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ]
        ]);

        $response = @file_get_contents($this->baseUrl . '/lemmatize/', false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['lemma'] ?? null;
    }

    /**
     * Lemmatize multiple words using the NLP service.
     *
     * @param array  $words        Words to lemmatize
     * @param string $languageCode ISO language code
     * @param string $lemmatizer   Lemmatizer type: 'spacy' (default)
     *
     * @return array<string, string|null> Mapping of words to lemmas
     */
    public function lemmatizeBatch(array $words, string $languageCode, string $lemmatizer = 'spacy'): array
    {
        if (empty($words)) {
            return [];
        }

        $payload = json_encode([
            'words' => $words,
            'language' => $languageCode,
            'lemmatizer' => $lemmatizer,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload,
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ]
        ]);

        $response = @file_get_contents($this->baseUrl . '/lemmatize/batch', false, $context);
        if ($response === false) {
            return array_fill_keys($words, null);
        }

        $data = json_decode($response, true);
        return $data['results'] ?? array_fill_keys($words, null);
    }

    /**
     * Get list of available lemmatizers and their supported languages.
     *
     * @return array Lemmatizer information
     */
    public function getAvailableLemmatizers(): array
    {
        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 10]
        ]);

        $response = @file_get_contents($this->baseUrl . '/lemmatize/available', false, $context);
        if ($response === false) {
            return [];
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Check if a language is supported for lemmatization.
     *
     * @param string $languageCode ISO language code
     *
     * @return array Language support information
     */
    public function checkLemmatizationSupport(string $languageCode): array
    {
        $context = stream_context_create([
            'http' => ['method' => 'GET', 'timeout' => 10]
        ]);

        $response = @file_get_contents(
            $this->baseUrl . '/lemmatize/languages/' . urlencode($languageCode),
            false,
            $context
        );

        if ($response === false) {
            return [
                'language' => $languageCode,
                'spacy' => ['supported' => false, 'installed' => false, 'model' => null]
            ];
        }

        return json_decode($response, true) ?? [];
    }
}
