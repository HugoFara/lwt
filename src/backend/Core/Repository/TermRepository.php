<?php declare(strict_types=1);
/**
 * Term Repository - Backward Compatibility Alias
 *
 * This file maintains backward compatibility during the modular monolith migration.
 * New code should use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository directly.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Repository
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 * @deprecated Use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository instead
 */

namespace Lwt\Core\Repository;

use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository;

/**
 * Backward compatibility alias for TermRepository.
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Infrastructure\MySqlTermRepository instead
 */
class_alias(MySqlTermRepository::class, 'Lwt\\Core\\Repository\\TermRepository');
