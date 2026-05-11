@extends('layout.master')

@section('content')
@php
    $rawContext = $technicalAnalysis->raw_context_json ?? [];
    $smcSummary = data_get($rawContext, 'smc_summary', []);
    $executionTimeframe = $technicalAnalysis->execution_timeframe;
    $executionSmc = data_get($rawContext, "smc.{$executionTimeframe}", []);
    $demandZones = collect(data_get($executionSmc, 'zones.demand', []))
        ->reject(fn ($zone) => $zone['invalidated'] ?? false)
        ->reverse()
        ->take(5);
    $supplyZones = collect(data_get($executionSmc, 'zones.supply', []))
        ->reject(fn ($zone) => $zone['invalidated'] ?? false)
        ->reverse()
        ->take(5);
@endphp

<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">Technical Analysis Detail</h4>
            <p class="text-muted mb-0">
                {{ $technicalAnalysis->symbol }} / {{ $technicalAnalysis->execution_timeframe }}
            </p>
        </div>

        <a href="{{ route('technical-analyses.index') }}" class="btn btn-light">
            Back
        </a>
    </div>

    <div class="row">

        <div class="col-md-4 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Summary</h6>

                    <table class="table table-sm">
                        <tr>
                            <th>UUID</th>
                            <td>{{ $technicalAnalysis->analysis_uuid }}</td>
                        </tr>
                        <tr>
                            <th>Symbol</th>
                            <td>{{ $technicalAnalysis->symbol }}</td>
                        </tr>
                        <tr>
                            <th>Timeframe</th>
                            <td>{{ $technicalAnalysis->execution_timeframe }}</td>
                        </tr>
                        <tr>
                            <th>HTF Bias</th>
                            <td>{{ $technicalAnalysis->higher_timeframe_bias }}</td>
                        </tr>
                        <tr>
                            <th>Execution Bias</th>
                            <td>{{ $technicalAnalysis->execution_bias }}</td>
                        </tr>
                        <tr>
                            <th>Preferred</th>
                            <td>{{ $technicalAnalysis->preferred_action }}</td>
                        </tr>
                        <tr>
                            <th>Current Price</th>
                            <td>{{ $technicalAnalysis->current_price }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>{{ $technicalAnalysis->status }}</td>
                        </tr>
                        <tr>
                            <th>Decision</th>
                            <td>{{ $technicalAnalysis->decision ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Confidence</th>
                            <td>{{ $technicalAnalysis->confidence !== null ? $technicalAnalysis->confidence . '%' : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-8 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Prompt Text</h6>
                    <pre style="white-space: pre-wrap; max-height: 420px; overflow:auto;">{{ $technicalAnalysis->prompt_text }}</pre>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-md-3 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-0">SMC HTF Bias</h6>
                    <h3 class="mt-3 mb-2">{{ data_get($smcSummary, 'higher_timeframe_bias', '-') }}</h3>
                    <p class="text-muted tx-13 mb-0">D1/H4/H1</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-0">SMC Exec Bias</h6>
                    <h3 class="mt-3 mb-2">{{ data_get($smcSummary, 'execution_bias', '-') }}</h3>
                    <p class="text-muted tx-13 mb-0">{{ $executionTimeframe }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-0">SMC Structure</h6>
                    <h3 class="mt-3 mb-2">{{ data_get($smcSummary, 'execution_structure', '-') }}</h3>
                    <p class="text-muted tx-13 mb-0">Struktur TF entry</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-0">SMC Last Event</h6>
                    <h3 class="mt-3 mb-2">{{ data_get($smcSummary, 'execution_last_event', '-') }}</h3>
                    <p class="text-muted tx-13 mb-0">BOS / CHoCH</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">SMC Execution Context</h6>

            <div class="table-responsive mb-4">
                <table class="table table-sm">
                    <tr>
                        <th>Bias</th>
                        <td>{{ data_get($executionSmc, 'bias', '-') }}</td>
                        <th>Score</th>
                        <td>{{ data_get($executionSmc, 'score', '-') }}</td>
                    </tr>
                    <tr>
                        <th>Structure</th>
                        <td>{{ data_get($executionSmc, 'structure', '-') }}</td>
                        <th>Premium/Discount</th>
                        <td>{{ data_get($executionSmc, 'premium_discount.current_area', '-') }}</td>
                    </tr>
                    <tr>
                        <th>Last Event</th>
                        <td>{{ data_get($executionSmc, 'last_event.type', '-') }}</td>
                        <th>Liquidity Sweeps</th>
                        <td>{{ count(data_get($executionSmc, 'liquidity_sweeps', [])) }}</td>
                    </tr>
                </table>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h6>Demand Zones</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Low</th>
                                    <th>High</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($demandZones as $zone)
                                    <tr>
                                        <td>{{ $zone['low'] ?? '-' }}</td>
                                        <td>{{ $zone['high'] ?? '-' }}</td>
                                        <td>{{ $zone['status'] ?? '-' }}</td>
                                        <td>{{ $zone['created_by'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted text-center">Tidak ada demand zone valid.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6>Supply Zones</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Low</th>
                                    <th>High</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($supplyZones as $zone)
                                    <tr>
                                        <td>{{ $zone['low'] ?? '-' }}</td>
                                        <td>{{ $zone['high'] ?? '-' }}</td>
                                        <td>{{ $zone['status'] ?? '-' }}</td>
                                        <td>{{ $zone['created_by'] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted text-center">Tidak ada supply zone valid.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">AI Response JSON</h6>
            <pre style="white-space: pre-wrap; max-height: 420px; overflow:auto;">{{ json_encode($technicalAnalysis->ai_response_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Raw Context JSON</h6>
            <pre style="white-space: pre-wrap; max-height: 520px; overflow:auto;">{{ json_encode($technicalAnalysis->raw_context_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="card-title">Trade Signals</h6>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Signal UUID</th>
                            <th>Decision</th>
                            <th>Entry</th>
                            <th>SL</th>
                            <th>TP1</th>
                            <th>RR</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($technicalAnalysis->tradeSignals as $signal)
                            <tr>
                                <td>{{ $signal->signal_uuid }}</td>
                                <td>{{ $signal->decision }}</td>
                                <td>{{ $signal->entry_price }}</td>
                                <td>{{ $signal->stop_loss }}</td>
                                <td>{{ $signal->take_profit_1 }}</td>
                                <td>{{ $signal->risk_reward }}</td>
                                <td>{{ $signal->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Belum ada trade signal.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

@endsection
