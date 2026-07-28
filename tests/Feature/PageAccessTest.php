<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /** @test */
    public function 書籍一覧画面が正常に表示される(): void
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
        $response = $this->get(route('books.index'));

        // Assert
        $response->assertStatus(200);

        $response->assertSee('テスト書籍');
        $response->assertSee('テスト著者');
        $response->assertSee('小説');
    }

    /** @test */
    public function 書籍一覧は10件ずつ表示され複数ページを閲覧できる(): void
    {
        // Arrange
        for ($i = 1; $i <= 11; $i++) {
            Book::factory()->create([
                'title' => sprintf('テスト書籍%02d', $i),
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }

        // Act
        $firstPageResponse = $this->get(route('books.index'));

        // Assert
        $firstPageResponse->assertStatus(200);
        $firstPageResponse->assertSee('テスト書籍11');
        $firstPageResponse->assertSee('テスト書籍02');
        $firstPageResponse->assertDontSee('テスト書籍01');

        // Act
        $secondPageResponse = $this->get(route('books.index', [
            'page' => 2,
        ]));

        // Assert
        $secondPageResponse->assertStatus(200);
        $secondPageResponse->assertSee('テスト書籍01');
        $secondPageResponse->assertDontSee('テスト書籍11');
    }

    /** @test */
    public function 書籍詳細画面が正常に表示される(): void
    {
        // Arrange
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $book->genres()->attach($genre);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本です。',
        ]);

        // Act
        $response = $this->get(route('books.show', $book));

        // Assert
        $response->assertStatus(200);

        $response->assertSee('テスト書籍');
        $response->assertSee('テスト著者');
        $response->assertSee('小説');
        $response->assertSee('とても面白い本です。');
    }

    /** @test */
    public function 書籍登録画面が正常に表示される(): void
    {
        // Arrange
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        // Act
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('books.create'));

        // Assert
        $response->assertStatus(200);

        $response->assertViewHas('genres', function ($genres) use ($genre) {
            return $genres->contains('id', $genre->id);
        });

        $response->assertSee('小説');
    }

    /** @test */
    public function 書籍作成者のみ書籍編集画面が表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create([
            'created_by' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $book->genres()->attach($genre);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('books.edit', $book));

        // Assert
        $response->assertStatus(200);

        $response->assertViewHas('book', function ($viewBook) use ($book) {
            return $viewBook->id === $book->id;
        });

        $response->assertViewHas('genres', function ($genres) use ($genre) {
            return $genres->contains('id', $genre->id);
        });

        $response->assertSee('テスト書籍');
        $response->assertSee('テスト著者');
        $response->assertSee('小説');
    }

    /** @test */
    public function 書籍作成者以外は書籍編集画面にアクセスできない(): void
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
            ->get(route('books.edit', $book));

        // Assert
        $response->assertForbidden();
    }

    /** @test */
    public function ジャンル一覧画面が正常に表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '小説',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee('小説');
    }

    /** @test */
    public function ジャンル詳細画面が正常に表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create([
            'title' => 'テスト書籍',
        ]);

        $book->genres()->attach($genre);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        // Assert
        $response->assertStatus(200);

        $response->assertSee('小説');
        $response->assertSee('テスト書籍');
    }

    /** @test */
    public function ジャンル詳細画面の書籍は10件ずつ表示され複数ページを閲覧できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        for ($i = 1; $i <= 11; $i++) {
            $book = Book::factory()->create([
                'title' => sprintf('テスト書籍%02d', $i),
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);

            $book->genres()->attach($genre);
        }

        // Act
        $firstPageResponse = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        // Assert
        $firstPageResponse->assertStatus(200);

        $firstPageResponse->assertViewHas('books', function ($books) {
            return $books->count() === 10
            && $books->currentPage() === 1
            && $books->lastPage() === 2;
        });

        $firstPageResponse->assertSee('テスト書籍11');
        $firstPageResponse->assertDontSee('テスト書籍01');

        // Act
        $secondPageResponse = $this
            ->actingAs($user)
            ->get(route('genres.show', [
                'genre' => $genre,
                'page' => 2,
            ]));

        // Assert
        $secondPageResponse->assertStatus(200);

        $secondPageResponse->assertViewHas('books', function ($books) {
            return $books->count() === 1
            && $books->currentPage() === 2
            && $books->lastPage() === 2;
        });

        $secondPageResponse->assertSee('テスト書籍01');
        $secondPageResponse->assertDontSee('テスト書籍11');
    }

    /** @test */
    public function ジャンル登録画面が正常に表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.create'));

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function ジャンル編集画面が正常に表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('genres.edit', $genre));

        // Assert
        $response->assertStatus(200);

        $response->assertViewHas('genre', function ($viewGenre) use ($genre) {
            return $viewGenre->id === $genre->id;
        });

        $response->assertSee('小説');
    }

    /** @test */
    public function レビュー投稿者のみレビュー編集画面が表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本です。',
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('reviews.edit', $review));

        // Assert
        $response->assertStatus(200);

        $response->assertViewHas('review', function ($viewReview) use ($review) {
            return $viewReview->id === $review->id;
        });

        $response->assertSee('5');
        $response->assertSee('とても面白い本です。');
    }

    /** @test */
    public function レビュー投稿者以外はレビュー編集画面にアクセスできない(): void
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
            ->get(route('reviews.edit', $review));

        // Assert
        $response->assertForbidden();
    }

    /** @test */
    public function お気に入り一覧画面が正常に表示される(): void
    {
        // Arrange
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'テスト書籍',
        ]);

        $user->favorites()->create([
            'book_id' => $book->id,
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        // Assert
        $response->assertStatus(200);

        $response->assertSee('テスト書籍');
    }

    /** @test */
    public function お気に入り一覧画面の書籍は10件ずつ表示され複数ページを閲覧できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        for ($i = 1; $i <= 11; $i++) {
            $book = Book::factory()->create([
                'title' => sprintf('テスト書籍%02d', $i),
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);

            $user->favorites()->create([
                'book_id' => $book->id,
            ]);
        }

        // Act
        $firstPageResponse = $this
            ->actingAs($user)
            ->get(route('favorites.index'));

        // Assert
        $firstPageResponse->assertStatus(200);

        $firstPageResponse->assertViewHas('books', function ($books) {
            return $books->count() === 10
            && $books->currentPage() === 1
            && $books->lastPage() === 2;
        });

        // Act
        $secondPageResponse = $this
            ->actingAs($user)
            ->get(route('favorites.index', [
                'page' => 2,
            ]));

        // Assert
        $secondPageResponse->assertStatus(200);

        $secondPageResponse->assertViewHas('books', function ($books) {
            return $books->count() === 1
            && $books->currentPage() === 2
            && $books->lastPage() === 2;
        });
    }

    /** @test */
    public function ランキング画面が正常に表示される(): void
    {
        // Arrange
        $book = Book::factory()->create([
            'title' => 'テスト書籍',
        ]);

        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $user->favorites()->create([
                'book_id' => $book->id,
            ]);
        }

        // Act
        $response = $this->get(route('ranking.index'));

        // Assert
        $response->assertStatus(200);

        $response->assertSee('テスト書籍');
    }

    /** @test */
    public function 未ログインユーザーは認証必須画面へアクセスできない(): void
    {
        // Arrange
        $owner = User::factory()->create();

        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'created_by' => $owner->id,
        ]);

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        // Act・Assert
        $this->get(route('books.create'))
            ->assertRedirect(route('login'));

        $this->get(route('books.edit', $book))
            ->assertRedirect(route('login'));

        $this->get(route('genres.index'))
            ->assertRedirect(route('login'));

        $this->get(route('genres.show', $genre))
            ->assertRedirect(route('login'));

        $this->get(route('genres.create'))
            ->assertRedirect(route('login'));

        $this->get(route('genres.edit', $genre))
            ->assertRedirect(route('login'));

        $this->get(route('reviews.edit', $review))
            ->assertRedirect(route('login'));

        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }
}
