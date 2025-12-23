<?php declare(strict_types=1);
/**
 * Language Module Service Provider
 *
 * Registers all services for the Language module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Language
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Language;

use Lwt\Core\Container\Container;
use Lwt\Core\Container\ServiceProviderInterface;

// Domain
use Lwt\Modules\Language\Domain\LanguageRepositoryInterface;

// Infrastructure
use Lwt\Modules\Language\Infrastructure\MySqlLanguageRepository;

// Use Cases
use Lwt\Modules\Language\Application\UseCases\CreateLanguage;
use Lwt\Modules\Language\Application\UseCases\UpdateLanguage;
use Lwt\Modules\Language\Application\UseCases\DeleteLanguage;
use Lwt\Modules\Language\Application\UseCases\GetLanguageById;
use Lwt\Modules\Language\Application\UseCases\GetLanguageCode;
use Lwt\Modules\Language\Application\UseCases\GetPhoneticReading;
use Lwt\Modules\Language\Application\UseCases\ListLanguages;
use Lwt\Modules\Language\Application\UseCases\ReparseLanguageTexts;

// Application
use Lwt\Modules\Language\Application\LanguageFacade;

// Http
use Lwt\Modules\Language\Http\LanguageController;
use Lwt\Modules\Language\Http\LanguageApiHandler;

/**
 * Service provider for the Language module.
 *
 * Registers the LanguageRepositoryInterface, all use cases,
 * LanguageFacade, LanguageController, and LanguageApiHandler.
 *
 * @since 3.0.0
 */
class LanguageServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register Repository Interface binding
        $container->singleton(LanguageRepositoryInterface::class, function (Container $_c) {
            return new MySqlLanguageRepository();
        });

        // Register MySqlLanguageRepository as concrete implementation
        $container->singleton(MySqlLanguageRepository::class, function (Container $c) {
            return $c->get(LanguageRepositoryInterface::class);
        });

        // Register Use Cases
        $this->registerUseCases($container);

        // Register Facade
        $container->singleton(LanguageFacade::class, function (Container $c) {
            return new LanguageFacade(
                $c->get(LanguageRepositoryInterface::class)
            );
        });

        // Register Controller
        $container->singleton(LanguageController::class, function (Container $c) {
            return new LanguageController(
                $c->get(LanguageFacade::class)
            );
        });

        // Register API Handler
        $container->singleton(LanguageApiHandler::class, function (Container $c) {
            return new LanguageApiHandler(
                $c->get(LanguageFacade::class)
            );
        });
    }

    /**
     * Register all use case classes.
     *
     * @param Container $container The DI container
     *
     * @return void
     */
    private function registerUseCases(Container $container): void
    {
        // GetLanguageById use case
        $container->singleton(GetLanguageById::class, function (Container $c) {
            return new GetLanguageById(
                $c->get(LanguageRepositoryInterface::class)
            );
        });

        // ListLanguages use case
        $container->singleton(ListLanguages::class, function (Container $c) {
            return new ListLanguages(
                $c->get(LanguageRepositoryInterface::class)
            );
        });

        // CreateLanguage use case
        $container->singleton(CreateLanguage::class, function (Container $c) {
            return new CreateLanguage(
                $c->get(LanguageRepositoryInterface::class)
            );
        });

        // ReparseLanguageTexts use case
        $container->singleton(ReparseLanguageTexts::class, function (Container $c) {
            return new ReparseLanguageTexts(
                $c->get(LanguageRepositoryInterface::class)
            );
        });

        // UpdateLanguage use case (depends on ReparseLanguageTexts)
        $container->singleton(UpdateLanguage::class, function (Container $c) {
            return new UpdateLanguage(
                $c->get(LanguageRepositoryInterface::class),
                $c->get(ReparseLanguageTexts::class)
            );
        });

        // DeleteLanguage use case
        $container->singleton(DeleteLanguage::class, function (Container $c) {
            return new DeleteLanguage(
                $c->get(LanguageRepositoryInterface::class)
            );
        });

        // GetLanguageCode use case
        $container->singleton(GetLanguageCode::class, function (Container $c) {
            return new GetLanguageCode(
                $c->get(LanguageRepositoryInterface::class)
            );
        });

        // GetPhoneticReading use case
        $container->singleton(GetPhoneticReading::class, function (Container $c) {
            return new GetPhoneticReading(
                $c->get(LanguageRepositoryInterface::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for the Language module
    }
}
