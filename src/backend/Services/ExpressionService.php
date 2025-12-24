<?php declare(strict_types=1);
/**
 * Expression Service - Multi-word expression handling
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Application\Services\ExpressionService instead
 */

namespace Lwt\Services;

use Lwt\Modules\Vocabulary\Application\Services\ExpressionService as ModuleExpressionService;

/**
 * Service class for multi-word expression handling.
 *
 * This is a backward-compatibility wrapper. Use ModuleExpressionService directly.
 *
 * @category Lwt
 * @package  Lwt\Services
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Application\Services\ExpressionService instead
 */
class ExpressionService extends ModuleExpressionService
{
    // All functionality is inherited from ModuleExpressionService
}
