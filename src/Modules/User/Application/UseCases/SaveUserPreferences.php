<?php

/**
 * Save User Preferences Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\User\Application\UseCases;

use Lwt\Shared\Infrastructure\Database\Settings;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Shared\Infrastructure\Http\InputValidator;
use Lwt\Modules\Admin\Domain\SettingDefinitions;

/**
 * Use case for saving user-scoped preferences from form data.
 *
 * In multi-user mode, saves with the current user's ID.
 * In single-user mode, saves to the global row (StUsID=0).
 *
 * @since 3.0.0
 */
class SaveUserPreferences
{
    /**
     * Execute the use case - save user preferences from request.
     *
     * @return array{success: bool}
     */
    public function execute(): array
    {
        $userId = Globals::getCurrentUserId();
        $userKeys = SettingDefinitions::getUserKeys();

        foreach ($userKeys as $key) {
            if ($key === 'set-tts') {
                $value = InputValidator::getBool($key, false) ? '1' : '0';
            } elseif (InputValidator::has($key)) {
                $value = InputValidator::getString($key);
            } else {
                continue;
            }

            if ($userId !== null) {
                Settings::saveForUser($key, $value, $userId);
            } else {
                Settings::save($key, $value);
            }
        }

        return ['success' => true];
    }
}
