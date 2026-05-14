<?php

namespace App\Http\Controllers;

use App\Models\TechnicalAnalysis;
use App\Models\TradingBotPair;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BotPairSettingController extends Controller
{
    public function index()
    {
        $pairs = TradingBotPair::query()
            ->orderBy('symbol')
            ->orderBy('entry_timeframe')
            ->get();

        $symbols = $pairs
            ->pluck('symbol')
            ->filter()
            ->unique()
            ->values();

        $timeframes = $pairs
            ->pluck('entry_timeframe')
            ->filter()
            ->unique()
            ->values();

        $latestAnalyses = TechnicalAnalysis::query()
            ->whereIn('symbol', $symbols)
            ->whereIn('execution_timeframe', $timeframes)
            ->latest('created_at')
            ->get()
            ->unique(function ($analysis) {
                return $analysis->symbol . '|' . $analysis->execution_timeframe;
            })
            ->keyBy(function ($analysis) {
                return $analysis->symbol . '|' . $analysis->execution_timeframe;
            });

        $summary = [
            'total' => TradingBotPair::count(),
            'enabled' => TradingBotPair::where('enabled', true)->count(),
            'auto_generate' => TradingBotPair::where('auto_generate', true)->count(),
            'disabled' => TradingBotPair::where('enabled', false)->count(),
        ];

        return view('bot_pairs.index', compact(
            'pairs',
            'latestAnalyses',
            'summary'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:30'],
            'entry_timeframe' => ['required', 'string', 'max:10'],
            'higher_timeframes' => ['nullable', 'string'],
            'agent_risk_mode' => ['required', Rule::in(TradingBotPair::AGENT_RISK_MODES)],
            'enabled' => ['nullable'],
            'auto_generate' => ['nullable'],
            'notes' => ['nullable', 'string'],
        ]);

        $symbol = strtoupper(trim($validated['symbol']));
        $entryTimeframe = strtoupper(trim($validated['entry_timeframe']));

        $exists = TradingBotPair::query()
            ->where('symbol', $symbol)
            ->where('entry_timeframe', $entryTimeframe)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->with('error', 'Pair dan timeframe ini sudah ada.');
        }

        TradingBotPair::create([
            'symbol' => $symbol,
            'entry_timeframe' => $entryTimeframe,
            'higher_timeframes' => $this->parseHigherTimeframes($validated['higher_timeframes'] ?? null),
            'agent_risk_mode' => $validated['agent_risk_mode'],
            'enabled' => $request->boolean('enabled'),
            'auto_generate' => $request->boolean('auto_generate'),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('bot-pairs.index')
            ->with('success', 'Bot pair berhasil ditambahkan.');
    }

    public function edit(TradingBotPair $tradingBotPair)
    {
        return view('bot_pairs.edit', compact('tradingBotPair'));
    }

    public function update(Request $request, TradingBotPair $tradingBotPair)
    {
        $validated = $request->validate([
            'symbol' => ['required', 'string', 'max:30'],
            'entry_timeframe' => ['required', 'string', 'max:10'],
            'higher_timeframes' => ['nullable', 'string'],
            'agent_risk_mode' => ['required', Rule::in(TradingBotPair::AGENT_RISK_MODES)],
            'enabled' => ['nullable'],
            'auto_generate' => ['nullable'],
            'notes' => ['nullable', 'string'],
        ]);

        $symbol = strtoupper(trim($validated['symbol']));
        $entryTimeframe = strtoupper(trim($validated['entry_timeframe']));

        $exists = TradingBotPair::query()
            ->where('id', '!=', $tradingBotPair->id)
            ->where('symbol', $symbol)
            ->where('entry_timeframe', $entryTimeframe)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->with('error', 'Pair dan timeframe ini sudah dipakai oleh setting lain.');
        }

        $tradingBotPair->update([
            'symbol' => $symbol,
            'entry_timeframe' => $entryTimeframe,
            'higher_timeframes' => $this->parseHigherTimeframes($validated['higher_timeframes'] ?? null),
            'agent_risk_mode' => $validated['agent_risk_mode'],
            'enabled' => $request->boolean('enabled'),
            'auto_generate' => $request->boolean('auto_generate'),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('bot-pairs.index')
            ->with('success', 'Bot pair berhasil diupdate.');
    }

    public function toggleEnabled(TradingBotPair $tradingBotPair)
    {
        $tradingBotPair->update([
            'enabled' => ! $tradingBotPair->enabled,
        ]);

        return back()->with('success', 'Status enabled berhasil diubah.');
    }

    public function toggleAutoGenerate(TradingBotPair $tradingBotPair)
    {
        $tradingBotPair->update([
            'auto_generate' => ! $tradingBotPair->auto_generate,
        ]);

        return back()->with('success', 'Status auto generate berhasil diubah.');
    }

    public function destroy(TradingBotPair $tradingBotPair)
    {
        $tradingBotPair->delete();

        return redirect()
            ->route('bot-pairs.index')
            ->with('success', 'Bot pair berhasil dihapus.');
    }

    private function parseHigherTimeframes(?string $value): array
    {
        if (! $value) {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($item) => strtoupper(trim($item)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }
}
