@extends('layout.master')

@push('style')
<style>
  :root {
    --context-bullish-bg: #ecfdf3;
    --context-bullish-border: #bbf7d0;
    --context-bullish-text: #15803d;
    --context-bearish-bg: #fef2f2;
    --context-bearish-border: #fecaca;
    --context-bearish-text: #b91c1c;
    --context-neutral-bg: #fefce8;
    --context-neutral-border: #fde68a;
    --context-neutral-text: #a16207;
    --context-panel-border: #e5e7eb;
    --context-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
  }

  .context-hero {
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: var(--context-shadow);
  }

  .context-summary-card {
    border: 1px solid var(--context-panel-border);
    border-radius: 14px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    overflow: hidden;
    background: #fff;
  }

  .context-summary-card .card-body {
    position: relative;
    min-height: 126px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .context-summary-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    width: 6px;
    background: #cbd5e1;
  }

  .context-label {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-weight: 700;
    color: #64748b;
    margin-bottom: .75rem;
  }

  .context-value {
    margin-bottom: .35rem;
    font-size: 1.9rem;
    line-height: 1.15;
    font-weight: 700;
    word-break: break-word;
    color: #0f172a;
  }

  .context-note {
    font-size: .82rem;
    color: #64748b;
    margin-bottom: 0;
  }

  .tone-bullish {
    background: #f4fbf6;
    border-color: var(--context-bullish-border);
  }

  .tone-bullish::before {
    background: var(--context-bullish-text);
  }

  .tone-bullish .context-value {
    color: var(--context-bullish-text);
  }

  .tone-bearish {
    background: #fff5f5;
    border-color: var(--context-bearish-border);
  }

  .tone-bearish::before {
    background: var(--context-bearish-text);
  }

  .tone-bearish .context-value {
    color: var(--context-bearish-text);
  }

  .tone-neutral {
    background: #fffdf2;
    border-color: var(--context-neutral-border);
  }

  .tone-neutral::before {
    background: var(--context-neutral-text);
  }

  .tone-neutral .context-value {
    color: var(--context-neutral-text);
  }

  .tone-default {
    background: #ffffff;
  }

  .context-panel {
    border: 1px solid var(--context-panel-border);
    border-radius: 16px;
    box-shadow: var(--context-shadow);
  }

  .context-status {
    border-radius: 12px;
    background: #f8fafc;
    border-color: #dbeafe !important;
  }

  .context-table thead th {
    background: #f8fafc;
    color: #334155;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
  }

  .context-table tbody td {
    vertical-align: middle;
  }

  .zone-panel {
    border: 1px solid var(--context-panel-border);
    border-radius: 14px;
    background: #f8fafc;
  }

  #rawJson {
    background: #0f172a !important;
    color: #e2e8f0;
    border-color: #1e293b !important;
  }
</style>
@endpush

@section('content')
<div class="context-hero d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Technical Context</h4>
    <p class="text-muted mb-0">Ringkasan bias teknikal dari data OHLC MariaDB untuk OpenClaw Technical Agent.</p>
  </div>

  <div class="d-flex align-items-center flex-wrap text-nowrap">
    <button type="button" class="btn btn-primary btn-icon-text mb-2 mb-md-0" onclick="loadTechnicalContext()">
      <i class="btn-icon-prepend" data-feather="refresh-cw"></i>
      Generate Context
    </button>
  </div>
</div>

