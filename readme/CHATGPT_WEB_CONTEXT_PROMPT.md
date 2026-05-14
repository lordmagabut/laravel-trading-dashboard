# Prompt Konteks Awal untuk ChatGPT Web

Salin prompt di bawah ini ke chat baru di ChatGPT Web:

```text
Saya sedang melanjutkan proyek Laravel bernama `laravel-trading-dashboard`, yaitu dashboard trading bot AI terdistribusi.

Tolong jadikan konteks berikut sebagai baseline proyek, lalu bantu saya melanjutkan pekerjaan tanpa mengulang analisis dari nol.

Konteks proyek:

1. Arsitektur utama:
   - Python / MT5 feeder mengisi tabel `market_data`
   - Laravel dashboard membaca `market_data`
   - Laravel membuat `technical_analyses`
   - Technical Agent OpenClaw membaca pending analysis
   - Technical Agent hanya mengembalikan hasil analisa teknikal
   - Technical Agent TIDAK membuat `trade_signals`
   - Ke depan `trade_signals` harus dibuat oleh workflow Manager Agent

2. Workflow teknikal yang sudah ada:
   - Pair diatur di `trading_bot_pairs`
   - Scheduler menjalankan `php artisan technical-analysis:auto-generate-all`
   - Laravel generate `technical_analyses` dari candle terbaru per pair/timeframe
   - Status yang dipakai:
     - `GENERATED`
     - `SENT_TO_TECHNICAL_AGENT`
     - `TECHNICAL_COMPLETED`
     - `FAILED`
   - API yang sudah ada:
     - `GET /api/technical-analyses/pending`
     - `POST /api/technical-analyses/{id}/technical-result`
     - `POST /api/technical-analyses/{id}/ai-result` sebagai alias kompatibilitas

3. Technical context:
   - Sudah ada classic context dan SMC context
   - Service penting:
     - `TechnicalContextService`
     - `SmcContextService`
     - `TechnicalAnalysisGeneratorService`
     - `TechnicalAnalysisPromptService`
   - `raw_context_json` sekarang sudah memuat:
     - `bias`
     - `summary`
     - `smc`
     - `smc_summary`
   - Kondisi terbaru: hasil `auto-generate` terbaru sudah terbukti membawa SMC context dan `smc_summary`
   - Catatan historis: pernah ada row lama sebelum update SMC yang belum punya `smc_summary`, tetapi itu bukan baseline utama saat ini

4. Technical Agent:
   - Runner Python sudah dibuat di `agents/technical-agent-runner/technical_agent_runner.py`
   - Runner:
     - GET pending dari Laravel
     - compact context
     - panggil OpenClaw CLI
     - parse JSON hasil OpenClaw
     - POST balik ke Laravel
   - Command OpenClaw yang dipakai:
     - `openclaw agent --local --session-id technical-analysis-<id> --message "<prompt>" --json --thinking off --timeout 900`
   - Output OpenClaw diparse dari `meta.finalAssistantRawText`

5. Dashboard/UI:
   - Homepage `/` sudah diganti menjadi dashboard trading / control room
   - Dashboard menampilkan:
     - chart market utama
     - ringkasan kondisi market
     - workflow summary
     - recent technical analyses
     - recent trade signals
   - `Feed terakhir` di dashboard sudah ditampilkan sesuai timezone browser user
   - Halaman list utama (`Technical Analyses`, `Signal Dashboard`, `Bot Pair Settings`) sudah memakai DataTables ringan client-side

6. Navbar:
   - Sudah dirapikan sesuai urutan workflow:
     - `Dashboard`
     - `Bot Pair Settings`
     - `Workflow`
       - `Market Chart`
       - `Technical Context`
       - `Technical Analyses`
       - `Signal Dashboard`
   - `Users` dan `Roles` dipindah ke dropdown user
   - Logout hanya ada di dropdown user

7. Auth / RBAC:
   - Sudah memakai `spatie/laravel-permission`
   - Auth login/register/logout sudah ada
   - Flow RBAC yang dipakai sekarang:
     - `User -> Role -> Permissions`
   - Role default:
     - `super-admin`
     - `admin`
     - `analyst`
     - `reviewer`
     - `viewer`
   - Seeder default admin:
     - email: `admin@nomaden.site`
     - password: `ChangeMe123!`
   - Halaman `Users` dan `Roles` sudah ada

8. Masalah penting yang sudah diketahui:
   - Secara historis pernah ada analysis lama yang belum punya `smc_summary`, tetapi hasil auto-generate terbaru sudah membawa SMC
   - Scheduler CasaOS perlu direstart bila kode generator berubah
   - Akses via domain HTTPS sempat kena mixed-content pada fetch chart, dan sudah diperbaiki dengan route relatif
   - Deploy domain/server sempat error `Trait "Spatie\\Permission\\Traits\\HasRoles" not found`, yang menunjukkan dependency server/container belum sinkron dengan package Spatie terbaru

9. Fokus kerja berikutnya:
   - pertahankan agar hasil scheduler terus konsisten mengandung `smc_summary`
   - stabilkan workflow Technical Agent end-to-end:
     - `GENERATED -> SENT_TO_TECHNICAL_AGENT -> TECHNICAL_COMPLETED`
   - setelah itu baru lanjut ke:
     - `fundamental_analyses`
     - `risk_rules`
     - `entry_rules`
     - `manager_decisions`
     - workflow Manager Agent

Saat menjawab, anggap seluruh konteks di atas sudah benar sebagai kondisi proyek terakhir. Tolong bantu saya melanjutkan dari state ini, bukan dari desain awal.
```

## Catatan

Kalau ingin versi lengkap, referensi utamanya ada di:

- [PROJECT_RESUME_HANDOFF.md](C:/np/laravel-trading-dashboard/PROJECT_RESUME_HANDOFF.md)
- [readme/PROJECT_RESUME_HANDOFF.md](C:/np/laravel-trading-dashboard/readme/PROJECT_RESUME_HANDOFF.md)
