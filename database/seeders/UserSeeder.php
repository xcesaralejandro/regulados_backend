<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::create([
            'name' => 'Pulento',
            'surname' => 'césar',
            'gender' => 'male',
            'custom_gender' => null,
            'semester' => 1,
            'email' => 'c@c.cl',
            'phone' => '123456789',
            'birthdate' => '1995-12-20',
            'program_id' => 1,
            'avatar' => 'avatar.jpg'
        ]);
    }
}
