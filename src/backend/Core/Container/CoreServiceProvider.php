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
use Lwt\Services\BackupService;
use Lwt\Services\DemoService;
use Lwt\Services\ExportService;
use Lwt\Modules\Vocabulary\Application\Services\ExpressionService;
use Lwt\Services\HomeService;
use Lwt\Services\LanguageService;
use Lwt\Services\PasswordService;
use Lwt\Services\SentenceService;
use Lwt\Services\ServerDataService;
use Lwt\Services\SettingsService;
use Lwt\Services\StatisticsService;
use Lwt\Services\TestService;
use Lwt\Services\TextDisplayService;
use Lwt\Services\TextParsingService;
use Lwt\Services\TextPrintService;
use Lwt\Services\ThemeService;
use Lwt\Services\TranslationService;
use Lwt\Services\TtsService;
use Lwt\Modules\Vocabulary\Application\Services\WordListService;
use Lwt\Services\WordPressService;
use Lwt\Services\WordService;
use Lwt\Services\WordUploadService;
use Lwt\Core\Parser\ParserRegistry;
use Lwt\Modules\Text\Infrastructure\MySqlTextRepository;
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;
use Lwt\Core\Parser\ParsingCoordinator;

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

        // =====================
        // Base services (no dependencies)
        // =====================

        $container->singleton(TextParsingService::class, function (Container $_c) {
            return new TextParsingService();
        });

        $container->singleton(ParserRegistry::class, function (Container $_c) {
            return new ParserRegistry();
        });

        $container->singleton(ParsingCoordinator::class, function (Container $c) {
            return new ParsingCoordinator(
                $c->get(ParserRegistry::class)
            );
        });

        $container->singleton(LanguageService::class, function (Container $_c) {
            return new LanguageService();
        });

        $container->singleton(AuthService::class, function (Container $c) {
            return new AuthService(
                $c->get(PasswordService::class),
                $c->get(MySqlUserRepository::class)
            );
        });

        $container->singleton(HomeService::class, function (Container $_c) {
            return new HomeService();
        });

        $container->singleton(TestService::class, function (Container $_c) {
            return new TestService();
        });

        $container->singleton(TextPrintService::class, function (Container $_c) {
            return new TextPrintService();
        });

        $container->singleton(TranslationService::class, function (Container $_c) {
            return new TranslationService();
        });

        $container->singleton(WordPressService::class, function (Container $_c) {
            return new WordPressService();
        });

        $container->singleton(BackupService::class, function (Container $_c) {
            return new BackupService();
        });

        $container->singleton(StatisticsService::class, function (Container $_c) {
            return new StatisticsService();
        });

        $container->singleton(SettingsService::class, function (Container $_c) {
            return new SettingsService();
        });

        $container->singleton(DemoService::class, function (Container $_c) {
            return new DemoService();
        });

        $container->singleton(ServerDataService::class, function (Container $_c) {
            return new ServerDataService();
        });

        $container->singleton(ThemeService::class, function (Container $_c) {
            return new ThemeService();
        });

        $container->singleton(PasswordService::class, function (Container $_c) {
            return new PasswordService();
        });

        $container->singleton(TextDisplayService::class, function (Container $_c) {
            return new TextDisplayService();
        });

        $container->singleton(WordListService::class, function (Container $_c) {
            return new WordListService();
        });

        $container->singleton(WordUploadService::class, function (Container $_c) {
            return new WordUploadService();
        });

        $container->singleton(ExportService::class, function (Container $_c) {
            return new ExportService();
        });

        // =====================
        // Services with dependencies
        // =====================

        $container->singleton(SentenceService::class, function (Container $c) {
            return new SentenceService(
                $c->get(TextParsingService::class)
            );
        });

        $container->singleton(ExpressionService::class, function (Container $c) {
            return new ExpressionService(
                $c->get(TextParsingService::class)
            );
        });

        $container->singleton(TtsService::class, function (Container $c) {
            return new TtsService(
                $c->get(LanguageService::class)
            );
        });

        // Note: TextFacade is registered by TextServiceProvider in the Text module

        $container->singleton(WordService::class, function (Container $c) {
            return new WordService(
                $c->get(ExpressionService::class),
                $c->get(SentenceService::class),
                $c->get(MySqlTermRepository::class)
            );
        });
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

// Note: Services not registered here will be auto-wired on demand by the container
// if they have constructors with type-hinted dependencies that are also registered.
