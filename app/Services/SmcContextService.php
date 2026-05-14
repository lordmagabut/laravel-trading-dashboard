<?php

namespace App\Services;

class SmcContextService
{
    public function analyze(array $candles, string $timeframe, array $config = []): array
    {
        $config = array_merge($this->defaultConfig(), $config);

        if (count($candles) < 30) {
            return [
                'timeframe' => $timeframe,
                'status' => 'insufficient_data',
                'structure' => 'unknown',
                'bias' => 'neutral',
                'score' => 0,
                'reason' => ['Data candle kurang dari 30 untuk analisa SMC.'],
            ];
        }

        $swingDepth = $this->usableDepth($candles, (int) $config['swing_length']);
        $internalDepth = $this->usableDepth($candles, (int) $config['internal_length']);

        $externalSwings = $this->detectSwings($candles, $swingDepth);
        $internalSwings = $this->detectSwings($candles, $internalDepth);

        $events = $this->detectStructureEvents($candles, $externalSwings, 'swing');
        $internalEvents = $this->detectStructureEvents($candles, $internalSwings, 'internal');

        $lastEvent = $this->last($events);
        $lastInternalEvent = $this->last($internalEvents);
        $structure = $this->resolveStructure($events, $externalSwings);
        $internalStructure = $this->resolveStructure($internalEvents, $internalSwings);

        $liquiditySweeps = $this->detectLiquiditySweeps($candles, $externalSwings);
        $equalHighsLows = $config['enable_equal_high_low']
            ? $this->detectEqualHighsLows(
                $candles,
                $this->usableDepth($candles, (int) $config['equal_high_low_length']),
                (float) $config['equal_high_low_threshold'],
                (int) $config['atr_period']
            )
            : ['equal_highs' => [], 'equal_lows' => []];

        $swingOrderBlocks = $this->buildOrderBlocks($candles, $events, 'swing', $config);
        $internalOrderBlocks = $this->buildOrderBlocks($candles, $internalEvents, 'internal', $config);
        $zones = $this->buildLegacyZones($swingOrderBlocks);

        $lastCandle = $this->last($candles);
        $currentPrice = (float) $lastCandle['close'];
        $dealingRange = $this->buildDealingRange($externalSwings, $currentPrice);
        $fairValueGaps = $config['enable_fvg']
            ? $this->detectFairValueGaps($candles, (bool) $config['fvg_auto_threshold'])
            : ['bullish' => [], 'bearish' => []];
        $strongWeakLevels = $this->buildStrongWeakLevels($externalSwings, $structure);
        $smcSupportResistance = $this->buildSmcSupportResistance(
            $zones,
            $externalSwings,
            $currentPrice
        );

        [$bias, $score, $reason] = $this->buildBias(
            $structure,
            $lastEvent,
            $internalStructure,
            $lastInternalEvent,
            $liquiditySweeps,
            $zones,
            $dealingRange,
            $fairValueGaps,
            $currentPrice
        );

        return [
            'timeframe' => $timeframe,
            'status' => 'ok',

            // Legacy keys kept for dashboard, smc_summary, and Technical Agent prompt.
            'structure' => $structure,
            'bias' => $bias,
            'score' => $score,
            'last_event' => $lastEvent,
            'events' => array_slice($events, -10),
            'swings' => [
                'external' => [
                    'highs' => array_slice($externalSwings['highs'], -10),
                    'lows' => array_slice($externalSwings['lows'], -10),
                ],
                'internal' => [
                    'highs' => array_slice($internalSwings['highs'], -10),
                    'lows' => array_slice($internalSwings['lows'], -10),
                ],
            ],
            'liquidity_sweeps' => array_slice($liquiditySweeps, -10),
            'zones' => $zones,
            'support_resistance' => $smcSupportResistance,
            'premium_discount' => $dealingRange,
            'reason' => $reason,

            // New richer SMC context, modeled conceptually after Lux-style components.
            'config' => [
                'swing_length' => $swingDepth,
                'internal_length' => $internalDepth,
                'atr_period' => $config['atr_period'],
                'order_block_mitigation' => $config['order_block_mitigation'],
            ],
            'structure_detail' => [
                'swing' => [
                    'trend' => $structure,
                    'last_event' => $lastEvent,
                    'events' => array_slice($events, -20),
                    'swings' => [
                        'highs' => array_slice($externalSwings['highs'], -20),
                        'lows' => array_slice($externalSwings['lows'], -20),
                    ],
                ],
                'internal' => [
                    'trend' => $internalStructure,
                    'last_event' => $lastInternalEvent,
                    'events' => array_slice($internalEvents, -20),
                    'swings' => [
                        'highs' => array_slice($internalSwings['highs'], -20),
                        'lows' => array_slice($internalSwings['lows'], -20),
                    ],
                ],
            ],
            'order_blocks' => [
                'swing' => $swingOrderBlocks,
                'internal' => $internalOrderBlocks,
            ],
            'liquidity' => [
                'sweeps' => array_slice($liquiditySweeps, -10),
                'equal_highs' => array_slice($equalHighsLows['equal_highs'], -10),
                'equal_lows' => array_slice($equalHighsLows['equal_lows'], -10),
            ],
            'fair_value_gaps' => $fairValueGaps,
            'strong_weak_levels' => $strongWeakLevels,
        ];
    }

