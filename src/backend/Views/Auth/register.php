<?php declare(strict_types=1);
/**
 * Registration Form View
 *
 * Variables expected:
 * - $error: string|null Error message to display
 * - $username: string Pre-filled username
 * - $email: string Pre-filled email
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

namespace Lwt\Views\Auth;

// Default variables
$error = $error ?? null;
$username = $username ?? '';
$email = $email ?? '';
?>

<section class="section">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-5-tablet is-4-desktop">
                <div class="box">
                    <!-- Logo/Title -->
                    <div class="has-text-centered mb-5">
                        <h1 class="title is-3">
                            <span class="icon-text">
                                <span class="icon has-text-primary">
                                    <i data-lucide="book-open"></i>
                                </span>
                                <span>LWT</span>
                            </span>
                        </h1>
                        <p class="subtitle is-6 has-text-grey">
                            Create Your Account
                        </p>
                    </div>

                    <!-- Error message -->
                    <?php if ($error !== null && $error !== ''): ?>
                    <div class="notification is-danger is-light">
                        <button class="delete" onclick="this.parentElement.remove()"></button>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Registration form -->
                    <form method="POST" action="/register" x-data="registerForm()" @submit="submitForm($event)">
                        <div class="field">
                            <label class="label" for="username">Username</label>
                            <div class="control has-icons-left has-icons-right">
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="input"
                                    :class="{ 'is-danger': errors.username }"
                                    placeholder="Choose a username"
                                    value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                    minlength="3"
                                    maxlength="100"
                                    pattern="[a-zA-Z0-9_-]+"
                                    @blur="validateUsername($event.target.value)"
                                    autofocus
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="user"></i>
                                </span>
                                <span class="icon is-small is-right" x-show="errors.username">
                                    <i data-lucide="alert-circle" class="has-text-danger"></i>
                                </span>
                            </div>
                            <p class="help is-danger" x-show="errors.username" x-text="errors.username"></p>
                            <p class="help" x-show="!errors.username">
                                3-100 characters, letters, numbers, underscores, and hyphens only
                            </p>
                        </div>

                        <div class="field">
                            <label class="label" for="email">Email</label>
                            <div class="control has-icons-left has-icons-right">
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="input"
                                    :class="{ 'is-danger': errors.email }"
                                    placeholder="Enter your email address"
                                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                    @blur="validateEmail($event.target.value)"
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="mail"></i>
                                </span>
                                <span class="icon is-small is-right" x-show="errors.email">
                                    <i data-lucide="alert-circle" class="has-text-danger"></i>
                                </span>
                            </div>
                            <p class="help is-danger" x-show="errors.email" x-text="errors.email"></p>
                        </div>

                        <div class="field">
                            <label class="label" for="password">Password</label>
                            <div class="control has-icons-left has-icons-right">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="input"
                                    :class="{ 'is-danger': errors.password }"
                                    placeholder="Create a password"
                                    required
                                    minlength="8"
                                    maxlength="128"
                                    x-model="password"
                                    @input="validatePassword()"
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
                            <label class="label" for="password_confirm">Confirm Password</label>
                            <div class="control has-icons-left has-icons-right">
                                <input
                                    type="password"
                                    id="password_confirm"
                                    name="password_confirm"
                                    class="input"
                                    :class="{ 'is-danger': errors.passwordConfirm }"
                                    placeholder="Confirm your password"
                                    required
                                    x-model="passwordConfirm"
                                    @input="validatePasswordConfirm()"
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="lock"></i>
                                </span>
                                <span class="icon is-small is-right" x-show="passwordConfirm && !errors.passwordConfirm">
                                    <i data-lucide="check" class="has-text-success"></i>
                                </span>
                                <span class="icon is-small is-right" x-show="errors.passwordConfirm">
                                    <i data-lucide="alert-circle" class="has-text-danger"></i>
                                </span>
                            </div>
                            <p class="help is-danger" x-show="errors.passwordConfirm" x-text="errors.passwordConfirm"></p>
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
                                        <i data-lucide="user-plus"></i>
                                    </span>
                                    <span>Create Account</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Login link -->
                    <hr>
                    <p class="has-text-centered">
                        Already have an account?
                        <a href="/login">Log in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
