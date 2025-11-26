<?php

/**
 * \file
 * \brief Word status definitions.
 *
 * Defines the possible statuses for words in the learning system.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/files/inc-word-status.html
 * @since    2.10.0-fork Split from kernel_utility.php
 */

/**
 * Return an associative array of all possible statuses
 *
 * @return array<int<1, 5>|98|99, array{string, string}>
 * Statuses, keys are 1, 2, 3, 4, 5, 98, 99.
 * Values are associative arrays of keys abbr and name
 */
function get_statuses()
{
    static $statuses;
    if (!$statuses) {
        $statuses = array(
        1 => array("abbr" =>   "1", "name" => "Learning"),
        2 => array("abbr" =>   "2", "name" => "Learning"),
        3 => array("abbr" =>   "3", "name" => "Learning"),
        4 => array("abbr" =>   "4", "name" => "Learning"),
        5 => array("abbr" =>   "5", "name" => "Learned"),
        99 => array("abbr" => "WKn", "name" => "Well Known"),
        98 => array("abbr" => "Ign", "name" => "Ignored"),
        );
    }
    return $statuses;
}
