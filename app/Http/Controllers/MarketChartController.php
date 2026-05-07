<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketChartController extends Controller
{
    public function index()
    {
        $symbols = DB::table('market_data')
            ->select('symbol')
            ->distinct()
            ->orderBy('symbol')
            ->pluck('symbol');

        $timeframes = DB::table('market_data')
            ->select('timeframe')
            ->distinct()
            ->pluck('timeframe')
            ->sortBy(function ($tf) {
                $order = [
                    'M1' => 1,
                    'M5' => 2,
                    'M15' => 3,
                    'M30' => 4,
                    'H1' => 5,
                    'H4' => 6,
                    'D1' => 7,
                ];

                return $order[$tf] ?? 99;
            })
            ->values();

        return view('market.chart', compact('symbols', 'timeframes'));
    }

    public function candles(Request $request)
    {
        $request->validate([
            'symbol' => ['required', 'string', 'max:20'],
            'timeframe' => ['required', 'string', 'max:10'],
            'limit' => ['nullable', 'integer', 'min:50', 'max:3000'],
        ]);

        $symbol = strtoupper($request->query('symbol'));
        $timeframe = strtoupper($request->query('timeframe'));
        $limit = (int) $request->query('limit', 500);

        $rows = DB::table('market_data')
            ->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderByDesc('tick_time')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        $candles = $rows->map(function ($row) {
            return [
                // Lightweight Chart butuh UNIX timestamp dalam detik
                'time' => Carbon::parse($row->tick_time, 'UTC')->timestamp,

                'open' => (float) $row->open,
                'high' => (float) $row->high,
                'low' => (float) $row->low,
                'close' => (float) $row->close,
                'volume' => (int) $row->volume,

                // Untuk debug / info tambahan
                'tick_time' => $row->tick_time,
            ];
        });

        return response()->json([
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'limit' => $limit,
            'total' => $candles->count(),
            'data' => $candles,
        ]);
    }
}