# Resume Proyek Trading Dashboard + Technical Agent

Dokumen ini adalah handoff terbaru proyek `laravel-trading-dashboard` agar mudah dilanjutkan di chat lain, termasuk di ChatGPT Web.

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
Trade signal final nantinya dibuat oleh workflow Manager Agent.
```

## 2. Status Arsitektur Saat Ini

Yang sudah aktif:

- Python feeder mengisi `market_data`
- Laravel dashboard membaca `market_data` untuk chart dan technical context
- `technical_analyses` bisa di-generate otomatis/manual per pair dan timeframe
- Technical Agent OpenClaw mengambil pending analysis via API
- hasil Technical Agent dikirim balik ke `technical_analyses`
- dashboard utama sudah diganti menjadi control room trading
- autentikasi login + role + permission sudah ditambahkan

Yang belum aktif:

- `fundamental_analyses`
- `risk_rules`
- `entry_rules`
- `manager_decisions`
- workflow final pembentukan `trade_signals` oleh Manager Agent

## 3. Struktur Data Penting

### `market_data`

Sumber data OHLC dari Python feeder. Dipakai untuk:

- chart market
- technical context
- auto generate technical analyses
- dashboard market overview

### `trading_bot_pairs`

Konfigurasi pair yang dianalisa.

Field penting:

- `symbol`
- `entry_timeframe`
- `enabled`
- `auto_generate`
- `higher_timeframes`
- `last_checked_at`
- `last_generated_at`
- `last_generated_candle_time`

### `technical_analyses`

Tempat menyimpan hasil generate context + prompt + hasil Technical Agent.

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

Status yang sekarang dipakai:

```text
GENERATED
SENT_TO_TECHNICAL_AGENT
TECHNICAL_COMPLETED
FAILED
```

### `trade_signals`

Masih ada dan dipakai oleh dashboard signal, tetapi secara arsitektur final seharusnya nanti dibentuk oleh Manager Agent, bukan oleh Technical Agent.

## 4. Workflow Laravel yang Sudah Berjalan

### Bot Pair Settings

Halaman:

```text
/bot-pair-settings
```

Fungsi:

- tambah/edit pair
- enable/disable pair
- aktif/nonaktif auto generate
- generate manual
- melihat latest analysis

### Scheduler

Command utama:

```bash
php artisan technical-analysis:auto-generate-all
```

Logikanya:

1. baca pair dari `trading_bot_pairs`
2. ambil pair `enabled = true` dan `auto_generate = true`
3. cek candle terbaru sesuai `symbol + entry_timeframe`
4. bila candle itu belum punya analysis, generate row baru di `technical_analyses`

Catatan penting:

- scheduler CasaOS dirancang sebagai container/service terpisah
- pernah ada kasus scheduler masih pakai proses lama sehingga hasilnya belum memuat SMC terbaru
- bila kode generator berubah, scheduler perlu direstart

### Generate Technical Analysis

Service utama:

- [app/Services/TechnicalAnalysisGeneratorService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalAnalysisGeneratorService.php)

Flow:

1. cari latest candle di `market_data`
2. cek duplikasi berdasarkan `symbol + timeframe + context_candle_time`
3. bangun technical context
4. bangun prompt
5. simpan row `technical_analyses` dengan status `GENERATED`

## 5. Technical Context dan SMC

Service:

- [app/Services/TechnicalContextService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalContextService.php)
- [app/Services/SmcContextService.php](C:/np/laravel-trading-dashboard/app/Services/SmcContextService.php)

Timeframe utama:

```text
D1, H4, H1, M15, M5
```

Technical context sekarang memuat:

- bias classic
- summary classic
- SMC per timeframe
- `smc_summary`

SMC yang sudah dihitung:

- external swing
- internal swing
- market structure
- BOS
- CHoCH
- liquidity sweep
- demand zones
- supply zones
- premium/discount
- support/resistance SMC
- SMC bias

Output sekarang tersimpan di `raw_context_json`:

```text
bias
summary
smc
smc_summary
```

Catatan penting:`r`n`r`n- kondisi terbaru menunjukkan hasil `auto-generate` baru sudah membawa SMC context dan `smc_summary``r`n- secara historis memang pernah ada row lama sebelum update SMC yang belum punya `smc_summary``r`n- jika row historis itu diproses agent, OpenClaw bisa memberi respons seperti `SMC context is unavailable`

## 6. Prompt Technical Agent

Service:

- [app/Services/TechnicalAnalysisPromptService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalAnalysisPromptService.php)

Prompt saat ini memuat:

- classic summary
- SMC summary
- instruksi output JSON only
- instruksi bahwa agent hanya mengembalikan technical result
- tidak membuat `trade_signals`

## 7. API Technical Agent yang Sudah Ada

File:

- [routes/api.php](C:/np/laravel-trading-dashboard/routes/api.php)

Endpoint penting:

```text
POST /api/technical-analyses/generate
GET  /api/technical-analyses/pending
POST /api/technical-analyses/{id}/technical-result
POST /api/technical-analyses/{id}/ai-result
```

Catatan:

- `technical-result` adalah jalur yang benar untuk workflow final
- `ai-result` dipertahankan sebagai alias ke logic baru
- endpoint ini tidak lagi membuat `trade_signals`

Flow status:

```text
GENERATED
   -> GET pending
