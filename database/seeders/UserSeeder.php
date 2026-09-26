<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Comptes de démo avec mot de passe connu : jamais en production
        if (app()->environment('production')) {
            $this->command?->warn('UserSeeder ignoré en production.');
            return;
        }

        $role = Role::firstOrCreate(['name' => 'user']);

        $users = [
            ['name' => 'Alice Martin',  'email' => 'alice@yamslogistics.com'],
            ['name' => 'Bob Dupont',    'email' => 'bob@yamsgroup.com'],
            ['name' => 'Claire Durand', 'email' => 'claire@yamscorporate.com'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], [
                'name'     => $u['name'],
                'password' => Hash::make('password'),
                'role_id'  => $role->id
            ]);
        }
    }
}