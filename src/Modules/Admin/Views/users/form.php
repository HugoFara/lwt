<?php

declare(strict_types=1);

namespace Lwt\Views\Admin;

use Lwt\Shared\UI\Helpers\FormHelper;
use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Modules\User\Domain\User;

/** @var bool $isEdit */
$isEdit = $isEdit ?? false;
/** @var \Lwt\Modules\User\Domain\User|null $user */
$user = $user ?? null;
/** @var array $formData */
$formData = $formData ?? [];
/** @var array $errors */
$errors = $errors ?? [];
/** @var int|null $currentAdminId */
$currentAdminId = $currentAdminId ?? null;

$base = UrlUtilities::getBasePath();
$isSelf = $isEdit && $user !== null && $user->id()->toInt() === $currentAdminId;

$formAction = $isEdit && $user !== null
    ? $base . '/admin/users/' . $user->id()->toInt() . '/edit'
    : $base . '/admin/users/new';
?>

<div class="container">
    <div class="box">
        <h2 class="title is-4">
            <?php echo $isEdit ? 'Edit User' : 'Create New User'; ?>
        </h2>

        <form method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo FormHelper::csrfField(); ?>

            <!-- Username -->
            <div class="field">
                <label class="label" for="username">Username</label>
                <div class="control has-icons-left">
                    <input class="input" type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($formData['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required minlength="3" maxlength="100"
                           pattern="[a-zA-Z0-9_-]+"
                           placeholder="Letters, numbers, underscores, hyphens">
                    <span class="icon is-small is-left">
                        <?php echo IconHelper::render('user', ['class' => 'icon']); ?>
                    </span>
                </div>
            </div>

            <!-- Email -->
            <div class="field">
                <label class="label" for="email">Email</label>
                <div class="control has-icons-left">
                    <input class="input" type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($formData['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required maxlength="255"
                           placeholder="user@example.com">
                    <span class="icon is-small is-left">
                        <?php echo IconHelper::render('mail', ['class' => 'icon']); ?>
                    </span>
                </div>
            </div>

            <!-- Password -->
            <div class="field">
                <label class="label" for="password">
                    Password
                    <?php if ($isEdit) : ?>
                        <span class="has-text-grey has-text-weight-normal">(leave blank to keep current)</span>
                    <?php endif; ?>
                </label>
                <div class="control has-icons-left">
                    <input class="input" type="password" id="password" name="password"
                           <?php echo $isEdit ? '' : 'required'; ?>
                           minlength="8"
                           placeholder="<?php echo $isEdit ? 'Leave blank to keep current password' : 'Minimum 8 characters'; ?>">
                    <span class="icon is-small is-left">
                        <?php echo IconHelper::render('lock', ['class' => 'icon']); ?>
                    </span>
                </div>
            </div>

            <!-- Role -->
            <div class="field">
                <label class="label" for="role">Role</label>
                <div class="control">
                    <div class="select">
                        <select id="role" name="role" <?php echo $isSelf ? 'disabled' : ''; ?>>
                            <option value="user" <?php echo ($formData['role'] ?? 'user') === 'user' ? 'selected' : ''; ?>>
                                User
                            </option>
                            <option value="admin" <?php echo ($formData['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>
                                Admin
                            </option>
                        </select>
                    </div>
                    <?php if ($isSelf) : ?>
                        <input type="hidden" name="role" value="admin">
                        <p class="help">You cannot change your own role.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active -->
            <div class="field">
                <div class="control">
                    <label class="checkbox">
                        <input type="checkbox" name="is_active" value="1"
                               <?php echo ($formData['is_active'] ?? true) ? 'checked' : ''; ?>
                               <?php echo $isSelf ? 'disabled' : ''; ?>>
                        Active
                    </label>
                    <?php if ($isSelf) : ?>
                        <input type="hidden" name="is_active" value="1">
                        <p class="help">You cannot deactivate your own account.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isEdit && $user !== null) : ?>
            <!-- OAuth Providers (read-only) -->
                <?php
                $providers = [];
                if ($user->isLinkedToGoogle()) {
                    $providers[] = 'Google';
                }
                if ($user->isLinkedToMicrosoft()) {
                    $providers[] = 'Microsoft';
                }
                if ($user->isLinkedToWordPress()) {
                    $providers[] = 'WordPress';
                }
                ?>
                <?php if (!empty($providers)) : ?>
            <div class="field">
                <label class="label">Linked OAuth Providers</label>
                <div class="control">
                    <div class="tags">
                        <?php foreach ($providers as $provider) : ?>
                            <span class="tag is-info is-light">
                                <?php echo htmlspecialchars($provider, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
                <?php endif; ?>

            <!-- Metadata (read-only) -->
            <div class="field">
                <label class="label">Account Info</label>
                <div class="content is-small">
                    <p>
                        <strong>Created:</strong>
                        <?php echo htmlspecialchars($user->created()->format('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?>
                        <br>
                        <strong>Last Login:</strong>
                        <?php echo $user->lastLogin() !== null
                            ? htmlspecialchars($user->lastLogin()->format('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8')
                            : '<em>Never</em>'; ?>
                        <br>
                        <strong>Has Password:</strong>
                        <?php echo $user->hasPassword() ? 'Yes' : 'No (OAuth only)'; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Buttons -->
            <div class="field is-grouped">
                <div class="control">
                    <button class="button is-primary" type="submit">
                        <?php echo $isEdit ? 'Save Changes' : 'Create User'; ?>
                    </button>
                </div>
                <div class="control">
                    <a class="button is-light"
                       href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users">
                        Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
