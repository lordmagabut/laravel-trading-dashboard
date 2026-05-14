<?php

namespace App\Http\Controllers;

use App\Models\FundamentalAnalysis;
use App\Services\FundamentalAnalysisGeneratorService;
use App\Services\FundamentalAnalysisPromptService;
use Illuminate\Http\Request;

class FundamentalAnalysisController extends Controller
{
    protected $generatorService;
    protected $promptService;

    public function __construct(FundamentalAnalysisGeneratorService $generatorService, FundamentalAnalysisPromptService $promptService)
    {
        $this->generatorService = $generatorService;
        $this->promptService = $promptService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = FundamentalAnalysis::query();

        if ($request->has('symbol')) {
            $query->where('symbol', $request->symbol);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
            'timeframe_scope' => 'nullable|string',
        ]);

        $analysis = $this->generatorService->generateForSymbol(
            $request->symbol,
            $request->timeframe_scope
        );

        return response()->json($analysis, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $analysis = FundamentalAnalysis::findOrFail($id);
        return response()->json($analysis);
    }

    /**
     * Get latest analysis for symbol.
     */
    public function latest(Request $request)
    {
        $request->validate([
            'symbol' => 'required|string',
        ]);

        $analysis = FundamentalAnalysis::where('symbol', $request->symbol)
            ->latest()
            ->first();

        if (!$analysis) {
            return response()->json(['message' => 'No analysis found'], 404);
        }

        return response()->json($analysis);
    }

    /**
     * Get pending fundamental analyses.
     */
    public function pendingFundamentalAnalyses(Request $request)
    {
        $limit = $request->get('limit', 10);
        $query = FundamentalAnalysis::where('status', 'GENERATED')
            ->orderBy('created_at', 'asc')
            ->limit($limit);

        $analyses = $query->get();

        // Optionally mark as sent
        if ($request->get('mark_sent') === '1') {
            $query->update(['status' => 'SENT_TO_AGENT']);
        }

        return response()->json([
            'data' => $analyses,
            'count' => $analyses->count(),
        ]);
    }

    /**
     * Submit fundamental result from agent.
     */
    public function submitResult(Request $request, $id)
    {
        $analysis = FundamentalAnalysis::findOrFail($id);

        $request->validate([
            'fundamental_bias' => 'required|in:bullish,bearish,neutral',
            'news_risk_level' => 'required|in:low,medium,high',
            'sentiment_bias' => 'required|in:bullish,bearish,neutral',
            'avoid_trade' => 'required|boolean',
            'confidence' => 'required|integer|min:0|max:100',
            'reason_summary' => 'required|string',
            'reasons_json' => 'required|array',
            'agent_name' => 'nullable|string',
            'agent_model' => 'nullable|string',
        ]);

        $analysis->update([
            'fundamental_bias' => $request->fundamental_bias,
            'news_risk_level' => $request->news_risk_level,
            'sentiment_bias' => $request->sentiment_bias,
            'avoid_trade' => $request->avoid_trade,
            'confidence' => $request->confidence,
            'reason_summary' => $request->reason_summary,
            'reasons_json' => $request->reasons_json,
            'ai_response_json' => $request->all(),
            'agent_name' => $request->agent_name,
            'agent_model' => $request->agent_model,
            'status' => 'COMPLETED',
        ]);

        return response()->json($analysis);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
