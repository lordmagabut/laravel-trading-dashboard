<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TechnicalContextService
{
    protected array $analysisTimeframes = ['D1', 'H4', 'H1', 'M15', 'M5'];

    public function __construct(
        protected SmcContextService $smcContextService
    ) {}

    public function build(string $symbol, string $executionTimeframe = 'M15'): array
    {
        $symbol = strtoupper($symbol);
        $executionTimeframe = strtoupper($executionTimeframe);

        $result = [
            'symbol' => $symbol,
            'execution_timeframe' => $executionTimeframe,
            'generated_at_utc' => now('UTC')->toDateTimeString(),
            'current_price' => null,
            'bias' => [],
            'summary' => [
                'higher_timeframe_bias' => 'neutral',
                'execution_bias' => 'neutral',
                'preferred_action' => 'NO_TRADE',
                'reason' => [],
            ],
            'smc' => [],
        ];

        foreach ($this->analysisTimeframes as $timeframe) {
            $candles = $this->getCandles($symbol, $timeframe, 300);
            $result['smc'][$timeframe] = $this->smcContextService->analyze($candles, $timeframe);

            if (count($candles) < 60) {
                $result['bias'][$timeframe] = [
                    'timeframe' => $timeframe,
                    'bias' => 'insufficient_data',
                    'score' => 0,
                    'reason' => ['Data candle kurang dari 60.'],
                    'last_close' => null,
                ];

                continue;
            }

            $context = $this->analyzeTimeframe($candles, $timeframe);
            $result['bias'][$timeframe] = $context;

            if ($timeframe === $executionTimeframe) {
                $result['current_price'] = $context['last_close'];
            }
        }

        $result['summary'] = $this->buildSummary($result['bias'], $executionTimeframe);
        $result['smc_summary'] = $this->buildSmcSummary($result['smc'], $executionTimeframe);

        return $result;
    }

    protected function getCandles(string $symbol, string $timeframe, int $limit = 300): array
    {
        return DB::table('market_data')
            ->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderByDesc('tick_time')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($row) {
                return [
                    'time' => $row->tick_time,
                    'open' => (float) $row->open,
                    'high' => (float) $row->high,
                    'low' => (float) $row->low,
                    'close' => (float) $row->close,
                    'volume' => (int) $row->volume,
                ];
            })
            ->toArray();
    }

    protected function analyzeTimeframe(array $candles, string $timeframe): array
    {
        $closes = array_column($candles, 'close');
        $lastCandle = end($candles);
        $lastClose = $lastCandle['close'];

        $ema20 = $this->ema($closes, 20);
        $ema50 = $this->ema($closes, 50);
        $ema200 = $this->ema($closes, 200);
        $atr14 = $this->atr($candles, 14);

        $swings = $this->detectSwings($candles, 3);
        $structure = $this->detectStructure($swings, $lastClose);

        $score = 0;
        $reason = [];

        if ($structure['structure'] === 'bullish') {
            $score += 2;
            $reason[] = 'Market structure membentuk Higher High dan Higher Low.';
        }

        if ($structure['structure'] === 'bearish') {
            $score -= 2;
            $reason[] = 'Market structure membentuk Lower High dan Lower Low.';
        }

        if ($structure['bos'] === 'bullish_bos') {
            $score += 2;
            $reason[] = 'Bullish BOS terdeteksi, close menembus swing high sebelumnya.';
        }

        if ($structure['bos'] === 'bearish_bos') {
            $score -= 2;
            $reason[] = 'Bearish BOS terdeteksi, close menembus swing low sebelumnya.';
        }

        if ($ema50 !== null && $lastClose > $ema50) {
            $score += 1;
            $reason[] = 'Close berada di atas EMA50.';
        }

        if ($ema50 !== null && $lastClose < $ema50) {
            $score -= 1;
            $reason[] = 'Close berada di bawah EMA50.';
        }

        if ($ema50 !== null && $ema200 !== null && $ema50 > $ema200) {
            $score += 1;
            $reason[] = 'EMA50 berada di atas EMA200.';
        }

        if ($ema50 !== null && $ema200 !== null && $ema50 < $ema200) {
            $score -= 1;
            $reason[] = 'EMA50 berada di bawah EMA200.';
        }

        if ($ema20 !== null && $this->emaSlope($closes, 20, 5) === 'up') {
            $score += 1;
            $reason[] = 'Slope EMA20 naik.';
        }

        if ($ema20 !== null && $this->emaSlope($closes, 20, 5) === 'down') {
            $score -= 1;
            $reason[] = 'Slope EMA20 turun.';
        }

        $bias = match (true) {
            $score >= 3 => 'bullish',
            $score <= -3 => 'bearish',
            default => 'neutral',
        };

        return [
            'timeframe' => $timeframe,
            'bias' => $bias,
            'score' => $score,
            'last_close' => $lastClose,
            'last_candle_time' => $lastCandle['time'],
            'ema' => [
                'ema20' => $ema20,
                'ema50' => $ema50,
                'ema200' => $ema200,
            ],
            'atr14' => $atr14,
            'structure' => $structure,
            'levels' => [
                'support' => $this->nearestSupports($swings, $lastClose),
                'resistance' => $this->nearestResistances($swings, $lastClose),
            ],
            'reason' => $reason,
        ];
    }

    protected function ema(array $values, int $period): ?float
    {
        if (count($values) < $period) {
            return null;
        }

        $slice = array_slice($values, 0, $period);
        $ema = array_sum($slice) / $period;
        $multiplier = 2 / ($period + 1);

        for ($i = $period; $i < count($values); $i++) {
            $ema = (($values[$i] - $ema) * $multiplier) + $ema;
        }

        return round($ema, 5);
    }

    protected function emaSlope(array $values, int $period, int $lookback = 5): string
    {
        if (count($values) < ($period + $lookback + 2)) {
            return 'flat';
        }

        $current = $this->ema($values, $period);
        $previousValues = array_slice($values, 0, count($values) - $lookback);
        $previous = $this->ema($previousValues, $period);

        if ($current === null || $previous === null) {
            return 'flat';
        }

        if ($current > $previous) {
            return 'up';
        }

        if ($current < $previous) {
            return 'down';
        }

        return 'flat';
    }

    protected function atr(array $candles, int $period = 14): ?float
    {
        if (count($candles) < $period + 1) {
            return null;
        }

        $trueRanges = [];

        for ($i = 1; $i < count($candles); $i++) {
            $high = $candles[$i]['high'];
            $low = $candles[$i]['low'];
            $prevClose = $candles[$i - 1]['close'];

            $trueRanges[] = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
        }

        $lastRanges = array_slice($trueRanges, -$period);

        return round(array_sum($lastRanges) / count($lastRanges), 5);
    }

    protected function detectSwings(array $candles, int $depth = 3): array
    {
        $swingHighs = [];
        $swingLows = [];

        $count = count($candles);

        for ($i = $depth; $i < $count - $depth; $i++) {
            $currentHigh = $candles[$i]['high'];
            $currentLow = $candles[$i]['low'];

            $isSwingHigh = true;
            $isSwingLow = true;

            for ($j = $i - $depth; $j <= $i + $depth; $j++) {
                if ($j === $i) {
                    continue;
                }

                if ($candles[$j]['high'] >= $currentHigh) {
                    $isSwingHigh = false;
                }

                if ($candles[$j]['low'] <= $currentLow) {
                    $isSwingLow = false;
                }
            }

            if ($isSwingHigh) {
                $swingHighs[] = [
                    'time' => $candles[$i]['time'],
                    'price' => $currentHigh,
                ];
            }

            if ($isSwingLow) {
                $swingLows[] = [
                    'time' => $candles[$i]['time'],
                    'price' => $currentLow,
                ];
            }
        }

        return [
            'highs' => $swingHighs,
            'lows' => $swingLows,
        ];
    }

    protected function detectStructure(array $swings, float $lastClose): array
    {
        $highs = $swings['highs'];
        $lows = $swings['lows'];

        $lastHigh = count($highs) >= 1 ? $highs[count($highs) - 1] : null;
        $prevHigh = count($highs) >= 2 ? $highs[count($highs) - 2] : null;

        $lastLow = count($lows) >= 1 ? $lows[count($lows) - 1] : null;
        $prevLow = count($lows) >= 2 ? $lows[count($lows) - 2] : null;

        $structure = 'ranging';

        if ($lastHigh && $prevHigh && $lastLow && $prevLow) {
            if ($lastHigh['price'] > $prevHigh['price'] && $lastLow['price'] > $prevLow['price']) {
                $structure = 'bullish';
            } elseif ($lastHigh['price'] < $prevHigh['price'] && $lastLow['price'] < $prevLow['price']) {
                $structure = 'bearish';
            }
        }

        $bos = 'none';

        if ($lastHigh && $lastClose > $lastHigh['price']) {
            $bos = 'bullish_bos';
        }

        if ($lastLow && $lastClose < $lastLow['price']) {
            $bos = 'bearish_bos';
        }

        return [
            'structure' => $structure,
            'bos' => $bos,
            'last_swing_high' => $lastHigh,
            'previous_swing_high' => $prevHigh,
            'last_swing_low' => $lastLow,
            'previous_swing_low' => $prevLow,
        ];
    }

    protected function nearestSupports(array $swings, float $price): array
    {
        return collect($swings['lows'])
            ->filter(fn ($swing) => $swing['price'] < $price)
            ->sortByDesc('price')
            ->take(5)
            ->values()
            ->toArray();
    }

    protected function nearestResistances(array $swings, float $price): array
    {
        return collect($swings['highs'])
            ->filter(fn ($swing) => $swing['price'] > $price)
            ->sortBy('price')
            ->take(5)
            ->values()
            ->toArray();
    }

    protected function buildSummary(array $bias, string $executionTimeframe): array
    {
        $directions = collect(['D1', 'H4', 'H1'])
            ->map(fn ($tf) => $bias[$tf]['bias'] ?? 'neutral')
            ->filter(fn ($value) => in_array($value, ['bullish', 'bearish', 'neutral']))
            ->values();

        $bullishCount = $directions->filter(fn ($v) => $v === 'bullish')->count();
        $bearishCount = $directions->filter(fn ($v) => $v === 'bearish')->count();

        $higherBias = 'neutral';

        if ($bullishCount >= 2) {
            $higherBias = 'bullish';
        }

        if ($bearishCount >= 2) {
            $higherBias = 'bearish';
        }

        $executionBias = $bias[$executionTimeframe]['bias'] ?? 'neutral';

        $preferredAction = 'NO_TRADE';
        $reason = [];

        if ($higherBias === 'bullish' && in_array($executionBias, ['bullish', 'neutral'])) {
            $preferredAction = 'LOOK_FOR_BUY';
            $reason[] = 'Higher timeframe mayoritas bullish.';
        } elseif ($higherBias === 'bearish' && in_array($executionBias, ['bearish', 'neutral'])) {
            $preferredAction = 'LOOK_FOR_SELL';
            $reason[] = 'Higher timeframe mayoritas bearish.';
        } else {
            $reason[] = 'Bias antar timeframe belum selaras.';
        }

        return [
            'higher_timeframe_bias' => $higherBias,
            'execution_bias' => $executionBias,
            'preferred_action' => $preferredAction,
            'reason' => $reason,
        ];
    }

    protected function buildSmcSummary(array $smc, string $executionTimeframe): array
    {
        $directions = collect(['D1', 'H4', 'H1'])
            ->map(fn ($tf) => $smc[$tf]['bias'] ?? 'neutral')
            ->filter(fn ($value) => in_array($value, ['bullish', 'bearish', 'neutral']))
            ->values();

        $bullishCount = $directions->filter(fn ($value) => $value === 'bullish')->count();
        $bearishCount = $directions->filter(fn ($value) => $value === 'bearish')->count();

        $higherBias = 'neutral';

        if ($bullishCount >= 2) {
            $higherBias = 'bullish';
        }

        if ($bearishCount >= 2) {
            $higherBias = 'bearish';
        }

        $executionBias = $smc[$executionTimeframe]['bias'] ?? 'neutral';
        $executionStructure = $smc[$executionTimeframe]['structure'] ?? 'unknown';
        $executionLastEvent = data_get($smc, "{$executionTimeframe}.last_event.type");

        $preferredAction = 'NO_TRADE';
        $reason = [];

        if ($higherBias === 'bullish' && in_array($executionBias, ['bullish', 'neutral'], true)) {
            $preferredAction = 'LOOK_FOR_BUY';
            $reason[] = 'SMC higher timeframe mayoritas bullish.';
        } elseif ($higherBias === 'bearish' && in_array($executionBias, ['bearish', 'neutral'], true)) {
            $preferredAction = 'LOOK_FOR_SELL';
            $reason[] = 'SMC higher timeframe mayoritas bearish.';
        } else {
            $reason[] = 'SMC bias antar timeframe belum selaras.';
        }

        if ($executionLastEvent) {
            $reason[] = "SMC event terakhir pada {$executionTimeframe}: {$executionLastEvent}.";
        }

        return [
            'higher_timeframe_bias' => $higherBias,
            'execution_bias' => $executionBias,
            'execution_structure' => $executionStructure,
            'execution_last_event' => $executionLastEvent,
            'preferred_action' => $preferredAction,
            'reason' => $reason,
        ];
    }
}
