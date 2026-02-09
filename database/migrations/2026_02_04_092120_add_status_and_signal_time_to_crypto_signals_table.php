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
        Schema::table('crypto_signals', function (Blueprint $table) {
            // Время создания сигнала (для проверки статуса)
            // По умолчанию используем created_at, но можно задать отдельно
            $table->timestamp('signal_time')->nullable()->after('created_at')
                ->comment('Время создания сигнала (для проверки статуса). Если NULL, используется created_at');
            
            // Статус сигнала: DONE (TP достигнут), MISSED (SL достигнут), PROCESSING (в процессе)
            $table->enum('status', ['DONE', 'MISSED', 'PROCESSING'])->nullable()->after('signal_time')
                ->comment('Статус сигнала: DONE (TP достигнут), MISSED (SL достигнут), PROCESSING (в процессе)');
            
            // Индекс для быстрого поиска сигналов без статуса
            $table->index(['status', 'signal_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_signals', function (Blueprint $table) {
            $table->dropIndex(['status', 'signal_time']);
            $table->dropColumn(['status', 'signal_time']);
        });
    }
};
