<?php declare(strict_types=1);
/**
 * Feed Edit Text Form View
 *
 * Renders the form for editing feed items before creating texts.
 *
 * Variables expected:
 * - $texts: array of text data (TxTitle, TxText, TxSourceURI, TxAudioURI)
 * - $row: array feed link and feed data (NfLgID, NfID)
 * - $count: int starting form counter (passed by reference)
 * - $tagName: string tag name for the text
 * - $nfId: int feed ID
 * - $maxTexts: int maximum texts setting
 * - $languages: array of language records
 * - $scrdir: string script direction HTML attribute
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\Feed;

use Lwt\View\Helper\IconHelper;

foreach ($texts as $text):
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
                        <?php echo IconHelper::render('circle-x', ['alt' => 'Required']); ?>
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
                                <?php foreach ($languages as $rowLang): ?>
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
                                  required><?php echo htmlspecialchars($text['TxText'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('circle-x', ['alt' => 'Required']); ?>
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
                            <span class="tag is-info is-light"><?php echo htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <input type="hidden" name="feed[<?php echo $count; ?>][Nf_ID]" value="<?php echo $nfId; ?>" />
                        <input type="hidden" name="feed[<?php echo $count; ?>][Nf_Max_Texts]" value="<?php echo $maxTexts; ?>" />
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
