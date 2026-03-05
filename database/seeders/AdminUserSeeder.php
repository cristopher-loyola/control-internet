<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => '1@gmail.com'],
            [
                'name' => 'Administrador',
                'password' => '12345678',
                'role' => 'admin',
            ]
        );
    }
}
