<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Domain;


class DomainSeeder extends Seeder
{
    public function run(): void
    {
       Domain::create(['domain' => 'yamslogistics.com']);
       Domain::create(['domain' => 'yamsgroup.com']);
       Domain::create(['domain' => 'yamscorporate.com']);
    }
}