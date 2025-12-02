<?php declare(strict_types=1);
namespace Lwt\Api\V1;

/**
 * Registry of API V1 endpoints.
 *
 * Extracted from api_v1.php lines 999-1070.
 */
class Endpoints
{
    /**
     * @var array<string, string[]> Map of endpoint patterns to allowed HTTP methods
     */
    private const ROUTES = [
        'languages' => ['GET'],
        'languages/with-texts' => ['GET'],
        'languages/with-archived-texts' => ['GET'],
        'media-files' => ['GET'],
        'phonetic-reading' => ['GET'],
        'review/next-word' => ['GET'],
        'review/tomorrow-count' => ['GET'],
        'review/status' => ['PUT'],
        'review/config' => ['GET'],
        'review/table-words' => ['GET'],
        'sentences-with-term' => ['GET'],
        'similar-terms' => ['GET'],
        'settings' => ['POST'],
        'settings/theme-path' => ['GET'],
        'statuses' => ['GET'],
        'tags' => ['GET'],
        'tags/term' => ['GET'],
        'tags/text' => ['GET'],
        'terms' => ['GET', 'POST', 'PUT', 'DELETE'],
        'terms/imported' => ['GET'],
        'terms/new' => ['POST'],
        'terms/quick' => ['POST'],
        'terms/full' => ['POST'],
        'terms/for-edit' => ['GET'],
        'terms/bulk-status' => ['PUT'],
        'terms/list' => ['GET'],
        'terms/filter-options' => ['GET'],
        'terms/bulk-action' => ['PUT'],
        'terms/all-action' => ['PUT'],
        'texts' => ['GET', 'POST', 'PUT'],
        'texts/statistics' => ['GET'],
        'texts/by-language' => ['GET'],
        'texts/archived-by-language' => ['GET'],
        'feeds' => ['POST'],
        'version' => ['GET'],
    ];

    /**
     * Check if an API endpoint exists and return it.
     *
     * @param string $method     HTTP method (e.g. 'GET' or 'POST')
     * @param string $requestUri The URI being requested
     *
     * @return string The matching endpoint path
     */
    public static function resolve(string $method, string $requestUri): string
    {
        $uriQuery = parse_url($requestUri, PHP_URL_PATH);

        // Support both legacy /api.php/v1/ and new /api/v1/ URL formats
        $matching = preg_match('/(.*?\/api(?:\.php)?\/v\d\/).+/', $uriQuery, $matches);
        if (!$matching) {
            Response::error('Unrecognized URL format ' . $uriQuery, 400);
        }
        if (count($matches) == 0) {
            Response::error('Wrong API Location: ' . $uriQuery, 404);
        }

        // endpoint without prepending URL, like 'version'
        $reqEndpoint = rtrim(str_replace($matches[1], '', $uriQuery), '/');

        $methodsAllowed = self::getMethodsForEndpoint($reqEndpoint);
        if ($methodsAllowed === null) {
            Response::error('Endpoint Not Found: ' . $reqEndpoint, 404);
        }

        // Validate request method for the endpoint
        if (!in_array($method, $methodsAllowed)) {
            Response::error('Method Not Allowed', 405);
        }

        return $reqEndpoint;
    }

    /**
     * Get allowed methods for an endpoint.
     *
     * @param string $endpoint Endpoint path
     *
     * @return string[]|null Allowed methods or null if not found
     */
    private static function getMethodsForEndpoint(string $endpoint): ?array
    {
        if (array_key_exists($endpoint, self::ROUTES)) {
            return self::ROUTES[$endpoint];
        }

        // Check first segment for dynamic endpoints (e.g., terms/123/status)
        $firstElem = preg_split('/\//', $endpoint)[0];
        if (array_key_exists($firstElem, self::ROUTES)) {
            return self::ROUTES[$firstElem];
        }

        return null;
    }

    /**
     * Parse endpoint into fragments.
     *
     * @param string $endpoint Endpoint path
     *
     * @return string[] Endpoint path segments
     */
    public static function parseFragments(string $endpoint): array
    {
        return preg_split("/\//", $endpoint);
    }
}
