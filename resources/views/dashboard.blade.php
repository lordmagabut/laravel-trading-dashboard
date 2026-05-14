@extends('layout.master')

@push('style')
<style>
    .control-hero,
    .control-panel {
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .control-panel {
        background: #fff;
    }

    .metric-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        height: 100%;
    }

    .metric-label {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: #64748b;
        font-weight: 700;
    }

    .metric-value {
        margin-top: .65rem;
        margin-bottom: .35rem;
        font-size: 2rem;
        line-height: 1.1;
        font-weight: 700;
        color: #0f172a;
    }

    .metric-note {
        color: #64748b;
        font-size: .85rem;
        margin-bottom: 0;
    }

    .tone-bullish { border-color: #bbf7d0; background: #f4fbf6; }
    .tone-bullish .metric-value { color: #15803d; }
    .tone-bearish { border-color: #fecaca; background: #fff5f5; }
    .tone-bearish .metric-value { color: #dc2626; }
    .tone-neutral { border-color: #f6d365; background: #fffdf2; }
    .tone-neutral .metric-value { color: #b7791f; }
    .tone-info { border-color: #bfdbfe; background: #f8fbff; }
    .tone-info .metric-value { color: #2563eb; }

    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 1rem;
    }

    .mini-stat {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 1rem;
        background: #fff;
        height: 100%;
    }

    .mini-stat h3 {
        margin-bottom: .25rem;
        font-size: 1.6rem;
        font-weight: 700;
    }

    .mini-stat p {
        margin-bottom: 0;
        color: #64748b;
        font-size: .85rem;
    }

    .dashboard-table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    #dashboardMarketChart {
        width: 100%;
        height: 420px;
    }

    .dashboard-list-item + .dashboard-list-item {
        border-top: 1px solid #e5e7eb;
    }
</style>
@endpush

@section('content')
@php
    $marketTone = 'tone-info';
    $statusText = $smcSummary['preferred_action'] ?? $summary['preferred_action'] ?? 'NO_TRADE';
    $execBias = $smcSummary['execution_bias'] ?? $summary['execution_bias'] ?? '-';
    $htfBias = $smcSummary['higher_timeframe_bias'] ?? $summary['higher_timeframe_bias'] ?? '-';
    $smcStructure = $smcSummary['execution_structure'] ?? '-';
    $smcLastEvent = $smcSummary['execution_last_event'] ?? '-';

    if (str_contains(strtolower((string) $statusText), 'buy') || str_contains(strtolower((string) $execBias), 'bullish')) {
        $marketTone = 'tone-bullish';
    } elseif (str_contains(strtolower((string) $statusText), 'sell') || str_contains(strtolower((string) $execBias), 'bearish')) {
        $marketTone = 'tone-bearish';
    } elseif (str_contains(strtolower((string) $statusText), 'no_trade') || str_contains(strtolower((string) $statusText), 'neutral')) {
        $marketTone = 'tone-neutral';
    }

    $signalBadge = [
        'PENDING' => 'warning',
        'APPROVED' => 'success',
        'REJECTED' => 'danger',
        'CANCELLED' => 'secondary',
        'EXECUTED' => 'success',
        'FAILED' => 'danger',
    ];
@endphp

<div class="page-content">
    <div class="control-hero p-4 mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h4 class="mb-1">Trading Control Room</h4>
                <p class="text-muted mb-0">Ringkasan kondisi market, antrian analisa, dan status signal dalam satu layar kerja.</p>
            </div>
            <div class="text-md-end">
                <div class="metric-label">Feed terakhir</div>
                <div class="fw-semibold text-dark" id="lastFeedTimeDisplay" data-utc="{{ $lastFeedTime ?: '' }}">
                    {{ $lastFeedTime ?: '-' }}
                </div>
                <small class="text-muted" id="lastFeedTimeZone"></small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 grid-margin stretch-card">
            <div class="card control-panel">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                        <div>
                            <div class="section-title mb-1">Market Overview</div>
                            <p class="text-muted mb-0">{{ $focusSymbol ?: '-' }} · {{ $focusTimeframe ?: '-' }}</p>
                        </div>
                        <div class="text-md-end">
                            <div class="metric-label">Current Price</div>
                            <div class="fw-bold fs-4 text-dark">{{ $lastCandle ? number_format((float) $lastCandle->close, 3) : '-' }}</div>
                            <small class="{{ ($priceChange ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                @if($priceChange !== null)
                                    {{ $priceChange >= 0 ? '+' : '' }}{{ number_format($priceChange, 3) }}
                                    ({{ $priceChangePercent >= 0 ? '+' : '' }}{{ number_format($priceChangePercent, 2) }}%)
                                @else
                                    -
                                @endif
                            </small>
                        </div>
                    </div>

                    <div id="dashboardMarketChart"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 grid-margin stretch-card">
            <div class="card metric-card {{ $marketTone }}">
                <div class="card-body">
                    <div class="metric-label">Market Condition</div>
                    <div class="metric-value">{{ str_replace('_', ' ', $statusText) }}</div>
                    <p class="metric-note">
                        {{ $focusSymbol ?: '-' }} {{ $focusTimeframe ?: '-' }} sedang dibaca dengan konteks classic + SMC.
                    </p>

                    <div class="row g-3 mt-3">
                        <div class="col-6">
                            <div class="mini-stat">
                                <h3>{{ $htfBias ?: '-' }}</h3>
                                <p>HTF Bias</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat">
                                <h3>{{ $execBias ?: '-' }}</h3>
                                <p>Execution Bias</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat">
                                <h3>{{ $smcStructure ?: '-' }}</h3>
                                <p>SMC Structure</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mini-stat">
                                <h3>{{ $smcLastEvent ?: '-' }}</h3>
                                <p>Last Event</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12 grid-margin stretch-card">
            <div class="card control-panel">
                <div class="card-body">
                    <div class="section-title">Workflow Summary</div>
                    <div class="row g-3">
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['pairs_enabled'] }}</h3>
                                <p>Enabled Pairs</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['pairs_auto'] }}</h3>
                                <p>Auto Generate</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['analyses_generated'] }}</h3>
                                <p>Generated</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['analyses_sent'] }}</h3>
                                <p>Sent to Agent</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['analyses_completed'] }}</h3>
                                <p>Completed</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['signals_pending'] }}</h3>
                                <p>Pending Signals</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['signals_approved'] }}</h3>
                                <p>Approved</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <div class="mini-stat">
                                <h3>{{ $workflowSummary['signals_executed'] }}</h3>
                                <p>Executed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 grid-margin stretch-card">
            <div class="card control-panel">
                <div class="card-body">
                    <div class="section-title">Recent Technical Analyses</div>
                    <div class="table-responsive">
                        <table class="table table-hover dashboard-table mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Pair</th>
                                    <th>Bias</th>
                                    <th>Status</th>
                                    <th>Decision</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentAnalyses as $analysis)
                                    <tr>
                                        <td><small>{{ optional($analysis->created_at)->format('Y-m-d H:i') }}</small></td>
                                        <td><strong>{{ $analysis->symbol }}</strong> <small class="text-muted">{{ $analysis->execution_timeframe }}</small></td>
                                        <td>{{ $analysis->execution_bias ?: '-' }}</td>
                                        <td><span class="badge bg-secondary">{{ $analysis->status }}</span></td>
                                        <td>{{ $analysis->decision ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada technical analysis.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 grid-margin stretch-card">
            <div class="card control-panel">
                <div class="card-body">
                    <div class="section-title">Recent Trade Signals</div>
                    @forelse($recentSignals as $signal)
                        <div class="dashboard-list-item py-3 d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold">{{ $signal->symbol }} <span class="text-muted">{{ $signal->timeframe }}</span></div>
                                <div class="small text-muted">{{ optional($signal->created_at)->format('Y-m-d H:i') }}</div>
                                <div class="small mt-1">{{ \Illuminate\Support\Str::limit($signal->reason_summary, 90) }}</div>
                            </div>
                            <div class="text-end">
                                <div>
                                    @if($signal->decision === 'BUY')
                                        <span class="badge bg-success">BUY</span>
                                    @elseif($signal->decision === 'SELL')
                                        <span class="badge bg-danger">SELL</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $signal->decision }}</span>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-{{ $signalBadge[$signal->status] ?? 'secondary' }}">{{ $signal->status }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">Belum ada trade signal.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
