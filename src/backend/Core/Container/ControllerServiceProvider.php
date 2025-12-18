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

        $container->bind(AdminController::class, function (Container $_c) {
            return new AdminController();
        });

        $container->bind(ApiController::class, function (Container $_c) {
            return new ApiController();
        });

        $container->bind(AuthController::class, function (Container $_c) {
            return new AuthController();
        });

        $container->bind(FeedsController::class, function (Container $_c) {
            return new FeedsController();
        });

        $container->bind(HomeController::class, function (Container $_c) {
            return new HomeController();
        });

        $container->bind(LanguageController::class, function (Container $_c) {
            return new LanguageController();
        });

        $container->bind(TagsController::class, function (Container $_c) {
            return new TagsController();
        });

        $container->bind(TestController::class, function (Container $_c) {
            return new TestController();
        });

        $container->bind(TextController::class, function (Container $_c) {
            return new TextController();
        });

        $container->bind(TextPrintController::class, function (Container $_c) {
            return new TextPrintController();
        });

        $container->bind(TranslationController::class, function (Container $_c) {
            return new TranslationController();
        });

        $container->bind(WordController::class, function (Container $_c) {
            return new WordController();
        });

        $container->bind(WordPressController::class, function (Container $_c) {
            return new WordPressController();
        });

        // Note: Controllers without explicit registration will be auto-wired
        // by the container since they have no-argument constructors
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for controllers
    }
}
