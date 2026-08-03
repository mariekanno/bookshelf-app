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
            'timing' => $this->timing(),
            'title' => $this->title(),
            'body' => $this->message(),
        ];
    }

    private function timing(): string
    {
        return match ($this->type) {
            'before_3_days' => 'three_days_before',
            'due_today' => 'on_due_date',
            'after_3_days' => 'three_days_after',
            default => 'unknown',
        };
    }

    private function title(): string
    {
        return match ($this->type) {
            'before_3_days' => "「{$this->readingPlan->book->title}」の読書期日が近づいています。",
            'due_today' => "「{$this->readingPlan->book->title}」は今日が読書期日です。",
            'after_3_days' => "「{$this->readingPlan->book->title}」の読書期日を過ぎています。",
            default => '読書計画のお知らせ',
        };
    }

    private function message(): string
    {
        return match ($this->type) {
            'before_3_days' => "「{$this->readingPlan->book->title}」の読書期日まであと3日です。",
            'due_today' => "「{$this->readingPlan->book->title}」は今日が読書期日です。",
            'after_3_days' => "「{$this->readingPlan->book->title}」の読書期日を3日過ぎています。",
            default => '読書計画のお知らせです。',
        };
    }
}
