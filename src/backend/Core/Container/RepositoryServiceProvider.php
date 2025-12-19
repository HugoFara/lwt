<?php declare(strict_types=1);
/**
 * Repository Service Provider
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

use Lwt\Core\Repository\LanguageRepository;
use Lwt\Core\Repository\RepositoryInterface;
use Lwt\Core\Repository\TermRepository;
use Lwt\Core\Repository\TextRepository;

/**
 * Service provider that registers all repository classes.
 *
 * @since 3.0.0
 *
 * @psalm-suppress UnusedClass Class will be used when container is fully integrated
 */
class RepositoryServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register LanguageRepository as a singleton
        $container->singleton(LanguageRepository::class, function (Container $_c) {
            return new LanguageRepository();
        });

        // Register TextRepository as a singleton
        $container->singleton(TextRepository::class, function (Container $_c) {
            return new TextRepository();
        });

        // Register TermRepository as a singleton
        $container->singleton(TermRepository::class, function (Container $_c) {
            return new TermRepository();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for repositories
    }
}
