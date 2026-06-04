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
        $role = Role::where('name', 'user')->first();

        $users = [
            ['name' => 'Alice Martin',  'email' => 'alice@yamslogistics.com'],
            ['name' => 'Bob Dupont',    'email' => 'bob@yamsgroup.com'],
            ['name' => 'Claire Durand', 'email' => 'claire@yamscorporate.com'],
        ];

        foreach ($users as $u) {
            User::create([
                'name'     => $u['name'],
                'email'    => $u['email'],
                'password' => Hash::make('password'),
                'role_id'  => $role->id
            ]);
        }
    }
}