<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategy_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained('user_strategies')->cascadeOnDelete();
            $table->enum('signal_type', ['BUY', 'SELL']);
            $table->foreignId('indicator_id')->constrained('indicators');

            // какой output индикатора сравниваем (напр. "rsi", "macd", "histogram")
            $table->string('indicator_output', 30)->default('value');
            // переопределение параметров индикатора: {"period":21}
            $table->json('param_overrides')->nullable();

            // оператор: >, <, >=, <=, =, between, crosses_above, crosses_below
            $table->string('operator', 20);

            // правая часть: число или имя другого индикатора-output
            $table->string('value_a', 50)->nullable();  // нижняя граница / единственное значение
            $table->string('value_b', 50)->nullable();  // верхняя граница (для between)

            // AND / OR к следующему условию в этом signal_type
            $table->enum('next_logic', ['AND', 'OR'])->default('AND');
            $table->unsignedTinyInteger('sort_order')->default(0);

            $table->timestamps();
            $table->index(['strategy_id', 'signal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategy_conditions');
    }
};
