<?php

use App\Models\TelegramBot;
use App\Models\TelegramChat;
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
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TelegramBot::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(TelegramUser::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(TelegramChat::class)->constrained()->cascadeOnDelete();
            $table->string('direction');
            $table->text('text')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
