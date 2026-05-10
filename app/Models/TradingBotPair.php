<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingBotPair extends Model
{
    protected $table = 'trading_bot_pairs';

    protected $fillable = [
        'symbol',
        'entry_timeframe',
        'enabled',
        'auto_generate',
        'higher_timeframes',
        'last_checked_at',
        'last_generated_at',
        'last_generated_candle_time',
        'notes',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'auto_generate' => 'boolean',
        'higher_timeframes' => 'array',
        'last_checked_at' => 'datetime',
        'last_generated_at' => 'datetime',
        'last_generated_candle_time' => 'datetime',
    ];
}