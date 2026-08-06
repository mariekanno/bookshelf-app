<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * 読書計画を登録できること
     */
    public function test_読書計画を登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $targetDate = now()->addWeek()->toDateString();

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => $targetDate,
            ]);

        // Assert
        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas(
                'success',
                '読書計画を作成しました。'
            );

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $readingPlan = ReadingPlan::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->firstOrFail();

        $this->assertSame(
            $targetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    /**
     * 読書計画の期日を編集できること
     */
    public function test_読書計画の期日を編集できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newTargetDate = now()->addMonth()->toDateString();

        // Act
        $response = $this
            ->actingAs($user)
            ->put(
                route('reading-plans.update', $readingPlan),
                [
                    'target_date' => $newTargetDate,
                ]
            );

        // Assert
        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas(
                'success',
                '読書計画を更新しました。'
            );

        $readingPlan->refresh();

        $this->assertSame(
            $newTargetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    /**
     * 読書計画登録時のステータスが進行中になること
     */
    public function test_読書計画登録時のステータスが進行中になる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => now()->addWeek()->toDateString(),
            ]);

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    /**
     * 進行中の読書計画を読了に変更できること
     */
    public function test_進行中の読書計画を読了に変更できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(
                route('reading-plans.complete', $readingPlan)
            );

        // Assert
        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas(
                'success',
                '読書計画を完了しました。'
            );

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    /**
     * 期限切れの読書計画を読了に変更できること
     */
    public function test_期限切れの読書計画を読了に変更できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->subWeek()->toDateString(),
            'status' => ReadingPlanStatus::Overdue,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(
                route('reading-plans.complete', $readingPlan)
            );

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
        ]);
    }

    /**
     * 読了操作時に読了日が自動登録されること
     */
    public function test_読了操作時に読了日が自動登録される(): void
    {
        // Arrange
        Carbon::setTestNow(
            Carbon::create(2026, 8, 5, 14, 30, 0)
        );

        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->toDateString(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->post(
                route('reading-plans.complete', $readingPlan)
            );

        // Assert
        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'status' => ReadingPlanStatus::Completed->value,
            'completed_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
