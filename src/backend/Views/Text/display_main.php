<?php

/**
 * Text Display Main View (Desktop)
 *
 * Variables expected:
 * - $textId: int - Text ID
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @psalm-suppress UndefinedGlobalVariable Variables are injected by including file
 * @psalm-suppress UndefinedFunction Functions are defined in the including namespace
 */

// Variables injected from text_display.php:
// $textId

// Functions from Lwt\Interface\TextDisplay namespace are available because
// this file is included from text_display.php which defines them

?>
<div style="width: 95%; height: 100%;">
    <div id="frame-h">
        <?php \Lwt\Interface\TextDisplay\do_diplay_impr_text_header_main($textId); ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php \Lwt\Interface\TextDisplay\do_display_impr_text_text_main($textId); ?>
    </div>
</div>
