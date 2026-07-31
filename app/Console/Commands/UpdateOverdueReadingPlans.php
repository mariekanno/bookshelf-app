<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

class UpdateOverdueReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-overdue-reading-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update overdue reading plans.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        ReadingPlan::inProgress()
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Overdue,
            ]);

        return self::SUCCESS;
    }
}
