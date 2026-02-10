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
            // UUID потока: один UUID для всех сигналов, найденных за один запуск команды
            $table->uuid('flow_id')
                ->nullable()
                ->after('id')
                ->index()
                ->comment('UUID потока сигналов за один запуск команды');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crypto_signals', function (Blueprint $table) {
            $table->dropIndex(['flow_id']);
            $table->dropColumn('flow_id');
        });
    }
};



