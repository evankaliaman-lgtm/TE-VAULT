<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const array ADMIN_PERMISSIONS = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'assets.view',
        'assets.create',
        'assets.update',
        'assets.delete',
        'borrowings.view',
        'borrowings.create',
        'borrowings.approve',
        'borrowings.reject',
        'borrowings.return',
        'borrowings.verify-return',
        'reports.view',
        'reports.export',
        'audit.view',
        'notifications.view',
    ];

    /**
     * @var list<string>
     */
    private const array BORROWER_PERMISSIONS = [
        'assets.view',
        'borrowings.view',
        'borrowings.create',
        'borrowings.return',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_the_initial_roles_exist(): void
    {
        $this->assertSame(
            ['admin', 'guru', 'siswa'],
            Role::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_admin_has_the_expected_permissions(): void
    {
        $this->assertRolePermissions('admin', self::ADMIN_PERMISSIONS);
    }

    public function test_guru_has_only_borrower_permissions(): void
    {
        $this->assertRolePermissions('guru', self::BORROWER_PERMISSIONS);
        $this->assertFalse(Role::findByName('guru')->hasPermissionTo('users.view'));
        $this->assertFalse(Role::findByName('guru')->hasPermissionTo('borrowings.approve'));
    }

    public function test_siswa_has_only_borrower_permissions(): void
    {
        $this->assertRolePermissions('siswa', self::BORROWER_PERMISSIONS);
        $this->assertFalse(Role::findByName('siswa')->hasPermissionTo('users.view'));
        $this->assertFalse(Role::findByName('siswa')->hasPermissionTo('borrowings.verify-return'));
    }

    public function test_users_can_receive_roles_and_permission_checks_use_those_roles(): void
    {
        $user = User::factory()->create();

        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->can('users.create'));
        $this->assertTrue($user->can('borrowings.approve'));
    }

    public function test_permission_middleware_rejects_an_authenticated_user_without_permission(): void
    {
        Route::get('/authorization-foundation/users', static fn () => response()->noContent())
            ->middleware(['auth', 'permission:users.view']);

        $guru = User::factory()->create();
        $guru->assignRole('guru');

        $this->actingAs($guru)
            ->get('/authorization-foundation/users')
            ->assertForbidden();
    }

    public function test_permission_middleware_allows_an_authorized_user(): void
    {
        Route::get('/authorization-foundation/users', static fn () => response()->noContent())
            ->middleware(['auth', 'permission:users.view']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/authorization-foundation/users')
            ->assertNoContent();
    }

    /**
     * @param  list<string>  $expectedPermissions
     */
    private function assertRolePermissions(string $roleName, array $expectedPermissions): void
    {
        $actualPermissions = Role::findByName($roleName)
            ->permissions()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        sort($expectedPermissions);

        $this->assertSame($expectedPermissions, $actualPermissions);
        $this->assertSame(18, Permission::query()->count());
    }
}
