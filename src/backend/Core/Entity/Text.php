<?php declare(strict_types=1);
/**
 * Text Entity - Backward Compatibility Alias
 *
 * @deprecated Use Lwt\Modules\Text\Domain\Text instead
 */

namespace Lwt\Core\Entity;

// Import the new class location
use Lwt\Modules\Text\Domain\Text as ModuleText;

// Create alias for backward compatibility
class_alias(ModuleText::class, Text::class);
