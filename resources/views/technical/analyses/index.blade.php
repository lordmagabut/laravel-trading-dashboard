@extends('layout.master')

@section('content')

@php
    $statusBadge = [
        'GENERATED' => 'warning',
        'SENT_TO_AI' => 'primary',
        'AI_COMPLETED' => 'info',
        'SIGNAL_CREATED' => 'success',
        'FAILED' => 'danger',
    ];
@endphp

<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">Technical Analyses</h4>
            <p class="text-muted mb-0">
                Generate technical context dari market_data dan siapkan prompt untuk OpenClaw.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        @foreach(['GENERATED', 'SENT_TO_AI', 'AI_COMPLETED', 'SIGNAL_CREATED', 'FAILED'] as $status)
            <div class="col-md-2 stretch-card grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">{{ $status }}</h6>
                        <h3 class="mb-0">{{ $summary[$status] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Generate New Analysis</h6>

            <form method="POST" action="{{ route('technical-analyses.generate') }}" class="row g-3 align-items-end">
                @csrf

                <div class="col-md-3">
                    <label class="form-label">Symbol</label>
                    <input type="text"
                           name="symbol"
                           class="form-control"
                           value="{{ old('symbol', 'XAUUSD') }}"
                           required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Execution Timeframe</label>
                    <select name="execution_timeframe" class="form-select" required>
                        @foreach(['M5', 'M15', 'M30', 'H1', 'H4', 'D1'] as $tf)
                            <option value="{{ $tf }}" @selected(old('execution_timeframe', 'M15') === $tf)>
                                {{ $tf }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        Generate Analysis
                    </button>
                </div>
            </form>

            @if($errors->any())
                <div class="alert alert-danger mt-3 mb-0">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('technical-analyses.index') }}" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['GENERATED', 'SENT_TO_AI', 'AI_COMPLETED', 'SIGNAL_CREATED', 'FAILED'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Symbol</label>
                    <select name="symbol" class="form-select">
                        <option value="">All Symbol</option>
                        @foreach($symbols as $symbol)
                            <option value="{{ $symbol }}" @selected(request('symbol') === $symbol)>
                                {{ $symbol }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Timeframe</label>
                    <select name="execution_timeframe" class="form-select">
                        <option value="">All Timeframe</option>
                        @foreach($timeframes as $timeframe)
                            <option value="{{ $timeframe }}" @selected(request('execution_timeframe') === $timeframe)>
                                {{ $timeframe }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('technical-analyses.index') }}" class="btn btn-light">Reset</a>
                </div>

            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <h6 class="card-title">Analysis List</h6>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>UUID</th>
                            <th>Symbol</th>
                            <th>TF</th>
                            <th>HTF Bias</th>
                            <th>Exec Bias</th>
                            <th>Preferred</th>
                            <th>Price</th>
                            <th>Decision</th>
                            <th>Confidence</th>
                            <th>Status</th>
                            <th>Signal</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($analyses as $analysis)
                            <tr>
                                <td>
                                    <small>{{ $analysis->created_at ? $analysis->created_at->timezone('Asia/Jakarta')->format('Y-m-d H:i') : '' }}</small>
                                </td>

                                <td>
                                    <small>{{ \Illuminate\Support\Str::limit($analysis->analysis_uuid, 8, '') }}</small>
                                </td>

                                <td>
                                    <strong>{{ $analysis->symbol }}</strong>
                                </td>

                                <td>{{ $analysis->execution_timeframe }}</td>

                                <td>{{ $analysis->higher_timeframe_bias }}</td>

                                <td>{{ $analysis->execution_bias }}</td>

                                <td>{{ $analysis->preferred_action }}</td>

                                <td>{{ $analysis->current_price }}</td>

                                <td>
                                    @if($analysis->decision === 'BUY')
                                        <span class="badge bg-success">BUY</span>
                                    @elseif($analysis->decision === 'SELL')
                                        <span class="badge bg-danger">SELL</span>
                                    @elseif($analysis->decision === 'NO_TRADE')
                                        <span class="badge bg-secondary">NO_TRADE</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $analysis->confidence !== null ? $analysis->confidence . '%' : '-' }}
                                </td>

                                <td>
                                    <span class="badge bg-{{ $statusBadge[$analysis->status] ?? 'secondary' }}">
                                        {{ $analysis->status }}
                                    </span>
                                </td>

                                <td>
                                    @if($analysis->tradeSignals->count())
                                        <span class="badge bg-success">
                                            {{ $analysis->tradeSignals->count() }} signal
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('technical-analyses.show', $analysis) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">
                                    Belum ada technical analysis.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $analyses->links() }}
            </div>

        </div>
    </div>

</div>

@endsection