<?php

use App\Models\TelegramBot;
use App\Models\TelegramUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TelegramBot::class)->nullable();
            $table->foreignIdFor(TelegramUser::class);
            $table->UnsignedBigInteger('chat_id');
            $table->string('type', 32)->nullable();
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_chats');
    }
};
