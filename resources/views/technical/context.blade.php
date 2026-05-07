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

  function formatNumber(value) {
    if (value === null || value === undefined) return '-';
    return Number(value).toFixed(3);
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