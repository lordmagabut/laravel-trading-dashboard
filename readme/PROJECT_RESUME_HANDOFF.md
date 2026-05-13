# Resume Proyek Trading Dashboard + Technical Agent

Dokumen ini adalah resume kerja dari awal sampai kondisi terakhir proyek, agar mudah dilanjutkan di chat lain, termasuk di ChatGPT Web.

## 1. Tujuan Proyek

Proyek ini membangun sistem trading bot AI terdistribusi dengan arsitektur:

```text
MT5 / Python Feeder
        |
        v
market_data
        |
        v
Laravel Trading Dashboard / Control Tower
        |
        v
technical_analyses
        |
        v
Technical Agent OpenClaw
        |
        v
technical_analyses TECHNICAL_COMPLETED
        |
        v
Manager Agent / Executor Agent
        |
        v
trade_signals
        |
        v
MT5 Executor
```

Prinsip penting:

```text
Technical Agent hanya memberi analisa teknikal.
Technical Agent tidak membuat trade_signals.
Technical Agent tidak mengeksekusi order.
```

## 2. Arsitektur yang Sudah Ditetapkan

### Data market

Data OHLC disimpan di tabel:

```text
market_data
```

Data ini berasal dari Python feeder dan dipakai oleh Laravel untuk:

- chart market
- technical context
- auto generate technical analyses

### Pair configuration

Pair yang dianalisa disimpan di:

```text
trading_bot_pairs
```

Field penting:

- `symbol`
- `entry_timeframe`
- `enabled`
- `auto_generate`
- `higher_timeframes`
- `last_checked_at`
- `last_generated_at`
- `last_generated_candle_time`

### Technical analysis

Hasil generate context disimpan di:

```text
technical_analyses
```

Field penting:

- `analysis_uuid`
- `symbol`
- `execution_timeframe`
- `higher_timeframe_bias`
- `execution_bias`
- `preferred_action`
- `current_price`
- `context_candle_time`
- `raw_context_json`
- `prompt_text`
- `ai_response_json`
- `decision`
- `confidence`
- `reason_summary`
- `reasons_json`
- `status`

### Trade signal

Trade signal tetap ada di tabel:

```text
trade_signals
```

Tetapi sesuai arsitektur final, tabel ini nantinya harus dibuat oleh Manager Agent, bukan Technical Agent.

## 3. Workflow Laravel yang Sudah Berjalan

### Bot Pair Settings

Halaman:

```text
/bot-pair-settings
```

Fungsi:

- tambah pair
- edit pair
- enable/disable
- auto/manual generate
- lihat status scheduler
- lihat latest analysis

### Scheduler

Laravel scheduler menjalankan command:

```bash
php artisan technical-analysis:auto-generate-all
```

Command ini:

1. membaca pair dari `trading_bot_pairs`
2. mengambil pair yang `enabled = true` dan `auto_generate = true`
3. mengecek candle terbaru sesuai `symbol + entry_timeframe`
4. jika belum ada analysis untuk candle tersebut, maka generate technical analysis baru

### Generate Technical Analysis

Service utama:

```text
app/Services/TechnicalAnalysisGeneratorService.php
```

Logic utama:

1. cari candle terbaru dari `market_data`
2. cek apakah `technical_analyses` dengan `symbol + timeframe + context_candle_time` sudah ada
3. build context
4. build prompt
5. simpan row baru dengan status:

```text
GENERATED
```

### Technical Context

Service:

```text
app/Services/TechnicalContextService.php
```

Menghasilkan:

- bias classic
- summary classic
- smc per timeframe
- smc_summary

Timeframe yang dianalisa:

```text
D1, H4, H1, M15, M5
```

## 4. SMC Context yang Sudah Ditambahkan

Service baru:

```text
app/Services/SmcContextService.php
```

SMC context yang sudah dihitung:

- external swing
- internal swing
- market structure
- BOS
- CHoCH
- liquidity sweep
- demand zones
- supply zones
- premium/discount
- SMC support/resistance
- SMC bias

Output sekarang tersimpan di `raw_context_json` di bawah:

```text
smc
smc_summary
```

Catatan:

Technical analyses baru yang digenerate manual sudah mengandung `smc_summary`.
Technical analyses lama yang dibuat sebelum SMC ditambahkan tidak memiliki data SMC.

## 5. Prompt Technical Agent

Service:

```text
app/Services/TechnicalAnalysisPromptService.php
```

Prompt sekarang sudah memuat:

- classic summary
- SMC summary
- instruksi agar output hanya JSON
- instruksi bahwa Technical Agent hanya memberi technical result

## 6. Endpoint API yang Sudah Ada

File:

```text
routes/api.php
```

Endpoint penting:

