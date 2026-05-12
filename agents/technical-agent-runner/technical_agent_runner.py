#!/usr/bin/env python3
import argparse
import json
import os
import shlex
import subprocess
import sys
import time
from pathlib import Path
from typing import Any

import requests


VALID_DECISIONS = {"BUY", "SELL", "NO_TRADE"}


def load_env(path: str = ".env") -> None:
    env_path = Path(path)
    if not env_path.exists():
        return

    for line in env_path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        key = key.strip()
        value = value.strip().strip('"').strip("'")
        os.environ.setdefault(key, value)


def env_bool(key: str, default: bool = False) -> bool:
    value = os.getenv(key)
    if value is None:
        return default

    return value.strip().lower() in {"1", "true", "yes", "on"}


def env_int(key: str, default: int) -> int:
    value = os.getenv(key)
    if value is None or value.strip() == "":
        return default

    return int(value)


def api_url(path: str) -> str:
    base_url = os.getenv("LARAVEL_API_BASE_URL", "http://127.0.0.1:8000/api").rstrip("/")
    return f"{base_url}/{path.lstrip('/')}"


def request_timeout() -> int:
    return env_int("REQUEST_TIMEOUT_SECONDS", 30)


def fetch_pending() -> list[dict[str, Any]]:
    limit = env_int("PENDING_LIMIT", 1)
    mark_sent = "0" if env_bool("DRY_RUN", False) else "1"
    url = api_url("/technical-analyses/pending")
    response = requests.get(
        url,
        params={"limit": limit, "mark_sent": mark_sent},
        headers={"Accept": "application/json"},
        timeout=request_timeout(),
    )
    response.raise_for_status()
    payload = decode_json_response(response, url)

    return payload.get("data", [])


def submit_result(analysis_id: int, result: dict[str, Any]) -> dict[str, Any]:
    url = api_url(f"/technical-analyses/{analysis_id}/technical-result")
    response = requests.post(
        url,
        json=result,
        headers={"Accept": "application/json"},
        timeout=request_timeout(),
    )
    response.raise_for_status()

    return decode_json_response(response, url)


def decode_json_response(response: requests.Response, url: str) -> dict[str, Any]:
    try:
        return response.json()
    except ValueError as exc:
        content_type = response.headers.get("content-type", "-")
        body = response.text[:800].replace("\n", "\\n")
        raise RuntimeError(
            "Expected JSON response from Laravel API, but got non-JSON response. "
            f"url={url} status={response.status_code} content_type={content_type} body_preview={body}"
        ) from exc


def compact_context(analysis: dict[str, Any]) -> dict[str, Any]:
    raw = analysis.get("raw_context_json") or {}
    execution_tf = analysis.get("execution_timeframe")
    smc = raw.get("smc") or {}
    bias = raw.get("bias") or {}
    execution_smc = smc.get(execution_tf) or {}
    execution_classic = bias.get(execution_tf) or {}

    return {
        "analysis_id": analysis.get("id"),
        "analysis_uuid": analysis.get("analysis_uuid"),
        "symbol": analysis.get("symbol"),
        "execution_timeframe": execution_tf,
        "context_candle_time": analysis.get("context_candle_time"),
        "current_price": analysis.get("current_price") or raw.get("current_price"),
        "classic_summary": raw.get("summary") or {
            "higher_timeframe_bias": analysis.get("higher_timeframe_bias"),
            "execution_bias": analysis.get("execution_bias"),
            "preferred_action": analysis.get("preferred_action"),
        },
        "smc_summary": raw.get("smc_summary") or {},
        "higher_timeframes": compact_higher_timeframes(smc, bias),
        "execution_classic": compact_classic_context(execution_classic),
        "execution_smc": compact_smc_context(execution_smc),
    }


def compact_higher_timeframes(smc: dict[str, Any], bias: dict[str, Any]) -> dict[str, Any]:
    result = {}

    for timeframe in ["D1", "H4", "H1"]:
        smc_item = smc.get(timeframe) or {}
        bias_item = bias.get(timeframe) or {}
        result[timeframe] = {
            "classic_bias": bias_item.get("bias"),
            "classic_score": bias_item.get("score"),
            "smc_bias": smc_item.get("bias"),
            "smc_score": smc_item.get("score"),
            "smc_structure": smc_item.get("structure"),
            "smc_last_event": get_nested(smc_item, ["last_event", "type"]),
            "premium_discount": get_nested(smc_item, ["premium_discount", "current_area"]),
        }

    return result


