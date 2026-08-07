<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;

/**
 * 読書計画の期日に応じてリマインダー通知を送信するコマンド。
 *
 * 期日3日前・期日当日・期日3日後に該当する読書計画を取得し、
 * 対象ユーザーへデータベース通知を送信する。
 */
class SendReadingPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-reading-plan-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reading plan reminder notifications.';

    /**
     * 読書計画の期日に応じたリマインダー通知を実行する。
     *
     * 期日3日前・期日当日・期日3日後の読書計画を対象として、
     * それぞれの通知種別を指定して通知処理を呼び出す。
     */
    public function handle(): int
    {
        $this->sendReminders(
            today()->addDays(3)->toDateString(),
            'before_3_days'
        );

        $this->sendReminders(
            today()->toDateString(),
            'due_today'
        );

        $this->sendReminders(
            today()->subDays(3)->toDateString(),
            'after_3_days'
        );

        return self::SUCCESS;
    }

    /**
     * 指定した期日に一致する読書計画のユーザーへ通知を送信する。
     *
     * 進行中または期限切れの読書計画を取得し、
     * 書籍情報とユーザー情報を読み込んだうえで通知する。
     *
     * @param  string  $targetDate  通知対象となる読書期日
     * @param  string  $type  通知の種類
     */
    public function sendReminders(string $targetDate, string $type): void
    {
        $readingPlans = ReadingPlan::with(['book', 'user'])
            ->reminderTarget()
            ->dueOn($targetDate)
            ->get();

        foreach ($readingPlans as $readingPlan) {
            $readingPlan->user->notify(
                new ReadingPlanReminderNotification(
                    $readingPlan,
                    $type
                )
            );
        }
    }
}
