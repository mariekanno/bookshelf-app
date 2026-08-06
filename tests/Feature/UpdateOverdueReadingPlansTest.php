<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateOverdueReadingPlansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト中の現在日時を固定する
        Carbon::setTestNow(
            Carbon::create(2026, 8, 6, 20, 0, 0)
        );
    }

    protected function tearDown(): void
    {
        // 固定した日時を解除する
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 期日を通過した進行中の読書計画が期限切れに変更されること
     */
    public function test_期日を通過した進行中の読書計画が期限切れに変更される(): void
    {
        // Arrange
        $readingPlan = $this->createReadingPlan(
            targetDate: today()->subDay()->toDateString(),
            status: ReadingPlanStatus::InProgress
        );

        // Act
        $this->artisan('app:update-overdue-reading-plans')
            ->assertSuccessful();

        // Assert
        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Overdue,
            $readingPlan->status
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Overdue->value,
        ]);
    }

    /**
     * 読了済みの読書計画は期限切れへ変更されないこと
     */
    public function test_読了済みの読書計画は期限切れへ変更されない(): void
    {
        // Arrange
        $readingPlan = $this->createReadingPlan(
            targetDate: today()->subDay()->toDateString(),
            status: ReadingPlanStatus::Completed
        );

        // Act
        $this->artisan('app:update-overdue-reading-plans')
            ->assertSuccessful();

        // Assert
        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Overdue->value,
        ]);
    }

    /**
     * 毎日20時に日次バッチが実行されること
     */
    public function test_毎日20時に日次バッチが実行される(): void
    {
        // Arrange
        $schedule = app(Schedule::class);

        // Act
        $scheduledEvent = collect($schedule->events())
            ->first(function (Event $event): bool {
                return str_contains(
                    $event->command ?? '',
                    'app:update-overdue-reading-plans'
                );
            });

        // Assert
        $this->assertNotNull(
            $scheduledEvent,
            '期限切れ更新コマンドがスケジュールに登録されていません。'
        );

        $this->assertSame(
            '0 20 * * *',
            $scheduledEvent->expression
        );
    }

    /**
     * テスト用の読書計画を作成する
     */
    private function createReadingPlan(
        string $targetDate,
        ReadingPlanStatus $status
    ): ReadingPlan {
        $user = User::factory()->create();

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