    private function defaultConfig(): array
    {
        return [
            'swing_length' => 50,
            'internal_length' => 5,
            'equal_high_low_length' => 3,
            'equal_high_low_threshold' => 0.1,
            'atr_period' => 200,
            'order_block_filter' => 'atr',
            'order_block_mitigation' => 'high_low',
            'max_order_blocks' => 8,
            'enable_fvg' => true,
            'fvg_auto_threshold' => true,
            'enable_equal_high_low' => true,
        ];
    }

    private function usableDepth(array $candles, int $requestedDepth): int
    {
        $maxDepth = max(2, intdiv(max(0, count($candles) - 2), 2));

        return max(2, min($requestedDepth, $maxDepth));
    }

    private function detectSwings(array $candles, int $depth): array
    {
        $highs = [];
        $lows = [];
        $count = count($candles);

        for ($i = $depth; $i < $count - $depth; $i++) {
            $high = (float) $candles[$i]['high'];
            $low = (float) $candles[$i]['low'];
            $isHigh = true;
            $isLow = true;

            for ($j = $i - $depth; $j <= $i + $depth; $j++) {
                if ($j === $i) {
                    continue;
                }

                if ((float) $candles[$j]['high'] > $high) {
                    $isHigh = false;
                }

                if ((float) $candles[$j]['low'] < $low) {
                    $isLow = false;
                }
            }

            if ($isHigh) {
                $highs[] = [
                    'index' => $i,
                    'time' => $candles[$i]['time'],
                    'price' => $high,
                ];
            }

            if ($isLow) {
                $lows[] = [
                    'index' => $i,
                    'time' => $candles[$i]['time'],
                    'price' => $low,
                ];
            }
        }

        return [
            'highs' => $highs,
            'lows' => $lows,
        ];
    }

