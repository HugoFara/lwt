<?php

/**
 * Text API Handler
 *
 * Thin facade delegating to focused sub-handlers:
 * - TextPositionApiHandler: position, audio, display mode, bulk status
 * - TextAnnotationApiHandler: annotation CRUD, print items, edit term form
 * - TextTermApiHandler: words, translations, scoring, text listing
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

use Lwt\Modules\Vocabulary\Application\Services\WordDiscoveryService;
use Lwt\Shared\Http\ApiRoutableInterface;
use Lwt\Shared\Http\ApiRoutableTrait;
use Lwt\Shared\Infrastructure\Http\JsonResponse;
use Lwt\Api\V1\Response;

/**
 * Handler for text-related API operations.
 *
 * Delegates to TextPositionApiHandler, TextAnnotationApiHandler,
 * and TextTermApiHandler for actual logic.
 */
class TextApiHandler implements ApiRoutableInterface
{
    use ApiRoutableTrait;

    private TextPositionApiHandler $positionHandler;
    private TextAnnotationApiHandler $annotationHandler;
    private TextTermApiHandler $termHandler;

    public function __construct(?WordDiscoveryService $discoveryService = null)
    {
        $this->positionHandler = new TextPositionApiHandler($discoveryService);
        $this->annotationHandler = new TextAnnotationApiHandler();
        $this->termHandler = new TextTermApiHandler();
    }

    // =========================================================================
    // Position & Display Mode (delegates to TextPositionApiHandler)
    // =========================================================================

    public function saveTextPosition(int $textid, int $position): void
    {
        $this->positionHandler->saveTextPosition($textid, $position);
    }

    public function saveAudioPosition(int $textid, int $audioposition): void
    {
        $this->positionHandler->saveAudioPosition($textid, $audioposition);
    }

    public function formatSetTextPosition(int $textId, int $position): array
    {
        return $this->positionHandler->formatSetTextPosition($textId, $position);
    }

    public function formatSetAudioPosition(int $textId, int $position): array
    {
        return $this->positionHandler->formatSetAudioPosition($textId, $position);
    }

    public function setDisplayMode(int $textId, ?int $annotations, ?bool $romanization, ?bool $translation): array
    {
        return $this->positionHandler->setDisplayMode($textId, $annotations, $romanization, $translation);
    }

    public function formatSetDisplayMode(int $textId, array $params): array
    {
        return $this->positionHandler->formatSetDisplayMode($textId, $params);
    }

    public function markAllWellKnown(int $textId): array
    {
        return $this->positionHandler->markAllWellKnown($textId);
    }

    public function markAllIgnored(int $textId): array
    {
        return $this->positionHandler->markAllIgnored($textId);
    }

    public function formatMarkAllWellKnown(int $textId): array
    {
        return $this->positionHandler->formatMarkAllWellKnown($textId);
    }

    public function formatMarkAllIgnored(int $textId): array
    {
        return $this->positionHandler->formatMarkAllIgnored($textId);
    }

    // =========================================================================
    // Annotation & Print (delegates to TextAnnotationApiHandler)
    // =========================================================================

    public function saveImprTextData(int $textid, int $line, string $val): array
    {
        return $this->annotationHandler->saveImprTextData($textid, $line, $val);
    }

    public function saveImprText(int $textid, string $elem, object $data): array
    {
        return $this->annotationHandler->saveImprText($textid, $elem, $data);
    }

    public function formatSetAnnotation(int $textId, string $elem, string $data): array
    {
        return $this->annotationHandler->formatSetAnnotation($textId, $elem, $data);
    }

    public function getPrintItems(int $textId): array
    {
        return $this->annotationHandler->getPrintItems($textId);
    }

    public function formatGetPrintItems(int $textId): array
    {
        return $this->annotationHandler->formatGetPrintItems($textId);
    }

    public function getAnnotation(int $textId): array
    {
        return $this->annotationHandler->getAnnotation($textId);
    }

    public function formatGetAnnotation(int $textId): array
    {
        return $this->annotationHandler->formatGetAnnotation($textId);
    }

    public function makeTrans(int $i, ?int $wid, string $trans, string $word, int $lang): string
    {
        return $this->annotationHandler->makeTrans($i, $wid, $trans, $word, $lang);
    }

    public function editTermForm(int $textid): string
    {
        return $this->annotationHandler->editTermForm($textid);
    }

    public function formatEditTermForm(int $textId): array
    {
        return $this->annotationHandler->formatEditTermForm($textId);
    }

    // =========================================================================
    // Terms & Scoring (delegates to TextTermApiHandler)
    // =========================================================================

    public function getWords(int $textId): array
    {
        return $this->termHandler->getWords($textId);
    }

    public function formatGetWords(int $textId): array
    {
        return $this->termHandler->formatGetWords($textId);
    }

    public function formatTextsByLanguage(int $langId, array $params): array
    {
        return $this->termHandler->formatTextsByLanguage($langId, $params);
    }

