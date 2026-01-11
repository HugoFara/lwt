<?php

declare(strict_types=1);

/**
 * Text Reading Content View
 *
 * Variables expected:
 * - $textId: int - Text ID
 * - $langId: int - Language ID
 * - $textTitle: string - Text title
 * - $annotatedText: string - Annotated text content
 * - $textPosition: int - Reading position
 * - $dictLink1: string - Dictionary 1 URI
 * - $dictLink2: string - Dictionary 2 URI
 * - $translatorLink: string - Google Translate URI
 * - $textSize: int - Text font size
 * - $regexpWordChars: string - Regexp word characters
 * - $rtlScript: bool - Right-to-left script
 * - $modeTrans: int - Annotation position (1-4)
 * - $visitStatus: string - Visit status filter
 * - $termDelimiter: string - Term translation delimiter
 * - $hts: string - HTS setting
 *
 * PHP version 8.1
 *
 * @category Views
 * @package  Lwt
 * @license  Unlicense <http://unlicense.org/>
 * @since    3.0.0
 *
 * @var int $textId
 * @var int $langId
 * @var string $textTitle
 * @var string $annotatedText
 * @var int $textPosition
 * @var string $dictLink1
 * @var string $dictLink2
 * @var string $translatorLink
 * @var int $textSize
 * @var string $regexpWordChars
 * @var bool $rtlScript
 * @var int $modeTrans
 * @var string $visitStatus
 * @var string $termDelimiter
 * @var string $hts
 */

namespace Lwt\Views\Text;

use Lwt\Modules\Text\Application\Services\AnnotationService;

// Type-safe variable extraction from controller context
assert(is_int($textId));
assert(is_int($langId));
assert(is_string($annotatedText));

// Compute annotations JSON safely
$annotationJson = (new AnnotationService())->annotationToJson($annotatedText);
/**
 * @var array<int, mixed>|null
*/
$annotations = json_decode($annotationJson !== false ? $annotationJson : '[]', true);

// Build variable array for JavaScript - will be merged into LWT_DATA by TypeScript
$varArray = [
    'LWT_DATA' => [
        'language' => [
            'id'              => $langId,
            'dict_link1'      => $dictLink1,
            'dict_link2'      => $dictLink2,
            'translator_link' => $translatorLink,
            'delimiter'       => htmlspecialchars(
                str_replace(
                    ['\\',']','-','^'],
                    ['\\\\','\\]','\\-','\\^'],
                    (string)$termDelimiter
                ),
                ENT_QUOTES,
                'UTF-8'
            ),
            'word_parsing'    => $regexpWordChars,
            'rtl'             => $rtlScript
        ],
        'text' => [
            'id'               => $textId,
            'reading_position' => $textPosition,
            'annotations'      => $annotations
        ],
        'settings' => [
            'hts'                => $hts,
            'word_status_filter' => \Lwt\View\Helper\StatusHelper::makeClassFilter((int)$visitStatus),
            'annotations_mode'   => $modeTrans
        ],
    ]
];
?>
<script type="application/json" id="text-reading-config"><?php echo json_encode($varArray, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>

<!-- Text container - content loaded dynamically via Alpine.js -->
<div id="thetext"
     x-data
     x-init="$store.words.loadText(<?php echo $textId; ?>)"
     <?php echo ($rtlScript ? 'dir="rtl"' : '') ?>>

    <!-- Loading state -->
    <div x-show="$store.words.isLoading" class="has-text-centered p-4">
        <span class="icon is-large">
            <i class="fas fa-spinner fa-pulse fa-2x"></i>
        </span>
    </div>

    <!-- Text content (rendered by Alpine.js from word store) -->
    <p x-show="!$store.words.isLoading && $store.words.isInitialized"
       x-bind:style="$store.words.paragraphStyles"
       x-effect="$store.words.setTextHtml($el)">
    </p>

    <!-- Bottom padding -->
    <p x-show="$store.words.isInitialized"
       x-bind:style="'font-size:' + $store.words.textSize + '%;line-height: 1.4; margin-bottom: 300px;'">
        &nbsp;
    </p>
</div>
