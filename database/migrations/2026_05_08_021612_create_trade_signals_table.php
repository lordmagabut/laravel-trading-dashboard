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
        Schema::create('trade_signals', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relasi ke technical_analyses
            |--------------------------------------------------------------------------
            */
            $table->foreignId('technical_analysis_id')
                ->nullable()
                ->constrained('technical_analyses')
                ->nullOnDelete();

            $table->uuid('signal_uuid')->unique();

            /*
            |--------------------------------------------------------------------------
            | Identitas Signal
            |--------------------------------------------------------------------------
            */
            $table->string('symbol', 20);
            $table->string('timeframe', 10)->default('M15');

            /*
            |--------------------------------------------------------------------------
            | Keputusan
            |--------------------------------------------------------------------------
            | decision = BUY / SELL / NO_TRADE
            | side     = buy / sell / null
            */
            $table->string('decision', 30)->default('NO_TRADE');
            $table->string('side', 10)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Entry Type
            |--------------------------------------------------------------------------
            | market / limit / stop / none
            */
            $table->string('entry_type', 20)->default('none');

            /*
            |--------------------------------------------------------------------------
            | Harga Trading Plan
            |--------------------------------------------------------------------------
            */
            $table->decimal('entry_price', 20, 8)->nullable();
            $table->decimal('stop_loss', 20, 8)->nullable();
            $table->decimal('take_profit_1', 20, 8)->nullable();
            $table->decimal('take_profit_2', 20, 8)->nullable();
            $table->decimal('take_profit_3', 20, 8)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Risk Management
            |--------------------------------------------------------------------------
            */
            $table->decimal('risk_reward', 10, 2)->nullable();
            $table->decimal('risk_percent', 5, 2)->nullable();
            $table->decimal('lot_size', 10, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Confidence dan Reason
            |--------------------------------------------------------------------------
            */
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->json('reasons_json')->nullable();
            $table->text('invalidation')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status Workflow
            |--------------------------------------------------------------------------
            | PENDING   = menunggu review
            | APPROVED  = disetujui manual / risk manager
            | REJECTED  = ditolak
            | EXECUTED  = sudah dikirim ke MT5
            | FAILED    = gagal eksekusi
            | CANCELLED = dibatalkan
            | EXPIRED   = signal kadaluarsa
            */
            $table->string('status', 30)->default('PENDING');

            /*
            |--------------------------------------------------------------------------
            | Execution Info
            |--------------------------------------------------------------------------
            */
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->string('executor_response_code', 50)->nullable();
            $table->json('executor_response_json')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */
            $table->index(['symbol', 'timeframe'], 'idx_signal_symbol_tf');
            $table->index(['symbol', 'created_at'], 'idx_signal_symbol_created');
            $table->index('decision', 'idx_signal_decision');
            $table->index('side', 'idx_signal_side');
            $table->index('status', 'idx_signal_status');
            $table->index('created_at', 'idx_signal_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_signals');
    }
};