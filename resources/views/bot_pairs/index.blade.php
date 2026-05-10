@extends('layout.master')

@section('content')

<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
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
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Pair</h6>
                    <h3 class="mb-0">{{ $summary['total'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Enabled</h6>
                    <h3 class="mb-0">{{ $summary['enabled'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Auto Generate</h6>
                    <h3 class="mb-0">{{ $summary['auto_generate'] }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Disabled</h6>
                    <h3 class="mb-0">{{ $summary['disabled'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Tambah Bot Pair</h6>

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

                <div class="col-md-3">
                    <label class="form-label">Higher TF</label>
                    <input type="text"
                           name="higher_timeframes"
                           class="form-control"
                           value="{{ old('higher_timeframes', 'D1,H4,H1') }}"
                           placeholder="D1,H4,H1">
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

                <div class="col-md-2">
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

    <div class="card">
        <div class="card-body">

            <h6 class="card-title">Bot Pair List</h6>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Entry TF</th>
                            <th>Higher TF</th>
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
                                <td colspan="10" class="text-center text-muted py-4">
                                    Belum ada bot pair setting.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $pairs->links() }}
            </div>

        </div>
    </div>

</div>

@endsection