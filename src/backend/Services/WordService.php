<?php declare(strict_types=1);
/**
 * Word Service - Backward Compatibility Alias
 *
 * This file provides a backward compatibility alias for the WordService class
 * which has been moved to the Vocabulary module.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 * @deprecated 3.0.0 Use Lwt\Modules\Vocabulary\Application\Services\WordService instead.
 */

namespace Lwt\Services;

require_once __DIR__ . '/../../Modules/Vocabulary/Application/Services/WordService.php';

// Create class alias for backward compatibility
if (!class_exists(\Lwt\Services\WordService::class, false)) {
    class_alias(
        \Lwt\Modules\Vocabulary\Application\Services\WordService::class,
        \Lwt\Services\WordService::class
    );
}
