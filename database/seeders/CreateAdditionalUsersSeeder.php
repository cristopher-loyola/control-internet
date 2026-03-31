<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CreateAdditionalUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => '6',
                'email' => '6@gmail.com',
                'password' => '12345678',
                'role' => 'rosalito',
            ],
            [
                'name' => '7',
                'email' => '7@gmail.com',
                'password' => '12345678',
                'role' => 'pozo_hondo',
            ],
            [
                'name' => '8',
                'email' => '8@gmail.com',
                'password' => '12345678',
                'role' => 'chivato',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
            // Update role and name for existing users
            $user->update([
                'name' => $userData['name'],
                'role' => $userData['role'],
            ]);
        }
    }
}
