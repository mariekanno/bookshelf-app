<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GoogleBooksService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksServiceTest extends TestCase
{
    use RefreshDatabase;

    private const ISBN = '9784101010014';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config([
            'services.google_books.api_key' => 'test-api-key',
        ]);
    }

    /**
     * Google Books APIのレスポンスから書籍情報を取得できること
     */
    public function test_google_books_apiから書籍情報を取得できる(): void
    {
        // Arrange
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response($this->googleBooksResponse(), 200),
        ]);

        $service = app(GoogleBooksService::class);

        // Act
        $result = $service->searchByIsbn(self::ISBN);

        // Assert
        $this->assertSame([
            'title' => 'テスト書籍',
            'author' => '山田太郎, 佐藤花子',
            'published_date' => '2026-08-01',
            'description' => 'テスト書籍の説明です。',
            'image_url' => 'https://example.com/test-book.jpg',
        ], $result);

        Http::assertSent(function ($request) {
            return str_starts_with(
                $request->url(),
                'https://www.googleapis.com/books/v1/volumes'
            )
                && $request['q'] === 'isbn:'.self::ISBN
                && $request['key'] === 'test-api-key';
        });
    }

    /**
     * ISBN検索結果を書籍登録フォーム用のJSONとして取得できること
     */
    public function test_isbn検索結果を書籍登録フォーム用のjsonとして取得できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response($this->googleBooksResponse(), 200),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => self::ISBN,
            ]));

        // Assert
        $response
            ->assertOk()
            ->assertJson([
                'title' => 'テスト書籍',
                'author' => '山田太郎, 佐藤花子',
                'published_date' => '2026-08-01',
                'description' => 'テスト書籍の説明です。',
                'image_url' => 'https://example.com/test-book.jpg',
            ]);

        Http::assertSentCount(1);
    }

    /**
     * 検索結果が0件の場合にエラーメッセージを返すこと
     */
    public function test_isbn検索結果が0件の場合にエラーメッセージを返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => self::ISBN,
            ]));

        // Assert
        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);

        Http::assertSentCount(1);
    }

    /**
     * 外部APIがエラーを返した場合にエラーを処理できること
     */
    public function test_google_books_apiがエラーを返した場合にエラーを処理できる(): void
    {
        // Arrange
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'error' => [
                    'message' => 'Internal Server Error',
                ],
            ], 500),
        ]);

        // Act
        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => self::ISBN,
            ]));

        // Assert
        $response
            ->assertNotFound()
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);

        Http::assertSentCount(1);
    }

    /**
     * 13桁以外のISBNを指定した場合に422を返すこと
     */
    public function test_isbnが13桁でない場合に422を返す(): void
    {
        // Arrange
        $user = User::factory()->create();

        Http::fake();

        $invalidIsbn = '1234567890';

        // Act
        $response = $this
            ->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => $invalidIsbn,
            ]));

        // Assert
        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);

        Http::assertNothingSent();
    }

    /**
     * Google Books APIの正常レスポンス
     *
     * @return array<string, mixed>
     */
    private function googleBooksResponse(): array
    {
        return [
            'totalItems' => 1,
            'items' => [
                [
                    'volumeInfo' => [
                        'title' => 'テスト書籍',
                        'authors' => [
                            '山田太郎',
                            '佐藤花子',
                        ],
                        'publishedDate' => '2026-08-01',
                        'description' => 'テスト書籍の説明です。',
                        'imageLinks' => [
                            'thumbnail' => 'https://example.com/test-book.jpg',
                        ],
                    ],
                ],
            ],
        ];
    }
}
