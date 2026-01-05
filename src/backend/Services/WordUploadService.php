<?php declare(strict_types=1);
/**
 * Word Upload Service - Backward compatibility wrapper
 *
 * PHP version 8.1
 *
 * @category   Lwt
 * @package    Lwt\Services
 * @author     HugoFara <hugo.farajallah@protonmail.com>
 * @license    Unlicense <http://unlicense.org/>
 * @link       https://hugofara.github.io/lwt/docs/php/
 * @since      3.0.0
 * @deprecated 3.0.0 Use Lwt\Modules\Vocabulary\Application\Services\WordUploadService instead
 */

namespace Lwt\Services;

require_once __DIR__ . '/../../Modules/Vocabulary/Application/Services/WordUploadService.php';

use Lwt\Modules\Vocabulary\Application\Services\WordUploadService as ModuleWordUploadService;

/**
 * Service class for importing words/terms from files or text input.
 *
 * @category   Lwt
 * @package    Lwt\Services
 * @author     HugoFara <hugo.farajallah@protonmail.com>
 * @license    Unlicense <http://unlicense.org/>
 * @link       https://hugofara.github.io/lwt/docs/php/
 * @since      3.0.0
 * @deprecated 3.0.0 Use Lwt\Modules\Vocabulary\Application\Services\WordUploadService instead
 */
class WordUploadService extends ModuleWordUploadService
{
    // All methods inherited from module class
}
