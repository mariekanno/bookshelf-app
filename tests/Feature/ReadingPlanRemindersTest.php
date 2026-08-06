<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::create(2026, 8, 6, 20, 0, 0)
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 期日3日前に進行中の読書計画へ通知されること
     */
    public function test_期日3日前に進行中の読書計画へ通知される(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            user: $user,
            targetDate: today()->addDays(3)->toDateString(),
            status: ReadingPlanStatus::InProgress
        );

        // Act
        $this->artisan('app:send-reading-plan-reminders')
            ->assertSuccessful();

        // Assert
        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (
                ReadingPlanReminderNotification $notification
            ) use ($user, $readingPlan): bool {
                $data = $notification->toArray($user);

                return $data['reading_plan_id'] === $readingPlan->id
                && $data['book_id'] === $readingPlan->book_id
                && $data['book_title'] === $readingPlan->book->title
                && $data['target_date']
                === today()->addDays(3)->toDateString()
                && $data['timing'] === 'three_days_before';
            }
        );
    }

    /**
     * 期日当日に進行中の読書計画へ通知されること
     */
    public function test_期日当日に進行中の読書計画へ通知される(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            user: $user,
            targetDate: today()->toDateString(),
            status: ReadingPlanStatus::InProgress
        );

        // Act
        $this->artisan('app:send-reading-plan-reminders')
            ->assertSuccessful();

        // Assert
        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (
                ReadingPlanReminderNotification $notification
            ) use ($user): bool {
                $data = $notification->toArray($user);

                return $data['timing'] === 'on_due_date';
            }
        );
    }

    /**
     * 期日3日後に期限切れの読書計画へ通知されること
     */
    public function test_期日3日後に期限切れの読書計画へ通知される(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create();

        $readingPlan = $this->createReadingPlan(
            user: $user,
            targetDate: today()->subDays(3)->toDateString(),
            status: ReadingPlanStatus::Overdue
        );

        // Act
        $this->artisan('app:send-reading-plan-reminders')
            ->assertSuccessful();

        // Assert
        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (
                ReadingPlanReminderNotification $notification
            ) use ($user, $readingPlan): bool {
                $data = $notification->toArray($user);

                return $data['reading_plan_id'] === $readingPlan->id
                && $data['book_id'] === $readingPlan->book_id
                && $data['book_title'] === $readingPlan->book->title
                && $data['target_date']
                === today()->subDays(3)->toDateString()
                && $data['timing'] === 'three_days_after';
            }
        );
    }

    /**
     * 進行中と期限切れの読書計画が通知対象になること
     */
    public function test_進行中と期限切れの読書計画が通知対象になる(): void
    {
        // Arrange
        Notification::fake();

        $inProgressUser = User::factory()->create();
        $overdueUser = User::factory()->create();

        $this->createReadingPlan(
            user: $inProgressUser,
            targetDate: today()->subDays(3)->toDateString(),
            status: ReadingPlanStatus::InProgress
        );

        $this->createReadingPlan(
            user: $overdueUser,
            targetDate: today()->subDays(3)->toDateString(),
            status: ReadingPlanStatus::Overdue
        );

        // Act
        $this->artisan('app:send-reading-plan-reminders')
            ->assertSuccessful();

        // Assert
        Notification::assertSentTo(
            $inProgressUser,
            ReadingPlanReminderNotification::class
        );

        Notification::assertSentTo(
            $overdueUser,
            ReadingPlanReminderNotification::class
        );
    }

    /**
     * 読了済みの読書計画には通知されないこと
     */
    public function test_読了済みの読書計画には通知されない(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create();

        $this->createReadingPlan(
            user: $user,
            targetDate: today()->subDays(3)->toDateString(),
            status: ReadingPlanStatus::Completed
        );

        // Act
        $this->artisan('app:send-reading-plan-reminders')
            ->assertSuccessful();

        // Assert
        Notification::assertNothingSentTo($user);
    }

    /**
     * 通知対象日以外の読書計画には通知されないこと
     */
    public function test_通知対象日以外の読書計画には通知されない(): void
    {
        // Arrange
        Notification::fake();

        $user = User::factory()->create();

        $this->createReadingPlan(
            user: $user,
            targetDate: today()->addDays(1)->toDateString(),
            status: ReadingPlanStatus::InProgress
        );

        // Act
        $this->artisan('app:send-reading-plan-reminders')
            ->assertSuccessful();

        // Assert
        Notification::assertNothingSentTo($user);
    }

    /**
     * テスト用の読書計画を作成する
     */
    private function createReadingPlan(
        User $user,
        string $targetDate,
        ReadingPlanStatus $status
    ): ReadingPlan {
        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        return ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'completed_at' => $status === ReadingPlanStatus::Completed
            ? now()
            : null,
            'status' => $status,
        ]);
    }
}
