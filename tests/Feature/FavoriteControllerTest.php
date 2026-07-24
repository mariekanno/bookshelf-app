<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function ログイン済みユーザーが書籍をお気に入り登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        // Assert
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    /** @test */
    public function ログイン済みユーザーが書籍をお気に入り解除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        // Assert
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    /** @test */
    public function ログインユーザーがお気に入り登録した書籍のみ取得・表示できる(): void
    {
        $this->withoutVite();

        // Arrange
        $user = User::factory()->create();

        $favoriteBook = Book::factory()->create();

        $otherBook = Book::factory()->create();

        $user->favoriteBooks()->attach($favoriteBook->id);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        // Assert
        $response->assertOk();

        $response->assertViewIs('favorites.index');

        $response->assertViewHas('books', function ($books) use ($favoriteBook, $otherBook) {
            return $books->contains($favoriteBook)
            && ! $books->contains($otherBook);
        });
    }
}
