<?php

namespace App\Services;

class TechnicalContextCompactorService
{
    private array $higherTimeframes = ['D1', 'H4', 'H1'];

    private array $analysisTimeframes = ['D1', 'H4', 'H1', 'M15', 'M5'];

    public function compact(array $context): array
    {
        $executionTimeframe = strtoupper((string) ($context['execution_timeframe'] ?? 'M15'));
        $classicBias = $context['bias'] ?? [];
        $smc = $context['smc'] ?? [];

        return [
            'symbol' => $context['symbol'] ?? 'UNKNOWN',
            'execution_timeframe' => $executionTimeframe,
            'generated_at_utc' => $context['generated_at_utc'] ?? null,
            'context_candle_time' => $context['context_candle_time']
                ?? data_get($classicBias, "{$executionTimeframe}.last_candle_time"),
            'current_price' => $context['current_price'] ?? null,
            'agent_profile' => $this->compactAgentProfile($context['agent_profile'] ?? []),
            'classic_summary' => $context['summary'] ?? [],
            'smc_summary' => $context['smc_summary'] ?? [],
            'conflicts' => $this->detectConflicts($context),
            'higher_timeframes' => $this->compactTimeframes($this->higherTimeframes, $classicBias, $smc),
            'execution_timeframe_context' => [
                'classic' => $this->compactClassic($classicBias[$executionTimeframe] ?? []),
                'smc' => $this->compactSmc($smc[$executionTimeframe] ?? [], (float) ($context['current_price'] ?? 0)),
            ],
            'supporting_timeframes' => $this->compactTimeframes(
                $this->supportingTimeframes($executionTimeframe),
                $classicBias,
                $smc
            ),
        ];
    }

    private function compactAgentProfile(array $agentProfile): array
    {
        $mode = $agentProfile['risk_mode'] ?? 'balanced';

        if (! in_array($mode, ['conservative', 'balanced', 'aggressive'], true)) {
            $mode = 'balanced';
        }

        return [
            'risk_mode' => $mode,
            'policy' => [
                'smc_can_override_classic' => (bool) data_get($agentProfile, 'policy.smc_can_override_classic', true),
                'neutral_execution_allows_limit' => (bool) data_get($agentProfile, 'policy.neutral_execution_allows_limit', true),
                'market_entry_requires_momentum' => (bool) data_get($agentProfile, 'policy.market_entry_requires_momentum', true),
                'conflict_is_veto' => (bool) data_get($agentProfile, 'policy.conflict_is_veto', false),
                'min_confidence' => (int) data_get($agentProfile, 'policy.min_confidence', 65),
            ],
        ];
    }

    private function supportingTimeframes(string $executionTimeframe): array
    {
        return array_values(array_filter(
            $this->analysisTimeframes,
            fn ($timeframe) => $timeframe !== $executionTimeframe
                && ! in_array($timeframe, $this->higherTimeframes, true)
        ));
    }

    private function compactTimeframes(array $timeframes, array $classicBias, array $smc): array
    {
        $result = [];

        foreach ($timeframes as $timeframe) {
            $classic = $classicBias[$timeframe] ?? [];
            $smcItem = $smc[$timeframe] ?? [];
            $price = (float) ($classic['last_close'] ?? data_get($smcItem, 'premium_discount.current_price', 0));

            $result[$timeframe] = [
                'classic' => $this->compactClassic($classic),
                'smc' => $this->compactSmc($smcItem, $price),
            ];
        }

        return $result;
    }

    private function compactClassic(array $item): array
    {
        return [
            'bias' => $item['bias'] ?? null,
            'score' => $item['score'] ?? null,
            'last_close' => $item['last_close'] ?? null,
            'last_candle_time' => $item['last_candle_time'] ?? null,
            'ema' => $item['ema'] ?? null,
            'atr14' => $item['atr14'] ?? null,
            'structure' => [
                'structure' => data_get($item, 'structure.structure'),
                'bos' => data_get($item, 'structure.bos'),
                'last_swing_high' => data_get($item, 'structure.last_swing_high'),
                'last_swing_low' => data_get($item, 'structure.last_swing_low'),
            ],
            'nearest_support' => array_slice(data_get($item, 'levels.support', []), 0, 3),
            'nearest_resistance' => array_slice(data_get($item, 'levels.resistance', []), 0, 3),
            'reason' => array_slice($item['reason'] ?? [], 0, 6),
        ];
    }

    private function compactSmc(array $item, float $currentPrice): array
    {
        $latestSweep = $this->last($item['liquidity_sweeps'] ?? []);

        return [
            'bias' => $item['bias'] ?? null,
            'score' => $item['score'] ?? null,
            'structure' => $item['structure'] ?? null,
            'swing_trend' => data_get($item, 'structure_detail.swing.trend', $item['structure'] ?? null),
            'internal_trend' => data_get($item, 'structure_detail.internal.trend'),
            'last_swing_event' => $this->compactEvent(data_get($item, 'structure_detail.swing.last_event', $item['last_event'] ?? null)),
            'last_internal_event' => $this->compactEvent(data_get($item, 'structure_detail.internal.last_event')),
            'premium_discount' => [
                'current_area' => data_get($item, 'premium_discount.current_area'),
                'range_high' => data_get($item, 'premium_discount.range_high'),
                'range_low' => data_get($item, 'premium_discount.range_low'),
                'equilibrium' => data_get($item, 'premium_discount.equilibrium'),
            ],
            'latest_liquidity_sweep' => $this->compactSweep($latestSweep),
            'fresh_order_blocks' => $this->freshOrderBlocks($item, $currentPrice),
            'open_fair_value_gaps' => $this->openFairValueGaps($item, $currentPrice),
            'latest_equal_highs' => array_slice(data_get($item, 'liquidity.equal_highs', []), -2),
            'latest_equal_lows' => array_slice(data_get($item, 'liquidity.equal_lows', []), -2),
            'strong_weak_levels' => $item['strong_weak_levels'] ?? null,
            'reason' => array_slice($item['reason'] ?? [], 0, 8),
        ];
    }

