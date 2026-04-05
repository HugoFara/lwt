<?php

/**
 * Translator Service Provider
 *
 * Registers the Translator service in the DI container and configures
 * the active locale from the user's app_language setting.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Shared\I18n
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Shared\I18n;

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;
use Lwt\Shared\Infrastructure\Database\Settings;

/**
 * Registers the Translator as a singleton and sets the active locale
 * from the database during the boot phase.
 *
 * @since 3.0.0
 */
class TranslatorServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Translator singleton.
     *
     * @param Container $container The DI container
     *
     * @return void
     */
    public function register(Container $container): void
    {
        $container->singleton(Translator::class, function () {
            $localePath = getcwd() . '/locale';
            return new Translator($localePath, 'en');
        });
    }

    /**
     * Read the user's locale preference from settings and apply it.
     *
     * Runs after all providers are registered, so the DB is available.
     * Silently defaults to English if the DB is not yet configured
     * (e.g. during the setup wizard).
     *
     * @param Container $container The DI container
     *
     * @return void
     */
    public function boot(Container $container): void
    {
        try {
            $locale = Settings::getWithDefault('app_language');
            if ($locale !== '') {
                $container->getTyped(Translator::class)->setLocale($locale);
            }
        } catch (\Throwable $e) {
            // DB not available (e.g. wizard page) — stay with English
        }
    }
}
