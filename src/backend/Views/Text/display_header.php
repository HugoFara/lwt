<?php

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

// Variables injected from text_display_header.php:
// $title, $audio, $sourceUri, $textLinks

?>
<h1><?php echo tohtml($title); ?></h1>
<div class="flex-spaced">
    <div>
        <img id="hidet" class="click" src="/assets/icons/light-bulb-T.png"
        title="Toggle Text Display (Now ON)" alt="Toggle Text Display (Now ON)"
        data-action="hide-translations" />
        <img id="showt" style="display:none;" class="click" src="/assets/icons/light-bulb-off-T.png"
        title="Toggle Text Display (Now OFF)" alt="Toggle Text Display (Now OFF)"
        data-action="show-translations" />
        <img id="hide" class="click" src="/assets/icons/light-bulb-A.png"
        title="Toggle Annotation Display (Now ON)" alt="Toggle Annotation Display (Now ON)"
        data-action="hide-annotations" />
        <img id="show" style="display:none;" class="click" src="/assets/icons/light-bulb-off-A.png"
        title="Toggle Annotation Display (Now OFF)" alt="Toggle Annotation Display (Now OFF)"
        data-action="show-annotations" />
    </div>
    <div>
        <?php
        if ($sourceUri !== null && $sourceUri !== '') {
            echo ' <a href="' . $sourceUri . '" target="_blank">
                <img src="' . get_file_path('assets/icons/chain.png') . '" title="Text Source" alt="Text Source" />
            </a>';
        }
        echo $textLinks;
        ?>
    </div>
    <div>
        <img class="click" src="/assets/icons/cross.png" title="Close Window" alt="Close Window"
        data-action="close-window" />
    </div>
</div>
<?php
\makeMediaPlayer($audio);
?>
