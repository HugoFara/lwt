<?php declare(strict_types=1);
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
 * @package  Lwt\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Views\LocalDictionary;

use Lwt\View\Helper\IconHelper;
use Lwt\View\Helper\PageLayoutHelper;

// Display messages
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

if (!empty($message)):
    $messageText = match ($message) {
        'deleted' => 'Dictionary deleted successfully.',
        default => str_starts_with($message, 'imported_')
            ? 'Imported ' . substr($message, 9) . ' entries.'
            : $message,
    };
?>
<div class="notification is-success is-light mb-4">
    <button class="delete" onclick="this.parentElement.remove()"></button>
    <?php echo htmlspecialchars($messageText, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="notification is-danger is-light mb-4">
    <button class="delete" onclick="this.parentElement.remove()"></button>
    <?php echo htmlspecialchars($error, ENT_QUOTES); ?>
</div>
<?php endif; ?>

<?php
echo PageLayoutHelper::buildActionCard([
    ['url' => '/languages', 'label' => 'Languages', 'icon' => 'globe'],
    ['url' => '/dictionaries/import?lang=' . $langId, 'label' => 'Import Dictionary', 'icon' => 'upload', 'class' => 'is-primary'],
]);
?>

<div class="box mb-4">
    <form method="GET" action="/dictionaries">
        <div class="field has-addons">
            <div class="control is-expanded">
                <div class="select is-fullwidth">
                    <select name="lang" onchange="this.form.submit()">
                        <?php foreach ($languages as $lang): ?>
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
                    <?php echo IconHelper::render('search', ['alt' => 'Go']); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Mode Info -->
<div class="box mb-4">
    <h4 class="title is-5 mb-2">Local Dictionary Mode</h4>
    <p class="mb-2">
        Current mode:
        <span class="tag is-info is-medium">
            <?php
            echo match ($localDictMode) {
                0 => 'Online dictionaries only',
                1 => 'Local first, online fallback',
                2 => 'Local dictionaries only',
                3 => 'Combined (show both)',
                default => 'Unknown',
            };
            ?>
        </span>
    </p>
    <p class="help">
        You can change the mode in the
        <a href="/languages?edit=<?php echo $langId; ?>#local-dict-mode">language settings</a>.
    </p>
</div>

<!-- Quick Create -->
<div class="box mb-4">
    <h4 class="title is-5 mb-2">Quick Create Dictionary</h4>
    <form method="POST" action="/dictionaries?lang=<?php echo $langId; ?>">
        <div class="field has-addons">
            <div class="control is-expanded">
                <input type="text" name="dict_name" class="input" placeholder="Dictionary name..." required>
            </div>
            <div class="control">
                <button type="submit" name="create_dictionary" value="1" class="button is-primary">
                    <?php echo IconHelper::render('plus', ['alt' => 'Create']); ?>
                    Create
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Dictionaries List -->
<div class="box">
    <h4 class="title is-5 mb-4">Dictionaries for <?php echo htmlspecialchars($langName, ENT_QUOTES); ?></h4>

    <?php if (empty($dictionaries)): ?>
    <div class="notification is-light">
        <p>No local dictionaries found for this language.</p>
        <p class="mt-2">
            <a href="/dictionaries/import?lang=<?php echo $langId; ?>" class="button is-primary is-small">
                <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                Import a dictionary
            </a>
        </p>
    </div>
    <?php else: ?>
    <div class="table-container">
        <table class="table is-fullwidth is-striped is-hoverable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Format</th>
                    <th>Entries</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dictionaries as $dict): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($dict->name(), ENT_QUOTES); ?></strong>
                        <?php if ($dict->description()): ?>
                        <br><span class="is-size-7 has-text-grey"><?php echo htmlspecialchars($dict->description(), ENT_QUOTES); ?></span>
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
                        <?php if ($dict->isEnabled()): ?>
                        <span class="tag is-success">Enabled</span>
                        <?php else: ?>
                        <span class="tag is-warning">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="buttons are-small">
                            <!-- Toggle enable/disable -->
                            <form method="POST" action="/dictionaries?lang=<?php echo $langId; ?>" style="display:inline;">
                                <input type="hidden" name="dict_id" value="<?php echo $dict->id(); ?>">
                                <button type="submit" name="toggle_enabled" value="1"
                                        class="button <?php echo $dict->isEnabled() ? 'is-warning' : 'is-success'; ?>"
                                        title="<?php echo $dict->isEnabled() ? 'Disable' : 'Enable'; ?>">
                                    <?php echo IconHelper::render($dict->isEnabled() ? 'eye-off' : 'eye', ['alt' => 'Toggle']); ?>
                                </button>
                            </form>

                            <!-- Import more entries -->
                            <a href="/dictionaries/import?lang=<?php echo $langId; ?>&dict_id=<?php echo $dict->id(); ?>"
                               class="button is-info" title="Import entries">
                                <?php echo IconHelper::render('upload', ['alt' => 'Import']); ?>
                            </a>

                            <!-- Delete -->
                            <form method="POST" action="/dictionaries/delete" style="display:inline;"
                                  onsubmit="return confirm('Delete this dictionary and all its entries?');">
                                <input type="hidden" name="dict_id" value="<?php echo $dict->id(); ?>">
                                <input type="hidden" name="lang_id" value="<?php echo $langId; ?>">
                                <button type="submit" class="button is-danger" title="Delete">
                                    <?php echo IconHelper::render('trash', ['alt' => 'Delete']); ?>
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
