<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fundamental_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('analysis_uuid')->unique();
            $table->string('symbol');
            $table->string('timeframe_scope')->nullable();
            $table->enum('fundamental_bias', ['bullish', 'bearish', 'neutral']);
            $table->enum('news_risk_level', ['low', 'medium', 'high']);
            $table->enum('sentiment_bias', ['bullish', 'bearish', 'neutral']);
            $table->boolean('avoid_trade')->default(false);
            $table->integer('confidence')->unsigned();
            $table->text('reason_summary');
            $table->json('reasons_json');
            $table->json('raw_context_json')->nullable();
            $table->json('ai_response_json')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('agent_model')->nullable();
            $table->enum('status', ['GENERATED', 'SENT_TO_AGENT', 'COMPLETED', 'FAILED'])->default('GENERATED');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fundamental_analyses');
    }
};
