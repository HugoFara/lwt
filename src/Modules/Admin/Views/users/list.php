<?php

declare(strict_types=1);

namespace Lwt\Views\Admin;

use Lwt\Shared\UI\Helpers\IconHelper;
use Lwt\Shared\UI\Helpers\FormHelper;
use Lwt\Shared\UI\Helpers\PageLayoutHelper;
use Lwt\Shared\Infrastructure\Http\UrlUtilities;
use Lwt\Modules\User\Domain\User;

/** @var array<string, mixed> $data */
$data = is_array($data ?? null) ? $data : [];
/** @var list<User> $items */
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$page = isset($data['page']) ? (int) $data['page'] : 1;
$totalPages = isset($data['total_pages']) ? (int) $data['total_pages'] : 0;
/** @var array{total?: int, active?: int, inactive?: int, admins?: int} $stats */
$stats = isset($data['statistics']) && is_array($data['statistics']) ? $data['statistics'] : [];
$currentAdminId = isset($data['current_admin_id']) ? (int) $data['current_admin_id'] : 0;
$search = isset($data['search']) && is_string($data['search']) ? $data['search'] : '';
$sort = isset($data['sort']) && is_string($data['sort']) ? $data['sort'] : 'username';
$dir = isset($data['dir']) && is_string($data['dir']) ? $data['dir'] : 'ASC';

$base = UrlUtilities::getBasePath();
$baseEsc = htmlspecialchars($base, ENT_QUOTES, 'UTF-8');
$titleEdit = htmlspecialchars(__('admin.users_action_edit'), ENT_QUOTES, 'UTF-8');
$titleDeact = htmlspecialchars(__('admin.users_action_deactivate'), ENT_QUOTES, 'UTF-8');
$titleAct = htmlspecialchars(__('admin.users_action_activate'), ENT_QUOTES, 'UTF-8');
$titleDemote = htmlspecialchars(__('admin.users_action_demote'), ENT_QUOTES, 'UTF-8');
$titlePromote = htmlspecialchars(__('admin.users_action_promote'), ENT_QUOTES, 'UTF-8');
$titleDelete = htmlspecialchars(__('admin.users_action_delete'), ENT_QUOTES, 'UTF-8');
$searchPlaceholder = htmlspecialchars(__('admin.users_search_placeholder'), ENT_QUOTES, 'UTF-8');

/**
 * Build a sortable column header link.
 */
$sortLink = function (string $column, string $label) use ($base, $sort, $dir, $search): string {
    $newDir = ($sort === $column && $dir === 'ASC') ? 'DESC' : 'ASC';
    $arrow = '';
    if ($sort === $column) {
        $arrow = $dir === 'ASC' ? ' &uarr;' : ' &darr;';
    }
    /** @var array<string, string> $params */
    $params = ['sort' => $column, 'dir' => $newDir];
    if ($search !== '') {
        $params['search'] = $search;
    }
    $query = htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8');
    return '<a href="' . $base . '/admin/users?' . $query . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $arrow . '</a>';
};
?>

