# Fundamental Agent Runner

Runner ini menghubungkan Laravel Control Tower dengan OpenClaw Fundamental Agent.

Workflow:

```text
GET Laravel /api/fundamental-analyses/pending
        |
        v
Build prompt dari raw_context_json
        |
        v
openclaw agent --local --json
        |
        v
Parse meta.finalAssistantRawText
        |
        v
POST Laravel /api/fundamental-analyses/{id}/submit-result
```

## Setup di VM OpenClaw

```bash
cd fundamental-agent-runner
cp .env.example .env
nano .env
python3 -m venv .venv
source .venv/bin/activate
python -m pip install requests
```

Isi `LARAVEL_API_BASE_URL` dengan alamat Laravel dari VM OpenClaw, contoh:

```env
LARAVEL_API_BASE_URL=http://192.168.1.50:8000/api
```

## Test Sekali Jalan

```bash
python fundamental_agent_runner.py --once
```

## Loop Worker

```bash
python fundamental_agent_runner.py
```

## Dry Run

Mode ini tidak memanggil OpenClaw dan tidak POST balik ke Laravel.

```bash
DRY_RUN=true python fundamental_agent_runner.py --once
```

## Output OpenClaw Yang Dipakai

Runner mengambil hasil dari:

```text
meta.finalAssistantRawText
```

Assistant harus mengembalikan JSON valid:

```json
{
  "fundamental_bias": "neutral",
  "news_risk_level": "medium",
  "sentiment_bias": "bearish",
  "avoid_trade": false,
  "confidence": 65,
  "reason_summary": "Mixed fundamental signals.",
  "reasons_json": ["Upcoming FOMC meeting", "Bearish sentiment in commodities"],
  "agent_name": "OpenClaw Fundamental Agent",
  "agent_model": "fundamental-vm-01"
}
```