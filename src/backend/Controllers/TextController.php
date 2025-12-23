<?php declare(strict_types=1);
/**
 * \file
 * \brief Text Controller - Backward Compatibility Alias
 *
 * This file provides backward compatibility for code referencing
 * the old TextController location. The actual implementation has
 * moved to the Text module.
 *
 * @deprecated Use Lwt\Modules\Text\Http\TextController instead
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Controllers;

require_once dirname(__DIR__, 2) . '/Modules/Text/Http/TextController.php';

class_alias(\Lwt\Modules\Text\Http\TextController::class, TextController::class);
