<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * 読書計画の期日に応じたリマインダー通知を生成するクラス。
 *
 * 期日3日前・期日当日・期日3日後の通知内容を作成し、
 * データベース通知として保存する。
 */
class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    /**
     * 通知インスタンスを生成する。
     *
     * @param  ReadingPlan  $readingPlan  通知対象の読書計画
     * @param  string  $type  通知の種類
     */
    public function __construct(
        private ReadingPlan $readingPlan,
        private string $type,
    ) {}

    /**
     * 通知の配信方法を指定する。
     *
     * @param  object  $notifiable  通知先のユーザー
     * @return array<int, string> 通知チャンネル
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * データベースへ保存する通知内容を配列で返す。
     *
     * @param  object  $notifiable  通知先のユーザー
     * @return array<string, mixed> 通知データ
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

    /**
     * 通知種別に対応するタイミング識別子を返す。
     */
    private function timing(): string
    {
        return match ($this->type) {
            'before_3_days' => 'three_days_before',
            'due_today' => 'on_due_date',
            'after_3_days' => 'three_days_after',
            default => 'unknown',
        };
    }

    /**
     * 通知種別に応じた通知タイトルを返す。
     */
    private function title(): string
    {
        return match ($this->type) {
            'before_3_days' => "「{$this->readingPlan->book->title}」の読書期日が近づいています。",
            'due_today' => "「{$this->readingPlan->book->title}」は今日が読書期日です。",
            'after_3_days' => "「{$this->readingPlan->book->title}」の読書期日を過ぎています。",
            default => '読書計画のお知らせ',
        };
    }

    /**
     * 通知種別に応じた本文を返す。
     */
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
