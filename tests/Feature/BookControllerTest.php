<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 書籍を新規登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $bookData = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-22',
            'description' => 'テスト用の説明文です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $bookData);

        // Assert
        $book = Book::first();

        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas('success', '書籍を登録しました。');
    }

    /** @test */
    public function バリデーションエラー時は書籍登録画面へリダイレクトされ、エラーが返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        $invalidBookData = [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'description' => null,
            'image_url' => null,
            'genres' => [],
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $invalidBookData);

        // Assert
        $response->assertRedirect(route('books.create'));

        $response->assertSessionHasErrors([
            'title',
            'author',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    /** @test */
    public function 書籍情報を更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '更新前のタイトル',
            'author' => '更新前の著者',
            'isbn' => '1234567890123',
        ]);

        $oldGenre = Genre::factory()->create();
        $newGenres = Genre::factory()->count(2)->create();

        $book->genres()->attach($oldGenre->id);

        $updateBookData = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-23',
            'description' => '更新後の説明文です。',
            'image_url' => 'https://example.com/updated-book.jpg',
            'genres' => $newGenres->pluck('id')->toArray(),
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $updateBookData);

        // Assert
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '1234567890123',
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenres[0]->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenres[1]->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍情報を更新しました。'
        );
    }

    /** @test */
    public function バリデーションエラー時は書籍編集画面へリダイレクトされ、エラーが返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $invalidBookData = [
            'title' => '',
            'author' => '更新後の著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-07-23',
            'description' => '更新後の説明文です。',
            'image_url' => 'https://example.com/updated-book.jpg',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->from(route('books.edit', $book))
            ->put(route('books.update', $book), $invalidBookData);

        // Assert
        $response->assertRedirect(route('books.edit', $book));

        $response->assertSessionHasErrors([
            'title',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
        ]);
    }

    /** @test */
    public function 書籍情報を削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->delete(route('books.destroy', $book));

        // Assert
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $response->assertRedirect(route('books.index'));

        $response->assertSessionHas(
            'success',
            '書籍を削除しました。'
        );
    }

    /** @test */
    public function 書籍作成者以外は書籍を削除できない(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    /** @test */
    public function 存在しない書籍_i_dにアクセスした場合404が返る(): void
    {
        // Arrange
        $user = User::factory()->create();

        $notFoundBookId = 999999;

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.show', $notFoundBookId));

        // Assert
        $response->assertNotFound();

    }
}
