<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundamentalAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'analysis_uuid',
        'symbol',
        'timeframe_scope',
        'fundamental_bias',
        'news_risk_level',
        'sentiment_bias',
        'avoid_trade',
        'confidence',
        'reason_summary',
        'reasons_json',
        'raw_context_json',
        'ai_response_json',
        'agent_name',
        'agent_model',
        'status',
        'notes',
    ];

    protected $casts = [
        'reasons_json' => 'array',
        'raw_context_json' => 'array',
        'ai_response_json' => 'array',
        'avoid_trade' => 'boolean',
        'confidence' => 'integer',
    ];
}
