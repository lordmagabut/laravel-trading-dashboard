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
        Schema::create('technical_analyses', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identitas Analisa
            |--------------------------------------------------------------------------
            */
            $table->uuid('analysis_uuid')->unique();

            $table->string('symbol', 20);
            $table->string('execution_timeframe', 10)->default('M15');

            /*
            |--------------------------------------------------------------------------
            | Ringkasan Bias dari Technical Context API
            |--------------------------------------------------------------------------
            | Contoh:
            | higher_timeframe_bias = bullish / bearish / neutral
            | execution_bias        = bullish / bearish / neutral
            | preferred_action      = LOOK_FOR_BUY / LOOK_FOR_SELL / NO_TRADE
            */
            $table->string('higher_timeframe_bias', 30)->nullable();
            $table->string('execution_bias', 30)->nullable();
            $table->string('preferred_action', 30)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Harga saat analisa dibuat
            |--------------------------------------------------------------------------
            */
            $table->decimal('current_price', 20, 8)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Hasil Perhitungan Teknis
            |--------------------------------------------------------------------------
            | raw_context_json = output dari TechnicalContextService
            | ai_response_json = jawaban OpenClaw / AI agent
            */
            $table->json('raw_context_json')->nullable();
            $table->longText('prompt_text')->nullable();
            $table->json('ai_response_json')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Metadata Agent
            |--------------------------------------------------------------------------
            */
            $table->string('agent_name', 100)->nullable();
            $table->string('agent_model', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Kesimpulan AI
            |--------------------------------------------------------------------------
            | decision: BUY / SELL / NO_TRADE
            */
            $table->string('decision', 30)->default('NO_TRADE');
            $table->unsignedTinyInteger('confidence')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status Workflow
            |--------------------------------------------------------------------------
            | GENERATED     = context dibuat
            | SENT_TO_AI    = sudah dikirim ke OpenClaw
            | AI_COMPLETED  = AI sudah memberi jawaban
            | SIGNAL_CREATED = sinyal dibuat
            | FAILED        = proses gagal
            */
            $table->string('status', 30)->default('GENERATED');

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */
            $table->index(['symbol', 'execution_timeframe'], 'idx_ta_symbol_tf');
            $table->index(['symbol', 'created_at'], 'idx_ta_symbol_created');
            $table->index('decision', 'idx_ta_decision');
            $table->index('status', 'idx_ta_status');
            $table->index('created_at', 'idx_ta_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_analyses');
    }
};