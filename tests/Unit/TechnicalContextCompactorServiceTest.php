<?php

namespace Tests\Unit;

use App\Services\TechnicalAnalysisPromptService;
use App\Services\TechnicalContextCompactorService;
use Tests\TestCase;

class TechnicalContextCompactorServiceTest extends TestCase
{
    public function test_it_compacts_context_for_agent_decision_making(): void
    {
        $service = new TechnicalContextCompactorService();

        $compact = $service->compact($this->context());

        $this->assertSame('XAUUSD', $compact['symbol']);
        $this->assertSame('M5', $compact['execution_timeframe']);
        $this->assertSame('LOOK_FOR_SELL', $compact['classic_summary']['preferred_action']);
        $this->assertSame('LOOK_FOR_BUY', $compact['smc_summary']['preferred_action']);
        $this->assertSame('aggressive', $compact['agent_profile']['risk_mode']);
        $this->assertTrue($compact['agent_profile']['policy']['neutral_execution_allows_limit']);
        $this->assertNotEmpty($compact['conflicts']);
        $this->assertSame('2026-05-14 04:45:00', $compact['context_candle_time']);
        $this->assertArrayNotHasKey('timeframes', $compact);
        $this->assertArrayHasKey('M15', $compact['supporting_timeframes']);
        $this->assertArrayNotHasKey('D1', $compact['supporting_timeframes']);
        $this->assertArrayNotHasKey('M5', $compact['supporting_timeframes']);

        $m5Smc = $compact['execution_timeframe_context']['smc'];
        $this->assertSame('bullish_choch', $m5Smc['last_internal_event']['type']);
        $this->assertCount(1, $m5Smc['fresh_order_blocks']);
        $this->assertCount(1, $m5Smc['open_fair_value_gaps']);
        $this->assertArrayNotHasKey('structure_detail', $m5Smc);
        $this->assertArrayNotHasKey('order_blocks', $m5Smc);
        $this->assertArrayNotHasKey('fair_value_gaps', $m5Smc);
    }

    public function test_prompt_service_uses_compact_context_instead_of_full_raw_context(): void
    {
        $promptService = new TechnicalAnalysisPromptService(new TechnicalContextCompactorService());

        $prompt = $promptService->build($this->context());

        $this->assertStringContainsString('Technical Context JSON:', $prompt);
        $this->assertStringContainsString('"classic_summary"', $prompt);
        $this->assertStringContainsString('"smc_summary"', $prompt);
        $this->assertStringContainsString('Risk mode: aggressive', $prompt);
        $this->assertStringNotContainsString('"structure_detail"', $prompt);
        $this->assertStringNotContainsString('"fair_value_gaps"', $prompt);
        $this->assertStringNotContainsString('old historical event that should not enter prompt', $prompt);
    }

    private function context(): array
    {
        return [
            'symbol' => 'XAUUSD',
            'execution_timeframe' => 'M5',
            'generated_at_utc' => '2026-05-14 04:50:38',
            'current_price' => 4705.234,
            'summary' => [
                'higher_timeframe_bias' => 'bearish',
                'execution_bias' => 'neutral',
                'preferred_action' => 'LOOK_FOR_SELL',
            ],
            'smc_summary' => [
                'higher_timeframe_bias' => 'bullish',
                'execution_bias' => 'neutral',
                'execution_structure' => 'ranging',
                'execution_last_event' => null,
                'preferred_action' => 'LOOK_FOR_BUY',
            ],
            'agent_profile' => [
                'risk_mode' => 'aggressive',
                'policy' => [
                    'smc_can_override_classic' => true,
                    'neutral_execution_allows_limit' => true,
                    'market_entry_requires_momentum' => true,
                    'conflict_is_veto' => false,
                    'min_confidence' => 55,
                ],
            ],
            'bias' => [
                'D1' => $this->classic('bullish'),
                'H4' => $this->classic('bearish'),
                'H1' => $this->classic('bearish'),
                'M15' => $this->classic('neutral'),
                'M5' => $this->classic('neutral'),
            ],
            'smc' => [
                'D1' => $this->smc('bullish', 'bullish'),
                'H4' => $this->smc('neutral', 'ranging'),
                'H1' => $this->smc('bullish', 'bullish'),
                'M15' => $this->smc('neutral', 'ranging'),
                'M5' => $this->smc('neutral', 'ranging'),
            ],
        ];
    }

