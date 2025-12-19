<?php declare(strict_types=1);
/**
 * User Repository
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Core\Repository
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Core\Repository;

use DateTimeImmutable;
use Lwt\Core\Entity\User;
use Lwt\Core\Entity\ValueObject\UserId;
use Lwt\Database\Connection;

/**
 * Repository for User entities.
 *
 * Provides database access for user management operations.
 * Handles authentication lookups and user CRUD.
 *
 * @extends AbstractRepository<User>
 *
 * @since 3.0.0
 */
class UserRepository extends AbstractRepository
{
    /**
     * @var string Table name without prefix
     */
    protected string $tableName = 'users';

    /**
     * @var string Primary key column
     */
    protected string $primaryKey = 'UsID';

    /**
     * @var array<string, string> Property to column mapping
     */
    protected array $columnMap = [
        'id' => 'UsID',
        'username' => 'UsUsername',
        'email' => 'UsEmail',
        'passwordHash' => 'UsPasswordHash',
        'apiToken' => 'UsApiToken',
        'apiTokenExpires' => 'UsApiTokenExpires',
        'wordPressId' => 'UsWordPressId',
        'created' => 'UsCreated',
        'lastLogin' => 'UsLastLogin',
        'isActive' => 'UsIsActive',
        'role' => 'UsRole',
    ];

