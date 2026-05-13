<?php

namespace App\Http\Controllers;

use App\Models\TradeSignal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignalDashboardController extends Controller
{
    public function index(Request $request)
    {
        $signalsQuery = TradeSignal::query()
            ->with('technicalAnalysis')
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('symbol'), function ($query) use ($request) {
                $query->where('symbol', $request->symbol);
            })
            ->when($request->filled('timeframe'), function ($query) use ($request) {
                $query->where('timeframe', $request->timeframe);
            })
            ->orderByRaw("
                FIELD(
                    status,
                    'PENDING',
                    'APPROVED',
                    'SENT_TO_EXECUTOR',
                    'EXECUTED',
                    'REJECTED',
                    'CANCELLED',
                    'FAILED',
                    'EXPIRED'
                )
            ")
            ->latest('created_at');

        $signals = $signalsQuery->get();

        $summary = TradeSignal::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $symbols = TradeSignal::query()
            ->select('symbol')
            ->distinct()
            ->orderBy('symbol')
            ->pluck('symbol');

        $timeframes = TradeSignal::query()
            ->select('timeframe')
            ->distinct()
            ->orderBy('timeframe')
            ->pluck('timeframe');

        return view('signals.index', compact(
            'signals',
            'summary',
            'symbols',
            'timeframes'
        ));
    }

    public function approve(TradeSignal $tradeSignal)
    {
        if ($tradeSignal->status !== 'PENDING') {
            return back()->with('error', 'Signal hanya bisa di-approve jika status masih PENDING.');
        }

        $tradeSignal->update([
            'status' => 'APPROVED',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Signal berhasil di-approve.');
    }

    public function reject(Request $request, TradeSignal $tradeSignal)
    {
        if ($tradeSignal->status !== 'PENDING') {
            return back()->with('error', 'Signal hanya bisa di-reject jika status masih PENDING.');
        }

        $tradeSignal->update([
            'status' => 'REJECTED',
            'notes' => $this->appendNote($tradeSignal->notes, 'Rejected by user. ' . $request->input('notes')),
        ]);

        return back()->with('success', 'Signal berhasil di-reject.');
    }

    public function cancel(Request $request, TradeSignal $tradeSignal)
    {
        if (! in_array($tradeSignal->status, ['PENDING', 'APPROVED'], true)) {
            return back()->with('error', 'Signal hanya bisa di-cancel jika status PENDING atau APPROVED.');
        }

        $tradeSignal->update([
            'status' => 'CANCELLED',
            'notes' => $this->appendNote($tradeSignal->notes, 'Cancelled by user. ' . $request->input('notes')),
        ]);

        return back()->with('success', 'Signal berhasil di-cancel.');
    }

    public function sendToExecutor(TradeSignal $tradeSignal)
    {
        if ($tradeSignal->status !== 'APPROVED') {
            return back()->with('error', 'Signal hanya bisa dikirim ke executor jika status APPROVED.');
        }

        /*
         * Nanti bagian ini diganti dengan:
         * - dispatch Job
         * - HTTP request ke MT5 executor
         * - insert ke executor queue
         * - atau publish ke Redis/RabbitMQ
         */

        return back()->with('info', 'Send to Executor belum dihubungkan. Signal sudah APPROVED dan siap dikirim nanti.');
    }

    private function appendNote(?string $oldNote, ?string $newNote): string
    {
        $newNote = trim((string) $newNote);

        if ($newNote === '') {
            return (string) $oldNote;
        }

        $timestamp = now()->format('Y-m-d H:i:s');

        return trim(($oldNote ? $oldNote . PHP_EOL : '') . '[' . $timestamp . '] ' . $newNote);
    }
}
