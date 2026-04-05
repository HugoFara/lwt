<?php

/**
 * Activity Module Service Provider
 *
 * Registers all services for the Activity module.
 *
 * PHP version 8.2
 *
 * @category Lwt
 * @package  Lwt\Modules\Activity
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Activity;

use Lwt\Shared\Infrastructure\Container\Container;
use Lwt\Shared\Infrastructure\Container\ServiceProviderInterface;
use Lwt\Modules\Activity\Domain\ActivityRepositoryInterface;
use Lwt\Modules\Activity\Infrastructure\MySqlActivityRepository;
use Lwt\Modules\Activity\Application\ActivityFacade;
use Lwt\Modules\Activity\Http\ActivityApiHandler;

/**
 * Service provider for the Activity module.
 *
 * @since 3.0.0
 */
class ActivityServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $container): void
    {
        $container->singleton(ActivityRepositoryInterface::class, function (Container $_c) {
            return new MySqlActivityRepository();
        });

        $container->singleton(MySqlActivityRepository::class, function (Container $c): ActivityRepositoryInterface {
            return $c->getTyped(ActivityRepositoryInterface::class);
        });

        $container->singleton(ActivityFacade::class, function (Container $c) {
            return new ActivityFacade(
                $c->getTyped(ActivityRepositoryInterface::class)
            );
        });

        $container->singleton(ActivityApiHandler::class, function (Container $c) {
            return new ActivityApiHandler(
                $c->getTyped(ActivityFacade::class)
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Container $container): void
    {
        // No bootstrap logic needed
    }
}
