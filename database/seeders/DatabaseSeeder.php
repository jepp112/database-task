<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        for ($i = 0; $i < 100_000; $i++) {
            try {
                DB::table('deals')->insert([
                    'created_at' => fake()->dateTimeThisYear(),
                    'status_id' => rand(1, 5),
                ]);
            } catch (\Throwable) {

            }
        }
    }
}
