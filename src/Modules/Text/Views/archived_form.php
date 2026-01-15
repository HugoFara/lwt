<?php

/**
 * Archived Text Edit Form View
 *
 * Variables expected:
 * - $textId: int - Archived text ID (same as TxID, but with TxArchivedAt IS NOT NULL)
 * - $record: array - Archived text record with keys: TxLgID, TxTitle, TxText, TxAudioURI, TxSourceURI, annotlen
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
 * @psalm-suppress UndefinedVariable - Variables are set by the including controller
 */

declare(strict_types=1);

namespace Lwt\Views\Text;

use Lwt\Modules\Admin\Application\Services\MediaService;
use Lwt\Modules\Tags\Application\TagsFacade;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\SearchableSelectHelper;

// Form action URL - posts back to the same RESTful route

// Type-safe variable extraction from controller context
/**
 * @var int
*/
$textIdTyped = $textId;
assert(is_array($record));
$recordLgId = (int)$record['TxLgID'];
$recordTitle = (string)$record['TxTitle'];
$recordText = (string)$record['TxText'];
$recordAnnotLen = (int)$record['annotlen'];
$recordSourceUri = (string)$record['TxSourceURI'];
$recordAudioUri = (string)$record['TxAudioURI'];
assert(is_array($languages));
/**
 * @var array<int, array{id: int, name: string}>
*/
$languagesTyped = $languages;
?>
<h2 class="title is-4">Edit Archived Text</h2>

<form class="validate" action="/text/archived/<?php echo $textIdTyped; ?>/edit#rec<?php echo $textIdTyped; ?>" method="post">
    <input type="hidden" name="TxID" value="<?php echo $textIdTyped; ?>" />

    <div class="box">
        <!-- Language -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxLgID">Language</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <?php
                        echo SearchableSelectHelper::forLanguages(
                            $languagesTyped,
                            $recordLgId,
                            [
                                'name' => 'TxLgID',
                                'id' => 'TxLgID',
                                'placeholder' => '[Choose...]',
                                'required' => true
                            ]
                        );
                        ?>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Title -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxTitle">Title</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input notempty checkoutsidebmp"
                               data_info="Title"
                               name="TxTitle"
                               id="TxTitle"
                               value="<?php echo htmlspecialchars($recordTitle, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
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

        <!-- Text Content -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxText">Text</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <textarea name="TxText"
                                  id="TxText"
                                  class="textarea notempty checkbytes checkoutsidebmp"
                                  data_maxlength="65000"
                                  data_info="Text"
                                  rows="15"
                                  required><?php echo htmlspecialchars($recordText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="control">
                        <span class="icon has-text-danger" title="Field must not be empty">
                            <?php echo IconHelper::render('asterisk', ['alt' => 'Required']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Annotated Text -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label">Annotated Text</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <?php if ($recordAnnotLen > 0) : ?>
                        <div class="notification is-info is-light">
                            <span class="icon-text">
                                <span class="icon has-text-success">
                                    <?php echo IconHelper::render('check', ['alt' => 'Has Annotation']); ?>
                                </span>
                                <span>Exists - May be partially or fully lost if you change the text!</span>
                            </span>
                        </div>
                        <?php else : ?>
                        <div class="notification is-light">
                            <span class="icon-text">
                                <span class="icon has-text-grey">
                                    <?php echo IconHelper::render('x', ['alt' => 'No Annotation']); ?>
                                </span>
                                <span>None</span>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Source URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxSourceURI">Source URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="url"
                               class="input checkurl checkoutsidebmp"
                               data_info="Source URI"
                               name="TxSourceURI"
                               id="TxSourceURI"
                               value="<?php echo htmlspecialchars($recordSourceUri, ENT_QUOTES, 'UTF-8'); ?>"
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
                        <?php echo TagsFacade::getArchivedTextTagsHtml($textIdTyped); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="TxAudioURI">Audio URI</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Audio-URI"
                               name="TxAudioURI"
                               id="TxAudioURI"
                               value="<?php echo htmlspecialchars($recordAudioUri, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               placeholder="Path to audio file or URL" />
                    </div>
                    <div class="control" id="mediaselect">
                        <?php echo (new MediaService())->getMediaPathSelector('TxAudioURI'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="field is-grouped is-grouped-right">
        <div class="control">
            <button type="button"
                    class="button is-light"
                    data-action="cancel-navigate"
                    data-url="/text/archived#rec<?php echo $textIdTyped; ?>">
                Cancel
            </button>
        </div>
        <div class="control">
            <button type="submit" name="op" value="Change" class="button is-primary">
                <span class="icon is-small">
                    <?php echo IconHelper::render('save', ['alt' => 'Save']); ?>
                </span>
                <span>Save Changes</span>
            </button>
        </div>
    </div>
</form>
