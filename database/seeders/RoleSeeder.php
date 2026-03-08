<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insertion des rôles
        Role::insert([
            ['name' => 'admin'],
            ['name' => 'user'],
        ]);

        // Création de l'utilisateur admin
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@yams.com',
            'password' => Hash::make('password'),
            'role_id'  => 1, // admin
        ]);
    }
}
