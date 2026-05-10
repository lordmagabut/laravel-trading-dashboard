<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TechnicalAnalysis extends Model
{
    protected $fillable = [
        'analysis_uuid',
        'symbol',
        'execution_timeframe',
        'higher_timeframe_bias',
        'execution_bias',
        'preferred_action',
        'current_price',
        'raw_context_json',
        'prompt_text',
        'ai_response_json',
        'agent_name',
        'agent_model',
        'decision',
        'confidence',
        'reason_summary',
        'reasons_json',
        'status',
        'notes',
        'context_candle_time', 
    ];

    protected $casts = [
        'raw_context_json' => 'array',
        'ai_response_json' => 'array',
        'current_price' => 'decimal:8',
        'confidence' => 'integer',
        'context_candle_time' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->analysis_uuid)) {
                $model->analysis_uuid = (string) Str::uuid();
            }
        });
    }

    public function tradeSignals()
    {
        return $this->hasMany(TradeSignal::class, 'technical_analysis_id');
    }
}