    private function detectStructureEvents(array $candles, array $swings, string $scope = 'swing'): array
    {
        $events = [];
        $trend = 'unknown';
        $brokenHighs = [];
        $brokenLows = [];

        foreach ($candles as $index => $candle) {
            $close = (float) $candle['close'];
            $previousClose = $index > 0 ? (float) $candles[$index - 1]['close'] : null;
            $lastHigh = $this->lastSwingBefore($swings['highs'], $index);
            $lastLow = $this->lastSwingBefore($swings['lows'], $index);

            if (
                $lastHigh
                && $this->crossedAbove($previousClose, $close, (float) $lastHigh['price'])
                && ! $this->isBroken($brokenHighs, $lastHigh)
            ) {
                $previousTrend = $trend;
                $type = $trend === 'bearish' ? 'bullish_choch' : 'bullish_bos';
                $tag = str_ends_with($type, 'choch') ? 'CHoCH' : 'BOS';
                $protectedLow = $this->lastSwingBefore($swings['lows'], $index);

                $events[] = [
                    'scope' => $scope,
                    'type' => $type,
                    'tag' => $tag,
                    'direction' => 'bullish',
                    'time' => $candle['time'],
                    'index' => $index,
                    'break_index' => $index,
                    'break_time' => $candle['time'],
                    'break_price' => $lastHigh['price'],
                    'pivot_level' => $lastHigh['price'],
                    'pivot_index' => $lastHigh['index'],
                    'close' => $close,
                    'previous_trend' => $previousTrend,
                    'new_trend' => 'bullish',
                    'broken_swing' => $lastHigh,
                    'protected_level' => $protectedLow,
                ];

                $brokenHighs[] = $lastHigh;
                $trend = 'bullish';
            }

            if (
                $lastLow
                && $this->crossedBelow($previousClose, $close, (float) $lastLow['price'])
                && ! $this->isBroken($brokenLows, $lastLow)
            ) {
                $previousTrend = $trend;
                $type = $trend === 'bullish' ? 'bearish_choch' : 'bearish_bos';
                $tag = str_ends_with($type, 'choch') ? 'CHoCH' : 'BOS';
                $protectedHigh = $this->lastSwingBefore($swings['highs'], $index);

                $events[] = [
                    'scope' => $scope,
                    'type' => $type,
                    'tag' => $tag,
                    'direction' => 'bearish',
                    'time' => $candle['time'],
                    'index' => $index,
                    'break_index' => $index,
                    'break_time' => $candle['time'],
                    'break_price' => $lastLow['price'],
                    'pivot_level' => $lastLow['price'],
                    'pivot_index' => $lastLow['index'],
                    'close' => $close,
                    'previous_trend' => $previousTrend,
                    'new_trend' => 'bearish',
                    'broken_swing' => $lastLow,
                    'protected_level' => $protectedHigh,
                ];

                $brokenLows[] = $lastLow;
                $trend = 'bearish';
            }
        }

        return $events;
    }

    private function crossedAbove(?float $previousClose, float $close, float $level): bool
    {
        return $previousClose === null
            ? $close > $level
            : $previousClose <= $level && $close > $level;
    }

    private function crossedBelow(?float $previousClose, float $close, float $level): bool
    {
        return $previousClose === null
            ? $close < $level
            : $previousClose >= $level && $close < $level;
    }

    private function detectLiquiditySweeps(array $candles, array $swings): array
    {
        $sweeps = [];

        foreach ($candles as $index => $candle) {
            $lastHigh = $this->lastSwingBefore($swings['highs'], $index);
            $lastLow = $this->lastSwingBefore($swings['lows'], $index);
            $high = (float) $candle['high'];
            $low = (float) $candle['low'];
            $close = (float) $candle['close'];

            if ($lastHigh && $high > $lastHigh['price'] && $close < $lastHigh['price']) {
                $sweeps[] = [
                    'type' => 'buy_side_liquidity_sweep',
                    'time' => $candle['time'],
                    'index' => $index,
                    'swept_level' => $lastHigh,
                    'wick_extreme' => $high,
                    'close' => $close,
                ];
            }

            if ($lastLow && $low < $lastLow['price'] && $close > $lastLow['price']) {
                $sweeps[] = [
                    'type' => 'sell_side_liquidity_sweep',
                    'time' => $candle['time'],
                    'index' => $index,
                    'swept_level' => $lastLow,
                    'wick_extreme' => $low,
                    'close' => $close,
                ];
            }
        }

        return $sweeps;
    }

