<?php

declare(strict_types=1);

/**
 * Text Reading Header View
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $langId: int - Language ID
 * - $title: string - Text title
 * - $sourceUri: string|null - Source URI
 * - $media: string - Audio URI
 * - $audioPosition: int - Audio playback position
 * - $text: string - Text content for TTS
 * - $languageName: string - Language name
 * - $showAll: int - Show all words setting (0 or 1)
 * - $showLearning: int - Show learning translations (0 or 1)
 * - $languageCode: string - BCP 47 language code
 * - $phoneticText: string - Phonetic reading of text
 * - $voiceApi: string|null - TTS voice API setting
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @var int $textId
 * @var int $langId
 * @var string $title
 * @var string|null $sourceUri
 * @var string $media
 * @var int $audioPosition
 * @var string $text
 * @var string $languageName
 * @var int $showAll
 * @var int $showLearning
 * @var string $languageCode
 * @var string $phoneticText
 * @var string|null $voiceApi
 */

namespace Lwt\Views\Text;

use Lwt\Modules\Text\Application\Services\AnnotationService;
use Lwt\Modules\Admin\Application\Services\MediaService;
use Lwt\Modules\Text\Application\Services\TextNavigationService;
use Lwt\Modules\Text\Application\Services\TextStatisticsService;

// Type-safe variable extraction from controller context
assert(is_int($textId));
assert(is_int($langId));
$title = isset($title) && is_string($title) ? $title : '';
$sourceUri = isset($sourceUri) && is_string($sourceUri) ? $sourceUri : null;
assert(is_string($media));

?>
<script type="application/json" id="text-header-config"><?php echo json_encode(
    [
    'textId' => $textId,
    'phoneticText' => $phoneticText,
    'languageCode' => $languageCode,
    'voiceApi' => $voiceApi
    ]
); ?></script>

<div class="flex-header">
    <div>
    <a href="/texts" target="_top">
        <?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildLogo(); ?>
    </a>
    </div>
    <div>
        <?php
        echo (new TextNavigationService())->getPreviousAndNextTextLinks(
            $textId,
            '/text/read?start=',
            false,
            ''
        );
        ?>
    </div>
    <div>
        <a href="/review?text=<?php echo $textId; ?>" target="_top">
            <?php
            echo \Lwt\Shared\UI\Helpers\IconHelper::render('circle-help', ['title' => 'Review', 'alt' => 'Review']);
            ?>
        </a>
        <a href="/text/<?php echo $textId; ?>/print-plain" target="_top">
            <?php echo \Lwt\Shared\UI\Helpers\IconHelper::render('printer', ['title' => 'Print', 'alt' => 'Print']); ?>
        </a>
        <?php echo (new AnnotationService())->getAnnotationLink($textId); ?>
        <a target="_top" href="/texts/<?php echo $textId; ?>/edit">
            <?php
            echo \Lwt\Shared\UI\Helpers\IconHelper::render(
                'file-pen',
                ['title' => 'Edit Text', 'alt' => 'Edit Text']
            );
            ?>
        </a>
    </div>
    <div>
        <a
            href="/word/new?text=<?php echo $textId; ?>&amp;lang=<?php echo $langId; ?>"
            target="ro" data-action="show-right-frames"
        >
            <?php
            echo \Lwt\Shared\UI\Helpers\IconHelper::render(
                'notepad-text-dashed',
                ['title' => 'New Term', 'alt' => 'New Term']
            );
            ?>
        </a>
    </div>
    <div>
        <?php echo \Lwt\Shared\UI\Helpers\PageLayoutHelper::buildNavbar(); ?>
    </div>
</div>

<h1>READ &#x25B6;
    <?php
    echo \htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    if ($sourceUri !== null && $sourceUri !== '' && !str_starts_with(trim($sourceUri), '#')) {
        ?>
    <a href="<?php echo $sourceUri ?>" target="_blank">
        <?php
        echo \Lwt\Shared\UI\Helpers\IconHelper::render('link', ['title' => 'Text Source', 'alt' => 'Text Source']);
        ?>
    </a>
        <?php
    }
    ?>
</h1>

<div class="flex-spaced">
    <div>
        Unknown words:
        <span id="learnstatus"><?php echo (new TextStatisticsService())->getTodoWordsContent($textId); ?></span>
    </div>
    <div title="[Show All] = ON: ALL terms are shown, and all multi-word terms are shown as
superscripts before the first word. The superscript indicates the number of words in
the multi-word term. [Show All] = OFF: Multi-word terms now hide single words and
shorter or overlapping multi-word terms.">
        <label for="showallwords">Show All</label>&nbsp;
        <input type="checkbox" id="showallwords" <?php echo \Lwt\Shared\UI\Helpers\FormHelper::getChecked($showAll); ?>
        data-action="toggle-show-all" />
</div>
    <div
    title="[Learning Translations] = ON: Terms with Learning Level&nbsp;1 display their translations under the term.
[Learning Translations] = OFF: No translations are shown in the reading mode.">
        <label for="showlearningtranslations">Translations</label>&nbsp;
        <input type="checkbox" id="showlearningtranslations"
        <?php echo \Lwt\Shared\UI\Helpers\FormHelper::getChecked($showLearning); ?> data-action="toggle-show-all" />
</div>
    <div id="thetextid" class="is-hidden"><?php echo $textId; ?></div>
    <div><button id="readTextButton">Read in browser</button></div>
</div>

<?php (new MediaService())->renderMediaPlayer($media, (int) $audioPosition); ?>
