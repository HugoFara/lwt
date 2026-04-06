<?php

/**
 * Local Dictionaries Index View
 *
 * Variables expected:
 * - $langId: int current language ID
 * - $langName: string current language name
 * - $dictionaries: array of LocalDictionary entities
 * - $localDictMode: int (0-3)
 * - $languages: array of languages for dropdown
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Dictionary\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\Dictionary\Views;

use Lwt\Modules\Dictionary\Domain\LocalDictionary;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;

/**
 * @var int $langId
 * @var string $langName
 * @var array<LocalDictionary> $dictionaries
 * @var int $localDictMode
 * @var array<array{id: int, name: string}> $languages
 * @var string $message
 * @var string $error
 */

if (!empty($message)) :
    $messageText = match ($message) {
        'deleted' => __('dictionary.deleted_success'),
        default => str_starts_with($message, 'imported_')
            ? __('dictionary.imported_count', ['count' => substr($message, 9)])
            : $message,
    };
    ?>
<div class="notification is-success is-light mb-4">
    <button class="delete" @click="$el.parentElement.remove()"></button>
    <?php echo htmlspecialchars($messageText, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<?php if (!empty($error)) : ?>
<div class="notification is-danger is-light mb-4">
    <button class="delete" @click="$el.parentElement.remove()"></button>
    <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<?php
echo PageLayoutHelper::buildActionCard([
    ['url' => '/languages', 'label' => __('dictionary.languages_link'), 'icon' => 'globe'],
    [
        'url' => '/word/upload?tab=dictionary', 'label' => __('dictionary.import_dictionary'),
        'icon' => 'upload', 'class' => 'is-primary'
    ],
]);
?>

<div class="box mb-4">
    <form method="GET" action="/dictionaries">
        <div class="field has-addons">
            <div class="control is-expanded">
                <div class="select is-fullwidth">
                    <select name="lang" @change="$el.form.submit()">
                        <?php foreach ($languages as $lang) : ?>
                        <option value="<?php echo $lang['id']; ?>"
                            <?php echo $lang['id'] == $langId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lang['name'], ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="control">
                <button type="submit" class="button is-info">
                    <?php echo IconHelper::render('search', ['alt' => __('common.go')]); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Mode Info -->
<div class="box mb-4">
    <h4 class="title is-5 mb-2"><?php echo __('dictionary.local_mode_title'); ?></h4>
    <p class="mb-2">
        <?php echo __('dictionary.current_mode'); ?>
        <span class="tag is-info is-medium">
            <?php
            echo match ($localDictMode) {
                0 => __('dictionary.mode_online'),
                1 => __('dictionary.mode_local_first'),
                2 => __('dictionary.mode_local_only'),
                3 => __('dictionary.mode_combined'),
                default => __('dictionary.mode_unknown'),
            };
            ?>
        </span>
    </p>
    <p class="help">
        <?php echo __('dictionary.mode_change_help'); ?>
        <a href="/languages/<?php echo $langId; ?>/edit#local-dict-mode">
            <?php echo __('dictionary.language_settings'); ?>
        </a>.
    </p>
</div>

<!-- Quick Create -->
<div class="box mb-4">
    <h4 class="title is-5 mb-2"><?php echo __('dictionary.quick_create'); ?></h4>
    <form method="POST" action="/languages/<?php echo $langId; ?>/dictionaries">
        <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
        <div class="field has-addons">
            <div class="control is-expanded">
                <?php
                $createPlaceholder = htmlspecialchars(
                    __('dictionary.dictionary_name_placeholder'),
                    ENT_QUOTES
                );
                ?>
                <input type="text" name="dict_name" class="input"
                       placeholder="<?php echo $createPlaceholder; ?>"
                       required>
            </div>
            <div class="control">
                <button type="submit" name="create_dictionary" value="1" class="button is-primary">
                    <?php echo IconHelper::render('plus', ['alt' => __('common.create')]); ?>
                    <?php echo __('common.create'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Dictionaries List -->
<div class="box">
    <h4 class="title is-5 mb-4">
        <?php
        echo htmlspecialchars(
            __('dictionary.dictionaries_for', ['language' => $langName]),
            ENT_QUOTES
        );
        ?>
    </h4>

    <?php if (empty($dictionaries)) : ?>
    <div class="notification is-light">
        <p><?php echo __('dictionary.no_local_dicts'); ?></p>
        <p class="mt-2">
            <a href="/word/upload?tab=dictionary" class="button is-primary is-small">
                <?php echo IconHelper::render('upload', ['alt' => __('common.import')]); ?>
                <?php echo __('dictionary.import_a_dictionary'); ?>
            </a>
        </p>
    </div>
    <?php else : ?>
    <div class="table-container">
        <table class="table is-fullwidth is-striped is-hoverable">
            <thead>
                <tr>
                    <th><?php echo __('common.name'); ?></th>
                    <th><?php echo __('dictionary.col_format'); ?></th>
                    <th><?php echo __('dictionary.col_entries'); ?></th>
                    <th><?php echo __('dictionary.col_priority'); ?></th>
                    <th><?php echo __('common.status'); ?></th>
                    <th><?php echo __('common.actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dictionaries as $dict) :
                    $description = $dict->description();
                    ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($dict->name(), ENT_QUOTES); ?></strong>
                        <?php if ($description !== null && $description !== '') : ?>
                        <br><span class="is-size-7 has-text-grey"><?php
                            echo htmlspecialchars($description, ENT_QUOTES);
                        ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="tag"><?php echo strtoupper($dict->sourceFormat()); ?></span>
                    </td>
                    <td>
                        <?php echo number_format($dict->entryCount()); ?>
                    </td>
                    <td>
                        <?php echo $dict->priority(); ?>
                    </td>
                    <td>
                        <?php if ($dict->isEnabled()) : ?>
                        <span class="tag is-success"><?php echo __('common.enabled'); ?></span>
                        <?php else : ?>
                        <span class="tag is-warning"><?php echo __('common.disabled'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="buttons are-small">
                            <!-- Toggle enable/disable -->
                            <form method="POST" action="/languages/<?php echo $langId; ?>/dictionaries"
                                  style="display:inline;">
                                <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
                                <input type="hidden" name="dict_id" value="<?php echo $dict->id(); ?>">
                                <?php
                                $toggleTitle = $dict->isEnabled()
                                    ? __('dictionary.toggle_disable')
                                    : __('dictionary.toggle_enable');
                                ?>
                                <button type="submit" name="toggle_enabled" value="1"
                                        class="button <?php echo $dict->isEnabled() ? 'is-warning' : 'is-success'; ?>"
                                        title="<?php echo htmlspecialchars($toggleTitle, ENT_QUOTES); ?>">
                                    <?php
                                    $eyeIcon = $dict->isEnabled() ? 'eye-off' : 'eye';
                                    echo IconHelper::render($eyeIcon, ['alt' => $toggleTitle]);
                                    ?>
                                </button>
                            </form>

                            <!-- Import more entries -->
                            <a href="/word/upload?tab=dictionary"
                               class="button is-info"
                               title="<?php echo htmlspecialchars(__('dictionary.import_entries'), ENT_QUOTES); ?>">
                                <?php echo IconHelper::render('upload', ['alt' => __('common.import')]); ?>
                            </a>

                            <!-- Delete -->
                            <?php
                            $confirmDelete = htmlspecialchars(
                                __('dictionary.confirm_delete_dict'),
                                ENT_QUOTES
                            );
                            ?>
                            <form method="POST" action="/dictionaries/delete" style="display:inline;"
                                  @submit="if(!confirm('<?php echo $confirmDelete; ?>')) $event.preventDefault()">
                                <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
                                <input type="hidden" name="dict_id" value="<?php echo $dict->id(); ?>">
                                <input type="hidden" name="lang_id" value="<?php echo $langId; ?>">
                                <button type="submit" class="button is-danger"
                                        title="<?php echo htmlspecialchars(__('common.delete'), ENT_QUOTES); ?>">
                                    <?php echo IconHelper::render('trash', ['alt' => __('common.delete')]); ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
