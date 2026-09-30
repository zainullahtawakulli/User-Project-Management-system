<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(RolePermissionSeeder::class);
        $this->call(UserSeeder::class);

        User::updateOrCreate(
            ['email' => env('USER_EMAIL', 'user@example.com')],
            [
                'name' => 'Regular User',
                'password' => env('USER_PASSWORD', 'password'),
                'status' => 'active',
                'role' => 'user',
            ],
        );
    }
}