    private function compactEvent(?array $event): ?array
    {
        if (! $event) {
            return null;
        }

        return [
            'scope' => $event['scope'] ?? null,
            'type' => $event['type'] ?? null,
            'tag' => $event['tag'] ?? null,
            'direction' => $event['direction'] ?? null,
            'time' => $event['time'] ?? null,
            'pivot_level' => $event['pivot_level'] ?? $event['break_price'] ?? null,
            'break_price' => $event['break_price'] ?? null,
            'close' => $event['close'] ?? null,
            'previous_trend' => $event['previous_trend'] ?? null,
            'new_trend' => $event['new_trend'] ?? null,
        ];
    }

    private function compactSweep(?array $sweep): ?array
    {
        if (! $sweep) {
            return null;
        }

        return [
            'type' => $sweep['type'] ?? null,
            'time' => $sweep['time'] ?? null,
            'swept_price' => data_get($sweep, 'swept_level.price'),
            'wick_extreme' => $sweep['wick_extreme'] ?? null,
            'close' => $sweep['close'] ?? null,
        ];
    }

    private function freshOrderBlocks(array $item, float $currentPrice): array
    {
        $blocks = array_merge(
            data_get($item, 'order_blocks.swing.bullish', []),
            data_get($item, 'order_blocks.swing.bearish', []),
            data_get($item, 'order_blocks.internal.bullish', []),
            data_get($item, 'order_blocks.internal.bearish', [])
        );

        return collect($blocks)
            ->filter(fn ($block) => in_array($block['status'] ?? null, ['fresh', 'mitigated'], true))
            ->sortBy(fn ($block) => $this->distanceToZone($block, $currentPrice))
            ->take(4)
            ->map(fn ($block) => [
                'type' => $block['type'] ?? null,
                'scope' => $block['scope'] ?? null,
                'bias' => $block['bias'] ?? null,
                'low' => $block['low'] ?? null,
                'high' => $block['high'] ?? null,
                'midpoint' => $block['midpoint'] ?? null,
                'status' => $block['status'] ?? null,
                'created_by' => $block['created_by'] ?? null,
                'origin_time' => $block['origin_time'] ?? null,
            ])
            ->values()
            ->toArray();
    }

    private function openFairValueGaps(array $item, float $currentPrice): array
    {
        $gaps = [];

        foreach (['bullish', 'bearish'] as $direction) {
            foreach (data_get($item, "fair_value_gaps.{$direction}", []) as $gap) {
                if (($gap['status'] ?? null) !== 'open') {
                    continue;
                }

                $gaps[] = array_merge($gap, ['direction' => $direction]);
            }
        }

        return collect($gaps)
            ->sortBy(fn ($gap) => $this->distanceToZone($gap, $currentPrice))
            ->take(4)
            ->map(fn ($gap) => [
                'direction' => $gap['direction'],
                'top' => $gap['top'] ?? null,
                'bottom' => $gap['bottom'] ?? null,
                'midpoint' => $gap['midpoint'] ?? null,
                'time' => $gap['time'] ?? null,
                'status' => $gap['status'] ?? null,
            ])
            ->values()
            ->toArray();
    }

    private function distanceToZone(array $zone, float $price): float
    {
        $low = (float) ($zone['low'] ?? $zone['bottom'] ?? $price);
        $high = (float) ($zone['high'] ?? $zone['top'] ?? $price);

        if ($price >= $low && $price <= $high) {
            return 0.0;
        }

        return min(abs($price - $low), abs($price - $high));
    }

    private function detectConflicts(array $context): array
    {
        $conflicts = [];
        $classicAction = data_get($context, 'summary.preferred_action');
        $smcAction = data_get($context, 'smc_summary.preferred_action');
        $classicHtf = data_get($context, 'summary.higher_timeframe_bias');
        $smcHtf = data_get($context, 'smc_summary.higher_timeframe_bias');
        $smcExecution = data_get($context, 'smc_summary.execution_bias');

        if ($classicAction && $smcAction && $classicAction !== $smcAction) {
            $conflicts[] = "Classic preferred action {$classicAction} conflicts with SMC preferred action {$smcAction}.";
        }

        if ($classicHtf && $smcHtf && $classicHtf !== $smcHtf) {
            $conflicts[] = "Classic HTF bias {$classicHtf} differs from SMC HTF bias {$smcHtf}.";
        }

        if ($smcAction !== 'NO_TRADE' && $smcExecution === 'neutral') {
            $conflicts[] = 'SMC preferred action is directional but execution SMC bias is neutral.';
        }

        return $conflicts;
    }

    private function last(array $items): mixed
    {
        if ($items === []) {
            return null;
        }

        return $items[array_key_last($items)];
    }
}
