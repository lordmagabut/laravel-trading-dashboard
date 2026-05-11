<?php

namespace App\Services;

class SmcContextService
{
    public function analyze(array $candles, string $timeframe): array
    {
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

        $externalSwings = $this->detectSwings($candles, 5);
        $internalSwings = $this->detectSwings($candles, 2);
        $events = $this->detectStructureEvents($candles, $externalSwings);
        $lastEvent = $this->last($events);
        $structure = $this->resolveStructure($events, $externalSwings);
        $liquiditySweeps = $this->detectLiquiditySweeps($candles, $externalSwings);
        $zones = $this->buildZones($candles, $events);
        $lastCandle = $this->last($candles);
        $currentPrice = (float) $lastCandle['close'];
        $dealingRange = $this->buildDealingRange($externalSwings, $currentPrice);
        $smcSupportResistance = $this->buildSmcSupportResistance(
            $zones,
            $externalSwings,
            $currentPrice
        );

        [$bias, $score, $reason] = $this->buildBias(
            $structure,
            $lastEvent,
            $liquiditySweeps,
            $zones,
            $dealingRange,
            $currentPrice
        );

        return [
            'timeframe' => $timeframe,
            'status' => 'ok',
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
        ];
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

    private function detectStructureEvents(array $candles, array $swings): array
    {
        $events = [];
        $structure = 'unknown';
        $brokenHighs = [];
        $brokenLows = [];

        foreach ($candles as $index => $candle) {
            $close = (float) $candle['close'];
            $lastHigh = $this->lastSwingBefore($swings['highs'], $index);
            $lastLow = $this->lastSwingBefore($swings['lows'], $index);

            if ($lastHigh && $close > $lastHigh['price'] && ! $this->isBroken($brokenHighs, $lastHigh)) {
                $type = $structure === 'bearish' ? 'bullish_choch' : 'bullish_bos';
                $protectedLow = $this->lastSwingBefore($swings['lows'], $index);

                $events[] = [
                    'type' => $type,
                    'direction' => 'bullish',
                    'time' => $candle['time'],
                    'index' => $index,
                    'break_price' => $lastHigh['price'],
                    'close' => $close,
                    'broken_swing' => $lastHigh,
                    'protected_level' => $protectedLow,
                ];

                $brokenHighs[] = $lastHigh;
                $structure = 'bullish';
            }

            if ($lastLow && $close < $lastLow['price'] && ! $this->isBroken($brokenLows, $lastLow)) {
                $type = $structure === 'bullish' ? 'bearish_choch' : 'bearish_bos';
                $protectedHigh = $this->lastSwingBefore($swings['highs'], $index);

                $events[] = [
                    'type' => $type,
                    'direction' => 'bearish',
                    'time' => $candle['time'],
                    'index' => $index,
                    'break_price' => $lastLow['price'],
                    'close' => $close,
                    'broken_swing' => $lastLow,
                    'protected_level' => $protectedHigh,
                ];

                $brokenLows[] = $lastLow;
                $structure = 'bearish';
            }
        }

        return $events;
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

    private function buildZones(array $candles, array $events): array
    {
        $demand = [];
        $supply = [];

        foreach ($events as $event) {
            if ($event['direction'] === 'bullish') {
                $origin = $this->findLastOppositeCandle($candles, $event['index'], 'bearish');

                if ($origin) {
                    $demand[] = $this->makeZone('demand', $origin, $event, $candles);
                }
            }

            if ($event['direction'] === 'bearish') {
                $origin = $this->findLastOppositeCandle($candles, $event['index'], 'bullish');

                if ($origin) {
                    $supply[] = $this->makeZone('supply', $origin, $event, $candles);
                }
            }
        }

        return [
            'demand' => array_slice($demand, -8),
            'supply' => array_slice($supply, -8),
        ];
    }

    private function makeZone(string $type, array $origin, array $event, array $candles): array
    {
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

            if ($type === 'demand' && $candleClose < $low) {
                $invalidated = true;
            }

            if ($type === 'supply' && $candleClose > $high) {
                $invalidated = true;
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
        $area = 'equilibrium';

        if ($currentPrice > $equilibrium) {
            $area = 'premium';
        }

        if ($currentPrice < $equilibrium) {
            $area = 'discount';
        }

        return [
            'status' => 'ok',
            'range_high' => $high,
            'range_low' => $low,
            'equilibrium' => $equilibrium,
            'current_price' => $currentPrice,
            'current_area' => $area,
            'high_source' => $lastHigh,
            'low_source' => $lastLow,
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
        array $liquiditySweeps,
        array $zones,
        array $dealingRange,
        float $currentPrice
    ): array {
        $score = 0;
        $reason = [];

        if ($structure === 'bullish') {
            $score += 2;
            $reason[] = 'Struktur SMC terakhir bullish.';
        }

        if ($structure === 'bearish') {
            $score -= 2;
            $reason[] = 'Struktur SMC terakhir bearish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bullish_bos') {
            $score += 2;
            $reason[] = 'Bullish BOS terakhir mengindikasikan continuation bullish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bearish_bos') {
            $score -= 2;
            $reason[] = 'Bearish BOS terakhir mengindikasikan continuation bearish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bullish_choch') {
            $score += 1;
            $reason[] = 'Bullish CHoCH terakhir mengindikasikan potensi reversal ke bullish.';
        }

        if ($lastEvent && $lastEvent['type'] === 'bearish_choch') {
            $score -= 1;
            $reason[] = 'Bearish CHoCH terakhir mengindikasikan potensi reversal ke bearish.';
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
            $reason[] = 'Ada demand/order-block valid dekat harga saat ini.';
        }

        if ($this->hasValidNearbyZone($zones['supply'], $currentPrice, 'supply')) {
            $score -= 1;
            $reason[] = 'Ada supply/order-block valid dekat harga saat ini.';
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

    private function findLastOppositeCandle(array $candles, int $eventIndex, string $direction): ?array
    {
        $start = max(0, $eventIndex - 20);

        for ($i = $eventIndex - 1; $i >= $start; $i--) {
            $open = (float) $candles[$i]['open'];
            $close = (float) $candles[$i]['close'];

            if ($direction === 'bearish' && $close < $open) {
                return [
                    'index' => $i,
                    'candle' => $candles[$i],
                ];
            }

            if ($direction === 'bullish' && $close > $open) {
                return [
                    'index' => $i,
                    'candle' => $candles[$i],
                ];
            }
        }

        return null;
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
