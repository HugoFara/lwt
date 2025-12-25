<?php declare(strict_types=1);
/**
 * Simple Router for LWT Front Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Router
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Router;

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Http\SecurityHeaders;
use Lwt\Router\Middleware\MiddlewareInterface;

/**
 * Simple Router for LWT Front Controller
 *
 * Handles routing URLs to controller-based handlers
 *
 * @category Lwt
 * @package  Lwt\Router
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */
class Router
{
    private array $routes = [];
    private array $prefixRoutes = [];

    /**
     * Middleware stack for routes.
     *
     * Structure: ['path' => ['method' => [middleware1, middleware2, ...]]]
     *
     * @var array<string, array<string, array<MiddlewareInterface|string>>>
     */
    private array $middleware = [];

    /**
     * Middleware stack for prefix routes.
     *
     * @var array<string, array<string, array<MiddlewareInterface|string>>>
     */
    private array $prefixMiddleware = [];

    /**
     * The dependency injection container.
     *
     * @var Container|null
     */
    private ?Container $container;

    /**
     * Base path for resolving file paths.
     *
     * @var string
     */
    private string $basePath;

    /**
     * Create a new Router instance.
     *
     * @param string         $basePath  Base path for resolving file paths
     * @param Container|null $container Optional DI container for resolving controllers
     */
    public function __construct(string $basePath, ?Container $container = null)
    {
        $this->basePath = $basePath;
        $this->container = $container;
    }

    /**
     * Register a route
     *
     * @param string $path    The URL path
     * @param string $handler The handler (file path or controller@method)
     * @param string $method  HTTP method (GET, POST, or *)
     *
     * @return void
     */
    public function register(string $path, string $handler, string $method = '*'): void
    {
        $this->routes[$path][$method] = $handler;
    }

    /**
     * Register a route with middleware.
     *
     * @param string $path       The URL path
     * @param string $handler    The handler (file path or controller@method)
     * @param array  $middleware Array of middleware class names or instances
     * @param string $method     HTTP method (GET, POST, or *)
     *
     * @return void
     */
    public function registerWithMiddleware(
        string $path,
        string $handler,
        array $middleware,
        string $method = '*'
    ): void {
        $this->routes[$path][$method] = $handler;
        $this->middleware[$path][$method] = $middleware;
    }

    /**
     * Register a prefix route (matches all paths starting with prefix)
     *
     * @param string $prefix  The URL prefix (e.g., '/api/v1')
     * @param string $handler The handler (file path or method)
     * @param string $method  HTTP method (GET, POST, or *)
     *
     * @return void
     */
    public function registerPrefix(
        string $prefix,
        string $handler,
        string $method = '*'
    ): void {
        $this->prefixRoutes[$prefix][$method] = $handler;
    }

    /**
     * Register a prefix route with middleware.
     *
     * @param string $prefix     The URL prefix (e.g., '/api/v1')
     * @param string $handler    The handler (file path or method)
     * @param array  $middleware Array of middleware class names or instances
     * @param string $method     HTTP method (GET, POST, or *)
     *
     * @return void
     *
     * @psalm-suppress PossiblyUnusedMethod - Public API for route registration
     */
    public function registerPrefixWithMiddleware(
        string $prefix,
        string $handler,
        array $middleware,
        string $method = '*'
    ): void {
        $this->prefixRoutes[$prefix][$method] = $handler;
        $this->prefixMiddleware[$prefix][$method] = $middleware;
    }

    /**
     * Resolve the current request to a handler
     *
     * @return (((array|string)[]|string)[]|int|mixed|string)[]
     *
     * @psalm-return array{type: 'handler'|'not_found'|'redirect'|'static', path?: string, url?: string, code?: 301, handler?: mixed, params?: array<array<int|string, array<int|string, mixed>|string>|string>, file?: string, mime?: string, middleware?: array}
     */
    public function resolve(): array
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Parse URL to extract path and query string
        $parsedUrl = parse_url($requestUri);
        $path = $parsedUrl['path'] ?? '/';

        // Remove leading/trailing slashes for consistency
        $path = '/' . trim($path, '/');

