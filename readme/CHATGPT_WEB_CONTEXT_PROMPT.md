# ChatGPT Web Context Prompt

Gunakan prompt ini saat membuka ChatGPT web untuk melanjutkan diskusi teknis proyek ini.

```text
Saya sedang melanjutkan proyek Laravel bernama laravel-trading-dashboard / OpenClaw.

Konteks umum:
- Aplikasi ini adalah dashboard trading dan bot orchestration berbasis Laravel.
- Domain produksi: trade.nomaden.site.
- Fokus utama saat ini adalah Technical Analysis workflow, SMC context, prompt Technical Agent, dan Bot Pair Settings.
- Hindari mengubah perilaku trading nyata tanpa mengecek service, migration, dan workflow terkait.

Arsitektur penting:
1. Technical Analysis
- Pair diatur di tabel `trading_bot_pairs`.
- Pair dapat memiliki `agent_risk_mode`: `conservative`, `balanced`, atau `aggressive`.
- Risk mode memengaruhi gaya rekomendasi Technical Agent, bukan kalkulasi SMC inti dan bukan izin eksekusi final.
- Hasil analisis disimpan di `technical_analyses`.
- Detail signal disimpan di `trade_signals`.

2. Service utama
- `app/Services/TechnicalContextService.php`
  Membuat konteks teknikal klasik per timeframe.
- `app/Services/SmcContextService.php`
  Membuat konteks SMC yang lebih kaya: swing/internal structure, BOS/CHoCH, liquidity sweep, order block, FVG, equal highs/lows, premium/discount, strong/weak levels.
- `app/Services/TechnicalContextCompactorService.php`
  Membuat versi context yang ringkas untuk prompt AI agar token/ukuran payload terkendali.
- `app/Services/TechnicalAnalysisPromptService.php`
  Menyusun prompt Technical Agent.
- `app/Services/TechnicalAnalysisGeneratorService.php`
  Menjalankan workflow generate analysis, menyimpan raw context, prompt, AI response, dan trade signal.

3. Context dan prompt AI
- `raw_context_json` tetap menyimpan konteks penuh untuk audit/debug.
- Prompt AI memakai compact JSON, bukan full historical dump.
- Compact context berisi struktur utama:
  - `symbol`
  - `execution_timeframe`
  - `generated_at_utc`
  - `context_candle_time`
  - `current_price`
  - `agent_profile`
  - `classic_summary`
  - `smc_summary`
  - `conflicts`
  - `higher_timeframes`
  - `execution_timeframe_context`
  - `supporting_timeframes`
- Prompt scheduler dan manual generate harus konsisten membawa:
  - Technical Agent profile
  - Risk mode
  - Policy guidance
  - compact Technical Context JSON

4. Technical Agent profile
- `conservative`: lebih selektif, konflik klasik/SMC lebih sering jadi veto.
- `balanced`: default, mengizinkan SMC override jika konteks jelas.
- `aggressive`: SMC boleh override konflik klasik saat HTF SMC dan internal execution mendukung setup; neutral execution boleh memakai LIMIT/STOP dari FVG/order block/liquidity context; MARKET tetap butuh momentum dan risk-reward jelas.
- Agent profile hanya memengaruhi rekomendasi AI, bukan auto execution permission.

5. SMC summary saat ini
- SMC summary dapat berbeda dari classic summary.
- Classic bias bisa netral, sementara SMC HTF bisa bullish/bearish.
- `preferred_action` SMC dapat berupa `LOOK_FOR_BUY`, `LOOK_FOR_SELL`, atau `NO_TRADE`.
- Jika context mixed, late entry, dekat invalidation, atau risk-reward buruk, Technical Agent tetap harus mengembalikan `NO_TRADE`.

6. Scheduler dan manual generate
- Scheduler memanggil workflow yang sama dengan manual generate.
- Manual generate di controller juga resolve `agent_profile` dari pair.
- `TechnicalContextController` API juga menyertakan agent profile default atau dari pair.
- Pastikan perubahan prompt/service menjaga konsistensi manual generate, scheduler, dan API preview.

7. UI dan performa
- Halaman list `technical-analyses` dan Bot Pair Settings pernah terkena HTTP 500 karena memuat payload besar seperti `raw_context_json`, `prompt_text`, dan `ai_response_json`.
- Index/list page harus memilih kolom kecil saja dan memakai count ringkas, bukan eager load payload besar.
- Detail page boleh memuat payload penuh.
- Setelah deploy perubahan route/view/controller, jalankan clear cache Laravel bila perlu.

8. Hal yang sudah diperbaiki
- `SmcContextService` sudah di-upgrade ke rich SMC context.
- `TechnicalContextCompactorService` sudah ditambahkan.
- Prompt Technical Agent sudah memakai compact context dan agent profile.
- Bot Pair Settings sudah punya `agent_risk_mode`.
- Manual generate, scheduler, dan API context sudah resolve agent profile.
- List technical analyses dan bot pair settings sudah dioptimasi agar tidak memuat payload besar di index.
- `php artisan test` terakhir lulus 7 test.
- File handoff root `PROJECT_RESUME_HANDOFF.md` sudah dihapus; handoff aktif hanya di folder `readme`.

9. File referensi utama
- `readme/PROJECT_RESUME_HANDOFF.md`
- `readme/WORKFLOW.md`
- `README.md`
- `app/Services/SmcContextService.php`
- `app/Services/TechnicalContextCompactorService.php`
- `app/Services/TechnicalAnalysisPromptService.php`
- `app/Services/TechnicalAnalysisGeneratorService.php`
- `app/Models/TradingBotPair.php`
- `app/Http/Controllers/TechnicalAnalysisWorkflowController.php`
- `app/Http/Controllers/BotPairSettingController.php`

10. Fokus kalau melanjutkan pekerjaan
- Validasi end-to-end Technical Agent: scheduler, manual generate, API preview, dan detail page.
- Pastikan migration `agent_risk_mode` sudah jalan di environment target.
- Pastikan production sudah menjalankan `php artisan optimize:clear` setelah deploy.
- Jika ada HTTP 500 lagi, cek `storage/logs/laravel.log` dan pastikan list page tidak memuat JSON besar.
- Setelah Technical Agent stabil, lanjutkan ke Fundamental Analysis dan Sentiment Analysis.
```
