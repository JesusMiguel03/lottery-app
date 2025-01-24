<?php

namespace Database\Seeders;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [];
        for ($i = 0; $i < 100; $i++) {
            $now = Carbon::now('utc')->toDateTimeString();
            $clients[] = [
                'name' => fake()->name(),
                'last_name' => fake()->lastName(),
                'code' => fake()->randomElement(['0412', '0414', '0416', '0424', '0426']),
                'phone' => fake()->numberBetween(000_00_00, 999_99_99),
                'doc' => fake()->numberBetween(000_000_000, 999_999_999),
                'doc_type' => fake()->randomElement(['V', 'E', 'J', 'G']),
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        Client::insert($clients);
        Client::create([
            'name' => 'Lorem',
            'last_name' => 'Lorem',
            'code' => '0424',
            'phone' => 3011753,
            'doc' => fake()->numberBetween(000_000_000, 999_999_999),
            'doc_type' => fake()->randomElement(['V', 'E', 'J', 'G']),
        ]);
    }
}
