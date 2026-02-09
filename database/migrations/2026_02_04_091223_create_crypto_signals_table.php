<?php

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
        Schema::create('crypto_signals', function (Blueprint $table) {
            $table->id();
            
            // ===== ОСНОВНЫЕ ДАННЫЕ СИГНАЛА =====
            $table->string('symbol', 20)->index(); // BTCUSDT, ETHUSDT, etc.
            $table->string('strategy', 50)->default('EMA+RSI+MACD'); // Название стратегии
            $table->enum('type', ['BUY', 'SELL', 'HOLD'])->index(); // Тип сигнала (результат анализа)
            $table->enum('strength', ['STRONG', 'MEDIUM', 'WEAK'])->index(); // Сила сигнала (на основе разницы вероятностей)
            
            // ===== ЦЕНЫ =====
            $table->decimal('price', 20, 8); // Текущая цена закрытия последней свечи
            
            // ===== ИНДИКАТОРЫ EMA (Exponential Moving Average) =====
            $table->decimal('ema', 20, 8)->nullable()->comment('EMA(20) - быстрая экспоненциальная скользящая средняя');
            $table->decimal('ema_slow', 20, 8)->nullable()->comment('EMA(50) - медленная экспоненциальная скользящая средняя');
            
            // ===== ИНДИКАТОР RSI (Relative Strength Index) =====
            $table->decimal('rsi', 8, 4)->nullable()->comment('RSI(14) - индекс относительной силы, диапазон 0-100');
            
            // ===== ИНДИКАТОР MACD (Moving Average Convergence Divergence) =====
            $table->decimal('macd', 20, 8)->nullable()->comment('MACD Line = EMA(12) - EMA(26)');
            $table->decimal('macd_signal', 20, 8)->nullable()->comment('MACD Signal Line = EMA(9) от MACD Line');
            $table->decimal('macd_histogram', 20, 8)->nullable()->comment('MACD Histogram = MACD Line - Signal Line');
            
            // ===== ИНДИКАТОР ATR (Average True Range) =====
            $table->decimal('atr', 20, 8)->nullable()->comment('ATR(14) - средний истинный диапазон для расчета SL/TP');
            
            // ===== УРОВНИ ВХОДА/ВЫХОДА =====
            $table->decimal('stop_loss', 20, 8)->nullable()->comment('Stop Loss = цена ± (ATR × 2.3)');
            $table->decimal('take_profit', 20, 8)->nullable()->comment('Take Profit = цена ± (ATR × 2.0)');
            
            // ===== БАЛЛЬНАЯ СИСТЕМА (критерии для генерации сигнала) =====
            $table->integer('long_score')->default(0)->comment('Сумма баллов для BUY сигнала (макс ~100)');
            $table->integer('short_score')->default(0)->comment('Сумма баллов для SELL сигнала (макс ~100)');
            $table->integer('long_probability')->default(0)->comment('Вероятность BUY в % (long_score / total_score × 100)');
            $table->integer('short_probability')->default(0)->comment('Вероятность SELL в % (short_score / total_score × 100)');
            
            // ===== ПАРАМЕТРЫ ЗАПРОСА ДАННЫХ =====
            $table->string('interval', 10)->default('15m')->comment('Таймфрейм свечей (15m, 1h, 4h, etc.)');
            $table->integer('limit')->default(200)->comment('Количество свечей, использованных для расчета');
            
            // ===== ДОПОЛНИТЕЛЬНЫЕ ПАРАМЕТРЫ =====
            $table->decimal('volume_ratio', 10, 4)->nullable()->default(1.0)->comment('Отношение объемов (для будущего использования)');
            $table->string('htf_trend', 20)->nullable()->default('N/A')->comment('Тренд на старшем таймфрейме (для будущего использования)');
            $table->decimal('htf_rsi', 8, 4)->nullable()->default(0)->comment('RSI на старшем таймфрейме (для будущего использования)');
            $table->decimal('ltf_rsi', 8, 4)->nullable()->default(0)->comment('RSI на младшем таймфрейме (для будущего использования)');
            
            // ===== МЕТАДАННЫЕ =====
            $table->text('reason')->nullable()->comment('Текстовое описание причины сигнала (формируется автоматически)');
            $table->boolean('sent_to_telegram')->default(false)->index()->comment('Была ли отправка в Telegram (true только для STRONG/MEDIUM)');
            
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['symbol', 'type', 'strength', 'created_at']);
            $table->index(['symbol', 'strategy', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_signals');
    }
};
