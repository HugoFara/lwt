<?php

declare(strict_types=1);

/**
 * Login Form View
 *
 * Variables expected:
 * - $error: string|null Error message to display
 * - $username: string Pre-filled username
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
                            Learning With Texts
                        </p>
                    </div>

                    <!-- Error message -->
                    <?php if ($error !== null && $error !== '') : ?>
                    <div class="notification is-danger is-light">
                        <button class="delete" onclick="this.parentElement.remove()"></button>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Login form -->
                    <form method="POST" action="/login" x-data="{ loading: false }" @submit="loading = true">
                        <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>
                        <div class="field">
                            <label class="label" for="username">Username or Email</label>
                            <div class="control has-icons-left">
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    class="input"
                                    placeholder="Enter your username or email"
                                    value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                    autofocus
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="user"></i>
                                </span>
                            </div>
                        </div>

                        <div class="field">
                            <label class="label" for="password">Password</label>
                            <div class="control has-icons-left">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="input"
                                    placeholder="Enter your password"
                                    required
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="lock"></i>
                                </span>
                            </div>
                        </div>

                        <div class="field">
                            <label class="checkbox">
                                <input type="checkbox" name="remember" value="1">
                                Remember me
                            </label>
                        </div>

                        <div class="field">
                            <div class="control">
                                <button
                                    type="submit"
                                    class="button is-primary is-fullwidth"
                                    :class="{ 'is-loading': loading }"
                                    :disabled="loading"
                                >
                                    <span class="icon">
                                        <i data-lucide="log-in"></i>
                                    </span>
                                    <span>Log In</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Registration link -->
                    <hr>
                    <p class="has-text-centered">
                        Don't have an account?
                        <a href="/register">Create one</a>
                    </p>

                    <!-- WordPress login link (if available) -->
                    <p class="has-text-centered mt-3">
                        <a href="/wordpress/start" class="has-text-grey">
                            <span class="icon-text">
                                <span class="icon is-small">
                                    <i data-lucide="external-link"></i>
                                </span>
                                <span>Login with WordPress</span>
                            </span>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
