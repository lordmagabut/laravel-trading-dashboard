@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@push('style')
<style>
    .dashboard-hero {
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }

    .dashboard-summary-card {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        background: #fff;
    }

    .dashboard-summary-card .card-body {
        min-height: 124px;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .dashboard-summary-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 6px;
        background: #cbd5e1;
    }

    .summary-kicker {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .75rem;
    }

    .summary-value {
        margin-bottom: 0;
        font-size: 2.25rem;
        line-height: 1.1;
        font-weight: 700;
        background: transparent !important;
    }

    .tone-generated {
        background: #fffdf2;
        border-color: #f6d365;
    }

    .tone-generated::before,
    .tone-generated .summary-value {
        background: #b7791f;
        color: #b7791f;
    }

    .tone-agent {
        background: #f8fbff;
        border-color: #bfdbfe;
    }

    .tone-agent::before,
    .tone-agent .summary-value {
        background: #2563eb;
        color: #2563eb;
    }

    .tone-completed {
        background: #f4fbf6;
        border-color: #bbf7d0;
    }

    .tone-completed::before,
    .tone-completed .summary-value {
        background: #15803d;
        color: #15803d;
    }

    .tone-failed {
        background: #fff5f5;
        border-color: #fecaca;
    }

    .tone-failed::before,
    .tone-failed .summary-value {
        background: #dc2626;
        color: #dc2626;
    }

    .dashboard-panel {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .dashboard-table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .dashboard-section-title {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: .02em;
        color: #0f172a;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')

@php
    $statusBadge = [
        'GENERATED' => 'warning',
        'SENT_TO_TECHNICAL_AGENT' => 'primary',
        'TECHNICAL_COMPLETED' => 'info',
        'FAILED' => 'danger',
    ];

    $statusLabels = [
        'GENERATED' => 'Generated',
        'SENT_TO_TECHNICAL_AGENT' => 'Sent to Technical Agent',
        'TECHNICAL_COMPLETED' => 'Technical Completed',
        'FAILED' => 'Failed',
    ];

    $statusTone = [
        'GENERATED' => 'tone-generated',
        'SENT_TO_TECHNICAL_AGENT' => 'tone-agent',
        'TECHNICAL_COMPLETED' => 'tone-completed',
        'FAILED' => 'tone-failed',
    ];
@endphp

<div class="page-content">

    <div class="dashboard-hero d-flex justify-content-between align-items-center flex-wrap grid-margin">
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
        @foreach(['GENERATED', 'SENT_TO_TECHNICAL_AGENT', 'TECHNICAL_COMPLETED', 'FAILED'] as $status)
            <div class="col-md-2 stretch-card grid-margin">
                <div class="card dashboard-summary-card {{ $statusTone[$status] ?? '' }}">
                    <div class="card-body">
                        <div class="summary-kicker">{{ $statusLabels[$status] ?? $status }}</div>
                        <h3 class="summary-value">{{ $summary[$status] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card dashboard-panel mb-4">
        <div class="card-body">
            <div class="dashboard-section-title">Generate New Analysis</div>

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

    <div class="card dashboard-panel mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('technical-analyses.index') }}" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['GENERATED', 'SENT_TO_TECHNICAL_AGENT', 'TECHNICAL_COMPLETED', 'FAILED'] as $status)
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

    <div class="card dashboard-panel">
        <div class="card-body">

            <div class="dashboard-section-title">Analysis List</div>

            <div class="table-responsive">
                <table id="technicalAnalysesTable" class="table table-hover table-bordered align-middle nowrap dashboard-table" style="width:100%">
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

        </div>
    </div>

</div>

@endsection

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
    $(document).ready(function () {
        $('#technicalAnalysesTable').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: [12] }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            }
        });
    });
</script>
@endpush
