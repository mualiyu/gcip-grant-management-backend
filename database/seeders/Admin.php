<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Admin extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'mualiyuoox@gmail.com',
            'phone' => "2348167236629",
            'username' => 'admin',
            'password' => Hash::make('Test1234'), //Admin password
        ]);
    }
}
