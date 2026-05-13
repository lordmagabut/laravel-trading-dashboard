<?php

namespace App\Http\Controllers;

use App\Models\TechnicalAnalysis;
use App\Models\TradeSignal;
use App\Models\TradingBotPair;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $enabledPairs = TradingBotPair::query()
            ->where('enabled', true)
            ->orderBy('symbol')
            ->get();

        $latestAnalysis = TechnicalAnalysis::query()
            ->latest('created_at')
            ->first();

        $focusPair = $enabledPairs->firstWhere('auto_generate', true) ?? $enabledPairs->first();
        $focusSymbol = $latestAnalysis->symbol ?? $focusPair?->symbol ?? DB::table('market_data')->orderBy('symbol')->value('symbol');
        $focusTimeframe = $latestAnalysis->execution_timeframe ?? $focusPair?->entry_timeframe ?? 'M15';

        $focusAnalysis = TechnicalAnalysis::query()
            ->where('symbol', $focusSymbol)
            ->where('execution_timeframe', $focusTimeframe)
            ->latest('created_at')
            ->first();

        $marketRows = collect();
        if ($focusSymbol && $focusTimeframe) {
            $marketRows = DB::table('market_data')
                ->select(['tick_time', 'open', 'high', 'low', 'close'])
                ->where('symbol', $focusSymbol)
                ->where('timeframe', $focusTimeframe)
                ->orderByDesc('tick_time')
                ->limit(96)
                ->get()
                ->reverse()
                ->values();
        }

        $lastCandle = $marketRows->last();
        $firstCandle = $marketRows->first();

        $priceChange = null;
        $priceChangePercent = null;
        if ($firstCandle && $lastCandle && (float) $firstCandle->close !== 0.0) {
            $priceChange = (float) $lastCandle->close - (float) $firstCandle->close;
            $priceChangePercent = ($priceChange / (float) $firstCandle->close) * 100;
        }

        $rawContext = $focusAnalysis?->raw_context_json ?? [];
        $summary = $rawContext['summary'] ?? [];
        $smcSummary = $rawContext['smc_summary'] ?? [];

        $workflowSummary = [
            'pairs_enabled' => $enabledPairs->count(),
            'pairs_auto' => $enabledPairs->where('auto_generate', true)->count(),
            'analyses_generated' => TechnicalAnalysis::query()->where('status', 'GENERATED')->count(),
            'analyses_sent' => TechnicalAnalysis::query()->where('status', 'SENT_TO_TECHNICAL_AGENT')->count(),
            'analyses_completed' => TechnicalAnalysis::query()->where('status', 'TECHNICAL_COMPLETED')->count(),
            'signals_pending' => TradeSignal::query()->where('status', 'PENDING')->count(),
            'signals_approved' => TradeSignal::query()->where('status', 'APPROVED')->count(),
            'signals_executed' => TradeSignal::query()->where('status', 'EXECUTED')->count(),
        ];

        $recentAnalyses = TechnicalAnalysis::query()
            ->withCount('tradeSignals')
            ->latest('created_at')
            ->limit(6)
            ->get();

        $recentSignals = TradeSignal::query()
            ->latest('created_at')
            ->limit(6)
            ->get();

        $lastFeedTime = DB::table('market_data')->max('tick_time');

        return view('dashboard', [
            'focusSymbol' => $focusSymbol,
            'focusTimeframe' => $focusTimeframe,
            'focusAnalysis' => $focusAnalysis,
            'summary' => $summary,
            'smcSummary' => $smcSummary,
            'workflowSummary' => $workflowSummary,
            'recentAnalyses' => $recentAnalyses,
            'recentSignals' => $recentSignals,
            'lastCandle' => $lastCandle,
            'priceChange' => $priceChange,
            'priceChangePercent' => $priceChangePercent,
            'lastFeedTime' => $lastFeedTime,
            'candlesLimit' => 300,
        ]);
    }
}
