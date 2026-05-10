<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_bot_pairs', function (Blueprint $table) {
            $table->id();

            $table->string('symbol', 30);
            $table->string('entry_timeframe', 10);

            $table->boolean('enabled')->default(true);
            $table->boolean('auto_generate')->default(true);

            $table->json('higher_timeframes')->nullable();

            $table->dateTime('last_checked_at')->nullable();
            $table->dateTime('last_generated_at')->nullable();
            $table->dateTime('last_generated_candle_time')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['symbol', 'entry_timeframe'], 'trading_bot_pairs_unique_symbol_tf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_bot_pairs');
    }
};