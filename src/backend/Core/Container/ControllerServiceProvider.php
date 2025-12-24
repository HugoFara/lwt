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
use Lwt\Modules\Language\Http\LanguageController;
use Lwt\Controllers\TestController;
use Lwt\Modules\Text\Http\TextController;
use Lwt\Controllers\TextPrintController;
use Lwt\Controllers\TranslationController;
use Lwt\Controllers\WordController;
use Lwt\Controllers\WordPressController;
use Lwt\Services\AuthService;
use Lwt\Services\BackupService;
use Lwt\Services\DemoService;
use Lwt\Services\ExportService;
use Lwt\Modules\Vocabulary\Application\Services\ExpressionService;
use Lwt\Modules\Feed\Application\FeedFacade;
use Lwt\Services\HomeService;
use Lwt\Services\LanguageService;
use Lwt\Modules\Language\Application\LanguageFacade;
use Lwt\Services\PasswordService;
use Lwt\Services\ServerDataService;
use Lwt\Services\SettingsService;
use Lwt\Services\StatisticsService;
use Lwt\Services\TestService;
use Lwt\Services\TextDisplayService;
use Lwt\Services\TextPrintService;
use Lwt\Modules\Text\Application\TextFacade;
use Lwt\Services\ThemeService;
use Lwt\Services\TranslationService;
use Lwt\Services\TtsService;
use Lwt\Modules\Vocabulary\Application\Services\WordListService;
use Lwt\Services\WordPressService;
use Lwt\Services\WordService;
use Lwt\Services\WordUploadService;

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

        // Controllers with service dependencies
        $container->bind(ApiController::class, function (Container $c) {
            return new ApiController(
                $c->get(TranslationController::class)
            );
        });

        $container->bind(AuthController::class, function (Container $c) {
            return new AuthController(
                $c->get(AuthService::class),
                $c->get(PasswordService::class)
            );
        });

        $container->bind(LanguageController::class, function (Container $c) {
            return new LanguageController(
                $c->get(LanguageFacade::class)
            );
        });

        // Also bind the old namespace for backward compatibility
        $container->bind(\Lwt\Controllers\LanguageController::class, function (Container $c) {
            return $c->get(LanguageController::class);
        });

        $container->bind(TestController::class, function (Container $c) {
            return new TestController(
                $c->get(TestService::class),
                $c->get(LanguageService::class)
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
                $c->get(FeedFacade::class),
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
                $c->get(TextFacade::class),
                $c->get(LanguageService::class),
                $c->get(TextDisplayService::class)
            );
        });

        $container->bind(WordController::class, function (Container $c) {
            return new WordController(
                $c->get(WordService::class),
                $c->get(LanguageService::class),
                $c->get(WordListService::class),
                $c->get(WordUploadService::class),
                $c->get(ExportService::class),
                $c->get(TextFacade::class),
                $c->get(ExpressionService::class)
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
