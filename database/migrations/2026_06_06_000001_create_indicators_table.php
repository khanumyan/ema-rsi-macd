<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // "RSI"
            $table->string('short_name', 30);        // "rsi"
            $table->string('category', 40);          // "momentum", "trend", "volatility", "volume"
            $table->text('description')->nullable();
            // JSON: [{"name":"period","type":"int","default":14,"label":"Период"},...]
            $table->json('params')->nullable();
            // JSON: список возможных output-полей: ["rsi"], ["macd","signal","histogram"]
            $table->json('outputs')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indicators');
    }
};
