<?php declare(strict_types=1);
/**
 * Term ID Value Object - Backward Compatibility Alias
 *
 * This file maintains backward compatibility during the modular monolith migration.
 * New code should use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId directly.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Entity\ValueObject
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 * @deprecated Use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId instead
 */

namespace Lwt\Core\Entity\ValueObject;

use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId as ModuleTermId;

/**
 * Backward compatibility alias for TermId value object.
 *
 * @deprecated Use Lwt\Modules\Vocabulary\Domain\ValueObject\TermId instead
 */
class_alias(ModuleTermId::class, 'Lwt\\Core\\Entity\\ValueObject\\TermId');
