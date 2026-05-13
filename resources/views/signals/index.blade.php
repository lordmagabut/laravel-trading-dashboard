@extends('layout.master')

@push('plugin-styles')
<link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet" />
@endpush

@section('content')

@php
    $statusBadge = [
        'PENDING' => 'warning',
        'APPROVED' => 'success',
        'REJECTED' => 'danger',
        'CANCELLED' => 'secondary',
        'SENT_TO_EXECUTOR' => 'primary',
        'EXECUTED' => 'success',
        'FAILED' => 'danger',
        'EXPIRED' => 'dark',
    ];
@endphp

<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">Signal Dashboard</h4>
            <p class="text-muted mb-0">
                Control Tower untuk approval signal BUY / SELL sebelum dikirim ke MT5 Executor.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row">
        @foreach(['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED', 'EXECUTED', 'FAILED'] as $status)
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
            <form method="GET" action="{{ route('signals.index') }}" class="row g-3 align-items-end">

                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED', 'SENT_TO_EXECUTOR', 'EXECUTED', 'FAILED', 'EXPIRED'] as $status)
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
                    <select name="timeframe" class="form-select">
                        <option value="">All Timeframe</option>
                        @foreach($timeframes as $timeframe)
                            <option value="{{ $timeframe }}" @selected(request('timeframe') === $timeframe)>
                                {{ $timeframe }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('signals.index') }}" class="btn btn-light">Reset</a>
                </div>

            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <h6 class="card-title">Trade Signals</h6>

            <div class="table-responsive">
                <table id="signalsTable" class="table table-hover table-bordered align-middle nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Symbol</th>
                            <th>TF</th>
                            <th>Decision</th>
                            <th>Entry</th>
                            <th>SL</th>
                            <th>TP</th>
                            <th>RR</th>
                            <th>Confidence</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th style="min-width: 260px;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($signals as $signal)
                            <tr>
                                <td>
                                    <small>
                                        {{ optional($signal->created_at)->format('Y-m-d H:i') }}
                                    </small>
                                </td>

                                <td>
                                    <strong>{{ $signal->symbol }}</strong>
                                </td>

                                <td>{{ $signal->timeframe }}</td>

                                <td>
                                    @if($signal->decision === 'BUY')
                                        <span class="badge bg-success">BUY</span>
                                    @elseif($signal->decision === 'SELL')
                                        <span class="badge bg-danger">SELL</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $signal->decision }}</span>
                                    @endif
                                </td>

                                <td>{{ $signal->entry_price }}</td>

                                <td>{{ $signal->stop_loss }}</td>

                                <td>
                                    <div>TP1: {{ $signal->take_profit_1 }}</div>
                                    <div>TP2: {{ $signal->take_profit_2 }}</div>
                                    <div>TP3: {{ $signal->take_profit_3 }}</div>
                                </td>

                                <td>
                                    {{ $signal->risk_reward }}
                                </td>

                                <td>
                                    {{ $signal->confidence }}%
                                </td>

                                <td>
                                    <span class="badge bg-{{ $statusBadge[$signal->status] ?? 'secondary' }}">
                                        {{ $signal->status }}
                                    </span>
                                </td>

                                <td style="max-width: 260px;">
                                    <small class="text-muted">
                                        {{ \Illuminate\Support\Str::limit($signal->reason_summary, 120) }}
                                    </small>
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-1">

                                        @if($signal->status === 'PENDING')
                                            <form method="POST"
                                                  action="{{ route('signals.approve', $signal) }}"
                                                  onsubmit="return confirm('Approve signal ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    Approve
                                                </button>
                                            </form>

                                            <form method="POST"
                                                  action="{{ route('signals.reject', $signal) }}"
                                                  onsubmit="return confirm('Reject signal ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Reject
                                                </button>
                                            </form>
                                        @endif

                                        @if(in_array($signal->status, ['PENDING', 'APPROVED'], true))
                                            <form method="POST"
                                                  action="{{ route('signals.cancel', $signal) }}"
                                                  onsubmit="return confirm('Cancel signal ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-secondary">
                                                    Cancel
                                                </button>
                                            </form>
                                        @endif

                                        @if($signal->status === 'APPROVED')
                                            <form method="POST"
                                                  action="{{ route('signals.sendToExecutor', $signal) }}"
                                                  onsubmit="return confirm('Kirim signal ini ke Executor?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    Send to Executor
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary" disabled>
                                                Send to Executor
                                            </button>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
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

@push('plugin-scripts')
<script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
@endpush

@push('custom-scripts')
<script>
    $(document).ready(function () {
        $('#signalsTable').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: [11] }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            }
        });
    });
</script>
@endpush