    /**
     * {@inheritdoc}
     */
    protected function mapToEntity(array $row): User
    {
        return User::reconstitute(
            (int) $row['UsID'],
            (string) $row['UsUsername'],
            (string) $row['UsEmail'],
            $row['UsPasswordHash'] !== null ? (string) $row['UsPasswordHash'] : null,
            $row['UsApiToken'] !== null ? (string) $row['UsApiToken'] : null,
            $this->parseNullableDateTime($row['UsApiTokenExpires'] ?? null),
            $row['UsWordPressId'] !== null ? (int) $row['UsWordPressId'] : null,
            $this->parseDateTime($row['UsCreated'] ?? null),
            $this->parseNullableDateTime($row['UsLastLogin'] ?? null),
            (bool) ($row['UsIsActive'] ?? true),
            (string) ($row['UsRole'] ?? User::ROLE_USER)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param User $entity
     *
     * @return array<string, mixed>
     */
    protected function mapToRow(object $entity): array
    {
        return [
            'UsID' => $entity->id()->toInt(),
            'UsUsername' => $entity->username(),
            'UsEmail' => $entity->email(),
            'UsPasswordHash' => $entity->passwordHash(),
            'UsApiToken' => $entity->apiToken(),
            'UsApiTokenExpires' => $entity->apiTokenExpires()?->format('Y-m-d H:i:s'),
            'UsWordPressId' => $entity->wordPressId(),
            'UsCreated' => $entity->created()->format('Y-m-d H:i:s'),
            'UsLastLogin' => $entity->lastLogin()?->format('Y-m-d H:i:s'),
            'UsIsActive' => $entity->isActive() ? 1 : 0,
            'UsRole' => $entity->role(),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param User $entity
     */
    protected function getEntityId(object $entity): int
    {
        return $entity->id()->toInt();
    }

    /**
     * {@inheritdoc}
     *
     * @param User $entity
     */
    protected function setEntityId(object $entity, int $id): void
    {
        $entity->setId(UserId::fromInt($id));
    }

    /**
     * Parse a datetime string into DateTimeImmutable.
     *
     * @param string|null $datetime The datetime string
     *
     * @return DateTimeImmutable
     */
    private function parseDateTime(?string $datetime): DateTimeImmutable
    {
        if ($datetime === null || $datetime === '' || $datetime === '0000-00-00 00:00:00') {
            return new DateTimeImmutable();
        }
        return new DateTimeImmutable($datetime);
    }

    /**
     * Parse a nullable datetime string.
     *
     * @param string|null $datetime The datetime string
     *
     * @return DateTimeImmutable|null
     */
    private function parseNullableDateTime(?string $datetime): ?DateTimeImmutable
    {
        if ($datetime === null || $datetime === '' || $datetime === '0000-00-00 00:00:00') {
            return null;
        }
        return new DateTimeImmutable($datetime);
    }

    /**
     * Find a user by username.
     *
     * @param string $username The username
     *
     * @return User|null
     */
    public function findByUsername(string $username): ?User
    {
        $row = $this->query()
            ->where('UsUsername', '=', $username)
            ->firstPrepared();

        return $row !== null ? $this->mapToEntity($row) : null;
    }

    /**
     * Find a user by email.
     *
     * @param string $email The email address
     *
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $row = $this->query()
            ->where('UsEmail', '=', strtolower($email))
            ->firstPrepared();

        return $row !== null ? $this->mapToEntity($row) : null;
    }

    /**
     * Find a user by API token.
     *
     * @param string $token The API token
     *
     * @return User|null
     */
    public function findByApiToken(string $token): ?User
    {
        $row = $this->query()
            ->where('UsApiToken', '=', $token)
            ->firstPrepared();

        return $row !== null ? $this->mapToEntity($row) : null;
    }

    /**
     * Find a user by WordPress ID.
     *
     * @param int $wordPressId The WordPress user ID
     *
     * @return User|null
     */
    public function findByWordPressId(int $wordPressId): ?User
    {
        $row = $this->query()
            ->where('UsWordPressId', '=', $wordPressId)
            ->firstPrepared();

        return $row !== null ? $this->mapToEntity($row) : null;
    }

    /**
     * Check if a username exists.
     *
     * @param string   $username  Username to check
     * @param int|null $excludeId User ID to exclude (for updates)
     *
     * @return bool
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $query = $this->query()
            ->where('UsUsername', '=', $username);

        if ($excludeId !== null) {
            $query->where('UsID', '!=', $excludeId);
        }

        return $query->existsPrepared();
    }

    /**
     * Check if an email exists.
     *
     * @param string   $email     Email to check
     * @param int|null $excludeId User ID to exclude (for updates)
     *
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->query()
            ->where('UsEmail', '=', strtolower($email));

        if ($excludeId !== null) {
            $query->where('UsID', '!=', $excludeId);
        }

        return $query->existsPrepared();
    }

    /**
     * Find all active users.
     *
     * @return User[]
     */
    public function findActive(): array
    {
        $rows = $this->query()
            ->where('UsIsActive', '=', 1)
            ->orderBy('UsUsername')
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find all inactive users.
     *
     * @return User[]
     */
    public function findInactive(): array
    {
        $rows = $this->query()
            ->where('UsIsActive', '=', 0)
            ->orderBy('UsUsername')
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find all admin users.
     *
     * @return User[]
     */
    public function findAdmins(): array
    {
        $rows = $this->query()
            ->where('UsRole', '=', User::ROLE_ADMIN)
            ->orderBy('UsUsername')
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find users linked to WordPress.
     *
     * @return User[]
     */
    public function findWordPressUsers(): array
    {
        $rows = $this->query()
            ->whereNotNull('UsWordPressId')
            ->orderBy('UsUsername')
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Update the last login timestamp.
     *
     * @param int $userId User ID
     *
     * @return bool True if updated
     */
    public function updateLastLogin(int $userId): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared(['UsLastLogin' => date('Y-m-d H:i:s')]);

        return $affected > 0;
    }

    /**
     * Update the password hash.
     *
     * @param int    $userId       User ID
     * @param string $passwordHash New password hash
     *
     * @return bool True if updated
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared(['UsPasswordHash' => $passwordHash]);

        return $affected > 0;
    }

    /**
     * Update the API token.
     *
     * @param int                    $userId  User ID
     * @param string|null            $token   API token (null to clear)
     * @param DateTimeImmutable|null $expires Token expiration
     *
     * @return bool True if updated
     */
    public function updateApiToken(int $userId, ?string $token, ?DateTimeImmutable $expires): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared([
                'UsApiToken' => $token,
                'UsApiTokenExpires' => $expires?->format('Y-m-d H:i:s'),
            ]);

        return $affected > 0;
    }

    /**
     * Activate a user account.
     *
     * @param int $userId User ID
     *
     * @return bool True if updated
     */
    public function activate(int $userId): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared(['UsIsActive' => 1]);

        return $affected > 0;
    }

    /**
     * Deactivate a user account.
     *
     * @param int $userId User ID
     *
     * @return bool True if updated
     */
    public function deactivate(int $userId): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared(['UsIsActive' => 0]);

        return $affected > 0;
    }

    /**
     * Update user role.
     *
     * @param int    $userId User ID
     * @param string $role   New role (user or admin)
     *
     * @return bool True if updated
     */
    public function updateRole(int $userId, string $role): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared(['UsRole' => $role]);

        return $affected > 0;
    }

    /**
     * Link user to WordPress account.
     *
     * @param int $userId      User ID
     * @param int $wordPressId WordPress user ID
     *
     * @return bool True if updated
     */
    public function linkWordPress(int $userId, int $wordPressId): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared(['UsWordPressId' => $wordPressId]);

        return $affected > 0;
    }

    /**
     * Unlink user from WordPress account.
     *
     * @param int $userId User ID
     *
     * @return bool True if updated
     */
    public function unlinkWordPress(int $userId): bool
    {
        $affected = $this->query()
            ->where('UsID', '=', $userId)
            ->updatePrepared(['UsWordPressId' => null]);

        return $affected > 0;
    }

    /**
     * Get users formatted for select dropdown options.
     *
     * @param int $maxNameLength Maximum username length before truncation
     *
     * @return array<int, array{id: int, username: string, email: string}>
     */
    public function getForSelect(int $maxNameLength = 40): array
    {
        $rows = $this->query()
            ->select(['UsID', 'UsUsername', 'UsEmail'])
            ->where('UsIsActive', '=', 1)
            ->orderBy('UsUsername')
            ->getPrepared();

        $result = [];

        foreach ($rows as $row) {
            $username = (string) $row['UsUsername'];
            if (mb_strlen($username, 'UTF-8') > $maxNameLength) {
                $username = mb_substr($username, 0, $maxNameLength, 'UTF-8') . '...';
            }
            $result[] = [
                'id' => (int) $row['UsID'],
                'username' => $username,
                'email' => (string) $row['UsEmail'],
            ];
        }

        return $result;
    }

    /**
     * Get basic user info (minimal data for lists).
     *
     * @param int $userId User ID
     *
     * @return array{id: int, username: string, email: string, is_active: bool, is_admin: bool}|null
     */
    public function getBasicInfo(int $userId): ?array
    {
        $row = $this->query()
            ->select([
                'UsID',
                'UsUsername',
                'UsEmail',
                'UsIsActive',
                'UsRole',
            ])
            ->where('UsID', '=', $userId)
            ->firstPrepared();

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['UsID'],
            'username' => (string) $row['UsUsername'],
            'email' => (string) $row['UsEmail'],
            'is_active' => (bool) $row['UsIsActive'],
            'is_admin' => $row['UsRole'] === User::ROLE_ADMIN,
        ];
    }

    /**
     * Get users with pagination.
     *
     * @param int    $page      Page number (1-based)
     * @param int    $perPage   Items per page
     * @param string $orderBy   Column to order by
     * @param string $direction Sort direction
     *
     * @return array{items: User[], total: int, page: int, per_page: int, total_pages: int}
     */
    public function findPaginated(
        int $page = 1,
        int $perPage = 20,
        string $orderBy = 'UsUsername',
        string $direction = 'ASC'
    ): array {
        $query = $this->query();

        $total = (clone $query)->countPrepared();
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 0;

        // Ensure page is within bounds
        $page = max(1, min($page, max(1, $totalPages)));
        $offset = ($page - 1) * $perPage;

        $rows = $query
            ->orderBy($orderBy, $direction)
            ->limit($perPage)
            ->offset($offset)
            ->getPrepared();

        $items = array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Search users by username or email.
     *
     * @param string $query Search query
     * @param int    $limit Maximum results
     *
     * @return User[]
     */
    public function search(string $query, int $limit = 50): array
    {
        $searchPattern = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $query) . '%';

        $sql = "SELECT * FROM users WHERE (UsUsername LIKE ? OR UsEmail LIKE ?) ORDER BY UsUsername LIMIT ?";
        $rows = Connection::preparedFetchAll($sql, [$searchPattern, $searchPattern, $limit]);

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find users who logged in recently.
     *
     * @param int $days  Number of days to look back
     * @param int $limit Maximum results
     *
     * @return User[]
     */
    public function findRecentlyActive(int $days = 30, int $limit = 50): array
    {
        $sinceDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $rows = $this->query()
            ->where('UsLastLogin', '>=', $sinceDate)
            ->orderBy('UsLastLogin', 'DESC')
            ->limit($limit)
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Find users created recently.
     *
     * @param int $days  Number of days to look back
     * @param int $limit Maximum results
     *
     * @return User[]
     */
    public function findRecentlyCreated(int $days = 30, int $limit = 50): array
    {
        $sinceDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $rows = $this->query()
            ->where('UsCreated', '>=', $sinceDate)
            ->orderBy('UsCreated', 'DESC')
            ->limit($limit)
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Get user statistics.
     *
     * @return array{total: int, active: int, inactive: int, admins: int, wordpress_linked: int}
     */
    public function getStatistics(): array
    {
        $baseQuery = $this->query();

        $total = (clone $baseQuery)->countPrepared();

        $active = (clone $baseQuery)
            ->where('UsIsActive', '=', 1)
            ->countPrepared();

        $inactive = (clone $baseQuery)
            ->where('UsIsActive', '=', 0)
            ->countPrepared();

        $admins = (clone $baseQuery)
            ->where('UsRole', '=', User::ROLE_ADMIN)
            ->countPrepared();

        $wordPressLinked = (clone $baseQuery)
            ->whereNotNull('UsWordPressId')
            ->countPrepared();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'admins' => $admins,
            'wordpress_linked' => $wordPressLinked,
        ];
    }

    /**
     * Find users with expired API tokens.
     *
     * @return User[]
     */
    public function findWithExpiredApiTokens(): array
    {
        $now = date('Y-m-d H:i:s');

        $rows = $this->query()
            ->whereNotNull('UsApiToken')
            ->where('UsApiTokenExpires', '<', $now)
            ->getPrepared();

        return array_map(
            fn(array $row) => $this->mapToEntity($row),
            $rows
        );
    }

    /**
     * Clear expired API tokens.
     *
     * @return int Number of cleared tokens
     */
    public function clearExpiredApiTokens(): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->query()
            ->whereNotNull('UsApiToken')
            ->where('UsApiTokenExpires', '<', $now)
            ->updatePrepared([
                'UsApiToken' => null,
                'UsApiTokenExpires' => null,
            ]);
    }

    /**
     * Delete multiple users by IDs.
     *
     * @param int[] $userIds Array of user IDs
     *
     * @return int Number of deleted users
     */
    public function deleteMultiple(array $userIds): int
    {
        if (empty($userIds)) {
            return 0;
        }

        return $this->query()
            ->whereIn('UsID', array_map('intval', $userIds))
            ->deletePrepared();
    }

    /**
     * Count users by role.
     *
     * @return array<string, int> Role => count
     */
    public function countByRole(): array
    {
        $sql = "SELECT UsRole, COUNT(*) as cnt FROM users GROUP BY UsRole";
        $rows = Connection::preparedFetchAll($sql, []);

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['UsRole']] = (int) $row['cnt'];
        }

        return $counts;
    }
}
