<?php

namespace Database\Seeders;

use App\Models\User;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Wick',
            'email' => 'test@example.com',
            'phone' => '03017223846',
            'username' => 'faker007',
            'email_verified_at' => now(),
            'referral_code' => 'INE9999',
            'status' => 'active',
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);
    }
}