def compact_classic_context(item: dict[str, Any]) -> dict[str, Any]:
    return {
        "bias": item.get("bias"),
        "score": item.get("score"),
        "last_close": item.get("last_close"),
        "last_candle_time": item.get("last_candle_time"),
        "ema": item.get("ema"),
        "atr14": item.get("atr14"),
        "structure": item.get("structure"),
        "nearest_support": (item.get("levels") or {}).get("support", [])[:3],
        "nearest_resistance": (item.get("levels") or {}).get("resistance", [])[:3],
        "reason": item.get("reason", [])[:8],
    }


def compact_smc_context(item: dict[str, Any]) -> dict[str, Any]:
    zones = item.get("zones") or {}
    sweeps = item.get("liquidity_sweeps") or []
    events = item.get("events") or []

    return {
        "bias": item.get("bias"),
        "score": item.get("score"),
        "structure": item.get("structure"),
        "last_event": item.get("last_event"),
        "recent_events": events[-5:],
        "premium_discount": item.get("premium_discount"),
        "nearest_demand_zones": valid_recent_zones(zones.get("demand", []), limit=3),
        "nearest_supply_zones": valid_recent_zones(zones.get("supply", []), limit=3),
        "recent_liquidity_sweeps": sweeps[-5:],
        "support_resistance": item.get("support_resistance"),
        "reason": item.get("reason", [])[:8],
    }


def valid_recent_zones(zones: list[dict[str, Any]], limit: int) -> list[dict[str, Any]]:
    valid = [zone for zone in zones if not zone.get("invalidated")]

    return valid[-limit:]


def build_prompt(compact: dict[str, Any]) -> str:
    context_json = json.dumps(compact, ensure_ascii=False, separators=(",", ":"))

    return f"""
You are OpenClaw Technical Agent for a distributed trading bot.

Your role:
- Analyze ONLY technical context.
- Use objective SMC context first, then classic EMA/ATR context as support.
- You are NOT the final trade manager.
- Do NOT create trade signals.
- Return ONLY valid JSON. No markdown. No commentary outside JSON.

Decision rules:
- decision must be BUY, SELL, or NO_TRADE.
- BUY/SELL means technical setup exists, not final execution permission.
- Use NO_TRADE when structure is mixed, zone is invalidated, entry is late, risk is unclear, or SMC and HTF context conflict.
- Confidence must be an integer from 0 to 100.
- Prefer clear SMC reasons: BOS, CHoCH, liquidity sweep, demand/supply, premium/discount, invalidation.

Required JSON shape:
{{
  "decision": "BUY | SELL | NO_TRADE",
  "confidence": 0,
  "reason_summary": "short technical summary",
  "reasons": ["reason 1", "reason 2"],
  "technical_setup": {{
    "entry_zone": {{"from": null, "to": null}},
    "stop_loss_zone": {{"price": null}},
    "take_profit_zones": [],
    "invalidation": "what invalidates this technical setup"
  }},
  "agent_name": "OpenClaw Technical Agent",
  "agent_model": "technical-vm-01"
}}

Compact technical context JSON:
{context_json}
""".strip()


def run_openclaw(prompt: str, analysis_id: int) -> dict[str, Any]:
    command = os.getenv("OPENCLAW_COMMAND", "openclaw")
    session_prefix = os.getenv("OPENCLAW_SESSION_PREFIX", "technical-analysis")
    thinking = os.getenv("OPENCLAW_THINKING", "off")
    timeout = env_int("OPENCLAW_TIMEOUT_SECONDS", 900)
    model = os.getenv("OPENCLAW_MODEL", "").strip()

    args = [
        command,
        "agent",
        "--local",
        "--session-id",
        f"{session_prefix}-{analysis_id}",
        "--message",
        prompt,
        "--json",
        "--thinking",
        thinking,
        "--timeout",
        str(timeout),
    ]

    if model:
        args.extend(["--model", model])

    print(f"[openclaw] running: {shell_preview(args)}", flush=True)

    process = subprocess.run(
        args,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        timeout=timeout + 60,
        check=False,
    )

    if process.returncode != 0:
        raise RuntimeError(
            f"OpenClaw failed with exit code {process.returncode}: {process.stderr.strip()}"
        )

    stdout = strip_non_json_prefix(process.stdout)
    payload = json.loads(stdout)
    assistant_text = (
        get_nested(payload, ["meta", "finalAssistantRawText"])
        or get_nested(payload, ["meta", "finalAssistantVisibleText"])
        or get_nested(payload, ["payloads", 0, "text"])
    )

    if not assistant_text:
        raise RuntimeError("OpenClaw output did not include assistant text.")

    return parse_agent_json(assistant_text)


