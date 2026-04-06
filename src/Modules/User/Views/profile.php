<?php

/**
 * User Profile View
 *
 * Variables expected:
 * - $user: \Lwt\Modules\User\Domain\User The current user
 * - $error: string|null Error message
 * - $success: string|null Success message
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/developer/api
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\User\Views;

use Lwt\Shared\UI\Helpers\FormHelper;

assert(isset($user) && $user instanceof \Lwt\Modules\User\Domain\User);
assert(isset($error) && (is_string($error) || $error === null));
assert(isset($success) && (is_string($success) || $success === null));

$escapedUsername = htmlspecialchars($user->username(), ENT_QUOTES, 'UTF-8');
$escapedEmail = htmlspecialchars($user->email(), ENT_QUOTES, 'UTF-8');

$t = static fn(string $key): string => htmlspecialchars(__('user.' . $key), ENT_QUOTES, 'UTF-8');
?>

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-6-tablet is-5-desktop">

                <?php if ($error !== null) : ?>
                    <div class="notification is-danger">
                        <button class="delete" aria-label="close"></button>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== null) : ?>
                    <div class="notification is-success" data-auto-hide="true">
                        <button class="delete" aria-label="close"></button>
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Info -->
                <div class="box">
                    <h2 class="title is-4">
                        <span class="icon-text">
                            <span class="icon"><i data-lucide="user"></i></span>
                            <span><?php echo $t('profile.section_title'); ?></span>
                        </span>
                    </h2>

                    <?php if ($user->isEmailVerified()) : ?>
                        <div class="notification is-success is-light is-size-7 py-2 px-3 mb-4">
                            <?php echo $t('profile.email_verified'); ?>
                        </div>
                    <?php else : ?>
                        <div class="notification is-warning is-light is-size-7 py-2 px-3 mb-4">
                            <?php echo $t('profile.email_not_verified'); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="/profile">
                        <?= FormHelper::csrfField() ?>

                        <div class="field">
                            <label class="label" for="profile-username">
                                <?php echo $t('profile.username_label'); ?>
                            </label>
                            <div class="control has-icons-left">
                                <input class="input" type="text" id="profile-username"
                                       name="username" value="<?= $escapedUsername ?>"
                                       required minlength="3" maxlength="100"
                                       pattern="[a-zA-Z0-9_-]+">
                                <span class="icon is-small is-left">
                                    <i data-lucide="at-sign"></i>
                                </span>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="profile-email">
                                <?php echo $t('profile.email_label'); ?>
                            </label>
                            <div class="control has-icons-left">
                                <input class="input" type="email" id="profile-email"
                                       name="email" value="<?= $escapedEmail ?>"
                                       required maxlength="255">
                                <span class="icon is-small is-left">
                                    <i data-lucide="mail"></i>
                                </span>
                            </div>
                            <p class="help"><?php echo $t('profile.email_change_help'); ?></p>
                        </div>

                        <div class="field">
                            <div class="control">
                                <button type="submit" class="button is-primary">
                                    <?php echo $t('profile.update_button'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <?php if ($user->hasPassword()) : ?>
                <div class="box">
                    <h2 class="title is-4">
                        <span class="icon-text">
                            <span class="icon"><i data-lucide="lock"></i></span>
                            <span><?php echo $t('profile.change_password_title'); ?></span>
                        </span>
                    </h2>

                    <form method="post" action="/profile/password">
                        <?= FormHelper::csrfField() ?>

                        <div class="field">
                            <label class="label" for="current-password">
                                <?php echo $t('profile.current_password_label'); ?>
                            </label>
                            <div class="control has-icons-left">
                                <input class="input" type="password" id="current-password"
                                       name="current_password" required>
                                <span class="icon is-small is-left">
                                    <i data-lucide="key"></i>
                                </span>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="new-password">
                                <?php echo $t('profile.new_password_label'); ?>
                            </label>
                            <div class="control has-icons-left">
                                <input class="input" type="password" id="new-password"
                                       name="new_password" required minlength="8">
                                <span class="icon is-small is-left">
                                    <i data-lucide="lock"></i>
                                </span>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="confirm-password">
                                <?php echo $t('profile.new_password_confirm_label'); ?>
                            </label>
                            <div class="control has-icons-left">
                                <input class="input" type="password" id="confirm-password"
                                       name="new_password_confirm" required minlength="8">
                                <span class="icon is-small is-left">
                                    <i data-lucide="lock"></i>
                                </span>
                            </div>
                        </div>

                        <div class="field">
                            <div class="control">
                                <button type="submit" class="button is-warning">
                                    <?php echo $t('profile.change_password_button'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Preferences Link -->
                <div class="box">
                    <h2 class="title is-4">
                        <span class="icon-text">
                            <span class="icon"><i data-lucide="sliders-horizontal"></i></span>
                            <span><?php echo $t('profile.preferences_title'); ?></span>
                        </span>
                    </h2>
                    <p class="mb-3"><?php echo $t('profile.preferences_description'); ?></p>
                    <a href="/profile/preferences" class="button is-primary is-outlined">
                        <span class="icon"><i data-lucide="settings"></i></span>
                        <span><?php echo $t('profile.edit_preferences_button'); ?></span>
                    </a>
                </div>

                <!-- Account Info -->
                <div class="box">
                    <h2 class="title is-5 mb-3"><?php echo $t('profile.account_info_title'); ?></h2>
                    <div class="content is-small">
                        <p>
                            <strong><?php echo $t('profile.role_label'); ?></strong>
                            <?= htmlspecialchars($user->role(), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p>
                            <strong><?php echo $t('profile.member_since_label'); ?></strong>
                            <?= $user->created()->format('F j, Y') ?>
                        </p>
                        <?php $lastLogin = $user->lastLogin(); ?>
                        <?php if ($lastLogin !== null) : ?>
                            <p>
                                <strong><?php echo $t('profile.last_login_label'); ?></strong>
                                <?= $lastLogin->format('F j, Y g:i A') ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
