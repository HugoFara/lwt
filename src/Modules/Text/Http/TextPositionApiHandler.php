<?php

/**
 * Text Position API Handler
 *
 * Handles text position, audio position, display mode, and bulk word status
 * operations. Extracted from TextApiHandler.
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

use Lwt\Shared\Infrastructure\Database\QueryBuilder;
use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Modules\Vocabulary\Application\Services\WordDiscoveryService;

/**
 * Handler for text position, audio, display mode, and bulk status operations.
 */
class TextPositionApiHandler
{
    private WordDiscoveryService $discoveryService;

    public function __construct(?WordDiscoveryService $discoveryService = null)
    {
        $this->discoveryService = $discoveryService ?? new WordDiscoveryService();
    }

    /**
     * Save the reading position of the text.
     *
     * @param int $textid   Text ID
     * @param int $position Position in text to save
     *
     * @return void
     */
    public function saveTextPosition(int $textid, int $position): void
    {
        QueryBuilder::table('texts')
            ->where('TxID', '=', $textid)
            ->updatePrepared(['TxPosition' => $position]);
    }

    /**
     * Save the audio position in the text.
     *
     * @param int $textid        Text ID
     * @param int $audioposition Audio position
     *
     * @return void
     */
    public function saveAudioPosition(int $textid, int $audioposition): void
    {
        QueryBuilder::table('texts')
            ->where('TxID', '=', $textid)
            ->updatePrepared(['TxAudioPosition' => $audioposition]);
    }

    /**
     * Format response for setting text position.
     *
     * @param int $textId   Text ID
     * @param int $position Position
     *
     * @return array{text: string}
     */
    public function formatSetTextPosition(int $textId, int $position): array
    {
        $this->saveTextPosition($textId, $position);
        return ["text" => "Reading position set"];
    }

    /**
     * Format response for setting audio position.
     *
     * @param int $textId   Text ID
     * @param int $position Audio position
     *
     * @return array{audio: string}
     */
    public function formatSetAudioPosition(int $textId, int $position): array
    {
        $this->saveAudioPosition($textId, $position);
        return ["audio" => "Audio position set"];
    }

    /**
     * Set display mode settings for a text.
     *
     * @param int       $textId       Text ID
     * @param int|null  $annotations  Annotation mode (0=none, 1=translations, 2=romanization, 3=both)
     * @param bool|null $romanization Whether to show romanization
     * @param bool|null $translation  Whether to show translation
     *
     * @return array{updated: bool, error?: string}
     */
    public function setDisplayMode(int $textId, ?int $annotations, ?bool $romanization, ?bool $translation): array
    {
        $exists = QueryBuilder::table('texts')
            ->where('TxID', '=', $textId)
            ->existsPrepared();

        if (!$exists) {
            return ['updated' => false, 'error' => 'Text not found'];
        }

        if ($annotations !== null) {
            Settings::save('set-text-h-annotations', (string)$annotations);
        }

        if ($romanization !== null) {
            Settings::save('set-display-romanization', $romanization ? '1' : '0');
        }

        if ($translation !== null) {
            Settings::save('set-display-translation', $translation ? '1' : '0');
        }

        return ['updated' => true];
    }

    /**
     * Format response for setting display mode.
     *
     * @param int   $textId Text ID
     * @param array $params Display mode parameters
     *
     * @return array{updated: bool, error?: string}
     */
    public function formatSetDisplayMode(int $textId, array $params): array
    {
        $annotations = isset($params['annotations']) ? (int)$params['annotations'] : null;
        $romanization = isset($params['romanization'])
            ? filter_var($params['romanization'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $translation = isset($params['translation'])
            ? filter_var($params['translation'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        return $this->setDisplayMode($textId, $annotations, $romanization, $translation);
    }

    /**
     * Mark all unknown words in a text as well-known.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function markAllWellKnown(int $textId): array
    {
        list($count, $wordsData) = $this->discoveryService->markAllWordsWithStatus($textId, 99);
        return [
            'count' => $count,
            'words' => $wordsData
        ];
    }

    /**
     * Mark all unknown words in a text as ignored.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function markAllIgnored(int $textId): array
    {
        list($count, $wordsData) = $this->discoveryService->markAllWordsWithStatus($textId, 98);
        return [
            'count' => $count,
            'words' => $wordsData
        ];
    }

    /**
     * Format response for marking all words as well-known.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function formatMarkAllWellKnown(int $textId): array
    {
        return $this->markAllWellKnown($textId);
    }

    /**
     * Format response for marking all words as ignored.
     *
     * @param int $textId Text ID
     *
     * @return array{count: int, words?: array}
     */
    public function formatMarkAllIgnored(int $textId): array
    {
        return $this->markAllIgnored($textId);
    }
}