def parse_agent_json(text: str) -> dict[str, Any]:
    text = text.strip()

    if text.startswith("```"):
        text = text.strip("`")
        if text.startswith("json"):
            text = text[4:]
        text = text.strip()

    try:
        result = json.loads(text)
    except json.JSONDecodeError:
        start = text.find("{")
        end = text.rfind("}")
        if start < 0 or end < 0 or end <= start:
            raise
        result = json.loads(text[start : end + 1])

    return normalize_result(result)


def normalize_result(result: dict[str, Any]) -> dict[str, Any]:
    decision = str(result.get("decision", "NO_TRADE")).upper()
    if decision not in VALID_DECISIONS:
        decision = "NO_TRADE"

    confidence = result.get("confidence", 0)
    try:
        confidence = int(confidence)
    except (TypeError, ValueError):
        confidence = 0
    confidence = max(0, min(100, confidence))

    reasons = result.get("reasons", [])
    if not isinstance(reasons, list):
        reasons = [str(reasons)]

    technical_setup = result.get("technical_setup") or {}
    if not isinstance(technical_setup, dict):
        technical_setup = {}

    return {
        "decision": decision,
        "confidence": confidence,
        "reason_summary": str(result.get("reason_summary") or ""),
        "reasons": [str(reason) for reason in reasons],
        "technical_setup": technical_setup,
        "agent_name": str(result.get("agent_name") or "OpenClaw Technical Agent"),
        "agent_model": str(result.get("agent_model") or "technical-vm-01"),
    }


def strip_non_json_prefix(text: str) -> str:
    stripped = text.strip()
    start = stripped.find("{")
    if start > 0:
        return stripped[start:]

    return stripped


def get_nested(data: Any, path: list[Any]) -> Any:
    current = data
    for key in path:
        if isinstance(key, int) and isinstance(current, list):
            if key >= len(current):
                return None
            current = current[key]
            continue

        if not isinstance(current, dict) or key not in current:
            return None
        current = current[key]

    return current


def shell_preview(args: list[str]) -> str:
    preview = args[:]
    if "--message" in preview:
        message_index = preview.index("--message") + 1
        if message_index < len(preview):
            preview[message_index] = f"<prompt {len(preview[message_index])} chars>"

    return " ".join(shlex.quote(part) for part in preview)


def process_once() -> int:
    pending = fetch_pending()
    if not pending:
        print("[runner] no pending technical analyses.", flush=True)
        return 0

    dry_run = env_bool("DRY_RUN", False)

    for analysis in pending:
        analysis_id = int(analysis["id"])
        print(f"[runner] processing analysis #{analysis_id}", flush=True)
        compact = compact_context(analysis)
        prompt = build_prompt(compact)

        if dry_run:
            print(json.dumps(compact, ensure_ascii=False, indent=2), flush=True)
            print("[runner] dry run enabled; skipping OpenClaw and submit.", flush=True)
            continue

        result = run_openclaw(prompt, analysis_id)
        submit_payload = submit_result(analysis_id, result)
        status = get_nested(submit_payload, ["technical_analysis", "status"])
        print(f"[runner] submitted analysis #{analysis_id}, status={status}", flush=True)

    return len(pending)


def main() -> int:
    parser = argparse.ArgumentParser(description="OpenClaw Technical Agent Runner")
    parser.add_argument("--once", action="store_true", help="Process one poll cycle and exit.")
    parser.add_argument("--env", default=".env", help="Path to runner .env file.")
    args = parser.parse_args()

    load_env(args.env)

    if args.once:
        process_once()
        return 0

    interval = env_int("POLL_INTERVAL_SECONDS", 10)
    print(f"[runner] started. interval={interval}s", flush=True)

    while True:
        try:
            process_once()
        except KeyboardInterrupt:
            print("[runner] stopped.", flush=True)
            return 0
        except Exception as exc:
            print(f"[runner] error: {exc}", file=sys.stderr, flush=True)

        time.sleep(interval)


if __name__ == "__main__":
    raise SystemExit(main())
