<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearScheduleMutexCommand extends Command
{
    protected $signature = 'schedule:clear-mutex
                            {--all : Очистить все мьютексы (не только истекшие)}';

    protected $description = 'Clear expired or all schedule mutexes from cache_locks table';

    public function handle()
    {
        $this->info('🔍 Checking schedule mutexes...');

        try {
            $query = DB::table('cache_locks')
                ->where('key', 'like', '%schedule%');

            $allMutexes = $query->get();
            $expiredMutexes = $query->where('expiration', '<', time())->get();

            $this->info("📊 Found {$allMutexes->count()} schedule mutex(es)");
            $this->info("   - Active: " . ($allMutexes->count() - $expiredMutexes->count()));
            $this->info("   - Expired: {$expiredMutexes->count()}");

            if ($allMutexes->isEmpty()) {
                $this->info('✅ No mutexes found');
                return Command::SUCCESS;
            }

            // Display mutexes
            $this->newLine();
            $this->table(
                ['Key', 'Owner', 'Expires', 'Status'],
                $allMutexes->map(function ($mutex) {
                    $expired = $mutex->expiration < time();
                    return [
                        substr($mutex->key, 0, 50) . '...',
                        $mutex->owner,
                        date('Y-m-d H:i:s', $mutex->expiration),
                        $expired ? '🔴 EXPIRED' : '🟢 ACTIVE',
                    ];
                })->toArray()
            );

            // Clear mutexes
            if ($this->option('all')) {
                $deleted = $query->delete();
                $this->info("✅ Cleared all {$deleted} mutex(es)");
                Log::info('Schedule mutexes cleared (all)', ['count' => $deleted]);
            } else {
                $deleted = $query->where('expiration', '<', time())->delete();
                if ($deleted > 0) {
                    $this->info("✅ Cleared {$deleted} expired mutex(es)");
                    Log::info('Schedule mutexes cleared (expired)', ['count' => $deleted]);
                } else {
                    $this->info('ℹ️  No expired mutexes to clear');
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('ClearScheduleMutex command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}


