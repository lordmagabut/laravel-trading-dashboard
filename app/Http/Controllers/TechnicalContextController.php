<?php

namespace App\Http\Controllers;

use App\Services\TechnicalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TechnicalContextController extends Controller
{
    public function page()
    {
        $symbols = DB::table('market_data')
            ->select('symbol')
            ->distinct()
            ->orderBy('symbol')
            ->pluck('symbol');

        $timeframes = collect(['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1']);

        return view('technical.context', compact('symbols', 'timeframes'));
    }

    public function api(Request $request, TechnicalContextService $service)
    {
        $request->validate([
            'symbol' => ['required', 'string', 'max:20'],
            'execution_timeframe' => ['nullable', 'string', 'max:10'],
        ]);

        $symbol = strtoupper($request->query('symbol'));
        $executionTimeframe = strtoupper($request->query('execution_timeframe', 'M15'));

        return response()->json(
            $service->build($symbol, $executionTimeframe)
        );
    }
}