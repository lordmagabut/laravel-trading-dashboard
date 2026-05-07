@extends('layout.master')

@push('plugin-styles')
  <style>
    #marketCandleChart {
      width: 100%;
      height: 620px;
    }

    .market-chart-toolbar .form-select,
    .market-chart-toolbar .form-control {
      min-width: 130px;
    }

    .chart-loading {
      min-height: 620px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
  <div>
    <h4 class="mb-3 mb-md-0">Market Candle Chart</h4>
    <p class="text-muted mb-0">Monitoring data OHLC dari feeder MT5 ke MariaDB.</p>
  </div>

  <div class="d-flex align-items-center flex-wrap text-nowrap">
    <button type="button" class="btn btn-outline-primary btn-icon-text me-2 mb-2 mb-md-0" onclick="loadCandles()">
      <i class="btn-icon-prepend" data-feather="refresh-cw"></i>
      Refresh
    </button>
    <button type="button" class="btn btn-primary btn-icon-text mb-2 mb-md-0" onclick="fitChart()">
      <i class="btn-icon-prepend" data-feather="maximize"></i>
      Fit Chart
    </button>
  </div>
</div>

<div class="row">
  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline">
          <h6 class="card-title mb-0">Symbol</h6>
          <i data-feather="activity" class="icon-lg text-primary"></i>
        </div>
        <div class="row mt-3">
          <div class="col-12">
            <h3 class="mb-2" id="infoSymbol">-</h3>
            <p class="text-muted tx-13 mb-0">Pair aktif pada chart</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline">
          <h6 class="card-title mb-0">Timeframe</h6>
          <i data-feather="clock" class="icon-lg text-info"></i>
        </div>
        <div class="row mt-3">
          <div class="col-12">
            <h3 class="mb-2" id="infoTimeframe">-</h3>
            <p class="text-muted tx-13 mb-0">TF data candle</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline">
          <h6 class="card-title mb-0">Total Candle</h6>
          <i data-feather="bar-chart-2" class="icon-lg text-success"></i>
        </div>
        <div class="row mt-3">
          <div class="col-12">
            <h3 class="mb-2" id="infoTotal">0</h3>
            <p class="text-muted tx-13 mb-0">Jumlah candle ditampilkan</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-3 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline">
          <h6 class="card-title mb-0">Last Close</h6>
          <i data-feather="trending-up" class="icon-lg text-warning"></i>
        </div>
        <div class="row mt-3">
          <div class="col-12">
            <h3 class="mb-2" id="infoLastClose">-</h3>
            <p class="text-muted tx-13 mb-0" id="infoLastTime">Last candle UTC</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-baseline mb-4">
          <h6 class="card-title mb-0">OHLC Candle Viewer</h6>

          <div class="dropdown mb-2">
            <button class="btn btn-link p-0" type="button" id="dropdownMarketChart" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownMarketChart">
              <a class="dropdown-item d-flex align-items-center" href="javascript:;" onclick="loadCandles()">
                <i data-feather="refresh-cw" class="icon-sm me-2"></i>
                <span>Refresh</span>
              </a>
              <a class="dropdown-item d-flex align-items-center" href="javascript:;" onclick="fitChart()">
                <i data-feather="maximize" class="icon-sm me-2"></i>
                <span>Fit Chart</span>
              </a>
            </div>
          </div>
        </div>

        <div class="row align-items-end market-chart-toolbar mb-4">
          <div class="col-md-3 mb-3 mb-md-0">
            <label for="symbol" class="form-label">Pair / Symbol</label>
            <select id="symbol" class="form-select">
              @forelse ($symbols as $symbol)
                <option value="{{ $symbol }}">{{ $symbol }}</option>
              @empty
                <option value="">Belum ada data</option>
              @endforelse
            </select>
          </div>

          <div class="col-md-3 mb-3 mb-md-0">
            <label for="timeframe" class="form-label">Timeframe</label>
            <select id="timeframe" class="form-select">
              @forelse ($timeframes as $timeframe)
                <option value="{{ $timeframe }}" {{ $timeframe === 'M15' ? 'selected' : '' }}>
                  {{ $timeframe }}
                </option>
              @empty
                <option value="">Belum ada data</option>
              @endforelse
            </select>
          </div>

          <div class="col-md-3 mb-3 mb-md-0">
            <label for="limit" class="form-label">Jumlah Candle</label>
            <select id="limit" class="form-select">
              <option value="100">100 candle</option>
              <option value="300">300 candle</option>
              <option value="500" selected>500 candle</option>
              <option value="1000">1000 candle</option>
              <option value="2000">2000 candle</option>
            </select>
          </div>

          <div class="col-md-3">
            <button type="button" class="btn btn-primary btn-icon-text w-100" onclick="loadCandles()">
              <i class="btn-icon-prepend" data-feather="search"></i>
              Load Chart
            </button>
          </div>
        </div>

        <div class="alert alert-light border d-flex align-items-center mb-4" role="alert">
          <i data-feather="database" class="icon-md text-primary me-2"></i>
          <div>
            <strong>Status:</strong>
            <span id="chartStatus">Menunggu data...</span>
          </div>
        </div>

        <div id="marketCandleChart"></div>
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
  const candlesUrl = @json(route('market.chart.candles'));

  let chart;
  let candleSeries;
  const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  const chartElement = document.getElementById('marketCandleChart');

  function initChart() {
    chart = LightweightCharts.createChart(chartElement, {
      width: chartElement.clientWidth,
      height: 620,
      layout: {
        background: {
          color: '#ffffff'
        },
        textColor: '#333333'
      },
      grid: {
        vertLines: {
          color: '#f1f1f1'
        },
        horzLines: {
          color: '#f1f1f1'
        }
      },
      rightPriceScale: {
        borderColor: '#e5e7eb'
      },
      timeScale: {
        borderColor: '#e5e7eb',
        timeVisible: true,
        secondsVisible: false
      },
      crosshair: {
        mode: LightweightCharts.CrosshairMode.Normal
      }
    });

    candleSeries = chart.addCandlestickSeries({
      upColor: '#10b981',
      downColor: '#ef4444',
      borderUpColor: '#10b981',
      borderDownColor: '#ef4444',
      wickUpColor: '#10b981',
      wickDownColor: '#ef4444'
    });
  }

  function fitChart() {
    if (chart) {
      chart.timeScale().fitContent();
    }
  }

  function parseUtcDateTime(utcTimeString) {
    const match = /^([0-9]{4})-([0-9]{2})-([0-9]{2})(?:[ T]([0-9]{2}):([0-9]{2}):([0-9]{2}))?$/.exec(utcTimeString);
    if (!match) {
      const fallback = new Date(utcTimeString + 'Z');
      return Number.isNaN(fallback.getTime()) ? null : fallback;
    }

    const [, year, month, day, hour = '0', minute = '0', second = '0'] = match;
    return new Date(Date.UTC(
      Number(year),
      Number(month) - 1,
      Number(day),
      Number(hour),
      Number(minute),
      Number(second)
    ));
  }

  async function loadCandles() {
    const symbol = document.getElementById('symbol').value;
    const timeframe = document.getElementById('timeframe').value;
    const limit = document.getElementById('limit').value;
    const status = document.getElementById('chartStatus');

    if (!symbol || !timeframe) {
      status.textContent = 'Symbol atau timeframe belum tersedia.';
      if (candleSeries) {
        candleSeries.setData([]);
      }
      return;
    }

    status.textContent = 'Loading data candle...';

    try {
      const url = `${candlesUrl}?symbol=${encodeURIComponent(symbol)}&timeframe=${encodeURIComponent(timeframe)}&limit=${encodeURIComponent(limit)}`;
      const response = await fetch(url);
      const result = await response.json();

      if (!response.ok) {
        console.error(result);
        status.textContent = 'Gagal mengambil data dari server.';
        return;
      }

      const candles = result.data ?? [];

      const processedCandles = candles.map(candle => {
        const date = parseUtcDateTime(candle.tick_time);
        return {
          ...candle,
          time: date ? Math.round(date.getTime() / 1000) : candle.time
        };
      });

      candleSeries.setData(processedCandles);
      chart.timeScale().fitContent();

      document.getElementById('infoSymbol').textContent = result.symbol ?? '-';
      document.getElementById('infoTimeframe').textContent = result.timeframe ?? '-';
      document.getElementById('infoTotal').textContent = result.total ?? 0;

      if (candles.length > 0) {
        const last = candles[candles.length - 1];

        document.getElementById('infoLastClose').textContent = Number(last.close).toFixed(3);
        const lastTime = parseUtcDateTime(last.tick_time) ?? new Date((last.time || 0) * 1000);
        const formatter = new Intl.DateTimeFormat('id-ID', {
          timeZone: userTimeZone,
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit'
        });
        document.getElementById('infoLastTime').textContent = `Last candle: ${formatter.format(lastTime)} (${userTimeZone})`;
        status.textContent = `Data berhasil dimuat. Total ${result.total} candle.`;
      } else {
        document.getElementById('infoLastClose').textContent = '-';
        document.getElementById('infoLastTime').textContent = 'Last candle UTC';
        status.textContent = 'Data kosong untuk symbol/timeframe ini.';
      }

      if (typeof feather !== 'undefined') {
        feather.replace();
      }

    } catch (error) {
      console.error(error);
      status.textContent = 'Error koneksi saat mengambil data candle.';
    }
  }

  window.addEventListener('resize', function () {
    if (chart) {
      chart.applyOptions({
        width: chartElement.clientWidth
      });
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    initChart();
    loadCandles();
  });
</script>
@endpush