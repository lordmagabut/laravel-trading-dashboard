# Workflow Sistem Trading Bot AI Terdistribusi

Dokumen ini menjelaskan workflow final sistem trading bot AI terdistribusi berbasis MT5, Python Feeder, MariaDB, Laravel Control Tower, dan OpenClaw Agents.

Prinsip utama arsitektur ini:

```text
Technical Agent bukan trader.
Fundamental Agent bukan trader.
Manager Agent adalah decision maker.
Executor hanya mengeksekusi keputusan yang sudah valid.
```

## Ringkasan Arsitektur Final

```text
MT5 Windows VM
        |
        v
Python Feeder
        |
        v
MariaDB: market_data
        |
        v
Laravel Noble UI Control Tower
        |
        v
technical_analyses status: GENERATED
        |
        v
Technical Agent OpenClaw
        |
        v
technical_analyses status: TECHNICAL_COMPLETED

Fundamental Agent OpenClaw
        |
        v
fundamental_analyses status: COMPLETED

Risk Rules + Entry Rules
        |
        v
Manager / Executor Agent
        |
        v
manager_decisions
        |
        v
trade_signals
        |
        v
MT5 Executor
```

## 1. Market Data Pipeline

Data candle berasal dari MT5 dan dikirim oleh Python Feeder langsung ke MariaDB.

Alur:

```text
MT5
   |
   v
Python Feeder
   |
   v
MariaDB: market_data
```

Tabel:

```text
market_data
```

Kolom utama:

- `symbol`
- `timeframe`
- `tick_time`
- `open`
- `high`
- `low`
- `close`
- `volume`
- `created_at`
- `updated_at`

Unique key:

```sql
UNIQUE(symbol, timeframe, tick_time)
```

Unique key ini penting agar Python Feeder bisa melakukan upsert candle tanpa membuat data dobel.

Catatan:

- Python Feeder hanya mengisi `market_data`.
- Python Feeder tidak membuat `technical_analyses`.
- Python Feeder tidak membuat `trade_signals`.

## 2. Market Chart Laravel

Laravel menyediakan halaman chart untuk membaca data dari `market_data`.

Route:

```text
/market-chart
/market-chart/candles
```

Komponen:

- `MarketChartController`
- `resources/views/market/chart.blade.php`
- TradingView Lightweight Charts

Workflow:

```text
market_data
   |
   v
MarketChartController
   |
   v
Laravel Market Chart
```

## 3. Bot Pair Settings

Pair dan timeframe entry diatur melalui halaman:

```text
/bot-pair-settings
```

Tabel:

```text
trading_bot_pairs
```

Kolom utama:

- `symbol`
- `entry_timeframe`
- `enabled`
- `auto_generate`
- `higher_timeframes`
- `last_checked_at`
- `last_generated_at`
- `last_generated_candle_time`
- `notes`

Contoh konfigurasi:

```text
XAUUSD -> M15
EURUSD -> M5
GBPJPY -> H1
BTCUSD -> M30
```

Hanya pair dengan kondisi berikut yang diproses otomatis:

```text
enabled = true
auto_generate = true
```

## 4. Scheduler Auto Generate Technical Analysis

Laravel Scheduler berjalan setiap 1 menit.

Command:

```bash
php artisan technical-analysis:auto-generate-all
```

File:

```text
app/Console/Kernel.php
app/Console/Commands/AutoGenerateAllTechnicalAnalysisCommand.php
```

Workflow:

```text
Scheduler setiap menit
   |
   v
Baca trading_bot_pairs
   |
   v
Ambil pair enabled dan auto_generate
   |
   v
Cek candle terbaru di market_data sesuai symbol dan entry_timeframe
   |
   v
Jika candle belum pernah dibuat analysis
   |
   v
Buat technical_analyses status GENERATED
```

Field penting untuk mencegah analisa dobel:

```text
context_candle_time
```

Rekomendasi unique index:

```sql
UNIQUE(symbol, execution_timeframe, context_candle_time)
```

Artinya, satu candle hanya boleh menghasilkan satu row `technical_analyses`.

## 5. TechnicalAnalysisGeneratorService

Service:

