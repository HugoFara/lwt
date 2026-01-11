<?php

/**
 * Feed Edit Text Form View
 *
 * Renders the form for editing feed items before creating texts.
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
 * @psalm-suppress PossiblyUndefinedVariable - Variables are set by the including controller
 */

declare(strict_types=1);

namespace Lwt\Views\Feed;

use Lwt\Shared\UI\Helpers\IconHelper;

/**
 * @var array<int, array{TxTitle?: string, TxText?: string, TxSourceURI?: string, TxAudioURI?: string}> $texts
 * @var array{NfLgID: int, NfID: int} $row
 * @var int $count
 * @var string $tagName
 * @var int $nfId
 * @var int $maxTexts
 * @var array<int, array{LgID: int, LgName: string}> $languages
 * @var string $scrdir
 */
$texts = $texts ?? [];
$row = $row ?? ['NfLgID' => 0, 'NfID' => 0];
$count = $count ?? 0;
$tagName = $tagName ?? '';
$nfId = $nfId ?? 0;
$maxTexts = $maxTexts ?? 0;
$languages = $languages ?? [];
$scrdir = $scrdir ?? '';

// Ensure $texts and $languages are iterable
if (!is_array($texts)) {
    return;
}
/** @var array<int, array{LgID: int, LgName: string}> $languagesArr */
$languagesArr = is_array($languages) ? $languages : [];

// Ensure $tagName is a string
$tagNameStr = is_string($tagName) ? $tagName : '';

/** @var array{TxTitle?: string, TxText?: string, TxSourceURI?: string, TxAudioURI?: string} $text */
foreach ($texts as $text) :
    ?>
<div class="box mb-4" x-data="{ isSelected: true }">
    <!-- Header with checkbox and title -->
    <div class="field is-horizontal">
        <div class="field-label is-normal">
            <label class="checkbox">
                <input class="markcheck"
                       type="checkbox"
                       name="Nf_count[<?php echo $count; ?>]"
                       value="<?php echo $count; ?>"
                       checked
                       x-model="isSelected" />
            </label>
        </div>
        <div class="field-body">
            <div class="field has-addons">
                <div class="control is-expanded">
                    <input type="text"
                           class="input notempty"
                           name="feed[<?php echo $count; ?>][TxTitle]"
                           value="<?php echo htmlspecialchars($text['TxTitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           maxlength="200"
                           placeholder="Title"
                           :disabled="!isSelected"
                           required />
                </div>
                <div class="control">
                    <span class="icon has-text-danger" title="Field must not be empty">
                        <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div x-show="isSelected" x-transition x-cloak>
        <!-- Language -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Language</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="feed[<?php echo $count; ?>][TxLgID]" class="notempty setfocus">
                                <?php
                                /** @var array{LgID: int, LgName: string} $rowLang */
                                foreach ($languagesArr as $rowLang) : ?>
                                <option value="<?php echo $rowLang['LgID']; ?>"<?php
                                if ($row['NfLgID'] === $rowLang['LgID']) {
                                    echo ' selected';
                                }
                                ?>><?php echo htmlspecialchars($rowLang['LgName'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Text Content -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Text</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <textarea <?php echo $scrdir; ?>
                                  name="feed[<?php echo $count; ?>][TxText]"
                                  class="textarea notempty checkbytes"
                                  rows="12"
                                  required
                        ><?php echo htmlspecialchars($text['TxText'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Source URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Source URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="url"
                               class="input checkurl"
                               name="feed[<?php echo $count; ?>][TxSourceURI]"
                               value="<?php echo htmlspecialchars($text['TxSourceURI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="1000"
                               placeholder="https://..." />
                    </div>
                </div>
            </div>
        </div>

        <!-- Tags -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Tags</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <div class="tags">
                            <span class="tag is-info is-light"><?php
                                echo htmlspecialchars($tagNameStr, ENT_QUOTES, 'UTF-8');
                            ?></span>
                        </div>
                        <input
                            type="hidden"
                            name="feed[<?php echo $count; ?>][Nf_ID]"
                            value="<?php echo $nfId; ?>"
                        />
                        <input
                            type="hidden"
                            name="feed[<?php echo $count; ?>][Nf_Max_Texts]"
                            value="<?php echo $maxTexts; ?>"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Audio URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="text"
                               class="input"
                               name="feed[<?php echo $count; ?>][TxAudioURI]"
                               value="<?php echo htmlspecialchars($text['TxAudioURI'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               placeholder="Path to audio file or URL" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsed state indicator -->
    <div x-show="!isSelected" x-transition class="has-text-grey-light is-italic">
        <span class="icon is-small">
            <?php echo IconHelper::render('eye-off', ['alt' => 'Hidden']); ?>
        </span>
        Text deselected - check the box to include
    </div>
</div>
    <?php
    $count++;
endforeach;
