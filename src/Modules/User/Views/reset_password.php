<?php

/**
 * Reset Password Form View
 *
 * Variables expected:
 * - $token: string The reset token
 * - $error: string|null Error message to display
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

declare(strict_types=1);

namespace Lwt\Modules\User\Views;

// Default variables - variables are extracted from controller, may not be set
/** @psalm-suppress TypeDoesNotContainNull */
$token = isset($token) && is_string($token) ? $token : '';
$error = isset($error) && is_string($error) ? $error : null;
?>

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-5-tablet is-4-desktop">
                <div class="box">
                    <!-- Title -->
                    <div class="has-text-centered mb-5">
                        <h1 class="title is-3">
                            <span class="icon-text">
                                <span class="icon has-text-primary">
                                    <i data-lucide="lock"></i>
                                </span>
                                <span>Reset Password</span>
                            </span>
                        </h1>
                        <p class="subtitle is-6 has-text-grey">
                            Enter your new password
                        </p>
                    </div>

                    <!-- Error message -->
                    <?php if ($error !== null) : ?>
                    <div class="notification is-danger is-light">
                        <button class="delete" @click="$el.parentElement.remove()"></button>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Reset password form -->
                    <form
                        method="POST"
                        action="/password/reset"
                        x-data="resetPasswordForm()"
                        @submit="submitForm($event)"
                    >
                        <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
                        <input
                            type="hidden"
                            name="token"
                            value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>"
                        >

                        <div class="field">
                            <label class="label" for="password">New Password</label>
                            <div class="control has-icons-left has-icons-right">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="input"
                                    :class="{ 'is-danger': errors.password }"
                                    placeholder="Enter new password"
                                    required
                                    minlength="8"
                                    maxlength="128"
                                    x-model="password"
                                    @input="validatePassword()"
                                    autofocus
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="lock"></i>
                                </span>
                                <span class="icon is-small is-right" x-show="errors.password">
                                    <i data-lucide="alert-circle" class="has-text-danger"></i>
                                </span>
                            </div>
                            <p class="help is-danger" x-show="errors.password" x-text="errors.password"></p>
                            <p class="help" x-show="!errors.password">
                                At least 8 characters with at least one letter and one number
                            </p>
                        </div>

                        <div class="field">
                            <label class="label" for="password_confirm">Confirm New Password</label>
                            <div class="control has-icons-left has-icons-right">
                                <input
                                    type="password"
                                    id="password_confirm"
                                    name="password_confirm"
                                    class="input"
                                    :class="{ 'is-danger': errors.passwordConfirm }"
                                    placeholder="Confirm new password"
                                    required
                                    x-model="passwordConfirm"
                                    @input="validatePasswordConfirm()"
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="lock"></i>
                                </span>
                                <span
                                    class="icon is-small is-right"
                                    x-show="passwordConfirm && !errors.passwordConfirm"
                                >
                                    <i data-lucide="check" class="has-text-success"></i>
                                </span>
                                <span class="icon is-small is-right" x-show="errors.passwordConfirm">
                                    <i data-lucide="alert-circle" class="has-text-danger"></i>
                                </span>
                            </div>
                            <p
                                class="help is-danger"
                                x-show="errors.passwordConfirm"
                                x-text="errors.passwordConfirm"
                            ></p>
                        </div>

                        <div class="field">
                            <div class="control">
                                <button
                                    type="submit"
                                    class="button is-primary is-fullwidth"
                                    :class="{ 'is-loading': loading }"
                                    :disabled="loading || hasErrors"
                                >
                                    <span class="icon">
                                        <i data-lucide="check"></i>
                                    </span>
                                    <span>Reset Password</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <hr>

                    <!-- Back to login link -->
                    <p class="has-text-centered">
                        <a href="/login">
                            <span class="icon-text">
                                <span class="icon">
                                    <i data-lucide="arrow-left"></i>
                                </span>
                                <span>Back to Login</span>
                            </span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
