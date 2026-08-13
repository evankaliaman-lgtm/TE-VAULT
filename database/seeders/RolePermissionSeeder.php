<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private const string GUARD_NAME = 'web';

    /**
     * @var array<string, list<string>>
     */
    private const array ROLE_PERMISSIONS = [
        'admin' => [
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
        ],
        'guru' => [
            'assets.view',
            'borrowings.view',
            'borrowings.create',
            'borrowings.return',
        ],
        'siswa' => [
            'assets.view',
            'borrowings.view',
            'borrowings.create',
            'borrowings.return',
        ],
    ];

    /**
     * Create the initial TE-VAULT authorization vocabulary and assignments.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::ROLE_PERMISSIONS)
            ->flatten()
            ->unique()
            ->mapWithKeys(fn (string $name): array => [
                $name => Permission::query()->firstOrCreate([
                    'name' => $name,
                    'guard_name' => self::GUARD_NAME,
                ]),
            ]);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionNames) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => self::GUARD_NAME,
            ]);

            $role->syncPermissions($permissions->only($permissionNames));
        }
    }
}
