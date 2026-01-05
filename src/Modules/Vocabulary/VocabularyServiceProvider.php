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

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;

// Domain
use Lwt\Modules\Vocabulary\Domain\TermRepositoryInterface;

// Infrastructure
use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;

// Use Cases
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTerm;
use Lwt\Modules\Vocabulary\Application\UseCases\DeleteTerm;
use Lwt\Modules\Vocabulary\Application\UseCases\FindSimilarTerms;
use Lwt\Modules\Vocabulary\Application\UseCases\GetTermById;
use Lwt\Modules\Vocabulary\Application\UseCases\UpdateTerm;
use Lwt\Modules\Vocabulary\Application\UseCases\UpdateTermStatus;

// Services
use Lwt\Modules\Vocabulary\Application\Services\SimilarityCalculator;

// Infrastructure
use Lwt\Modules\Vocabulary\Infrastructure\DictionaryAdapter;

// Application
use Lwt\Modules\Vocabulary\Application\VocabularyFacade;

// HTTP
use Lwt\Modules\Vocabulary\Http\VocabularyController;
use Lwt\Modules\Vocabulary\Http\VocabularyApiHandler;
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\Modules\Vocabulary\Application\Services\WordListService;
use Lwt\Modules\Vocabulary\Application\Services\WordUploadService;
use Lwt\Modules\Vocabulary\Application\Services\ExpressionService;

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

        // Register Services
        $container->singleton(SimilarityCalculator::class, function (Container $_c) {
            return new SimilarityCalculator();
        });

        $container->singleton(FindSimilarTerms::class, function (Container $c) {
            return new FindSimilarTerms(
                $c->get(TermRepositoryInterface::class),
                $c->get(SimilarityCalculator::class)
            );
        });

        $container->singleton(DictionaryAdapter::class, function (Container $_c) {
            return new DictionaryAdapter();
        });

        // Register Module Services
        $container->singleton(WordListService::class, function (Container $_c) {
            return new WordListService();
        });

        $container->singleton(WordUploadService::class, function (Container $_c) {
            return new WordUploadService();
        });

        $container->singleton(ExpressionService::class, function (Container $_c) {
            return new ExpressionService();
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

        // Register CreateTermFromHover Use Case
        $container->singleton(CreateTermFromHover::class, function (Container $_c) {
            return new CreateTermFromHover();
        });

        // Register Controller
        $container->singleton(VocabularyController::class, function (Container $c) {
            return new VocabularyController(
                $c->get(VocabularyFacade::class),
                $c->get(CreateTermFromHover::class),
                $c->get(FindSimilarTerms::class),
                $c->get(DictionaryAdapter::class)
            );
        });

        // Register API Handler
        $container->singleton(VocabularyApiHandler::class, function (Container $c) {
            return new VocabularyApiHandler(
                $c->get(VocabularyFacade::class),
                $c->get(FindSimilarTerms::class),
                $c->get(DictionaryAdapter::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed for the Vocabulary module yet
    }
}
