<?php declare(strict_types=1);
/**
 * Text Repository - Backward Compatibility Alias
 *
 * @deprecated Use Lwt\Modules\Text\Infrastructure\MySqlTextRepository instead
 */

namespace Lwt\Core\Repository;

use Lwt\Modules\Text\Infrastructure\MySqlTextRepository;

/**
 * @deprecated Use MySqlTextRepository from Lwt\Modules\Text\Infrastructure
 */
class TextRepository extends MySqlTextRepository
{
    // This class extends the new MySqlTextRepository for backward compatibility.
    // All functionality is inherited from the parent class.
}
