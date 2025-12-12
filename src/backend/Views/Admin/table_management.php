<?php declare(strict_types=1);
/**
 * Table Set Management View
 *
 * Modern Bulma + Alpine.js version of the table set management page.
 *
 * Variables expected:
 * - $fixedTbpref: bool Whether prefix is fixed in .env
 * - $prefixes: array List of available prefixes
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

namespace Lwt\Views\Admin;

use Lwt\Core\Globals;
use Lwt\View\Helper\IconHelper;

if ($fixedTbpref):
?>
<div class="container">
    <div class="notification is-warning">
        <div class="columns is-vcentered">
            <div class="column is-narrow">
                <span class="icon is-large has-text-warning">
                    <?php echo IconHelper::render('triangle-alert', ['width' => 32, 'height' => 32]); ?>
                </span>
            </div>
            <div class="column">
                <p class="title is-5 mb-2">Features Not Available</p>
                <div class="content">
                    <p><strong>Reason:</strong> <code>DB_TABLE_PREFIX</code> is set to a fixed value in <code>.env</code>.</p>
                    <p>
                        Please remove the definition
                        <code class="has-text-danger has-text-weight-bold">DB_TABLE_PREFIX=<?php echo htmlspecialchars(substr(Globals::getTablePrefix(), 0, -1), ENT_QUOTES, 'UTF-8'); ?></code>
                        in <code>.env</code> to make these features available.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="field">
        <div class="control">
            <a href="/" class="button is-light">
                <?php echo IconHelper::render('arrow-left'); ?>
                <span>Back to Main Menu</span>
            </a>
        </div>
    </div>
</div>
<?php else: ?>
<div class="container" x-data="tableManagementApp()">
    <!-- Select Table Set Card -->
    <div class="card mb-4">
        <header class="card-header">
            <p class="card-header-title">
                <?php echo IconHelper::render('list', ['class' => 'mr-2']); ?>
                Select Table Set
            </p>
        </header>
        <div class="card-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post">
                <div class="field">
                    <label class="label">Table Set</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="prefix" x-model="selectedPrefix">
                                <option value="-">[Choose...]</option>
                                <option value="">Default Table Set</option>
                                <?php foreach ($prefixes as $value): ?>
                                <option value="<?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <div class="control">
                        <button type="submit"
                                name="op"
                                value="Start LWT with selected Table Set"
                                class="button is-primary"
                                :disabled="selectedPrefix === '-'">
                            <?php echo IconHelper::render('play'); ?>
                            <span>Start LWT with selected Table Set</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Create New Table Set Card -->
    <div class="card mb-4">
        <header class="card-header">
            <p class="card-header-title">
                <?php echo IconHelper::render('plus-circle', ['class' => 'mr-2']); ?>
                Create New Table Set
            </p>
        </header>
        <div class="card-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post"
                  @submit="submitCreate($event)">
                <div class="field">
                    <label class="label">New Table Set Name</label>
                    <div class="control">
                        <input type="text"
                               name="newpref"
                               class="input"
                               :class="{ 'is-danger': createError }"
                               x-model="newPrefix"
                               @input="validatePrefix()"
                               maxlength="20"
                               placeholder="Enter table set name (letters, numbers, underscores)" />
                    </div>
                    <p class="help is-danger" x-show="createError" x-text="createError" x-cloak></p>
                    <p class="help" x-show="!createError">
                        Maximum 20 characters. Only letters, numbers, and underscores allowed.
                    </p>
                </div>
                <div class="field">
                    <div class="control">
                        <button type="submit"
                                name="op"
                                value="Create New Table Set &amp; Start LWT"
                                class="button is-success"
                                :disabled="!newPrefix.trim() || createError">
                            <?php echo IconHelper::render('plus'); ?>
                            <span>Create New Table Set &amp; Start LWT</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Table Set Card -->
    <div class="card mb-4">
        <header class="card-header has-background-danger-light">
            <p class="card-header-title has-text-danger">
                <?php echo IconHelper::render('trash-2', ['class' => 'mr-2']); ?>
                Delete Table Set
            </p>
        </header>
        <div class="card-content">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post"
                  @submit="submitDelete($event)">
                <div class="field">
                    <label class="label">Table Set to Delete</label>
                    <div class="control">
                        <div class="select is-fullwidth">
                            <select name="delpref" x-model="deletePrefix">
                                <option value="-">[Choose...]</option>
                                <?php foreach ($prefixes as $value): ?>
                                <?php if ($value != ''): ?>
                                <option value="<?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p class="help">You cannot delete the Default Table Set.</p>
                </div>

                <div class="notification is-danger is-light" x-show="deletePrefix !== '-'" x-cloak>
                    <div class="columns is-vcentered">
                        <div class="column is-narrow">
                            <span class="icon is-medium has-text-danger">
                                <?php echo IconHelper::render('triangle-alert'); ?>
                            </span>
                        </div>
                        <div class="column">
                            <p class="has-text-weight-semibold">Warning: This action cannot be undone!</p>
                            <p class="is-size-7">All data in the selected table set will be permanently deleted.</p>
                        </div>
                    </div>
                </div>

                <div class="field" x-show="deletePrefix !== '-'" x-cloak>
                    <label class="checkbox">
                        <input type="checkbox" x-model="confirmDelete">
                        <span class="has-text-danger has-text-weight-bold">
                            I understand all data in this table set will be permanently deleted
                        </span>
                    </label>
                </div>

                <div class="field">
                    <div class="control">
                        <button type="submit"
                                name="op"
                                value="DELETE Table Set"
                                class="button is-danger"
                                :disabled="deletePrefix === '-' || !confirmDelete">
                            <?php echo IconHelper::render('trash-2'); ?>
                            <span>DELETE Table Set</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Back Button -->
    <div class="field">
        <div class="control">
            <a href="/" class="button is-light">
                <?php echo IconHelper::render('arrow-left'); ?>
                <span>Back to Main Menu</span>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