<div class="row">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-default" id="summarySymbolCard">
      <div class="card-body">
        <div class="context-label">Symbol</div>
        <h3 class="context-value" id="summarySymbol">-</h3>
        <p class="context-note">Pair aktif</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-neutral" id="summaryHtfBiasCard">
      <div class="card-body">
        <div class="context-label">HTF Bias</div>
        <h3 class="context-value" id="summaryHtfBias">-</h3>
        <p class="context-note">D1/H4/H1 mayoritas</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-neutral" id="summaryExecBiasCard">
      <div class="card-body">
        <div class="context-label">Execution Bias</div>
        <h3 class="context-value" id="summaryExecBias">-</h3>
        <p class="context-note">Bias TF entry</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-neutral" id="summaryActionCard">
      <div class="card-body">
        <div class="context-label">Preferred Action</div>
        <h3 class="context-value" id="summaryAction">-</h3>
        <p class="context-note">Arahan awal, bukan entry langsung</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-neutral" id="smcSummaryHtfBiasCard">
      <div class="card-body">
        <div class="context-label">SMC HTF Bias</div>
        <h3 class="context-value" id="smcSummaryHtfBias">-</h3>
        <p class="context-note">D1/H4/H1 berdasarkan SMC</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-neutral" id="smcSummaryExecBiasCard">
      <div class="card-body">
        <div class="context-label">SMC Exec Bias</div>
        <h3 class="context-value" id="smcSummaryExecBias">-</h3>
        <p class="context-note">Bias SMC TF entry</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-neutral" id="smcSummaryStructureCard">
      <div class="card-body">
        <div class="context-label">SMC Structure</div>
        <h3 class="context-value" id="smcSummaryStructure">-</h3>
        <p class="context-note">Struktur TF entry</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card context-summary-card tone-neutral" id="smcSummaryLastEventCard">
      <div class="card-body">
        <div class="context-label">SMC Last Event</div>
        <h3 class="context-value" id="smcSummaryLastEvent">-</h3>
        <p class="context-note">BOS / CHoCH terakhir</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card context-panel">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline mb-4">
          <h6 class="card-title mb-0">Context Generator</h6>
        </div>

        <div class="row align-items-end mb-4">
          <div class="col-md-4 mb-3 mb-md-0">
            <label class="form-label">Symbol</label>
            <select id="symbol" class="form-select">
              @forelse ($symbols as $symbol)
                <option value="{{ $symbol }}">{{ $symbol }}</option>
              @empty
                <option value="">Belum ada data</option>
              @endforelse
            </select>
          </div>

          <div class="col-md-4 mb-3 mb-md-0">
            <label class="form-label">Execution Timeframe</label>
            <select id="executionTimeframe" class="form-select">
              @foreach ($timeframes as $timeframe)
                <option value="{{ $timeframe }}" {{ $timeframe === 'M15' ? 'selected' : '' }}>
                  {{ $timeframe }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-4">
            <button type="button" class="btn btn-primary btn-icon-text w-100" onclick="loadTechnicalContext()">
              <i class="btn-icon-prepend" data-feather="cpu"></i>
              Analyze
            </button>
          </div>
        </div>

        <div class="alert alert-light border d-flex align-items-center mb-4 context-status" role="alert">
          <i data-feather="info" class="icon-md text-primary me-2"></i>
          <div>
            <strong>Status:</strong>
            <span id="statusText">Menunggu generate context...</span>
          </div>
        </div>

        <div class="table-responsive mb-4">
          <table class="table table-hover mb-0 context-table">
            <thead>
              <tr>
                <th>TF</th>
                <th>Bias</th>
                <th>Score</th>
                <th>Last Close</th>
                <th>Structure</th>
                <th>BOS</th>
                <th>ATR14</th>
                <th>Last Candle</th>
              </tr>
            </thead>
            <tbody id="biasTableBody">
              <tr>
                <td colspan="8" class="text-muted text-center">Belum ada data.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <h6 class="card-title mb-3">SMC Context</h6>
        <div class="table-responsive mb-4">
          <table class="table table-hover mb-0 context-table">
            <thead>
              <tr>
                <th>TF</th>
                <th>Bias</th>
                <th>Score</th>
                <th>Structure</th>
                <th>Last Event</th>
                <th>Premium/Discount</th>
                <th>Demand</th>
                <th>Supply</th>
                <th>Sweeps</th>
              </tr>
            </thead>
            <tbody id="smcTableBody">
              <tr>
                <td colspan="9" class="text-muted text-center">Belum ada data SMC.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="row mb-4">
          <div class="col-md-6 mb-3 mb-md-0">
            <div class="zone-panel p-3 h-100">
              <h6 class="mb-3">SMC Demand Zones</h6>
              <div id="smcDemandZones" class="small text-muted">Belum ada data.</div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="zone-panel p-3 h-100">
              <h6 class="mb-3">SMC Supply Zones</h6>
              <div id="smcSupplyZones" class="small text-muted">Belum ada data.</div>
            </div>
          </div>
        </div>

        <h6 class="card-title mb-3">Raw JSON untuk OpenClaw</h6>
        <pre id="rawJson" class="border rounded p-3 mb-0" style="max-height: 480px; overflow:auto;">{}</pre>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  const technicalContextUrl = @json(route('technical.context.api', [], false));

  function toneClass(value) {
    const normalized = String(value ?? '').toLowerCase();

    if (normalized.includes('bullish') || normalized.includes('buy')) return 'tone-bullish';
    if (normalized.includes('bearish') || normalized.includes('sell')) return 'tone-bearish';
    if (normalized.includes('neutral') || normalized.includes('no_trade') || normalized.includes('no trade') || normalized.includes('ranging')) return 'tone-neutral';
    return 'tone-default';
  }

  function applyCardTone(cardId, value) {
    const card = document.getElementById(cardId);
    if (!card) return;

    card.classList.remove('tone-bullish', 'tone-bearish', 'tone-neutral', 'tone-default');
    card.classList.add(toneClass(value));
  }

  function badgeClass(bias) {
    if (bias === 'bullish') return 'badge bg-success';
    if (bias === 'bearish') return 'badge bg-danger';
    if (bias === 'neutral') return 'badge bg-warning text-dark';
    return 'badge bg-secondary';
  }

  function zoneBadgeClass(status) {
    if (status === 'fresh') return 'badge bg-success';
    if (status === 'mitigated') return 'badge bg-warning text-dark';
    if (status === 'invalidated') return 'badge bg-danger';
    return 'badge bg-secondary';
  }

  function formatNumber(value) {
    if (value === null || value === undefined) return '-';
    return Number(value).toFixed(3);
  }

  function escapeHtml(value) {
    return String(value ?? '-')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function renderZoneList(zones) {
    if (!zones || zones.length === 0) {
      return '<span class="text-muted">Tidak ada zone valid.</span>';
    }

    return zones.slice(-5).reverse().map((zone) => `
      <div class="d-flex justify-content-between align-items-start border-bottom py-2">
        <div>
          <div><strong>${formatNumber(zone.low)} - ${formatNumber(zone.high)}</strong></div>
          <div class="text-muted">Created by ${escapeHtml(zone.created_by)} at ${escapeHtml(zone.break_time)}</div>
        </div>
        <span class="${zoneBadgeClass(zone.status)}">${escapeHtml(zone.status)}</span>
      </div>
    `).join('');
  }

  async function loadTechnicalContext() {
    const symbol = document.getElementById('symbol').value;
    const executionTimeframe = document.getElementById('executionTimeframe').value;
    const statusText = document.getElementById('statusText');

    if (!symbol) {
      statusText.textContent = 'Symbol belum tersedia.';
      return;
    }

    statusText.textContent = 'Menghitung technical context...';

    try {
      const url = `${technicalContextUrl}?symbol=${encodeURIComponent(symbol)}&execution_timeframe=${encodeURIComponent(executionTimeframe)}`;
      const response = await fetch(url);
      const result = await response.json();

      if (!response.ok) {
        console.error(result);
        statusText.textContent = 'Gagal mengambil technical context.';
        return;
      }

      document.getElementById('summarySymbol').textContent = result.symbol ?? '-';
      document.getElementById('summaryHtfBias').textContent = result.summary?.higher_timeframe_bias ?? '-';
      document.getElementById('summaryExecBias').textContent = result.summary?.execution_bias ?? '-';
      document.getElementById('summaryAction').textContent = result.summary?.preferred_action ?? '-';
      document.getElementById('smcSummaryHtfBias').textContent = result.smc_summary?.higher_timeframe_bias ?? '-';
      document.getElementById('smcSummaryExecBias').textContent = result.smc_summary?.execution_bias ?? '-';
      document.getElementById('smcSummaryStructure').textContent = result.smc_summary?.execution_structure ?? '-';
      document.getElementById('smcSummaryLastEvent').textContent = result.smc_summary?.execution_last_event ?? '-';

      applyCardTone('summaryHtfBiasCard', result.summary?.higher_timeframe_bias ?? '-');
      applyCardTone('summaryExecBiasCard', result.summary?.execution_bias ?? '-');
      applyCardTone('summaryActionCard', result.summary?.preferred_action ?? '-');
      applyCardTone('smcSummaryHtfBiasCard', result.smc_summary?.higher_timeframe_bias ?? '-');
      applyCardTone('smcSummaryExecBiasCard', result.smc_summary?.execution_bias ?? '-');
      applyCardTone('smcSummaryStructureCard', result.smc_summary?.execution_structure ?? '-');
      applyCardTone('smcSummaryLastEventCard', result.smc_summary?.execution_last_event ?? '-');

      const bias = result.bias ?? {};
      const rows = Object.keys(bias).map((tf) => {
        const item = bias[tf];
        const structure = item.structure?.structure ?? '-';
        const bos = item.structure?.bos ?? '-';

        return `
          <tr>
            <td><strong>${tf}</strong></td>
            <td><span class="${badgeClass(item.bias)}">${item.bias}</span></td>
            <td>${item.score ?? 0}</td>
            <td>${formatNumber(item.last_close)}</td>
            <td>${structure}</td>
            <td>${bos}</td>
            <td>${formatNumber(item.atr14)}</td>
            <td>${item.last_candle_time ?? '-'}</td>
          </tr>
        `;
      }).join('');

      document.getElementById('biasTableBody').innerHTML = rows || `
        <tr>
          <td colspan="8" class="text-muted text-center">Data kosong.</td>
        </tr>
      `;

      const smc = result.smc ?? {};
      const smcRows = Object.keys(smc).map((tf) => {
        const item = smc[tf];
        const lastEvent = item.last_event?.type ?? '-';
        const pdArea = item.premium_discount?.current_area ?? '-';
        const demandCount = item.zones?.demand?.filter((zone) => !zone.invalidated).length ?? 0;
        const supplyCount = item.zones?.supply?.filter((zone) => !zone.invalidated).length ?? 0;
        const sweepCount = item.liquidity_sweeps?.length ?? 0;

        return `
          <tr>
            <td><strong>${escapeHtml(tf)}</strong></td>
            <td><span class="${badgeClass(item.bias)}">${escapeHtml(item.bias)}</span></td>
            <td>${item.score ?? 0}</td>
            <td>${escapeHtml(item.structure)}</td>
            <td>${escapeHtml(lastEvent)}</td>
            <td>${escapeHtml(pdArea)}</td>
            <td>${demandCount}</td>
            <td>${supplyCount}</td>
            <td>${sweepCount}</td>
          </tr>
        `;
      }).join('');

      document.getElementById('smcTableBody').innerHTML = smcRows || `
        <tr>
          <td colspan="9" class="text-muted text-center">Data SMC kosong.</td>
        </tr>
      `;

      const executionSmc = smc[executionTimeframe] ?? {};
      document.getElementById('smcDemandZones').innerHTML = renderZoneList(executionSmc.zones?.demand ?? []);
      document.getElementById('smcSupplyZones').innerHTML = renderZoneList(executionSmc.zones?.supply ?? []);

      document.getElementById('rawJson').textContent = JSON.stringify(result, null, 2);

      statusText.textContent = 'Technical context berhasil dibuat.';

      if (typeof feather !== 'undefined') {
        feather.replace();
      }

    } catch (error) {
      console.error(error);
      statusText.textContent = 'Error koneksi saat generate technical context.';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    loadTechnicalContext();
  });
</script>
@endpush
