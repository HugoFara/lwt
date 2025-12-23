<?php declare(strict_types=1);
/**
 * Text Handler - Backward Compatibility Alias
 *
 * This file provides backward compatibility for code referencing
 * the old TextHandler location. The actual implementation has
 * moved to the Text module as TextApiHandler.
 *
 * @deprecated Use Lwt\Modules\Text\Http\TextApiHandler instead
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Api\V1\Handlers
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Api\V1\Handlers;

require_once dirname(__DIR__, 4) . '/Modules/Text/Http/TextApiHandler.php';

class_alias(\Lwt\Modules\Text\Http\TextApiHandler::class, TextHandler::class);
