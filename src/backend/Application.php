<?php declare(strict_types=1);
/**
 * LWT Application Bootstrap
 *
 * This class encapsulates the front controller logic for LWT.
 * It handles routing, environment setup, and request execution.
 *
 * PHP version 8.1
 *
 * @category Core
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/src-backend-Application.html
 * @since    3.0.0
 */

namespace Lwt;

use Lwt\Core\Container\Container;
use Lwt\Core\Container\ControllerServiceProvider;
use Lwt\Core\Container\CoreServiceProvider;
use Lwt\Core\Container\RepositoryServiceProvider;
use Lwt\Core\Container\ServiceProviderInterface;
use Lwt\Modules\Text\TextServiceProvider;
use Lwt\Modules\Language\LanguageServiceProvider;
use Lwt\Core\Exception\ExceptionHandler;
use Lwt\Core\Http\InputValidator;
use Lwt\Router\Router;
use Lwt\Services\DatabaseWizardService;

/**
 * Main application class that bootstraps and runs LWT.
 *
 * @category Core
 * @package  Lwt
 * @author   LWT Project <lwt-project@hotmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/src-backend-Application.html
 * @since    3.0.0
 */
class Application
{
    /**
     * Base path for the application.
     *
     * @var string
     */
    private string $basePath;

    /**
     * The dependency injection container.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Service providers to register.
     *
     * @var ServiceProviderInterface[]
     */
    private array $providers = [];

    /**
     * Create a new Application instance.
     *
     * @param string $basePath The base path of the application
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->container = new Container();

        // Set the global container instance for static access
        Container::setInstance($this->container);
    }

    /**
     * Bootstrap the application.
     *
     * Sets up error reporting, include paths, autoloading, and DI container.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Determine if we're in debug mode (development)
        $debug = $this->isDebugMode();

        // Error reporting based on debug mode
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
        }

        // Register global exception handler
        $this->registerExceptionHandler($debug);

        // Set include path so legacy files can use their original relative paths
        // This allows 'Core/session_utility.php' to work from any location
        // We add src/backend so that 'Core/...' resolves to 'src/backend/Core/...'
        set_include_path(
            get_include_path() . PATH_SEPARATOR .
            $this->basePath . PATH_SEPARATOR .
            $this->basePath . '/src/backend'
        );

        // Change to base directory so relative paths work correctly
        chdir($this->basePath);

        // Register autoloader for Lwt namespace
        $this->registerAutoloader();

        // Register service providers with the DI container
        $this->registerServiceProviders();
    }

    /**
     * Register all service providers with the container.
     *
     * @return void
     */
    private function registerServiceProviders(): void
    {
        // Core service providers
        $this->providers = [
            new CoreServiceProvider(),
            new ControllerServiceProvider(),
            new RepositoryServiceProvider(),
            // Module service providers
            new TextServiceProvider(),
            new LanguageServiceProvider(),
        ];

        // Register phase: all providers register their bindings
        foreach ($this->providers as $provider) {
            $provider->register($this->container);
        }

        // Boot phase: providers can perform additional setup
        foreach ($this->providers as $provider) {
            $provider->boot($this->container);
        }
    }

    /**
     * Get the DI container instance.
     *
     * @return Container
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Register the PSR-4 autoloader for Lwt namespace.
     *
     * @return void
     */
    private function registerAutoloader(): void
    {
        $basePath = $this->basePath;

        spl_autoload_register(function ($class) use ($basePath) {
            // Convert namespace to file path
            // Lwt\Router\Router -> src/backend/Router/Router.php
            $prefix = 'Lwt\\';
            $baseDir = $basePath . '/src/backend/';

            // Check if class uses our namespace
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            // Get relative class name
            $relativeClass = substr($class, $len);

            // Convert to file path
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            // Include if exists
            if (file_exists($file)) {
                require $file;
            }
        });
    }

    /**
     * Check if the application is running in debug mode.
     *
     * Debug mode is enabled when:
     * - APP_DEBUG environment variable is set to 'true' or '1'
     * - Or APP_ENV is 'development' or 'local'
     *
     * @return bool
     */
    private function isDebugMode(): bool
    {
        // Check APP_DEBUG env variable
        $appDebug = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? null);
        if ($appDebug !== null && $appDebug !== false) {
            return in_array(strtolower((string)$appDebug), ['true', '1', 'yes'], true);
        }

