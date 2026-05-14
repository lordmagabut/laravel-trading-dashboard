<?php

namespace App\Services;

use App\Models\FundamentalAnalysis;

class FundamentalAnalysisPromptService
{
    public function buildPrompt(FundamentalAnalysis $analysis): string
    {
        $context = $analysis->raw_context_json;

        $prompt = "You are the Fundamental Agent for trading analysis.\n\n";
        $prompt .= "Symbol: {$analysis->symbol}\n";
        $prompt .= "Timeframe Scope: {$analysis->timeframe_scope}\n\n";

        $prompt .= "Fundamental Context:\n";
        $prompt .= json_encode($context, JSON_PRETTY_PRINT) . "\n\n";

        $prompt .= "Instructions:\n";
        $prompt .= "- Analyze news events, economic calendar, sentiment, and macro indicators.\n";
        $prompt .= "- Determine fundamental_bias: bullish, bearish, or neutral.\n";
        $prompt .= "- Assess news_risk_level: low, medium, or high.\n";
        $prompt .= "- Evaluate sentiment_bias: bullish, bearish, or neutral.\n";
        $prompt .= "- Set avoid_trade to true if high risk or conflicting signals.\n";
        $prompt .= "- Provide confidence (0-100).\n";
        $prompt .= "- Give reason_summary and detailed reasons_json.\n\n";

        $prompt .= "Output only valid JSON with keys: fundamental_bias, news_risk_level, sentiment_bias, avoid_trade, confidence, reason_summary, reasons_json.\n";

        return $prompt;
    }
}