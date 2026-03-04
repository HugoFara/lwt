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

<div class="container" x-data="userManagement()">

    <!-- Stats Summary -->
    <?php if (!empty($stats)) : ?>
    <div class="columns is-multiline mb-4">
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading">Total Users</p>
                <p class="title"><?php echo $stats['total'] ?? 0; ?></p>
            </div>
        </div>
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading">Active</p>
                <p class="title has-text-success"><?php echo $stats['active'] ?? 0; ?></p>
            </div>
        </div>
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading">Inactive</p>
                <p class="title has-text-grey"><?php echo $stats['inactive'] ?? 0; ?></p>
            </div>
        </div>
        <div class="column is-3">
            <div class="box has-text-centered">
                <p class="heading">Admins</p>
                <p class="title has-text-info"><?php echo $stats['admins'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search and Add -->
    <div class="level mb-4">
        <div class="level-left">
            <div class="level-item">
                <form method="get" action="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users">
                    <div class="field has-addons">
                        <div class="control">
                            <input class="input" type="text" name="search"
                                   placeholder="Search users..."
                                   value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="control">
                            <button class="button is-info" type="submit">Search</button>
                        </div>
                        <?php if ($search !== '') : ?>
                        <div class="control">
                            <a class="button" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users">Clear</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <div class="level-right">
            <div class="level-item">
                <a class="button is-primary" href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users/new">
                    <?php echo IconHelper::render('user-plus', ['class' => 'icon']); ?>
                    <span>Add New User</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="box">
        <table class="table is-striped is-hoverable is-fullwidth">
            <thead>
                <tr>
                    <th><?php echo $sortLink('username', 'Username'); ?></th>
                    <th><?php echo $sortLink('email', 'Email'); ?></th>
                    <th><?php echo $sortLink('role', 'Role'); ?></th>
                    <th><?php echo $sortLink('active', 'Active'); ?></th>
                    <th><?php echo $sortLink('last_login', 'Last Login'); ?></th>
                    <th><?php echo $sortLink('created', 'Created'); ?></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                <tr>
                    <td colspan="7" class="has-text-centered has-text-grey">
                        <?php echo $search !== '' ? 'No users found matching your search.' : 'No users found.'; ?>
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
                            <span class="tag is-info">Admin</span>
                        <?php else : ?>
                            <span class="tag">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isActive) : ?>
                            <span class="tag is-success is-light">Active</span>
                        <?php else : ?>
                            <span class="tag is-danger is-light">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $lastLogin = $user->lastLogin();
                        if ($lastLogin !== null) : ?>
                            <?php echo htmlspecialchars($lastLogin->format('Y-m-d H:i'), ENT_QUOTES, 'UTF-8'); ?>
                        <?php else : ?>
                            <span class="has-text-grey">Never</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($user->created()->format('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>
                    </td>
                    <td>
                        <div class="buttons are-small">
                            <!-- Edit -->
                            <a class="button is-small"
                               href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users/<?php echo $userId; ?>/edit"
                               title="Edit">
                                <?php echo IconHelper::render('edit', ['class' => 'icon']); ?>
                            </a>

                            <?php if (!$isSelf) : ?>
                            <!-- Toggle Active -->
                                <?php if ($isActive) : ?>
                            <form method="post"
                                  action="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users/<?php echo $userId; ?>/deactivate"
                                  style="display:inline"
                                  @submit.prevent="toggleStatus(<?php echo $userId; ?>, 'deactivate', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <button class="button is-small is-warning" type="submit" title="Deactivate">
                                    <?php echo IconHelper::render('user-x', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php else : ?>
                            <form method="post"
                                  action="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users/<?php echo $userId; ?>/activate"
                                  style="display:inline"
                                  @submit.prevent="toggleStatus(<?php echo $userId; ?>, 'activate', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <button class="button is-small is-success" type="submit" title="Activate">
                                    <?php echo IconHelper::render('user-check', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php endif; ?>

                            <!-- Toggle Role -->
                                <?php if ($isAdmin) : ?>
                            <form method="post"
                                  action="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users/<?php echo $userId; ?>/role"
                                  style="display:inline"
                                  @submit.prevent="toggleRole(<?php echo $userId; ?>, 'demote', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <input type="hidden" name="action" value="demote">
                                <button class="button is-small is-info is-light" type="submit" title="Demote to User">
                                    <?php echo IconHelper::render('shield-off', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php else : ?>
                            <form method="post"
                                  action="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users/<?php echo $userId; ?>/role"
                                  style="display:inline"
                                  @submit.prevent="toggleRole(<?php echo $userId; ?>, 'promote', $event.target)">
                                    <?php echo FormHelper::csrfField(); ?>
                                <input type="hidden" name="action" value="promote">
                                <button class="button is-small is-info" type="submit" title="Promote to Admin">
                                    <?php echo IconHelper::render('shield', ['class' => 'icon']); ?>
                                </button>
                            </form>
                                <?php endif; ?>

                            <!-- Delete -->
                            <form method="post"
                                  action="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/users/<?php echo $userId; ?>/delete"
                                  style="display:inline"
                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                <?php echo FormHelper::csrfField(); ?>
                                <button class="button is-small is-danger" type="submit" title="Delete">
                                    <?php echo IconHelper::render('trash-2', ['class' => 'icon']); ?>
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if ($isSelf) : ?>
                                <span class="tag is-light">You</span>
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

<script>
function userManagement() {
    return {
        toggleStatus(userId, action, form) {
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Action failed');
                }
            })
            .catch(() => alert('Request failed'));
        },
        toggleRole(userId, action, form) {
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.error || 'Action failed');
                }
            })
            .catch(() => alert('Request failed'));
        }
    };
}
</script>
