<?php
/**
 * Microsoft Account Link Confirmation View
 *
 * Shown when a user signs in with Microsoft but their email already
 * exists in the database with a different account.
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\User\Views
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 *
 * @var string $email The email address from Microsoft
 * @var string|null $error Error message if any
 */

use Lwt\Shared\UI\Helpers\FormHelper;

?>
<div class="container">
    <div class="columns is-centered">
        <div class="column is-half">
            <div class="box mt-6">
                <h1 class="title is-4 has-text-centered">Link Microsoft Account</h1>

                <div class="notification is-info is-light">
                    <p>
                        An account with the email <strong><?= htmlspecialchars($email) ?></strong>
                        already exists.
                    </p>
                    <p class="mt-2">
                        Enter your password to link your Microsoft account to your existing LWT account.
                    </p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="notification is-danger is-light">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="post" action="/microsoft/link-confirm">
                    <?= FormHelper::csrfField() ?>

                    <div class="field">
                        <label class="label" for="password">Password</label>
                        <div class="control">
                            <input
                                class="input"
                                type="password"
                                id="password"
                                name="password"
                                required
                                autofocus
                                placeholder="Enter your LWT password"
                            >
                        </div>
                    </div>

                    <div class="field is-grouped">
                        <div class="control">
                            <button
                                type="submit"
                                name="action"
                                value="link"
                                class="button is-primary"
                            >
                                Link Account
                            </button>
                        </div>
                        <div class="control">
                            <button
                                type="submit"
                                name="action"
                                value="cancel"
                                class="button is-light"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>

                <hr>

                <p class="has-text-centered has-text-grey">
                    <a href="/login">Back to login</a>
                </p>
            </div>
        </div>
    </div>
</div>