        // Check for static assets
        $staticResult = $this->resolveStaticAsset($path);
        if ($staticResult !== null) {
            return $staticResult;
        }

        // Handle /index.php/* paths - strip index.php and route the rest
        // e.g., /index.php/admin/install-demo -> /admin/install-demo
        if (preg_match('/^\/index\.php(\/.*)?$/', $path, $matches)) {
            $pathInfo = $matches[1] ?? '';
            if (!empty($pathInfo)) {
                // Has path info after index.php - redirect to that path
                $queryString = $_SERVER['QUERY_STRING'] ?? '';
                $redirectUrl = $pathInfo . ($queryString ? '?' . $queryString : '');

                return [
                    'type' => 'redirect',
                    'url' => $redirectUrl,
                    'code' => 301  // Permanent redirect
                ];
            }
            // No path info - /index.php alone will be handled by exact match below
        }

        // Try exact match first
        if (isset($this->routes[$path])) {
            $methodRoutes = $this->routes[$path];

            // Check specific method first, then wildcard
            if (isset($methodRoutes[$requestMethod])) {
                $middleware = $this->middleware[$path][$requestMethod] ?? [];
                return [
                    'type' => 'handler',
                    'handler' => $methodRoutes[$requestMethod],
                    'params' => $_GET,
                    'middleware' => $middleware,
                ];
            } elseif (isset($methodRoutes['*'])) {
                $middleware = $this->middleware[$path]['*'] ?? [];
                return [
                    'type' => 'handler',
                    'handler' => $methodRoutes['*'],
                    'params' => $_GET,
                    'middleware' => $middleware,
                ];
            }
        }

        // Try pattern matching for dynamic routes (e.g., /text/{id})
        foreach ($this->routes as $pattern => $methods) {
            $regex = $this->convertPatternToRegex($pattern);
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches); // Remove full match

                $methodRoutes = $methods;
                $matchedMethod = isset($methodRoutes[$requestMethod])
                    ? $requestMethod
                    : (isset($methodRoutes['*']) ? '*' : null);
                $handler = $matchedMethod !== null
                    ? $methodRoutes[$matchedMethod]
                    : null;

