<?php

namespace Database\Seeders;

use App\Models\TelegramBot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TelegramBotsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TelegramBot::create(['token' => '8288977334:AAGTqn-aWZkC4qZKEnT0X1Mu-Gn3xlksEQU']);
        TelegramBot::create(['token' => '8408254643:AAHlm2gzopL3_zUulZoJuH9Rmzn8M7tLwF0']);
        TelegramBot::create(['token' => '8189635334:AAFmtTfO4eblkebeE5Z-KYsmZdHIrZepSjA']);
    }
}
