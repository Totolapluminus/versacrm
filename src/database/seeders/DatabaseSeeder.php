<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'tech',
            'role' => 'tech',
            'email' => 'tech@example.com',
        ]);

        User::factory()->create([
            'name' => 'admin',
            'role' => 'admin',
            'email' => 'admin@yandex.ru',
            'password' => '12341234'
        ]);

        $this->call(TelegramBotsTableSeeder::class);
    }
}
