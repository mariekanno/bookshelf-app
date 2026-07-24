<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Like;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ユーザーから登録した書籍を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        // Act
        $books = $user->createdBooks;

        // Assert
        $this->assertTrue($books->contains($book));
    }

    /** @test */
    public function 書籍からユーザーを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        // Act
        $creator = $book->creator;

        // Assert
        $this->assertTrue($creator->is($user));
    }

    /** @test */
    public function 中間テーブルを介して、1つの書籍が複数のジャンルに紐づいている(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $genres = Genre::factory()->count(2)->create();

        $book->genres()->attach($genres->pluck('id'));

        // Act
        $bookGenres = $book->genres;

        // Assert
        $this->assertCount(2, $bookGenres);
        $this->assertTrue($bookGenres->contains($genres[0]));
        $this->assertTrue($bookGenres->contains($genres[1]));
    }

    /** @test */
    public function 書籍に紐づく複数のレビューを取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $reviews = Review::factory()->count(2)->create([
            'book_id' => $book->id,
        ]);

        // Act
        $bookReviews = $book->reviews;

        // Assert
        $this->assertCount(2, $bookReviews);
        $this->assertTrue($bookReviews->contains($reviews[0]));
        $this->assertTrue($bookReviews->contains($reviews[1]));
    }

    /** @test */
    public function 書籍に紐づくお気に入りを取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $favorites = Favorite::factory()->count(2)->create([
            'book_id' => $book->id,
        ]);

        // Act
        $bookFavorites = $book->favorites;

        // Assert
        $this->assertCount(2, $bookFavorites);
        $this->assertTrue($bookFavorites->contains($favorites[0]));
        $this->assertTrue($bookFavorites->contains($favorites[1]));
    }

    /** @test */
    public function 一つの書籍が複数のジャンルと同期できる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $oldGenre = Genre::factory()->create();
        $newGenres = Genre::factory()->count(2)->create();

        $book->genres()->attach($oldGenre->id);

        // Act
        $book->genres()->sync($newGenres->pluck('id'));

        $bookGenres = $book->genres;

        // Assert
        $this->assertCount(2, $bookGenres);
        $this->assertFalse($bookGenres->contains($oldGenre));
        $this->assertTrue($bookGenres->contains($newGenres[0]));
        $this->assertTrue($bookGenres->contains($newGenres[1]));
    }

    /** @test */
    public function 中間テーブルを介して、1つのジャンルが複数の書籍に紐づいている(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $books = Book::factory()->count(2)->create();

        $genre->books()->attach($books->pluck('id'));

        // Act
        $genreBooks = $genre->books;

        // Assert
        $this->assertCount(2, $genreBooks);
        $this->assertTrue($genreBooks->contains($books[0]));
        $this->assertTrue($genreBooks->contains($books[1]));
    }

    /** @test */
    public function レビューから投稿者を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $reviewUser = $review->user;

        // Assert
        $this->assertTrue($reviewUser->is($user));
    }

    /** @test */
    public function レビューから書籍を取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        // Act
        $reviewBook = $review->book;

        // Assert
        $this->assertTrue($reviewBook->is($book));
    }

    /** @test */
    public function お気に入りから登録者を取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $favorite = Favorite::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $favoriteUser = $favorite->user;

        // Assert
        $this->assertTrue($favoriteUser->is($user));
    }

    /** @test */
    public function お気に入りから書籍を取得できる(): void
    {
        // Arrange
        $book = Book::factory()->create();

        $favorite = Favorite::factory()->create([
            'book_id' => $book->id,
        ]);

        // Act
        $favoriteBook = $favorite->book;

        // Assert
        $this->assertTrue($favoriteBook->is($book));
    }

    /** @test */
    public function いいねからユーザーを取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $like = Like::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $likeUser = $like->user;

        // Assert
        $this->assertTrue($likeUser->is($user));
    }

    /** @test */
    public function いいねからレビューを取得できる(): void
    {
        // Arrange
        $review = Review::factory()->create();

        $like = Like::factory()->create([
            'review_id' => $review->id,
        ]);

        // Act
        $likeReview = $like->review;

        // Assert
        $this->assertTrue($likeReview->is($review));
    }
}
