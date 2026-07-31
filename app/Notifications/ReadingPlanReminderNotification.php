<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private ReadingPlan $readingPlan,
        private string $type,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'book_title' => $this->readingPlan->book->title,
            'target_date' => $this->readingPlan->target_date->format('Y-m-d'),
            'type' => $this->type,
            'message' => $this->message(),
        ];
    }

    public function message(): string
    {
        return match ($this->type) {
            'before_3_days' => "「{$this->readingPlan->book->title}」の読書期日まであと3日です。",
            'due_today' => "「{$this->readingPlan->book->title}」は今日が読書期日です。",
            'after_3_days' => "「{$this->readingPlan->book->title}」の読書期日を3日過ぎています。",
            default => '読書計画のお知らせです。',
        };
    }
}