    private function buildOrderBlocks(array $candles, array $events, string $scope, array $config): array
    {
        $bullish = [];
        $bearish = [];
        $volatilityMeasure = $this->volatilityMeasure($candles, $config);

        foreach ($events as $event) {
            $origin = $this->findExtremeOrderBlockOrigin($candles, $event, $volatilityMeasure);

            if (! $origin) {
                continue;
            }

            $zone = $this->makeZone(
                $event['direction'] === 'bullish' ? 'demand' : 'supply',
                $origin,
                $event,
                $candles,
                (string) $config['order_block_mitigation']
            );

            $block = array_merge($zone, [
                'scope' => $scope,
                'type' => $event['direction'] === 'bullish' ? 'bullish_ob' : 'bearish_ob',
                'bias' => $event['direction'],
                'mitigation_status' => $zone['status'],
                'break_index' => $event['break_index'] ?? $event['index'],
            ]);

            if ($event['direction'] === 'bullish') {
                $bullish[] = $block;
            } else {
                $bearish[] = $block;
            }
        }

        return [
            'bullish' => array_slice($bullish, -((int) $config['max_order_blocks'])),
            'bearish' => array_slice($bearish, -((int) $config['max_order_blocks'])),
        ];
    }

    private function findExtremeOrderBlockOrigin(array $candles, array $event, float $volatilityMeasure): ?array
    {
        $start = max(0, (int) ($event['pivot_index'] ?? data_get($event, 'broken_swing.index', 0)));
        $end = max($start, (int) ($event['break_index'] ?? $event['index']));
        $selectedIndex = null;
        $selectedValue = null;
        $selectedParsed = null;

        for ($i = $start; $i <= $end && $i < count($candles); $i++) {
            $parsed = $this->parsedHighLow($candles[$i], $volatilityMeasure);

            if ($event['direction'] === 'bullish') {
                if ($selectedValue === null || $parsed['low'] < $selectedValue) {
                    $selectedValue = $parsed['low'];
                    $selectedIndex = $i;
                    $selectedParsed = $parsed;
                }
            } elseif ($selectedValue === null || $parsed['high'] > $selectedValue) {
                $selectedValue = $parsed['high'];
                $selectedIndex = $i;
                $selectedParsed = $parsed;
            }
        }

        if ($selectedIndex === null || $selectedParsed === null) {
            return null;
        }

        return [
            'index' => $selectedIndex,
            'candle' => array_merge($candles[$selectedIndex], [
                'high' => $selectedParsed['high'],
                'low' => $selectedParsed['low'],
            ]),
        ];
    }

    private function parsedHighLow(array $candle, float $volatilityMeasure): array
    {
        $high = (float) $candle['high'];
        $low = (float) $candle['low'];
        $isHighVolatility = $volatilityMeasure > 0 && ($high - $low) >= (2 * $volatilityMeasure);

        return [
            'high' => $isHighVolatility ? $low : $high,
            'low' => $isHighVolatility ? $high : $low,
        ];
    }

    private function volatilityMeasure(array $candles, array $config): float
    {
        if (($config['order_block_filter'] ?? 'atr') === 'cumulative_mean_range') {
            $ranges = array_map(
                fn ($candle) => (float) $candle['high'] - (float) $candle['low'],
                $candles
            );

            return count($ranges) > 0 ? array_sum($ranges) / count($ranges) : 0.0;
        }

        return $this->atr($candles, (int) $config['atr_period']) ?? 0.0;
    }

    private function buildLegacyZones(array $swingOrderBlocks): array
    {
        return [
            'demand' => array_map(
                fn ($block) => $this->legacyZone($block, 'demand'),
                $swingOrderBlocks['bullish'] ?? []
            ),
            'supply' => array_map(
                fn ($block) => $this->legacyZone($block, 'supply'),
                $swingOrderBlocks['bearish'] ?? []
            ),
        ];
    }

    private function legacyZone(array $block, string $type): array
    {
        unset($block['bias']);
        $block['type'] = $type;

        return $block;
    }

