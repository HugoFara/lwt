<?php

declare(strict_types=1);

/**
 * Forgot Password Form View
 *
 * Variables expected:
 * - $error: string|null Error message to display
 * - $success: string|null Success message to display
 * - $email: string Pre-filled email
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

namespace Lwt\Modules\User\Views;

// Validate injected variables from controller
assert(isset($email) && is_string($email));
assert(isset($error) && (is_string($error) || $error === null));
assert(isset($success) && (is_string($success) || $success === null));
/** @var string|null $error */
/** @var string|null $success */
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
                                    <i data-lucide="key"></i>
                                </span>
                                <span>Forgot Password</span>
                            </span>
                        </h1>
                        <p class="subtitle is-6 has-text-grey">
                            Enter your email to receive a reset link
                        </p>
                    </div>

                    <!-- Error message -->
                    <?php if ($error !== null) : ?>
                    <div class="notification is-danger is-light">
                        <button class="delete" onclick="this.parentElement.remove()"></button>
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Success message -->
                    <?php if ($success !== null) : ?>
                    <div class="notification is-success is-light">
                        <button class="delete" onclick="this.parentElement.remove()"></button>
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Forgot password form -->
                    <form method="POST" action="/password/forgot" x-data="{ loading: false }" @submit="loading = true">
                        <?php echo \Lwt\Shared\UI\Helpers\FormHelper::csrfField(); ?>

                        <div class="field">
                            <label class="label" for="email">Email Address</label>
                            <div class="control has-icons-left">
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="input"
                                    placeholder="Enter your email address"
                                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                                    required
                                    autofocus
                                >
                                <span class="icon is-small is-left">
                                    <i data-lucide="mail"></i>
                                </span>
                            </div>
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
                                        <i data-lucide="send"></i>
                                    </span>
                                    <span>Send Reset Link</span>
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