SENT_TO_TECHNICAL_AGENT
   -> POST technical-result
TECHNICAL_COMPLETED
```

## 8. Technical Agent Runner

Folder:

```text
agents/technical-agent-runner
```

File penting:

- [technical_agent_runner.py](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/technical_agent_runner.py)
- [agents/technical-agent-runner/README.md](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/README.md)
- [agents/technical-agent-runner/.env.example](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/.env.example)
- [agents/technical-agent-runner/requirements.txt](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/requirements.txt)

Fungsi runner:

1. GET pending analysis dari Laravel
2. compact `raw_context_json`
3. build prompt yang lebih hemat token
4. panggil OpenClaw CLI
5. parse output JSON OpenClaw
6. POST hasil teknikal ke Laravel

Command OpenClaw yang sudah terbukti berjalan:

```bash
openclaw agent --local --session-id technical-analysis-<id> --message "<prompt>" --json --thinking off --timeout 900
```

Command test yang pernah berhasil:

```bash
openclaw agent --local --session-id technical-agent-test --message "Return only valid JSON with fields decision, confidence, reason_summary, reasons. Use decision NO_TRADE." --json
```

Output OpenClaw diparse dari:

```text
meta.finalAssistantRawText
```

Perbaikan runner yang sudah ada:

- `mark_sent=1` / `mark_sent=0`
- `DRY_RUN=true` tidak menandai analysis sebagai sent
- error non-JSON dari Laravel menampilkan status, content-type, dan body preview

## 9. Kondisi VM Technical Agent

Yang sudah dipastikan:

- OpenClaw terpasang di VM terpisah
- VM OpenClaw bisa ping IP Laravel
- endpoint Laravel bisa diakses dari VM

Contoh test yang berhasil:

```bash
curl -i http://192.168.70.50:8000/api/technical-analyses/pending?limit=1
```

Response:

```text
HTTP/1.1 200 OK
Content-Type: application/json
```

Catatan query boolean ke Laravel:

```text
mark_sent=1
mark_sent=0
```

Jangan pakai string `true/false`.

## 10. Dashboard Utama

Homepage `/` sudah tidak lagi memakai template admin default. Sekarang sudah diganti menjadi dashboard operasional trading.

Controller:

- [app/Http/Controllers/DashboardController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/DashboardController.php)

View:

- [resources/views/dashboard.blade.php](C:/np/laravel-trading-dashboard/resources/views/dashboard.blade.php)

Yang sekarang tampil di homepage:

- chart market utama
- focus symbol dan timeframe
- current price
- perubahan harga
- market condition
- HTF bias
- execution bias
- preferred action
- SMC structure
- last event
- workflow summary
- recent technical analyses
- recent trade signals

Perbaikan tambahan:

- `Feed terakhir` sekarang ditampilkan sesuai timezone browser user
- market chart di dashboard memakai endpoint candle yang sudah ada

## 11. UI, Tabel, dan Navigasi

### DataTables ringan

Halaman berikut memakai DataTables Bootstrap 5 client-side versi ringan:

- [resources/views/technical/analyses/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/analyses/index.blade.php)
- [resources/views/signals/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/signals/index.blade.php)
- [resources/views/bot_pairs/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/bot_pairs/index.blade.php)

Pendekatan:

- searchable
- sortable
- responsive
- tanpa animasi berat
- tanpa modal kompleks

Karena DataTables-nya client-side, controller list terkait sudah diubah dari `paginate()` ke `get()` agar semua data masuk ke browser.

### Halaman yang sudah dipoles

- Technical Context
- Technical Analyses
- Signal Dashboard
- Bot Pair Settings
- Dashboard utama

### Navbar dan urutan workflow

Navbar sekarang mengikuti alur kerja dan lebih bersih.

Urutan menu:

1. `Dashboard`
2. `Bot Pair Settings`
3. `Workflow`
   - Market Chart
   - Technical Context
   - Technical Analyses
   - Signal Dashboard

Catatan:

- `Bot Pair Settings` sengaja berdiri sendiri
- menu `Users` dan `Roles` dipindah ke dropdown user
- logout hanya ada di dropdown user

## 12. Auth, Role, dan Permission

Fitur auth + RBAC sekarang sudah ditambahkan dengan Spatie.

Package:

```text
spatie/laravel-permission
```

Bagian penting:

- [app/User.php](C:/np/laravel-trading-dashboard/app/User.php) memakai `HasRoles`
- [app/Http/Kernel.php](C:/np/laravel-trading-dashboard/app/Http/Kernel.php) sudah punya alias middleware:
  - `role`
  - `permission`
  - `role_or_permission`
- [app/Providers/AuthServiceProvider.php](C:/np/laravel-trading-dashboard/app/Providers/AuthServiceProvider.php) sudah punya `Gate::before` untuk `super-admin`
- [app/Http/Controllers/AuthController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/AuthController.php) menangani login/register/logout

Route utama sekarang dilindungi `auth` dan permission sesuai area.

Contoh permission:

- `view dashboard`
- `view market chart`
- `view technical context`
- `manage technical analyses`
- `manage bot pairs`
- `review trade signals`
- `manage users`
- `manage roles`

### Model RBAC yang dipakai sekarang

Alur UI sudah direfactor menjadi:

```text
User -> punya Role
Role -> punya Permissions
```

Bukan direct user permission sebagai alur utama.

### Roles default

Seeder:

- [database/seeders/RolesAndPermissionsSeeder.php](C:/np/laravel-trading-dashboard/database/seeders/RolesAndPermissionsSeeder.php)

Role default:

- `super-admin`
- `admin`
- `analyst`
- `reviewer`
- `viewer`

User admin default:

```text
email    : admin@nomaden.site
password : ChangeMe123!
```

Catatan:

- password ini sebaiknya diganti setelah deploy

### Manajemen user dan role

Controller:

- [app/Http/Controllers/UserManagementController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/UserManagementController.php)
- [app/Http/Controllers/RoleManagementController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/RoleManagementController.php)

View:

- [resources/views/users/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/users/index.blade.php)
- [resources/views/users/create.blade.php](C:/np/laravel-trading-dashboard/resources/views/users/create.blade.php)
- [resources/views/users/edit.blade.php](C:/np/laravel-trading-dashboard/resources/views/users/edit.blade.php)
- [resources/views/roles/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/roles/index.blade.php)
- [resources/views/roles/create.blade.php](C:/np/laravel-trading-dashboard/resources/views/roles/create.blade.php)
- [resources/views/roles/edit.blade.php](C:/np/laravel-trading-dashboard/resources/views/roles/edit.blade.php)

Perilaku UI:

- menu `Users` hanya muncul untuk user dengan permission `manage users`
- menu `Roles` hanya muncul untuk user dengan permission `manage roles`
- keduanya berada di dropdown user/header

## 13. Masalah Domain/Deploy yang Sudah Ditemukan

### Mixed content pada chart ketika diakses via domain HTTPS

Masalah:

- halaman dibuka lewat `https://...`
- JavaScript fetch candle ke URL `http://...`
- browser memblokir request sebagai `blocked:mixed-content`

