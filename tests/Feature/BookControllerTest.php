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

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

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
    public function 書籍作成者以外は書籍を編集できない(): void
    {
        // Arrange
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
        ]);

        $genre = Genre::factory()->create();

        $updateData = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this
            ->actingAs($otherUser)
            ->put(route('books.update', $book), $updateData);

        // Assert
        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'created_by' => $owner->id,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);
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

    /** @test */
    public function タイトルで検索できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田',
        ]);

        Book::factory()->create([
            'title' => 'Python入門',
            'author' => '佐藤',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index', [
                'keyword' => 'Laravel',
            ]));

        // Assert
        $response->assertOk();

        $response->assertSee('Laravel入門');
        $response->assertDontSee('Python入門');
    }

    /** @test */
    public function 著者名で検索できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '佐藤花子',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index', [
                'keyword' => '山田',
            ]));

        // Assert
        $response->assertOk();

        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP入門');
    }

    /** @test */
    public function ジャンルで書籍を絞り込みできる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '小説のテスト書籍',
            'author' => '小説著者',
        ]);

        $technicalBook = Book::factory()->create([
            'title' => '技術書のテスト書籍',
            'author' => '技術書著者',
        ]);

        $novelBook->genres()->attach($novelGenre->id);
        $technicalBook->genres()->attach($technicalGenre->id);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index', [
                'genre' => $novelGenre->id,
            ]));

        // Assert
        $response->assertOk();

        $response->assertSee('小説のテスト書籍');
        $response->assertDontSee('技術書のテスト書籍');
    }

    /** @test */
    public function キーワードとジャンルを組み合わせて検索できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $matchedBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $differentKeywordBook = Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '佐藤花子',
        ]);

        $differentGenreBook = Book::factory()->create([
            'title' => 'Laravel実践',
            'author' => '鈴木一郎',
        ]);

        $matchedBook->genres()->attach($technicalGenre->id);
        $differentKeywordBook->genres()->attach($technicalGenre->id);
        $differentGenreBook->genres()->attach($novelGenre->id);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index', [
                'keyword' => 'Laravel',
                'genre' => $technicalGenre->id,
            ]));

        // Assert
        $response->assertOk();

        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP入門');
        $response->assertDontSee('Laravel実践');
    }

    /** @test */
    public function 検索条件を指定しない場合はすべての書籍を表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP入門',
            'author' => '佐藤花子',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index'));

        // Assert
        $response->assertOk();

        $response->assertSee('Laravel入門');
        $response->assertSee('PHP入門');
    }

    /** @test */
    public function 書籍を新しい順に表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index', [
                'sort' => 'newest',
            ]));

        // Assert
        $response->assertOk();

        $response->assertViewHas('books', function ($books) use (
            $newBook,
            $oldBook
        ) {
            return $books->pluck('id')->values()->all() === [
                $newBook->id,
                $oldBook->id,
            ];
        });
    }

    /** @test */
    public function 書籍を古い順に表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $newBook = Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index', [
                'sort' => 'oldest',
            ]));

        // Assert
        $response->assertOk();

        $response->assertViewHas('books', function ($books) use (
            $oldBook,
            $newBook
        ) {
            return $books->pluck('id')->values()->all() === [
                $oldBook->id,
                $newBook->id,
            ];
        });
    }

    /** @test */
    public function 書籍をタイトル順に表示できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $bookC = Book::factory()->create([
            'title' => 'C Book',
        ]);

        $bookA = Book::factory()->create([
            'title' => 'A Book',
        ]);

        $bookB = Book::factory()->create([
            'title' => 'B Book',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.index', [
                'sort' => 'title',
            ]));

        // Assert
        $response->assertOk();

        $response->assertViewHas('books', function ($books) use (
            $bookA,
            $bookB,
            $bookC
        ) {
            return $books->pluck('id')->values()->all() === [
                $bookA->id,
                $bookB->id,
                $bookC->id,
            ];
        });
    }

    /** @test */
    public function isbnと出版日と説明を未入力でも書籍を登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $bookData = [
            'title' => '任意項目なしの書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => 'http://example.com/book.jpg',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $bookData);

        // Assert
        $response->assertSessionHasNoErrors();
        $response->dumpHeaders();

        $this->assertDatabaseCount('books', 1);

        $book = Book::where('title', '任意項目なしの書籍')
            ->firstOrFail();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '任意項目なしの書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );
    }

    /** @test */
    public function タイトルと著者のみ入力でも書籍を登録できる(): void
    {
        // Arrange
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $bookData = [
            'title' => 'タイトルのみ必須',
            'author' => 'テスト著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->post(route('books.store'), $bookData);

        // Assert
        $response->assertSessionHasNoErrors();

        $book = Book::where('title', 'タイトルのみ必須')->firstOrFail();

        $this->assertDatabaseHas('books', [
            'title' => 'タイトルのみ必須',
            'author' => 'テスト著者',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍を登録しました。'
        );
    }

    /** @test */
    public function isbnと出版日と説明を未入力でも書籍を更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-01',
            'description' => '更新前の説明',
            'image_url' => 'https://example.com/before.jpg',
        ]);

        $genre = Genre::factory()->create();
        $book->genres()->sync([$genre->id]);

        $updateData = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => 'https://example.com/after.jpg',
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $updateData);

        // Assert
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => 'https://example.com/after.jpg',
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍情報を更新しました。'
        );
    }

    /** @test */
    public function タイトルと著者のみ入力でも書籍を更新できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => '更新前タイトル',
            'author' => '更新前著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-07-01',
            'description' => '更新前の説明',
            'image_url' => 'https://example.com/before.jpg',
        ]);

        $genre = Genre::factory()->create();
        $book->genres()->sync([$genre->id]);

        $updateData = [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ];

        // Act
        $response = $this
            ->actingAs($user)
            ->put(route('books.update', $book), $updateData);

        // Assert
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => null,
            'published_date' => null,
            'description' => null,
            'image_url' => null,
        ]);

        $response->assertRedirect(route('books.show', $book));

        $response->assertSessionHas(
            'success',
            '書籍情報を更新しました。'
        );
    }
}
