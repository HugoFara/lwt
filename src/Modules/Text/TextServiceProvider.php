<?php declare(strict_types=1);
/**
 * Text Module Service Provider
 *
 * Registers all services for the Text module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Text;

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;

// Domain
use Lwt\Modules\Text\Domain\TextRepositoryInterface;

// Infrastructure
use Lwt\Modules\Text\Infrastructure\MySqlTextRepository;

// Use Cases
use Lwt\Modules\Text\Application\UseCases\ImportText;
use Lwt\Modules\Text\Application\UseCases\UpdateText;
use Lwt\Modules\Text\Application\UseCases\ArchiveText;
use Lwt\Modules\Text\Application\UseCases\DeleteText;
use Lwt\Modules\Text\Application\UseCases\GetTextForReading;
use Lwt\Modules\Text\Application\UseCases\GetTextForEdit;
use Lwt\Modules\Text\Application\UseCases\ListTexts;
use Lwt\Modules\Text\Application\UseCases\ParseText;
use Lwt\Modules\Text\Application\UseCases\BuildTextFilters;

// Application
use Lwt\Modules\Text\Application\TextFacade;

// Http
use Lwt\Modules\Text\Http\TextController;
use Lwt\Modules\Text\Http\TextApiHandler;

/**
 * Service provider for the Text module.
 *
 * Registers the TextRepositoryInterface, all use cases,
 * TextFacade, TextController, and TextApiHandler.
 *
 * @since 3.0.0
 */
class TextServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register Repository Interface binding
        $container->singleton(TextRepositoryInterface::class, function (Container $_c) {
            return new MySqlTextRepository();
        });

        // Register MySqlTextRepository as concrete implementation
        $container->singleton(MySqlTextRepository::class, function (Container $c) {
            return $c->get(TextRepositoryInterface::class);
        });

        // Register Use Cases
        $this->registerUseCases($container);

        // Register Facade
        $container->singleton(TextFacade::class, function (Container $c) {
            return new TextFacade(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // Register Controller
        $container->singleton(TextController::class, function (Container $_c) {
            return new TextController();
        });

        // Register API Handler
        $container->singleton(TextApiHandler::class, function (Container $_c) {
            return new TextApiHandler();
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
        // ImportText use case
        $container->singleton(ImportText::class, function (Container $c) {
            return new ImportText(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // UpdateText use case
        $container->singleton(UpdateText::class, function (Container $c) {
            return new UpdateText(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // ArchiveText use case
        $container->singleton(ArchiveText::class, function (Container $c) {
            return new ArchiveText(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // DeleteText use case
        $container->singleton(DeleteText::class, function (Container $c) {
            return new DeleteText(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // GetTextForReading use case
        $container->singleton(GetTextForReading::class, function (Container $c) {
            return new GetTextForReading(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // GetTextForEdit use case
        $container->singleton(GetTextForEdit::class, function (Container $c) {
            return new GetTextForEdit(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // ListTexts use case
        $container->singleton(ListTexts::class, function (Container $c) {
            return new ListTexts(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // ParseText use case
        $container->singleton(ParseText::class, function (Container $c) {
            return new ParseText(
                $c->get(TextRepositoryInterface::class)
            );
        });

        // BuildTextFilters use case (no dependencies)
        $container->singleton(BuildTextFilters::class, function (Container $_c) {
            return new BuildTextFilters();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for the Text module
    }
}
