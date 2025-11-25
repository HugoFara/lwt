<?php

namespace Lwt\Router;

/**
 * Simple Router for LWT Front Controller
 *
 * Handles routing from old URLs to new controller-based structure
 * while maintaining backward compatibility
 */
class Router
{
    private array $routes = [];
    private array $legacyMap = [];
    private array $prefixRoutes = [];

    /**
     * Register a route
     *
     * @param string $path The URL path
     * @param string $handler The handler (file path or controller@method)
     * @param string $method HTTP method (GET, POST, or *)
     */
    public function register(string $path, string $handler, string $method = '*'): void
    {
        $this->routes[$path][$method] = $handler;
    }

    /**
     * Register a prefix route (matches all paths starting with the prefix)
     *
     * @param string $prefix The URL prefix (e.g., '/api/v1')
     * @param string $handler The handler (file path or controller@method)
     * @param string $method HTTP method (GET, POST, or *)
     */
    public function registerPrefix(string $prefix, string $handler, string $method = '*'): void
    {
        $this->prefixRoutes[$prefix][$method] = $handler;
    }

    /**
     * Register a legacy file mapping for backward compatibility
     *
     * @param string $legacyFile Old filename (e.g., 'do_text.php')
     * @param string $newPath New route path (e.g., '/text/read')
     */
    public function registerLegacy(string $legacyFile, string $newPath): void
    {
        $this->legacyMap[$legacyFile] = $newPath;
    }

    /**
     * Resolve the current request to a handler
     *
     * @return array [handler, params] or [null, []] if not found
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

        // Check for legacy file access (e.g., /do_text.php or /api.php/v1/endpoint)
        // First try basename for simple cases like /do_text.php
        $filename = basename($path);
        if (str_ends_with($filename, '.php') && isset($this->legacyMap[$filename])) {
            // Legacy file accessed - redirect to new route
            $newPath = $this->legacyMap[$filename];
            $queryString = $_SERVER['QUERY_STRING'] ?? '';
            $redirectUrl = $newPath . ($queryString ? '?' . $queryString : '');

            return [
                'type' => 'redirect',
                'url' => $redirectUrl,
                'code' => 301  // Permanent redirect
            ];
        }

        // Handle legacy paths with path info after .php (e.g., /api.php/v1/version)
        if (preg_match('/^\/([^\/]+\.php)(\/.*)?$/', $path, $matches)) {
            $phpFile = $matches[1];
            $pathInfo = $matches[2] ?? '';

            if (isset($this->legacyMap[$phpFile])) {
                $newPath = $this->legacyMap[$phpFile];
                $queryString = $_SERVER['QUERY_STRING'] ?? '';

                // For API routes, the path info contains the version prefix we need to strip
                // e.g., /api.php/v1/version -> /api/v1/version (not /api/v1/v1/version)
                // The pathInfo is /v1/version, but newPath is already /api/v1
                // So we need to remove the /v1 prefix from pathInfo if it matches newPath's suffix
                if (!empty($pathInfo) && str_ends_with($newPath, '/v1')) {
                    // Strip /v1 or /vX prefix from pathInfo if present
                    $pathInfo = preg_replace('/^\/v\d+/', '', $pathInfo);
                }

                $redirectUrl = $newPath . $pathInfo . ($queryString ? '?' . $queryString : '');

                return [
                    'type' => 'redirect',
                    'url' => $redirectUrl,
                    'code' => 301  // Permanent redirect
                ];
            }
        }

        // Try exact match first
        if (isset($this->routes[$path])) {
            $methodRoutes = $this->routes[$path];

            // Check specific method first, then wildcard
            if (isset($methodRoutes[$requestMethod])) {
                return [
                    'type' => 'handler',
                    'handler' => $methodRoutes[$requestMethod],
                    'params' => $_GET
                ];
            } elseif (isset($methodRoutes['*'])) {
                return [
                    'type' => 'handler',
                    'handler' => $methodRoutes['*'],
                    'params' => $_GET
                ];
            }
        }

        // Try pattern matching for dynamic routes (e.g., /text/{id})
        foreach ($this->routes as $pattern => $methods) {
            $regex = $this->convertPatternToRegex($pattern);
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches); // Remove full match

                $methodRoutes = $methods;
                $handler = $methodRoutes[$requestMethod] ?? $methodRoutes['*'] ?? null;

                if ($handler) {
                    return [
                        'type' => 'handler',
                        'handler' => $handler,
                        'params' => array_merge($_GET, $matches)
                    ];
                }
            }
        }

        // Try prefix matching (for API routes that handle multiple sub-paths)
        foreach ($this->prefixRoutes as $prefix => $methods) {
            if (str_starts_with($path, $prefix)) {
                $handler = $methods[$requestMethod] ?? $methods['*'] ?? null;
                if ($handler) {
                    return [
                        'type' => 'handler',
                        'handler' => $handler,
                        'params' => $_GET
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
     * Convert route pattern to regex
     *
     * @param string $pattern Route pattern (e.g., '/text/{id}')
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
     * @return void
     */
    public function execute(array $resolution): void
    {
        switch ($resolution['type']) {
            case 'redirect':
                header("Location: {$resolution['url']}", true, $resolution['code']);
                exit;

            case 'handler':
                $this->executeHandler($resolution['handler'], $resolution['params']);
                break;

            case 'not_found':
                $this->handle404($resolution['path']);
                break;

            default:
                $this->handle500("Unknown resolution type: {$resolution['type']}");
        }
    }

    /**
     * Execute a handler (file include or controller method)
     *
     * @param string $handler Handler string
     * @param array $params Request parameters
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
     * @param string $method Method name
     * @param array $params Parameters
     * @return void
     */
    private function executeController(string $controllerClass, string $method, array $params): void
    {
        // Add namespace if not present
        if (!str_contains($controllerClass, '\\')) {
            $controllerClass = "Lwt\\Controllers\\{$controllerClass}";
        }

        if (!class_exists($controllerClass)) {
            $this->handle500("Controller not found: {$controllerClass}");
            return;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            $this->handle500("Method not found: {$controllerClass}::{$method}");
            return;
        }

        // Call the controller method
        call_user_func([$controller, $method], $params);
    }

    /**
     * Execute a legacy file
     *
     * @param string $filePath Path to PHP file or static file
     * @param array $params Parameters (made available to included file)
     * @return void
     */
    private function executeFile(string $filePath, array $params): void
    {
        if (!file_exists($filePath)) {
            $this->handle500("File not found: {$filePath}");
            return;
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
     * @return void
     */
    private function handle404(string $path): void
    {
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>404 - Page Not Found</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; }
                .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; }
                h1 { color: #721c24; }
                a { color: #004085; }
            </style>
        </head>
        <body>
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
     * @return void
     */
    private function handle500(string $message): void
    {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>500 - Internal Server Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 50px; }
                .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; }
                h1 { color: #721c24; }
                .message { font-family: monospace; background: #fff; padding: 10px; }
            </style>
        </head>
        <body>
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
