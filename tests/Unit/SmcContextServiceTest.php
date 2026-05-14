<?php

namespace Tests\Unit;

use App\Services\SmcContextService;
use Tests\TestCase;

class SmcContextServiceTest extends TestCase
{
    public function test_it_returns_insufficient_data_for_short_candle_series(): void
    {
        $service = new SmcContextService();

        $result = $service->analyze(array_slice($this->waveCandles(), 0, 10), 'M15');

        $this->assertSame('insufficient_data', $result['status']);
        $this->assertSame('unknown', $result['structure']);
        $this->assertSame('neutral', $result['bias']);
        $this->assertSame(0, $result['score']);
    }

    public function test_it_keeps_legacy_contract_and_adds_richer_smc_context(): void
    {
        $service = new SmcContextService();

        $result = $service->analyze($this->waveCandles(), 'M15', [
            'swing_length' => 2,
            'internal_length' => 2,
            'equal_high_low_length' => 2,
            'fvg_auto_threshold' => false,
        ]);

        $this->assertSame('ok', $result['status']);

        foreach ([
            'structure',
            'bias',
            'score',
            'last_event',
            'events',
            'swings',
            'liquidity_sweeps',
            'zones',
            'support_resistance',
            'premium_discount',
            'reason',
        ] as $legacyKey) {
            $this->assertArrayHasKey($legacyKey, $result);
        }

        $this->assertIsString($result['structure']);
        $this->assertIsString($result['bias']);
        $this->assertIsInt($result['score']);
        $this->assertContains($result['bias'], ['bullish', 'bearish', 'neutral']);

        $this->assertArrayHasKey('swing', $result['structure_detail']);
        $this->assertArrayHasKey('internal', $result['structure_detail']);
        $this->assertArrayHasKey('swing', $result['order_blocks']);
        $this->assertArrayHasKey('internal', $result['order_blocks']);
        $this->assertArrayHasKey('equal_highs', $result['liquidity']);
        $this->assertArrayHasKey('equal_lows', $result['liquidity']);
        $this->assertArrayHasKey('bullish', $result['fair_value_gaps']);
        $this->assertArrayHasKey('bearish', $result['fair_value_gaps']);
        $this->assertArrayHasKey('high', $result['strong_weak_levels']);
        $this->assertArrayHasKey('low', $result['strong_weak_levels']);

        $this->assertArrayHasKey('zones', $result['premium_discount']);
        $this->assertArrayHasKey('premium', $result['premium_discount']['zones']);
        $this->assertArrayHasKey('equilibrium', $result['premium_discount']['zones']);
        $this->assertArrayHasKey('discount', $result['premium_discount']['zones']);
    }

    public function test_it_detects_bullish_fair_value_gap_when_gap_is_unfilled(): void
    {
        $service = new SmcContextService();
        $candles = $this->waveCandles();

        $candles[20] = $this->candle(20, 100.0, 101.0, 99.5, 100.5);
        $candles[21] = $this->candle(21, 101.2, 102.8, 101.0, 102.5);
        $candles[22] = $this->candle(22, 103.2, 104.2, 103.0, 103.8);

        for ($i = 23; $i < count($candles); $i++) {
            $candles[$i]['low'] = max((float) $candles[$i]['low'], 101.2);
        }

        $result = $service->analyze($candles, 'M15', [
            'swing_length' => 2,
            'internal_length' => 2,
            'fvg_auto_threshold' => false,
        ]);

        $openBullishGaps = array_filter(
            $result['fair_value_gaps']['bullish'],
            fn ($gap) => $gap['status'] === 'open'
        );

        $this->assertNotEmpty($openBullishGaps);
    }

    private function waveCandles(): array
    {
        $closes = [
            100.0, 101.0, 102.5, 104.0, 102.8, 101.7, 100.3, 98.7, 99.6, 101.0,
            102.7, 104.4, 103.1, 101.8, 100.2, 99.0, 100.1, 101.7, 103.2, 104.7,
            103.3, 101.9, 100.6, 99.4, 100.4, 102.0, 103.7, 105.1, 103.8, 102.1,
            100.8, 99.7, 100.8, 102.4, 104.0, 105.4, 106.8, 108.2, 106.5, 105.2,
        ];

        return array_map(
            fn ($close, $index) => $this->candle(
                $index,
                $close - 0.4,
                $close + 0.6,
                $close - 0.6,
                $close
            ),
            $closes,
            array_keys($closes)
        );
    }

    private function candle(int $index, float $open, float $high, float $low, float $close): array
    {
        return [
            'time' => now('UTC')->startOfDay()->addMinutes($index * 15)->toDateTimeString(),
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $close,
            'volume' => 100 + $index,
        ];
    }
}
