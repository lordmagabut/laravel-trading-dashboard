<?php

namespace App\Http\Controllers;

use App\Models\FundamentalAnalysis;
use Illuminate\Http\Request;

class FundamentalAnalysisPageController extends Controller
{
    public function index(Request $request)
    {
        $query = FundamentalAnalysis::query();

        if ($request->has('symbol')) {
            $query->where('symbol', $request->symbol);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $analyses = $query->orderBy('created_at', 'desc')->paginate(25);

        // Summary counts
        $summary = [
            'GENERATED' => FundamentalAnalysis::where('status', 'GENERATED')->count(),
            'SENT_TO_AGENT' => FundamentalAnalysis::where('status', 'SENT_TO_AGENT')->count(),
            'COMPLETED' => FundamentalAnalysis::where('status', 'COMPLETED')->count(),
            'FAILED' => FundamentalAnalysis::where('status', 'FAILED')->count(),
        ];

        return view('fundamental.analyses.index', compact('analyses', 'summary'));
    }
}
