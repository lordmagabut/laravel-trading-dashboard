<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradingBotPair extends Model
{
    protected $table = 'trading_bot_pairs';

    public const AGENT_RISK_MODES = ['conservative', 'balanced', 'aggressive'];

    protected $fillable = [
        'symbol',
        'entry_timeframe',
        'enabled',
        'auto_generate',
        'higher_timeframes',
        'agent_risk_mode',
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

    public function agentProfile(): array
    {
        $mode = in_array($this->agent_risk_mode, self::AGENT_RISK_MODES, true)
            ? $this->agent_risk_mode
            : 'balanced';

        return self::agentProfileForMode($mode);
    }

    public static function defaultAgentProfile(): array
    {
        return self::agentProfileForMode('balanced');
    }

    public static function agentProfileForMode(string $mode): array
    {
        if (! in_array($mode, self::AGENT_RISK_MODES, true)) {
            $mode = 'balanced';
        }

        return [
            'risk_mode' => $mode,
            'policy' => match ($mode) {
                'conservative' => [
                    'smc_can_override_classic' => false,
                    'neutral_execution_allows_limit' => false,
                    'market_entry_requires_momentum' => true,
                    'conflict_is_veto' => true,
                    'min_confidence' => 75,
                ],
                'aggressive' => [
                    'smc_can_override_classic' => true,
                    'neutral_execution_allows_limit' => true,
                    'market_entry_requires_momentum' => true,
                    'conflict_is_veto' => false,
                    'min_confidence' => 55,
                ],
                default => [
                    'smc_can_override_classic' => true,
                    'neutral_execution_allows_limit' => true,
                    'market_entry_requires_momentum' => true,
                    'conflict_is_veto' => false,
                    'min_confidence' => 65,
                ],
            },
        ];
    }
}
