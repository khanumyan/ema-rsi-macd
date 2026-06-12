<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('user_strategies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 20);
            $table->string('interval', 10);
            $table->enum('type', ['BUY', 'SELL']);
            $table->decimal('price', 20, 8);
            $table->decimal('take_profit', 20, 8)->nullable();
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('atr', 20, 8)->nullable();
            // снимок значений индикаторов на момент сигнала
            $table->json('indicator_values')->nullable();
            $table->enum('status', ['PROCESSING', 'DONE', 'MISSED'])->default('PROCESSING');
            $table->boolean('is_backtest')->default(false);
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['strategy_id', 'status']);
            $table->index(['user_id', 'triggered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_signals');
    }
};
