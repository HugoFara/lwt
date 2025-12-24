<?php declare(strict_types=1);
/**
 * Get Setting Use Case
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
 * Use case for getting a single setting value.
 *
 * @since 3.0.0
 */
class GetSetting
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
     * @param string $key     Setting key
     * @param string $default Default value if not found
     *
     * @return string Setting value
     */
    public function execute(string $key, string $default = ''): string
    {
        return $this->repository->get($key, $default);
    }
}
