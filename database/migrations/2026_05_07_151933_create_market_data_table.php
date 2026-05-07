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
        Schema::create('market_data', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identitas Market
            |--------------------------------------------------------------------------
            | Diisi dari Python feeder:
            | symbol    = XAUUSD, EURUSD, GBPUSD, USDJPY, AUDJPY, dll
            | timeframe = M1, M5, M15, M30, H1, H4, D1
            */
            $table->string('symbol', 20);
            $table->string('timeframe', 10);

            /*
            |--------------------------------------------------------------------------
            | Waktu Candle MT5
            |--------------------------------------------------------------------------
            | tick_time berasal dari candle["time"] MT5.
            | Disimpan sebagai UTC datetime.
            */
            $table->dateTime('tick_time');

            /*
            |--------------------------------------------------------------------------
            | OHLC
            |--------------------------------------------------------------------------
            | decimal(20,8) aman untuk forex, gold, crypto, index.
            */
            $table->decimal('open', 20, 8);
            $table->decimal('high', 20, 8);
            $table->decimal('low', 20, 8);
            $table->decimal('close', 20, 8);

            /*
            |--------------------------------------------------------------------------
            | Volume
            |--------------------------------------------------------------------------
            | Dari MT5 tick_volume, dikirim Python sebagai volume.
            */
            $table->unsignedBigInteger('volume')->default(0);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Unique Key untuk UPSERT Python
            |--------------------------------------------------------------------------
            | Ini wajib supaya script Python:
            | ON DUPLICATE KEY UPDATE
            | bisa bekerja.
            */
            $table->unique(
                ['symbol', 'timeframe', 'tick_time'],
                'unique_market_candle'
            );

            /*
            |--------------------------------------------------------------------------
            | Index untuk Dashboard dan Query Analisa
            |--------------------------------------------------------------------------
            */
            $table->index(
                ['symbol', 'timeframe', 'tick_time'],
                'idx_market_symbol_timeframe_time'
            );

            $table->index('tick_time', 'idx_market_tick_time');
            $table->index('symbol', 'idx_market_symbol');
            $table->index('timeframe', 'idx_market_timeframe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_data');
    }
};