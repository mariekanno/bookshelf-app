<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン済みユーザーがレビューにいいねできる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        // Assert
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    /** @test */
    public function ログイン済みユーザーがレビューのいいねを解除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $user->likedReviews()->attach($review->id);

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        // Assert
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }
}
