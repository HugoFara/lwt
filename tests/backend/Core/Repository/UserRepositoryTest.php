<?php

declare(strict_types=1);

namespace Lwt\Tests\Core\Repository;

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/EnvLoader.php';

use Lwt\Modules\User\Domain\User;
use Lwt\Shared\Domain\ValueObjects\UserId;
use Lwt\Shared\Infrastructure\Bootstrap\EnvLoader;
use Lwt\Shared\Infrastructure\Globals;
use Lwt\Modules\User\Infrastructure\MySqlUserRepository;
use Lwt\Shared\Infrastructure\Database\Configuration;
use Lwt\Shared\Infrastructure\Database\Connection;
use PHPUnit\Framework\TestCase;

// Load config from .env and use test database
EnvLoader::load(__DIR__ . '/../../../../.env');
$config = EnvLoader::getDatabaseConfig();
Globals::setDatabaseName("test_" . $config['dbname']);

require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../../src/Shared/Domain/ValueObjects/UserId.php';
require_once __DIR__ . '/../../../../src/Modules/User/Domain/User.php';
require_once __DIR__ . '/../../../../src/Shared/Infrastructure/Database/PreparedStatement.php';
require_once __DIR__ . '/../../../../src/Modules/User/Infrastructure/MySqlUserRepository.php';

/**
 * Tests for the MySqlUserRepository class.
 *
 * @covers \Lwt\Modules\User\Infrastructure\MySqlUserRepository
 */
class UserRepositoryTest extends TestCase
{
    private static bool $dbConnected = false;
    private MySqlUserRepository $repository;
    private static array $testUserIds = [];

    public static function setUpBeforeClass(): void
    {
        $config = EnvLoader::getDatabaseConfig();
        $testDbname = "test_" . $config['dbname'];

        if (!Globals::getDbConnection()) {
            $connection = Configuration::connect(
                $config['server'],
                $config['userid'],
                $config['passwd'],
                $testDbname,
                $config['socket'] ?? ''
            );
            Globals::setDbConnection($connection);
        }
        self::$dbConnected = (Globals::getDbConnection() !== null);
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up test users
        $prefix = '';
        Connection::query("DELETE FROM {$prefix}users WHERE UsUsername LIKE 'UserRepoTest_%'");
    }

    protected function setUp(): void
    {
        $this->repository = new MySqlUserRepository();
    }

    protected function tearDown(): void
    {
        if (!self::$dbConnected) {
            return;
        }

        // Clean up users created during this test
        $prefix = '';
        Connection::query("DELETE FROM {$prefix}users WHERE UsUsername LIKE 'UserRepoTest_%'");
        self::$testUserIds = [];
    }

