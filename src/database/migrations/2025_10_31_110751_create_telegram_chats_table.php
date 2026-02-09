<?php

use App\Models\TelegramBot;
use App\Models\TelegramUser;
use App\Models\User;
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
            $table->foreignIdFor(TelegramBot::class);
            $table->foreignIdFor(TelegramUser::class);
            $table->foreignIdFor(User::class);
            $table->UnsignedBigInteger('chat_id');
            $table->string('type', 32)->nullable();
            $table->string('ticket_type', 32);
            $table->string('ticket_id', 32);
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->boolean('has_new')->default(false);
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
