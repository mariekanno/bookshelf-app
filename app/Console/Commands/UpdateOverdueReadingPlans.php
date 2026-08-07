<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Console\Command;

/**
 * 期日を過ぎた読書計画を期限切れへ更新するコマンド。
 *
 * 進行中の読書計画のうち、期日が現在日より前のものを対象として、
 * ステータスを期限切れへ変更する。
 */
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
     * 期日を過ぎた進行中の読書計画を期限切れへ更新する。
     *
     * 読了済みや、期日が当日以降の読書計画は更新対象に含めない。
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
