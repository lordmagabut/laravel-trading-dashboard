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
        'SENT_TO_AGENT' => 'primary',
        'COMPLETED' => 'success',
        'FAILED' => 'danger',
    ];

    $statusLabels = [
        'GENERATED' => 'Generated',
        'SENT_TO_AGENT' => 'Sent to Fundamental Agent',
        'COMPLETED' => 'Completed',
        'FAILED' => 'Failed',
    ];

    $statusTone = [
        'GENERATED' => 'tone-generated',
        'SENT_TO_AGENT' => 'tone-agent',
        'COMPLETED' => 'tone-completed',
        'FAILED' => 'tone-failed',
    ];
@endphp

<div class="page-content">

    <div class="dashboard-hero d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">Fundamental Analyses</h4>
            <p class="text-muted mb-0">
                Generate fundamental context dari news, calendar, dan sentiment untuk OpenClaw.
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
        @foreach(['GENERATED', 'SENT_TO_AGENT', 'COMPLETED', 'FAILED'] as $status)
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
            <div class="dashboard-section-title">Generate New Fundamental Analysis</div>

            <form method="POST" action="{{ route('fundamental-analyses.store') }}" class="row g-3 align-items-end">
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
                    <label class="form-label">Timeframe Scope</label>
                    <select name="timeframe_scope" class="form-control">
                        <option value="">Global</option>
                        <option value="D1">D1</option>
                        <option value="H4">H4</option>
                        <option value="H1">H1</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Generate Analysis</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card dashboard-panel">
        <div class="card-body">
            <div class="dashboard-section-title">Fundamental Analyses</div>

            <table id="fundamental-analyses-table" class="table table-striped dashboard-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Symbol</th>
                        <th>Timeframe Scope</th>
                        <th>Fundamental Bias</th>
                        <th>News Risk Level</th>
                        <th>Sentiment Bias</th>
                        <th>Avoid Trade</th>
                        <th>Confidence</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyses as $analysis)
                        <tr>
                            <td>{{ $analysis->id }}</td>
                            <td>{{ $analysis->symbol }}</td>
                            <td>{{ $analysis->timeframe_scope ?: 'Global' }}</td>
                            <td>{{ $analysis->fundamental_bias ?: '-' }}</td>
                            <td>{{ $analysis->news_risk_level ?: '-' }}</td>
                            <td>{{ $analysis->sentiment_bias ?: '-' }}</td>
                            <td>{{ $analysis->avoid_trade ? 'Yes' : 'No' }}</td>
                            <td>{{ $analysis->confidence ?: '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $statusBadge[$analysis->status] ?? 'secondary' }}">
                                    {{ $statusLabels[$analysis->status] ?? $analysis->status }}
                                </span>
                            </td>
                            <td>{{ $analysis->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
    $(document).ready(function() {
        $('#fundamental-analyses-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: -1 }
            ]
        });
    });
</script>
@endpush

@endsection