Perbaikan yang sudah dibuat:

- [resources/views/market/chart.blade.php](C:/np/laravel-trading-dashboard/resources/views/market/chart.blade.php)
- [resources/views/technical/context.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/context.blade.php)

Route fetch diubah menjadi URL relatif:

```php
route('market.chart.candles', [], false)
route('technical.context.api', [], false)
```

Saran konfigurasi:

```env
APP_URL=https://trade.nomaden.site
```

Lalu clear cache Laravel dan restart container web.

### Error domain: `Trait "Spatie\Permission\Traits\HasRoles" not found`

Ini bukan bug kode lokal. Ini terjadi karena environment server/container belum sinkron dengan dependency terbaru.

Masalah yang ditemukan:

- `composer.json` di server berubah
- `composer.lock` di server belum sinkron
- package `spatie/laravel-permission` belum benar-benar tersedia di `vendor`

Di server pernah muncul error:

```text
Required package "spatie/laravel-permission" is not present in the lock file.
```

dan saat dicoba require di container:

```text
./composer.json is not writable.
```

Implikasinya:

- lokal bisa jalan
- domain/container bisa gagal load auth/RBAC

Solusi deployment yang pernah dibahas:

1. update `composer.lock` di source yang dideploy
2. install/update dependency di host atau container
3. jalankan:

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --class="Database\\Seeders\\RolesAndPermissionsSeeder" --force
```

4. restart container web dan scheduler

Catatan:

- pada setup CasaOS, source app dimount dari host
- seringkali lebih aman menjalankan `composer require/install` dari host path proyek daripada dari user default di container

## 14. Scheduler CasaOS

File YAML yang dibahas:

```text
C:\Users\User\Downloads\web.yaml
```

Sudah ada konsep service/container terpisah untuk scheduler:

```yaml
scheduler:
  command:
    - php
    - artisan
    - schedule:work