```text
POST /api/technical-analyses/generate
GET  /api/technical-analyses/pending
POST /api/technical-analyses/{id}/technical-result
POST /api/technical-analyses/{id}/ai-result
```

Catatan:

- `technical-result` adalah endpoint baru yang benar sesuai workflow final
- `ai-result` saat ini dibuat sebagai alias ke logic baru
- keduanya sekarang tidak lagi membuat `trade_signals`

## 7. Status Workflow Technical Agent

Status `technical_analyses` yang dipakai sekarang:

```text
GENERATED
SENT_TO_TECHNICAL_AGENT
TECHNICAL_COMPLETED
FAILED
```

Flow:

```text
GENERATED
   -> GET pending
SENT_TO_TECHNICAL_AGENT
   -> POST technical-result
TECHNICAL_COMPLETED
```

## 8. Technical Agent Runner

Folder runner:

```text
agents/technical-agent-runner
```

File penting:

- [technical_agent_runner.py](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/technical_agent_runner.py)
- [agents/technical-agent-runner/README.md](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/README.md)
- [agents/technical-agent-runner/.env.example](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/.env.example)
- [agents/technical-agent-runner/requirements.txt](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/requirements.txt)

### Fungsi runner

Runner ini:

1. GET pending analysis dari Laravel
2. compact `raw_context_json`
3. build prompt yang lebih hemat token
4. panggil OpenClaw CLI
5. parse output JSON OpenClaw
6. POST hasil teknikal ke Laravel

### Command OpenClaw yang dipakai

Format panggilan yang sudah terbukti berjalan:

```bash
openclaw agent --local --session-id technical-analysis-<id> --message "<prompt>" --json --thinking off --timeout 900
```

### Command test OpenClaw yang sudah berhasil

```bash
openclaw agent --local --session-id technical-agent-test --message "Return only valid JSON with fields decision, confidence, reason_summary, reasons. Use decision NO_TRADE." --json
```

Output OpenClaw berhasil diparse dari:

```text
meta.finalAssistantRawText
```

## 9. Setup VM Technical Agent

Kondisi yang sudah dipastikan:

- OpenClaw sudah terinstall di VM terpisah
- VM OpenClaw sudah bisa ping IP Laravel
- endpoint Laravel bisa diakses dari VM

Test yang berhasil:

```bash
curl -i http://192.168.70.50:8000/api/technical-analyses/pending?limit=1
```

Response:

```text
HTTP/1.1 200 OK
Content-Type: application/json
```

### Catatan penting

Untuk query boolean ke Laravel, `mark_sent` harus dikirim sebagai:

```text
mark_sent=1
mark_sent=0
```

Bukan `true/false` string.

## 10. Technical Agent Runner Behavior

### Test dry run

Sudah berhasil:

```bash
DRY_RUN=true python technical_agent_runner.py --once
```

### Test real run

Sudah berhasil mencapai tahap:

```text
[runner] processing analysis #...
[openclaw] running: openclaw agent ...
```

Artinya integrasi dasar runner ke OpenClaw sudah jalan.

### Perbaikan yang sudah dibuat

- runner sekarang mengirim `mark_sent=1` atau `mark_sent=0`
- `DRY_RUN=true` tidak lagi menandai analysis sebagai sent
- error non-JSON dari Laravel sekarang menampilkan status, content-type, dan body preview

## 11. Halaman Laravel yang Sudah Diperbarui

### Technical Context page

Halaman:

```text
/technical-context
```

Sudah menampilkan:

- summary classic
- summary SMC
- tabel SMC per timeframe
- demand/supply zones
- raw JSON

### Technical Analysis detail page

Halaman detail sudah menampilkan:

- summary basic
- SMC summary
- execution SMC context
- demand zones
- supply zones
- raw context JSON
- AI response JSON

### Tabel DataTables ringan

Halaman-halaman ini sudah diubah memakai DataTables Bootstrap 5 versi ringan:

- [resources/views/technical/analyses/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/analyses/index.blade.php)
- [resources/views/signals/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/signals/index.blade.php)
- [resources/views/bot_pairs/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/bot_pairs/index.blade.php)

Pendekatan yang dipakai:

- searchable
- sortable
- responsive
- tanpa modal kompleks
- tanpa animasi tambahan berat

### Perbaikan UI yang sudah dibuat

Card status di `Technical Analyses` sudah dirapikan. Label yang dipakai:

- `Generated`
- `Sent to Technical Agent`
- `Technical Completed`
- `Failed`

## 12. Scheduler CasaOS

File YAML yang dibahas:

```text
C:\Users\User\Downloads\web.yaml
```

Sudah ditambahkan service:

```yaml
scheduler:
  command:
    - php
    - artisan
    - schedule:work
```

