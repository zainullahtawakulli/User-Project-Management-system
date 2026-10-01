<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full system access.',
            ]
        );

        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Administrative access.',
            ]
        );

        $user = Role::updateOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'User',
                'description' => 'Basic application access.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Users
            [
                'name' => 'View Users',
                'slug' => 'users.view',
                'group' => 'Users',
            ],
            [
                'name' => 'Create Users',
                'slug' => 'users.create',
                'group' => 'Users',
            ],
            [
                'name' => 'Update Users',
                'slug' => 'users.update',
                'group' => 'Users',
            ],
            [
                'name' => 'Delete Users',
                'slug' => 'users.delete',
                'group' => 'Users',
            ],

            // Projects
            [
                'name' => 'View Projects',
                'slug' => 'projects.view',
                'group' => 'Projects',
            ],
            [
                'name' => 'Create Projects',
                'slug' => 'projects.create',
                'group' => 'Projects',
            ],
            [
                'name' => 'Update Projects',
                'slug' => 'projects.update',
                'group' => 'Projects',
            ],
            [
                'name' => 'Delete Projects',
                'slug' => 'projects.delete',
                'group' => 'Projects',
            ],

            // Tasks
            [
                'name' => 'View Tasks',
                'slug' => 'tasks.view',
                'group' => 'Tasks',
            ],
            [
                'name' => 'Create Tasks',
                'slug' => 'tasks.create',
                'group' => 'Tasks',
            ],
            [
                'name' => 'Update Tasks',
                'slug' => 'tasks.update',
                'group' => 'Tasks',
            ],
            [
                'name' => 'Delete Tasks',
                'slug' => 'tasks.delete',
                'group' => 'Tasks',
            ],

            // Roles
            [
                'name' => 'View Roles',
                'slug' => 'roles.view',
                'group' => 'Roles',
            ],
            [
                'name' => 'Create Roles',
                'slug' => 'roles.create',
                'group' => 'Roles',
            ],
            [
                'name' => 'Update Roles',
                'slug' => 'roles.update',
                'group' => 'Roles',
            ],
            [
                'name' => 'Delete Roles',
                'slug' => 'roles.delete',
                'group' => 'Roles',
            ],
            [
                'name' => 'Manage Role Permissions',
                'slug' => 'roles.permissions',
                'group' => 'Roles',
            ],
            [
                'name' => 'Activity Log',
                'slug' => 'activity_logs.view',
                'group' => 'Activity'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'slug' => $permission['slug'],
                ],
                $permission
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Attach permissions
        |--------------------------------------------------------------------------
        */

        $allPermissions = Permission::pluck('id');

        // Super admin gets everything.
        $superAdmin->permissions()->sync($allPermissions);

        // Admin permissions.
        $adminPermissions = Permission::whereIn('slug', [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',

            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
        ])->pluck('id');

        $admin->permissions()->sync($adminPermissions);

        // Normal user permissions.
        $userPermissions = Permission::whereIn('slug', [
            'projects.view',
            'tasks.view',
            'tasks.update',
        ])->pluck('id');

        $user->permissions()->sync($userPermissions);
    }
}