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
        $admin = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);

        // En production : admin initial uniquement via ADMIN_EMAIL / ADMIN_PASSWORD
        // (jamais de mot de passe connu). En local : compte de démo.
        if (app()->environment('production')) {
            $email = env('ADMIN_EMAIL');
            $password = env('ADMIN_PASSWORD');

            if (! $email || ! $password) {
                $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD non définis : aucun admin créé.');
                return;
            }
        } else {
            $email = 'admin@yams.com';
            $password = 'password';
        }

        User::firstOrCreate(['email' => $email], [
            'name'     => 'Admin',
            'password' => Hash::make($password),
            'role_id'  => $admin->id,
        ]);
    }
}