    private function makeZone(
        string $type,
        array $origin,
        array $event,
        array $candles,
        string $mitigationMode = 'close'
    ): array {
        $low = (float) $origin['candle']['low'];
        $high = (float) $origin['candle']['high'];
        $mitigated = false;
        $invalidated = false;

        for ($i = $origin['index'] + 1; $i < count($candles); $i++) {
            $candleHigh = (float) $candles[$i]['high'];
            $candleLow = (float) $candles[$i]['low'];
            $candleClose = (float) $candles[$i]['close'];

            if ($i > $event['index'] && $candleLow <= $high && $candleHigh >= $low) {
                $mitigated = true;
            }

            if ($type === 'demand') {
                $mitigationSource = $mitigationMode === 'high_low' ? $candleLow : $candleClose;

                if ($mitigationSource < $low) {
                    $invalidated = true;
                }
            }

            if ($type === 'supply') {
                $mitigationSource = $mitigationMode === 'high_low' ? $candleHigh : $candleClose;

                if ($mitigationSource > $high) {
                    $invalidated = true;
                }
            }
        }

        return [
            'type' => $type,
            'origin_time' => $origin['candle']['time'],
            'origin_index' => $origin['index'],
            'low' => $low,
            'high' => $high,
            'midpoint' => round(($low + $high) / 2, 5),
            'created_by' => $event['type'],
            'break_time' => $event['time'],
            'break_price' => $event['break_price'],
            'mitigated' => $mitigated,
            'invalidated' => $invalidated,
            'status' => $invalidated ? 'invalidated' : ($mitigated ? 'mitigated' : 'fresh'),
        ];
    }

    private function detectEqualHighsLows(array $candles, int $length, float $threshold, int $atrPeriod): array
    {
        $swings = $this->detectSwings($candles, $length);
        $atr = $this->atr($candles, $atrPeriod) ?? $this->meanRange($candles);
        $maxDifference = $threshold * $atr;

        return [
            'equal_highs' => $this->matchingLevels($swings['highs'], $maxDifference),
            'equal_lows' => $this->matchingLevels($swings['lows'], $maxDifference),
        ];
    }

    private function matchingLevels(array $swings, float $maxDifference): array
    {
        $matches = [];

        for ($i = 1; $i < count($swings); $i++) {
            $first = $swings[$i - 1];
            $second = $swings[$i];
            $difference = abs((float) $first['price'] - (float) $second['price']);

            if ($difference <= $maxDifference) {
                $matches[] = [
                    'first_index' => $first['index'],
                    'first_time' => $first['time'],
                    'second_index' => $second['index'],
                    'second_time' => $second['time'],
                    'level' => round(((float) $first['price'] + (float) $second['price']) / 2, 5),
                    'difference' => round($difference, 5),
                    'threshold' => round($maxDifference, 5),
                ];
            }
        }

        return $matches;
    }

    private function detectFairValueGaps(array $candles, bool $autoThreshold = true): array
    {
        $bullish = [];
        $bearish = [];
        $deltas = [];

        for ($i = 2; $i < count($candles); $i++) {
            $previous = $candles[$i - 1];
            $delta = abs($this->barDeltaPercent($previous));
            $deltas[] = $delta;
            $threshold = $autoThreshold && count($deltas) > 0
                ? (array_sum($deltas) / count($deltas)) * 2
                : 0.0;

            $current = $candles[$i];
            $twoBack = $candles[$i - 2];
            $previousClose = (float) $previous['close'];
            $bullishBottom = (float) $twoBack['high'];
            $bullishTop = (float) $current['low'];
            $bearishTop = (float) $twoBack['low'];
            $bearishBottom = (float) $current['high'];
            $barDelta = $this->barDeltaPercent($previous);

            if ($bullishTop > $bullishBottom && $previousClose > $bullishBottom && $barDelta > $threshold) {
                $bullish[] = $this->makeFairValueGap('bullish', $i, $current['time'], $bullishTop, $bullishBottom, $candles);
            }

            if ($bearishBottom < $bearishTop && $previousClose < $bearishTop && -$barDelta > $threshold) {
                $bearish[] = $this->makeFairValueGap('bearish', $i, $current['time'], $bearishTop, $bearishBottom, $candles);
            }
        }

        return [
            'bullish' => array_slice($bullish, -10),
            'bearish' => array_slice($bearish, -10),
        ];
    }

