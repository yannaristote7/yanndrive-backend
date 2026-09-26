<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Domain;


class DomainSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['yamslogistics.com', 'yamsgroup.com', 'yamscorporate.com'] as $domain) {
            Domain::firstOrCreate(['domain' => $domain]);
        }
    }
}