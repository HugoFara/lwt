<?php declare(strict_types=1);
/**
 * Term Status Value Object - Backward Compatibility Alias
 *
 * This file maintains backward compatibility during the modular monolith migration.
 * New code should use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus directly.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Entity\ValueObject
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 * @deprecated Use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus instead
 */

namespace Lwt\Core\Entity\ValueObject;

use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus as ModuleTermStatus;

/**
 * Backward compatibility alias for TermStatus value object.
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Domain\ValueObject\TermStatus instead
 */
class_alias(ModuleTermStatus::class, 'Lwt\\Core\\Entity\\ValueObject\\TermStatus');