```text
app/Services/TechnicalAnalysisGeneratorService.php
```

Tugas:

1. Ambil latest candle dari `market_data`.
2. Cek apakah candle tersebut sudah punya technical analysis.
3. Panggil `TechnicalContextService`.
4. Buat `prompt_text` lewat `TechnicalAnalysisPromptService`.
5. Simpan row ke `technical_analyses`.

Status awal:

```text
GENERATED
```

Pada status `GENERATED`, AI belum menjawab. Karena itu field berikut boleh kosong:

- `decision`
- `confidence`
- `reason_summary`
- `reasons_json`
- `ai_response_json`
- `agent_model`

Catatan penting:

```text
NO_TRADE tidak boleh dipakai sebagai default decision saat GENERATED.
NO_TRADE hanya boleh masuk setelah agent benar-benar menjawab.
```

## 6. Technical Context

Service:

```text
app/Services/TechnicalContextService.php
```

Endpoint:

```text
/technical-context
/api/technical-context?symbol=XAUUSD&execution_timeframe=M15
```

Technical context menghitung:

- EMA20
- EMA50
- EMA200
- EMA slope
- ATR14
- swing high
- swing low
- market structure
- BOS
- support terdekat
- resistance terdekat
- bias D1/H4/H1/M15/M5
- preferred action

Rule bias:

```text
score >= 3  -> bullish
score <= -3 -> bearish
selain itu -> neutral
```

Catatan:

```text
Bias bullish bukan berarti langsung BUY.
Bias bearish bukan berarti langsung SELL.
```

## 7. Technical Prompt

Service:

```text
app/Services/TechnicalAnalysisPromptService.php
```

Service ini membuat prompt untuk Technical Agent.

Prompt berisi:

- symbol
- execution timeframe
- higher timeframe bias
- execution bias
- preferred action
- current price
- raw technical context JSON

Technical Agent boleh memberi hasil teknikal seperti:

- `technical_decision`
- `confidence`
- `reason_summary`
- `reasons_json`
- entry zone teknikal
- stop loss zone teknikal
- take profit zone teknikal
- invalidation teknikal

Namun hasil ini belum menjadi keputusan trading final.

## 8. Technical Agent Workflow

Technical Agent OpenClaw berjalan mandiri di VM.

Tugas Technical Agent:

```text
Membaca technical_analyses status GENERATED
Menganalisa raw_context_json dan prompt_text
Mengirim hasil analisa teknikal ke Laravel
Mengubah status menjadi TECHNICAL_COMPLETED
```

Technical Agent tidak boleh:

- membuat `trade_signals`
- mengeksekusi trading
- menjadi decision maker final

Endpoint yang direkomendasikan:

```text
GET  /api/technical-analyses/pending
POST /api/technical-analyses/{id}/technical-result
```

Response endpoint pending sebaiknya berisi:

- `id`
- `analysis_uuid`
- `symbol`
- `execution_timeframe`
- `context_candle_time`
- `raw_context_json`
- `prompt_text`

Saat row diambil oleh Technical Agent, status bisa diubah menjadi:

```text
SENT_TO_TECHNICAL_AGENT
```

Payload contoh untuk technical result:

```json
{
  "decision": "BUY",
  "confidence": 78,
  "reason_summary": "Bullish technical setup, but final execution must wait manager validation.",
  "reasons": [
    "M15 bullish structure",
    "Price above EMA50",
    "M5 confirmation present"
  ],
  "technical_setup": {
    "entry_zone": {
      "from": 4715,
      "to": 4720
    },
    "stop_loss_zone": {
      "price": 4704
    },
    "take_profit_zones": [
      4732,
      4740,
      4755
    ],
    "invalidation": "Close below support M15"
  },
  "agent_name": "OpenClaw Technical Agent",
  "agent_model": "technical-vm-01"
}
```

Hasil update:

```text
technical_analyses.status = TECHNICAL_COMPLETED
technical_analyses.decision = BUY / SELL / NO_TRADE
technical_analyses.confidence = confidence
technical_analyses.ai_response_json = payload
```

Yang tidak boleh dilakukan di endpoint ini:

```text
Jangan membuat trade_signals.
```

## 9. Status Technical Analysis

Status yang direkomendasikan:

```text
GENERATED
SENT_TO_TECHNICAL_AGENT
TECHNICAL_COMPLETED
FAILED
```

Status lama seperti `AI_COMPLETED` masih bisa dipakai secara teknis, tetapi secara konsep multi-agent lebih jelas memakai:

```text
TECHNICAL_COMPLETED
```

## 10. Fundamental Agent Workflow

Fundamental Agent OpenClaw juga berjalan mandiri di VM.

Tugas:

```text
Membaca news, calendar, macro, dan sentiment
Menganalisa risiko fundamental
Menyimpan hasil ke fundamental_analyses
```

Tabel yang diperlukan:

```text
fundamental_analyses
```

Kolom inti:

- `id`
- `analysis_uuid`
- `symbol`
- `timeframe_scope`
- `fundamental_bias`
- `news_risk_level`
- `sentiment_bias`
- `avoid_trade`
- `confidence`
- `reason_summary`
- `reasons_json`
- `raw_context_json`
- `ai_response_json`
- `agent_name`
- `agent_model`
- `status`
- `notes`
- `created_at`
- `updated_at`

Endpoint yang direkomendasikan:

```text
POST /api/fundamental-analyses
GET  /api/fundamental-analyses/latest?symbol=XAUUSD
```

Fundamental Agent juga tidak boleh membuat `trade_signals`.

## 11. Risk Rules

Manager Agent membutuhkan aturan risiko sebelum boleh mengambil keputusan.

Tabel yang diperlukan:

```text
risk_rules
```

Contoh rule:

- `max_risk_percent_per_trade`
- `max_daily_loss_percent`
- `max_open_positions`
- `max_positions_per_symbol`
- `min_risk_reward`
- `allowed_sessions`
- `avoid_high_impact_news`
- `max_spread_points`

## 12. Entry Rules

Manager Agent juga membutuhkan aturan validasi entry.

Tabel yang diperlukan:

```text
entry_rules
```

Contoh rule:

- `require_htf_alignment`
- `require_fundamental_alignment`
- `allow_counter_trend`
- `minimum_technical_confidence`
- `minimum_fundamental_confidence`
- `entry_type_allowed`
- `max_distance_from_entry_zone`

## 13. Manager Agent Workflow

Manager Agent adalah decision maker final.

Manager Agent membaca:

- `technical_analyses`
- `fundamental_analyses`
- `risk_rules`
- `entry_rules`
- account status
- open positions
- trade history

Manager Agent memutuskan:

- `EXECUTE_BUY`
- `EXECUTE_SELL`
- `WAIT`
- `REJECT`
- `NO_TRADE`

Manager Agent yang boleh membuat:

- `manager_decisions`
- `trade_signals`

## 14. Manager Decisions

Tabel yang diperlukan:

```text
manager_decisions
```

Kolom inti:

- `id`
- `decision_uuid`
- `symbol`
- `timeframe`
- `technical_analysis_id`
- `fundamental_analysis_id`
- `decision`
- `side`
- `confidence`
- `manager_reason_summary`
- `manager_reasons_json`
- `risk_check_json`
- `entry_rule_check_json`
- `status`
- `notes`
- `created_at`
- `updated_at`

Jika Manager Agent memutuskan entry valid, barulah sistem membuat `trade_signals`.

## 15. Trade Signals

Tabel:

```text
trade_signals
```

Dalam arsitektur final, `trade_signals` dibuat oleh Manager Agent, bukan Technical Agent.

Status awal:

```text
PENDING
```

Data signal berisi:

- `technical_analysis_id`
- `signal_uuid`
- `symbol`
- `timeframe`
- `decision`
- `side`
- `entry_type`
- `entry_price`
- `stop_loss`
- `take_profit_1`
- `take_profit_2`
- `take_profit_3`
- `risk_reward`
- `risk_percent`
- `lot_size`
- `confidence`
- `reason_summary`
- `reasons_json`
- `invalidation`
- `expired_at`

## 16. Signal Dashboard

Halaman:

```text
/signal-dashboard
```

Controller:

```text
app/Http/Controllers/SignalDashboardController.php
```

Fungsi:

- Menampilkan `trade_signals`
- Filter status, symbol, timeframe
- Approve signal
- Reject signal
- Cancel signal
- Send to Executor placeholder

Flow status yang sudah tersedia:

```text
PENDING -> APPROVED
PENDING -> REJECTED
PENDING / APPROVED -> CANCELLED
```

Status signal yang digunakan:

- `PENDING`
- `APPROVED`
- `REJECTED`
- `CANCELLED`
- `SENT_TO_EXECUTOR`
- `EXECUTED`
- `FAILED`
- `EXPIRED`

## 17. MT5 Executor

Executor hanya mengeksekusi signal yang sudah valid.

Alur target:

```text
trade_signals APPROVED
   |
   v
Send to Executor
   |
   v
MT5 Executor
   |
   v
Update execution result
```

Saat ini `sendToExecutor()` masih placeholder.

Implementasi berikutnya bisa berupa:

- dispatch Job
- HTTP request ke MT5 executor
- insert ke executor queue
- publish ke Redis atau RabbitMQ

## 18. Workflow Final End-to-End

```text
1. Python Feeder mengisi market_data.

2. Laravel Scheduler membaca trading_bot_pairs.

3. Laravel membuat technical_analyses status GENERATED
   berdasarkan candle baru sesuai entry_timeframe tiap pair.

4. Technical Agent VM membaca pending technical_analyses.

5. Technical Agent mengirim hasil analisa teknikal.

6. technical_analyses berubah menjadi TECHNICAL_COMPLETED.

7. Fundamental Agent VM membuat atau mengirim fundamental_analyses.

8. Manager Agent VM membaca:
   - technical_analyses
   - fundamental_analyses
   - risk_rules
   - entry_rules
   - account status
   - open positions

9. Manager Agent membuat manager_decisions.

10. Jika Manager Agent memutuskan entry, Manager membuat trade_signals.

11. Signal Dashboard menampilkan trade_signals.

12. User atau system approve sesuai rule.

13. Executor MT5 mengeksekusi signal yang sudah valid.
```

## 19. Kondisi Kode Saat Ini

Yang sudah ada di kode saat ini:

- `market_data`
- `technical_analyses`
- `trade_signals`
- `trading_bot_pairs`
- Market Chart
- Technical Context API
- Bot Pair Settings
- Scheduler auto generate technical analysis
- Signal Dashboard
- Approve / reject / cancel signal

Yang belum sesuai arsitektur final:

- Method `aiResult()` di `TechnicalAnalysisWorkflowController` masih bisa membuat `trade_signals`.
- Route technical result khusus agent belum tersedia.
- Endpoint pending technical analyses belum tersedia.
- `fundamental_analyses` belum tersedia.
- `risk_rules` belum tersedia.
- `entry_rules` belum tersedia.
- `manager_decisions` belum tersedia.
- Manager Agent workflow belum tersedia.
- `sendToExecutor()` masih placeholder.

## 20. Prioritas Revisi Berikutnya

Prioritas implementasi:

1. Revisi `TechnicalAnalysisWorkflowController` agar Technical Agent result hanya update `technical_analyses`.
2. Hapus logic create `trade_signals` dari flow Technical Agent.
3. Buat endpoint:

```text
GET  /api/technical-analyses/pending
POST /api/technical-analyses/{id}/technical-result
```

4. Buat `fundamental_analyses`.
5. Buat `risk_rules` dan `entry_rules`.
6. Buat `manager_decisions`.
7. Buat Manager Agent workflow yang membaca technical + fundamental + rules.
8. Pindahkan pembuatan `trade_signals` ke flow Manager Agent.

## 21. Kesimpulan

Workflow final bukan:

```text
Technical Agent -> trade_signals
```

Workflow final yang benar:

```text
Technical Agent -> technical_analyses
Fundamental Agent -> fundamental_analyses
Manager Agent -> manager_decisions -> trade_signals
Executor -> MT5
```

Dengan arsitektur ini, sistem menjadi lebih modular, lebih aman, dan sesuai konsep distributed AI agents.