    /**
     * Helper to create a test user directly in DB.
     */
    private function createTestUserInDb(
        string $username,
        string $email = '',
        string $role = 'user',
        bool $isActive = true,
        ?string $apiToken = null,
        ?int $wordPressId = null
    ): int {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        if ($email === '') {
            $email = strtolower($username) . '@test.example.com';
        }

        $prefix = '';
        $conn = Globals::getDbConnection();
        $escapedUsername = mysqli_real_escape_string($conn, $username);
        $escapedEmail = mysqli_real_escape_string($conn, $email);
        $escapedRole = mysqli_real_escape_string($conn, $role);
        $isActiveInt = $isActive ? 1 : 0;
        $escapedApiToken = $apiToken !== null ? "'" . mysqli_real_escape_string($conn, $apiToken) . "'" : 'NULL';
        $wpIdValue = $wordPressId !== null ? $wordPressId : 'NULL';

        Connection::query(
            "INSERT INTO {$prefix}users (
                UsUsername, UsEmail, UsPasswordHash, UsApiToken, UsApiTokenExpires,
                UsWordPressId, UsCreated, UsLastLogin, UsIsActive, UsRole
            ) VALUES (
                '$escapedUsername', '$escapedEmail', 'hash123', $escapedApiToken, NULL,
                $wpIdValue, NOW(), NOW(), $isActiveInt, '$escapedRole'
            )"
        );
        $id = (int) mysqli_insert_id($conn);
        self::$testUserIds[] = $id;
        return $id;
    }

    // ===== find() tests =====

    public function testFindReturnsUser(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_Find');

        $result = $this->repository->find($id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($id, $result->id()->toInt());
        $this->assertEquals('UserRepoTest_Find', $result->username());
    }

    public function testFindReturnsNullForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->repository->find(999999);

        $this->assertNull($result);
    }

    // ===== save() tests =====

    public function testSaveInsertsNewEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $user = User::create(
            'UserRepoTest_Insert',
            'userrepotest_insert@test.example.com',
            'hashedpassword123'
        );

        $id = $this->repository->save($user);

        $this->assertGreaterThan(0, $id);
        $this->assertEquals($id, $user->id()->toInt());

        // Verify in database
        $found = $this->repository->find($id);
        $this->assertNotNull($found);
        $this->assertEquals('UserRepoTest_Insert', $found->username());

        self::$testUserIds[] = $id;
    }

    public function testSaveUpdatesExistingEntity(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_Update');
        $user = $this->repository->find($id);

        $user->changeEmail('updated@test.example.com');
        $this->repository->save($user);

        $updated = $this->repository->find($id);
        $this->assertEquals('updated@test.example.com', $updated->email());
    }

    // ===== delete() tests =====

    public function testDeleteById(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_DeleteById');

        $result = $this->repository->delete($id);

        $this->assertTrue($result);
        $this->assertNull($this->repository->find($id));
    }

    public function testDeleteReturnsFalseForNonExistent(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $result = $this->repository->delete(999999);

        $this->assertFalse($result);
    }

    // ===== exists() tests =====

    public function testExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_Exists');

        $this->assertTrue($this->repository->exists($id));
        $this->assertFalse($this->repository->exists(999999));
    }

    // ===== findByUsername() tests =====

    public function testFindByUsername(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_ByUsername');

        $found = $this->repository->findByUsername('UserRepoTest_ByUsername');

        $this->assertNotNull($found);
        $this->assertEquals('UserRepoTest_ByUsername', $found->username());
    }

    public function testFindByUsernameNotFound(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $found = $this->repository->findByUsername('NonExistentUser12345');

        $this->assertNull($found);
    }

    // ===== findByEmail() tests =====

    public function testFindByEmail(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_ByEmail', 'unique_email@test.example.com');

        $found = $this->repository->findByEmail('unique_email@test.example.com');

        $this->assertNotNull($found);
        $this->assertEquals('unique_email@test.example.com', $found->email());
    }

    public function testFindByEmailCaseInsensitive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_EmailCase', 'casetest@test.example.com');

        $found = $this->repository->findByEmail('CASETEST@test.example.com');

        $this->assertNotNull($found);
    }

    // ===== findByApiToken() tests =====

    public function testFindByApiToken(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $token = 'test_token_' . bin2hex(random_bytes(16));
        $this->createTestUserInDb('UserRepoTest_ByToken', '', 'user', true, $token);

        $found = $this->repository->findByApiToken($token);

        $this->assertNotNull($found);
        $this->assertEquals('UserRepoTest_ByToken', $found->username());
    }

    // ===== findByWordPressId() tests =====

    public function testFindByWordPressId(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $wpId = 12345;
        $this->createTestUserInDb('UserRepoTest_ByWpId', '', 'user', true, null, $wpId);

        $found = $this->repository->findByWordPressId($wpId);

        $this->assertNotNull($found);
        $this->assertEquals('UserRepoTest_ByWpId', $found->username());
    }

    // ===== usernameExists() tests =====

    public function testUsernameExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_UsernameExists');

        $this->assertTrue($this->repository->usernameExists('UserRepoTest_UsernameExists'));
        $this->assertFalse($this->repository->usernameExists('NonExistentUser'));
    }

    public function testUsernameExistsWithExclude(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_UsernameExclude');

        // Should not find if excluding the same user
        $this->assertFalse(
            $this->repository->usernameExists('UserRepoTest_UsernameExclude', $id)
        );
    }

    // ===== emailExists() tests =====

    public function testEmailExists(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_EmailExists', 'exists@test.example.com');

        $this->assertTrue($this->repository->emailExists('exists@test.example.com'));
        $this->assertFalse($this->repository->emailExists('nonexistent@test.example.com'));
    }

    // ===== findActive() / findInactive() tests =====

    public function testFindActive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_Active1', '', 'user', true);
        $this->createTestUserInDb('UserRepoTest_Active2', '', 'user', true);
        $this->createTestUserInDb('UserRepoTest_Inactive', '', 'user', false);

        $active = $this->repository->findActive();

        $usernames = array_map(fn(User $u) => $u->username(), $active);
        $this->assertContains('UserRepoTest_Active1', $usernames);
        $this->assertContains('UserRepoTest_Active2', $usernames);
        $this->assertNotContains('UserRepoTest_Inactive', $usernames);
    }

    public function testFindInactive(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_InactiveFind', '', 'user', false);
        $this->createTestUserInDb('UserRepoTest_ActiveFind', '', 'user', true);

        $inactive = $this->repository->findInactive();

        $usernames = array_map(fn(User $u) => $u->username(), $inactive);
        $this->assertContains('UserRepoTest_InactiveFind', $usernames);
        $this->assertNotContains('UserRepoTest_ActiveFind', $usernames);
    }

    // ===== findAdmins() tests =====

    public function testFindAdmins(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_Admin', '', 'admin', true);
        $this->createTestUserInDb('UserRepoTest_NotAdmin', '', 'user', true);

        $admins = $this->repository->findAdmins();

        $usernames = array_map(fn(User $u) => $u->username(), $admins);
        $this->assertContains('UserRepoTest_Admin', $usernames);
        $this->assertNotContains('UserRepoTest_NotAdmin', $usernames);
    }

    // ===== findWordPressUsers() tests =====

    public function testFindWordPressUsers(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_WpLinked', '', 'user', true, null, 99999);
        $this->createTestUserInDb('UserRepoTest_NotWpLinked', '', 'user', true, null, null);

        $wpUsers = $this->repository->findWordPressUsers();

        $usernames = array_map(fn(User $u) => $u->username(), $wpUsers);
        $this->assertContains('UserRepoTest_WpLinked', $usernames);
        $this->assertNotContains('UserRepoTest_NotWpLinked', $usernames);
    }

    // ===== update methods tests =====

    public function testUpdateLastLogin(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_LastLogin');

        $this->assertTrue($this->repository->updateLastLogin($id));

        $found = $this->repository->find($id);
        $this->assertNotNull($found->lastLogin());
    }

    public function testUpdatePassword(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_Password');

        $this->assertTrue($this->repository->updatePassword($id, 'newhash456'));

        $found = $this->repository->find($id);
        $this->assertEquals('newhash456', $found->passwordHash());
    }

    public function testActivateDeactivate(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_ActivateDeactivate', '', 'user', true);

        $this->assertTrue($this->repository->deactivate($id));
        $found = $this->repository->find($id);
        $this->assertFalse($found->isActive());

        $this->assertTrue($this->repository->activate($id));
        $found = $this->repository->find($id);
        $this->assertTrue($found->isActive());
    }

    public function testUpdateRole(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_UpdateRole', '', 'user', true);

        $this->assertTrue($this->repository->updateRole($id, 'admin'));

        $found = $this->repository->find($id);
        $this->assertEquals('admin', $found->role());
        $this->assertTrue($found->isAdmin());
    }

    public function testLinkUnlinkWordPress(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_WpLink', '', 'user', true, null, null);

        $this->assertTrue($this->repository->linkWordPress($id, 88888));
        $found = $this->repository->find($id);
        $this->assertEquals(88888, $found->wordPressId());

        $this->assertTrue($this->repository->unlinkWordPress($id));
        $found = $this->repository->find($id);
        $this->assertNull($found->wordPressId());
    }

    // ===== getForSelect() tests =====

    public function testGetForSelect(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_Select1', '', 'user', true);

        $options = $this->repository->getForSelect();

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);

        $firstOption = $options[0];
        $this->assertArrayHasKey('id', $firstOption);
        $this->assertArrayHasKey('username', $firstOption);
        $this->assertArrayHasKey('email', $firstOption);
    }

    // ===== getBasicInfo() tests =====

    public function testGetBasicInfo(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id = $this->createTestUserInDb('UserRepoTest_BasicInfo', '', 'admin', true);

        $info = $this->repository->getBasicInfo($id);

        $this->assertNotNull($info);
        $this->assertEquals($id, $info['id']);
        $this->assertEquals('UserRepoTest_BasicInfo', $info['username']);
        $this->assertTrue($info['is_active']);
        $this->assertTrue($info['is_admin']);
    }

    public function testGetBasicInfoNotFound(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $info = $this->repository->getBasicInfo(999999);

        $this->assertNull($info);
    }

    // ===== findPaginated() tests =====

    public function testFindPaginated(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        for ($i = 1; $i <= 5; $i++) {
            $this->createTestUserInDb("UserRepoTest_Paginated$i");
        }

        $result = $this->repository->findPaginated(1, 3, 'UsUsername', 'ASC');

        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
        $this->assertArrayHasKey('total_pages', $result);

        $this->assertLessThanOrEqual(3, count($result['items']));
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(3, $result['per_page']);
    }

    // ===== search() tests =====

    public function testSearch(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_SearchAlpha');
        $this->createTestUserInDb('UserRepoTest_SearchBeta');
        $this->createTestUserInDb('UserRepoTest_Different');

        $results = $this->repository->search('UserRepoTest_Search');

        $this->assertGreaterThanOrEqual(2, count($results));
        foreach ($results as $user) {
            $this->assertStringContainsString('Search', $user->username());
        }
    }

    // ===== getStatistics() tests =====

    public function testGetStatistics(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_Stats1', '', 'user', true);
        $this->createTestUserInDb('UserRepoTest_Stats2', '', 'admin', true);
        $this->createTestUserInDb('UserRepoTest_Stats3', '', 'user', false);
        $this->createTestUserInDb('UserRepoTest_Stats4', '', 'user', true, null, 77777);

        $stats = $this->repository->getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('inactive', $stats);
        $this->assertArrayHasKey('admins', $stats);
        $this->assertArrayHasKey('wordpress_linked', $stats);

        $this->assertGreaterThanOrEqual(4, $stats['total']);
        $this->assertGreaterThanOrEqual(1, $stats['admins']);
        $this->assertGreaterThanOrEqual(1, $stats['inactive']);
        $this->assertGreaterThanOrEqual(1, $stats['wordpress_linked']);
    }

    // ===== deleteMultiple() tests =====

    public function testDeleteMultiple(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $id1 = $this->createTestUserInDb('UserRepoTest_DelMulti1');
        $id2 = $this->createTestUserInDb('UserRepoTest_DelMulti2');

        $deleted = $this->repository->deleteMultiple([$id1, $id2]);

        $this->assertEquals(2, $deleted);
        $this->assertNull($this->repository->find($id1));
        $this->assertNull($this->repository->find($id2));
    }

    // ===== countByRole() tests =====

    public function testCountByRole(): void
    {
        if (!self::$dbConnected) {
            $this->markTestSkipped('Database connection required');
        }

        $this->createTestUserInDb('UserRepoTest_Role1', '', 'user', true);
        $this->createTestUserInDb('UserRepoTest_Role2', '', 'admin', true);

        $counts = $this->repository->countByRole();

        $this->assertIsArray($counts);
        $this->assertGreaterThanOrEqual(1, $counts['user'] ?? 0);
        $this->assertGreaterThanOrEqual(1, $counts['admin'] ?? 0);
    }
}