Tujuannya agar Laravel scheduler berjalan otomatis di CasaOS sebagai container terpisah.

## 13. Masalah yang Pernah Ditemukan

### Technical analysis lama belum punya SMC

Row lama di `technical_analyses` yang dibuat sebelum SMC service ditambahkan akan punya:

```text
smc_summary = null / {}
execution_smc kosong
```

Akibatnya OpenClaw bisa menjawab:

```text
SMC context is unavailable
```

### Manual generate vs scheduler generate

Ditemukan bahwa:

- hasil manual generate sudah punya `smc_summary`
- hasil scheduler sempat belum punya `smc_summary`

Kemungkinan penyebab:

```text
process schedule:work lama belum restart setelah perubahan kode
```

Solusi:

- restart scheduler container/service
- generate ulang analysis baru

## 14. Script dan File Penting

### Laravel Services

- [TechnicalAnalysisGeneratorService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalAnalysisGeneratorService.php)
- [TechnicalContextService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalContextService.php)
- [SmcContextService.php](C:/np/laravel-trading-dashboard/app/Services/SmcContextService.php)
- [TechnicalAnalysisPromptService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalAnalysisPromptService.php)

### Laravel Controllers

- [TechnicalAnalysisWorkflowController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/TechnicalAnalysisWorkflowController.php)
- [SignalDashboardController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/SignalDashboardController.php)
- [BotPairSettingController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/BotPairSettingController.php)
- [TechnicalContextController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/TechnicalContextController.php)

### Laravel Views

- [resources/views/technical/context.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/context.blade.php)
- [resources/views/technical/analyses/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/analyses/index.blade.php)
- [resources/views/technical/analyses/show.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/analyses/show.blade.php)
- [resources/views/signals/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/signals/index.blade.php)
- [resources/views/bot_pairs/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/bot_pairs/index.blade.php)

### Routes

- [routes/web.php](C:/np/laravel-trading-dashboard/routes/web.php)
- [routes/api.php](C:/np/laravel-trading-dashboard/routes/api.php)

### Runner

- [agents/technical-agent-runner/technical_agent_runner.py](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/technical_agent_runner.py)
- [agents/technical-agent-runner/README.md](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/README.md)

### Dokumentasi internal

- [WORKFLOW.md](C:/np/laravel-trading-dashboard/WORKFLOW.md)
- [PROJECT_RESUME_HANDOFF.md](C:/np/laravel-trading-dashboard/PROJECT_RESUME_HANDOFF.md)

## 15. Kondisi Sekarang

Yang sudah selesai:

- Technical Context classic
- SMC Context objektif
- penyimpanan SMC ke raw context
- prompt untuk Technical Agent
- endpoint pending technical analysis
- endpoint submit technical result
- Technical Agent runner
- test OpenClaw CLI
- test akses API dari VM OpenClaw
- DataTables ringan di halaman utama
- scheduler container concept untuk CasaOS

Yang belum selesai:

- pastikan semua generated analysis baru dari scheduler sudah mengandung `smc_summary`
- jalankan Technical Agent runner end-to-end sampai konsisten `TECHNICAL_COMPLETED`
- bikin `fundamental_analyses`
- bikin `risk_rules`
- bikin `entry_rules`
- bikin `manager_decisions`
- pindahkan pembuatan `trade_signals` ke Manager Agent workflow

## 16. Langkah Lanjut yang Direkomendasikan

Urutan lanjutan yang paling aman:

1. Bersihkan `technical_analyses` lama yang belum punya SMC, lalu generate ulang.
2. Pastikan scheduler CasaOS benar-benar restart dan memakai kode terbaru.
3. Test runner Technical Agent end-to-end pada analysis baru yang pasti punya `smc_summary`.
4. Verifikasi status berubah:

```text
GENERATED -> SENT_TO_TECHNICAL_AGENT -> TECHNICAL_COMPLETED
```

5. Setelah Technical Agent stabil, baru lanjut ke desain:

```text
fundamental_analyses
risk_rules
entry_rules
manager_decisions
```

## 17. Ringkasan Singkat untuk Handoff

Kalau ingin melanjutkan di chat lain, inti konteksnya adalah:

```text
Laravel dashboard sudah bisa auto-generate technical_analyses dari market_data per pair/timeframe.
Technical context classic dan SMC sudah ada.
Technical Agent OpenClaw tidak membuat trade_signals.
Technical Agent runner Python sudah dibuat dan sudah bisa bicara ke Laravel API dan OpenClaw CLI.
Saat ini fokus utamanya adalah memastikan seluruh analysis baru dari scheduler mengandung smc_summary dan runner berjalan stabil end-to-end.
```
