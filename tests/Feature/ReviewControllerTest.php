<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン済みユーザーがレビュー投稿できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $reviewData = [
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), $reviewData);

        // Assert
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを投稿しました。'
        );
    }

    /** @test */
    public function レビュー投稿のバリデーションエラー時は書籍詳細画面へリダイレクトされ、エラーが返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $invalidReviewData = [
            'rating' => 999,
            'comment' => 'とても面白い本でした。',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), $invalidReviewData);

        // Assert
        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHasErrors([
            'rating',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'rating' => 999,
        ]);
    }

    /** @test */
    public function レビュー更新のバリデーションエラー時はレビュー編集画面へリダイレクトされ、エラーが返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '更新前のレビューです。',
        ]);

        $invalidReviewData = [
            'rating' => 999,
            'comment' => '更新後のレビューです。',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), $invalidReviewData);

        // Assert
        $response->assertRedirect(route('reviews.edit', $review));

        $response->assertSessionHasErrors([
            'rating',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '更新前のレビューです。',
        ]);
    }

    /** @test */
    public function レビュー投稿者はレビューを更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        $updateData = [
            'rating' => 5,
            'comment' => '更新後のレビューです。',
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), $updateData);

        // Assert
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => '更新後のレビューです。',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを更新しました。'
        );
    }

    /** @test */
    public function レビュー投稿者以外はレビューを更新できない(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        $updateData = [
            'rating' => 5,
            'comment' => '不正に更新されたレビューです。',
        ];

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->put(route('reviews.update', $review), $updateData);

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前のレビューです。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '不正に更新されたレビューです。',
        ]);
    }

    /** @test */
    public function レビュー投稿者はレビューを削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        // Assert
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            'レビューを削除しました。'
        );
    }

    /** @test */
    public function レビュー投稿者以外はレビューを削除できない(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->delete(route('reviews.destroy', $review));

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);
    }
}
