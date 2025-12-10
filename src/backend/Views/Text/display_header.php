<?php declare(strict_types=1);
/**
 * Text Display Header View
 *
 * Variables expected:
 * - $title: string - Text title
 * - $textId: int - Text ID
 * - $audio: string - Audio URI
 * - $sourceUri: string|null - Source URI
 * - $textLinks: string - Previous/next text navigation links
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

// Variables injected from text_display_header.php:
// $title, $audio, $sourceUri, $textLinks

use Lwt\Services\MediaService;
use Lwt\View\Helper\IconHelper;

?>
<h1><?php echo \htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
<div class="flex-spaced">
    <div>
        <span id="hidet" class="click" data-action="hide-translations">
            <?php echo IconHelper::render('lightbulb', ['title' => 'Toggle Text Display (Now ON)', 'alt' => 'Toggle Text Display (Now ON)', 'class' => 'click']); ?>
        </span>
        <span id="showt" style="display:none;" class="click" data-action="show-translations">
            <?php echo IconHelper::render('lightbulb-off', ['title' => 'Toggle Text Display (Now OFF)', 'alt' => 'Toggle Text Display (Now OFF)', 'class' => 'click']); ?>
        </span>
        <span id="hide" class="click" data-action="hide-annotations">
            <?php echo IconHelper::render('lightbulb', ['title' => 'Toggle Annotation Display (Now ON)', 'alt' => 'Toggle Annotation Display (Now ON)', 'class' => 'click']); ?>
        </span>
        <span id="show" style="display:none;" class="click" data-action="show-annotations">
            <?php echo IconHelper::render('lightbulb-off', ['title' => 'Toggle Annotation Display (Now OFF)', 'alt' => 'Toggle Annotation Display (Now OFF)', 'class' => 'click']); ?>
        </span>
    </div>
    <div>
        <?php
        if ($sourceUri !== null && $sourceUri !== '') {
            echo ' <a href="' . $sourceUri . '" target="_blank">';
            echo IconHelper::render('link', ['title' => 'Text Source', 'alt' => 'Text Source']);
            echo '</a>';
        }
        echo $textLinks;
        ?>
    </div>
    <div>
        <span class="click" data-action="close-window">
            <?php echo IconHelper::render('x', ['title' => 'Close Window', 'alt' => 'Close Window', 'class' => 'click']); ?>
        </span>
    </div>
</div>
<?php
MediaService::renderMediaPlayer($audio);
?>
