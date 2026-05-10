<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technical_analyses', function (Blueprint $table) {
            if (! Schema::hasColumn('technical_analyses', 'context_candle_time')) {
                $table->dateTime('context_candle_time')->nullable()->after('current_price');
            }
        });

        DB::statement("
            CREATE UNIQUE INDEX technical_analyses_unique_context_candle
            ON technical_analyses (symbol, execution_timeframe, context_candle_time)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX technical_analyses_unique_context_candle ON technical_analyses");

        Schema::table('technical_analyses', function (Blueprint $table) {
            $table->dropColumn('context_candle_time');
        });
    }
};