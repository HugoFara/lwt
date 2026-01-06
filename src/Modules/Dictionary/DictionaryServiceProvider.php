<?php declare(strict_types=1);
/**
 * Dictionary Module Service Provider
 *
 * Registers all services for the Dictionary module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Dictionary
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Dictionary;

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;

// Application
use Lwt\Modules\Dictionary\Application\DictionaryFacade;
use Lwt\Modules\Dictionary\Application\TranslationService;

// Http
use Lwt\Modules\Dictionary\Http\DictionaryController;
use Lwt\Modules\Dictionary\Http\TranslationController;

// Application Services
use Lwt\Modules\Dictionary\Application\Services\LocalDictionaryService;

// Infrastructure - Dictionary Importers
use Lwt\Modules\Dictionary\Infrastructure\Import\ImporterInterface;
use Lwt\Modules\Dictionary\Infrastructure\Import\CsvImporter;
use Lwt\Modules\Dictionary\Infrastructure\Import\JsonImporter;
use Lwt\Modules\Dictionary\Infrastructure\Import\StarDictImporter;

// Language Module
use Lwt\Modules\Language\Application\LanguageFacade;

/**
 * Service provider for the Dictionary module.
 *
 * Registers the facade, controller, and related services
 * for the Dictionary module.
 *
 * @since 3.0.0
 */
class DictionaryServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register LocalDictionaryService (used by facade)
        $container->singleton(LocalDictionaryService::class, function (Container $_c) {
            return new LocalDictionaryService();
        });

        // Register Facade
        $container->singleton(DictionaryFacade::class, function (Container $c) {
            return new DictionaryFacade(
                $c->get(LocalDictionaryService::class)
            );
        });

        // Register Controller
        $container->singleton(DictionaryController::class, function (Container $c) {
            return new DictionaryController(
                $c->get(DictionaryFacade::class),
                $c->get(LanguageFacade::class)
            );
        });

        // Register TranslationService
        $container->singleton(TranslationService::class, function (Container $_c) {
            return new TranslationService();
        });

        // Register TranslationController
        $container->singleton(TranslationController::class, function (Container $c) {
            return new TranslationController(
                $c->get(TranslationService::class)
            );
        });

        // Register Dictionary Importers
        $container->singleton(CsvImporter::class, function (Container $_c) {
            return new CsvImporter();
        });

        $container->singleton(JsonImporter::class, function (Container $_c) {
            return new JsonImporter();
        });

        $container->singleton(StarDictImporter::class, function (Container $_c) {
            return new StarDictImporter();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for the Dictionary module
    }
}
