<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'view market chart',
            'view technical context',
            'manage technical analyses',
            'manage fundamental analyses',
            'manage bot pairs',
            'review trade signals',
            'manage users',
            'manage roles',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            'super-admin' => $permissions,
            'admin' => $permissions,
            'analyst' => [
                'view dashboard',
                'view market chart',
                'view technical context',
                'manage technical analyses',
                'manage fundamental analyses',
                'manage bot pairs',
            ],
            'reviewer' => [
                'view dashboard',
                'view market chart',
                'view technical context',
                'review trade signals',
            ],
            'viewer' => [
                'view dashboard',
                'view market chart',
                'view technical context',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        $adminEmail = env('ADMIN_EMAIL', 'admin@nomaden.site');
        $adminPassword = env('ADMIN_PASSWORD', 'ChangeMe123!');
        $adminName = env('ADMIN_NAME', 'Super Admin');

        $adminUser = User::query()->firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]
        );

        if (! $adminUser->email_verified_at) {
            $adminUser->email_verified_at = now();
            $adminUser->save();
        }

        $adminUser->assignRole('super-admin');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
