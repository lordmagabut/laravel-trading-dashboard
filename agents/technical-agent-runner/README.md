# Technical Agent Runner

Runner ini menghubungkan Laravel Control Tower dengan OpenClaw Technical Agent.

Workflow:

```text
GET Laravel /api/technical-analyses/pending
        |
        v
Compact raw_context_json menjadi prompt ringkas
        |
        v
openclaw agent --local --json
        |
        v
Parse meta.finalAssistantRawText
        |
        v
POST Laravel /api/technical-analyses/{id}/technical-result
```

## Setup di VM OpenClaw

```bash
cd technical-agent-runner
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
python technical_agent_runner.py --once
```

## Loop Worker

```bash
python technical_agent_runner.py
```

## Dry Run

Mode ini tidak memanggil OpenClaw dan tidak POST balik ke Laravel.

```bash
DRY_RUN=true python technical_agent_runner.py --once
```

## Output OpenClaw Yang Dipakai

Runner mengambil hasil dari:

```text
meta.finalAssistantRawText
```

Jika field itu kosong, runner mencoba fallback:

```text
payloads[0].text
```

Assistant harus mengembalikan JSON valid:

```json
{
  "decision": "NO_TRADE",
  "confidence": 50,
  "reason_summary": "Technical setup is not clear.",
  "reasons": ["Mixed SMC structure"],
  "technical_setup": {
    "entry_zone": null,
    "stop_loss_zone": null,
    "take_profit_zones": [],
    "invalidation": "No valid setup"
  },
  "agent_name": "OpenClaw Technical Agent",
  "agent_model": "technical-vm-01"
}
```
