<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * ログインユーザーがマイ読書レポートを表示できること
     */
    public function test_ログインユーザーがマイ読書レポートを表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewIs('reports.index')
            ->assertViewHas('stats');
    }

    /**
     * ログインユーザーのレビューをもとに統計情報を集計できること
     */
    public function test_ログインユーザーの統計情報を正しく集計できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstBook = Book::factory()->create();
        $secondBook = Book::factory()->create();
        $thirdBook = Book::factory()->create();
        $otherUserBook = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $firstBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $secondBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $thirdBook->id,
            'rating' => 3,
        ]);

        // 他ユーザーのレビューは集計対象外
        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherUserBook->id,
            'rating' => 1,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reports.index'));

        // Assert
        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                $summary = $stats['summary'];

                $this->assertSame(3, $summary['total_reviews']);
                $this->assertSame(3, $summary['books_read']);
                $this->assertEquals(4, $summary['average_rating']);

                return true;
            });
    }
}
