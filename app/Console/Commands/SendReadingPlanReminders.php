<?php

namespace App\Console\Commands;

use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Console\Command;

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
     * Execute the console command.
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