<script src="https://unpkg.com/lightweight-charts@4.2.3/dist/lightweight-charts.standalone.production.js"></script>
@endpush

@push('custom-scripts')
<script>
    const dashboardCandlesUrl = @json(route('market.chart.candles', [], false));
    const dashboardSymbol = @json($focusSymbol);
    const dashboardTimeframe = @json($focusTimeframe);
    const dashboardLimit = @json($candlesLimit);

    let dashboardChart;
    let dashboardSeries;

    function formatUtcToUserTime(utcTimeString) {
        if (!utcTimeString) {
            return { text: '-', zone: '' };
        }

        const date = parseUtcDateTime(utcTimeString);
        if (!date) {
            return { text: utcTimeString, zone: '' };
        }

        const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const formatter = new Intl.DateTimeFormat('id-ID', {
            timeZone: userTimeZone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        return {
            text: formatter.format(date),
            zone: userTimeZone
        };
    }

    function updateLastFeedTimeDisplay() {
        const display = document.getElementById('lastFeedTimeDisplay');
        const zone = document.getElementById('lastFeedTimeZone');

        if (!display) return;

        const utcTime = display.dataset.utc || '';
        const formatted = formatUtcToUserTime(utcTime);

        display.textContent = formatted.text;

        if (zone) {
            zone.textContent = formatted.zone ? `Timezone: ${formatted.zone}` : '';
        }
    }

    function initDashboardChart() {
        const element = document.getElementById('dashboardMarketChart');
        if (!element) return;

        dashboardChart = LightweightCharts.createChart(element, {
            width: element.clientWidth,
            height: 420,
            layout: {
                background: { color: '#ffffff' },
                textColor: '#334155'
            },
            grid: {
                vertLines: { color: '#f1f5f9' },
                horzLines: { color: '#f1f5f9' }
            },
            rightPriceScale: { borderColor: '#e2e8f0' },
            timeScale: {
                borderColor: '#e2e8f0',
                timeVisible: true,
                secondsVisible: false
            }
        });

        dashboardSeries = dashboardChart.addCandlestickSeries({
            upColor: '#10b981',
            downColor: '#ef4444',
            borderUpColor: '#10b981',
            borderDownColor: '#ef4444',
            wickUpColor: '#10b981',
            wickDownColor: '#ef4444'
        });
    }

    function parseUtcDateTime(utcTimeString) {
        const match = /^([0-9]{4})-([0-9]{2})-([0-9]{2})(?:[ T]([0-9]{2}):([0-9]{2}):([0-9]{2}))?$/.exec(utcTimeString);
        if (!match) {
            const fallback = new Date(utcTimeString + 'Z');
            return Number.isNaN(fallback.getTime()) ? null : fallback;
        }

        const [, year, month, day, hour = '0', minute = '0', second = '0'] = match;
        return new Date(Date.UTC(Number(year), Number(month) - 1, Number(day), Number(hour), Number(minute), Number(second)));
    }

    async function loadDashboardChart() {
        if (!dashboardSymbol || !dashboardTimeframe) return;

        const url = `${dashboardCandlesUrl}?symbol=${encodeURIComponent(dashboardSymbol)}&timeframe=${encodeURIComponent(dashboardTimeframe)}&limit=${encodeURIComponent(dashboardLimit)}`;
        const response = await fetch(url);
        const result = await response.json();
        const candles = (result.data ?? []).map((candle) => {
            const date = parseUtcDateTime(candle.tick_time);
            return {
                time: date ? Math.round(date.getTime() / 1000) : candle.time,
                open: Number(candle.open),
                high: Number(candle.high),
                low: Number(candle.low),
                close: Number(candle.close),
            };
        });

        dashboardSeries.setData(candles);
        dashboardChart.timeScale().fitContent();
    }

    window.addEventListener('resize', function () {
        const element = document.getElementById('dashboardMarketChart');
        if (dashboardChart && element) {
            dashboardChart.applyOptions({ width: element.clientWidth });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        updateLastFeedTimeDisplay();
        initDashboardChart();
        loadDashboardChart();
    });
</script>
@endpush
