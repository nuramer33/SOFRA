<?php

namespace App\Console\Commands;

use App\Models\Shop;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes analytics counters older than each shop's own retention period.
 */
#[Signature('sofra:prune-analytics')]
#[Description('Delete analytics counters older than each shop\'s retention period')]
class PruneAnalytics extends Command
{
    public function handle(): int
    {
        $deleted = 0;

        Shop::query()->select(['id', 'analytics_retention_days'])->chunkById(200, function ($shops) use (&$deleted) {
            foreach ($shops->groupBy('analytics_retention_days') as $days => $group) {
                $deleted += DB::table('analytics_dailies')
                    ->whereIn('shop_id', $group->modelKeys())
                    ->where('day', '<', today()->subDays((int) $days)->toDateString())
                    ->delete();
            }
        });

        $this->info("Deleted {$deleted} old analytics rows.");

        return self::SUCCESS;
    }
}
