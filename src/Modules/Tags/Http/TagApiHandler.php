<?php declare(strict_types=1);
/**
 * Tag API Handler
 *
 * Handles REST API endpoints for tag operations.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Tags\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Tags\Http;

use Lwt\Api\V1\Response;
use Lwt\Modules\Tags\Application\TagsFacade;

/**
 * API handler for tag endpoints.
 *
 * Handles:
 * - GET /api/v1/tags - Get all tags (both term and text)
 * - GET /api/v1/tags/term - Get term tags only
 * - GET /api/v1/tags/text - Get text tags only
 *
 * @since 3.0.0
 */
class TagApiHandler
{
    private TagsFacade $termFacade;
    private TagsFacade $textFacade;

    /**
     * Constructor.
     *
     * @param TagsFacade|null $termFacade Term tags facade
     * @param TagsFacade|null $textFacade Text tags facade
     */
    public function __construct(
        ?TagsFacade $termFacade = null,
        ?TagsFacade $textFacade = null
    ) {
        $this->termFacade = $termFacade ?? TagsFacade::forTermTags();
        $this->textFacade = $textFacade ?? TagsFacade::forTextTags();
    }

    /**
     * Handle GET request for tags.
     *
     * @param array $fragments URL path fragments after /tags
     *
     * @return void
     */
    public function handleGet(array $fragments): void
    {
        $type = $fragments[0] ?? '';

        switch ($type) {
            case 'term':
                Response::success(TagsFacade::getAllTermTags());
                break;
            case 'text':
                Response::success(TagsFacade::getAllTextTags());
                break;
            default:
                // Return both tag types
                Response::success([
                    'term' => TagsFacade::getAllTermTags(),
                    'text' => TagsFacade::getAllTextTags(),
                ]);
        }
    }

    /**
     * Handle request routing.
     *
     * @param string $method    HTTP method
     * @param array  $fragments URL fragments
     *
     * @return void
     */
    public function handle(string $method, array $fragments): void
    {
        switch (strtoupper($method)) {
            case 'GET':
                $this->handleGet($fragments);
                break;
            default:
                Response::error('Method not allowed', 405);
        }
    }
}
