<?php declare(strict_types=1);
/**
 * Reset All Settings Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Admin\Application\UseCases\Settings
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Admin\Application\UseCases\Settings;

use Lwt\Modules\Admin\Domain\SettingsRepositoryInterface;

/**
 * Use case for resetting all settings to defaults.
 *
 * @since 3.0.0
 */
class ResetAllSettings
{
    private SettingsRepositoryInterface $repository;

    /**
     * Constructor.
     *
     * @param SettingsRepositoryInterface $repository Settings repository
     */
    public function __construct(SettingsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Execute the use case.
     *
     * Deletes all settings with 'set-' prefix, restoring defaults.
     *
     * @return array{success: bool}
     */
    public function execute(): array
    {
        $this->repository->deleteByPattern('set-%');
        return ['success' => true];
    }
}
