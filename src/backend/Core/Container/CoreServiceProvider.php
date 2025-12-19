<?php declare(strict_types=1);
/**
 * Core Service Provider
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Container
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Container;

use Lwt\Services\AuthService;
use Lwt\Services\FeedService;
use Lwt\Services\HomeService;
use Lwt\Services\LanguageService;
use Lwt\Services\TestService;
use Lwt\Services\TextPrintService;
use Lwt\Services\TextService;
use Lwt\Services\TranslationService;
use Lwt\Services\WordPressService;
use Lwt\Services\WordService;

/**
 * Core service provider that registers essential application services.
 *
 * Services are registered as singletons to avoid creating multiple
 * instances during a single request.
 *
 * @since 3.0.0
 */
class CoreServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register core services as singletons
        // These are the most commonly used services throughout the application

        $container->singleton(AuthService::class, function (Container $_c) {
            return new AuthService();
        });

        $container->singleton(FeedService::class, function (Container $_c) {
            return new FeedService();
        });

        $container->singleton(HomeService::class, function (Container $_c) {
            return new HomeService();
        });

        $container->singleton(LanguageService::class, function (Container $_c) {
            return new LanguageService();
        });

        $container->singleton(TestService::class, function (Container $_c) {
            return new TestService();
        });

        $container->singleton(TextPrintService::class, function (Container $_c) {
            return new TextPrintService();
        });

        $container->singleton(TextService::class, function (Container $_c) {
            return new TextService();
        });

        $container->singleton(TranslationService::class, function (Container $_c) {
            return new TranslationService();
        });

        $container->singleton(WordPressService::class, function (Container $_c) {
            return new WordPressService();
        });

        $container->singleton(WordService::class, function (Container $_c) {
            return new WordService();
        });

        // Note: Other services will be auto-wired on demand by the container
        // since they have no-argument constructors
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for services
        // Services are lazily instantiated when first requested
    }
}
