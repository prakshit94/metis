<?php

namespace App\Console\Commands;

use App\Models\Target;
use App\Services\TargetAchievementService;
use Illuminate\Console\Command;

class RecalculateTargetAchievements extends Command
{
    protected $signature = 'targets:recalculate
                            {--metric= : Only recalculate a specific metric_type}
                            {--period= : Only recalculate a specific period_type (daily/monthly/yearly)}
                            {--id=     : Recalculate a single target by ID}';

    protected $description = 'Recalculate achieved_amount for all targets from source data (orders, invoices, payments, calls)';

    public function handle(TargetAchievementService $service): int
    {
        $query = Target::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        if ($metric = $this->option('metric')) {
            $query->where('metric_type', $metric);
        }

        if ($period = $this->option('period')) {
            $query->where('period_type', $period);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No targets found matching the given filters.');
            return self::SUCCESS;
        }

        $this->info("Recalculating {$total} target(s)…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $updated = 0;

        $query->each(function (Target $target) use ($service, $bar, &$updated) {
            $before = (float) $target->achieved_amount;
            $service->recalculate($target);
            $target->refresh();

            if (round((float) $target->achieved_amount, 2) !== round($before, 2)) {
                $updated++;
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$updated} target(s) had their achieved_amount updated.");

        return self::SUCCESS;
    }
}