    private function classic(string $bias): array
    {
        return [
            'bias' => $bias,
            'score' => $bias === 'bullish' ? 3 : ($bias === 'bearish' ? -3 : 0),
            'last_close' => 4705.234,
            'last_candle_time' => '2026-05-14 04:45:00',
            'ema' => ['ema20' => 4700, 'ema50' => 4690, 'ema200' => 4680],
            'atr14' => 6.26,
            'structure' => [
                'structure' => 'ranging',
                'bos' => 'none',
                'last_swing_high' => ['price' => 4710],
                'last_swing_low' => ['price' => 4668],
            ],
            'levels' => [
                'support' => [['price' => 4690], ['price' => 4680], ['price' => 4670], ['price' => 4660]],
                'resistance' => [['price' => 4710], ['price' => 4720], ['price' => 4730], ['price' => 4740]],
            ],
            'reason' => ['reason 1', 'reason 2'],
        ];
    }

    private function smc(string $bias, string $structure): array
    {
        return [
            'bias' => $bias,
            'score' => $bias === 'bullish' ? 4 : 0,
            'structure' => $structure,
            'last_event' => null,
            'events' => [
                ['type' => 'old historical event that should not enter prompt'],
            ],
            'liquidity_sweeps' => [
                [
                    'type' => 'buy_side_liquidity_sweep',
                    'time' => '2026-05-14 04:35:00',
                    'swept_level' => ['price' => 4710.041],
                    'wick_extreme' => 4712.871,
                    'close' => 4707.494,
                ],
            ],
            'premium_discount' => [
                'current_area' => 'range_middle',
                'range_high' => 4710.041,
                'range_low' => 4669.407,
                'equilibrium' => 4689.724,
            ],
            'structure_detail' => [
                'swing' => [
                    'trend' => $structure,
                    'last_event' => null,
                ],
                'internal' => [
                    'trend' => 'bullish',
                    'last_event' => [
                        'scope' => 'internal',
                        'type' => 'bullish_choch',
                        'tag' => 'CHoCH',
                        'direction' => 'bullish',
                        'time' => '2026-05-14 04:25:00',
                        'pivot_level' => 4693.223,
                        'break_price' => 4693.223,
                        'close' => 4702.478,
                        'previous_trend' => 'bearish',
                        'new_trend' => 'bullish',
                    ],
                ],
            ],
            'order_blocks' => [
                'swing' => ['bullish' => [], 'bearish' => []],
                'internal' => [
                    'bullish' => [
                        [
                            'type' => 'bullish_ob',
                            'scope' => 'internal',
                            'bias' => 'bullish',
                            'low' => 4668.043,
                            'high' => 4675.337,
                            'midpoint' => 4671.69,
                            'status' => 'fresh',
                            'created_by' => 'bullish_choch',
                            'origin_time' => '2026-05-14 04:00:00',
                        ],
                    ],
                    'bearish' => [
                        [
                            'type' => 'bearish_ob',
                            'scope' => 'internal',
                            'bias' => 'bearish',
                            'low' => 4700,
                            'high' => 4710,
                            'midpoint' => 4705,
                            'status' => 'invalidated',
                        ],
                    ],
                ],
            ],
            'liquidity' => [
                'equal_highs' => [['level' => 4695.25]],
                'equal_lows' => [['level' => 4694.78]],
            ],
            'fair_value_gaps' => [
                'bullish' => [
                    [
                        'top' => 4701.417,
                        'bottom' => 4692.45,
                        'midpoint' => 4696.9335,
                        'time' => '2026-05-14 04:30:00',
                        'status' => 'open',
                    ],
                ],
                'bearish' => [
                    [
                        'top' => 4688.151,
                        'bottom' => 4686.635,
                        'midpoint' => 4687.393,
                        'time' => '2026-05-14 03:35:00',
                        'status' => 'filled',
                    ],
                ],
            ],
            'strong_weak_levels' => [
                'high' => ['type' => 'weak_high', 'price' => 4710.041],
                'low' => ['type' => 'weak_low', 'price' => 4669.407],
            ],
            'reason' => ['Internal structure mendukung arah bullish.'],
        ];
    }
}