    private function makeFairValueGap(
        string $direction,
        int $index,
        string $time,
        float $top,
        float $bottom,
        array $candles
    ): array {
        $filled = false;

        for ($i = $index + 1; $i < count($candles); $i++) {
            if ($direction === 'bullish' && (float) $candles[$i]['low'] <= $bottom) {
                $filled = true;
            }

            if ($direction === 'bearish' && (float) $candles[$i]['high'] >= $top) {
                $filled = true;
            }
        }

        return [
            'index' => $index,
            'time' => $time,
            'top' => $top,
            'bottom' => $bottom,
            'midpoint' => round(($top + $bottom) / 2, 5),
            'status' => $filled ? 'filled' : 'open',
        ];
    }

    private function barDeltaPercent(array $candle): float
    {
        $open = (float) $candle['open'];

        if ($open == 0.0) {
            return 0.0;
        }

        return (((float) $candle['close'] - $open) / $open) * 100;
    }

    private function buildDealingRange(array $swings, float $currentPrice): array
    {
        $lastHigh = $this->last($swings['highs']);
        $lastLow = $this->last($swings['lows']);

        if (! $lastHigh || ! $lastLow) {
            return [
                'status' => 'unavailable',
                'current_area' => 'unknown',
            ];
        }

        $high = max($lastHigh['price'], $lastLow['price']);
        $low = min($lastHigh['price'], $lastLow['price']);
        $equilibrium = round(($high + $low) / 2, 5);
        $premiumBottom = round((0.95 * $high) + (0.05 * $low), 5);
        $discountTop = round((0.95 * $low) + (0.05 * $high), 5);
        $equilibriumTop = round((0.525 * $high) + (0.475 * $low), 5);
        $equilibriumBottom = round((0.525 * $low) + (0.475 * $high), 5);
        $area = 'range_middle';

        if ($currentPrice >= $premiumBottom) {
            $area = 'premium';
        } elseif ($currentPrice <= $discountTop) {
            $area = 'discount';
        } elseif ($currentPrice >= $equilibriumBottom && $currentPrice <= $equilibriumTop) {
            $area = 'equilibrium';
        }

        return [
            'status' => 'ok',
            'range_high' => $high,
            'range_low' => $low,
            'equilibrium' => $equilibrium,
            'current_price' => $currentPrice,
            'current_area' => $area,
            'zones' => [
                'premium' => [
                    'top' => $high,
                    'bottom' => $premiumBottom,
                ],
                'equilibrium' => [
                    'top' => $equilibriumTop,
                    'bottom' => $equilibriumBottom,
                ],
                'discount' => [
                    'top' => $discountTop,
                    'bottom' => $low,
                ],
            ],
            'high_source' => $lastHigh,
            'low_source' => $lastLow,
        ];
    }

    private function buildStrongWeakLevels(array $swings, string $structure): array
    {
        $lastHigh = $this->last($swings['highs']);
        $lastLow = $this->last($swings['lows']);

        return [
            'high' => $lastHigh ? [
                'type' => $structure === 'bearish' ? 'strong_high' : 'weak_high',
                'price' => $lastHigh['price'],
                'time' => $lastHigh['time'],
                'index' => $lastHigh['index'],
            ] : null,
            'low' => $lastLow ? [
                'type' => $structure === 'bullish' ? 'strong_low' : 'weak_low',
                'price' => $lastLow['price'],
                'time' => $lastLow['time'],
                'index' => $lastLow['index'],
            ] : null,
        ];
    }

