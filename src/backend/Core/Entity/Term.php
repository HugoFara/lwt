<?php declare(strict_types=1);
/**
 * Term Entity - Backward Compatibility Alias
 *
 * This file maintains backward compatibility during the modular monolith migration.
 * New code should use Lwt\Modules\Vocabulary\Domain\Term directly.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Entity
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    2.7.0
 * @deprecated Use Lwt\Modules\Vocabulary\Domain\Term instead
 */

namespace Lwt\Core\Entity;

use Lwt\Modules\Vocabulary\Domain\Term as ModuleTerm;

/**
 * Backward compatibility alias for Term entity.
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Domain\Term instead
 */
class_alias(ModuleTerm::class, 'Lwt\\Core\\Entity\\Term');
