<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeSignal extends Model
{
    protected $table = 'trade_signals';

    protected $fillable = [
        'technical_analysis_id',
        'signal_uuid',
        'symbol',
        'timeframe',
        'decision',
        'side',
        'entry_type',
        'entry_price',
        'stop_loss',
        'take_profit_1',
        'take_profit_2',
        'take_profit_3',
        'risk_reward',
        'risk_percent',
        'lot_size',
        'confidence',
        'reason_summary',
        'reasons_json',
        'invalidation',
        'status',
        'approved_at',
        'executed_at',
        'expired_at',
        'executor_response_code',
        'executor_response_json',
        'notes',
    ];

    protected $casts = [
        'entry_price' => 'decimal:5',
        'stop_loss' => 'decimal:5',
        'take_profit_1' => 'decimal:5',
        'take_profit_2' => 'decimal:5',
        'take_profit_3' => 'decimal:5',
        'risk_reward' => 'decimal:2',
        'risk_percent' => 'decimal:2',
        'lot_size' => 'decimal:2',
        'confidence' => 'integer',
        'reasons_json' => 'array',
        'executor_response_json' => 'array',
        'approved_at' => 'datetime',
        'executed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function technicalAnalysis()
    {
        return $this->belongsTo(TechnicalAnalysis::class, 'technical_analysis_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['PENDING', 'APPROVED'], true);
    }
}