    private function buildSmcSupportResistance(array $zones, array $swings, float $currentPrice): array
    {
        $validDemand = collect($zones['demand'])
            ->reject(fn ($zone) => $zone['invalidated'])
            ->filter(fn ($zone) => $zone['high'] <= $currentPrice || ($zone['low'] <= $currentPrice && $zone['high'] >= $currentPrice))
            ->sortByDesc('high')
            ->values()
            ->take(5)
            ->toArray();

        $validSupply = collect($zones['supply'])
            ->reject(fn ($zone) => $zone['invalidated'])
            ->filter(fn ($zone) => $zone['low'] >= $currentPrice || ($zone['low'] <= $currentPrice && $zone['high'] >= $currentPrice))
            ->sortBy('low')
            ->values()
            ->take(5)
            ->toArray();

        return [
            'support' => [
                'type' => 'smc_demand_and_protected_lows',
                'zones' => $validDemand,
                'protected_lows' => array_slice($swings['lows'], -5),
            ],
            'resistance' => [
                'type' => 'smc_supply_and_protected_highs',
                'zones' => $validSupply,
                'protected_highs' => array_slice($swings['highs'], -5),
            ],
        ];
    }

    private function buildBias(
        string $structure,
        ?array $lastEvent,
        string $internalStructure,
        ?array $lastInternalEvent,
        array $liquiditySweeps,
        array $zones,
        array $dealingRange,
        array $fairValueGaps,
        float $currentPrice
    ): array {
        $score = 0;
        $reason = [];

        if ($structure === 'bullish') {
            $score += 2;
            $reason[] = 'Struktur SMC swing terakhir bullish.';
        }

        if ($structure === 'bearish') {
            $score -= 2;
            $reason[] = 'Struktur SMC swing terakhir bearish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bullish_bos') {
            $score += 2;
            $reason[] = 'Bullish swing BOS terakhir mengindikasikan continuation bullish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bearish_bos') {
            $score -= 2;
            $reason[] = 'Bearish swing BOS terakhir mengindikasikan continuation bearish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bullish_choch') {
            $score += 1;
            $reason[] = 'Bullish swing CHoCH terakhir mengindikasikan potensi reversal ke bullish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bearish_choch') {
            $score -= 1;
            $reason[] = 'Bearish swing CHoCH terakhir mengindikasikan potensi reversal ke bearish.';
        }

        if ($internalStructure === 'bullish' && $structure !== 'bearish') {
            $score += 1;
            $reason[] = 'Internal structure mendukung arah bullish.';
        }

        if ($internalStructure === 'bearish' && $structure !== 'bullish') {
            $score -= 1;
            $reason[] = 'Internal structure mendukung arah bearish.';
        }

        if ($lastInternalEvent && $lastInternalEvent['direction'] === 'bullish' && $structure === 'bullish') {
            $score += 1;
            $reason[] = 'Internal event terakhir searah dengan swing bullish.';
        }

        if ($lastInternalEvent && $lastInternalEvent['direction'] === 'bearish' && $structure === 'bearish') {
            $score -= 1;
            $reason[] = 'Internal event terakhir searah dengan swing bearish.';
        }

        if (($dealingRange['current_area'] ?? null) === 'discount') {
            $score += 1;
            $reason[] = 'Harga berada di area discount dari dealing range terakhir.';
        }

        if (($dealingRange['current_area'] ?? null) === 'premium') {
            $score -= 1;
            $reason[] = 'Harga berada di area premium dari dealing range terakhir.';
        }

        if ($this->hasValidNearbyZone($zones['demand'], $currentPrice, 'demand')) {
            $score += 1;
            $reason[] = 'Ada bullish order-block/demand valid dekat harga saat ini.';
        }

        if ($this->hasValidNearbyZone($zones['supply'], $currentPrice, 'supply')) {
            $score -= 1;
            $reason[] = 'Ada bearish order-block/supply valid dekat harga saat ini.';
        }

        if ($this->hasOpenNearbyFvg($fairValueGaps['bullish'] ?? [], $currentPrice)) {
            $score += 1;
            $reason[] = 'Ada bullish fair value gap terbuka dekat harga saat ini.';
        }

        if ($this->hasOpenNearbyFvg($fairValueGaps['bearish'] ?? [], $currentPrice)) {
            $score -= 1;
            $reason[] = 'Ada bearish fair value gap terbuka dekat harga saat ini.';
        }

        $lastSweep = $this->last($liquiditySweeps);

        if ($lastSweep && $lastSweep['type'] === 'sell_side_liquidity_sweep') {
            $score += 1;
            $reason[] = 'Sell-side liquidity sweep terakhir dapat mendukung reaksi bullish.';
        }

        if ($lastSweep && $lastSweep['type'] === 'buy_side_liquidity_sweep') {
            $score -= 1;
            $reason[] = 'Buy-side liquidity sweep terakhir dapat mendukung reaksi bearish.';
        }

        $bias = match (true) {
            $score >= 3 => 'bullish',
            $score <= -3 => 'bearish',
            default => 'neutral',
        };

        return [$bias, $score, $reason];
    }

