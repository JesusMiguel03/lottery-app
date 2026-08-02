<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ["email" => "admin@demo.com"],
            [
                "name" => "Admin Demo",
                "password" => Hash::make("demo"),
            ]
        );
    }
}