```

Tujuannya agar Laravel scheduler jalan otomatis di CasaOS.

## 15. File dan Script Penting

### Services

- [TechnicalAnalysisGeneratorService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalAnalysisGeneratorService.php)
- [TechnicalContextService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalContextService.php)
- [SmcContextService.php](C:/np/laravel-trading-dashboard/app/Services/SmcContextService.php)
- [TechnicalAnalysisPromptService.php](C:/np/laravel-trading-dashboard/app/Services/TechnicalAnalysisPromptService.php)

### Controllers

- [DashboardController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/DashboardController.php)
- [TechnicalAnalysisWorkflowController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/TechnicalAnalysisWorkflowController.php)
- [SignalDashboardController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/SignalDashboardController.php)
- [BotPairSettingController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/BotPairSettingController.php)
- [TechnicalContextController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/TechnicalContextController.php)
- [AuthController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/AuthController.php)
- [UserManagementController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/UserManagementController.php)
- [RoleManagementController.php](C:/np/laravel-trading-dashboard/app/Http/Controllers/RoleManagementController.php)

### Views

- [resources/views/dashboard.blade.php](C:/np/laravel-trading-dashboard/resources/views/dashboard.blade.php)
- [resources/views/market/chart.blade.php](C:/np/laravel-trading-dashboard/resources/views/market/chart.blade.php)
- [resources/views/technical/context.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/context.blade.php)
- [resources/views/technical/analyses/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/analyses/index.blade.php)
- [resources/views/technical/analyses/show.blade.php](C:/np/laravel-trading-dashboard/resources/views/technical/analyses/show.blade.php)
- [resources/views/signals/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/signals/index.blade.php)
- [resources/views/bot_pairs/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/bot_pairs/index.blade.php)
- [resources/views/layout/header.blade.php](C:/np/laravel-trading-dashboard/resources/views/layout/header.blade.php)
- [resources/views/pages/auth/login.blade.php](C:/np/laravel-trading-dashboard/resources/views/pages/auth/login.blade.php)
- [resources/views/pages/auth/register.blade.php](C:/np/laravel-trading-dashboard/resources/views/pages/auth/register.blade.php)
- [resources/views/users/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/users/index.blade.php)
- [resources/views/roles/index.blade.php](C:/np/laravel-trading-dashboard/resources/views/roles/index.blade.php)

### Routes

- [routes/web.php](C:/np/laravel-trading-dashboard/routes/web.php)
- [routes/api.php](C:/np/laravel-trading-dashboard/routes/api.php)

### Seeder dan migration auth/RBAC

- [database/migrations/2026_05_13_120000_create_permission_tables.php](C:/np/laravel-trading-dashboard/database/migrations/2026_05_13_120000_create_permission_tables.php)
- [database/seeders/RolesAndPermissionsSeeder.php](C:/np/laravel-trading-dashboard/database/seeders/RolesAndPermissionsSeeder.php)
- [database/seeders/DatabaseSeeder.php](C:/np/laravel-trading-dashboard/database/seeders/DatabaseSeeder.php)

### Runner

- [agents/technical-agent-runner/technical_agent_runner.py](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/technical_agent_runner.py)
- [agents/technical-agent-runner/README.md](C:/np/laravel-trading-dashboard/agents/technical-agent-runner/README.md)

### Dokumentasi internal

- [WORKFLOW.md](C:/np/laravel-trading-dashboard/WORKFLOW.md)
- [PROJECT_RESUME_HANDOFF.md](C:/np/laravel-trading-dashboard/PROJECT_RESUME_HANDOFF.md)
- [readme/PROJECT_RESUME_HANDOFF.md](C:/np/laravel-trading-dashboard/readme/PROJECT_RESUME_HANDOFF.md)

## 16. Verifikasi yang Sudah Pernah Berhasil

Secara lokal sudah pernah berhasil:

- `php artisan migrate --force`
- `php artisan db:seed --class="Database\\Seeders\\RolesAndPermissionsSeeder" --force`
- `php artisan route:list`
- `php artisan route:list --path=users`
- `php artisan route:list --path=roles`
- `php artisan view:cache`
- `php artisan test`

Status test terakhir lokal:

```text
2 passed
```

## 17. Kondisi Proyek Sekarang

Yang sudah selesai:

- auto generate `technical_analyses` dari `market_data`
- classic technical context
- SMC context objektif
- simpan `smc_summary` ke `raw_context_json`
- prompt Technical Agent
- endpoint pending technical analysis
- endpoint submit technical result
- Technical Agent runner
- test OpenClaw CLI
- test akses API dari VM OpenClaw
- dashboard utama baru
- DataTables ringan di halaman utama
- auth login/register/logout
- role dan permission dengan Spatie
- manajemen user dan role
- navbar sesuai urutan workflow
- fix mixed-content chart untuk domain HTTPS

Yang belum stabil / belum selesai:

- pastikan semua hasil scheduler baru selalu memuat `smc_summary`
- jalankan Technical Agent runner end-to-end sampai konsisten `TECHNICAL_COMPLETED`
- deploy RBAC/auth dengan benar di server/domain
- `fundamental_analyses`
- `risk_rules`
- `entry_rules`
- `manager_decisions`
- workflow final pembentukan `trade_signals` oleh Manager Agent

## 18. Langkah Lanjut yang Direkomendasikan

Urutan lanjutan yang paling aman:

1. Pastikan environment server/domain sinkron dengan dependency terbaru, terutama Spatie.
2. Abaikan atau bersihkan hanya row historis lama yang dibuat sebelum update SMC, bila masih mengganggu testing.
3. Restart scheduler CasaOS agar pasti memakai generator terbaru.
4. Test runner Technical Agent end-to-end pada analysis baru yang jelas punya SMC.
5. Verifikasi status:

```text
GENERATED -> SENT_TO_TECHNICAL_AGENT -> TECHNICAL_COMPLETED
```

6. Setelah Technical Agent stabil, lanjut desain tabel/workflow:

```text
fundamental_analyses
risk_rules
entry_rules
manager_decisions
```

7. Setelah itu, pindahkan pembentukan `trade_signals` sepenuhnya ke workflow Manager Agent.

## 19. Ringkasan Singkat untuk Handoff

Kalau ingin melanjutkan di chat lain, inti konteks terbarunya adalah:

```text
Laravel dashboard sudah bisa auto-generate technical_analyses dari market_data per pair/timeframe.
Technical context classic dan SMC sudah ada, lalu hasilnya dikirim ke Technical Agent OpenClaw lewat runner Python.
Technical Agent tidak membuat trade_signals.
Dashboard utama sudah diganti menjadi control room trading.
Auth + role + permission dengan Spatie sudah ditambahkan, termasuk halaman Users dan Roles.
Navbar sudah dirapikan mengikuti workflow, dan menu access control dipindah ke dropdown user.
Masalah utama yang tersisa sekarang adalah memastikan deploy server/domain sinkron dengan dependency terbaru dan memastikan workflow Technical Agent stabil end-to-end.
```