        // Check APP_ENV
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production');
        return in_array(strtolower($appEnv), ['development', 'local', 'dev'], true);
    }

    /**
     * Register the global exception handler.
     *
     * @param bool $debug Whether to show detailed error information
     *
     * @return void
     */
    private function registerExceptionHandler(bool $debug): void
    {
        $logFile = $this->basePath . '/var/logs/error.log';

        $handler = ExceptionHandler::getInstance($debug, $logFile);
        $handler->register();

        // Store in container for access elsewhere
        $this->container->singleton(ExceptionHandler::class, fn() => $handler);
    }

    /**
     * Run the application.
     *
     * Handles the incoming request through the router.
     *
     * @psalm-suppress UndefinedFunction Function loaded via require_once
     *
     * @return void
     */
    public function run(): void
    {
        // Check for .env configuration
        if (!$this->hasEnvFile()) {
            $this->handleMissingEnv();
            return;
        }

        // Initialize router with base path and DI container
        $router = new Router($this->basePath, $this->container);

        // Load route configuration
        require_once $this->basePath . '/src/backend/Router/routes.php';
        \Lwt\Router\registerRoutes($router);

        // Resolve and execute the request
        $resolution = $router->resolve();
        $router->execute($resolution);
    }

    /**
     * Check if .env file exists.
     *
     * @return bool
     */
    private function hasEnvFile(): bool
    {
        return file_exists($this->basePath . '/.env');
    }

    /**
     * Handle missing .env file.
     *
     * Shows the database wizard if requested, otherwise shows an error page.
     *
     * @return void
     */
    private function handleMissingEnv(): void
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        // Check if accessing database wizard
        if (
            str_contains($requestUri, 'database_wizard') ||
            str_contains($requestUri, 'admin/wizard')
        ) {
            $this->runDatabaseWizard();
            return;
        }

        // Show error page
        $this->showNoEnvErrorPage();
    }

    /**
     * Run the database wizard without requiring database connection.
     *
     * @psalm-suppress UnusedVariable Variables used by included wizard.php view
     *
     * @return void
     */
    private function runDatabaseWizard(): void
    {
        require_once $this->basePath . '/src/backend/Services/DatabaseWizardService.php';
        $wizardService = new DatabaseWizardService();

        $conn = null;
        $errorMessage = null;

        $op = InputValidator::getString('op');
        if ($op != '') {
            if ($op == "Autocomplete") {
                $conn = $wizardService->autocompleteConnection();
            } elseif ($op == "Check") {
                $formData = $this->getWizardFormData();
                $conn = $wizardService->createConnectionFromForm($formData);
                $errorMessage = $wizardService->testConnection($conn);
            } elseif ($op == "Change") {
                $formData = $this->getWizardFormData();
                $conn = $wizardService->createConnectionFromForm($formData);
                $wizardService->saveConnection($conn);
                header("Location: /");
                exit;
            }
        } elseif ($wizardService->envFileExists()) {
            $conn = $wizardService->loadConnection();
        } else {
            $conn = $wizardService->createEmptyConnection();
        }

        include $this->basePath . '/src/backend/Views/Admin/wizard.php';
    }

    /**
     * Display error page when .env is missing.
     *
     * @return void
     */
    private function showNoEnvErrorPage(): void
    {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>LWT - Configuration Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
                .container { max-width: 800px; margin: 50px auto; background: white; padding: 30px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .error { color: #d32f2f; }
                h1 { color: #d32f2f; border-bottom: 2px solid #d32f2f; padding-bottom: 10px; }
                ul { line-height: 1.8; }
                a { color: #1976d2; text-decoration: none; }
                a:hover { text-decoration: underline; }
                .btn { display: inline-block; padding: 10px 20px; background: #1976d2; color: white; border-radius: 3px; margin: 10px 5px; }
                .btn:hover { background: #1565c0; text-decoration: none; }
                code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Configuration Required</h1>
                <p class="error">
                    <strong>Cannot find file: ".env"</strong>
                </p>
                <p>Please do one of the following:</p>
                <ul>
                    <li>
                        Copy <code>.env.example</code> to <code>.env</code> and update the database credentials<br>
                        <small>(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME)</small>
                    </li>
                    <li>
                        <a href="/admin/wizard" class="btn">Use the Database Setup Wizard</a>
                    </li>
                </ul>
                <p>
                    <strong>Documentation:</strong>
                    <a href="https://hugofara.github.io/lwt/README.md" target="_blank">
                        https://hugofara.github.io/lwt/README.md
                    </a>
                </p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Get database wizard form data from request parameters.
     *
     * @return array<string, string> Form data array
     */
    private function getWizardFormData(): array
    {
        return [
            'db_hostname' => InputValidator::getString('db_hostname'),
            'db_socket' => InputValidator::getString('db_socket'),
            'db_user' => InputValidator::getString('db_user'),
            'db_password' => InputValidator::getString('db_password'),
            'db_name' => InputValidator::getString('db_name'),
            'db_prefix' => InputValidator::getString('db_prefix'),
        ];
    }
}