    private function hasOpenNearbyFvg(array $gaps, float $currentPrice): bool
    {
        foreach (array_reverse($gaps) as $gap) {
            if (($gap['status'] ?? null) !== 'open') {
                continue;
            }

            if ($gap['bottom'] <= $currentPrice && $gap['top'] >= $currentPrice) {
                return true;
            }
        }

        return false;
    }

    private function hasValidNearbyZone(array $zones, float $currentPrice, string $type): bool
    {
        foreach (array_reverse($zones) as $zone) {
            if ($zone['invalidated']) {
                continue;
            }

            if ($zone['low'] <= $currentPrice && $zone['high'] >= $currentPrice) {
                return true;
            }

            if ($type === 'demand' && $zone['high'] < $currentPrice) {
                return true;
            }

            if ($type === 'supply' && $zone['low'] > $currentPrice) {
                return true;
            }
        }

        return false;
    }

    private function resolveStructure(array $events, array $swings): string
    {
        $lastEvent = $this->last($events);

        if ($lastEvent) {
            return $lastEvent['direction'];
        }

        $lastHigh = $this->last($swings['highs']);
        $previousHigh = $this->previous($swings['highs']);
        $lastLow = $this->last($swings['lows']);
        $previousLow = $this->previous($swings['lows']);

        if ($lastHigh && $previousHigh && $lastLow && $previousLow) {
            if ($lastHigh['price'] > $previousHigh['price'] && $lastLow['price'] > $previousLow['price']) {
                return 'bullish';
            }

            if ($lastHigh['price'] < $previousHigh['price'] && $lastLow['price'] < $previousLow['price']) {
                return 'bearish';
            }
        }

        return 'ranging';
    }

    private function lastSwingBefore(array $swings, int $index): ?array
    {
        $result = null;

        foreach ($swings as $swing) {
            if ($swing['index'] < $index) {
                $result = $swing;
            }
        }

        return $result;
    }

    private function isBroken(array $brokenSwings, array $swing): bool
    {
        foreach ($brokenSwings as $broken) {
            if ($broken['index'] === $swing['index'] && $broken['price'] === $swing['price']) {
                return true;
            }
        }

        return false;
    }

    private function atr(array $candles, int $period): ?float
    {
        if (count($candles) < 2) {
            return null;
        }

        $trueRanges = [];

        for ($i = 1; $i < count($candles); $i++) {
            $high = (float) $candles[$i]['high'];
            $low = (float) $candles[$i]['low'];
            $previousClose = (float) $candles[$i - 1]['close'];

            $trueRanges[] = max(
                $high - $low,
                abs($high - $previousClose),
                abs($low - $previousClose)
            );
        }

        $ranges = count($trueRanges) >= $period
            ? array_slice($trueRanges, -$period)
            : $trueRanges;

        return count($ranges) > 0 ? array_sum($ranges) / count($ranges) : null;
    }

    private function meanRange(array $candles): float
    {
        $ranges = array_map(
            fn ($candle) => (float) $candle['high'] - (float) $candle['low'],
            $candles
        );

        return count($ranges) > 0 ? array_sum($ranges) / count($ranges) : 0.0;
    }

    private function last(array $items): mixed
    {
        if ($items === []) {
            return null;
        }

        return $items[array_key_last($items)];
    }

    private function previous(array $items): mixed
    {
        if (count($items) < 2) {
            return null;
        }

        return $items[array_key_last($items) - 1];
    }
}
