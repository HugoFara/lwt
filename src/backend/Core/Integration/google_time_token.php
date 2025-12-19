<?php declare(strict_types=1);
/**
 * \file
 * \brief Google Translate time token functions - DEPRECATED wrappers.
 *
 * Use Lwt\Core\Integration\GoogleTimeToken class instead.
 *
 * PHP version 8.1
 *
 * @category Integration
 * @package  Lwt\Core\Integration
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 * @deprecated Use Lwt\Core\Integration\GoogleTimeToken class instead
 */

namespace Lwt\Includes;

require_once __DIR__ . '/GoogleTimeToken.php';

use Lwt\Core\Integration\GoogleTimeToken;

/**
 * Generate a new token for Google.
 *
 * @return int[]|null
 *
 * @psalm-return list{int, int}|null
 *
 * @deprecated Use GoogleTimeToken::regenerate() instead
 */
function regenGoogleTimeToken(): ?array
{
    return GoogleTimeToken::regenerate();
}

/**
 * Get the time token to use for Google, generating a new one if necessary.
 *
 * @return int[]|null
 *
 * @psalm-return list{int, int}|null
 *
 * @deprecated Use GoogleTimeToken::get() instead
 */
function getGoogleTimeToken(): ?array
{
    return GoogleTimeToken::get();
}
