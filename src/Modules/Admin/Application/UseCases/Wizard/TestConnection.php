<?php declare(strict_types=1);
/**
 * Test Connection Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\UseCases\Wizard
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Application\UseCases\Wizard;

use Lwt\Modules\Admin\Application\DTO\DatabaseConnectionDTO;
use Lwt\Modules\Admin\Infrastructure\FileSystemEnvRepository;

/**
 * Use case for testing database connection.
 *
 * This use case does NOT require an existing database connection.
 * It attempts to establish a new connection using provided credentials.
 *
 * @since 3.0.0
 */
class TestConnection
{
    private FileSystemEnvRepository $repository;

    /**
     * Constructor.
     *
     * @param FileSystemEnvRepository|null $repository Env file repository
     */
    public function __construct(?FileSystemEnvRepository $repository = null)
    {
        $this->repository = $repository ?? new FileSystemEnvRepository();
    }

    /**
     * Execute the use case.
     *
     * @param DatabaseConnectionDTO $connection Connection data to test
     *
     * @return string Status message
     */
    public function execute(DatabaseConnectionDTO $connection): string
    {
        return $this->repository->testConnection($connection);
    }

    /**
     * Execute with form data array.
     *
     * @param array<string, mixed> $formData Form input data
     *
     * @return string Status message
     */
    public function executeFromForm(array $formData): string
    {
        $dto = DatabaseConnectionDTO::fromFormData($formData);
        return $this->repository->testConnection($dto);
    }

    /**
     * Check if connection was successful.
     *
     * @param string $message Status message from execute()
     *
     * @return bool True if connection succeeded
     */
    public function isSuccess(string $message): bool
    {
        return str_contains($message, 'success');
    }
}
