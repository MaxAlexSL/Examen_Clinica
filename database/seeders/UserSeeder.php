<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Administrador Clínica', 'email' => 'clinica@example.com', 'role' => 'Administrador'],
            ['name' => 'Personal de Admisión', 'email' => 'admision@example.com', 'role' => 'Admisión'],
            ['name' => 'Vendedor Uno', 'email' => 'vendedor1@example.com', 'role' => 'Vendedor'],
            ['name' => 'Vendedor Dos', 'email' => 'vendedor2@example.com', 'role' => 'Vendedor'],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'clinica123', // el cast "hashed" del modelo la encripta
                    'is_active' => true,
                ],
            );

            // Un solo rol por usuario.
            $user->syncRoles([$data['role']]);
        }
    }
}
