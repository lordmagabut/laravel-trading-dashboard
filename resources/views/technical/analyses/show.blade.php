@extends('layout.master')

@section('content')

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