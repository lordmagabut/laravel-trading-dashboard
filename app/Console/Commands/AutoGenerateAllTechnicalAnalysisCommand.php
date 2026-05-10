<?php

namespace App\Console\Commands;

use App\Models\TradingBotPair;
use App\Services\TechnicalAnalysisGeneratorService;
use Illuminate\Console\Command;

class AutoGenerateAllTechnicalAnalysisCommand extends Command
{
    protected $signature = 'technical-analysis:auto-generate-all';

    protected $description = 'Auto generate technical analyses for all enabled trading bot pairs.';

    public function handle(TechnicalAnalysisGeneratorService $generator): int
    {
        $pairs = TradingBotPair::query()
            ->where('enabled', true)
            ->where('auto_generate', true)
            ->orderBy('symbol')
            ->get();

        if ($pairs->isEmpty()) {
            $this->info('No active trading bot pairs.');
            return self::SUCCESS;
        }

        foreach ($pairs as $pair) {
            $symbol = strtoupper($pair->symbol);
            $timeframe = strtoupper($pair->entry_timeframe);

            $pair->update([
                'last_checked_at' => now(),
            ]);

            try {
                $analysis = $generator->generate($symbol, $timeframe);

                if (! $analysis) {
                    $this->line("Skipped {$symbol} {$timeframe}: no new candle or already generated.");
                    continue;
                }

                $pair->update([
                    'last_generated_at' => now(),
                    'last_generated_candle_time' => $analysis->context_candle_time,
                ]);

                $this->info("Generated analysis #{$analysis->id} for {$symbol} {$timeframe}.");

            } catch (\Throwable $e) {
                $this->error("Failed {$symbol} {$timeframe}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}