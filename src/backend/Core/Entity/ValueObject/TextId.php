<?php declare(strict_types=1);
/**
 * Text ID Value Object - Backward Compatibility Alias
 *
 * @deprecated Use Lwt\Modules\Text\Domain\ValueObject\TextId instead
 */

namespace Lwt\Core\Entity\ValueObject;

// Import the new class location
use Lwt\Modules\Text\Domain\ValueObject\TextId as ModuleTextId;

// Create alias for backward compatibility
class_alias(ModuleTextId::class, TextId::class);
