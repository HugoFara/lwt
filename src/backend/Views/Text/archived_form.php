<?php declare(strict_types=1);
/**
 * Archived Text Edit Form View
 *
 * Variables expected:
 * - $textId: int - Archived text ID
 * - $record: array - Archived text record with keys: AtLgID, AtTitle, AtText, AtAudioURI, AtSourceURI, annotlen
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
 * @psalm-suppress UndefinedVariable, PossiblyUndefinedVariable - Variables are set by the including controller
 */

namespace Lwt\Views\Text;

use Lwt\Modules\Admin\Application\Services\MediaService;
use Lwt\Shared\UI\Helpers\IconHelper;

// Type assertions for view variables
$textId = (int) ($textId ?? 0);
/** @var array $recordInput */
$recordInput = $record ?? [];
/** @var array<string, mixed> $recordMerged */
$recordMerged = array_merge([
    'AtLgID' => 0, 'AtTitle' => '', 'AtText' => '',
    'AtAudioURI' => '', 'AtSourceURI' => '', 'annotlen' => 0
], $recordInput);
// Extract typed values
$recordAtLgId = (int) $recordMerged['AtLgID'];
$recordAtTitle = (string) $recordMerged['AtTitle'];
$recordAtText = (string) $recordMerged['AtText'];
$recordAtAudioUri = (string) $recordMerged['AtAudioURI'];
$recordAtSourceUri = (string) $recordMerged['AtSourceURI'];
$recordAnnotLen = (int) $recordMerged['annotlen'];
/** @var array<int, array{id: int, name: string}> $languages */
$languages = $languages ?? [];

$phpSelf = $_SERVER['PHP_SELF'] ?? '';
?>
<h2 class="title is-4">Edit Archived Text</h2>

<form class="validate" action="<?php echo $phpSelf; ?>#rec<?php echo $textId; ?>" method="post">
    <input type="hidden" name="AtID" value="<?php echo $textId; ?>" />

    <div class="box">
        <!-- Language -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="AtLgID">Language</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <div class="select is-fullwidth">
                            <select name="AtLgID" id="AtLgID" class="notempty setfocus" required>
                                <?php echo \Lwt\Shared\UI\Helpers\SelectOptionsBuilder::forLanguages($languages, $recordAtLgId, '[Choose...]'); ?>
                            </select>
                        </div>
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
                <label class="label" for="AtTitle">Title</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input notempty checkoutsidebmp"
                               data_info="Title"
                               name="AtTitle"
                               id="AtTitle"
                               value="<?php echo htmlspecialchars($recordAtTitle, ENT_QUOTES, 'UTF-8'); ?>"
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
                <label class="label" for="AtText">Text</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <textarea name="AtText"
                                  id="AtText"
                                  class="textarea notempty checkbytes checkoutsidebmp"
                                  data_maxlength="65000"
                                  data_info="Text"
                                  rows="15"
                                  required><?php echo htmlspecialchars($recordAtText, ENT_QUOTES, 'UTF-8'); ?></textarea>
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
                        <?php if ($recordAnnotLen > 0): ?>
                        <div class="notification is-info is-light">
                            <span class="icon-text">
                                <span class="icon has-text-success">
                                    <?php echo IconHelper::render('check', ['alt' => 'Has Annotation']); ?>
                                </span>
                                <span>Exists - May be partially or fully lost if you change the text!</span>
                            </span>
                        </div>
                        <?php else: ?>
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
                <label class="label" for="AtSourceURI">Source URI</label>
            </div>
            <div class="field-body">
                <div class="field">
                    <div class="control">
                        <input type="url"
                               class="input checkurl checkoutsidebmp"
                               data_info="Source URI"
                               name="AtSourceURI"
                               id="AtSourceURI"
                               value="<?php echo htmlspecialchars($recordAtSourceUri, ENT_QUOTES, 'UTF-8'); ?>"
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
                        <?php
                        echo (string) getArchivedTextTags($textId);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audio URI -->
        <div class="field is-horizontal">
            <div class="field-label is-normal">
                <label class="label" for="AtAudioURI">Audio URI</label>
            </div>
            <div class="field-body">
                <div class="field has-addons">
                    <div class="control is-expanded">
                        <input type="text"
                               class="input checkoutsidebmp"
                               data_info="Audio-URI"
                               name="AtAudioURI"
                               id="AtAudioURI"
                               value="<?php echo htmlspecialchars($recordAtAudioUri, ENT_QUOTES, 'UTF-8'); ?>"
                               maxlength="200"
                               placeholder="Path to audio file or URL" />
                    </div>
                    <div class="control" id="mediaselect">
                        <?php echo (new MediaService())->getMediaPathSelector('AtAudioURI'); ?>
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
                    data-url="/text/archived#rec<?php echo $textId; ?>">
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