    public function formatArchivedTextsByLanguage(int $langId, array $params): array
    {
        return $this->termHandler->formatArchivedTextsByLanguage($langId, $params);
    }

    public function getTranslations(int $wordId): array
    {
        return $this->termHandler->getTranslations($wordId);
    }

    public function getTermTranslations(string $wordlc, int $textid): array
    {
        return $this->termHandler->getTermTranslations($wordlc, $textid);
    }

    public function formatTermTranslations(string $termLc, int $textId): array
    {
        return $this->termHandler->formatTermTranslations($termLc, $textId);
    }

    public function formatGetTextScore(int $textId): array
    {
        return $this->termHandler->formatGetTextScore($textId);
    }

    public function formatGetTextScores(array $textIds): array
    {
        return $this->termHandler->formatGetTextScores($textIds);
    }

    public function formatGetRecommendedTexts(int $languageId, array $params): array
    {
        return $this->termHandler->formatGetRecommendedTexts($languageId, $params);
    }

    // =========================================================================
    // Routing Methods (ApiRoutableInterface)
    // =========================================================================

    public function routeGet(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'scoring') {
            if ($frag2 === 'recommended') {
                $langId = (int) ($params['language_id'] ?? 0);
                if ($langId <= 0) {
                    return Response::error('language_id is required', 400);
                }
                return Response::success($this->formatGetRecommendedTexts($langId, $params));
            }
            $textId = isset($params['text_id']) ? (int) $params['text_id'] : 0;
            $textIds = (string) ($params['text_ids'] ?? '');

            if ($textId > 0) {
                return Response::success($this->formatGetTextScore($textId));
            } elseif ($textIds !== '') {
                $ids = array_map('intval', explode(',', $textIds));
                $ids = array_filter($ids, fn($id) => $id > 0);
                if (empty($ids)) {
                    return Response::error('No valid text IDs provided', 400);
                }
                return Response::success($this->formatGetTextScores($ids));
            }
            return Response::error('text_id or text_ids parameter is required', 400);
        } elseif ($frag1 === 'by-language') {
            if ($frag2 === '' || !ctype_digit($frag2)) {
                return Response::error('Expected Language ID after "by-language"', 404);
            }
            return Response::success($this->formatTextsByLanguage((int) $frag2, $params));
        } elseif ($frag1 === 'archived-by-language') {
            if ($frag2 === '' || !ctype_digit($frag2)) {
                return Response::error('Expected Language ID after "archived-by-language"', 404);
            }
            return Response::success($this->formatArchivedTextsByLanguage((int) $frag2, $params));
        } elseif ($frag1 !== '' && ctype_digit($frag1)) {
            $textId = (int) $frag1;
            if ($frag2 === 'words') {
                return Response::success($this->formatGetWords($textId));
            } elseif ($frag2 === 'print-items') {
                return Response::success($this->formatGetPrintItems($textId));
            } elseif ($frag2 === 'annotation') {
                return Response::success($this->formatGetAnnotation($textId));
            }
            return Response::error('Expected "words", "print-items", or "annotation"', 404);
        }
        return Response::error('Expected "scoring", "by-language", "archived-by-language", or text ID', 404);
    }

    public function routePost(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === 'extract-url') {
            $url = (string) ($params['url'] ?? '');
            if ($url === '') {
                return Response::error('url parameter is required', 400);
            }
            $extractor = new \Lwt\Shared\Infrastructure\Http\WebPageExtractor();
            $result = $extractor->extractFromUrl($url);
            if (isset($result['error'])) {
                return Response::error($result['error'], 422);
            }
            return Response::success($result);
        }

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Text ID (Integer) Expected', 404);
        }

        $textId = (int) $frag1;

        switch ($frag2) {
            case 'annotation':
                return Response::success($this->formatSetAnnotation(
                    $textId,
                    (string) ($params['elem'] ?? ''),
                    (string) ($params['data'] ?? '')
                ));
            case 'audio-position':
                return Response::success($this->formatSetAudioPosition(
                    $textId,
                    (int) ($params['position'] ?? 0)
                ));
            case 'reading-position':
                return Response::success($this->formatSetTextPosition(
                    $textId,
                    (int) ($params['position'] ?? 0)
                ));
            default:
                return Response::error('Endpoint Not Found: ' . $frag2, 404);
        }
    }

    public function routePut(array $fragments, array $params): JsonResponse
    {
        $frag1 = $this->frag($fragments, 1);
        $frag2 = $this->frag($fragments, 2);

        if ($frag1 === '' || !ctype_digit($frag1)) {
            return Response::error('Text ID (Integer) Expected', 404);
        }

        $textId = (int) $frag1;

        switch ($frag2) {
            case 'display-mode':
                return Response::success($this->formatSetDisplayMode($textId, $params));
            case 'mark-all-wellknown':
                return Response::success($this->formatMarkAllWellKnown($textId));
            case 'mark-all-ignored':
                return Response::success($this->formatMarkAllIgnored($textId));
            default:
                return Response::error('Expected "display-mode", "mark-all-wellknown", or "mark-all-ignored"', 404);
        }
    }
}
