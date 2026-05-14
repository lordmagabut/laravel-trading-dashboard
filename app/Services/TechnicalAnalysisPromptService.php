<?php

namespace App\Services;

class TechnicalAnalysisPromptService
{
    public function __construct(
        protected TechnicalContextCompactorService $contextCompactorService
    ) {}

    public function build(array $context): string
    {
        $symbol = $context['symbol'] ?? 'UNKNOWN';
        $executionTimeframe = $context['execution_timeframe'] ?? 'UNKNOWN';
        $compactContext = $this->contextCompactorService->compact($context);

        $higherBias = $context['higher_timeframe_bias']
            ?? data_get($context, 'bias.higher_timeframe_bias')
            ?? data_get($context, 'summary.higher_timeframe_bias')
            ?? 'neutral';

        $executionBias = $context['execution_bias']
            ?? data_get($context, 'bias.execution_bias')
            ?? data_get($context, 'summary.execution_bias')
            ?? 'neutral';

        $preferredAction = $context['preferred_action']
            ?? data_get($context, 'summary.preferred_action')
            ?? 'WAIT';

        $currentPrice = $context['current_price']
            ?? data_get($context, 'price.current')
            ?? data_get($context, 'latest.close')
            ?? null;

        $smcHigherBias = data_get($context, 'smc_summary.higher_timeframe_bias', 'neutral');
        $smcExecutionBias = data_get($context, 'smc_summary.execution_bias', 'neutral');
        $smcExecutionStructure = data_get($context, 'smc_summary.execution_structure', 'unknown');
        $smcLastEvent = data_get($context, 'smc_summary.execution_last_event', 'none');
        $smcPreferredAction = data_get($context, 'smc_summary.preferred_action', 'NO_TRADE');
        $riskMode = data_get($context, 'agent_profile.risk_mode', 'balanced');
        $policyGuidance = $this->policyGuidance($riskMode);

        $contextJson = json_encode($compactContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are OpenClaw Technical Agent.

Your task:
Analyze the provided technical context and return ONLY valid JSON.

Trading instrument:
- Symbol: {$symbol}
- Execution timeframe: {$executionTimeframe}
- Higher timeframe bias: {$higherBias}
- Execution bias: {$executionBias}
- Preferred action: {$preferredAction}
- Current price: {$currentPrice}

Objective SMC context:
- SMC higher timeframe bias: {$smcHigherBias}
- SMC execution bias: {$smcExecutionBias}
- SMC execution structure: {$smcExecutionStructure}
- SMC last execution event: {$smcLastEvent}
- SMC preferred action: {$smcPreferredAction}

Technical Agent profile:
- Risk mode: {$riskMode}
- Policy: {$policyGuidance}

Rules:
1. Decision must be one of: BUY, SELL, NO_TRADE.
2. Follow the Technical Agent profile when deciding how selective or aggressive to be.
3. If context is unclear, mixed, late entry, near invalidation, or bad risk-reward, return NO_TRADE.
4. Do not force trade.
5. Use technical reasons from SMC structure, BOS, CHoCH, liquidity sweep, supply/demand zones, EMA, ATR, swing, support/resistance, and multi-timeframe bias.
6. Return JSON only. No markdown. No explanation outside JSON.
7. The JSON below is compacted for decision-making. Treat full historical lists as intentionally omitted.

Expected JSON format:

{
  "decision": "BUY | SELL | NO_TRADE",
  "confidence": 0,
  "reason_summary": "short explanation",
  "reasons": [
    "reason 1",
    "reason 2"
  ],
  "signal": {
    "entry_type": "MARKET | LIMIT | STOP | NONE",
    "entry_price": null,
    "stop_loss": null,
    "take_profit_1": null,
    "take_profit_2": null,
    "take_profit_3": null,
    "risk_reward": null,
    "risk_percent": 1,
    "lot_size": null,
    "invalidation": "what invalidates this setup"
  }
}

Technical Context JSON:
{$contextJson}
PROMPT;
    }

    private function policyGuidance(string $riskMode): string
    {
        return match ($riskMode) {
            'conservative' => 'Require strong classic and SMC alignment. Treat classic-vs-SMC conflict or neutral execution as a veto unless momentum and risk-reward are exceptional. Prefer NO_TRADE over early entries.',
            'aggressive' => 'SMC may override classic conflict when higher timeframe SMC and execution internal structure support the setup. Neutral execution can allow LIMIT or STOP entries from valid FVG/order-block/liquidity context. MARKET entries still require clear momentum and valid risk-reward.',
            default => 'Allow SMC to override mild classic conflict when higher timeframe SMC is strong, but prefer LIMIT or STOP entries when execution bias is neutral. Require clear invalidation and valid risk-reward.',
        };
    }
}
