<?php declare(strict_types=1);
/**
 * Text Display Main View (Desktop)
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $title: string - Text title
 * - $audio: string - Audio URI
 * - $sourceUri: string|null - Source URI
 * - $textLinks: string - Previous/next text navigation links
 * - $annotations: array - Parsed annotation items
 * - $textSize: int - Text size percentage
 * - $rtlScript: bool - Whether text is right-to-left
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
 */

namespace Lwt\Views\Text;

?>
<div style="width: 95%; height: 100%;">
    <div id="frame-h">
        <?php include __DIR__ . '/display_header.php'; ?>
    </div>
    <hr />
    <div id="frame-l">
        <?php include __DIR__ . '/display_text.php'; ?>
    </div>
</div>
