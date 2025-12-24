<?php declare(strict_types=1);
/**
 * Review Module Service Provider
 *
 * Registers all services for the Review module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Review
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Review;

use Lwt\Core\Container\Container;
use Lwt\Core\Container\ServiceProviderInterface;

// Domain
use Lwt\Modules\Review\Domain\ReviewRepositoryInterface;

// Infrastructure
use Lwt\Modules\Review\Infrastructure\MySqlReviewRepository;
use Lwt\Modules\Review\Infrastructure\SessionStateManager;

// Use Cases
use Lwt\Modules\Review\Application\UseCases\GetNextTerm;
use Lwt\Modules\Review\Application\UseCases\GetTableWords;
use Lwt\Modules\Review\Application\UseCases\GetTestConfiguration;
use Lwt\Modules\Review\Application\UseCases\GetTomorrowCount;
use Lwt\Modules\Review\Application\UseCases\StartReviewSession;
use Lwt\Modules\Review\Application\UseCases\SubmitAnswer;

// Application
use Lwt\Modules\Review\Application\ReviewFacade;

// Http
use Lwt\Modules\Review\Http\TestController;
use Lwt\Modules\Review\Http\ReviewApiHandler;

/**
 * Service provider for the Review module.
 *
 * Registers the ReviewRepositoryInterface, all use cases,
 * ReviewFacade, TestController, and ReviewApiHandler.
 *
 * @since 3.0.0
 */
class ReviewServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        // Register Repository Interface binding
        $container->singleton(ReviewRepositoryInterface::class, function (Container $_c) {
            return new MySqlReviewRepository();
        });

        // Register MySqlReviewRepository as concrete implementation
        $container->singleton(MySqlReviewRepository::class, function (Container $c) {
            return $c->get(ReviewRepositoryInterface::class);
        });

        // Register Infrastructure
        $container->singleton(SessionStateManager::class, function (Container $_c) {
            return new SessionStateManager();
        });

        // Register Use Cases
        $this->registerUseCases($container);

        // Register Facade
        $container->singleton(ReviewFacade::class, function (Container $c) {
            return new ReviewFacade(
                $c->get(ReviewRepositoryInterface::class),
                $c->get(SessionStateManager::class),
                $c->get(GetNextTerm::class),
                $c->get(GetTableWords::class),
                $c->get(GetTestConfiguration::class),
                $c->get(GetTomorrowCount::class),
                $c->get(StartReviewSession::class),
                $c->get(SubmitAnswer::class)
            );
        });

        // Register Controller
        $container->singleton(TestController::class, function (Container $c) {
            return new TestController(
                $c->get(ReviewFacade::class)
            );
        });

        // Register API Handler
        $container->singleton(ReviewApiHandler::class, function (Container $c) {
            return new ReviewApiHandler(
                $c->get(ReviewFacade::class)
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
        // GetNextTerm use case
        $container->singleton(GetNextTerm::class, function (Container $c) {
            return new GetNextTerm(
                $c->get(ReviewRepositoryInterface::class)
            );
        });

        // GetTableWords use case
        $container->singleton(GetTableWords::class, function (Container $c) {
            return new GetTableWords(
                $c->get(ReviewRepositoryInterface::class)
            );
        });

        // GetTestConfiguration use case
        $container->singleton(GetTestConfiguration::class, function (Container $c) {
            return new GetTestConfiguration(
                $c->get(ReviewRepositoryInterface::class),
                $c->get(SessionStateManager::class)
            );
        });

        // GetTomorrowCount use case
        $container->singleton(GetTomorrowCount::class, function (Container $c) {
            return new GetTomorrowCount(
                $c->get(ReviewRepositoryInterface::class)
            );
        });

        // StartReviewSession use case
        $container->singleton(StartReviewSession::class, function (Container $c) {
            return new StartReviewSession(
                $c->get(ReviewRepositoryInterface::class),
                $c->get(SessionStateManager::class)
            );
        });

        // SubmitAnswer use case
        $container->singleton(SubmitAnswer::class, function (Container $c) {
            return new SubmitAnswer(
                $c->get(ReviewRepositoryInterface::class),
                $c->get(SessionStateManager::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for the Review module
    }
}
