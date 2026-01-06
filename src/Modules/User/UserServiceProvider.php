<?php declare(strict_types=1);
/**
 * User Module Service Provider
 *
 * Registers all services for the User module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\User;

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;

// Domain
use Lwt\Modules\User\Domain\UserRepositoryInterface;

// Infrastructure
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;

// Application
use Lwt\Modules\User\Application\UserFacade;
use Lwt\Modules\User\Application\Services\PasswordHasher;

// Http
use Lwt\Modules\User\Http\UserController;
use Lwt\Modules\User\Http\UserApiHandler;
use Lwt\Modules\User\Http\WordPressController;

// WordPress integration
use Lwt\Modules\User\Application\Services\WordPressAuthService;

// Application Services
use Lwt\Modules\User\Application\Services\PasswordService;
use Lwt\Modules\User\Application\Services\AuthService;

/**
 * Service provider for the User module.
 *
 * Registers repositories, services, facade, controller,
 * and API handler for the User module.
 *
 * @since 3.0.0
 */
class UserServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register Repository Interface bindings
        $this->registerRepositories($container);

        // Register Application Services
        $this->registerServices($container);

        // Register Facade
        $container->singleton(UserFacade::class, function (Container $c) {
            return new UserFacade(
                $c->get(UserRepositoryInterface::class),
                $c->get(PasswordHasher::class)
            );
        });

        // Register Controller
        $container->bind(UserController::class, function (Container $c) {
            return new UserController(
                $c->get(UserFacade::class)
            );
        });

        // Register API Handler
        $container->singleton(UserApiHandler::class, function (Container $c) {
            return new UserApiHandler(
                $c->get(UserFacade::class)
            );
        });

        // Register WordPress integration services
        $this->registerWordPressServices($container);

        // Register authentication services
        $this->registerAuthServices($container);
    }

    /**
     * Register repository bindings.
     *
     * @param Container $container The DI container
     *
     * @return void
     */
    private function registerRepositories(Container $container): void
    {
        // User Repository Interface -> MySQL implementation
        $container->singleton(UserRepositoryInterface::class, function (Container $_c) {
            return new MySqlUserRepository();
        });

        // Also register concrete class for direct injection
        $container->singleton(MySqlUserRepository::class, function (Container $c) {
            return $c->get(UserRepositoryInterface::class);
        });
    }

    /**
     * Register application services.
     *
     * @param Container $container The DI container
     *
     * @return void
     */
    private function registerServices(Container $container): void
    {
        // Password Service (shared)
        $container->singleton(PasswordService::class, function (Container $_c) {
            return new PasswordService();
        });

        // Password Hasher (wraps PasswordService for module use)
        $container->singleton(PasswordHasher::class, function (Container $c) {
            return new PasswordHasher($c->get(PasswordService::class));
        });
    }

    /**
     * Register WordPress integration services.
     *
     * @param Container $container The DI container
     *
     * @return void
     */
    private function registerWordPressServices(Container $container): void
    {
        // WordPress Auth Service
        $container->singleton(WordPressAuthService::class, function (Container $c) {
            return new WordPressAuthService(
                $c->get(UserFacade::class)
            );
        });

        // WordPress Controller
        $container->bind(WordPressController::class, function (Container $c) {
            return new WordPressController(
                $c->get(WordPressAuthService::class)
            );
        });
    }

    /**
     * Register authentication services.
     *
     * @param Container $container The DI container
     *
     * @return void
     */
    private function registerAuthServices(Container $container): void
    {
        // AuthService - uses UserRepository for database access
        $container->singleton(AuthService::class, function (Container $c) {
            return new AuthService(
                $c->get(PasswordService::class),
                $c->get(MySqlUserRepository::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for the User module
    }
}
