<?php declare(strict_types=1);
/**
 * Controller Service Provider
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

use Lwt\Controllers\AdminController;
use Lwt\Controllers\ApiController;
use Lwt\Controllers\AuthController;
use Lwt\Controllers\FeedsController;
use Lwt\Controllers\HomeController;
use Lwt\Controllers\LanguageController;
use Lwt\Controllers\TagsController;
use Lwt\Controllers\TestController;
use Lwt\Controllers\TextController;
use Lwt\Controllers\TextPrintController;
use Lwt\Controllers\TranslationController;
use Lwt\Controllers\WordController;
use Lwt\Controllers\WordPressController;
use Lwt\Services\AuthService;
use Lwt\Services\BackupService;
use Lwt\Services\DemoService;
use Lwt\Services\FeedService;
use Lwt\Services\HomeService;
use Lwt\Services\LanguageService;
use Lwt\Services\ServerDataService;
use Lwt\Services\SettingsService;
use Lwt\Services\StatisticsService;
use Lwt\Services\TestService;
use Lwt\Services\TextPrintService;
use Lwt\Services\TextService;
use Lwt\Services\ThemeService;
use Lwt\Services\TranslationService;
use Lwt\Services\TtsService;
use Lwt\Services\WordPressService;
use Lwt\Services\WordService;

/**
 * Controller service provider that registers all controllers.
 *
 * Controllers are NOT registered as singletons - a fresh instance
 * is created for each request to avoid state issues.
 *
 * @since 3.0.0
 */
class ControllerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Controllers are registered as factories (new instance each time)
        // This ensures clean state for each request
        // Dependencies are injected from the container

        // Controllers without service dependencies
        $container->bind(ApiController::class, function (Container $_c) {
            return new ApiController();
        });

        $container->bind(TagsController::class, function (Container $_c) {
            return new TagsController();
        });

        // Controllers with single service dependency
        $container->bind(AuthController::class, function (Container $c) {
            return new AuthController(
                $c->get(AuthService::class)
            );
        });

        $container->bind(LanguageController::class, function (Container $c) {
            return new LanguageController(
                $c->get(LanguageService::class)
            );
        });

        $container->bind(TestController::class, function (Container $c) {
            return new TestController(
                $c->get(TestService::class)
            );
        });

        $container->bind(TextPrintController::class, function (Container $c) {
            return new TextPrintController(
                $c->get(TextPrintService::class)
            );
        });

        $container->bind(TranslationController::class, function (Container $c) {
            return new TranslationController(
                $c->get(TranslationService::class)
            );
        });

        $container->bind(WordPressController::class, function (Container $c) {
            return new WordPressController(
                $c->get(WordPressService::class)
            );
        });

        // Controllers with multiple service dependencies
        $container->bind(FeedsController::class, function (Container $c) {
            return new FeedsController(
                $c->get(FeedService::class),
                $c->get(LanguageService::class)
            );
        });

        $container->bind(HomeController::class, function (Container $c) {
            return new HomeController(
                $c->get(HomeService::class),
                $c->get(LanguageService::class)
            );
        });

        $container->bind(TextController::class, function (Container $c) {
            return new TextController(
                $c->get(TextService::class),
                $c->get(LanguageService::class)
            );
        });

        $container->bind(WordController::class, function (Container $c) {
            return new WordController(
                $c->get(WordService::class),
                $c->get(LanguageService::class)
            );
        });

        $container->bind(AdminController::class, function (Container $c) {
            return new AdminController(
                $c->get(BackupService::class),
                $c->get(StatisticsService::class),
                $c->get(SettingsService::class),
                $c->get(TtsService::class),
                $c->get(WordService::class),
                $c->get(DemoService::class),
                $c->get(ServerDataService::class),
                $c->get(ThemeService::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for controllers
    }
}
