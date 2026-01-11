<?php

/**
 * \file
 * \brief Google Translate time token management.
 *
 * PHP version 8.1
 *
 * @category Integration
 * @package  Lwt\Core\Integration
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Core\Integration;

use Lwt\Shared\Infrastructure\Database\Connection;

/**
 * Google Translate time token manager.
 *
 * Handles generation and retrieval of time tokens for Google Translate API.
 *
 * @since 3.0.0
 */
class GoogleTimeToken
{
    /**
     * Generate a new token for Google.
     *
     * @return int[]|null Token pair [time, hash] or null on failure
     *
     * @psalm-return list{int, int}|null
     */
    public static function regenerate(): ?array
    {
        if (is_callable('curl_init')) {
            $curl = curl_init("https://translate.google.com");
            if ($curl === false) {
                return null;
            }
            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                ["User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1"]
            );
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $g = (string) curl_exec($curl);
            curl_close($curl);
            if ($g == '') {
                return null;
            }
        } else {
            $ctx = stream_context_create([
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) " .
                    "Gecko/20100101 Firefox/40.1\r\n"
                ]
            ]);
            $g = file_get_contents(
                "https://translate.google.com",
                false,
                $ctx
            );
            if ($g === false) {
                return null;
            }
        }
        // May be replaced by /TKK=eval\D+3d([\d-]+)\D+3d([\d-]+)\D+(\d+)\D/
        preg_match_all(
            "/TKK=eval[^0-9]+3d([0-9-]+)[^0-9]+3d([0-9-]+)[^0-9]+([0-9]+)[^0-9]/u",
            $g,
            $ma
        );
        if (isset($ma[1][0]) && isset($ma[2][0]) && isset($ma[3][0])) {
            $tok = strval($ma[3][0]) . "." .
                strval(intval($ma[1][0]) + intval($ma[2][0]));
            Connection::query(
                "INSERT INTO _lwtgeneral (LWTKey, LWTValue)
                VALUES ('GoogleTimeToken', '$tok')
                ON DUPLICATE KEY UPDATE LWTValue = '$tok'"
            );
            return [intval($ma[3][0]), intval($ma[1][0]) + intval($ma[2][0])];
        }
        return null;
    }

    /**
     * Get the time token to use for Google, generating a new one if necessary.
     *
     * @return int[]|null Token pair [time, hash] or null on failure
     *
     * @psalm-return list{int, int}|null
     */
    public static function get(): ?array
    {
        $val = (string) Connection::fetchValue(
            'SELECT LWTValue AS token from _lwtgeneral WHERE LWTKey = "GoogleTimeToken"',
            'token'
        );
        $arr = empty($val) ? ['0'] : explode('.', $val);
        if (intval($arr[0]) < floor(time() / 3600) - 100) {
            // Token renewed after 100 hours
            return self::regenerate();
        }
        return [intval($arr[0]), intval($arr[1] ?? 0)];
    }
}