                if ($handler) {
                    $middleware = $this->middleware[$pattern][$matchedMethod] ?? [];
                    return [
                        'type' => 'handler',
                        'handler' => $handler,
                        'params' => array_merge($_GET, $matches),
                        'middleware' => $middleware,
                    ];
                }
            }
        }

        // Try prefix matching (for API routes that handle multiple sub-paths)
        foreach ($this->prefixRoutes as $prefix => $methods) {
            if (str_starts_with($path, $prefix)) {
                $matchedMethod = isset($methods[$requestMethod])
                    ? $requestMethod
                    : (isset($methods['*']) ? '*' : null);
                $handler = $matchedMethod !== null
                    ? $methods[$matchedMethod]
                    : null;

                if ($handler) {
                    $middleware = $this->prefixMiddleware[$prefix][$matchedMethod] ?? [];
                    return [
                        'type' => 'handler',
                        'handler' => $handler,
                        'params' => $_GET,
                        'middleware' => $middleware,
                    ];
                }
            }
        }

        // Not found
        return [
            'type' => 'not_found',
            'path' => $path
        ];
    }

    /**
     * Resolve static asset requests
     *
     * Maps legacy paths to new asset locations:
     * - /css/* -> /assets/css/*
     * - /icn/* -> /assets/icons/*
     * - /img/* -> /assets/images/*
     * - /js/* -> /assets/js/*
     * - /assets/* -> /assets/* (direct access)
     * - /docs/* -> /docs/* (documentation)
     * - /favicon.ico -> /favicon.ico
     *
     * @param string $path Request path
     *
     * @return array|null Resolution array or null if not a static asset
     */
    private function resolveStaticAsset(string $path): ?array
    {
        // Path mappings from legacy to new structure
        $mappings = [
            '/css/' => '/assets/css/',
            '/icn/' => '/assets/icons/',
            '/img/' => '/assets/images/',
            '/js/' => '/assets/js/',
            '/sounds/' => '/assets/sounds/',
        ];

        // Check if it's a legacy path that needs mapping
        foreach ($mappings as $oldPrefix => $newPrefix) {
            if (str_starts_with($path, $oldPrefix)) {
                $newPath = $newPrefix . substr($path, strlen($oldPrefix));
                $filePath = $this->basePath . $newPath;

                if (file_exists($filePath) && is_file($filePath)) {
                    return [
                        'type' => 'static',
                        'file' => $filePath,
                        'mime' => $this->getMimeType($filePath)
                    ];
                }
                // Return 404 for non-existent mapped paths
                return null;
            }
        }

        // Direct static asset paths
        $directPaths = ['/assets/', '/docs/', '/media/'];
        foreach ($directPaths as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $filePath = $this->basePath . $path;
                if (file_exists($filePath) && is_file($filePath)) {
                    return [
                        'type' => 'static',
                        'file' => $filePath,
                        'mime' => $this->getMimeType($filePath)
                    ];
                }
                // Return 404 for non-existent direct paths
                return null;
            }
        }

        // Special files at root level
        $rootFiles = ['/favicon.ico', '/UNLICENSE.md'];
        if (in_array($path, $rootFiles)) {
            $filePath = $this->basePath . $path;
            if (file_exists($filePath)) {
                return [
                    'type' => 'static',
                    'file' => $filePath,
                    'mime' => $this->getMimeType($filePath)
                ];
            }
        }

        return null;
    }

    /**
     * Get MIME type for a file
     *
     * @param string $filePath Path to file
     *
     * @return string MIME type
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'html' => 'text/html',
            'htm' => 'text/html',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'xml' => 'application/xml',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Convert route pattern to regex
     *
     * @param string $pattern Route pattern (e.g., '/text/{id}')
     *
     * @return string Regex pattern
     */
    private function convertPatternToRegex(string $pattern): string
    {
        // Escape slashes
        $pattern = str_replace('/', '\/', $pattern);

        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^\/]+)', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Execute the resolved handler
     *
     * @param array $resolution Result from resolve()
     *
     * @return void
     */
    public function execute(array $resolution): void
    {
        // Send security headers on all responses
        SecurityHeaders::send();

        switch ($resolution['type']) {
            case 'redirect':
                $code = $resolution['code'];
                header("Location: {$resolution['url']}", true, $code);
                exit;

            case 'static':
                $this->serveStaticFile(
                    $resolution['file'],
                    $resolution['mime']
                );
                break;

            case 'handler':
                // Execute middleware chain first
                $middleware = $resolution['middleware'] ?? [];
                if (!$this->executeMiddleware($middleware)) {
                    // Middleware halted the request
                    return;
                }

                $this->executeHandler(
                    $resolution['handler'],
                    $resolution['params']
                );
                break;

            case 'not_found':
                $this->handle404($resolution['path']);

            default:
                $this->handle500(
                    "Unknown resolution type: {$resolution['type']}"
                );
        }
    }

    /**
     * Execute the middleware chain.
     *
     * @param array $middlewareList List of middleware class names or instances
     *
     * @return bool True if all middleware passed, false if halted
     */
    private function executeMiddleware(array $middlewareList): bool
    {
        foreach ($middlewareList as $middleware) {
            $instance = $this->resolveMiddleware($middleware);
            if (!$instance->handle()) {
                // Middleware halted the request
                return false;
            }
        }
        return true;
    }

    /**
     * Resolve a middleware to an instance.
     *
     * @param MiddlewareInterface|string $middleware Middleware instance or class name
     *
     * @return MiddlewareInterface The resolved middleware instance
     *
     * @throws \RuntimeException If middleware class not found or invalid
     */
    private function resolveMiddleware(
        MiddlewareInterface|string $middleware
    ): MiddlewareInterface {
        // Already an instance
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        // It's a class name - try to resolve from container or instantiate directly
        if (!class_exists($middleware)) {
            throw new \RuntimeException("Middleware class not found: {$middleware}");
        }

        // Use DI container if available
        if ($this->container !== null && $this->container->has($middleware)) {
            $instance = $this->container->get($middleware);
        } else {
            $instance = new $middleware();
        }

        if (!$instance instanceof MiddlewareInterface) {
            throw new \RuntimeException(
                "Middleware must implement MiddlewareInterface: {$middleware}"
            );
        }

        return $instance;
    }

    /**
     * Serve a static file with proper headers
     *
     * @param string $filePath Full path to the file
     * @param string $mimeType MIME type of the file
     *
     * @return void
     */
    private function serveStaticFile(string $filePath, string $mimeType): void
    {
        // Set cache headers for static assets (1 week)
        $maxAge = 604800;
        header('Cache-Control: public, max-age=' . $maxAge);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));

        // Send file contents
        readfile($filePath);
        exit;
    }

    /**
     * Execute a handler (file include or controller method)
     *
     * @param string $handler Handler string
     * @param array  $params  Request parameters
     *
     * @return void
     */
    private function executeHandler(string $handler, array $params): void
    {
        // Check if it's a controller@method format
        if (str_contains($handler, '@')) {
            list($controller, $method) = explode('@', $handler, 2);
            $this->executeController($controller, $method, $params);
        } else {
            // It's a file path - include it
            $this->executeFile($handler, $params);
        }
    }

    /**
     * Execute a controller method
     *
     * @param string $controllerClass Controller class name
     * @param string $method          Method name
     * @param array  $params          Parameters
     *
     * @return void
     */
    private function executeController(
        string $controllerClass,
        string $method,
        array $params
    ): void {
        // Add namespace if not present
        if (!str_contains($controllerClass, '\\')) {
            $controllerClass = "Lwt\\Controllers\\{$controllerClass}";
        }

        if (!class_exists($controllerClass)) {
            $this->handle500("Controller not found: {$controllerClass}");
        }

        // Resolve controller from DI container if available, otherwise instantiate directly
        $controller = $this->resolveController($controllerClass);

        if (!method_exists($controller, $method)) {
            $this->handle500(
                "Method not found: {$controllerClass}::{$method}"
            );
        }

        // Call the controller method
        call_user_func([$controller, $method], $params);
    }

    /**
     * Resolve a controller instance from the container or create directly.
     *
     * @param string $controllerClass The fully qualified controller class name
     *
     * @return object The controller instance
     */
    private function resolveController(string $controllerClass): object
    {
        // Use DI container if available
        if ($this->container !== null) {
            return $this->container->get($controllerClass);
        }

        // Fallback to direct instantiation
        return new $controllerClass();
    }

    /**
     * Execute a legacy file
     *
     * @param string $filePath Path to PHP file or static file
     * @param array  $params   Parameters (available to file)
     *
     * @return void
     */
    private function executeFile(string $filePath, array $params): void
    {
        if (!file_exists($filePath)) {
            $this->handle500("File not found: {$filePath}");
        }

        // Handle static HTML files
        if (str_ends_with($filePath, '.html')) {
            header('Content-Type: text/html; charset=utf-8');
            readfile($filePath);
            return;
        }

        // Make params available to the included file
        extract($params, EXTR_SKIP);

        // Include the file
        include $filePath;
    }

    /**
     * Handle 404 Not Found
     *
     * @param string $path Requested path
     *
     * @return never
     */
    private function handle404(string $path)
    {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>404 - Page Not Found</title>
            <link rel="stylesheet" href="/assets/css/standalone.css" type="text/css"/>
        </head>
        <body class="error-page">
            <div class="error">
                <h1>404 - Page Not Found</h1>
                <p>The requested page <code><?php echo htmlspecialchars($path); ?></code> was not found.</p>
                <p><a href="/">Return to Home</a></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Handle 500 Internal Server Error
     *
     * @param string $message Error message
     *
     * @return never
     */
    private function handle500(string $message)
    {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>500 - Internal Server Error</title>
            <link rel="stylesheet" href="/assets/css/standalone.css" type="text/css"/>
        </head>
        <body class="error-page">
            <div class="error">
                <h1>500 - Internal Server Error</h1>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
