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


VALID_FUNDAMENTAL_BIASES = {"bullish", "bearish", "neutral"}
VALID_NEWS_RISK_LEVELS = {"low", "medium", "high"}
VALID_SENTIMENT_BIASES = {"bullish", "bearish", "neutral"}


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
    url = api_url("/fundamental-analyses/pending")
    response = requests.get(
        url,
        params={"limit": limit},
        headers={"Accept": "application/json"},
        timeout=request_timeout(),
    )
    response.raise_for_status()
    payload = decode_json_response(response, url)

    return payload.get("data", [])


def submit_result(analysis_id: int, result: dict[str, Any]) -> dict[str, Any]:
    url = api_url(f"/fundamental-analyses/{analysis_id}/submit-result")
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


def build_prompt(analysis: dict[str, Any]) -> str:
    context_json = json.dumps(analysis.get("raw_context_json") or {}, ensure_ascii=False, separators=(",", ":"))

    return f"""
You are OpenClaw Fundamental Agent for a distributed trading bot.

Your role:
- Analyze ONLY fundamental context: news, economic calendar, sentiment, macro indicators.
- You are NOT the final trade manager.
- Do NOT create trade signals.
- Return ONLY valid JSON. No markdown. No commentary outside JSON.

Context:
{context_json}

Output JSON with keys: fundamental_bias, news_risk_level, sentiment_bias, avoid_trade, confidence, reason_summary, reasons_json, agent_name, agent_model.
"""


def call_openclaw(prompt: str) -> str:
    command = [
        "openclaw",
        "agent",
        "--local",
        "--session-id",
        f"fundamental-analysis-{int(time.time())}",
        "--message",
        prompt,
        "--json",
        "--thinking",
        "off",
        "--timeout",
        "900",
    ]

    result = subprocess.run(
        command,
        capture_output=True,
        text=True,
        timeout=env_int("OPENCLAW_TIMEOUT_SECONDS", 900),
    )

    if result.returncode != 0:
        stderr = result.stderr.strip()
        stdout = result.stdout.strip()
        raise RuntimeError(f"OpenClaw failed: exit_code={result.returncode} stderr={stderr} stdout={stdout}")

    return result.stdout.strip()


def parse_openclaw_response(raw_response: str) -> dict[str, Any]:
    try:
        parsed = json.loads(raw_response)
        final_text = parsed.get("meta", {}).get("finalAssistantRawText", "")
        if not final_text:
            raise ValueError("No finalAssistantRawText in response")

        return json.loads(final_text)
    except (json.JSONDecodeError, KeyError) as exc:
        raise RuntimeError(f"Failed to parse OpenClaw JSON response: {exc}") from exc


def validate_result(result: dict[str, Any]) -> None:
    required_keys = {
        "fundamental_bias",
        "news_risk_level",
        "sentiment_bias",
        "avoid_trade",
        "confidence",
        "reason_summary",
        "reasons_json",
    }

    missing = required_keys - set(result.keys())
    if missing:
        raise ValueError(f"Missing required keys in result: {missing}")

    if result["fundamental_bias"] not in VALID_FUNDAMENTAL_BIASES:
        raise ValueError(f"Invalid fundamental_bias: {result['fundamental_bias']}")

    if result["news_risk_level"] not in VALID_NEWS_RISK_LEVELS:
        raise ValueError(f"Invalid news_risk_level: {result['news_risk_level']}")

    if result["sentiment_bias"] not in VALID_SENTIMENT_BIASES:
        raise ValueError(f"Invalid sentiment_bias: {result['sentiment_bias']}")

    if not isinstance(result["avoid_trade"], bool):
        raise ValueError(f"avoid_trade must be boolean: {result['avoid_trade']}")

    confidence = result["confidence"]
    if not isinstance(confidence, int) or not (0 <= confidence <= 100):
        raise ValueError(f"confidence must be int 0-100: {confidence}")


def main() -> None:
    parser = argparse.ArgumentParser(description="Fundamental Agent Runner")
    parser.add_argument("--dry-run", action="store_true", help="Dry run mode")
    parser.add_argument("--limit", type=int, default=1, help="Number of analyses to process")
    args = parser.parse_args()

    load_env()

    if args.dry_run:
        os.environ["DRY_RUN"] = "1"

    if env_bool("DRY_RUN"):
        print("DRY RUN MODE: Will not submit results to Laravel")

    try:
        pending = fetch_pending()
        if not pending:
            print("No pending fundamental analyses")
            return

        for analysis in pending[: args.limit]:
            analysis_id = analysis["id"]
            print(f"Processing fundamental analysis {analysis_id} for {analysis['symbol']}")

            prompt = build_prompt(analysis)
            raw_response = call_openclaw(prompt)
            result = parse_openclaw_response(raw_response)
            validate_result(result)

            print(f"Result: bias={result['fundamental_bias']} risk={result['news_risk_level']} confidence={result['confidence']}")

            if not env_bool("DRY_RUN"):
                submit_result(analysis_id, result)
                print(f"Submitted result for analysis {analysis_id}")
            else:
                print(f"DRY RUN: Would submit result for analysis {analysis_id}")

    except Exception as exc:
        print(f"Error: {exc}", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()