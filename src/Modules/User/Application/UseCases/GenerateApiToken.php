<?php declare(strict_types=1);
/**
 * Generate API Token Use Case
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

use DateTimeImmutable;
use Lwt\Modules\User\Application\Services\PasswordHasher;
use Lwt\Modules\User\Domain\UserRepositoryInterface;

/**
 * Use case for generating API tokens for users.
 *
 * @since 3.0.0
 */
class GenerateApiToken
{
    /**
     * User repository.
     *
     * @var UserRepositoryInterface
     */
    private UserRepositoryInterface $repository;

    /**
     * Password hasher (for token generation).
     *
     * @var PasswordHasher
     */
    private PasswordHasher $passwordHasher;

    /**
     * API token expiration time in seconds (default: 30 days).
     */
    private const API_TOKEN_EXPIRATION = 30 * 24 * 60 * 60;

    /**
     * Create a new GenerateApiToken use case.
     *
     * @param UserRepositoryInterface $repository     User repository
     * @param PasswordHasher|null     $passwordHasher Password hasher
     */
    public function __construct(
        UserRepositoryInterface $repository,
        ?PasswordHasher $passwordHasher = null
    ) {
        $this->repository = $repository;
        $this->passwordHasher = $passwordHasher ?? new PasswordHasher();
    }

    /**
     * Execute to generate an API token.
     *
     * @param int $userId The user ID
     *
     * @return string The generated API token
     *
     * @throws \InvalidArgumentException If user not found
     */
    public function execute(int $userId): string
    {
        $user = $this->repository->find($userId);
        if ($user === null) {
            throw new \InvalidArgumentException('User not found');
        }

        $token = $this->passwordHasher->generateToken(32);
        $expires = new DateTimeImmutable('+' . self::API_TOKEN_EXPIRATION . ' seconds');

        $user->setApiToken($token, $expires);
        $this->repository->save($user);

        return $token;
    }
}
