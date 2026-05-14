<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_bot_pairs', function (Blueprint $table) {
            $table->string('agent_risk_mode', 20)
                ->default('balanced')
                ->after('higher_timeframes');
        });
    }

    public function down(): void
    {
        Schema::table('trading_bot_pairs', function (Blueprint $table) {
            $table->dropColumn('agent_risk_mode');
        });
    }
};
