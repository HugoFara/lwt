<?php

declare(strict_types=1);

/**
 * Get All Tag Names Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Tags\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Tags\Application\UseCases;

use Lwt\Modules\Tags\Domain\TagRepositoryInterface;
use Lwt\Modules\Tags\Domain\TagType;

/**
 * Use case for retrieving all tag names (for caching and autocomplete).
 *
 * @since 3.0.0
 */
class GetAllTagNames
{
    private TagRepositoryInterface $termRepository;
    private TagRepositoryInterface $textRepository;

    /**
     * Constructor.
     *
     * @param TagRepositoryInterface $termRepository Term tag repository
     * @param TagRepositoryInterface $textRepository Text tag repository
     */
    public function __construct(
        TagRepositoryInterface $termRepository,
        TagRepositoryInterface $textRepository
    ) {
        $this->termRepository = $termRepository;
        $this->textRepository = $textRepository;
    }

    /**
     * Get all term tag names.
     *
     * @param bool $refresh Force refresh from database
     *
     * @return string[]
     */
    public function getTermTags(bool $refresh = false): array
    {
        $cacheKey = 'TAGS';
        $urlBase = $this->getUrlBase();

        if (!$refresh && isset($_SESSION[$cacheKey]) && isset($_SESSION['TAGS_URL_BASE'])) {
            if ($_SESSION['TAGS_URL_BASE'] === $urlBase) {
                /** @var mixed $cached */
                $cached = $_SESSION[$cacheKey];
                if (is_array($cached)) {
                    /** @var string[] $validCache */
                    $validCache = $cached;
                    return $validCache;
                }
            }
        }

        $tags = $this->termRepository->getAllTexts();
        $_SESSION[$cacheKey] = $tags;
        $_SESSION['TAGS_URL_BASE'] = $urlBase;

        return $tags;
    }

    /**
     * Get all text tag names.
     *
     * @param bool $refresh Force refresh from database
     *
     * @return string[]
     */
    public function getTextTags(bool $refresh = false): array
    {
        $cacheKey = 'TEXTTAGS';
        $urlBase = $this->getUrlBase();

        if (!$refresh && isset($_SESSION[$cacheKey]) && isset($_SESSION['TEXTTAGS_URL_BASE'])) {
            if ($_SESSION['TEXTTAGS_URL_BASE'] === $urlBase) {
                /** @var mixed $cached */
                $cached = $_SESSION[$cacheKey];
                if (is_array($cached)) {
                    /** @var string[] $validCache */
                    $validCache = $cached;
                    return $validCache;
                }
            }
        }

        $tags = $this->textRepository->getAllTexts();
        $_SESSION[$cacheKey] = $tags;
        $_SESSION['TEXTTAGS_URL_BASE'] = $urlBase;

        return $tags;
    }

    /**
     * Refresh term tag cache.
     *
     * @return string[]
     */
    public function refreshTermTags(): array
    {
        return $this->getTermTags(true);
    }

    /**
     * Refresh text tag cache.
     *
     * @return string[]
     */
    public function refreshTextTags(): array
    {
        return $this->getTextTags(true);
    }

    /**
     * Get URL base for cache invalidation.
     *
     * @return string
     */
    private function getUrlBase(): string
    {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        return substr($url, 0, (int) strrpos($url, '/'));
    }
}