<div class="container" x-data="userManagement">

    <!-- Stats Summary -->
    <?php if (!empty($stats)) : ?>
    <div class="columns is-multiline mb-4">
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading"><?= __('admin.users_total') ?></p>
                <p class="title"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
        </div>
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading"><?= __('admin.users_active') ?></p>
                <p class="title has-text-success"><?php echo $stats['active'] ?? 0; ?></p>
            </div>
        </div>
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading"><?= __('admin.users_inactive') ?></p>
                <p class="title has-text-grey"><?php echo $stats['inactive'] ?? 0; ?></p>
            </div>
        </div>
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading"><?= __('admin.users_admins') ?></p>
                <p class="title has-text-info"><?php echo $stats['admins'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search and Add -->
    <div class="level mb-4">
        <div class="level-left">
            <div class="level-item">
                <form method="get" action="<?= $baseEsc ?>/admin/users">
                    <div class="field has-addons">
                        <div class="control">
                            <input
                                class="input"
                                type="text"
                                name="search"
                                placeholder="<?= $searchPlaceholder ?>"
                                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        <div class="control">
                            <button class="button is-info" type="submit">
                                <?= __('admin.users_search_button') ?>
                            </button>
                        </div>
                        <?php if ($search !== '') : ?>
                        <div class="control">
                            <a
                                class="button"
                                href="<?= $baseEsc ?>/admin/users"
                            ><?= __('admin.users_clear') ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <div class="level-right">
            <div class="level-item">
                <a class="button is-primary" href="<?= $baseEsc ?>/admin/users/new">
                    <?php echo IconHelper::render('user-plus', ['class' => 'icon']); ?>
                    <span><?= __('admin.users_add_new') ?></span>
                </a>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="box">
        <table class="table is-striped is-hoverable is-fullwidth">
            <thead>
                <tr>
                    <th><?php echo $sortLink('username', __('admin.users_col_username')); ?></th>
                    <th><?php echo $sortLink('email', __('admin.users_col_email')); ?></th>
                    <th><?php echo $sortLink('role', __('admin.users_col_role')); ?></th>
                    <th><?php echo $sortLink('active', __('admin.users_col_active')); ?></th>
                    <th><?php echo $sortLink('last_login', __('admin.users_col_last_login')); ?></th>
                    <th><?php echo $sortLink('created', __('admin.users_col_created')); ?></th>
                    <th><?= __('admin.users_col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                <tr>
                    <td colspan="7" class="has-text-centered has-text-grey">
                        <?= $search !== '' ? __('admin.users_none_search') : __('admin.users_none') ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($items as $user) : ?>
                    <?php
                    $userId = $user->id()->toInt();
                    $isSelf = ($userId === $currentAdminId);
                    $isActive = $user->isActive();
                    $isAdmin = $user->isAdmin();
                    ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($user->username(), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($user->email(), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <?php if ($isAdmin) : ?>
                            <span class="tag is-info"><?= __('admin.users_role_admin') ?></span>
                        <?php else : ?>
                            <span class="tag"><?= __('admin.users_role_user') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isActive) : ?>
                            <span class="tag is-success is-light"><?= __('admin.users_status_active') ?></span>
                        <?php else : ?>
                            <span class="tag is-danger is-light"><?= __('admin.users_status_inactive') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $lastLogin = $user->lastLogin();
                        if ($lastLogin !== null) : ?>
                            <?php echo htmlspecialchars($lastLogin->format('Y-m-d H:i'), ENT_QUOTES, 'UTF-8'); ?>
                        <?php else : ?>
                            <span class="has-text-grey"><?= __('admin.users_never') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($user->created()->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td>
                        <div class="buttons are-small">
                            <!-- Edit -->
                            <a class="button is-small"
                               href="<?= $baseEsc ?>/admin/users/<?php echo $userId; ?>/edit"
                               title="<?= $titleEdit ?>">
                                <?php echo IconHelper::render('edit', ['class' => 'icon']); ?>
                            </a>

                            <?php if (!$isSelf) : ?>
                            <!-- Toggle Active -->
                                <?php if ($isActive) : ?>
                            <form method="post"
                                  action="<?= $baseEsc ?>/admin/users/<?php echo $userId; ?>/deactivate"
                                  style="display:inline"
                                  @submit.prevent="toggleStatus(<?php echo $userId; ?>, 'deactivate', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <button class="button is-small is-warning" type="submit" title="<?= $titleDeact ?>">
                                    <?php echo IconHelper::render('user-x', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php else : ?>
                            <form method="post"
                                  action="<?= $baseEsc ?>/admin/users/<?php echo $userId; ?>/activate"
                                  style="display:inline"
                                  @submit.prevent="toggleStatus(<?php echo $userId; ?>, 'activate', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <button class="button is-small is-success" type="submit" title="<?= $titleAct ?>">
                                    <?php echo IconHelper::render('user-check', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php endif; ?>

                            <!-- Toggle Role -->
                                <?php if ($isAdmin) : ?>
                            <form method="post"
                                  action="<?= $baseEsc ?>/admin/users/<?php echo $userId; ?>/role"
                                  style="display:inline"
                                  @submit.prevent="toggleRole(<?php echo $userId; ?>, 'demote', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <input type="hidden" name="action" value="demote">
                                <button
                                    class="button is-small is-info is-light"
                                    type="submit"
                                    title="<?= $titleDemote ?>"
                                >
                                    <?php echo IconHelper::render('shield-off', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php else : ?>
                            <form method="post"
                                  action="<?= $baseEsc ?>/admin/users/<?php echo $userId; ?>/role"
                                  style="display:inline"
                                  @submit.prevent="toggleRole(<?php echo $userId; ?>, 'promote', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <input type="hidden" name="action" value="promote">
                                <button class="button is-small is-info" type="submit" title="<?= $titlePromote ?>">
                                    <?php echo IconHelper::render('shield', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php endif; ?>

                            <!-- Delete -->
                            <form method="post"
                                  action="<?= $baseEsc ?>/admin/users/<?php echo $userId; ?>/delete"
                                  style="display:inline"
                                  @submit="confirmDelete($event)">
                                <?php echo FormHelper::csrfField(); ?>
                                <button class="button is-small is-danger" type="submit" title="<?= $titleDelete ?>">
                                    <?php echo IconHelper::render('trash-2', ['class' => 'icon']); ?>
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if ($isSelf) : ?>
                                <span class="tag is-light"><?= __('admin.users_self_tag') ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1) : ?>
    <nav class="pagination is-centered" role="navigation" aria-label="pagination">
        <?php
        echo PageLayoutHelper::buildPager(
            $page,
            $totalPages,
            $base . '/admin/users',
            'users',
            ['search' => $search, 'sort' => $sort, 'dir' => $dir]
        );
        ?>
    </nav>
    <?php endif; ?>

</div>
