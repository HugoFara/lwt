<?php

/**
 * Core Service Provider
 *
 * Registers cross-cutting infrastructure services that are shared across modules.
 * Module-specific services are registered by their respective ServiceProviders.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Shared\Infrastructure\Container
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Shared\Infrastructure\Container;

use Lwt\Core\Bootstrap\DatabaseBootstrap;
use Lwt\Core\Parser\ParserRegistry;
use Lwt\Core\Parser\ParsingCoordinator;
use Lwt\Core\Parser\ExternalParserLoader;

/**
 * Core service provider that registers essential cross-cutting services.
 *
 * This provider only registers:
 * - Parser infrastructure (ParserRegistry, ParsingCoordinator)
 *
 * Module-specific services are registered by their respective ServiceProviders:
 * - TextParsingService → LanguageServiceProvider
 * - SentenceService → TextServiceProvider
 * - WordListService, WordUploadService, ExpressionService, ExportService → VocabularyServiceProvider
 * - AuthService, PasswordService → UserServiceProvider
 * - TtsService, BackupService, StatisticsService, etc. → AdminServiceProvider
 * - TranslationService → DictionaryServiceProvider
 * - TestService → ReviewServiceProvider
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
        // =====================
        // Parser Infrastructure (core cross-cutting services)
        // =====================

        $container->singleton(ExternalParserLoader::class, function (Container $_c) {
            return new ExternalParserLoader();
        });

        $container->singleton(ParserRegistry::class, function (Container $c) {
            return new ParserRegistry(
                $c->getTyped(ExternalParserLoader::class)
            );
        });

        $container->singleton(ParsingCoordinator::class, function (Container $c) {
            return new ParsingCoordinator(
                $c->getTyped(ParserRegistry::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // Bootstrap database connection
        // This loads .env configuration, establishes connection, and runs migrations
        DatabaseBootstrap::bootstrap();
    }
}
