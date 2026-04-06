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
/** @var array<string, string> $formData */
$formData = $formData ?? [];
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
            <?= $isEdit ? __('admin.user_form_edit_title') : __('admin.user_form_create_title') ?>
        </h2>

        <form method="post" action="<?php echo htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo FormHelper::csrfField(); ?>

            <!-- Username -->
            <div class="field">
                <label class="label" for="username"><?= __('admin.user_form_username') ?></label>
                <div class="control has-icons-left">
                    <input class="input" type="text" id="username" name="username"
                           value="<?php echo htmlspecialchars($formData['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required minlength="3" maxlength="100"
                           pattern="[a-zA-Z0-9_-]+"
                           placeholder="<?= htmlspecialchars(
                               __('admin.user_form_username_placeholder'),
                               ENT_QUOTES,
                               'UTF-8'
                           ) ?>">
                    <span class="icon is-small is-left">
                        <?php echo IconHelper::render('user', ['class' => 'icon']); ?>
                    </span>
                </div>
            </div>

            <!-- Email -->
            <div class="field">
                <label class="label" for="email"><?= __('admin.user_form_email') ?></label>
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
                    <?= __('admin.user_form_password') ?>
                    <?php if ($isEdit) : ?>
                        <span class="has-text-grey has-text-weight-normal">
                            <?= __('admin.user_form_password_keep') ?>
                        </span>
                    <?php endif; ?>
                </label>
                <?php
                    $pwPlaceholder = $isEdit
                        ? __('admin.user_form_password_placeholder_edit')
                        : __('admin.user_form_password_placeholder_new');
                ?>
                <div class="control has-icons-left">
                    <input class="input" type="password" id="password" name="password"
                           <?php echo $isEdit ? '' : 'required'; ?>
                           minlength="8"
                           placeholder="<?= htmlspecialchars($pwPlaceholder, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="icon is-small is-left">
                        <?php echo IconHelper::render('lock', ['class' => 'icon']); ?>
                    </span>
                </div>
            </div>

            <!-- Role -->
            <div class="field">
                <label class="label" for="role"><?= __('admin.user_form_role') ?></label>
                <div class="control">
                    <div class="select">
                        <select id="role" name="role" <?php echo $isSelf ? 'disabled' : ''; ?>>
                            <?php $roleValue = $formData['role'] ?? 'user'; ?>
                            <option value="user" <?= $roleValue === 'user' ? 'selected' : '' ?>>
                                <?= __('admin.user_form_role_user') ?>
                            </option>
                            <option value="admin" <?= $roleValue === 'admin' ? 'selected' : '' ?>>
                                <?= __('admin.user_form_role_admin') ?>
                            </option>
                        </select>
                    </div>
                    <?php if ($isSelf) : ?>
                        <input type="hidden" name="role" value="admin">
                        <p class="help"><?= __('admin.user_form_role_self_help') ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active -->
            <div class="field">
                <div class="control">
                    <label class="checkbox">
                        <input type="checkbox" name="is_active" value="1"
                               <?php echo ($formData['is_active'] ?? '1') === '1' ? 'checked' : ''; ?>
                               <?php echo $isSelf ? 'disabled' : ''; ?>>
                        <?= __('admin.user_form_active') ?>
                    </label>
                    <?php if ($isSelf) : ?>
                        <input type="hidden" name="is_active" value="1">
                        <p class="help"><?= __('admin.user_form_active_self_help') ?></p>
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
                <label class="label"><?= __('admin.user_form_oauth_label') ?></label>
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
                <label class="label"><?= __('admin.user_form_account_info') ?></label>
                <div class="content is-small">
                    <p>
                        <strong><?= __('admin.user_form_created') ?></strong>
                        <?php echo htmlspecialchars($user->created()->format('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8'); ?>
                        <br>
                        <strong><?= __('admin.user_form_last_login') ?></strong>
                        <?php
                        $lastLogin = $user->lastLogin();
                        echo $lastLogin !== null
                            ? htmlspecialchars($lastLogin->format('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8')
                            : '<em>' . __('admin.user_form_never') . '</em>';
                        ?>
                        <br>
                        <strong><?= __('admin.user_form_has_password') ?></strong>
                        <?= $user->hasPassword() ? __('admin.user_form_yes') : __('admin.user_form_no_oauth') ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Buttons -->
            <div class="field is-grouped">
                <div class="control">
                    <button class="button is-primary" type="submit">
                        <?= $isEdit ? __('admin.user_form_save_changes') : __('admin.user_form_create') ?>
                    </button>
                </div>
                <div class="control">
                    <a class="button is-light"
                       href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users">
                        <?= __('admin.user_form_cancel') ?>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
