<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

        Sanctum::actingAs($user);

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
        $user = User::factory()->create();

        Sanctum::actingAs($user);

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

        Sanctum::actingAs($user);

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

        Sanctum::actingAs($user);

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

        Sanctum::actingAs($user);

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

        Sanctum::actingAs($user);

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
        // Arrange
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        // Act
        $response = $this->deleteJson('/api/v1/books/999999');

        // Assert
        $response->assertNotFound();

        $response->assertJson([
            'message' => '書籍が見つかりませんでした。',
        ]);
    }

    /** @test */
    public function sanctum認証済みなら書籍登録_apiを実行できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $genre = Genre::factory()->create();

        $bookData = [
            'title' => 'Sanctumテスト',
            'author' => 'テスト著者',
            'isbn' => '9999999999999',
            'published_date' => '2026-08-06',
            'description' => 'テストです。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
            'created_by' => $user->id,
        ];

        // Act
        $response = $this->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertCreated();

        $this->assertDatabaseHas('books', [
            'title' => 'Sanctumテスト',
        ]);
    }

    /** @test */
    public function sanctum未認証では401を返す(): void
    {
        // Arrange
        $genre = Genre::factory()->create();

        $bookData = [
            'title' => 'テスト',
            'author' => '著者',
            'isbn' => '8888888888888',
            'published_date' => '2026-08-06',
            'description' => 'テスト',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
            'created_by' => 1,
        ];

        // Act
        $response = $this->postJson('/api/v1/books', $bookData);

        // Assert
        $response->assertUnauthorized();
    }

    /** @test */
    public function 他人の書籍は更新できず403を返す(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($otherUser);

        $book = Book::factory()->create([
            'title' => '更新前のタイトル',
            'author' => '更新前の著者',
            'created_by' => $owner->id,
        ]);

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $updateData = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-08-06',
            'description' => '更新後説明',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
            'created_by' => $owner->id,
        ];

        // Act
        $response = $this->putJson(
            "/api/v1/books/{$book->id}",
            $updateData
        );

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前のタイトル',
            'author' => '更新前の著者',
            'created_by' => $owner->id,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
        ]);
    }
}
