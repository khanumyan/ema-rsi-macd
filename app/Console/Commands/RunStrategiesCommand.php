<?php

namespace App\Console\Commands;

use App\Models\UserStrategy;
use App\Services\StrategyEngineService;
use Illuminate\Console\Command;

class RunStrategiesCommand extends Command
{
    protected $signature   = 'strategies:run {--strategy= : Run a single strategy by ID}';
    protected $description = 'Run all active user strategies and generate signals';

    public function handle(StrategyEngineService $engine): int
    {
        $query = UserStrategy::where('is_active', true)
            ->with(['buyConditions.indicator', 'sellConditions.indicator', 'profile']);

        if ($id = $this->option('strategy')) {
            $query->where('id', $id);
        }

        $strategies = $query->get();

        if ($strategies->isEmpty()) {
            $this->info('No active strategies found.');
            return 0;
        }

        $this->info("Running {$strategies->count()} active strategies...");
        $bar = $this->output->createProgressBar($strategies->count());
        $bar->start();

        $signalCount = 0;
        foreach ($strategies as $strategy) {
            try {
                $signals = $engine->runStrategy($strategy);
                $signalCount += count($signals);
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Strategy #{$strategy->id} ({$strategy->name}): {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Generated {$signalCount} new signals.");

        return 0;
    }
}
