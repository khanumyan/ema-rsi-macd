<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('symbol', 20)->default('BTCUSDT');
            $table->string('interval', 10)->default('15m');
            $table->unsignedTinyInteger('candles_limit')->default(200);

            // take_profit / stop_loss
            $table->enum('tp_sl_mode', ['atr', 'percent'])->default('atr');
            $table->decimal('tp_multiplier', 6, 2)->default(2.50); // ATR × или %
            $table->decimal('sl_multiplier', 6, 2)->default(1.50);

            // режим работы
            $table->enum('mode', ['telegram', 'autotrading'])->default('telegram');
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('telegram_chat_id', 60)->nullable();

            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_strategies');
    }
};
