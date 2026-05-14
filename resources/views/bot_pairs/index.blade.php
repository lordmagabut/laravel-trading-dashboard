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

    .tone-total { background: #f8fbff; border-color: #bfdbfe; }
    .tone-total::before, .tone-total .summary-value { background: #2563eb; color: #2563eb; }
    .tone-enabled { background: #f4fbf6; border-color: #bbf7d0; }
    .tone-enabled::before, .tone-enabled .summary-value { background: #15803d; color: #15803d; }
    .tone-auto { background: #f1fbf4; border-color: #86efac; }
    .tone-auto::before, .tone-auto .summary-value { background: #166534; color: #166534; }
    .tone-disabled { background: #fff5f5; border-color: #fecaca; }
    .tone-disabled::before, .tone-disabled .summary-value { background: #dc2626; color: #dc2626; }

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

<div class="page-content">

    <div class="dashboard-hero d-flex justify-content-between align-items-center flex-wrap grid-margin">
        <div>
            <h4 class="mb-1">Bot Pair Settings</h4>
            <p class="text-muted mb-0">
                Atur pair, entry timeframe, dan auto generate technical analysis.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-3 stretch-card grid-margin">
            <div class="card dashboard-summary-card tone-total">
                <div class="card-body">
                    <div class="summary-kicker">Total Pair</div>
                    <h3 class="summary-value">{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card dashboard-summary-card tone-enabled">
                <div class="card-body">
                    <div class="summary-kicker">Enabled</div>
                    <h3 class="summary-value">{{ $summary['enabled'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card dashboard-summary-card tone-auto">
                <div class="card-body">
                    <div class="summary-kicker">Auto Generate</div>
                    <h3 class="summary-value">{{ $summary['auto_generate'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card dashboard-summary-card tone-disabled">
                <div class="card-body">
                    <div class="summary-kicker">Disabled</div>
                    <h3 class="summary-value">{{ $summary['disabled'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-panel mb-4">
        <div class="card-body">
            <div class="dashboard-section-title">Tambah Bot Pair</div>

            <form method="POST" action="{{ route('bot-pairs.store') }}" class="row g-3 align-items-end">
                @csrf

                <div class="col-md-2">
                    <label class="form-label">Symbol</label>
                    <input type="text"
                           name="symbol"
                           class="form-control"
                           value="{{ old('symbol', 'XAUUSD') }}"
                           placeholder="XAUUSD"
                           required>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Entry TF</label>
                    <select name="entry_timeframe" class="form-select" required>
                        @foreach(['M1', 'M5', 'M15', 'M30', 'H1', 'H4', 'D1'] as $tf)
                            <option value="{{ $tf }}" @selected(old('entry_timeframe', 'M15') === $tf)>
                                {{ $tf }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Higher TF</label>
                    <input type="text"
                           name="higher_timeframes"
                           class="form-control"
                           value="{{ old('higher_timeframes', 'D1,H4,H1') }}"
                           placeholder="D1,H4,H1">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Agent Mode</label>
                    <select name="agent_risk_mode" class="form-select" required>
                        @foreach(\App\Models\TradingBotPair::AGENT_RISK_MODES as $mode)
                            <option value="{{ $mode }}" @selected(old('agent_risk_mode', 'balanced') === $mode)>
                                {{ ucfirst($mode) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label d-block">Options</label>

                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="checkbox"
                               name="enabled"
                               value="1"
                               checked>
                        <label class="form-check-label">Enabled</label>
                    </div>

                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="checkbox"
                               name="auto_generate"
                               value="1"
                               checked>
                        <label class="form-check-label">Auto</label>
                    </div>
                </div>

                <div class="col-md-1">
                    <label class="form-label">Notes</label>
                    <input type="text"
                           name="notes"
                           class="form-control"
                           value="{{ old('notes') }}"
                           placeholder="optional">
                </div>

                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        Add
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card dashboard-panel">
        <div class="card-body">

            <div class="dashboard-section-title">Bot Pair List</div>

            <div class="table-responsive">
                <table id="botPairsTable" class="table table-hover table-bordered align-middle nowrap dashboard-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Entry TF</th>
                            <th>Higher TF</th>
                            <th>Agent Mode</th>
                            <th>Enabled</th>
                            <th>Auto Generate</th>
                            <th>Scheduler Status</th>
                            <th>Last Checked</th>
                            <th>Last Generated Candle</th>
                            <th>Latest Analysis</th>
                            <th style="min-width: 260px;">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($pairs as $pair)
                            @php
                                $key = $pair->symbol . '|' . $pair->entry_timeframe;
                                $latestAnalysis = $latestAnalyses[$key] ?? null;

                                $schedulerStatus = 'Not Checked';
                                $schedulerBadge = 'secondary';

                                if (! $pair->enabled) {
                                    $schedulerStatus = 'Disabled';
                                    $schedulerBadge = 'dark';
                                } elseif (! $pair->auto_generate) {
                                    $schedulerStatus = 'Manual Only';
                                    $schedulerBadge = 'warning';
                                } elseif ($pair->last_checked_at && $pair->last_checked_at->diffInMinutes(now()) <= 2) {
                                    $schedulerStatus = 'Active';
                                    $schedulerBadge = 'success';
                                } elseif ($pair->last_checked_at) {
                                    $schedulerStatus = 'Stale';
                                    $schedulerBadge = 'danger';
                                }
                            @endphp

                            <tr>
                                <td>
                                    <strong>{{ $pair->symbol }}</strong>
                                </td>

                                <td>
                                    <span class="badge bg-primary">{{ $pair->entry_timeframe }}</span>
                                </td>

                                <td>
                                    @forelse(($pair->higher_timeframes ?? []) as $tf)
                                        <span class="badge bg-light text-dark">{{ $tf }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>

                                <td>
                                    @php
                                        $modeBadge = match($pair->agent_risk_mode) {
                                            'conservative' => 'secondary',
                                            'aggressive' => 'danger',
                                            default => 'info',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $modeBadge }}">
                                        {{ ucfirst($pair->agent_risk_mode ?? 'balanced') }}
                                    </span>
                                </td>

                                <td>
                                    @if($pair->enabled)
                                        <span class="badge bg-success">Enabled</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </td>

                                <td>
                                    @if($pair->auto_generate)
                                        <span class="badge bg-success">Auto</span>
                                    @else
                                        <span class="badge bg-warning">Manual</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-{{ $schedulerBadge }}">
                                        {{ $schedulerStatus }}
                                    </span>
                                </td>

                                <td>
                                    <small>
                                        {{ $pair->last_checked_at ? $pair->last_checked_at->format('Y-m-d H:i:s') : '-' }}
                                    </small>
                                </td>

                                <td>
                                    <small>
                                        {{ $pair->last_generated_candle_time ? $pair->last_generated_candle_time->format('Y-m-d H:i:s') : '-' }}
                                    </small>
                                </td>

                                <td>
                                    @if($latestAnalysis)
                                        <div>
                                            <span class="badge bg-info">{{ $latestAnalysis->status }}</span>
                                        </div>
                                        <small class="text-muted">
                                            #{{ $latestAnalysis->id }}
                                            {{ $latestAnalysis->context_candle_time ? $latestAnalysis->context_candle_time->format('Y-m-d H:i') : '' }}
                                        </small>
                                    @else
                                        <span class="text-muted">No analysis</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-1">

                                        <a href="{{ route('bot-pairs.edit', $pair) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            Edit
                                        </a>

                                        <form method="POST"
                                              action="{{ route('bot-pairs.toggle-enabled', $pair) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm {{ $pair->enabled ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                {{ $pair->enabled ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('bot-pairs.toggle-auto-generate', $pair) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm {{ $pair->auto_generate ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                {{ $pair->auto_generate ? 'Manual' : 'Auto' }}
                                            </button>
                                        </form>

                                        <form method="POST"
                                              action="{{ route('bot-pairs.destroy', $pair) }}"
                                              onsubmit="return confirm('Hapus bot pair ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Belum ada bot pair setting.
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
        $('#botPairsTable').DataTable({
            responsive: true,
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            order: [[7, 'desc']],
            columnDefs: [
                { orderable: false, targets: [10] }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
            }
        });
    });
</script>
@endpush
