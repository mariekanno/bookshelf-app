<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 書籍一覧_ap_iを取得できる(): void
    {
        // Arrange
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $book->genres()->attach($genre->id);

        // Act
        $response = $this->getJson('/api/v1/books');

        // Assert
        $response->assertOk();

        $response->assertJsonFragment([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'image_url',
                    'description',
                    'genres',
                    'review_count',
                    'created_at',
                ],
            ],
            'meta',
        ]);
    }

    /** @test */
    public function 書籍詳細_ap_iを取得できる(): void
    {
        // Arrange
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $book->genres()->attach($genre->id);

        // Act
        $response = $this->getJson("/api/v1/books/{$book->id}");

        // Assert
        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $book->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'image_url',
                'description',
                'genres',
                'review_count',
                'reviews',
                'created_at',
            ],
        ]);
    }

    /** @test */
    public function 書籍登録_ap_iで書籍を登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $bookData = [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-22',
            'description' => 'テスト用の説明文です。',
            'image_url' => 'https://example.com/book.jpg',
            'pages' => 300,
            'genres' => [$genre->id],
            'created_by' => $user->id,
        ];

        // Act
        $response = $this->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
        ]);

        $book = Book::where('isbn', '1234567890123')->firstOrFail();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertJsonFragment([
            'id' => $book->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'image_url',
                'description',
                'genres',
                'review_count',
                'created_at',
            ],
        ]);
    }

    /** @test */
    public function 書籍登録_ap_iのバリデーション時は422を返す(): void
    {
        // Arrange
        $invalidBookData = [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'created_by' => '',
        ];

        // Act
        $response = $this->postJson('/api/v1/books', $invalidBookData);

        // Assert
        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'created_by',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    /** @test */
    public function 書籍更新_ap_iで書籍を更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '更新前のタイトル',
            'author' => '更新前の著者',
            'created_by' => $user->id,
        ]);

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $newGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $book->genres()->attach($genre->id);

        $updateData = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-23',
            'description' => '更新後の説明文です。',
            'image_url' => 'https://example.com/updated-book.jpg',
            'genres' => [$newGenre->id],
            'created_by' => $user->id,
        ];

        // Act
        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $updateData
        );

        // Assert
        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'description' => '更新後の説明文です。',
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);

        $response->assertJsonFragment([
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
        ]);
    }

    /** @test */
    public function 書籍更新_ap_iのバリデーション時は422を返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '更新前のタイトル',
            'author' => '更新前の著者',
            'created_by' => $user->id,
        ]);

        $invalidUpdateData = [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'created_by' => '',
        ];

        // Act
        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $invalidUpdateData
        );

        // Assert
        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'created_by',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前のタイトル',
            'author' => '更新前の著者',
            'created_by' => $user->id,
        ]);
    }

    /** @test */
    public function 存在しない書籍更新_ap_iは404を返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        $updateData = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-23',
            'description' => '更新後の説明文です。',
            'image_url' => 'https://example.com/updated-book.jpg',
            'pages' => 300,
            'created_by' => $user->id,
        ];

        // Act
        $response = $this->putJson(
            '/api/v1/books/99999',
            $updateData
        );

        // Assert
        $response->assertNotFound();

        $response->assertJson([
            'message' => '書籍が見つかりませんでした。',
        ]);
    }

    /** @test */
    public function 書籍削除_ap_iで書籍を削除できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
        ]);

        // Act
        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        // Assert
        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    /** @test */
    public function 存在しない書籍削除_ap_iは404を返す(): void
    {
        // Act
        $response = $this->deleteJson('/api/v1/books/999999');

        // Assert
        $response->assertNotFound();

        $response->assertJson([
            'message' => '書籍が見つかりませんでした。',
        ]);
    }
}
