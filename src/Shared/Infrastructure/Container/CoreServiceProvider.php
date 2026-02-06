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

/**
 * Core service provider that registers essential cross-cutting services.
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
        // No core service registrations needed.
        // Parser infrastructure has moved to LanguageServiceProvider.
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
