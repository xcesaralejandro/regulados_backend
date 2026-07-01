<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(InstitutionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(EventCategorySeeder::class);
    }
}
