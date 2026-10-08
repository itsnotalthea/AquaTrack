<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['first_name' => 'Tom', 'last_name' => 'Reyes', 'email' => 'tom@admin.com'],
            ['first_name' => 'Jerry', 'last_name' => 'Lim', 'email' => 'jerry@admin.com'],
            ['first_name' => 'Althea', 'last_name' => 'Aqua', 'email' => 'althea@staff.com'],
            ['first_name' => 'Magzy', 'last_name' => 'Aqua', 'email' => 'magzy@staff.com'],
            ['first_name' => 'Clark', 'last_name' => 'Aqua', 'email' => 'clark@staff.com'],
            ['first_name' => 'Miguel', 'last_name' => 'Aqua', 'email' => 'miguel@staff.com'],
            ['first_name' => 'Lee', 'last_name' => 'Aqua', 'email' => 'lee@staff.com'],
            ['first_name' => 'Walter', 'last_name' => 'de la Cruz', 'email' => 'walter@delivery.com'],
            ['first_name' => 'Jesse', 'last_name' => 'Perez', 'email' => 'jesse@delivery.com'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user + [
                    'password' => Hash::make('password'),
                    'role' => User::resolveRole($user['email']),
                    'city' => 'Marikina City',
                ]
            );
        }
    }
}
