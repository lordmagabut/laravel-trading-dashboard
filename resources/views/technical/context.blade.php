@extends('layout.master')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
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
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">Symbol</h6>
        <h3 class="mt-3 mb-2" id="summarySymbol">-</h3>
        <p class="text-muted tx-13 mb-0">Pair aktif</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">HTF Bias</h6>
        <h3 class="mt-3 mb-2" id="summaryHtfBias">-</h3>
        <p class="text-muted tx-13 mb-0">D1/H4/H1 mayoritas</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">Execution Bias</h6>
        <h3 class="mt-3 mb-2" id="summaryExecBias">-</h3>
        <p class="text-muted tx-13 mb-0">Bias TF entry</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">Preferred Action</h6>
        <h3 class="mt-3 mb-2" id="summaryAction">-</h3>
        <p class="text-muted tx-13 mb-0">Arahan awal, bukan entry langsung</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">SMC HTF Bias</h6>
        <h3 class="mt-3 mb-2" id="smcSummaryHtfBias">-</h3>
        <p class="text-muted tx-13 mb-0">D1/H4/H1 berdasarkan SMC</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">SMC Exec Bias</h6>
        <h3 class="mt-3 mb-2" id="smcSummaryExecBias">-</h3>
        <p class="text-muted tx-13 mb-0">Bias SMC TF entry</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">SMC Structure</h6>
        <h3 class="mt-3 mb-2" id="smcSummaryStructure">-</h3>
        <p class="text-muted tx-13 mb-0">Struktur TF entry</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-0">SMC Last Event</h6>
        <h3 class="mt-3 mb-2" id="smcSummaryLastEvent">-</h3>
        <p class="text-muted tx-13 mb-0">BOS / CHoCH terakhir</p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
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

        <div class="alert alert-light border d-flex align-items-center mb-4" role="alert">
          <i data-feather="info" class="icon-md text-primary me-2"></i>
          <div>
            <strong>Status:</strong>
            <span id="statusText">Menunggu generate context...</span>
          </div>
        </div>

        <div class="table-responsive mb-4">
          <table class="table table-hover mb-0">
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
          <table class="table table-hover mb-0">
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
            <div class="border rounded p-3 h-100">
              <h6 class="mb-3">SMC Demand Zones</h6>
              <div id="smcDemandZones" class="small text-muted">Belum ada data.</div>
            </div>
          </div>

          <div class="col-md-6">
            <div class="border rounded p-3 h-100">
              <h6 class="mb-3">SMC Supply Zones</h6>
              <div id="smcSupplyZones" class="small text-muted">Belum ada data.</div>
            </div>
          </div>
        </div>

        <h6 class="card-title mb-3">Raw JSON untuk OpenClaw</h6>
        <pre id="rawJson" class="bg-light border rounded p-3 mb-0" style="max-height: 480px; overflow:auto;">{}</pre>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
  const technicalContextUrl = @json(route('technical.context.api'));

  function badgeClass(bias) {
    if (bias === 'bullish') return 'badge bg-success';
    if (bias === 'bearish') return 'badge bg-danger';
    if (bias === 'neutral') return 'badge bg-warning';
    return 'badge bg-secondary';
  }

  function zoneBadgeClass(status) {
    if (status === 'fresh') return 'badge bg-success';
    if (status === 'mitigated') return 'badge bg-warning';
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
