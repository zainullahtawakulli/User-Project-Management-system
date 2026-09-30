<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where(
            'slug',
            'super_admin'
        )->firstOrFail();

        $adminRole = Role::where(
            'slug',
            'admin'
        )->firstOrFail();

        $userRole = Role::where(
            'slug',
            'user'
        )->firstOrFail();

        User::updateOrCreate(
            [
                'email' => 'superadmin@example.com',
            ],
            [
                'name' => 'Super Admin',
                'password' => 'password123',
                'role_id' => $superAdminRole->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            [
                'email' => 'admin@example.com',
            ],
            [
                'name' => 'Admin User',
                'password' => 'password123',
                'role_id' => $adminRole->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            [
                'email' => 'user@example.com',
            ],
            [
                'name' => 'Normal User',
                'password' => 'password123',
                'role_id' => $userRole->id,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            [
                'email' => 'pending@example.com',
            ],
            [
                'name' => 'Pending User',
                'password' => 'password123',
                'role_id' => $userRole->id,
                'status' => 'pending',
            ]
        );
    }
}
