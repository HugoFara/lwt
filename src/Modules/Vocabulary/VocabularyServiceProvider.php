<?php declare(strict_types=1);
/**
 * Vocabulary Module Service Provider
 *
 * Registers all services for the Vocabulary module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary;

use Lwt\Core\Container\Container;
use Lwt\Core\Container\ServiceProviderInterface;

// Domain
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;

// Infrastructure
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;

// Use Cases
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTerm;
use Lwt\Modules\Vocabulary\Application\UseCases\DeleteTerm;
use Lwt\Modules\Vocabulary\Application\UseCases\GetTermById;
use Lwt\Modules\Vocabulary\Application\UseCases\UpdateTerm;
use Lwt\Modules\Vocabulary\Application\UseCases\UpdateTermStatus;

// Application
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;

/**
 * Service provider for the Vocabulary module.
 *
 * Registers the TermRepositoryInterface, all use cases,
 * and VocabularyFacade.
 *
 * @since 3.0.0
 */
class VocabularyServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register Repository Interface binding
        $container->singleton(TermRepositoryInterface::class, function (Container $_c) {
            return new MySqlTermRepository();
        });

        // Register MySqlTermRepository as concrete implementation
        $container->singleton(MySqlTermRepository::class, function (Container $c) {
            return $c->get(TermRepositoryInterface::class);
        });

        // Register Use Cases
        $container->singleton(CreateTerm::class, function (Container $c) {
            return new CreateTerm($c->get(TermRepositoryInterface::class));
        });

        $container->singleton(GetTermById::class, function (Container $c) {
            return new GetTermById($c->get(TermRepositoryInterface::class));
        });

        $container->singleton(UpdateTerm::class, function (Container $c) {
            return new UpdateTerm($c->get(TermRepositoryInterface::class));
        });

        $container->singleton(DeleteTerm::class, function (Container $c) {
            return new DeleteTerm($c->get(TermRepositoryInterface::class));
        });

        $container->singleton(UpdateTermStatus::class, function (Container $c) {
            return new UpdateTermStatus($c->get(TermRepositoryInterface::class));
        });

        // Register Facade
        $container->singleton(VocabularyFacade::class, function (Container $c) {
            return new VocabularyFacade(
                $c->get(TermRepositoryInterface::class),
                $c->get(CreateTerm::class),
                $c->get(GetTermById::class),
                $c->get(UpdateTerm::class),
                $c->get(DeleteTerm::class),
                $c->get(UpdateTermStatus::class)
            );
        });

        // Register Controller (to be added in future migration)
        // $container->singleton(VocabularyController::class, function (Container $c) {
        //     return new VocabularyController($c->get(VocabularyFacade::class));
        // });

        // Register API Handler (to be added in future migration)
        // $container->singleton(VocabularyApiHandler::class, function (Container $c) {
        //     return new VocabularyApiHandler($c->get(VocabularyFacade::class));
        // });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for the Vocabulary module yet
    }
}
