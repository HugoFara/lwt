<?php declare(strict_types=1);
/**
 * Validate API Token Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\User\Application\UseCases;

use Lwt\Core\Entity\User;
use Lwt\Modules\User\Domain\UserRepositoryInterface;

/**
 * Use case for validating API tokens.
 *
 * @since 3.0.0
 */
class ValidateApiToken
{
    /**
     * User repository.
     *
     * @var UserRepositoryInterface
     */
    private UserRepositoryInterface $repository;

    /**
     * Create a new ValidateApiToken use case.
     *
     * @param UserRepositoryInterface $repository User repository
     */
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Execute to validate an API token.
     *
     * @param string $token The API token to validate
     *
     * @return User|null The user if token is valid, null otherwise
     */
    public function execute(string $token): ?User
    {
        try {
            $user = $this->repository->findByApiToken($token);

            if ($user === null) {
                return null;
            }

            if (!$user->hasValidApiToken()) {
                return null;
            }

            if (!$user->canLogin()) {
                return null;
            }

            return $user;
        } catch (\RuntimeException $e) {
            return null;
        }
    }
}
